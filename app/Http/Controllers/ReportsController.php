<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Personnel;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceTicket;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\CustomerBalance;
use App\Support\CustomerLedger;
use App\Support\ReportFilters;
use App\Support\SaleDelivery;
use App\Support\SalesReportQuery;
use App\Support\ServiceTicketStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    private const TERMIN_DEFAULT_DAYS = 14;

    public function index()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $horizon = Carbon::today()->addDays(self::TERMIN_DEFAULT_DAYS);

        $monthlySales = (float) Sale::query()
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('grandTotal');

        $monthlySalesCount = (int) Sale::query()
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();

        $upcomingSalesCount = (int) Sale::query()
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $horizon)
            ->count();

        $upcomingTicketsCount = (int) ServiceTicket::query()
            ->whereNotIn('status', ['tamamlandi', 'iptal'])
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $horizon)
            ->count();

        $overdueSalesCount = (int) Sale::query()
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<', Carbon::today())
            ->count();

        $incomeExpense = $this->incomeExpenseData($monthStart, $monthEnd);
        $monthlyNetCash = $incomeExpense['tahsilat'] - $incomeExpense['gider'] - $incomeExpense['tedarikciOdeme'];

        $customerReceivable = max(0, (float) Sale::where('isCancelled', false)->sum('grandTotal')
            - (float) CustomerPayment::sum('amount'));

        $supplierPayable = max(0, (float) \App\Models\Purchase::where('isCancelled', false)->sum('grandTotal')
            - (float) SupplierPayment::sum('amount'));

        $monthLabel = Carbon::now()->locale('tr')->isoFormat('MMMM YYYY');
        $salesStageReports = $this->salesStageReportCards();
        $branchReports = $this->branchReportCards($monthStart, $monthEnd);

        return view('reports.index', compact(
            'monthlySales',
            'monthlySalesCount',
            'upcomingSalesCount',
            'upcomingTicketsCount',
            'overdueSalesCount',
            'incomeExpense',
            'monthlyNetCash',
            'customerReceivable',
            'supplierPayable',
            'monthLabel',
            'salesStageReports',
            'branchReports',
        ));
    }

    /** @return list<array<string, mixed>> */
    private function salesStageReportCards(): array
    {
        $definitions = [
            [
                'deliveryStatus' => SaleDelivery::FINAL_MEASUREMENT,
                'label' => 'Ölçüye gidilecekler',
                'desc' => 'Kesin ölçü alınacak siparişler',
                'tone' => 'amber',
                'keywords' => 'ölçü kesin ölçü bekliyor',
            ],
            [
                'deliveryStatus' => SaleDelivery::IN_PRODUCTION,
                'label' => 'Üretimde',
                'desc' => 'Atölyede üretimi devam eden siparişler',
                'tone' => 'violet',
                'keywords' => 'üretim atölye',
            ],
            [
                'deliveryStatus' => SaleDelivery::PENDING,
                'label' => 'Teslim bekleyenler',
                'desc' => 'Üretim sonrası teslimat bekleyen siparişler',
                'tone' => 'teal',
                'keywords' => 'teslim bekliyor sevkiyat',
            ],
            [
                'deliveryStatus' => SaleDelivery::IN_DISCUSSION,
                'label' => 'Halen görüşülüyor',
                'desc' => 'Müşteriyle görüşmesi süren siparişler',
                'tone' => 'sky',
                'keywords' => 'görüşme',
            ],
            [
                'deliveryStatus' => SaleDelivery::SSH,
                'label' => 'SSH var',
                'desc' => 'Açık servis kaydı bulunan siparişler',
                'tone' => 'orange',
                'keywords' => 'ssh servis',
            ],
            [
                'odeme' => 'borclu',
                'label' => 'Borçlu siparişler',
                'desc' => 'Tahsilatı tamamlanmamış siparişler',
                'tone' => 'red',
                'keywords' => 'borç tahsilat ödeme',
            ],
        ];

        $cards = [];
        foreach ($definitions as $definition) {
            $query = Sale::query()->where('isCancelled', false);

            if (! empty($definition['deliveryStatus'])) {
                SaleDelivery::applyDeliveryFilter($query, $definition['deliveryStatus']);
            } elseif (($definition['odeme'] ?? null) === 'borclu') {
                $query->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005');
            }

            $params = array_filter([
                'deliveryStatus' => $definition['deliveryStatus'] ?? null,
                'odeme' => $definition['odeme'] ?? null,
                'allTime' => 1,
            ]);

            $cards[] = array_merge($definition, [
                'count' => (int) $query->count(),
                'listUrl' => route('reports.sales', $params),
                'printUrl' => route('reports.sales.print', $params),
            ]);
        }

        return $cards;
    }

    /** @return list<array<string, mixed>> */
    private function branchReportCards(Carbon $monthStart, Carbon $monthEnd): array
    {
        $saleCounts = Sale::query()
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('branchId, COUNT(*) as aggregate')
            ->groupBy('branchId')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->branchStatKey($row->branchId) => (int) $row->aggregate]);

        $ticketCounts = ServiceTicket::query()
            ->whereBetween('openedAt', [$monthStart, $monthEnd])
            ->selectRaw('branchId, COUNT(*) as aggregate')
            ->groupBy('branchId')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->branchStatKey($row->branchId) => (int) $row->aggregate]);

        $openTicketCounts = ServiceTicket::query()
            ->whereNotIn('status', ['tamamlandi', 'iptal'])
            ->selectRaw('branchId, COUNT(*) as aggregate')
            ->groupBy('branchId')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->branchStatKey($row->branchId) => (int) $row->aggregate]);

        $cards = [];
        foreach (Branch::query()->where('isActive', true)->orderBy('name')->get() as $branch) {
            $key = (string) $branch->id;
            $cards[] = [
                'id' => $branch->id,
                'label' => $branch->name,
                'desc' => 'Bu ayın sipariş ve SSH özeti',
                'salesCount' => (int) ($saleCounts[$key] ?? 0),
                'sshCount' => (int) ($ticketCounts[$key] ?? 0),
                'openSsh' => (int) ($openTicketCounts[$key] ?? 0),
                'url' => route('reports.branches', ['branchId' => $branch->id, 'period' => 'this_month']),
                'printUrl' => route('reports.branches.print', ['branchId' => $branch->id, 'period' => 'this_month']),
                'keywords' => 'şube '.$branch->name.' sipariş ssh',
            ];
        }

        $unassignedSales = (int) ($saleCounts['_none'] ?? 0);
        $unassignedSsh = (int) ($ticketCounts['_none'] ?? 0);
        $unassignedOpen = (int) ($openTicketCounts['_none'] ?? 0);
        if ($unassignedSales > 0 || $unassignedSsh > 0 || $unassignedOpen > 0) {
            $cards[] = [
                'id' => 'none',
                'label' => 'Şube belirtilmemiş',
                'desc' => 'Şubeye bağlanmamış sipariş ve SSH kayıtları',
                'salesCount' => $unassignedSales,
                'sshCount' => $unassignedSsh,
                'openSsh' => $unassignedOpen,
                'url' => route('reports.branches', ['branchId' => 'none', 'period' => 'this_month']),
                'printUrl' => route('reports.branches.print', ['branchId' => 'none', 'period' => 'this_month']),
                'keywords' => 'şube belirtilmemiş sipariş ssh',
            ];
        }

        return $cards;
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
        ['from' => $from, 'to' => $to, 'year' => $year, 'month' => $month] = ReportFilters::range($request);

        $personnelOptions = $this->salesPersonnelOptions();
        $branchOptions = Branch::forSelect(false);
        $filters = $this->salesFilterState($request, $personnelOptions, $branchOptions);

        return view('reports.sales', array_merge(
            compact('from', 'to', 'year', 'month', 'personnelOptions', 'branchOptions', 'filters'),
            $this->salesData($from, $to, $request),
        ));
    }

    public function salesPrint(Request $request): View
    {
        ['from' => $from, 'to' => $to, 'year' => $year, 'month' => $month] = ReportFilters::range($request);

        $personnelOptions = $this->salesPersonnelOptions();
        $branchOptions = Branch::forSelect(false);
        $filters = $this->salesFilterState($request, $personnelOptions, $branchOptions);

        return view('reports.print.sales', array_merge(
            compact('from', 'to', 'year', 'month', 'personnelOptions', 'branchOptions', 'filters'),
            $this->salesData($from, $to, $request),
        ));
    }

    public function branches(Request $request)
    {
        ['from' => $from, 'to' => $to, 'year' => $year, 'month' => $month] = ReportFilters::range($request);

        return view('reports.branches', array_merge(
            compact('from', 'to', 'year', 'month'),
            $this->branchReportData($from, $to, $request),
        ));
    }

    public function branchesPrint(Request $request): View
    {
        ['from' => $from, 'to' => $to, 'year' => $year, 'month' => $month] = ReportFilters::range($request);

        return view('reports.print.branches', array_merge(
            compact('from', 'to', 'year', 'month'),
            $this->branchReportData($from, $to, $request),
        ));
    }

    public function upcomingDue(Request $request)
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));
        $personnelOptions = $this->salesPersonnelOptions();
        $branchOptions = Branch::forSelect(false);
        $filters = $this->salesFilterState($request, $personnelOptions, $branchOptions);

        return view('reports.upcoming-due', array_merge(
            $this->upcomingDueData($days, $request),
            compact('personnelOptions', 'branchOptions', 'filters'),
        ));
    }

    public function upcomingDuePrint(Request $request): View
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));
        $personnelOptions = $this->salesPersonnelOptions();
        $branchOptions = Branch::forSelect(false);
        $filters = $this->salesFilterState($request, $personnelOptions, $branchOptions);

        return view('reports.print.upcoming-due', array_merge(
            $this->upcomingDueData($days, $request),
            compact('personnelOptions', 'branchOptions', 'filters'),
            ['print' => true, 'forShipment' => false],
        ));
    }

    public function upcomingDueShipmentPrint(Request $request): View
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));
        $personnelOptions = $this->salesPersonnelOptions();
        $branchOptions = Branch::forSelect(false);
        $filters = $this->salesFilterState($request, $personnelOptions, $branchOptions);

        return view('reports.print.upcoming-due-shipment', array_merge(
            $this->upcomingDueData($days, $request),
            compact('personnelOptions', 'branchOptions', 'filters'),
            ['print' => true, 'forShipment' => true],
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

    /** @return array<string, mixed> */
    private function incomeExpenseData(Carbon $from, Carbon $to): array
    {
        $gelir = (float) Sale::whereBetween('saleDate', [$from, $to])->where('isCancelled', false)->sum('grandTotal');
        $salesCount = (int) Sale::whereBetween('saleDate', [$from, $to])->where('isCancelled', false)->count();

        $tahsilat = (float) CustomerPayment::whereBetween('paymentDate', [$from, $to])->sum('amount');
        $gider = (float) Expense::whereBetween('expenseDate', [$from, $to])->sum('amount');
        $tedarikciOdeme = (float) SupplierPayment::whereBetween('paymentDate', [$from, $to])->sum('amount');

        $alis = (float) Purchase::whereBetween('purchaseDate', [$from, $to])->where('isCancelled', false)->sum('grandTotal');
        $alisCount = (int) Purchase::whereBetween('purchaseDate', [$from, $to])->where('isCancelled', false)->count();

        $toplamCikis = $gider + $tedarikciOdeme;
        $netNakit = $tahsilat - $toplamCikis;
        $donemKar = $gelir - $alis - $gider;
        $tahsilatOrani = $gelir > 0.005 ? round($tahsilat / $gelir * 100, 1) : null;

        $payments = CustomerPayment::with(['customer', 'sale', 'kasa'])
            ->whereBetween('paymentDate', [$from, $to])
            ->orderByDesc('paymentDate')
            ->orderByDesc('createdAt')
            ->get();

        $expenses = Expense::with(['kasa'])
            ->whereBetween('expenseDate', [$from, $to])
            ->orderByDesc('expenseDate')
            ->orderByDesc('createdAt')
            ->get();

        $supplierPayments = SupplierPayment::with(['supplier', 'purchase', 'kasa'])
            ->whereBetween('paymentDate', [$from, $to])
            ->orderByDesc('paymentDate')
            ->orderByDesc('createdAt')
            ->get();

        $tahsilatByType = $payments
            ->groupBy(fn (CustomerPayment $p) => $p->paymentType ?: 'diger')
            ->map(fn ($items, $type) => (object) [
                'type' => $type,
                'label' => \App\Support\PaymentType::label($type),
                'total' => (float) $items->sum('amount'),
                'count' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $giderByCategory = $expenses
            ->groupBy(fn (Expense $e) => filled($e->category) ? $e->category : 'Diğer')
            ->map(fn ($items, $category) => (object) [
                'category' => $category,
                'total' => (float) $items->sum('amount'),
                'count' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $tedarikciBySupplier = $supplierPayments
            ->groupBy(fn (SupplierPayment $p) => $p->supplier?->name ?? '—')
            ->map(fn ($items, $name) => (object) [
                'name' => $name,
                'total' => (float) $items->sum('amount'),
                'count' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'gelir' => $gelir,
            'salesCount' => $salesCount,
            'tahsilat' => $tahsilat,
            'gider' => $gider,
            'tedarikciOdeme' => $tedarikciOdeme,
            'alis' => $alis,
            'alisCount' => $alisCount,
            'toplamCikis' => $toplamCikis,
            'netNakit' => $netNakit,
            'donemKar' => $donemKar,
            'tahsilatOrani' => $tahsilatOrani,
            'tahsilatByType' => $tahsilatByType,
            'giderByCategory' => $giderByCategory,
            'tedarikciBySupplier' => $tedarikciBySupplier,
            'payments' => $payments,
            'expenses' => $expenses,
            'supplierPayments' => $supplierPayments,
        ];
    }

    /** @return array<string, mixed> */
    private function salesData(Carbon $from, Carbon $to, Request $request): array
    {
        ['query' => $query, 'applyDateFilter' => $applyDateFilter, 'statusOnlyList' => $statusOnlyList] = SalesReportQuery::fromRequest($from, $to, $request);

        $sales = $query
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->get();

        return [
            'sales' => $sales,
            'applyDateFilter' => $applyDateFilter,
            'statusOnlyList' => $statusOnlyList,
            'totals' => (object) [
                'count' => $sales->count(),
                'grandTotal' => (float) $sales->sum('grandTotal'),
                'paidAmount' => (float) $sales->sum('paidAmount'),
                'remaining' => (float) $sales->sum(fn (Sale $s) => CustomerBalance::saleRemaining($s)),
            ],
        ];
    }

    /** Sistem hesabı bağlı aktif personel */
    private function salesPersonnelOptions()
    {
        return Personnel::query()
            ->where('isActive', true)
            ->whereNotNull('userId')
            ->orderBy('name')
            ->get();
    }

    /** @return array{personnelId: ?string, branchId: ?string, odeme: ?string, deliveryStatus: ?string, label: ?string} */
    private function salesFilterState(Request $request, $personnelOptions, $branchOptions = null): array
    {
        $personnelId = $request->input('personnelId');
        $branchId = $request->input('branchId');
        $odeme = $request->input('odeme');
        $deliveryStatus = SaleDelivery::isFilterValue($request->input('deliveryStatus'))
            ? $request->input('deliveryStatus')
            : null;

        $labels = [];
        if ($personnelId === 'none') {
            $labels[] = 'Personel atanmamış';
        } elseif ($personnelId) {
            $person = $personnelOptions->firstWhere('id', $personnelId)
                ?? Personnel::find($personnelId);
            $labels[] = 'Personel: ' . ($person?->name ?? '—');
        }

        if ($branchId === 'none') {
            $labels[] = 'Şube belirtilmemiş';
        } elseif ($branchId) {
            $branch = ($branchOptions ?: collect())->firstWhere('id', $branchId)
                ?? Branch::find($branchId);
            $labels[] = 'Şube: ' . ($branch?->name ?? '—');
        }

        if ($odeme === 'borclu') {
            $labels[] = 'Borçlu';
        } elseif ($odeme === 'borcsuz') {
            $labels[] = 'Borçsuzlar';
        }

        if ($deliveryStatus) {
            $labels[] = SaleDelivery::filterOptions()[$deliveryStatus];
        }

        return [
            'personnelId' => $personnelId,
            'branchId' => $branchId,
            'odeme' => $odeme,
            'deliveryStatus' => $deliveryStatus,
            'label' => $labels !== [] ? implode(' · ', $labels) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function branchReportData(Carbon $from, Carbon $to, Request $request): array
    {
        $branches = Branch::query()->orderBy('name')->get();
        $selectedBranchId = $request->input('branchId');

        $saleStats = Sale::query()
            ->where('isCancelled', false)
            ->whereBetween('saleDate', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('branchId, COUNT(*) as sale_count, COALESCE(SUM(grandTotal), 0) as grand_total, COALESCE(SUM(paidAmount), 0) as paid_amount')
            ->groupBy('branchId')
            ->get()
            ->keyBy(fn ($row) => $this->branchStatKey($row->branchId));

        $ticketStats = ServiceTicket::query()
            ->whereBetween('openedAt', [$from, $to])
            ->selectRaw("branchId, COUNT(*) as ticket_count,
                SUM(CASE WHEN status NOT IN ('tamamlandi', 'iptal') THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'tamamlandi' THEN 1 ELSE 0 END) as done_count")
            ->groupBy('branchId')
            ->get()
            ->keyBy(fn ($row) => $this->branchStatKey($row->branchId));

        $rows = [];
        foreach ($branches as $branch) {
            $key = (string) $branch->id;
            $rows[] = $this->makeBranchReportRow(
                $key,
                $branch->displayName(),
                $saleStats->get($key),
                $ticketStats->get($key),
                (bool) $branch->isActive,
            );
        }

        $unassignedSales = $saleStats->get('_none');
        $unassignedTickets = $ticketStats->get('_none');
        if ($unassignedSales || $unassignedTickets) {
            $rows[] = $this->makeBranchReportRow(
                'none',
                'Şube belirtilmemiş',
                $unassignedSales,
                $unassignedTickets,
                true,
            );
        }

        $totals = [
            'sale_count' => (int) collect($rows)->sum('sale_count'),
            'grand_total' => (float) collect($rows)->sum('grand_total'),
            'paid_amount' => (float) collect($rows)->sum('paid_amount'),
            'remaining' => (float) collect($rows)->sum('remaining'),
            'ticket_count' => (int) collect($rows)->sum('ticket_count'),
            'open_count' => (int) collect($rows)->sum('open_count'),
            'done_count' => (int) collect($rows)->sum('done_count'),
        ];

        $summaryTotals = $totals;
        $detailSales = collect();
        $detailTickets = collect();
        $selectedLabel = null;
        if (filled($selectedBranchId)) {
            $selectedRow = collect($rows)->first(fn ($row) => (string) $row['id'] === (string) $selectedBranchId);
            $selectedLabel = $selectedRow['name'] ?? null;
            if ($selectedRow) {
                $summaryTotals = [
                    'sale_count' => $selectedRow['sale_count'],
                    'grand_total' => $selectedRow['grand_total'],
                    'paid_amount' => $selectedRow['paid_amount'],
                    'remaining' => $selectedRow['remaining'],
                    'ticket_count' => $selectedRow['ticket_count'],
                    'open_count' => $selectedRow['open_count'],
                    'done_count' => $selectedRow['done_count'],
                ];
            }

            $detailSales = Sale::query()
                ->with(['customer', 'branch'])
                ->where('isCancelled', false)
                ->whereBetween('saleDate', [$from->toDateString(), $to->toDateString()])
                ->when(
                    $selectedBranchId === 'none',
                    fn ($q) => $q->whereNull('branchId'),
                    fn ($q) => $q->where('branchId', $selectedBranchId),
                )
                ->orderByDesc('saleDate')
                ->orderByDesc('createdAt')
                ->limit(150)
                ->get();

            $detailTickets = ServiceTicket::query()
                ->with(['customer', 'sale', 'branch'])
                ->whereBetween('openedAt', [$from, $to])
                ->when(
                    $selectedBranchId === 'none',
                    fn ($q) => $q->whereNull('branchId'),
                    fn ($q) => $q->where('branchId', $selectedBranchId),
                )
                ->orderByDesc('openedAt')
                ->limit(150)
                ->get();
        }

        return [
            'branchRows' => $rows,
            'branchTotals' => $totals,
            'summaryTotals' => $summaryTotals,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'selectedLabel' => $selectedLabel,
            'detailSales' => $detailSales,
            'detailTickets' => $detailTickets,
        ];
    }

    private function branchStatKey(mixed $branchId): string
    {
        return $branchId === null || $branchId === '' ? '_none' : (string) $branchId;
    }

    private function applyBranchFilter($query, Request $request): void
    {
        if (! $request->filled('branchId')) {
            return;
        }

        if ($request->input('branchId') === 'none') {
            $query->whereNull('branchId');

            return;
        }

        $query->where('branchId', $request->input('branchId'));
    }

    private function makeBranchReportRow(string $id, string $name, mixed $saleRow, mixed $ticketRow, bool $isActive): array
    {
        $grand = (float) ($saleRow?->grand_total ?? 0);
        $paid = (float) ($saleRow?->paid_amount ?? 0);

        return [
            'id' => $id,
            'name' => $name,
            'isActive' => $isActive,
            'sale_count' => (int) ($saleRow?->sale_count ?? 0),
            'grand_total' => $grand,
            'paid_amount' => $paid,
            'remaining' => round($grand - $paid, 2),
            'ticket_count' => (int) ($ticketRow?->ticket_count ?? 0),
            'open_count' => (int) ($ticketRow?->open_count ?? 0),
            'done_count' => (int) ($ticketRow?->done_count ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function upcomingDueData(int $days, Request $request): array
    {
        $horizon = Carbon::today()->addDays($days);

        $salesQuery = Sale::with(['customer.city', 'customer.district', 'personnel', 'branch'])
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $horizon);

        if ($request->filled('personnelId')) {
            if ($request->input('personnelId') === 'none') {
                $salesQuery->whereNull('personnelId');
            } else {
                $salesQuery->where('personnelId', $request->input('personnelId'));
            }
        }

        $this->applyBranchFilter($salesQuery, $request);

        $sshQuery = ServiceTicket::with(['customer.city', 'customer.district', 'sale', 'branch'])
            ->whereNotIn('status', ['tamamlandi', 'iptal'])
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $horizon);

        if ($request->filled('personnelId')) {
            if ($request->input('personnelId') === 'none') {
                $sshQuery->where(function ($q) {
                    $q->whereNull('saleId')
                        ->orWhereHas('sale', fn ($s) => $s->whereNull('personnelId'));
                });
            } else {
                $sshQuery->whereHas('sale', fn ($s) => $s->where('personnelId', $request->input('personnelId')));
            }
        }

        $this->applyBranchFilter($sshQuery, $request);

        return [
            'days' => $days,
            'horizon' => $horizon,
            'upcomingSales' => $salesQuery->orderBy('dueDate')->get(),
            'upcomingServiceTickets' => $sshQuery->orderBy('dueDate')->get(),
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
        return CustomerLedger::detailDataFromRequest($customer, $request);
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
