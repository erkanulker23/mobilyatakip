<?php

namespace App\Http\Controllers;

use App\Exports\SuppliersExport;
use App\Imports\SuppliersImport;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\QuoteItem;
use App\Models\ServicePart;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Rules\TurkishTaxId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = Supplier::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('taxNumber', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $suppliers = $q->paginate(20)->withQueryString();
        $supplierIds = $suppliers->getCollection()->pluck('id')->values()->all();

        // Tedarikçi borç (alış toplamı) ve alacak (ödeme toplamı)
        $borcBySupplier = [];
        $alacakBySupplier = [];
        if (!empty($supplierIds)) {
            $borcBySupplier = Purchase::whereIn('supplierId', $supplierIds)
                ->where('isCancelled', false)
                ->selectRaw('supplierId, sum(grandTotal) as total')
                ->groupBy('supplierId')
                ->pluck('total', 'supplierId')
                ->map(fn ($v) => (float) $v)
                ->all();
            $alacakBySupplier = SupplierPayment::whereIn('supplierId', $supplierIds)
                ->selectRaw('supplierId, sum(amount) as total')
                ->groupBy('supplierId')
                ->pluck('total', 'supplierId')
                ->map(fn ($v) => (float) $v)
                ->all();
        }

        return view('suppliers.index', compact('suppliers', 'supplierIds', 'borcBySupplier', 'alacakBySupplier'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'address' => 'nullable|string',
            'taxNumber' => ['nullable', 'string', 'max:50', new TurkishTaxId('vkn')],
            'taxOffice' => 'nullable|string|max:255',
        ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi kaydedildi.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases.items.product', 'products', 'payments.purchase']);
        return view('suppliers.show', compact('supplier'));
    }

    public function print(Supplier $supplier)
    {
        $supplier->load(['purchases.items.product', 'payments']);
        return view('suppliers.print', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'address' => 'nullable|string',
            'taxNumber' => ['nullable', 'string', 'max:50', new TurkishTaxId('vkn')],
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'nullable|boolean',
        ], ['phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)']);
        $validated['isActive'] = $request->boolean('isActive');
        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi güncellendi.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        if ($request->boolean('delete_products')) {
            $this->deleteSupplierProductsAndDependents($supplier);
        } else {
            $supplier->products()->update(['supplierId' => null]);
        }
        $this->detachSupplierDependents($supplier);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi silindi.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'required|uuid|exists:suppliers,id', 'delete_products' => 'nullable|boolean']);
        $deleteProducts = $request->boolean('delete_products');
        $count = 0;
        foreach ($request->ids as $id) {
            $supplier = Supplier::find($id);
            if (!$supplier) {
                continue;
            }
            if ($deleteProducts) {
                $this->deleteSupplierProductsAndDependents($supplier);
            } else {
                $supplier->products()->update(['supplierId' => null]);
            }
            $this->detachSupplierDependents($supplier);
            $supplier->delete();
            $count++;
        }
        return redirect()->route('suppliers.index')->with('success', $count . ' tedarikçi silindi.');
    }

    /**
     * Tedarikçiyi silmeden önce alımlar, ödemeler ve diğer bağları kaldırır (FK ihlalini önler).
     */
    private function detachSupplierDependents(Supplier $supplier): void
    {
        $id = $supplier->getKey();
        $purchaseIds = DB::table('purchases')->where('supplierId', $id)->pluck('id');
        if ($purchaseIds->isNotEmpty()) {
            DB::table('supplier_payments')->whereIn('purchaseId', $purchaseIds)->update(['purchaseId' => null]);
            DB::table('purchase_items')->whereIn('purchaseId', $purchaseIds)->delete();
            DB::table('purchases')->where('supplierId', $id)->delete();
        }
        DB::table('supplier_payments')->where('supplierId', $id)->delete();
        DB::table('supplier_statements')->where('supplierId', $id)->delete();
        DB::table('xml_feeds')->where('supplierId', $id)->update(['supplierId' => null]);
    }

    /**
     * Tedarikçiye ait ürünleri ve bu ürünlere bağlı kayıtları (teklif kalemleri, stok, vb.)
     * foreign key kısıtları nedeniyle sırayla siler.
     */
    private function deleteSupplierProductsAndDependents(Supplier $supplier): void
    {
        $productIds = $supplier->products()->pluck('id')->all();
        if (empty($productIds)) {
            return;
        }

        QuoteItem::whereIn('productId', $productIds)->delete();
        PurchaseItem::whereIn('productId', $productIds)->delete();
        ServicePart::whereIn('productId', $productIds)->delete();
        StockMovement::whereIn('productId', $productIds)->delete();
        Stock::whereIn('productId', $productIds)->delete();
        Product::whereIn('id', $productIds)->delete();
    }

    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(new SuppliersExport, 'tedarikciler-' . date('Y-m-d') . '.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new SuppliersImport, $request->file('file'));
            return redirect()->route('suppliers.index')->with('success', 'Excel dosyası başarıyla içe aktarıldı.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $msg = collect($e->failures())->map(fn ($f) => 'Satır ' . $f->row() . ': ' . implode(', ', $f->errors()))->implode('; ');
            return redirect()->route('suppliers.index')->with('error', 'İçe aktarma hatası: ' . $msg);
        }
    }
}
