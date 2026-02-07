<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Quote;
use App\Models\Purchase;
use App\Services\StockService;

class DashboardController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $stats = [
            'salesCount' => Sale::count(),
            'quotesCount' => Quote::count(),
            'purchasesCount' => Purchase::count(),
            'lowStockCount' => $this->stockService->getLowStock()->count(),
        ];
        $recentSales = Sale::with('customer')->orderBy('createdAt', 'desc')->take(5)->get();
        return view('dashboard.index', compact('stats', 'recentSales'));
    }
}
