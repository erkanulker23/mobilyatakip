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
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }
        $customers = $q->paginate(20);
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
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
        ]);
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Müşteri kaydedildi.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['quotes', 'sales', 'payments.sale']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
            'isActive' => 'boolean',
        ]);
        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Müşteri güncellendi.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Müşteri silindi.');
    }
}
