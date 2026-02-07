<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = Supplier::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
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
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
        ]);
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
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['isActive'] = $request->boolean('isActive');
        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi güncellendi.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Tedarikçi silindi.');
    }
}
