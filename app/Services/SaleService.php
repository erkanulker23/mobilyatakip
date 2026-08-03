<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleActivity;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Product;
use App\Models\StockMovement;
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

            // Negatif stoka izin veriliyor; yeterli stok yoksa da satış yapılır
            $saleNumber = $this->nextSaleNumber();
            $subtotal = 0;
            $kdvTotal = 0;

            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'saleNumber' => $saleNumber,
                'customerId' => $customerId,
                'personnelId' => $data['personnelId'] ?? null,
                'saleDate' => $data['saleDate'] ?? now(),
                'dueDate' => $data['dueDate'] ?? null,
                'needsFinalMeasurement' => (bool) ($data['needsFinalMeasurement'] ?? false),
                'subtotal' => 0,
                'kdvTotal' => 0,
                'grandTotal' => 0,
                'paidAmount' => 0,
                'kdvIncluded' => $kdvIncluded,
                'notes' => $data['notes'] ?? null,
            ]);

            $saleDiscountPercent = (float) ($data['saleDiscountPercent'] ?? 0);
            $grandTotalOverride = $data['grandTotalOverride'] ?? null;

            $computedLines = [];
            foreach ($items as $row) {
                $unitPrice = (float) $row['unitPrice'];
                $qty = (int) $row['quantity'];
                $kdvRate = (float) ($row['kdvRate'] ?? 10);
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
                $lineTotal = round($lineTotal * (1 - $saleDiscountPercent / 100), 2);
                $lineNet = round($lineTotal / (1 + $kdvRate / 100), 2);
                $lineKdv = round($lineTotal - $lineNet, 2);
                $computedLines[] = compact('row', 'unitPrice', 'qty', 'kdvRate', 'lineDiscPct', 'lineDiscAmt', 'lineTotal', 'lineNet', 'lineKdv');
                $subtotal += $lineNet;
                $kdvTotal += $lineKdv;
            }

            $grandTotal = round($subtotal + $kdvTotal, 2);
            $factor = 1.0;
            if ($grandTotalOverride !== null && $grandTotalOverride > 0 && $grandTotal > 0) {
                $factor = $grandTotalOverride / $grandTotal;
                $subtotal = 0;
                $kdvTotal = 0;
                foreach ($computedLines as &$cl) {
                    $cl['lineTotal'] = round($cl['lineTotal'] * $factor, 2);
                    $cl['lineNet'] = round($cl['lineTotal'] / (1 + $cl['kdvRate'] / 100), 2);
                    $cl['lineKdv'] = round($cl['lineTotal'] - $cl['lineNet'], 2);
                    $subtotal += $cl['lineNet'];
                    $kdvTotal += $cl['lineKdv'];
                }
                $grandTotal = round($grandTotalOverride, 2);
            }

            foreach ($computedLines as $cl) {
                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'saleId' => $sale->id,
                    'productId' => $cl['row']['productId'] ?? null,
                    'productName' => $cl['row']['productName'] ?? null,
                    'description' => isset($cl['row']['description']) ? trim((string) $cl['row']['description']) : null,
                    'unitPrice' => $cl['unitPrice'],
                    'quantity' => $cl['qty'],
                    'kdvRate' => $cl['kdvRate'],
                    'lineDiscountPercent' => $cl['lineDiscPct'] ?: null,
                    'lineDiscountAmount' => $cl['lineDiscAmt'] ?: null,
                    'lineTotal' => $cl['lineTotal'],
                ]);
                if (!empty($cl['row']['productId'])) {
                    $warehouseId = $this->stockService->findWarehouseWithStock($cl['row']['productId'], $cl['qty'])
                        ?? $this->stockService->getWarehouseForProduct($cl['row']['productId']);
                    if ($warehouseId) {
                        $this->stockService->movement(
                            $cl['row']['productId'],
                            $warehouseId,
                            'cikis',
                            $cl['qty'],
                            ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış {$saleNumber}"]
                        );
                    }
                }
            }

            $sale->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);

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
            // Negatif stoka izin veriliyor
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
                    'productName' => $qi->product?->name ?? $qi->productName ?? null,
                    'description' => $qi->description,
                    'unitPrice' => $qi->unitPrice,
                    'quantity' => $qi->quantity,
                    'kdvRate' => $qi->kdvRate,
                    'lineTotal' => round((float) $qi->lineTotal, 2),
                ]);
                $warehouseId = $this->stockService->findWarehouseWithStock($qi->productId, (int) $qi->quantity)
                    ?? $this->stockService->getWarehouseForProduct($qi->productId);
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

    public function createQuoteFromSale(string $saleId): Quote
    {
        return DB::transaction(function () use ($saleId) {
            $sale = Sale::with(['customer', 'items.product', 'payments'])->findOrFail($saleId);

            if ($sale->isCancelled ?? false) {
                throw new \RuntimeException('İptal edilmiş satış teklife dönüştürülemez.');
            }

            if ((float) ($sale->paidAmount ?? 0) > 0.005 || $sale->payments()->exists()) {
                throw new \RuntimeException('Ödeme alınmış satış teklife dönüştürülemez.');
            }

            $existing = Quote::where('sourceSaleId', $saleId)
                ->whereNull('convertedSaleId')
                ->first();

            if (! $existing) {
                $existing = Quote::where('notes', 'like', '%Kaynak satış: '.$sale->saleNumber.'%')
                    ->whereNull('convertedSaleId')
                    ->first();
            }

            if ($existing) {
                if (! $existing->sourceSaleId) {
                    $existing->update(['sourceSaleId' => $sale->id]);
                }
                $this->archiveSaleAsQuoteSource($sale, $existing->quoteNumber);

                return Quote::with(['customer', 'personnel', 'items.product'])->find($existing->id);
            }

            $last = Quote::whereYear('createdAt', date('Y'))
                ->orderBy('quoteNumber', 'desc')
                ->lockForUpdate()
                ->first();
            $next = $last ? (int) preg_replace('/^TKL-\d+-/', '', $last->quoteNumber) + 1 : 1;
            $quoteNumber = 'TKL-' . date('Y') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

            $sourceNote = 'Kaynak satış: ' . $sale->saleNumber;
            $notes = filled($sale->notes) ? trim($sale->notes) . "\n\n" . $sourceNote : $sourceNote;

            $quote = Quote::create([
                'quoteNumber' => $quoteNumber,
                'customerId' => $sale->customerId,
                'personnelId' => $sale->personnelId,
                'status' => 'taslak',
                'kdvIncluded' => $sale->kdvIncluded ?? true,
                'generalDiscountPercent' => 0,
                'generalDiscountAmount' => 0,
                'revision' => 1,
                'validUntil' => now()->addDays(30)->toDateString(),
                'notes' => $notes,
                'subtotal' => $sale->subtotal,
                'kdvTotal' => $sale->kdvTotal,
                'grandTotal' => $sale->grandTotal,
                'drawingFiles' => $sale->drawingFiles,
                'sourceSaleId' => $sale->id,
            ]);

            foreach ($sale->items as $item) {
                QuoteItem::create([
                    'quoteId' => $quote->id,
                    'productId' => $item->productId,
                    'productName' => $item->product?->name ?? $item->productName,
                    'description' => $item->description,
                    'unitPrice' => $item->unitPrice,
                    'quantity' => $item->quantity,
                    'lineDiscountPercent' => $item->lineDiscountPercent ?? 0,
                    'lineDiscountAmount' => $item->lineDiscountAmount ?? 0,
                    'kdvRate' => $item->kdvRate,
                    'lineTotal' => $item->lineTotal,
                ]);
            }

            $this->archiveSaleAsQuoteSource($sale, $quote->quoteNumber);

            return Quote::with(['customer', 'personnel', 'items.product'])->find($quote->id);
        });
    }

    public function archiveSaleAsQuoteSource(Sale $sale, string $quoteNumber): void
    {
        if ($sale->isCancelled ?? false) {
            return;
        }

        $this->reverseSaleStockMovements($sale, 'satis_teklife');
        $sale->update(['isCancelled' => true]);
        SaleActivity::create([
            'saleId' => $sale->id,
            'type' => SaleActivity::TYPE_CREATED,
            'description' => 'Teklife dönüştürüldü ('.$quoteNumber.') — satış listesinden kaldırıldı',
        ]);
    }

    public function reverseSaleStockMovements(Sale $sale, string $refType = 'satis_iptal'): void
    {
        $movements = StockMovement::where('refType', 'satis')->where('refId', $sale->id)->get();
        foreach ($movements as $movement) {
            $qty = (int) abs($movement->quantity);
            if ($qty > 0 && $movement->productId && $movement->warehouseId) {
                $this->stockService->movement(
                    $movement->productId,
                    $movement->warehouseId,
                    'giris',
                    $qty,
                    [
                        'refType' => $refType,
                        'refId' => $sale->id,
                        'description' => "Stok iade - {$refType}: {$sale->saleNumber}",
                    ]
                );
            }
        }
    }

    public function find(int|string $id): ?Sale
    {
        return Sale::with(['customer', 'personnel', 'quote', 'items.product.supplier', 'activities', 'payments', 'serviceTickets'])->find($id);
    }

    /**
     * Satış kalemlerini günceller: mevcut kalemler (açıklama, fiyat, adet), silinen kalemler (stok iade), yeni kalemler (stok çıkış).
     * Toplamları yeniden hesaplar.
     */
    public function updateSaleItems(Sale $sale, array $itemsInput, array $options = []): Sale
    {
        return DB::transaction(function () use ($sale, $itemsInput, $options) {
            $saleNumber = $sale->saleNumber;
            $sale->refresh();
            $kdvIncluded = (bool) $sale->kdvIncluded;
            $saleDiscountPercent = (float) ($options['saleDiscountPercent'] ?? 0);
            $existingItems = $sale->items->keyBy('id');
            $submittedIds = collect($itemsInput)->pluck('id')->filter()->values()->all();

            foreach ($itemsInput as $row) {
                $itemId = $row['id'] ?? null;
                $unitPrice = (float) ($row['unitPrice'] ?? 0);
                $qty = (int) ($row['quantity'] ?? 1);
                $kdvRate = (float) ($row['kdvRate'] ?? 10);
                $lineDiscPct = (float) ($row['lineDiscountPercent'] ?? 0);
                $lineDiscAmt = (float) ($row['lineDiscountAmount'] ?? 0);
                $description = isset($row['description']) ? trim((string) $row['description']) : null;
                $productId = !empty($row['productId']) ? $row['productId'] : null;
                $productName = isset($row['productName']) ? trim((string) $row['productName']) : null;

                if ($kdvIncluded) {
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
                    $lineTotal = round(max(0, $lineTotal - $lineDiscAmt), 2);
                }
                $lineTotal = round($lineTotal * (1 - $saleDiscountPercent / 100), 2);
                $lineNet = round($lineTotal / (1 + $kdvRate / 100), 2);
                $lineKdv = round($lineTotal - $lineNet, 2);

                if ($itemId && $existingItems->has($itemId)) {
                    $item = $existingItems->get($itemId);
                    $oldQty = (int) $item->quantity;
                    $oldProductId = $item->productId;
                    $item->update([
                        'productId' => $productId,
                        'productName' => $productName,
                        'description' => $description ?: null,
                        'unitPrice' => $unitPrice,
                        'quantity' => $qty,
                        'kdvRate' => $kdvRate,
                        'lineDiscountPercent' => $lineDiscPct ?: null,
                        'lineDiscountAmount' => $lineDiscAmt ?: null,
                        'lineTotal' => $lineTotal,
                    ]);
                    if ($oldProductId && $oldQty > 0) {
                        $wh = $this->stockService->getWarehouseForProduct($oldProductId);
                        if ($wh) {
                            $this->stockService->movement($oldProductId, $wh, 'giris', $oldQty, ['refType' => 'satis_iade', 'refId' => $sale->id, 'description' => "Satış düzenleme iade {$saleNumber}"]);
                        }
                    }
                    if ($productId && $qty > 0) {
                        $warehouseId = $this->stockService->findWarehouseWithStock($productId, $qty) ?? $this->stockService->getWarehouseForProduct($productId);
                        if ($warehouseId) {
                            $this->stockService->movement($productId, $warehouseId, 'cikis', $qty, ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış düzenleme {$saleNumber}"]);
                        }
                    }
                } else {
                    $newItem = SaleItem::create([
                        'id' => (string) Str::uuid(),
                        'saleId' => $sale->id,
                        'productId' => $productId,
                        'productName' => $productName,
                        'description' => $description ?: null,
                        'unitPrice' => $unitPrice,
                        'quantity' => $qty,
                        'kdvRate' => $kdvRate,
                        'lineDiscountPercent' => $lineDiscPct ?: null,
                        'lineDiscountAmount' => $lineDiscAmt ?: null,
                        'lineTotal' => $lineTotal,
                    ]);
                    if ($productId && $qty > 0) {
                        $warehouseId = $this->stockService->findWarehouseWithStock($productId, $qty) ?? $this->stockService->getWarehouseForProduct($productId);
                        if ($warehouseId) {
                            $this->stockService->movement($productId, $warehouseId, 'cikis', $qty, ['refType' => 'satis', 'refId' => $sale->id, 'description' => "Satış {$saleNumber}"]);
                        }
                    }
                }
            }

            $toDelete = $existingItems->keys()->diff($submittedIds)->all();
            foreach ($toDelete as $id) {
                $item = $existingItems->get($id);
                if ($item && $item->productId && (int) $item->quantity > 0) {
                    $warehouseId = $this->stockService->getWarehouseForProduct($item->productId);
                    if ($warehouseId) {
                        $this->stockService->movement($item->productId, $warehouseId, 'giris', (int) $item->quantity, ['refType' => 'satis_iade', 'refId' => $sale->id, 'description' => "Satış kalem silindi {$saleNumber}"]);
                    }
                }
                SaleItem::where('id', $id)->delete();
            }

            $items = $sale->items()->get();
            $subtotal = $items->sum(fn ($i) => round((float) $i->lineTotal / (1 + (float) ($i->kdvRate ?? 0) / 100), 2));
            $kdvTotal = $items->sum(fn ($i) => (float) $i->lineTotal - round((float) $i->lineTotal / (1 + (float) ($i->kdvRate ?? 0) / 100), 2));
            $grandTotal = round($subtotal + $kdvTotal, 2);
            $grandTotalOverride = $options['grandTotalOverride'] ?? null;
            if ($grandTotalOverride !== null && (float) $grandTotalOverride > 0) {
                $grandTotal = round((float) $grandTotalOverride, 2);
            }
            $sale->update(['subtotal' => $subtotal, 'kdvTotal' => $kdvTotal, 'grandTotal' => $grandTotal]);

            return Sale::with(['customer', 'items.product.supplier'])->find($sale->id);
        });
    }

    public function paginate(int $perPage = 20)
    {
        return Sale::with('customer')->orderBy('createdAt', 'desc')->paginate($perPage);
    }
}
