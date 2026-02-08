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

    public function show(SupplierPayment $supplierPayment)
    {
        $supplierPayment->load(['supplier', 'kasa', 'purchase']);
        return view('supplier-payments.show', compact('supplierPayment'));
    }

    public function edit(SupplierPayment $supplierPayment)
    {
        $supplierPayment->load(['supplier', 'purchase']);
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $openPurchases = Purchase::with('supplier')
            ->where('supplierId', $supplierPayment->supplierId)
            ->where('isCancelled', false)
            ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
            ->orderBy('purchaseDate', 'desc')
            ->get();
        if ($supplierPayment->purchaseId && !$openPurchases->contains('id', $supplierPayment->purchaseId)) {
            $openPurchases = $openPurchases->prepend($supplierPayment->purchase)->values();
        }
        return view('supplier-payments.edit', compact('supplierPayment', 'kasalar', 'openPurchases'));
    }

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

        $supplierBalance = null;
        if ($supplierId) {
            $supplier = Supplier::with(['purchases', 'payments'])->find($supplierId);
            if ($supplier) {
                $borc = (float) $supplier->purchases->where('isCancelled', false)->sum('grandTotal');
                $alacak = (float) $supplier->payments->sum('amount');
                $supplierBalance = (object) [
                    'borc' => $borc,
                    'alacak' => $alacak,
                    'bakiye' => $borc - $alacak,
                ];
            }
        }

        return view('supplier-payments.create', compact('suppliers', 'kasalar', 'openPurchases', 'supplierId', 'supplierBalance'));
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
                $paymentTypeLabel = match ($validated['paymentType'] ?? '') {
                    'nakit' => 'Nakit',
                    'havale' => 'Havale',
                    'kredi_karti' => 'Kredi Kartı',
                    'cek' => 'Çek',
                    'senet' => 'Senet',
                    'diger' => 'Diğer',
                    default => '',
                };
                $desc = 'Tedarikçi ödemesi - ' . ($supplier?->name ?? 'Tedarikçi');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                if ($purchase) {
                    $desc .= ' - Fatura: ' . $purchase->purchaseNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' - ' . $validated['reference'];
                }
                KasaHareket::create([
                    'kasaId' => $validated['kasaId'],
                    'type' => 'cikis',
                    'amount' => -(float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id() ?: null,
                    'refType' => 'supplier_payment',
                    'refId' => $payment->id,
                ]);
            }
        });

        return redirect()->route('suppliers.show', $validated['supplierId'])->with('success', 'Tedarikçi ödemesi kaydedildi.');
    }

    public function update(Request $request, SupplierPayment $supplierPayment)
    {
        $validated = $request->validate([
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
            if ($purchase->supplierId !== $supplierPayment->supplierId) {
                return back()->withInput()->with('error', 'Seçilen alış bu tedarikçiye ait değil.');
            }
            $currentPaid = (float) ($purchase->paidAmount ?? 0);
            $adjust = ($supplierPayment->purchaseId === $validated['purchaseId']) ? (float) $supplierPayment->amount : 0;
            $maxAllowed = (float) $purchase->grandTotal - $currentPaid + $adjust;
            if ($validated['amount'] > $maxAllowed) {
                return back()->withInput()->with('error', 'Tutar alış kalanından fazla olamaz. İzin verilen: ' . number_format($maxAllowed, 2, ',', '.') . ' ₺');
            }
        }

        $oldAmount = (float) $supplierPayment->amount;
        $oldPurchaseId = $supplierPayment->purchaseId;
        $newAmount = (float) $validated['amount'];
        $newPurchaseId = $validated['purchaseId'] ?? null;
        $newKasaId = $validated['kasaId'] ?? null;

        DB::transaction(function () use ($validated, $supplierPayment, $oldAmount, $oldPurchaseId, $newAmount, $newPurchaseId, $newKasaId) {
            $oldData = ['amount' => $supplierPayment->amount, 'purchaseId' => $supplierPayment->purchaseId, 'kasaId' => $supplierPayment->kasaId];
            $supplierPayment->update($validated);
            $this->auditService->logUpdate('supplier_payment', $supplierPayment->id, $oldData, ['amount' => $validated['amount'], 'purchaseId' => $validated['purchaseId'] ?? null, 'kasaId' => $validated['kasaId'] ?? null]);

            if ($oldPurchaseId) {
                Purchase::where('id', $oldPurchaseId)->decrement('paidAmount', $oldAmount);
            }
            if (!empty($newPurchaseId)) {
                Purchase::where('id', $newPurchaseId)->increment('paidAmount', $newAmount);
            }

            $oldHareket = KasaHareket::where('refType', 'supplier_payment')->where('refId', $supplierPayment->id)->first();
            if ($oldHareket) {
                $oldHareket->delete();
            }

            if (!empty($newKasaId)) {
                $supplier = $supplierPayment->supplier;
                $purchase = $newPurchaseId ? Purchase::find($newPurchaseId) : null;
                $paymentTypeLabel = match ($validated['paymentType'] ?? '') {
                    'nakit' => 'Nakit',
                    'havale' => 'Havale',
                    'kredi_karti' => 'Kredi Kartı',
                    'cek' => 'Çek',
                    'senet' => 'Senet',
                    'diger' => 'Diğer',
                    default => '',
                };
                $desc = 'Tedarikçi ödemesi - ' . ($supplier?->name ?? 'Tedarikçi');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                if ($purchase) {
                    $desc .= ' - Fatura: ' . $purchase->purchaseNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' - ' . $validated['reference'];
                }
                KasaHareket::create([
                    'kasaId' => $newKasaId,
                    'type' => 'cikis',
                    'amount' => -(float) $newAmount,
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id() ?: null,
                    'refType' => 'supplier_payment',
                    'refId' => $supplierPayment->id,
                ]);
            }
        });

        return redirect()->route('supplier-payments.show', $supplierPayment)->with('success', 'Tedarikçi ödemesi güncellendi.');
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        $supplierId = $supplierPayment->supplierId;
        DB::transaction(function () use ($supplierPayment) {
            if ($supplierPayment->purchaseId) {
                Purchase::where('id', $supplierPayment->purchaseId)->decrement('paidAmount', (float) $supplierPayment->amount);
            }
            $hareket = KasaHareket::where('refType', 'supplier_payment')->where('refId', $supplierPayment->id)->first();
            if ($hareket) {
                $hareket->delete();
            }
            $this->auditService->logDelete('supplier_payment', $supplierPayment->id, ['amount' => (float) $supplierPayment->amount, 'supplierId' => $supplierPayment->supplierId]);
            $supplierPayment->delete();
        });
        return redirect()->route('suppliers.show', $supplierId)->with('success', 'Tedarikçi ödemesi silindi.');
    }
}
