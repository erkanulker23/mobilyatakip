<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Kasa;
use App\Models\KasaHareket;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\StockService;
use App\Services\SaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestFullFlowCommand extends Command
{
    protected $signature = 'test:full-flow';
    protected $description = 'Tedarikçi → Alış → Müşteri → Satış → Tahsilat → Tedarikçi ödemesi akışını çalıştırır ve kasayı doğrular.';

    public function handle(StockService $stockService, SaleService $saleService): int
    {
        $this->info('=== Tam akış testi (test:full-flow) ===');
        $this->newLine();

        $warehouse = $this->getOrCreateWarehouse();
        $product = $this->getOrCreateProduct();
        $kasa = $this->getOrCreateKasa();

        // 1. Tedarikçi
        $supplier = Supplier::create([
            'name' => 'Test Tedarikçi ' . now()->format('Y-m-d H:i'),
            'email' => 'test-tedarikci-' . uniqid() . '@test.com',
            'phone' => null,
            'address' => null,
            'isActive' => true,
        ]);
        $this->info("1. Tedarikçi oluşturuldu: {$supplier->name}");

        // 2. Alış
        $purchase = $this->createPurchase($supplier, $product, $warehouse, $stockService);
        $this->info("2. Alış oluşturuldu: {$purchase->purchaseNumber} — Toplam: " . number_format((float) $purchase->grandTotal, 2, ',', '.') . ' ₺');

        // 3. Müşteri
        $customer = Customer::create([
            'name' => 'Test Müşteri ' . now()->format('Y-m-d H:i'),
            'email' => null,
            'phone' => null,
            'address' => null,
            'isActive' => true,
        ]);
        $this->info("3. Müşteri oluşturuldu: {$customer->name}");

        // 4. Satış (stok alıştan geldi)
        $sale = $saleService->createDirect([
            'customerId' => $customer->id,
            'saleDate' => now(),
            'kdvIncluded' => true,
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 1,
                    'unitPrice' => (float) $purchase->grandTotal * 1.2,
                    'kdvRate' => 18,
                ],
            ],
        ]);
        $this->info("4. Satış oluşturuldu: {$sale->saleNumber} — Toplam: " . number_format((float) $sale->grandTotal, 2, ',', '.') . ' ₺');

        // 5. Tahsilat (Havale, kasaya giriş)
        $tahsilatTutar = (float) $sale->grandTotal;
        $customerPayment = CustomerPayment::create([
            'customerId' => $customer->id,
            'saleId' => $sale->id,
            'amount' => $tahsilatTutar,
            'paymentDate' => now(),
            'paymentType' => 'havale',
            'kasaId' => $kasa->id,
            'reference' => 'Test tahsilat',
        ]);
        Sale::where('id', $sale->id)->update(['paidAmount' => $tahsilatTutar]);
        $desc = 'Tahsilat - ' . $customer->name . ' (Havale) - Fatura: ' . $sale->saleNumber;
        KasaHareket::create([
            'kasaId' => $kasa->id,
            'type' => 'giris',
            'amount' => $tahsilatTutar,
            'movementDate' => now(),
            'description' => $desc,
            'refType' => 'customer_payment',
            'refId' => $customerPayment->id,
        ]);
        $this->info("5. Tahsilat kaydedildi: " . number_format($tahsilatTutar, 2, ',', '.') . " ₺ — Ödeme tipi: Havale");

        // 6. Tedarikçi ödemesi (Nakit, kasadan çıkış)
        $tedarikciTutar = (float) $purchase->grandTotal;
        $supplierPayment = SupplierPayment::create([
            'supplierId' => $supplier->id,
            'purchaseId' => $purchase->id,
            'amount' => $tedarikciTutar,
            'paymentDate' => now(),
            'paymentType' => 'nakit',
            'kasaId' => $kasa->id,
            'reference' => 'Test ödeme',
        ]);
        Purchase::where('id', $purchase->id)->increment('paidAmount', $tedarikciTutar);
        $descSp = 'Tedarikçi ödemesi - ' . $supplier->name . ' (Nakit) - Fatura: ' . $purchase->purchaseNumber;
        KasaHareket::create([
            'kasaId' => $kasa->id,
            'type' => 'cikis',
            'amount' => -$tedarikciTutar,
            'movementDate' => now(),
            'description' => $descSp,
            'refType' => 'supplier_payment',
            'refId' => $supplierPayment->id,
        ]);
        $this->info("6. Tedarikçi ödemesi kaydedildi: " . number_format($tedarikciTutar, 2, ',', '.') . " ₺ — Ödeme tipi: Nakit");

        $this->newLine();
        $this->info('--- Kasa doğrulama ---');

        $hareketler = KasaHareket::where('kasaId', $kasa->id)->orderBy('movementDate')->orderBy('createdAt')->get();
        $isGiris = fn ($h) => strtolower((string) ($h->type ?? '')) === 'giris';
        $isCikis = fn ($h) => strtolower((string) ($h->type ?? '')) === 'cikis';
        $toplamGiris = $hareketler->filter($isGiris)->sum('amount');
        $toplamCikis = abs($hareketler->filter($isCikis)->sum('amount'));
        $acilis = (float) ($kasa->openingBalance ?? 0);
        $bakiye = $acilis + $toplamGiris - $toplamCikis;

        $this->table(
            ['Metrik', 'Tutar'],
            [
                ['Açılış bakiyesi', number_format($acilis, 2, ',', '.') . ' ₺'],
                ['Toplam giriş (tahsilat)', number_format($toplamGiris, 2, ',', '.') . ' ₺'],
                ['Toplam çıkış (tedarikçi ödemesi)', number_format($toplamCikis, 2, ',', '.') . ' ₺'],
                ['Güncel bakiye', number_format($bakiye, 2, ',', '.') . ' ₺'],
            ]
        );

        $customerPayments = CustomerPayment::with('customer')->whereIn('id', $hareketler->where('refType', 'customer_payment')->pluck('refId')->filter()->unique()->values()->all())->get()->keyBy('id');
        $supplierPayments = SupplierPayment::with('supplier')->whereIn('id', $hareketler->where('refType', 'supplier_payment')->pluck('refId')->filter()->unique()->values()->all())->get()->keyBy('id');
        $paymentTypes = ['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı', 'cek' => 'Çek', 'senet' => 'Senet', 'diger' => 'Diğer'];

        $rows = [];
        foreach ($hareketler as $h) {
            $pt = '—';
            if ($h->refType === 'customer_payment' && isset($customerPayments[$h->refId])) {
                $pt = $paymentTypes[$customerPayments[$h->refId]->paymentType ?? ''] ?? $customerPayments[$h->refId]->paymentType ?? '—';
            } elseif ($h->refType === 'supplier_payment' && isset($supplierPayments[$h->refId])) {
                $pt = $paymentTypes[$supplierPayments[$h->refId]->paymentType ?? ''] ?? $supplierPayments[$h->refId]->paymentType ?? '—';
            }
            $rows[] = [
                $h->movementDate?->format('d.m.Y H:i'),
                ucfirst($h->type ?? ''),
                $h->description ?? '-',
                $pt,
                number_format((float) $h->amount, 2, ',', '.') . ' ₺',
            ];
        }
        $this->table(['Tarih', 'Tip', 'Açıklama', 'Ödeme tipi', 'Tutar'], $rows);

        // Doğrulama: sadece bu çalışmada oluşturduğumuz hareketleri kontrol et (önceki çalıştırmalardan etkilenmesin)
        $buGirisHareketi = $hareketler->first(fn ($h) => $h->refType === 'customer_payment' && (string) $h->refId === (string) $customerPayment->id);
        $buCikisHareketi = $hareketler->first(fn ($h) => $h->refType === 'supplier_payment' && (string) $h->refId === (string) $supplierPayment->id);

        $ok = true;
        if (!$buGirisHareketi || abs((float) $buGirisHareketi->amount - $tahsilatTutar) > 0.01) {
            $this->error("Bu çalışmadaki tahsilat kasada eşleşmiyor: beklenen {$tahsilatTutar} ₺, hareket tutarı " . ($buGirisHareketi ? (float) $buGirisHareketi->amount : 'yok'));
            $ok = false;
        }
        if (!$buCikisHareketi || abs((float) $buCikisHareketi->amount + $tedarikciTutar) > 0.01) {
            $this->error("Bu çalışmadaki tedarikçi ödemesi kasada eşleşmiyor: beklenen -{$tedarikciTutar} ₺, hareket tutarı " . ($buCikisHareketi ? (float) $buCikisHareketi->amount : 'yok'));
            $ok = false;
        }
        if ($customerPayment->paymentType !== 'havale') {
            $this->error('Müşteri ödemesinde Ödeme Tipi "havale" olmalı.');
            $ok = false;
        }
        if ($supplierPayment->paymentType !== 'nakit') {
            $this->error('Tedarikçi ödemesinde Ödeme Tipi "nakit" olmalı.');
            $ok = false;
        }

        if ($ok) {
            $this->newLine();
            $this->info('Tam akış testi başarılı. Kasa tutarları ve ödeme tipleri doğru.');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function getOrCreateWarehouse()
    {
        $w = \App\Models\Warehouse::where('isActive', true)->first();
        if ($w) {
            return $w;
        }
        return \App\Models\Warehouse::create([
            'name' => 'Ana Depo',
            'code' => 'ANA',
            'isActive' => true,
        ]);
    }

    private function getOrCreateProduct(): Product
    {
        $p = Product::where('isActive', true)->first();
        if ($p) {
            return $p;
        }
        return Product::create([
            'name' => 'Test Ürün',
            'unitPrice' => 1000,
            'kdvIncluded' => true,
            'kdvRate' => 18,
            'isActive' => true,
        ]);
    }

    private function getOrCreateKasa(): Kasa
    {
        $k = Kasa::where('isActive', true)->first();
        if ($k) {
            return $k;
        }
        return Kasa::create([
            'name' => 'Ana Kasa',
            'type' => 'kasa',
            'openingBalance' => 0,
            'currency' => 'TRY',
            'isActive' => true,
        ]);
    }

    private function createPurchase(Supplier $supplier, Product $product, $warehouse, StockService $stockService): Purchase
    {
        return DB::transaction(function () use ($supplier, $product, $warehouse, $stockService) {
            $last = Purchase::whereYear('createdAt', date('Y'))->orderBy('purchaseNumber', 'desc')->lockForUpdate()->first();
            $next = $last ? (int) preg_replace('/^ALS-\d+-/', '', $last->purchaseNumber) + 1 : 1;
            $purchaseNumber = 'ALS-' . date('Y') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

            $purchase = Purchase::create([
                'purchaseNumber' => $purchaseNumber,
                'supplierId' => $supplier->id,
                'warehouseId' => $warehouse->id,
                'purchaseDate' => now(),
                'dueDate' => null,
                'kdvIncluded' => true,
                'supplierDiscountRate' => null,
                'subtotal' => 0,
                'kdvTotal' => 0,
                'grandTotal' => 0,
                'paidAmount' => 0,
                'notes' => 'Test alış (test:full-flow)',
            ]);

            $unitPrice = 10000;
            $qty = 1;
            $kdvRate = 18;
            $lineNet = round($unitPrice * $qty / (1 + $kdvRate / 100), 2);
            $lineKdv = round($lineNet * ($kdvRate / 100), 2);
            $lineTotal = round($lineNet + $lineKdv, 2);

            PurchaseItem::create([
                'purchaseId' => $purchase->id,
                'productId' => $product->id,
                'unitPrice' => $unitPrice,
                'quantity' => $qty,
                'kdvRate' => $kdvRate,
                'lineTotal' => $lineTotal,
            ]);

            $stockService->movement(
                $product->id,
                $warehouse->id,
                'giris',
                $qty,
                ['refType' => 'purchase', 'refId' => $purchase->id, 'description' => 'Alış: ' . $purchase->purchaseNumber]
            );

            $grandTotal = $lineTotal;
            $purchase->update([
                'subtotal' => $lineNet,
                'kdvTotal' => $lineKdv,
                'grandTotal' => $grandTotal,
            ]);

            return $purchase->fresh();
        });
    }
}
