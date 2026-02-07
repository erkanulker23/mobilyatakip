<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProductController extends Controller
{
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
        return view('products.index', compact('products', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('products.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unitPrice' => 'required|numeric|min:0',
            'kdvRate' => 'nullable|numeric|min:0|max:100',
            'supplierId' => 'nullable|exists:suppliers,id',
            'minStockLevel' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);
        $validated['kdvRate'] = $validated['kdvRate'] ?? 18;
        Product::create($validated);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unitPrice' => 'required|numeric|min:0',
            'kdvRate' => 'nullable|numeric|min:0|max:100',
            'supplierId' => 'nullable|exists:suppliers,id',
            'minStockLevel' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['isActive'] = $request->boolean('isActive');
        $product->update($validated);
        return redirect()->route('products.index')->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Ürün silindi.');
    }
}
