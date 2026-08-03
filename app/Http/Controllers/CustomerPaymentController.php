<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Support\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function __construct(private AuditService $auditService) {}
    public function create(Request $request)
    {
        if ($request->boolean('list')) {
            return $this->index($request);
        }

        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $suppliers = Supplier::where('isActive', true)->orderBy('name')->get();
        $customerId = request('customerId', old('customerId'));
        $openSales = collect();
        $totalDebt = null;
        $totalSalesSum = null;
        $totalPaidSum = null;
        $selectedCustomer = null;
        $recentPayments = collect();
        if ($customerId) {
            $selectedCustomer = Customer::find($customerId);
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
            $recentPayments = CustomerPayment::with(['sale', 'kasa'])
                ->where('customerId', $customerId)
                ->orderByDesc('paymentDate')
                ->orderByDesc('createdAt')
                ->take(5)
                ->get();
        }
        return view('customer-payments.create', compact(
            'customers',
            'kasalar',
            'suppliers',
            'customerId',
            'openSales',
            'totalDebt',
            'totalSalesSum',
            'totalPaidSum',
            'selectedCustomer',
            'recentPayments'
        ));
    }

    public function index(Request $request)
    {
        $query = CustomerPayment::query()
            ->with(['customer', 'kasa', 'sale'])
            ->orderByDesc('paymentDate')
            ->orderByDesc('createdAt');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($w) use ($s) {
                $w->where('reference', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('sale', fn ($q) => $q->where('saleNumber', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('customerId')) {
            $query->where('customerId', $request->customerId);
        }
        if ($request->filled('paymentType')) {
            $query->where('paymentType', $request->paymentType);
        }
        if ($request->filled('kasaId')) {
            $query->where('kasaId', $request->kasaId);
        }
        if ($request->filled('from')) {
            $query->where('paymentDate', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('paymentDate', '<=', $request->to);
        }

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->paginate(20)->withQueryString();

        $todayTotal = (float) CustomerPayment::query()
            ->whereDate('paymentDate', today())
            ->sum('amount');
        $monthTotal = (float) CustomerPayment::query()
            ->whereYear('paymentDate', now()->year)
            ->whereMonth('paymentDate', now()->month)
            ->sum('amount');

        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();

        return view('customer-payments.index', compact(
            'payments',
            'totalAmount',
            'todayTotal',
            'monthTotal',
            'customers',
            'kasalar'
        ));
    }

    public function store(Request $request)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'saleId' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date|before_or_equal:today',
            'paymentType' => PaymentType::customerReceiveValidationRule(),
            'supplierId' => 'nullable|exists:suppliers,id',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'redirectToSale' => 'nullable|exists:sales,id',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';
        $redirectToSale = $validated['redirectToSale'] ?? null;
        unset($validated['redirectToSale']);

        $redirectBackToSale = function () use ($redirectToSale) {
            if (!$redirectToSale) {
                return null;
            }
            return redirect()->route('sales.show', $redirectToSale)
                ->withInput()
                ->with('open_payment_modal', true);
        };

        $isSupplierPay = $validated['paymentType'] === 'tedarikciye_ode';

        if ($isSupplierPay) {
            if (empty($validated['supplierId'])) {
                $msg = 'Tedarikçiye Ödeme seçildiğinde tedarikçi seçimi zorunludur.';
                $response = $redirectBackToSale();
                if ($response) {
                    return $response->with('error', $msg);
                }
                return back()->withInput()->with('error', $msg);
            }
            $validated['kasaId'] = null;
        } else {
            $validated['supplierId'] = null;
            $kasaError = PaymentType::validateKasaSelection(
                $validated['kasaId'] ?? null,
                $validated['paymentType'],
                PaymentType::requiresKasa($validated['paymentType'])
            );
            if ($kasaError) {
                $response = $redirectBackToSale();
                if ($response) {
                    return $response->with('error', $kasaError);
                }
                return back()->withInput()->with('error', $kasaError);
            }
        }

        $kasaRequired = PaymentType::requiresKasa($validated['paymentType']);

        if (!empty($validated['saleId'])) {
            $sale = Sale::findOrFail($validated['saleId']);
            if ($sale->customerId !== $validated['customerId']) {
                $response = $redirectBackToSale();
                if ($response) {
                    return $response->with('error', 'Seçilen fatura bu müşteriye ait değil.');
                }
                return back()->withInput()->with('error', 'Seçilen fatura bu müşteriye ait değil.');
            }
            $remaining = (float) $sale->grandTotal - (float) ($sale->paidAmount ?? 0);
            if ($validated['amount'] > $remaining) {
                $msg = 'Tutar fatura kalanından fazla olamaz. Kalan: ' . number_format($remaining, 2, ',', '.') . ' ₺';
                $response = $redirectBackToSale();
                if ($response) {
                    return $response->with('error', $msg);
                }
                return back()->withInput()->with('error', $msg);
            }
        }

        DB::transaction(function () use ($validated, $kasaRequired, $isSupplierPay) {
            $payment = CustomerPayment::create($validated);
            $this->auditService->logCreate('customer_payment', $payment->id, ['amount' => $validated['amount'], 'customerId' => $validated['customerId']]);
            if (!empty($validated['saleId'])) {
                Sale::where('id', $validated['saleId'])->increment('paidAmount', $validated['amount']);
            }
            $kasaId = $validated['kasaId'] ?? null;
            if ($kasaRequired && $kasaId) {
                $customer = Customer::find($validated['customerId']);
                $sale = $validated['saleId'] ? Sale::find($validated['saleId']) : null;
                $paymentTypeLabel = PaymentType::label($validated['paymentType'] ?? null);
                if ($paymentTypeLabel === '—') {
                    $paymentTypeLabel = '';
                }
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

            if ($isSupplierPay) {
                $customer = Customer::find($validated['customerId']);
                $supplierNote = 'Müşteri tahsilatı: ' . ($customer?->name ?? 'Müşteri');
                if (!empty($validated['notes'])) {
                    $supplierNote .= ' — ' . $validated['notes'];
                }
                $supplierPayment = SupplierPayment::create([
                    'supplierId' => $validated['supplierId'],
                    'amount' => $validated['amount'],
                    'paymentDate' => $validated['paymentDate'],
                    'paymentType' => 'diger',
                    'reference' => $validated['reference'],
                    'notes' => $supplierNote,
                ]);
                $payment->update(['linkedSupplierPaymentId' => $supplierPayment->id]);
                $this->auditService->logCreate('supplier_payment', $supplierPayment->id, [
                    'amount' => $validated['amount'],
                    'supplierId' => $validated['supplierId'],
                    'viaCustomerPayment' => $payment->id,
                ]);
            }
        });

        if ($redirectToSale) {
            return redirect()->route('sales.show', $redirectToSale)->with('success', 'Tahsilat kaydedildi.');
        }

        return redirect()->route('customer-payments.create', ['customerId' => $validated['customerId']])->with('success', 'Tahsilat kaydedildi.');
    }

    public function show(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['customer', 'kasa', 'sale', 'supplier', 'linkedSupplierPayment']);
        return view('customer-payments.show', compact('customerPayment'));
    }

    public function edit(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['customer', 'sale', 'supplier']);
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $suppliers = Supplier::where('isActive', true)->orderBy('name')->get();
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
        return view('customer-payments.edit', compact('customerPayment', 'kasalar', 'suppliers', 'openSales'));
    }

    public function update(Request $request, CustomerPayment $customerPayment)
    {
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

        $validated = $request->validate([
            'saleId' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date|before_or_equal:today',
            'paymentType' => PaymentType::customerReceiveValidationRule(),
            'supplierId' => 'nullable|exists:suppliers,id',
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        $isSupplierPay = $validated['paymentType'] === 'tedarikciye_ode';
        $wasSupplierPay = $customerPayment->paymentType === 'tedarikciye_ode';

        if ($isSupplierPay) {
            if (empty($validated['supplierId'])) {
                return back()->withInput()->with('error', 'Tedarikçiye Ödeme seçildiğinde tedarikçi seçimi zorunludur.');
            }
            $validated['kasaId'] = null;
        } else {
            $validated['supplierId'] = null;
            $kasaError = PaymentType::validateKasaSelection(
                $validated['kasaId'] ?? null,
                $validated['paymentType'],
                PaymentType::requiresKasa($validated['paymentType'])
            );
            if ($kasaError) {
                return back()->withInput()->with('error', $kasaError);
            }
        }

        $kasaRequired = PaymentType::requiresKasa($validated['paymentType']);

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

        DB::transaction(function () use ($validated, $customerPayment, $oldAmount, $oldSaleId, $newAmount, $newSaleId, $newKasaId, $kasaRequired, $isSupplierPay, $wasSupplierPay) {
            $oldData = ['amount' => $customerPayment->amount, 'saleId' => $customerPayment->saleId, 'kasaId' => $customerPayment->kasaId, 'paymentType' => $customerPayment->paymentType];
            $customerPayment->update($validated);
            $this->auditService->logUpdate('customer_payment', $customerPayment->id, $oldData, ['amount' => $validated['amount'], 'saleId' => $validated['saleId'] ?? null, 'kasaId' => $validated['kasaId'] ?? null, 'paymentType' => $validated['paymentType']]);

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

            if ($kasaRequired && !empty($newKasaId)) {
                $customer = $customerPayment->customer;
                $sale = $newSaleId ? Sale::find($newSaleId) : null;
                $paymentTypeLabel = PaymentType::label($validated['paymentType'] ?? null);
                if ($paymentTypeLabel === '—') {
                    $paymentTypeLabel = '';
                }
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

            if ($wasSupplierPay && $customerPayment->linkedSupplierPaymentId) {
                $linked = SupplierPayment::find($customerPayment->linkedSupplierPaymentId);
                if ($linked) {
                    if ($isSupplierPay) {
                        $customer = $customerPayment->customer;
                        $supplierNote = 'Müşteri tahsilatı: ' . ($customer?->name ?? 'Müşteri');
                        if (!empty($validated['notes'])) {
                            $supplierNote .= ' — ' . $validated['notes'];
                        }
                        $linked->update([
                            'supplierId' => $validated['supplierId'],
                            'amount' => $newAmount,
                            'paymentDate' => $validated['paymentDate'],
                            'reference' => $validated['reference'],
                            'notes' => $supplierNote,
                        ]);
                    } else {
                        $this->auditService->logDelete('supplier_payment', $linked->id, ['amount' => (float) $linked->amount, 'supplierId' => $linked->supplierId]);
                        $linked->delete();
                        $customerPayment->update(['linkedSupplierPaymentId' => null]);
                    }
                }
            } elseif ($isSupplierPay) {
                $customer = $customerPayment->customer;
                $supplierNote = 'Müşteri tahsilatı: ' . ($customer?->name ?? 'Müşteri');
                if (!empty($validated['notes'])) {
                    $supplierNote .= ' — ' . $validated['notes'];
                }
                $supplierPayment = SupplierPayment::create([
                    'supplierId' => $validated['supplierId'],
                    'amount' => $newAmount,
                    'paymentDate' => $validated['paymentDate'],
                    'paymentType' => 'diger',
                    'reference' => $validated['reference'],
                    'notes' => $supplierNote,
                ]);
                $customerPayment->update(['linkedSupplierPaymentId' => $supplierPayment->id]);
                $this->auditService->logCreate('supplier_payment', $supplierPayment->id, [
                    'amount' => $newAmount,
                    'supplierId' => $validated['supplierId'],
                    'viaCustomerPayment' => $customerPayment->id,
                ]);
            }
        });

        return redirect()->route('customer-payments.show', $customerPayment)->with('success', 'Tahsilat güncellendi.');
    }

    public function destroy(CustomerPayment $customerPayment)
    {
        $customerId = $customerPayment->customerId;

        DB::transaction(function () use ($customerPayment) {
            if ($customerPayment->saleId) {
                Sale::where('id', $customerPayment->saleId)->decrement('paidAmount', (float) $customerPayment->amount);
            }

            $hareket = KasaHareket::where('refType', 'customer_payment')->where('refId', $customerPayment->id)->first();
            if ($hareket) {
                $hareket->delete();
            }

            if ($customerPayment->linkedSupplierPaymentId) {
                $linked = SupplierPayment::find($customerPayment->linkedSupplierPaymentId);
                if ($linked) {
                    $this->auditService->logDelete('supplier_payment', $linked->id, [
                        'amount' => (float) $linked->amount,
                        'supplierId' => $linked->supplierId,
                    ]);
                    $linked->delete();
                }
            }

            $this->auditService->logDelete('customer_payment', $customerPayment->id, [
                'amount' => (float) $customerPayment->amount,
                'customerId' => $customerPayment->customerId,
            ]);

            $customerPayment->delete();
        });

        return redirect()->route('customers.show', $customerId)->withFragment('tahsilatlar')->with('success', 'Tahsilat kaydı silindi.');
    }

    public function print(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['customer', 'kasa', 'sale', 'supplier']);
        return view('customer-payments.print', compact('customerPayment'));
    }
}
