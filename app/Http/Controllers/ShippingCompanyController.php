<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyPayment;
use Illuminate\Http\Request;

class ShippingCompanyController extends Controller
{
    public function index(Request $request)
    {
        $q = ShippingCompany::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $shippingCompanies = $q->paginate(20)->withQueryString();
        $ids = $shippingCompanies->getCollection()->pluck('id')->values()->all();

        $odemeByShipping = [];
        if (!empty($ids)) {
            $odemeByShipping = ShippingCompanyPayment::whereIn('shippingCompanyId', $ids)
                ->selectRaw('shippingCompanyId, sum(amount) as total')
                ->groupBy('shippingCompanyId')
                ->pluck('total', 'shippingCompanyId')
                ->map(fn ($v) => (float) $v)
                ->all();
        }

        return view('shipping-companies.index', compact('shippingCompanies', 'odemeByShipping'));
    }

    public function create()
    {
        return view('shipping-companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);
        ShippingCompany::create($validated);
        return redirect()->route('shipping-companies.index')->with('success', 'Nakliye firması kaydedildi.');
    }

    public function show(ShippingCompany $shippingCompany)
    {
        $shippingCompany->load(['purchases.supplier', 'payments.purchase']);
        return view('shipping-companies.show', compact('shippingCompany'));
    }

    public function edit(ShippingCompany $shippingCompany)
    {
        return view('shipping-companies.edit', compact('shippingCompany'));
    }

    public function update(Request $request, ShippingCompany $shippingCompany)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['isActive'] = $request->boolean('isActive');
        $shippingCompany->update($validated);
        return redirect()->route('shipping-companies.index')->with('success', 'Nakliye firması güncellendi.');
    }

    public function destroy(ShippingCompany $shippingCompany)
    {
        $shippingCompany->payments()->delete();
        Purchase::where('shippingCompanyId', $shippingCompany->id)->update(['shippingCompanyId' => null]);
        $shippingCompany->delete();
        return redirect()->route('shipping-companies.index')->with('success', 'Nakliye firması silindi.');
    }
}
