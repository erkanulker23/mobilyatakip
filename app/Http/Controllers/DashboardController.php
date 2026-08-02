<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Quote;
use App\Models\Purchase;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Services\StockService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private const TERMIN_WINDOW_DAYS = 14;

    private const TERMIN_ALERT_DAYS = 3;

    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $stats = [
            'salesCount' => Sale::where('isCancelled', false)->count(),
            'quotesCount' => Quote::count(),
            'purchasesCount' => Purchase::count(),
            'lowStockCount' => $this->stockService->getLowStock()->count(),
        ];

        $last3Days = collect(range(2, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'label' => $date->locale('tr')->isoFormat('ddd'),
                'count' => Sale::where('isCancelled', false)
                    ->whereDate('saleDate', $date)
                    ->count(),
            ];
        });

        $monthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $monthEnd = Carbon::now()->endOfMonth();

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
        $lastMonthSalesCount = (int) (clone $lastMonthSalesBase)->count();

        $monthlyChange = $lastMonthSales > 0
            ? round((($monthlySales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : ($monthlySales > 0 ? 100 : 0);

        $avgOrderValue = $stats['salesCount'] > 0
            ? (float) Sale::where('isCancelled', false)->avg('grandTotal')
            : 0;

        $totalCustomers = Customer::where('isActive', true)->count();

        $recentSales = Sale::with('customer')
            ->where('isCancelled', false)
            ->orderBy('createdAt', 'desc')
            ->take(5)
            ->get();

        $terminHorizon = Carbon::today()->addDays(self::TERMIN_WINDOW_DAYS);
        $terminAlertHorizon = Carbon::today()->addDays(self::TERMIN_ALERT_DAYS);

        $urgentDueSales = Sale::with('customer')
            ->where('isCancelled', false)
            ->whereNull('deliveredAt')
            ->whereNotNull('dueDate')
            ->whereDate('dueDate', '<=', $terminAlertHorizon)
            ->orderBy('dueDate')
            ->get();

        $upcomingSales = Sale::with('customer')
            ->where('isCancelled', false)
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
            ->take(5)
            ->get();

        $employeeOfTheMonth = $topPersonnel->first();
        $employeeOfTheMonthLabel = Carbon::now()->locale('tr')->isoFormat('MMMM YYYY');

        return view('dashboard.index', compact(
            'stats',
            'last3Days',
            'monthlySales',
            'monthlyCollected',
            'monthlyReceivable',
            'monthlySalesCount',
            'lastMonthSales',
            'lastMonthSalesCount',
            'monthlyChange',
            'avgOrderValue',
            'totalCustomers',
            'recentSales',
            'upcomingSales',
            'upcomingServiceTickets',
            'urgentDueSales',
            'topPersonnel',
            'employeeOfTheMonth',
            'employeeOfTheMonthLabel',
        ) + ['terminAlertDays' => self::TERMIN_ALERT_DAYS]);
    }

    public function tasks()
    {
        return view('tasks.index', [
            'taskPersonnel' => $this->taskPersonnelForCurrentUser(),
        ]);
    }

    private function taskPersonnelForCurrentUser()
    {
        return auth()->user()?->isAdmin()
            ? \App\Models\Personnel::where('isActive', true)->orderBy('name')->get(['id', 'name', 'title', 'userId'])
            : collect();
    }
}
