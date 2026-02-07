<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function __construct(private AuditService $auditService) {}
    public function create(Request $request)
    {
        $suppliers = Supplier::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $supplierId = $request->get('supplierId');
        $openPurchases = Purchase::with('supplier')
            ->when($supplierId, fn ($q) => $q->where('supplierId', $supplierId))
            ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
            ->orderBy('purchaseDate', 'desc')
            ->get();
        return view('supplier-payments.create', compact('suppliers', 'kasalar', 'openPurchases', 'supplierId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'purchaseId' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => 'nullable|in:nakit,havale,kredi_karti,cek,senet,diger',
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
            $remaining = (float) $purchase->grandTotal - (float) ($purchase->paidAmount ?? 0);
            if ($validated['amount'] > $remaining) {
                return back()->withInput()->with('error', 'Alış kalan tutar ' . number_format($remaining, 2) . ' ₺. Girilen tutar daha fazla olamaz.');
            }
        }

        DB::transaction(function () use ($validated) {
            $payment = SupplierPayment::create($validated);
            $this->auditService->logCreate('supplier_payment', $payment->id, ['amount' => $validated['amount'], 'supplierId' => $validated['supplierId']]);
            if (!empty($validated['purchaseId'])) {
                Purchase::where('id', $validated['purchaseId'])->increment('paidAmount', $validated['amount']);
            }
            if (!empty($validated['kasaId'])) {
                $supplier = Supplier::find($validated['supplierId']);
                $purchase = $validated['purchaseId'] ? Purchase::find($validated['purchaseId']) : null;
                $desc = 'Tedarikçi ödemesi - ' . ($supplier?->name ?? 'Tedarikçi');
                if ($purchase) {
                    $desc .= ' - Fatura: ' . $purchase->purchaseNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' (' . $validated['reference'] . ')';
                }
                KasaHareket::create([
                    'kasaId' => $validated['kasaId'],
                    'type' => 'cikis',
                    'amount' => -(float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id(),
                    'refType' => 'supplier_payment',
                    'refId' => $payment->id,
                ]);
            }
        });

        return redirect()->route('suppliers.show', $validated['supplierId'])->with('success', 'Tedarikçi ödemesi kaydedildi.');
    }
}
