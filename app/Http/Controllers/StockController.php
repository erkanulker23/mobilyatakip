<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $warehouses = Warehouse::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $stocks = collect();
        if ($warehouseId) {
            $stocks = $this->stockService->getByWarehouse($warehouseId);
            if ($request->filled('supplier_id')) {
                $stocks = $stocks->filter(fn ($s) => $s->product?->supplierId == $request->supplier_id);
            }
            if ($request->filled('search')) {
                $q = strtolower($request->search);
                $stocks = $stocks->filter(fn ($s) =>
                    stripos($s->product?->name ?? '', $q) !== false || stripos($s->product?->sku ?? '', $q) !== false
                );
            }
        }
        return view('stock.index', compact('warehouses', 'stocks', 'warehouseId', 'suppliers'));
    }

    public function edit(Request $request, Stock $stock)
    {
        $stock->load(['product', 'warehouse']);
        if ($stock->warehouseId != $request->get('warehouse_id')) {
            return redirect()->route('stock.index', ['warehouse_id' => $stock->warehouseId])->with('error', 'Geçersiz depo.');
        }
        return view('stock.edit', compact('stock'));
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'reservedQuantity' => 'required|integer|min:0',
        ]);
        $stock->update($validated);
        return redirect()->route('stock.index', ['warehouse_id' => $stock->warehouseId])->with('success', 'Stok güncellendi.');
    }

    public function lowStock(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $lowStocks = $this->stockService->getLowStock($warehouseId);
        $warehouses = Warehouse::orderBy('name')->get();
        return view('stock.low', compact('lowStocks', 'warehouses'));
    }
}
