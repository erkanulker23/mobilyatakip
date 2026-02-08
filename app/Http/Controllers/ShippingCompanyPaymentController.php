<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Purchase;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyPayment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingCompanyPaymentController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function show(ShippingCompanyPayment $shippingCompanyPayment)
    {
        $shippingCompanyPayment->load(['shippingCompany', 'kasa', 'purchase']);
        return view('shipping-company-payments.show', compact('shippingCompanyPayment'));
    }

    public function edit(ShippingCompanyPayment $shippingCompanyPayment)
    {
        $shippingCompanyPayment->load(['shippingCompany', 'purchase']);
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $purchasesWithShipping = Purchase::with('supplier')
            ->where('shippingCompanyId', $shippingCompanyPayment->shippingCompanyId)
            ->where('isCancelled', false)
            ->orderBy('purchaseDate', 'desc')
            ->get();
        return view('shipping-company-payments.edit', compact('shippingCompanyPayment', 'kasalar', 'purchasesWithShipping'));
    }

    public function create(Request $request)
    {
        $shippingCompanies = ShippingCompany::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $shippingCompanyId = $request->get('shippingCompanyId');

        $purchasesWithShipping = Purchase::with('supplier')
            ->when($shippingCompanyId, fn ($q) => $q->where('shippingCompanyId', $shippingCompanyId))
            ->where('isCancelled', false)
            ->whereNotNull('shippingCompanyId')
            ->orderBy('purchaseDate', 'desc')
            ->get();

        $totalPaid = null;
        if ($shippingCompanyId) {
            $totalPaid = (float) ShippingCompanyPayment::where('shippingCompanyId', $shippingCompanyId)->sum('amount');
        }

        return view('shipping-company-payments.create', compact('shippingCompanies', 'kasalar', 'purchasesWithShipping', 'shippingCompanyId', 'totalPaid'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shippingCompanyId' => 'required|exists:shipping_companies,id',
            'purchaseId' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => 'nullable|in:nakit,havale,kredi_karti,cek,senet,diger',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        DB::transaction(function () use ($validated) {
            $payment = ShippingCompanyPayment::create($validated);
            $this->auditService->logCreate('shipping_company_payment', $payment->id, ['amount' => $validated['amount'], 'shippingCompanyId' => $validated['shippingCompanyId']]);

            if (!empty($validated['kasaId'])) {
                $shippingCompany = ShippingCompany::find($validated['shippingCompanyId']);
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
                $desc = 'Nakliye ödemesi - ' . ($shippingCompany?->name ?? 'Nakliye');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                if ($purchase) {
                    $desc .= ' - Alış: ' . $purchase->purchaseNumber;
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
                    'refType' => 'shipping_company_payment',
                    'refId' => $payment->id,
                ]);
            }
        });

        return redirect()->route('shipping-companies.show', $validated['shippingCompanyId'])->with('success', 'Nakliye ödemesi kaydedildi.');
    }

    public function update(Request $request, ShippingCompanyPayment $shippingCompanyPayment)
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
        $newKasaId = $validated['kasaId'] ?? null;

        DB::transaction(function () use ($validated, $shippingCompanyPayment, $newKasaId) {
            $shippingCompanyPayment->update($validated);

            $oldHareket = KasaHareket::where('refType', 'shipping_company_payment')->where('refId', $shippingCompanyPayment->id)->first();
            if ($oldHareket) {
                $oldHareket->delete();
            }

            if (!empty($newKasaId)) {
                $shippingCompany = $shippingCompanyPayment->shippingCompany;
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
                $desc = 'Nakliye ödemesi - ' . ($shippingCompany?->name ?? 'Nakliye');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                if ($purchase) {
                    $desc .= ' - Alış: ' . $purchase->purchaseNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' - ' . $validated['reference'];
                }
                KasaHareket::create([
                    'kasaId' => $newKasaId,
                    'type' => 'cikis',
                    'amount' => -(float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id() ?: null,
                    'refType' => 'shipping_company_payment',
                    'refId' => $shippingCompanyPayment->id,
                ]);
            }
        });

        return redirect()->route('shipping-company-payments.show', $shippingCompanyPayment)->with('success', 'Nakliye ödemesi güncellendi.');
    }

    public function destroy(ShippingCompanyPayment $shippingCompanyPayment)
    {
        $shippingCompanyId = $shippingCompanyPayment->shippingCompanyId;
        DB::transaction(function () use ($shippingCompanyPayment) {
            $hareket = KasaHareket::where('refType', 'shipping_company_payment')->where('refId', $shippingCompanyPayment->id)->first();
            if ($hareket) {
                $hareket->delete();
            }
            $this->auditService->logDelete('shipping_company_payment', $shippingCompanyPayment->id, ['amount' => (float) $shippingCompanyPayment->amount]);
            $shippingCompanyPayment->delete();
        });
        return redirect()->route('shipping-companies.show', $shippingCompanyId)->with('success', 'Nakliye ödemesi silindi.');
    }
}
