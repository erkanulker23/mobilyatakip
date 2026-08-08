<?php

namespace App\Http\Controllers;

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
            ]);

            $cards[] = array_merge($definition, [
                'count' => (int) $query->count(),
                'listUrl' => route('reports.sales', $params),
                'printUrl' => route('reports.sales.print', $params),
            ]);
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
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range(
            $request,
            now()->startOfYear(),
            now()->endOfDay(),
        );

        $personnelOptions = $this->salesPersonnelOptions();
        $filters = $this->salesFilterState($request, $personnelOptions);

        return view('reports.sales', array_merge(
            compact('from', 'to', 'year', 'personnelOptions', 'filters'),
            $this->salesData($from, $to, $request),
        ));
    }

    public function salesPrint(Request $request): View
    {
        ['from' => $from, 'to' => $to, 'year' => $year] = ReportFilters::range(
            $request,
            now()->startOfYear(),
            now()->endOfDay(),
        );

        $personnelOptions = $this->salesPersonnelOptions();
        $filters = $this->salesFilterState($request, $personnelOptions);

        return view('reports.print.sales', array_merge(
            compact('from', 'to', 'year', 'personnelOptions', 'filters'),
            $this->salesData($from, $to, $request),
        ));
    }

    public function upcomingDue(Request $request)
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));
        $personnelOptions = $this->salesPersonnelOptions();
        $filters = $this->salesFilterState($request, $personnelOptions);

        return view('reports.upcoming-due', array_merge(
            $this->upcomingDueData($days, $request),
            compact('personnelOptions', 'filters'),
        ));
    }

    public function upcomingDuePrint(Request $request): View
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));
        $personnelOptions = $this->salesPersonnelOptions();
        $filters = $this->salesFilterState($request, $personnelOptions);

        return view('reports.print.upcoming-due', array_merge(
            $this->upcomingDueData($days, $request),
            compact('personnelOptions', 'filters'),
            ['print' => true, 'forShipment' => false],
        ));
    }

    public function upcomingDueShipmentPrint(Request $request): View
    {
        $days = max(1, min(90, (int) $request->input('days', self::TERMIN_DEFAULT_DAYS)));
        $personnelOptions = $this->salesPersonnelOptions();
        $filters = $this->salesFilterState($request, $personnelOptions);

        return view('reports.print.upcoming-due-shipment', array_merge(
            $this->upcomingDueData($days, $request),
            compact('personnelOptions', 'filters'),
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
        $deliveryStatus = SaleDelivery::isFilterValue($request->input('deliveryStatus'))
            ? $request->input('deliveryStatus')
            : null;
        $odeme = $request->input('odeme');
        $hasStatusFilter = $deliveryStatus !== null || in_array($odeme, ['borclu', 'borcsuz'], true);
        $hasExplicitDates = $request->filled('from') || $request->filled('to') || $request->filled('year');

        $query = Sale::with(['customer', 'personnel'])
            ->where('isCancelled', false);

        if ($hasExplicitDates || ! $hasStatusFilter) {
            $query->whereBetween('saleDate', [$from, $to]);
        }

        if ($request->filled('personnelId')) {
            if ($request->input('personnelId') === 'none') {
                $query->whereNull('personnelId');
            } else {
                $query->where('personnelId', $request->input('personnelId'));
            }
        }

        if ($deliveryStatus) {
            SaleDelivery::applyDeliveryFilter($query, $deliveryStatus);
        }

        if ($odeme === 'borclu') {
            $query->whereRaw('grandTotal - COALESCE(paidAmount, 0) > 0.005');
        } elseif ($odeme === 'borcsuz') {
            $query->whereRaw('ABS(grandTotal - COALESCE(paidAmount, 0)) <= 0.005');
        }

        $sales = $query
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->get();

        return [
            'sales' => $sales,
            'skipDateFilter' => $hasStatusFilter && ! $hasExplicitDates,
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

    /** @return array{personnelId: ?string, odeme: ?string, deliveryStatus: ?string, label: ?string} */
    private function salesFilterState(Request $request, $personnelOptions): array
    {
        $personnelId = $request->input('personnelId');
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
            'odeme' => $odeme,
            'deliveryStatus' => $deliveryStatus,
            'label' => $labels !== [] ? implode(' · ', $labels) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function upcomingDueData(int $days, Request $request): array
    {
        $horizon = Carbon::today()->addDays($days);

        $salesQuery = Sale::with(['customer.city', 'customer.district', 'personnel'])
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

        $sshQuery = ServiceTicket::with(['customer.city', 'customer.district', 'sale'])
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
