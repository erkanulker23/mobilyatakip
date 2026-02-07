<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Kasa;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Tarayıcı ve hesaplama testleri için örnek veri.
 * Çalıştırma: php artisan db:seed --class=TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            Company::create([
                'id' => (string) Str::uuid(),
                'name' => 'Test Mobilya A.Ş.',
                'address' => 'Test Mah. Test Sok. No:1',
                'taxNumber' => '1234567890',
                'taxOffice' => 'Kadıköy VD',
                'phone' => '02161234567',
                'email' => 'info@test.com',
            ]);
        }

        $customer = Customer::firstOrCreate(
            ['email' => 'test-musteri@test.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Müşteri',
                'phone' => '05321234567',
                'address' => 'Müşteri Adresi',
                'taxNumber' => '9876543210',
                'taxOffice' => 'Ümraniye VD',
                'identityNumber' => '12345678901',
                'isActive' => true,
            ]
        );
        if (!$customer->id) {
            $customer = Customer::where('email', 'test-musteri@test.com')->first();
        }

        $supplier = Supplier::firstOrCreate(
            ['email' => 'test-tedarikci@test.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Tedarikçi',
                'phone' => '05339876543',
                'address' => 'Tedarikçi Adresi',
                'taxNumber' => '1111111114',
                'taxOffice' => 'Kadıköy VD',
                'isActive' => true,
            ]
        );
        if (!$supplier->id) {
            $supplier = Supplier::where('email', 'test-tedarikci@test.com')->first();
        }

        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'Ana Depo'],
            [
                'id' => (string) Str::uuid(),
                'code' => 'DEPO1',
                'isActive' => true,
            ]
        );
        if (!$warehouse->id) {
            $warehouse = Warehouse::where('name', 'Ana Depo')->first();
        }

        $products = [];
        foreach (
            [
                ['name' => 'Test Koltuk', 'unitPrice' => 5000, 'kdvRate' => 18],
                ['name' => 'Test Masa', 'unitPrice' => 2000, 'kdvRate' => 18],
            ] as $p
        ) {
            $product = Product::firstOrCreate(
                ['name' => $p['name']],
                [
                    'id' => (string) Str::uuid(),
                    'sku' => 'SKU-' . strtoupper(substr(Str::slug($p['name']), 0, 6)),
                    'unitPrice' => $p['unitPrice'],
                    'kdvIncluded' => true,
                    'kdvRate' => $p['kdvRate'],
                    'supplierId' => $supplier->id,
                    'isActive' => true,
                ]
            );
            if (!$product->id) {
                $product = Product::where('name', $p['name'])->first();
            }
            $products[] = $product;
        }

        foreach ($products as $product) {
            $st = Stock::firstOrNew([
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
            ]);
            if (!$st->exists || $st->quantity < 20) {
                $st->id = $st->id ?? (string) Str::uuid();
                $st->quantity = 20;
                $st->reservedQuantity = 0;
                $st->save();
            }
        }

        $kasa = Kasa::firstOrCreate(
            ['name' => 'Ana Kasa'],
            [
                'id' => (string) Str::uuid(),
                'type' => 'nakit',
                'openingBalance' => 0,
                'currency' => 'TRY',
                'isActive' => true,
            ]
        );

        $this->command->info('Test verileri eklendi: Müşteri, Tedarikçi, 2 Ürün, Depo (stok 20), Kasa.');
    }
}
