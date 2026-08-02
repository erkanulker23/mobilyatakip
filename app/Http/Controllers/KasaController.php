<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\KasaService;
use App\Support\PaymentType;
use Illuminate\Http\Request;

class KasaController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private KasaService $kasaService,
    ) {}

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
        $summary = $this->kasaService->summary($kasa);
        $guncelBakiye = $summary['current'];
        $hareketlerToplam = $summary['netMovements'];

        $q = $kasa->hareketler()
            ->with(['fromKasa', 'toKasa'])
            ->orderBy('movementDate', 'desc')
            ->orderBy('createdAt', 'desc');

        if ($request->filled('date_from')) {
            $q->whereDate('movementDate', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('movementDate', '<=', $request->date_to);
        }
        if ($request->filled('movement')) {
            match ($request->movement) {
                'tahsilat' => $q->where('refType', 'customer_payment'),
                'odeme' => $q->where('refType', 'supplier_payment'),
                'gider' => $q->where('refType', 'expense'),
                'virman' => $q->where('refType', 'kasa_transfer'),
                default => null,
            };
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

        $otherKasalar = Kasa::query()
            ->where('isActive', true)
            ->where('id', '!=', $kasa->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'bankName']);

        return view('kasa.show', compact(
            'kasa',
            'hareketler',
            'guncelBakiye',
            'hareketlerToplam',
            'summary',
            'customerPayments',
            'supplierPayments',
            'otherKasalar',
        ) + [
            'paymentTypes' => PaymentType::labels(),
        ]);
    }

    public function transfer(Request $request, Kasa $kasa)
    {
        $validated = $request->validate([
            'toKasaId' => 'required|exists:kasa,id|different:' . $kasa->id,
            'amount' => 'required|numeric|min:0.01',
            'movementDate' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $toKasa = Kasa::findOrFail($validated['toKasaId']);
        if (! ($toKasa->isActive ?? true)) {
            return back()->withInput()->with('error', 'Hedef kasa aktif değil.');
        }

        try {
            $transferId = $this->kasaService->transfer(
                $kasa,
                $toKasa,
                (float) $validated['amount'],
                $validated['movementDate'],
                $validated['description'] ?? null,
                auth()->id() ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->auditService->logAction('kasa', $kasa->id, 'transfer', [
            'amount' => (float) $validated['amount'],
            'toKasaId' => $toKasa->id,
            'toKasaName' => $toKasa->name,
            'transferId' => $transferId,
        ]);

        return redirect()
            ->route('kasa.show', $kasa)
            ->with('success', number_format((float) $validated['amount'], 0, ',', '.') . ' ₺ ' . $toKasa->name . ' kasasına virman edildi.');
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
        $kasa = Kasa::create($validated);
        $this->auditService->logCreate('kasa', $kasa->id, ['name' => $kasa->name]);

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
        $oldData = ['name' => $kasa->name];
        $kasa->update($validated);
        $this->auditService->logUpdate('kasa', $kasa->id, $oldData, ['name' => $kasa->name]);

        return redirect()->route('kasa.index')->with('success', 'Kasa güncellendi.');
    }

    public function destroy(Kasa $kasa)
    {
        if ($kasa->hareketler()->exists()) {
            return back()->with('error', 'Hareket kaydı olan kasa silinemez.');
        }
        if ($kasa->customerPayments()->exists() || $kasa->supplierPayments()->exists()) {
            return back()->with('error', 'Ödeme kaydı bağlı kasa silinemez.');
        }
        if (\App\Models\Expense::where('kasaId', $kasa->id)->exists()) {
            return back()->with('error', 'Gider kaydı bağlı kasa silinemez.');
        }

        $this->auditService->logDelete('kasa', $kasa->id, ['name' => $kasa->name]);
        $kasa->delete();

        return redirect()->route('kasa.index')->with('success', 'Kasa silindi.');
    }
}
