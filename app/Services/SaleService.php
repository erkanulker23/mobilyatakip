<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Quote;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        private StockService $stockService
    ) {}

    private function nextSaleNumber(): string
    {
        $year = date('Y');
        $last = Sale::where('saleNumber', 'like', "SAT-{$year}-%")
            ->orderBy('saleNumber', 'desc')
            ->lockForUpdate()
            ->first();
        $next = $last ? (int) substr($last->saleNumber, -5) + 1 : 1;
        return sprintf('SAT-%s-%05d', $year, $next);
    }

    public function createDirect(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $customerId = $data['customerId'];
            $warehouseId = $data['warehouseId'];
            $items = $data['items'];
            $kdvIncluded = $data['kdvIncluded'] ?? true;

            foreach ($items as $row) {
                $stock = $this->stockService->getStock($row['productId'], $warehouseId);
                $available = (int) $stock->quantity - (int) ($stock->reservedQuantity ?? 0);
                if ($available < $row['quantity']) {
                    $product = Product::find($row['productId']);
                    $name = $product?->name ?? $row['productId'];
                    throw new \RuntimeException("Yetersiz stok: {$name} - Depoda {$available} adet, {$row['quantity']} adet satılamaz");
                }
            }

            $saleNumber = $this->nextSaleNumber();
            $subtotal = 0;
            $kdvTotal = 0;

            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'saleNumber' => $saleNumber,
                'customerId' => $customerId,
                'saleDate' => $data['saleDate'] ?? now(),
                'dueDate' => $data['dueDate'] ?? now()->addDays(30),
                'subtotal' => 0,
                'kdvTotal' => 0,
                'grandTotal' => 0,
                'paidAmount' => 0,
                'kdvIncluded' => $kdvIncluded,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $row) {
                $unitPrice = (float) $row['unitPrice'];
                $qty = (int) $row['quantity'];
                $kdvRate = (float) ($row['kdvRate'] ?? 18);

                if ($kdvIncluded) {
                    $lineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
                    $lineKdv = round($unitPrice * $qty - $lineNet, 2);
                    $lineTotal = round($unitPrice * $qty, 2);
                } else {
                    $lineNet = round($unitPrice * $qty, 2);
                    $lineKdv = round($lineNet * ($kdvRate / 100), 2);
                    $lineTotal = round($lineNet + $lineKdv, 2);
                }
                $subtotal += $lineNet;
                $kdvTotal += $lineKdv;

                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'saleId' => $sale->id,
                    'productId' => $row['productId'],
                    'unitPrice' => $unitPrice,
                    'quantity' => $qty,
                    'kdvRate' => $kdvRate,
                    'lineTotal' => $lineTotal,
                ]);
                $this->stockService->movement(
                    $row['productId'],
                    $warehouseId,
                    'cikis',
                    $qty,
                    ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış {$saleNumber}"]
                );
            }

            $sale->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => round($subtotal + $kdvTotal, 2)]);
            return Sale::with(['customer', 'items.product'])->find($sale->id);
        });
    }

    public function createFromQuote(string $quoteId, string $warehouseId): Sale
    {
        return DB::transaction(function () use ($quoteId, $warehouseId) {
            $quote = Quote::with(['customer', 'items.product'])->findOrFail($quoteId);
            foreach ($quote->items as $qi) {
                $stock = $this->stockService->getStock($qi->productId, $warehouseId);
                $available = (int) $stock->quantity - (int) ($stock->reservedQuantity ?? 0);
                if ($available < $qi->quantity) {
                    $name = $qi->product?->name ?? $qi->productId;
                    throw new \RuntimeException("Yetersiz stok: {$name} - Depoda {$available} adet, {$qi->quantity} adet satılamaz");
                }
            }

            $saleNumber = $this->nextSaleNumber();
            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'saleNumber' => $saleNumber,
                'customerId' => $quote->customerId,
                'quoteId' => $quote->id,
                'saleDate' => now(),
                'dueDate' => now()->addDays(30),
                'subtotal' => $quote->subtotal,
                'kdvTotal' => $quote->kdvTotal,
                'grandTotal' => $quote->grandTotal,
                'paidAmount' => 0,
                'kdvIncluded' => $quote->kdvIncluded ?? true,
            ]);

            foreach ($quote->items as $qi) {
                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'saleId' => $sale->id,
                    'productId' => $qi->productId,
                    'unitPrice' => $qi->unitPrice,
                    'quantity' => $qi->quantity,
                    'kdvRate' => $qi->kdvRate,
                    'lineTotal' => round((float) $qi->lineTotal, 2),
                ]);
                $this->stockService->movement(
                    $qi->productId,
                    $warehouseId,
                    'cikis',
                    $qi->quantity,
                    ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış {$saleNumber}"]
                );
            }

            $quote->update(['convertedSaleId' => $sale->id]);
            return Sale::with(['customer', 'items.product'])->find($sale->id);
        });
    }

    public function find(int|string $id): ?Sale
    {
        return Sale::with(['customer', 'quote', 'items.product'])->find($id);
    }

    public function paginate(int $perPage = 20)
    {
        return Sale::with('customer')->orderBy('createdAt', 'desc')->paginate($perPage);
    }
}
