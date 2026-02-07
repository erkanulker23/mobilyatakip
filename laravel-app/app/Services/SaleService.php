<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Quote;
use App\Models\Product;
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
            ->first();
        $next = $last ? (int) substr($last->saleNumber, -5) + 1 : 1;
        return sprintf('SAT-%s-%05d', $year, $next);
    }

    public function createFromQuote(string $quoteId, string $warehouseId): Sale
    {
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
        ]);

        foreach ($quote->items as $qi) {
            $lineNet = (float) $qi->lineTotal;
            $lineKdv = round($lineNet * ((float) $qi->kdvRate / 100), 2);
            SaleItem::create([
                'id' => (string) Str::uuid(),
                'saleId' => $sale->id,
                'productId' => $qi->productId,
                'unitPrice' => $qi->unitPrice,
                'quantity' => $qi->quantity,
                'kdvRate' => $qi->kdvRate,
                'lineTotal' => round($lineNet + $lineKdv, 2),
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
