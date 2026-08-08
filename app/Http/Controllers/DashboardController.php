<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KasaHareket;
use App\Models\Sale;
use App\Models\Quote;
use App\Models\Purchase;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Services\StockService;
use App\Support\SaleDelivery;
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

        $stats = $showDashboardMetrics ? [
            'salesCount' => Sale::where('isCancelled', false)->count(),
            'quotesCount' => Quote::count(),
            'purchasesCount' => Purchase::count(),
            'lowStockCount' => $this->stockService->getLowStock()->count(),
        ] : ['salesCount' => 0, 'quotesCount' => 0, 'purchasesCount' => 0, 'lowStockCount' => 0];

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        if ($showDashboardMetrics) {
            $todaySalesBase = Sale::query()
                ->where('isCancelled', false)
                ->whereDate('saleDate', $today);

            $todaySalesCount = (int) (clone $todaySalesBase)->count();
            $todaySalesTotal = (float) (clone $todaySalesBase)->sum('grandTotal');

            $todayKasaInflow = (float) KasaHareket::query()
                ->ledger()
                ->where('refType', 'customer_payment')
                ->whereDate('movementDate', $today)
                ->where('amount', '>', 0)
                ->sum('amount');

            $weekSalesBase = Sale::query()
                ->where('isCancelled', false)
                ->whereBetween('saleDate', [$weekStart->toDateString(), $weekEnd->toDateString()]);

            $weekSalesCount = (int) (clone $weekSalesBase)->count();
            $weekSalesTotal = (float) (clone $weekSalesBase)->sum('grandTotal');

            $weekKasaInflow = (float) KasaHareket::query()
                ->ledger()
                ->where('refType', 'customer_payment')
                ->whereBetween('movementDate', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->where('amount', '>', 0)
                ->sum('amount');

            $weekRangeLabel = $weekStart->locale('tr')->isoFormat('D MMM') . ' – ' . $weekEnd->locale('tr')->isoFormat('D MMM');

            $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            $monthlySalesBase = Sale::query()
                ->where('isCancelled', false)
                ->whereBetween('saleDate', [$monthStart->toDateString(), $monthEnd->toDateString()]);

            $monthlySales = (float) (clone $monthlySalesBase)->sum('grandTotal');
            $monthlyCollected = (float) (clone $monthlySalesBase)->sum('paidAmount');
            $monthlyReceivable = (float) (clone $monthlySalesBase)
                ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as total')
                ->value('total');
            $monthlySalesCount = (int) (clone $monthlySalesBase)->count();

            $lastMonthSalesBase = Sale::query()
                ->where('isCancelled', false)
                ->whereBetween('saleDate', [$lastMonthStart->toDateString(), $lastMonthEnd->toDateString()]);

            $lastMonthSales = (float) (clone $lastMonthSalesBase)->sum('grandTotal');

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
            $weekSalesCount = 0;
            $weekSalesTotal = 0;
            $weekKasaInflow = 0;
            $weekRangeLabel = $weekStart->locale('tr')->isoFormat('D MMM') . ' – ' . $weekEnd->locale('tr')->isoFormat('D MMM');
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

        $urgentDueSales = Sale::with('customer')
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminAlertHorizon)
            ->orderBy('dueDate')
            ->get();

        $upcomingSales = Sale::with('customer')
            ->where('isCancelled', false)
            ->pendingDelivery()
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminHorizon)
            ->orderBy('dueDate')
            ->take(8)
            ->get();

        $upcomingServiceTickets = ServiceTicket::with(['customer', 'sale'])
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

        $finalMeasurementSales = Sale::with(['customer', 'personnel'])
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

        return view('dashboard.index', compact(
            'showDashboardMetrics',
            'showPersonalTasks',
            'taskPersonnel',
            'personalPersonnelId',
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
            'weekSalesCount',
            'weekSalesTotal',
            'weekKasaInflow',
            'weekStart',
            'weekEnd',
            'weekRangeLabel',
        ) + ['terminAlertDays' => self::TERMIN_ALERT_DAYS]);
    }

    public function tasks()
    {
        $showPersonalTasks = auth()->user()?->showsPersonalTasksDashboard() ?? false;

        return view('tasks.index', [
            'taskPersonnel' => $this->taskPersonnelForCurrentUser(),
            'showPersonalTasks' => $showPersonalTasks,
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
}
