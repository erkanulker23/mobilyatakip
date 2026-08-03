<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;

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

    /** Ürünün yeterli stoku bulunan bir deposunu döner (en çok stok olan önce) */
    public function findWarehouseWithStock(string $productId, int $quantity): ?string
    {
        $stock = Stock::where('productId', $productId)
            ->whereRaw('(quantity - COALESCE(reservedQuantity, 0)) >= ?', [$quantity])
            ->orderByRaw('(quantity - COALESCE(reservedQuantity, 0)) DESC')
            ->first();
        return $stock?->warehouseId;
    }

    /** Ürün için stok hareketi yapılacak depo döner; yeterli stok yoksa bile depo seçer (negatif stok için) */
    public function getWarehouseForProduct(string $productId): ?string
    {
        $existing = Stock::where('productId', $productId)->first();
        if ($existing) {
            return $existing->warehouseId;
        }
        return Warehouse::where('isActive', true)->orderBy('name')->value('id');
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

        // Negatif stoka izin veriliyor; tedarikçiden alım yapıldığında stok güncellenir
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

    /**
     * Satışa bağlı net stok çıkışını iade eder (satis − satis_iade).
     * Düzenleme sonrası iptal/silmede çift iadeyi önler.
     */
    public function reverseNetSaleStock(string $saleId, string $saleNumber, string $refType): void
    {
        $movements = StockMovement::where('refId', $saleId)
            ->whereIn('refType', ['satis', 'satis_iade'])
            ->get();

        $netByKey = [];
        foreach ($movements as $movement) {
            if (! $movement->productId || ! $movement->warehouseId) {
                continue;
            }
            $key = $movement->productId . '|' . $movement->warehouseId;
            $qty = (int) abs($movement->quantity);
            if ($qty <= 0) {
                continue;
            }
            $netByKey[$key]['productId'] = $movement->productId;
            $netByKey[$key]['warehouseId'] = $movement->warehouseId;
            if ($movement->refType === 'satis') {
                $netByKey[$key]['out'] = ($netByKey[$key]['out'] ?? 0) + $qty;
            } else {
                $netByKey[$key]['in'] = ($netByKey[$key]['in'] ?? 0) + $qty;
            }
        }

        foreach ($netByKey as $entry) {
            $netOut = ($entry['out'] ?? 0) - ($entry['in'] ?? 0);
            if ($netOut <= 0) {
                continue;
            }
            $this->movement(
                $entry['productId'],
                $entry['warehouseId'],
                'giris',
                $netOut,
                [
                    'refType' => $refType,
                    'refId' => $saleId,
                    'description' => "Stok iade - {$refType}: {$saleNumber}",
                ]
            );
        }
    }
}
