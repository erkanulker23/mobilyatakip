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
        if ($customerId) {
            $openSales = Sale::with('customer')
                ->where('customerId', $customerId)
                ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
                ->orderBy('saleDate', 'desc')
                ->get();
        }
        return view('customer-payments.create', compact('customers', 'kasalar', 'openSales'));
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

        DB::transaction(function () use ($validated) {
            $payment = CustomerPayment::create($validated);
            $this->auditService->logCreate('customer_payment', $payment->id, ['amount' => $validated['amount'], 'customerId' => $validated['customerId']]);
            if (!empty($validated['saleId'])) {
                Sale::where('id', $validated['saleId'])->increment('paidAmount', $validated['amount']);
            }
            if (!empty($validated['kasaId'])) {
                $customer = Customer::find($validated['customerId']);
                $sale = $validated['saleId'] ? Sale::find($validated['saleId']) : null;
                $desc = 'Tahsilat - ' . ($customer?->name ?? 'Müşteri');
                if ($sale) {
                    $desc .= ' - Fatura: ' . $sale->saleNumber;
                }
                if (!empty($validated['reference'])) {
                    $desc .= ' (' . $validated['reference'] . ')';
                }
                KasaHareket::create([
                    'kasaId' => $validated['kasaId'],
                    'type' => 'giris',
                    'amount' => (float) $validated['amount'],
                    'movementDate' => $validated['paymentDate'],
                    'description' => $desc,
                    'createdBy' => auth()->id(),
                    'refType' => 'customer_payment',
                    'refId' => $payment->id,
                ]);
            }
        });

        return redirect()->route('customer-payments.create', ['customerId' => $validated['customerId']])->with('success', 'Tahsilat kaydedildi.');
    }
}
