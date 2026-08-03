<?php

namespace App\Http\Controllers;

use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceTicket;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyPayment;
use App\Support\PaymentType;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShippingCompanyPaymentController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function show(ShippingCompanyPayment $shippingCompanyPayment)
    {
        $shippingCompanyPayment->load(['shippingCompany', 'kasa', 'purchase', 'sale.customer', 'serviceTicket.customer']);
        return view('shipping-company-payments.show', compact('shippingCompanyPayment'));
    }

    public function edit(ShippingCompanyPayment $shippingCompanyPayment)
    {
        $shippingCompanyPayment->load(['shippingCompany', 'purchase', 'sale', 'serviceTicket']);
        $kasalar = Kasa::where('isActive', true)->orderBy('name')->get();
        $purchasesWithShipping = Purchase::with('supplier')
            ->where('shippingCompanyId', $shippingCompanyPayment->shippingCompanyId)
            ->where('isCancelled', false)
            ->orderBy('purchaseDate', 'desc')
            ->get();
        $sales = $this->salesForSelect();
        $serviceTickets = $this->serviceTicketsForSelect($shippingCompanyPayment->shippingCompanyId);
        $linkType = $this->resolveLinkType($shippingCompanyPayment);

        return view('shipping-company-payments.edit', compact(
            'shippingCompanyPayment',
            'kasalar',
            'purchasesWithShipping',
            'sales',
            'serviceTickets',
            'linkType',
        ));
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

        $sales = $this->salesForSelect();
        $serviceTickets = $this->serviceTicketsForSelect($shippingCompanyId);

        $totalPaid = null;
        if ($shippingCompanyId) {
            $totalPaid = (float) ShippingCompanyPayment::where('shippingCompanyId', $shippingCompanyId)->sum('amount');
        }

        $preselectedSaleId = $request->get('saleId');
        $preselectedServiceTicketId = $request->get('serviceTicketId');
        $preselectedPurchaseId = $request->get('purchaseId');
        $linkType = old('linkType', $request->get('linkType', $preselectedSaleId ? 'sale' : ($preselectedServiceTicketId ? 'service_ticket' : ($preselectedPurchaseId ? 'purchase' : ''))));

        return view('shipping-company-payments.create', compact(
            'shippingCompanies',
            'kasalar',
            'purchasesWithShipping',
            'sales',
            'serviceTickets',
            'shippingCompanyId',
            'totalPaid',
            'linkType',
            'preselectedSaleId',
            'preselectedServiceTicketId',
            'preselectedPurchaseId',
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayment($request, true);

        DB::transaction(function () use ($validated) {
            $payment = ShippingCompanyPayment::create($validated);
            $this->auditService->logCreate('shipping_company_payment', $payment->id, [
                'amount' => $validated['amount'],
                'shippingCompanyId' => $validated['shippingCompanyId'],
            ]);

            if (! empty($validated['kasaId'])) {
                $shippingCompany = ShippingCompany::find($validated['shippingCompanyId']);
                KasaHareket::create([
                    'kasaId' => $validated['kasaId'],
                    'type' => 'cikis',
                    'amount' => -(float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $this->kasaDescription($validated, $shippingCompany),
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
        $validated = $this->validatePayment($request, false);
        $newKasaId = $validated['kasaId'] ?? null;

        DB::transaction(function () use ($validated, $shippingCompanyPayment, $newKasaId) {
            $shippingCompanyPayment->update($validated);

            $oldHareket = KasaHareket::where('refType', 'shipping_company_payment')->where('refId', $shippingCompanyPayment->id)->first();
            if ($oldHareket) {
                $oldHareket->delete();
            }

            if (! empty($newKasaId)) {
                KasaHareket::create([
                    'kasaId' => $newKasaId,
                    'type' => 'cikis',
                    'amount' => -(float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $this->kasaDescription($validated, $shippingCompanyPayment->shippingCompany),
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

    /** @return \Illuminate\Database\Eloquent\Collection<int, Sale> */
    private function salesForSelect()
    {
        return Sale::with('customer')
            ->where('isCancelled', false)
            ->orderBy('saleDate', 'desc')
            ->orderBy('createdAt', 'desc')
            ->limit(300)
            ->get(['id', 'saleNumber', 'customerId', 'saleDate', 'grandTotal']);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, ServiceTicket> */
    private function serviceTicketsForSelect(?string $shippingCompanyId = null)
    {
        return ServiceTicket::with(['customer', 'sale'])
            ->when($shippingCompanyId, fn ($q) => $q->where('shippingCompanyId', $shippingCompanyId))
            ->orderBy('openedAt', 'desc')
            ->orderBy('createdAt', 'desc')
            ->limit(300)
            ->get(['id', 'ticketNumber', 'customerId', 'saleId', 'openedAt', 'status', 'shippingCompanyId']);
    }

    private function resolveLinkType(ShippingCompanyPayment $payment): string
    {
        if ($payment->purchaseId) {
            return 'purchase';
        }
        if ($payment->saleId) {
            return 'sale';
        }
        if ($payment->serviceTicketId) {
            return 'service_ticket';
        }
        if ($payment->paymentFor) {
            return 'manual';
        }

        return '';
    }

    private function validatePayment(Request $request, bool $creating): array
    {
        $rules = [
            'linkType' => 'required|in:purchase,sale,service_ticket,manual',
            'purchaseId' => 'nullable|exists:purchases,id',
            'saleId' => 'nullable|exists:sales,id',
            'serviceTicketId' => 'nullable|exists:service_tickets,id',
            'paymentFor' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentType' => PaymentType::validationRule(),
            'kasaId' => 'nullable|exists:kasa,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];

        if ($creating) {
            $rules['shippingCompanyId'] = 'required|exists:shipping_companies,id';
        }

        $validated = $request->validate($rules);
        $validated['paymentType'] = $validated['paymentType'] ?? 'nakit';

        $linkType = $validated['linkType'] ?? '';
        $validated['purchaseId'] = null;
        $validated['saleId'] = null;
        $validated['serviceTicketId'] = null;
        $validated['paymentFor'] = null;

        match ($linkType) {
            'purchase' => $validated['purchaseId'] = $request->input('purchaseId') ?: null,
            'sale' => $validated['saleId'] = $request->input('saleId') ?: null,
            'service_ticket' => $validated['serviceTicketId'] = $request->input('serviceTicketId') ?: null,
            'manual' => $validated['paymentFor'] = trim((string) $request->input('paymentFor', '')) ?: null,
            default => null,
        };

        if ($linkType === 'purchase' && empty($validated['purchaseId'])) {
            throw ValidationException::withMessages(['purchaseId' => 'Alış faturası seçiniz.']);
        }
        if ($linkType === 'manual' && empty($validated['paymentFor'])) {
            throw ValidationException::withMessages(['paymentFor' => 'Manuel açıklama giriniz.']);
        }

        unset($validated['linkType']);

        return $validated;
    }

    private function kasaDescription(array $validated, ?ShippingCompany $shippingCompany): string
    {
        $desc = 'Nakliye ödemesi - ' . ($shippingCompany?->name ?? 'Nakliye');

        $paymentTypeLabel = PaymentType::labels()[$validated['paymentType'] ?? ''] ?? '';
        if ($paymentTypeLabel) {
            $desc .= ' (' . $paymentTypeLabel . ')';
        }

        if (! empty($validated['purchaseId'])) {
            $purchase = Purchase::find($validated['purchaseId']);
            if ($purchase) {
                $desc .= ' - Alış: ' . $purchase->purchaseNumber;
            }
        } elseif (! empty($validated['saleId'])) {
            $sale = Sale::find($validated['saleId']);
            if ($sale) {
                $desc .= ' - Sipariş: ' . $sale->saleNumber;
            }
        } elseif (! empty($validated['serviceTicketId'])) {
            $ticket = ServiceTicket::find($validated['serviceTicketId']);
            if ($ticket) {
                $desc .= ' - SSH: ' . $ticket->ticketNumber;
            }
        } elseif (! empty($validated['paymentFor'])) {
            $desc .= ' - ' . $validated['paymentFor'];
        }

        if (! empty($validated['reference'])) {
            $desc .= ' · Ref: ' . $validated['reference'];
        }

        return $desc;
    }
}
