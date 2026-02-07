<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::query()->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%")
                    ->orWhere('taxNumber', 'like', "%{$s}%");
            });
        }
        if ($request->filled('isActive')) {
            $q->where('isActive', $request->boolean('isActive'));
        }
        $customers = $q->paginate(20)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'identityNumber' => 'nullable|string|size:11|regex:/^[0-9]+$/',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
        ]);
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Müşteri kaydedildi.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['quotes', 'sales.items.product', 'payments.sale']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['sales', 'quotes']);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'identityNumber' => 'nullable|string|size:11|regex:/^[0-9]+$/',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'boolean',
        ]);
        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Müşteri güncellendi.');
    }

    public function print(Customer $customer)
    {
        $customer->load(['quotes', 'sales.items.product', 'payments']);
        $totalSales = $customer->sales->sum('grandTotal');
        $totalPaid = $customer->payments->sum('amount');
        $totalDebt = $totalSales - $totalPaid;
        return view('customers.print', compact('customer', 'totalSales', 'totalPaid', 'totalDebt'));
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Müşteri silindi.');
    }
}
