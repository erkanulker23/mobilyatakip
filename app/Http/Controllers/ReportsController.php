<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function incomeExpense(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $gelir = (float) Sale::whereBetween('saleDate', [$from, $to])->where('isCancelled', false)->sum('grandTotal');
        $tahsilat = (float) CustomerPayment::whereBetween('paymentDate', [$from, $to])->sum('amount');
        $gider = (float) Expense::whereBetween('expenseDate', [$from, $to])->sum('amount');
        $tedarikciOdeme = (float) SupplierPayment::whereBetween('paymentDate', [$from, $to])->sum('amount');

        return view('reports.income-expense', compact('from', 'to', 'gelir', 'tahsilat', 'gider', 'tedarikciOdeme'));
    }

    public function customerLedger(Request $request)
    {
        $customers = Customer::with(['sales', 'payments'])->where('isActive', true)->orderBy('name')->get()->map(function ($c) {
            $borc = (float) $c->sales->where('isCancelled', false)->sum('grandTotal');
            $alacak = (float) $c->payments->sum('amount');
            $bakiye = $borc - $alacak;
            return (object) [
                'customer' => $c,
                'borc' => $borc,
                'alacak' => $alacak,
                'bakiye' => $bakiye,
            ];
        });
        if ($request->filled('tip')) {
            if ($request->tip === 'borclu') {
                $customers = $customers->filter(fn ($r) => $r->bakiye > 0)->values();
            } elseif ($request->tip === 'alacakli') {
                $customers = $customers->filter(fn ($r) => $r->bakiye < 0)->values();
            }
        }
        return view('reports.customer-ledger', compact('customers'));
    }

    public function customerLedgerDetail(Customer $customer, Request $request)
    {
        $customer->load(['sales', 'payments.sale']);
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        $rows = collect();
        foreach ($customer->sales()->where('isCancelled', false)->orderBy('saleDate')->orderBy('createdAt')->get() as $s) {
            $rows->push((object) [
                'date' => $s->saleDate,
                'type' => 'satis',
                'ref' => $s->saleNumber,
                'refId' => $s->id,
                'refRoute' => 'sales.show',
                'aciklama' => 'Satış ' . $s->saleNumber,
                'borc' => (float) $s->grandTotal,
                'alacak' => 0,
            ]);
        }
        foreach ($customer->payments()->orderBy('paymentDate')->orderBy('createdAt')->get() as $p) {
            $aciklama = 'Tahsilat';
            if ($p->sale) {
                $aciklama .= ' - ' . $p->sale->saleNumber;
            }
            if ($p->reference) {
                $aciklama .= ' (' . $p->reference . ')';
            }
            $rows->push((object) [
                'date' => $p->paymentDate,
                'type' => 'tahsilat',
                'ref' => null,
                'refId' => null,
                'refRoute' => null,
                'aciklama' => $aciklama,
                'borc' => 0,
                'alacak' => (float) $p->amount,
            ]);
        }
        $rows = $rows->sortBy('date')->values();
        $openingBalance = 0;
        $filteredRows = collect();
        foreach ($rows as $r) {
            if ($from && $r->date->lt($from)) {
                $openingBalance += $r->borc - $r->alacak;
                continue;
            }
            if ($to && $r->date->gt($to)) {
                continue;
            }
            $openingBalance += $r->borc - $r->alacak;
            $r->bakiye = $openingBalance;
            $filteredRows->push($r);
        }
        return view('reports.customer-ledger-detail', compact('customer', 'filteredRows', 'from', 'to', 'openingBalance'));
    }

    public function supplierLedger(Request $request)
    {
        $suppliers = Supplier::with(['purchases', 'payments'])->where('isActive', true)->orderBy('name')->get()->map(function ($s) {
            $borc = (float) $s->purchases->sum('grandTotal');
            $alacak = (float) $s->payments->sum('amount');
            $bakiye = $borc - $alacak;
            return (object) [
                'supplier' => $s,
                'borc' => $borc,
                'alacak' => $alacak,
                'bakiye' => $bakiye,
            ];
        });
        // borclu = Bize borçlu değil, biz tedarikçiye borçluyuz (bakiye > 0)
        // alacakli = Tedarikçi bize borçlu - fazla ödeme yaptık (bakiye < 0)
        if ($request->filled('tip')) {
            if ($request->tip === 'borclu') {
                $suppliers = $suppliers->filter(fn ($r) => $r->bakiye > 0)->values();
            } elseif ($request->tip === 'alacakli') {
                $suppliers = $suppliers->filter(fn ($r) => $r->bakiye < 0)->values();
            }
        }
        return view('reports.supplier-ledger', compact('suppliers'));
    }

    public function supplierLedgerDetail(Supplier $supplier, Request $request)
    {
        $supplier->load(['purchases', 'payments.purchase']);
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        $rows = collect();
        foreach ($supplier->purchases()->where('isCancelled', false)->orderBy('purchaseDate')->orderBy('createdAt')->get() as $p) {
            $rows->push((object) [
                'date' => $p->purchaseDate,
                'type' => 'alis',
                'ref' => $p->purchaseNumber,
                'refId' => $p->id,
                'refRoute' => 'purchases.show',
                'aciklama' => 'Alış ' . $p->purchaseNumber,
                'borc' => (float) $p->grandTotal,
                'alacak' => 0,
            ]);
        }
        foreach ($supplier->payments()->orderBy('paymentDate')->orderBy('createdAt')->get() as $pm) {
            $aciklama = 'Ödeme';
            if ($pm->purchase) {
                $aciklama .= ' - ' . $pm->purchase->purchaseNumber;
            }
            if ($pm->reference) {
                $aciklama .= ' (' . $pm->reference . ')';
            }
            $rows->push((object) [
                'date' => $pm->paymentDate,
                'type' => 'odeme',
                'ref' => null,
                'refId' => null,
                'refRoute' => null,
                'aciklama' => $aciklama,
                'borc' => 0,
                'alacak' => (float) $pm->amount,
            ]);
        }
        $rows = $rows->sortBy('date')->values();
        $openingBalance = 0;
        $filteredRows = collect();
        foreach ($rows as $r) {
            if ($from && $r->date->lt($from)) {
                $openingBalance += $r->borc - $r->alacak;
                continue;
            }
            if ($to && $r->date->gt($to)) {
                continue;
            }
            $openingBalance += $r->borc - $r->alacak;
            $r->bakiye = $openingBalance;
            $filteredRows->push($r);
        }
        return view('reports.supplier-ledger-detail', compact('supplier', 'filteredRows', 'from', 'to', 'openingBalance'));
    }

    public function kdvReport(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $saleItems = SaleItem::whereHas('sale', fn ($q) => $q->whereBetween('saleDate', [$from, $to]))
            ->get();
        $purchaseItems = PurchaseItem::whereHas('purchase', fn ($q) => $q->whereBetween('purchaseDate', [$from, $to]))
            ->get();

        $salesByRate = [];
        foreach ($saleItems as $i) {
            $rate = (float) ($i->kdvRate ?? 18);
            $lineTotal = (float) $i->lineTotal;
            $kdvAmount = round($lineTotal - $lineTotal / (1 + $rate / 100), 2);
            $netAmount = round($lineTotal - $kdvAmount, 2);
            if (!isset($salesByRate[$rate])) {
                $salesByRate[$rate] = ['net' => 0, 'kdv' => 0, 'total' => 0];
            }
            $salesByRate[$rate]['net'] += $netAmount;
            $salesByRate[$rate]['kdv'] += $kdvAmount;
            $salesByRate[$rate]['total'] += $lineTotal;
        }

        $purchasesByRate = [];
        foreach ($purchaseItems as $i) {
            $rate = (float) ($i->kdvRate ?? 18);
            $lineTotal = (float) $i->lineTotal;
            $kdvAmount = round($lineTotal - $lineTotal / (1 + $rate / 100), 2);
            $netAmount = round($lineTotal - $kdvAmount, 2);
            if (!isset($purchasesByRate[$rate])) {
                $purchasesByRate[$rate] = ['net' => 0, 'kdv' => 0, 'total' => 0];
            }
            $purchasesByRate[$rate]['net'] += $netAmount;
            $purchasesByRate[$rate]['kdv'] += $kdvAmount;
            $purchasesByRate[$rate]['total'] += $lineTotal;
        }

        ksort($salesByRate);
        ksort($purchasesByRate);

        return view('reports.kdv', compact('from', 'to', 'salesByRate', 'purchasesByRate'));
    }
}
