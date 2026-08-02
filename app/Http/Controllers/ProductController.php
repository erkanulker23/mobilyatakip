<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\QuoteItem;
use App\Models\ServicePart;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Support\ProductImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $q = Product::query()->with('supplier')->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }
        if ($request->filled('supplierId')) {
            $q->where('supplierId', $request->supplierId);
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        if ($request->filled('minPrice')) {
            $q->where('unitPrice', '>=', $request->minPrice);
        }
        if ($request->filled('maxPrice')) {
            $q->where('unitPrice', '<=', $request->maxPrice);
        }
        $products = $q->paginate(20)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();
        $productIds = $products->getCollection()->pluck('id')->values()->all();
        return view('products.index', compact('products', 'suppliers', 'productIds'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('products.create', compact('suppliers'));
    }

    public function quickStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'unitPrice' => 'required|numeric|min:0',
                'kdvRate' => 'nullable|numeric|min:0|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }
        $validated['kdvRate'] = $validated['kdvRate'] ?? 10;
        $product = Product::create([
            'name' => $validated['name'],
            'unitPrice' => (float) $validated['unitPrice'],
            'kdvRate' => (float) $validated['kdvRate'],
        ]);
        $this->auditService->logCreate('product', $product->id, ['name' => $product->name]);
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->unitPrice,
            'kdv' => (float) $product->kdvRate,
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeMoneyRequest($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unitPrice' => 'required|numeric|min:0',
            'netPurchasePrice' => 'nullable|numeric|min:0',
            'kdvRate' => 'nullable|numeric|min:0|max:100',
            'supplierId' => 'nullable|exists:suppliers,id',
            'minStockLevel' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);
        $validated['kdvRate'] = $validated['kdvRate'] ?? 10;
        unset($validated['images']);
        $validated['images'] = $this->storeUploadedImages($request);
        if ($request->hasFile('images') && $validated['images'] === []) {
            return back()->withInput()->with('error', 'Resim yüklenemedi. Dosya en az 32×32 piksel ve 1 KB olmalıdır.');
        }
        $product = Product::create($validated);
        $this->auditService->logCreate('product', $product->id, ['name' => $product->name]);
        return redirect()->route('products.index')->with('success', 'Ürün kaydedildi.');
    }

    public function show(Product $product)
    {
        $product->load(['supplier', 'stocks.warehouse']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('products.edit', compact('product', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $this->normalizeMoneyRequest($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unitPrice' => 'required|numeric|min:0',
            'netPurchasePrice' => 'nullable|numeric|min:0',
            'kdvRate' => 'nullable|numeric|min:0|max:100',
            'supplierId' => 'nullable|exists:suppliers,id',
            'minStockLevel' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'isActive' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);
        $validated['isActive'] = $request->boolean('isActive');
        unset($validated['images'], $validated['remove_images']);
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (! is_array($files)) {
                $files = [$files];
            }
            $hasFiles = count(array_filter($files, fn ($file) => $file && $file->isValid())) > 0;
            $newUploads = $this->storeUploadedImages($request);
            if ($hasFiles && $newUploads === []) {
                return back()->withInput()->with('error', 'Resim yüklenemedi. Dosya en az 32×32 piksel ve 1 KB olmalıdır.');
            }
        }
        $validated['images'] = $this->syncProductImages($product, $request);
        $oldData = ['name' => $product->name];
        $product->update($validated);
        $this->auditService->logUpdate('product', $product->id, $oldData, ['name' => $product->name]);

        return redirect()->route('products.show', $product)->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product)
    {
        $this->auditService->logDelete('product', $product->id, ['name' => $product->name]);
        $this->deleteProductsAndDependents([$product->id]);
        return redirect()->route('products.index')->with('success', 'Ürün silindi.');
    }

    public function bulkDestroy(Request $request)
    {
        if ($request->boolean('all_filtered')) {
            $q = Product::query();
            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                        ->orWhere('sku', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
            }
            if ($request->filled('supplierId')) {
                $q->where('supplierId', $request->supplierId);
            }
            if ($request->filled('isActive')) {
                $q->where('isActive', $request->boolean('isActive'));
            }
            if ($request->filled('minPrice')) {
                $q->where('unitPrice', '>=', $request->minPrice);
            }
            if ($request->filled('maxPrice')) {
                $q->where('unitPrice', '<=', $request->maxPrice);
            }
            $ids = $q->pluck('id')->all();
            $this->deleteProductsAndDependents($ids);
            return redirect()->route('products.index', $request->only(['search', 'supplierId', 'isActive', 'minPrice', 'maxPrice']))
                ->with('success', count($ids) . ' ürün silindi.');
        }

        $request->validate(['ids' => 'required|array', 'ids.*' => 'required|uuid|exists:products,id']);
        $ids = $request->ids;
        $this->deleteProductsAndDependents($ids);
        return redirect()->route('products.index')->with('success', count($ids) . ' ürün silindi.');
    }

    /**
     * Ürünleri ve bu ürünlere bağlı kayıtları (teklif kalemleri, alış kalemleri, stok vb.)
     * foreign key kısıtları nedeniyle sırayla siler.
     */
    private function deleteProductsAndDependents(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        foreach (Product::whereIn('id', $productIds)->get() as $product) {
            foreach ($this->productImages($product) as $image) {
                $this->deleteLocalProductImage($image);
            }
        }

        QuoteItem::whereIn('productId', $productIds)->delete();
        PurchaseItem::whereIn('productId', $productIds)->delete();
        ServicePart::whereIn('productId', $productIds)->delete();
        StockMovement::whereIn('productId', $productIds)->delete();
        Stock::whereIn('productId', $productIds)->delete();
        Product::whereIn('id', $productIds)->delete();
    }

    /** @return string[] */
    private function productImages(Product $product): array
    {
        return ProductImages::paths($product);
    }

    /** @return string[] */
    private function storeUploadedImages(Request $request, string $field = 'images'): array
    {
        $files = $request->file($field);
        if (! $files) {
            return [];
        }
        if (! is_array($files)) {
            $files = [$files];
        }

        $paths = [];
        foreach ($files as $file) {
            if (! $file || ! ProductImages::isValidUpload($file)) {
                continue;
            }
            $paths[] = '/storage/' . $file->store('products/' . date('Y-m-d'), 'public');
        }

        return $paths;
    }

    /** @return string[] */
    private function syncProductImages(Product $product, Request $request): array
    {
        $images = ProductImages::pruneInvalidPaths($this->productImages($product));
        $removedPaths = array_diff($this->productImages($product), $images);
        foreach ($removedPaths as $path) {
            $this->deleteLocalProductImage($path);
        }

        $remove = $request->input('remove_images', []);

        if (is_array($remove) && $remove !== []) {
            $images = array_values(array_filter($images, function ($image) use ($remove) {
                if (in_array($image, $remove, true)) {
                    $this->deleteLocalProductImage($image);
                    return false;
                }

                return true;
            }));
        }

        return array_values(array_merge($images, $this->storeUploadedImages($request)));
    }

    private function deleteLocalProductImage(?string $path): void
    {
        if (!$path || Str::startsWith($path, 'http')) {
            return;
        }

        $relative = ltrim(str_replace('/storage/', '', parse_url($path, PHP_URL_PATH) ?? ''), '/');
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function normalizeMoneyRequest(Request $request): void
    {
        if ($request->has('unitPrice')) {
            $request->merge(['unitPrice' => money_parse($request->input('unitPrice'))]);
        }
        if ($request->filled('netPurchasePrice')) {
            $request->merge(['netPurchasePrice' => money_parse($request->input('netPurchasePrice'))]);
        } elseif ($request->has('netPurchasePrice') && $request->input('netPurchasePrice') === '') {
            $request->merge(['netPurchasePrice' => null]);
        }
    }
}
