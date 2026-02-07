<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class KasaController extends Controller
{
    /** Ödeme tipi değerleri (filtre ve etiket için) */
    private const PAYMENT_TYPES = [
        'nakit' => 'Nakit',
        'havale' => 'Havale',
        'kredi_karti' => 'Kredi Kartı',
        'cek' => 'Çek',
        'senet' => 'Senet',
        'diger' => 'Diğer',
    ];
    public function index(Request $request)
    {
        $q = Kasa::query()->withSum('hareketler', 'amount')->orderBy('name');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('bankName', 'like', "%{$s}%")
                    ->orWhere('iban', 'like', "%{$s}%")
                    ->orWhere('accountNumber', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }
        $kasalar = $q->paginate(20)->withQueryString();
        return view('kasa.index', compact('kasalar'));
    }

    public function show(Request $request, Kasa $kasa)
    {
        $hareketlerToplam = (float) $kasa->hareketler()->sum('amount');
        $guncelBakiye = (float) ($kasa->openingBalance ?? 0) + $hareketlerToplam;

        $q = $kasa->hareketler()
            ->orderBy('movementDate', 'desc')
            ->orderBy('createdAt', 'desc');

        if ($request->filled('date_from')) {
            $q->whereDate('movementDate', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('movementDate', '<=', $request->date_to);
        }
        if ($request->filled('payment_type')) {
            $pt = $request->payment_type;
            $q->where(function ($w) use ($pt) {
                $w->where(function ($w2) use ($pt) {
                    $w2->where('refType', 'customer_payment')
                        ->whereIn('refId', CustomerPayment::query()->select('id')->where('paymentType', $pt));
                })->orWhere(function ($w2) use ($pt) {
                    $w2->where('refType', 'supplier_payment')
                        ->whereIn('refId', SupplierPayment::query()->select('id')->where('paymentType', $pt));
                });
            });
        }
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $q->where(function ($w) use ($cari) {
                $w->where(function ($w2) use ($cari) {
                    $w2->where('refType', 'customer_payment')
                        ->whereIn('refId', CustomerPayment::query()->select('id')->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$cari}%")));
                })->orWhere(function ($w2) use ($cari) {
                    $w2->where('refType', 'supplier_payment')
                        ->whereIn('refId', SupplierPayment::query()->select('id')->whereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$cari}%")));
                });
            });
        }

        $hareketler = $q->paginate(20)->withQueryString();

        $customerPaymentIds = $hareketler->where('refType', 'customer_payment')->pluck('refId')->unique()->filter()->values()->all();
        $supplierPaymentIds = $hareketler->where('refType', 'supplier_payment')->pluck('refId')->unique()->filter()->values()->all();
        $customerPayments = CustomerPayment::with('customer')->whereIn('id', $customerPaymentIds)->get()->keyBy('id');
        $supplierPayments = SupplierPayment::with('supplier')->whereIn('id', $supplierPaymentIds)->get()->keyBy('id');

        return view('kasa.show', compact('kasa', 'hareketler', 'guncelBakiye', 'hareketlerToplam', 'customerPayments', 'supplierPayments') + [
            'paymentTypes' => self::PAYMENT_TYPES,
        ]);
    }

    public function create()
    {
        return view('kasa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:kasa,banka',
            'accountNumber' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'bankName' => 'nullable|string|max:255',
            'openingBalance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
        ]);
        $validated['type'] = $validated['type'] ?? 'kasa';
        $validated['openingBalance'] = $validated['openingBalance'] ?? 0;
        $validated['currency'] = $validated['currency'] ?? 'TRY';
        Kasa::create($validated);
        return redirect()->route('kasa.index')->with('success', 'Kasa kaydedildi.');
    }

    public function edit(Kasa $kasa)
    {
        return view('kasa.edit', compact('kasa'));
    }

    public function update(Request $request, Kasa $kasa)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:kasa,banka',
            'accountNumber' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'bankName' => 'nullable|string|max:255',
            'openingBalance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['type'] = $validated['type'] ?? 'kasa';
        $validated['openingBalance'] = $validated['openingBalance'] ?? 0;
        $validated['isActive'] = $request->boolean('isActive');
        $kasa->update($validated);
        return redirect()->route('kasa.index')->with('success', 'Kasa güncellendi.');
    }
}
