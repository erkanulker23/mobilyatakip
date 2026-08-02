<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceTicket;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\CustomerBalance;
use App\Support\ReportFilters;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    private const TERMIN_DEFAULT_DAYS = 14;

    public function index()
    {
        return view('reports.index');
    }

    public function incomeExpense(Request $request)
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range($request);

        return view('reports.income-expense', array_merge(
            compact('from', 'to', 'year'),
            $this->incomeExpenseData($from, $to),
        ));
    }

    public function incomeExpensePrint(Request $request): View
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range($request);

        return view('reports.print.income-expense', array_merge(
            compact('from', 'to', 'year'),
            $this->incomeExpenseData($from, $to),
        ));
    }

    public function sales(Request $request)
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range(
            $request,
            now()->startOfYear(),
            now()->endOfDay(),
        );

        return view('reports.sales', array_merge(
            compact('from', 'to', 'year'),
            $this->salesData($from, $to),
        ));
    }

    public function salesPrint(Request $request): View
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range(
            $request,
            now()->startOfYear(),
            now()->endOfDay(),
        );

        return view('reports.print.sales', array_merge(
            compact('from', 'to', 'year'),
            $this->salesData($from, $to),
        ));
    }

    public function upcomingDue(Request $request)
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));

        return view('reports.upcoming-due', $this->upcomingDueData($days));
    }

    public function upcomingDuePrint(Request $request): View
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));

        return view('reports.print.upcoming-due', array_merge(
            $this->upcomingDueData($days),
            ['print' => true],
        ));
    }

    public function customerLedger(Request $request)
    {
        return view('reports.customer-ledger', [
            'customers' => $this->customerLedgerRows($request),
        ]);
    }

    public function customerLedgerPrint(Request $request): View
    {
        return view('reports.print.customer-ledger', [
            'customers' => $this->customerLedgerRows($request),
            'tip' => $request->input('tip'),
            'print' => true,
        ]);
    }

    public function customerLedgerDetail(Customer $customer, Request $request)
    {
        return view('reports.customer-ledger-detail', $this->customerLedgerDetailData($customer, $request));
    }

    public function customerLedgerDetailPrint(Customer $customer, Request $request): View
    {
        return view('reports.print.customer-ledger-detail', array_merge(
            $this->customerLedgerDetailData($customer, $request),
            ['print' => true],
        ));
    }

    public function supplierLedger(Request $request)
    {
        return view('reports.supplier-ledger', [
            'suppliers' => $this->supplierLedgerRows($request),
        ]);
    }

    public function supplierLedgerPrint(Request $request): View
    {
        return view('reports.print.supplier-ledger', [
            'suppliers' => $this->supplierLedgerRows($request),
            'tip' => $request->input('tip'),
            'print' => true,
        ]);
    }

    public function supplierLedgerDetail(Supplier $supplier, Request $request)
    {
        return view('reports.supplier-ledger-detail', $this->supplierLedgerDetailData($supplier, $request));
    }

    public function supplierLedgerDetailPrint(Supplier $supplier, Request $request): View
    {
        return view('reports.print.supplier-ledger-detail', array_merge(
            $this->supplierLedgerDetailData($supplier, $request),
            ['print' => true],
        ));
    }

    public function kdvReport(Request $request)
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range($request);

        return view('reports.kdv', array_merge(
            compact('from', 'to', 'year'),
            $this->kdvData($from, $to),
        ));
    }

    public function kdvReportPrint(Request $request): View
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range($request);

        return view('reports.print.kdv', array_merge(
            compact('from', 'to', 'year'),
            $this->kdvData($from, $to),
        ));
    }

    /** @return array<string, float> */
    private function incomeExpenseData(Carbon $from, Carbon $to): array
    {
        return [
            'gelir' => (float) Sale::whereBetween('saleDate', [$from, $to])->where('isCancelled', false)->sum('grandTotal'),
            'tahsilat' => (float) CustomerPayment::whereBetween('paymentDate', [$from, $to])->sum('amount'),
            'gider' => (float) Expense::whereBetween('expenseDate', [$from, $to])->sum('amount'),
            'tedarikciOdeme' => (float) SupplierPayment::whereBetween('paymentDate', [$from, $to])->sum('amount'),
        ];
    }

    /** @return array<string, mixed> */
    private function salesData(Carbon $from, Carbon $to): array
    {
        $sales = Sale::with('customer')
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$from, $to])
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->get();

        return [
            'sales' => $sales,
            'totals' => (object) [
                'count' => $sales->count(),
                'grandTotal' => (float) $sales->sum('grandTotal'),
                'paidAmount' => (float) $sales->sum('paidAmount'),
                'remaining' => (float) $sales->sum(fn (Sale $s) => CustomerBalance::saleRemaining($s)),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function upcomingDueData(int $days): array
    {
        $horizon = Carbon::today()->addDays($days);

        return [
            'days' => $days,
            'horizon' => $horizon,
            'upcomingSales' => Sale::with('customer')
                ->where('isCancelled', false)
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $horizon)
                ->orderBy('dueDate')
                ->get(),
            'upcomingServiceTickets' => ServiceTicket::with(['customer', 'sale'])
                ->whereNotIn('status', ['tamamlandi', 'iptal'])
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $horizon)
                ->orderBy('dueDate')
                ->get(),
        ];
    }

    private function customerLedgerRows(Request $request)
    {
        $customers = Customer::with(['sales', 'payments'])->where('isActive', true)->orderBy('name')->get()->map(function ($c) {
            $borc = (float) $c->sales->where('isCancelled', false)->sum('grandTotal');
            $alacak = (float) $c->payments->sum('amount');

            return (object) [
                'customer' => $c,
                'borc' => $borc,
                'alacak' => $alacak,
                'bakiye' => $borc - $alacak,
            ];
        });

        if ($request->filled('tip')) {
            if ($request->tip === 'borclu') {
                $customers = $customers->filter(fn ($r) => $r->bakiye > 0)->values();
            } elseif ($request->tip === 'alacakli') {
                $customers = $customers->filter(fn ($r) => $r->bakiye < 0)->values();
            }
        }

        return $customers;
    }

    /** @return array<string, mixed> */
    private function customerLedgerDetailData(Customer $customer, Request $request): array
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

        return compact('customer', 'filteredRows', 'from', 'to', 'openingBalance');
    }

    private function supplierLedgerRows(Request $request)
    {
        $suppliers = Supplier::with(['purchases', 'payments'])->where('isActive', true)->orderBy('name')->get()->map(function ($s) {
            $borc = (float) $s->purchases->where('isCancelled', false)->sum('grandTotal');
            $alacak = (float) $s->payments->sum('amount');

            return (object) [
                'supplier' => $s,
                'borc' => $borc,
                'alacak' => $alacak,
                'bakiye' => $borc - $alacak,
            ];
        });

        if ($request->filled('tip')) {
            if ($request->tip === 'borclu') {
                $suppliers = $suppliers->filter(fn ($r) => $r->bakiye > 0)->values();
            } elseif ($request->tip === 'alacakli') {
                $suppliers = $suppliers->filter(fn ($r) => $r->bakiye < 0)->values();
            }
        }

        return $suppliers;
    }

    /** @return array<string, mixed> */
    private function supplierLedgerDetailData(Supplier $supplier, Request $request): array
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

        return compact('supplier', 'filteredRows', 'from', 'to', 'openingBalance');
    }

    /** @return array<string, mixed> */
    private function kdvData(Carbon $from, Carbon $to): array
    {
        $saleItems = SaleItem::whereHas('sale', fn ($q) => $q->whereBetween('saleDate', [$from, $to])->where('isCancelled', false))
            ->get();
        $purchaseItems = PurchaseItem::whereHas('purchase', fn ($q) => $q->whereBetween('purchaseDate', [$from, $to])->where('isCancelled', false))
            ->get();

        $salesByRate = [];
        foreach ($saleItems as $i) {
            $rate = (float) ($i->kdvRate ?? 18);
            $lineTotal = (float) $i->lineTotal;
            $kdvAmount = round($lineTotal - $lineTotal / (1 + $rate / 100), 2);
            $netAmount = round($lineTotal - $kdvAmount, 2);
            if (! isset($salesByRate[$rate])) {
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
            if (! isset($purchasesByRate[$rate])) {
                $purchasesByRate[$rate] = ['net' => 0, 'kdv' => 0, 'total' => 0];
            }
            $purchasesByRate[$rate]['net'] += $netAmount;
            $purchasesByRate[$rate]['kdv'] += $kdvAmount;
            $purchasesByRate[$rate]['total'] += $lineTotal;
        }

        $expensesByRate = [];
        $expenses = Expense::whereBetween('expenseDate', [$from, $to])->whereNotNull('kdvRate')->get();
        foreach ($expenses as $e) {
            $rate = (float) ($e->kdvRate ?? 0);
            if ($rate <= 0) {
                continue;
            }
            $amount = (float) $e->amount;
            $kdvAmount = (float) ($e->kdvAmount ?? 0);
            $netAmount = round($amount - $kdvAmount, 2);
            if (! isset($expensesByRate[$rate])) {
                $expensesByRate[$rate] = ['net' => 0, 'kdv' => 0, 'total' => 0];
            }
            $expensesByRate[$rate]['net'] += $netAmount;
            $expensesByRate[$rate]['kdv'] += $kdvAmount;
            $expensesByRate[$rate]['total'] += $amount;
        }

        ksort($salesByRate);
        ksort($purchasesByRate);
        ksort($expensesByRate);

        return compact('salesByRate', 'purchasesByRate', 'expensesByRate');
    }
}
