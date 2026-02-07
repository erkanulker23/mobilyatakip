<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function create()
    {
        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $openSales = Sale::with('customer')
            ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
            ->orderBy('saleDate', 'desc')
            ->get();
        return view('customer-payments.create', compact('customers', 'kasalar', 'openSales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'saleId' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => 'nullable|in:nakit,havale,kredi_karti,diger',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        if (!empty($validated['saleId'])) {
            $sale = Sale::findOrFail($validated['saleId']);
            if ($sale->customerId !== $validated['customerId']) {
                return back()->withInput()->with('error', 'Seçilen fatura bu müşteriye ait değil.');
            }
            $remaining = (float) $sale->grandTotal - (float) $sale->paidAmount;
            if ($validated['amount'] > $remaining) {
                return back()->withInput()->with('error', "Fatura kalan tutar {$remaining} ₺. Girilen tutar daha fazla olamaz.");
            }
        }

        DB::transaction(function () use ($validated) {
            CustomerPayment::create($validated);
            if (!empty($validated['saleId'])) {
                Sale::where('id', $validated['saleId'])->increment('paidAmount', $validated['amount']);
            }
        });

        return redirect()->route('customer-payments.create')->with('success', 'Tahsilat kaydedildi.');
    }
}
