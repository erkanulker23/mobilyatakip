<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\KasaService;
use App\Support\KasaType;
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
        $q = Kasa::query()
            ->withSum(['hareketler as ledger_sum_amount' => fn ($h) => $h->ledger()], 'amount')
            ->orderBy('name');
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
            ->ledger()
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
        if ($request->filled('amount')) {
            $request->merge(['amount' => money_parse($request->input('amount'))]);
        }

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
        $request->merge(['openingBalance' => $this->parseOpeningBalance($request)]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => KasaType::validationRule(),
            'accountNumber' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'bankName' => 'nullable|string|max:255',
            'openingBalance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
        ]);
        $validated['type'] = $validated['type'] ?? KasaType::KASA;
        $validated = $this->normalizeKasaTypeFields($validated);
        $validated['openingBalance'] = (float) ($validated['openingBalance'] ?? 0);
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
        $request->merge(['openingBalance' => $this->parseOpeningBalance($request)]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => KasaType::validationRule(),
            'accountNumber' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'bankName' => 'nullable|string|max:255',
            'openingBalance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'isActive' => 'nullable|boolean',
        ]);
        $validated['type'] = $validated['type'] ?? KasaType::KASA;
        $validated = $this->normalizeKasaTypeFields($validated);
        $validated['openingBalance'] = (float) ($validated['openingBalance'] ?? 0);
        $validated['isActive'] = $request->boolean('isActive');
        $oldData = ['name' => $kasa->name, 'openingBalance' => (float) ($kasa->openingBalance ?? 0)];
        $kasa->update($validated);
        $this->auditService->logUpdate('kasa', $kasa->id, $oldData, [
            'name' => $kasa->name,
            'openingBalance' => (float) ($kasa->openingBalance ?? 0),
        ]);

        return redirect()->route('kasa.show', $kasa)->with('success', 'Kasa güncellendi.');
    }

    public function resetOpeningBalance(Kasa $kasa)
    {
        $old = (float) ($kasa->openingBalance ?? 0);
        if ($old === 0.0) {
            return back()->with('info', 'Açılış bakiyesi zaten sıfır.');
        }

        $kasa->update(['openingBalance' => 0]);
        $this->auditService->logUpdate('kasa', $kasa->id, ['openingBalance' => $old], ['openingBalance' => 0.0]);

        return back()->with('success', 'Açılış bakiyesi sıfırlandı.');
    }

    private function parseOpeningBalance(Request $request): float
    {
        $raw = $request->input('openingBalance');
        if ($raw === null || trim((string) $raw) === '') {
            return 0.0;
        }

        return money_parse((string) $raw);
    }

    /** @param  array<string, mixed>  $validated */
    private function normalizeKasaTypeFields(array $validated): array
    {
        if (($validated['type'] ?? KasaType::KASA) === KasaType::KASA) {
            $validated['bankName'] = null;
            $validated['iban'] = null;
            $validated['accountNumber'] = null;
        }

        return $validated;
    }

    public function destroyMovement(Kasa $kasa, KasaHareket $hareket)
    {
        if ($hareket->kasaId !== $kasa->id) {
            abort(404);
        }

        try {
            $this->kasaService->deleteMovement($kasa, $hareket);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->logDelete('kasa_hareket', $hareket->id, [
            'kasaId' => $kasa->id,
            'refType' => $hareket->refType,
            'amount' => (float) ($hareket->amount ?? 0),
        ]);

        return redirect()
            ->route('kasa.show', $kasa)
            ->with('success', 'Kasa hareketi silindi.');
    }

    public function destroy(Kasa $kasa)
    {
        $movementCount = (int) $kasa->hareketler()->count();
        $customerPaymentCount = (int) $kasa->customerPayments()->count();
        $supplierPaymentCount = (int) $kasa->supplierPayments()->count();
        $expenseCount = (int) \App\Models\Expense::where('kasaId', $kasa->id)->count();

        if ($movementCount > 0) {
            return back()->with('error', 'Bu kasada ' . $movementCount . ' hareket kaydı var. Önce kasa detayından hareketleri silin.');
        }
        if ($customerPaymentCount > 0 || $supplierPaymentCount > 0) {
            $parts = [];
            if ($customerPaymentCount > 0) {
                $parts[] = $customerPaymentCount . ' tahsilat';
            }
            if ($supplierPaymentCount > 0) {
                $parts[] = $supplierPaymentCount . ' tedarikçi ödemesi';
            }

            return back()->with('error', 'Bu kasaya bağlı ' . implode(' ve ', $parts) . ' kaydı var. Silinemez.');
        }
        if ($expenseCount > 0) {
            return back()->with('error', 'Bu kasaya bağlı ' . $expenseCount . ' gider kaydı var. Silinemez.');
        }

        $this->auditService->logDelete('kasa', $kasa->id, ['name' => $kasa->name]);
        $kasa->delete();

        return redirect()->route('kasa.index')->with('success', 'Kasa silindi.');
    }
}
