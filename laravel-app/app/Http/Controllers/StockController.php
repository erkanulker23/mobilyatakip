<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $warehouses = Warehouse::orderBy('name')->get();
        $stocks = $warehouseId
            ? $this->stockService->getByWarehouse($warehouseId)
            : collect();
        return view('stock.index', compact('warehouses', 'stocks', 'warehouseId'));
    }

    public function lowStock(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $lowStocks = $this->stockService->getLowStock($warehouseId);
        $warehouses = Warehouse::orderBy('name')->get();
        return view('stock.low', compact('lowStocks', 'warehouses'));
    }
}
