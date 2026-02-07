<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function create()
    {
        $suppliers = Supplier::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $openPurchases = Purchase::with('supplier')
            ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
            ->orderBy('purchaseDate', 'desc')
            ->get();
        return view('supplier-payments.create', compact('suppliers', 'kasalar', 'openPurchases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'purchaseId' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => 'nullable|in:nakit,havale,kredi_karti,diger',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        if (!empty($validated['purchaseId'])) {
            $purchase = Purchase::findOrFail($validated['purchaseId']);
            if ($purchase->supplierId !== $validated['supplierId']) {
                return back()->withInput()->with('error', 'Seçilen alış bu tedarikçiye ait değil.');
            }
            $remaining = (float) $purchase->grandTotal - (float) $purchase->paidAmount;
            if ($validated['amount'] > $remaining) {
                return back()->withInput()->with('error', "Alış kalan tutar " . number_format($remaining, 2) . " ₺. Girilen tutar daha fazla olamaz.");
            }
        }

        DB::transaction(function () use ($validated) {
            SupplierPayment::create($validated);
            if (!empty($validated['purchaseId'])) {
                Purchase::where('id', $validated['purchaseId'])->increment('paidAmount', $validated['amount']);
            }
        });

        return redirect()->route('supplier-payments.create')->with('success', 'Tedarikçi ödemesi kaydedildi.');
    }
}
