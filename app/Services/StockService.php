<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;

class StockService
{
    public function getStock(string $productId, string $warehouseId): Stock
    {
        $stock = Stock::where('productId', $productId)
            ->where('warehouseId', $warehouseId)
            ->with(['product', 'warehouse'])
            ->first();
        if (!$stock) {
            $stock = Stock::create([
                'productId' => $productId,
                'warehouseId' => $warehouseId,
                'quantity' => 0,
                'reservedQuantity' => 0,
            ]);
        }
        return $stock;
    }

    public function getByWarehouse(string $warehouseId)
    {
        return Stock::where('warehouseId', $warehouseId)
            ->with(['product.supplier'])
            ->orderByRaw('(SELECT name FROM products WHERE products.id = stocks.productId)')
            ->get();
    }

    public function getLowStock(?string $warehouseId = null)
    {
        $q = Stock::query()
            ->join('products', 'products.id', '=', 'stocks.productId')
            ->whereRaw('(stocks.quantity - COALESCE(stocks.reservedQuantity, 0)) <= products.minStockLevel')
            ->where('products.minStockLevel', '>', 0)
            ->select('stocks.*');
        if ($warehouseId) {
            $q->where('stocks.warehouseId', $warehouseId);
        }
        return $q->with('product')->get();
    }

    public function movement(
        string $productId,
        string $warehouseId,
        string $type,
        int $quantity,
        ?array $opts = null
    ): Stock {
        $stock = $this->getStock($productId, $warehouseId);
        $q = (int) $stock->quantity;
        $r = (int) $stock->reservedQuantity;
        $available = $q - $r;

        if (in_array($type, ['cikis', 'transfer']) && $quantity > $available) {
            throw new \RuntimeException('Yetersiz stok');
        }

        $delta = match ($type) {
            'giris' => $quantity,
            'düzeltme' => 0,
            default => -$quantity,
        };

        if ($type === 'düzeltme') {
            $stock->quantity = $quantity;
            $stock->save();
        } else {
            $stock->quantity = $q + $delta;
            $stock->save();
        }

        StockMovement::create([
            'productId' => $productId,
            'warehouseId' => $warehouseId,
            'type' => $type,
            'quantity' => in_array($type, ['cikis', 'transfer']) ? -$quantity : $quantity,
            'refType' => $opts['refType'] ?? null,
            'refId' => $opts['refId'] ?? null,
            'userId' => $opts['userId'] ?? null,
            'description' => $opts['description'] ?? null,
        ]);

        return $this->getStock($productId, $warehouseId);
    }
}
