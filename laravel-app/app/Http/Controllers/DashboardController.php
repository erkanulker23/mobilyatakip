<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Kasa;
use App\Models\Sale;
use App\Models\Quote;
use App\Models\Purchase;
use App\Services\StockService;
use Carbon\Carbon;

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

        $ayBasi = Carbon::now()->startOfMonth();
        $aySonu = Carbon::now()->endOfMonth();
        $gelirAy = (float) Sale::whereBetween('saleDate', [$ayBasi, $aySonu])->sum('grandTotal');
        $giderAy = (float) Expense::whereBetween('expenseDate', [$ayBası, $aySonu])->sum('amount');
        $toplamKasaBakiye = Kasa::all()->sum(fn ($k) => $k->balance);

        $vadesiGecenAlacaklar = Sale::with('customer')
            ->where('dueDate', '<', now()->toDateString())
            ->whereRaw('(grandTotal - COALESCE(paidAmount, 0)) > 0')
            ->orderBy('dueDate')
            ->take(10)
            ->get();
        $vadesiGecenToplam = (float) Sale::where('dueDate', '<', now()->toDateString())
            ->selectRaw('SUM(grandTotal - COALESCE(paidAmount, 0)) as kalan')
            ->value('kalan');

        $recentSales = Sale::with('customer')->orderBy('createdAt', 'desc')->take(5)->get();

        return view('dashboard.index', compact(
            'stats',
            'recentSales',
            'gelirAy',
            'giderAy',
            'toplamKasaBakiye',
            'vadesiGecenAlacaklar',
            'vadesiGecenToplam'
        ));
    }
}
