<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleActivity;
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
            $items = $data['items'];
            $kdvIncluded = $data['kdvIncluded'] ?? true;

            foreach ($items as $row) {
                if (!empty($row['productId'])) {
                    $warehouseId = $this->stockService->findWarehouseWithStock($row['productId'], (int) $row['quantity']);
                    if (!$warehouseId) {
                        $product = Product::find($row['productId']);
                        $name = $product?->name ?? $row['productId'];
                        throw new \RuntimeException("Yetersiz stok: {$name} - Talep edilen miktar: {$row['quantity']} adet");
                    }
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
                'dueDate' => $data['dueDate'] ?? null,
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
                $lineDiscPct = (float) ($row['lineDiscountPercent'] ?? 0);
                $lineDiscAmt = (float) ($row['lineDiscountAmount'] ?? 0);

                if ($kdvIncluded) {
                    $lineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
                    $lineKdv = round($unitPrice * $qty - $lineNet, 2);
                    $lineTotal = round($unitPrice * $qty, 2);
                } else {
                    $lineNet = round($unitPrice * $qty, 2);
                    $lineKdv = round($lineNet * ($kdvRate / 100), 2);
                    $lineTotal = round($lineNet + $lineKdv, 2);
                }
                if ($lineDiscPct > 0) {
                    $lineTotal = round($lineTotal * (1 - $lineDiscPct / 100), 2);
                }
                if ($lineDiscAmt > 0) {
                    $lineTotal = round($lineTotal - $lineDiscAmt, 2);
                }
                $lineNet = round($lineTotal / (1 + $kdvRate / 100), 2);
                $lineKdv = round($lineTotal - $lineNet, 2);
                $subtotal += $lineNet;
                $kdvTotal += $lineKdv;

                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'saleId' => $sale->id,
                    'productId' => $row['productId'] ?? null,
                    'productName' => $row['productName'] ?? null,
                    'unitPrice' => $unitPrice,
                    'quantity' => $qty,
                    'kdvRate' => $kdvRate,
                    'lineDiscountPercent' => $lineDiscPct ?: null,
                    'lineDiscountAmount' => $lineDiscAmt ?: null,
                    'lineTotal' => $lineTotal,
                ]);
                if (!empty($row['productId'])) {
                    $warehouseId = $this->stockService->findWarehouseWithStock($row['productId'], $qty);
                    if ($warehouseId) {
                        $this->stockService->movement(
                            $row['productId'],
                            $warehouseId,
                            'cikis',
                            $qty,
                            ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış {$saleNumber}"]
                        );
                    }
                }
            }

            $sale->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => round($subtotal + $kdvTotal, 2)]);

            SaleActivity::create([
                'saleId' => $sale->id,
                'type' => SaleActivity::TYPE_CREATED,
                'description' => 'Satış oluşturuldu',
            ]);

            return Sale::with(['customer', 'items.product.supplier'])->find($sale->id);
        });
    }

    public function createFromQuote(string $quoteId): Sale
    {
        return DB::transaction(function () use ($quoteId) {
            $quote = Quote::with(['customer', 'items.product'])->findOrFail($quoteId);
            foreach ($quote->items as $qi) {
                $warehouseId = $this->stockService->findWarehouseWithStock($qi->productId, (int) $qi->quantity);
                if (!$warehouseId) {
                    $product = Product::find($qi->productId);
                    $name = $product?->name ?? $qi->productId;
                    throw new \RuntimeException("Yetersiz stok: {$name} - Talep edilen miktar: {$qi->quantity} adet");
                }
            }

            $saleNumber = $this->nextSaleNumber();
            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'saleNumber' => $saleNumber,
                'customerId' => $quote->customerId,
                'quoteId' => $quote->id,
                'saleDate' => now(),
                'dueDate' => null,
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
                    'productName' => $qi->product?->name ?? null,
                    'unitPrice' => $qi->unitPrice,
                    'quantity' => $qi->quantity,
                    'kdvRate' => $qi->kdvRate,
                    'lineTotal' => round((float) $qi->lineTotal, 2),
                ]);
                $warehouseId = $this->stockService->findWarehouseWithStock($qi->productId, (int) $qi->quantity);
                if ($warehouseId) {
                    $this->stockService->movement(
                        $qi->productId,
                        $warehouseId,
                        'cikis',
                        (int) $qi->quantity,
                        ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış {$saleNumber}"]
                    );
                }
            }

            $quote->update(['convertedSaleId' => $sale->id]);

            SaleActivity::create([
                'saleId' => $sale->id,
                'type' => SaleActivity::TYPE_CREATED,
                'description' => 'Satış oluşturuldu (tekliften)',
            ]);

            return Sale::with(['customer', 'items.product.supplier'])->find($sale->id);
        });
    }

    public function find(int|string $id): ?Sale
    {
        return Sale::with(['customer', 'quote', 'items.product.supplier', 'activities', 'payments'])->find($id);
    }

    public function paginate(int $perPage = 20)
    {
        return Sale::with('customer')->orderBy('createdAt', 'desc')->paginate($perPage);
    }
}
