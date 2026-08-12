<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\Personnel;
use App\Models\Sale;
use App\Models\Quote;
use App\Models\Purchase;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Services\StockService;
use App\Support\PaymentType;
use App\Support\PeriodAccounting;
use App\Support\PersonnelSalesStats;
use App\Support\SaleDelivery;
use App\Support\SalesMonthStats;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private const TERMIN_WINDOW_DAYS = 14;

    private const TERMIN_ALERT_DAYS = 3;

    public function __construct(private StockService $stockService) {}

    public function index()
    {
        if (auth()->user()?->isWorkshop() && ! auth()->user()?->isAdmin()) {
            return redirect()->route('workshop.dashboard');
        }

        $showDashboardMetrics = auth()->user()?->canSeeDashboardMetrics() ?? false;
        $showPersonalTasks = auth()->user()?->showsPersonalTasksDashboard() ?? false;
        $taskPersonnel = $showPersonalTasks ? $this->taskPersonnelForCurrentUser() : collect();
        $personalPersonnelId = auth()->user()?->personnel?->id;
        $personnelDashboard = $showPersonalTasks && auth()->user()?->personnel
            ? $this->buildPersonnelDashboardData(auth()->user()->personnel)
            : null;

        $stats = $showDashboardMetrics ? [
            'salesCount' => Sale::where('isCancelled', false)->count(),
            'quotesCount' => Quote::count(),
            'purchasesCount' => Purchase::count(),
            'lowStockCount' => $this->stockService->getLowStock()->count(),
        ] : ['salesCount' => 0, 'quotesCount' => 0, 'purchasesCount' => 0, 'lowStockCount' => 0];

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $today = Carbon::today();
        $monthRange = SalesMonthStats::currentMonthRange($today);
        $weekStart = $monthRange['start'];
        $weekEnd = $monthRange['end'];
        $weekQueryEnd = $today->copy();
        $weekRangeLabel = $monthRange['label'];
        $currentMonthSalesQuery = SalesMonthStats::currentMonthQuery($today);

        if ($showDashboardMetrics) {
            $todaySalesBase = Sale::query()
                ->where('isCancelled', false)
                ->whereDate('saleDate', $today);

            $todaySalesCount = (int) (clone $todaySalesBase)->count();
            $todaySalesTotal = (float) (clone $todaySalesBase)->sum('grandTotal');

            $todayKasaBreakdown = $this->buildTodayKasaBreakdown($today);
            $todayKasaInflow = $todayKasaBreakdown['total'];

            $weekSalesCount = SalesMonthStats::count($currentMonthSalesQuery);
            $weekSalesTotal = SalesMonthStats::turnover($currentMonthSalesQuery);

            $monthAccounting = PeriodAccounting::forRange($weekStart, $weekQueryEnd);
            $weekKasaInflow = $monthAccounting['cashCollections'];
            $monthlySalesCollected = $monthAccounting['collectedOnSales'];
            $monthCashOnPeriodSales = $monthAccounting['cashOnPeriodSales'];
            $monthCashOnPriorSales = $monthAccounting['cashOnPriorSales'];
            $monthCashUnallocated = $monthAccounting['cashUnallocated'];

            $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            $monthlySales = $monthAccounting['revenue'];
            $monthlyCollected = $monthAccounting['collectedOnSales'];
            $monthlyReceivable = $monthAccounting['receivable'];
            $monthlySalesCount = $monthAccounting['saleCount'];

            $lastMonthSales = SalesMonthStats::turnover(
                SalesMonthStats::salesQuery($lastMonthStart, $lastMonthEnd)
            );

            $monthlyChange = $lastMonthSales > 0
                ? round((($monthlySales - $lastMonthSales) / $lastMonthSales) * 100, 1)
                : ($monthlySales > 0 ? 100 : 0);

            $totalCustomers = Customer::where('isActive', true)->count();

            $recentSales = Sale::with('customer')
                ->where('isCancelled', false)
                ->orderBy('createdAt', 'desc')
                ->take(5)
                ->get();

            $topPersonnel = Sale::query()
                ->join('personnel', 'sales.personnelId', '=', 'personnel.id')
                ->where('sales.isCancelled', false)
                ->whereNotNull('sales.personnelId')
                ->where('personnel.isActive', true)
                ->where('sales.saleDate', '>=', $monthStart)
                ->select(
                    'personnel.id',
                    'personnel.name',
                    'personnel.title',
                    'personnel.photoUrl',
                )
                ->selectRaw('COUNT(*) as sales_count')
                ->selectRaw('COALESCE(SUM(sales.grandTotal), 0) as sales_total')
                ->groupBy('personnel.id', 'personnel.name', 'personnel.title', 'personnel.photoUrl')
                ->orderByDesc('sales_count')
                ->orderByDesc('sales_total')
                ->take(3)
                ->get();

            $employeeOfTheMonth = $topPersonnel->first();
            $employeeOfTheMonthLabel = Carbon::now()->locale('tr')->isoFormat('MMMM YYYY');

            $deliveryScore = SaleDelivery::deliveryScoreStats();
            $deliveryScoreThisMonth = SaleDelivery::deliveryScoreStats($monthStart, $monthEnd);
        } else {
            $todaySalesCount = 0;
            $todaySalesTotal = 0;
            $todayKasaInflow = 0;
            $todayKasaBreakdown = ['total' => 0, 'nakitTotal' => 0, 'kasaTotal' => 0, 'supplierTotal' => 0, 'byType' => collect(), 'byKasa' => collect()];
            $weekSalesCount = 0;
            $weekSalesTotal = 0;
            $weekKasaInflow = 0;
            $monthlySalesCollected = 0;
            $monthCashOnPeriodSales = 0;
            $monthCashOnPriorSales = 0;
            $monthCashUnallocated = 0;
            $weekRangeLabel = SalesMonthStats::currentMonthRange($today)['label'];
            $monthlySales = 0;
            $monthlyCollected = 0;
            $monthlyReceivable = 0;
            $monthlySalesCount = 0;
            $monthlyChange = 0;
            $totalCustomers = 0;
            $recentSales = collect();
            $topPersonnel = collect();
            $employeeOfTheMonth = null;
            $employeeOfTheMonthLabel = '';
            $deliveryScore = ['rate' => null, 'onTimeCount' => 0, 'lateCount' => 0, 'totalCount' => 0, 'overduePendingCount' => 0];
            $deliveryScoreThisMonth = $deliveryScore;
        }

        $last3Days = collect(range(2, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'label' => $date->locale('tr')->isoFormat('ddd'),
                'count' => Sale::where('isCancelled', false)
                    ->whereDate('saleDate', $date)
                    ->count(),
            ];
        });

        $terminHorizon = Carbon::today()->addDays(self::TERMIN_WINDOW_DAYS);
        $terminAlertHorizon = Carbon::today()->addDays(self::TERMIN_ALERT_DAYS);

        if ($showPersonalTasks) {
            $urgentDueSales = collect();
            $upcomingSales = collect();
            $upcomingServiceTickets = collect();
            $finalMeasurementSales = collect();
            $upcomingSalesCount = 0;
            $upcomingSshCount = 0;
            $finalMeasurementCount = 0;
            $defaultWorkTab = 'termin';
        } else {
            $urgentDueSales = Sale::with(['customer', 'branch'])
                ->where('isCancelled', false)
                ->pendingDelivery()
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $terminAlertHorizon)
                ->orderBy('dueDate')
                ->get();

            $upcomingSales = Sale::with(['customer', 'branch'])
                ->where('isCancelled', false)
                ->pendingDelivery()
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $terminHorizon)
                ->orderBy('dueDate')
                ->take(8)
                ->get();

            $upcomingServiceTickets = ServiceTicket::with(['customer', 'sale', 'branch'])
                ->whereNotIn('status', ['tamamlandi', 'iptal'])
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $terminHorizon)
                ->orderBy('dueDate')
                ->take(8)
                ->get();

            $finalMeasurementCount = (int) Sale::query()
                ->where('isCancelled', false)
                ->where('needsFinalMeasurement', true)
                ->count();

            $finalMeasurementSales = Sale::with(['customer', 'personnel', 'branch'])
                ->where('isCancelled', false)
                ->where('needsFinalMeasurement', true)
                ->orderBy('saleDate')
                ->orderBy('createdAt')
                ->take(8)
                ->get();

            $upcomingSalesCount = (int) Sale::query()
                ->where('isCancelled', false)
                ->pendingDelivery()
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $terminHorizon)
                ->count();

            $upcomingSshCount = (int) ServiceTicket::query()
                ->whereNotIn('status', ['tamamlandi', 'iptal'])
                ->whereNotNull('dueDate')
                ->whereDate('dueDate', '<=', $terminHorizon)
                ->count();

            $defaultWorkTab = $urgentDueSales->isNotEmpty() || $upcomingSales->isNotEmpty()
                ? 'termin'
                : ($finalMeasurementSales->isNotEmpty() ? 'olcu' : ($upcomingServiceTickets->isNotEmpty() ? 'ssh' : 'termin'));
        }

        return view('dashboard.index', compact(
            'showDashboardMetrics',
            'showPersonalTasks',
            'taskPersonnel',
            'personalPersonnelId',
            'personnelDashboard',
            'stats',
            'monthlySales',
            'monthlyCollected',
            'monthlyReceivable',
            'monthlySalesCount',
            'monthlyChange',
            'totalCustomers',
            'recentSales',
            'upcomingSales',
            'upcomingSalesCount',
            'upcomingServiceTickets',
            'upcomingSshCount',
            'finalMeasurementSales',
            'finalMeasurementCount',
            'urgentDueSales',
            'topPersonnel',
            'employeeOfTheMonth',
            'employeeOfTheMonthLabel',
            'defaultWorkTab',
            'deliveryScore',
            'deliveryScoreThisMonth',
            'todaySalesCount',
            'todaySalesTotal',
            'todayKasaInflow',
            'todayKasaBreakdown',
            'weekSalesCount',
            'weekSalesTotal',
            'weekKasaInflow',
            'monthlySalesCollected',
            'monthCashOnPeriodSales',
            'monthCashOnPriorSales',
            'monthCashUnallocated',
            'weekStart',
            'weekEnd',
            'weekRangeLabel',
        ) + ['terminAlertDays' => self::TERMIN_ALERT_DAYS, 'monthFilterFrom' => $weekStart->toDateString(), 'monthFilterTo' => $weekQueryEnd->toDateString()]);
    }

    public function tasks()
    {
        return view('tasks.index', [
            'taskPersonnel' => $this->assignableTaskPersonnel(),
            'personalTasksView' => false,
            'personalPersonnelId' => auth()->user()?->personnel?->id,
        ]);
    }

    private function taskPersonnelForCurrentUser()
    {
        $user = auth()->user();
        if ($user?->isAdmin()) {
            return \App\Models\Personnel::where('isActive', true)->orderBy('name')->get(['id', 'name', 'title', 'userId', 'photoUrl']);
        }

        if ($user?->personnel && $user->personnel->isActive) {
            return collect([$user->personnel]);
        }

        return collect();
    }

    private function assignableTaskPersonnel()
    {
        $user = auth()->user();
        if ($user?->isAdmin() || $user?->canManageTeamTasks()) {
            return \App\Models\Personnel::where('isActive', true)->orderBy('name')->get(['id', 'name', 'title', 'userId', 'photoUrl']);
        }

        return $this->taskPersonnelForCurrentUser();
    }

    private function buildTodayKasaBreakdown(Carbon $date): array
    {
        $payments = CustomerPayment::query()
            ->whereDate('paymentDate', $date)
            ->get(['id', 'amount', 'kasaId', 'paymentType']);

        if ($payments->isEmpty()) {
            return [
                'total' => 0.0,
                'nakitTotal' => 0.0,
                'kasaTotal' => 0.0,
                'supplierTotal' => 0.0,
                'byType' => collect(),
                'byKasa' => collect(),
            ];
        }

        $kasalar = Kasa::query()
            ->whereIn('id', $payments->pluck('kasaId')->filter()->unique())
            ->get(['id', 'name', 'type', 'bankName'])
            ->keyBy('id');

        $byType = [];
        $byKasa = [];
        $kasaTotal = 0.0;
        $supplierTotal = 0.0;

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $storedType = $payment->paymentType;
            $kasa = $payment->kasaId ? $kasalar->get($payment->kasaId) : null;
            $isSupplierPay = $storedType === 'tedarikciye_ode';
            $paymentType = $isSupplierPay
                ? 'tedarikciye_ode'
                : PaymentType::effectiveTypeForKasaMovement($storedType, $kasa);

            $byType[$paymentType] = ($byType[$paymentType] ?? 0) + $amount;

            if ($isSupplierPay) {
                $supplierTotal += $amount;
                $byKasa['_supplier'] = ($byKasa['_supplier'] ?? 0) + $amount;
                continue;
            }

            $kasaTotal += $amount;
            if ($payment->kasaId) {
                $kasaKey = (string) $payment->kasaId;
                $byKasa[$kasaKey] = ($byKasa[$kasaKey] ?? 0) + $amount;
            }
        }

        $byTypeRows = collect($byType)
            ->map(fn (float $amount, string $type) => [
                'type' => $type,
                'label' => PaymentType::label($type),
                'amount' => $amount,
            ])
            ->sortByDesc('amount')
            ->values();

        $byKasaRows = collect($byKasa)
            ->map(function (float $amount, string $kasaId) use ($kasalar) {
                if ($kasaId === '_supplier') {
                    return [
                        'id' => null,
                        'name' => 'Tedarikçiye ödeme',
                        'typeLabel' => 'Kasa dışı',
                        'amount' => $amount,
                    ];
                }

                $kasa = $kasalar->get($kasaId);

                return [
                    'id' => $kasaId,
                    'name' => $kasa?->name ?? 'Kasa',
                    'typeLabel' => $kasa ? PaymentType::kasaTypeLabel($kasa) : '',
                    'amount' => $amount,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'total' => (float) $payments->sum('amount'),
            'nakitTotal' => (float) ($byType['nakit'] ?? 0),
            'kasaTotal' => $kasaTotal,
            'supplierTotal' => $supplierTotal,
            'byType' => $byTypeRows,
            'byKasa' => $byKasaRows,
        ];
    }

    private function buildPersonnelDashboardData(Personnel $personnel): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $terminHorizon = Carbon::today()->addDays(self::TERMIN_WINDOW_DAYS);

        $activeSalesQuery = $personnel->sales()->where('isCancelled', false);
        $monthSalesQuery = (clone $activeSalesQuery)
            ->whereBetween('saleDate', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $stats = [
            'monthCount' => (int) (clone $monthSalesQuery)->count(),
            'monthTotal' => (float) (clone $monthSalesQuery)->sum('grandTotal'),
            'monthCollected' => PersonnelSalesStats::collectedInPeriod($personnel, $monthStart, $monthEnd),
            'monthReceivable' => PersonnelSalesStats::receivableTotal(clone $monthSalesQuery),
            'totalReceivable' => PersonnelSalesStats::receivableTotal(clone $activeSalesQuery),
            'activeCount' => (int) (clone $activeSalesQuery)->count(),
        ];

        $recentSales = $personnel->sales()
            ->with('customer')
            ->where('isCancelled', false)
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->take(5)
            ->get();

        $recentPayments = CustomerPayment::query()
            ->with(['sale.customer', 'customer'])
            ->whereHas('sale', fn ($q) => $q
                ->where('personnelId', $personnel->id)
                ->where('isCancelled', false))
            ->orderByDesc('paymentDate')
            ->orderByDesc('createdAt')
            ->take(5)
            ->get();

        $upcomingDueSales = $personnel->sales()
            ->with('customer')
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminHorizon)
            ->orderBy('dueDate')
            ->take(8)
            ->get();

        $upcomingDueCount = (int) $personnel->sales()
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminHorizon)
            ->count();

        return [
            'personnel' => $personnel,
            'stats' => $stats,
            'recentSales' => $recentSales,
            'recentPayments' => $recentPayments,
            'upcomingDueSales' => $upcomingDueSales,
            'upcomingDueCount' => $upcomingDueCount,
        ];
    }
}
