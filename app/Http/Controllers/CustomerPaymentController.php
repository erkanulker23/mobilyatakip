<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Sale;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function __construct(private AuditService $auditService) {}
    public function create()
    {
        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $customerId = request('customerId', old('customerId'));
        $openSales = collect();
        $totalDebt = null;
        $totalSalesSum = null;
        $totalPaidSum = null;
        if ($customerId) {
            $openSales = Sale::with('customer')
                ->where('customerId', $customerId)
                ->where('isCancelled', false)
                ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
                ->orderBy('saleDate', 'desc')
                ->get();
            // Satış sayfasından saleId ile gelindiyse, o fatura listede yoksa (tam ödenmiş) bile ekle ki ön seçili görünsün
            $saleIdFromRequest = request('saleId');
            if ($saleIdFromRequest && !$openSales->contains('id', $saleIdFromRequest)) {
                $linkedSale = Sale::with('customer')->where('id', $saleIdFromRequest)->where('customerId', $customerId)->first();
                if ($linkedSale) {
                    $openSales = $openSales->prepend($linkedSale)->values();
                }
            }
            // Müşteri sayfasıyla aynı mantık: toplam borç = satışlar - yapılan tüm tahsilatlar (faturaya bağlı olsun olmasın)
            $totalSalesSum = (float) Sale::where('customerId', $customerId)->where('isCancelled', false)->sum('grandTotal');
            $totalPaidSum = (float) CustomerPayment::where('customerId', $customerId)->sum('amount');
            $totalDebt = $totalSalesSum - $totalPaidSum;
        }
        return view('customer-payments.create', compact('customers', 'kasalar', 'customerId', 'openSales', 'totalDebt', 'totalSalesSum', 'totalPaidSum'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'saleId' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => 'nullable|in:nakit,havale,kredi_karti,cek,senet,diger',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        // Nakit, havale ve kredi kartı tahsilatları kasaya işlendiği için kasa zorunlu
        $paymentTypesThatRequireKasa = ['nakit', 'havale', 'kredi_karti'];
        $kasaRequired = in_array($validated['paymentType'], $paymentTypesThatRequireKasa);
        if ($kasaRequired && empty($validated['kasaId'])) {
            return back()->withInput()->with('error', 'Nakit, havale ve kredi kartı tahsilatları için kasa seçimi zorunludur.');
        }

        if (!empty($validated['saleId'])) {
            $sale = Sale::findOrFail($validated['saleId']);
            if ($sale->customerId !== $validated['customerId']) {
                return back()->withInput()->with('error', 'Seçilen fatura bu müşteriye ait değil.');
            }
            $remaining = (float) $sale->grandTotal - (float) ($sale->paidAmount ?? 0);
            if ($validated['amount'] > $remaining) {
                return back()->withInput()->with('error', 'Tutar fatura kalanından fazla olamaz. Kalan: ' . number_format($remaining, 2, ',', '.') . ' ₺');
            }
        }

        DB::transaction(function () use ($validated, $kasaRequired) {
            $payment = CustomerPayment::create($validated);
            $this->auditService->logCreate('customer_payment', $payment->id, ['amount' => $validated['amount'], 'customerId' => $validated['customerId']]);
            if (!empty($validated['saleId'])) {
                Sale::where('id', $validated['saleId'])->increment('paidAmount', $validated['amount']);
            }
            // Nakit, havale, kredi kartı: kasaya giriş kaydı (bu tiplerde kasa zorunlu olduğu için kasaId dolu)
            $kasaId = $validated['kasaId'] ?? null;
            if ($kasaRequired && $kasaId) {
                $customer = Customer::find($validated['customerId']);
                $sale = $validated['saleId'] ? Sale::find($validated['saleId']) : null;
                $paymentTypeLabel = match ($validated['paymentType'] ?? '') {
                    'nakit' => 'Nakit',
                    'havale' => 'Havale',
                    'kredi_karti' => 'Kredi Kartı',
                    default => '',
                };
                $desc = 'Tahsilat - ' . ($customer?->name ?? 'Müşteri');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                if ($sale) {
                    $desc .= ' - Fatura: ' . $sale->saleNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' - ' . $validated['reference'];
                }
                KasaHareket::create([
                    'kasaId' => $kasaId,
                    'type' => 'giris',
                    'amount' => (float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id() ?: null,
                    'refType' => 'customer_payment',
                    'refId' => $payment->id,
                ]);
            }
        });

        return redirect()->route('customer-payments.create', ['customerId' => $validated['customerId']])->with('success', 'Tahsilat kaydedildi.');
    }

    public function show(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['customer', 'kasa', 'sale']);
        return view('customer-payments.show', compact('customerPayment'));
    }

    public function edit(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['customer', 'sale']);
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $openSales = Sale::with('customer')
            ->where('customerId', $customerPayment->customerId)
            ->where(function ($q) use ($customerPayment) {
                $q->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0');
                if ($customerPayment->saleId) {
                    $q->orWhere('id', $customerPayment->saleId);
                }
            })
            ->orderBy('saleDate', 'desc')
            ->get();
        return view('customer-payments.edit', compact('customerPayment', 'kasalar', 'openSales'));
    }

    public function update(Request $request, CustomerPayment $customerPayment)
    {
        $validated = $request->validate([
            'saleId' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => 'nullable|in:nakit,havale,kredi_karti,cek,senet,diger',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        // Nakit, havale ve kredi kartı tahsilatları kasaya işlendiği için kasa zorunlu
        $paymentTypesThatRequireKasa = ['nakit', 'havale', 'kredi_karti'];
        $kasaRequired = in_array($validated['paymentType'], $paymentTypesThatRequireKasa);
        if ($kasaRequired && empty($validated['kasaId'])) {
            return back()->withInput()->with('error', 'Nakit, havale ve kredi kartı tahsilatları için kasa seçimi zorunludur.');
        }

        if (!empty($validated['saleId'])) {
            $sale = Sale::findOrFail($validated['saleId']);
            if ($sale->customerId !== $customerPayment->customerId) {
                return back()->withInput()->with('error', 'Seçilen fatura bu müşteriye ait değil.');
            }
            $currentPaid = (float) ($sale->paidAmount ?? 0);
            $adjust = ($customerPayment->saleId === $validated['saleId']) ? (float) $customerPayment->amount : 0;
            $maxAllowed = (float) $sale->grandTotal - $currentPaid + $adjust;
            if ($validated['amount'] > $maxAllowed) {
                return back()->withInput()->with('error', 'Tutar fatura kalanından fazla olamaz. İzin verilen: ' . number_format($maxAllowed, 2, ',', '.') . ' ₺');
            }
        }

        $oldAmount = (float) $customerPayment->amount;
        $oldSaleId = $customerPayment->saleId;
        $newAmount = (float) $validated['amount'];
        $newSaleId = $validated['saleId'] ?? null;
        $newKasaId = $validated['kasaId'] ?? null;

        DB::transaction(function () use ($validated, $customerPayment, $oldAmount, $oldSaleId, $newAmount, $newSaleId, $newKasaId) {
            $oldData = ['amount' => $customerPayment->amount, 'saleId' => $customerPayment->saleId, 'kasaId' => $customerPayment->kasaId];
            $customerPayment->update($validated);
            $this->auditService->logUpdate('customer_payment', $customerPayment->id, $oldData, ['amount' => $validated['amount'], 'saleId' => $validated['saleId'] ?? null, 'kasaId' => $validated['kasaId'] ?? null]);

            if ($oldSaleId) {
                Sale::where('id', $oldSaleId)->decrement('paidAmount', $oldAmount);
            }
            if (!empty($newSaleId)) {
                Sale::where('id', $newSaleId)->increment('paidAmount', $newAmount);
            }

            $oldHareket = KasaHareket::where('refType', 'customer_payment')->where('refId', $customerPayment->id)->first();
            if ($oldHareket) {
                $oldHareket->delete();
            }

            if (!empty($newKasaId)) {
                $customer = $customerPayment->customer;
                $sale = $newSaleId ? Sale::find($newSaleId) : null;
                $paymentTypeLabel = match ($validated['paymentType'] ?? '') {
                    'nakit' => 'Nakit',
                    'havale' => 'Havale',
                    'kredi_karti' => 'Kredi Kartı',
                    default => '',
                };
                $desc = 'Tahsilat - ' . ($customer?->name ?? 'Müşteri');
                if ($paymentTypeLabel) {
                    $desc .= ' (' . $paymentTypeLabel . ')';
                }
                if ($sale) {
                    $desc .= ' - Fatura: ' . $sale->saleNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' - ' . $validated['reference'];
                }
                KasaHareket::create([
                    'kasaId' => $newKasaId,
                    'type' => 'giris',
                    'amount' => $newAmount,
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id() ?: null,
                    'refType' => 'customer_payment',
                    'refId' => $customerPayment->id,
                ]);
            }
        });

        return redirect()->route('customer-payments.show', $customerPayment)->with('success', 'Tahsilat güncellendi.');
    }

    public function print(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['customer', 'kasa', 'sale']);
        return view('customer-payments.print', compact('customerPayment'));
    }
}
