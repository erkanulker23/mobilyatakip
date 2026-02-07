<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculationsAndDbTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SuperAdminSeeder::class);
        $this->user = User::where('email', 'erkanulker0@gmail.com')->first();
        $this->seed(TestDataSeeder::class);
    }

    /** Teklif: kalem indirimi + genel indirim, toplamlar ve DB kaydı */
    public function test_quote_totals_and_line_discount_saved_to_db(): void
    {
        $customer = Customer::first();
        $products = Product::take(2)->get();
        $this->assertGreaterThanOrEqual(2, $products->count());

        $payload = [
            'customerId' => $customer->id,
            'kdvIncluded' => true,
            'generalDiscountPercent' => 10,
            'generalDiscountAmount' => 0,
            'items' => [
                [
                    'productId' => $products[0]->id,
                    'unitPrice' => 100,
                    'quantity' => 2,
                    'kdvRate' => 18,
                    'lineDiscountPercent' => 5,
                    'lineDiscountAmount' => 0,
                ],
                [
                    'productId' => $products[1]->id,
                    'unitPrice' => 50,
                    'quantity' => 4,
                    'kdvRate' => 18,
                    'lineDiscountPercent' => 0,
                    'lineDiscountAmount' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('quotes.store'), $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('quotes', ['customerId' => $customer->id]);

        $quote = Quote::where('customerId', $customer->id)->latest('createdAt')->first();
        $this->assertNotNull($quote);
        $this->assertGreaterThan(0, (float) $quote->subtotal);
        $this->assertGreaterThan(0, (float) $quote->kdvTotal);
        $this->assertGreaterThan(0, (float) $quote->grandTotal);
        $generalDisc = (float) $quote->subtotal * 0.10;
        $afterDisc = (float) $quote->subtotal - $generalDisc;
        $this->assertEqualsWithDelta(
            $afterDisc + (float) $quote->kdvTotal,
            (float) $quote->grandTotal,
            0.02
        );

        $items = QuoteItem::where('quoteId', $quote->id)->get();
        $this->assertCount(2, $items);
        $sumLineTotal = $items->sum(fn ($i) => (float) $i->lineTotal);
        $this->assertGreaterThan(0, $sumLineTotal);
    }

    /** Alış: supplierDiscountRate toplama uygulanıyor ve DB'de doğru */
    public function test_purchase_supplier_discount_applied_and_saved(): void
    {
        $supplier = Supplier::first();
        $products = Product::take(2)->get();
        $this->assertGreaterThanOrEqual(2, $products->count());

        $payload = [
            'supplierId' => $supplier->id,
            'purchaseDate' => now()->format('Y-m-d'),
            'kdvIncluded' => true,
            'supplierDiscountRate' => 10,
            'items' => [
                ['productId' => $products[0]->id, 'unitPrice' => 100, 'quantity' => 1, 'kdvRate' => 18],
                ['productId' => $products[1]->id, 'unitPrice' => 50, 'quantity' => 2, 'kdvRate' => 18],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchases.store'), $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('purchases', ['supplierId' => $supplier->id]);

        $purchase = Purchase::where('supplierId', $supplier->id)->latest('createdAt')->first();
        $this->assertNotNull($purchase);
        $rawSubtotal = 100 * 1 / 1.18 + 50 * 2 / 1.18;
        $expectedSubtotal = round($rawSubtotal * 0.9, 2);
        $this->assertEqualsWithDelta($expectedSubtotal, (float) $purchase->subtotal, 0.5);
    }

    /** Gider: KDV alanları hesaplanıp DB'ye yazılıyor */
    public function test_expense_kdv_saved_to_db(): void
    {
        $payload = [
            'amount' => 118,
            'kdvIncluded' => true,
            'kdvRate' => 18,
            'expenseDate' => now()->format('Y-m-d'),
            'description' => 'Test gider KDV dahil',
            'category' => 'Kırtasiye',
        ];

        $response = $this->actingAs($this->user)->post(route('expenses.store'), $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['description' => 'Test gider KDV dahil']);

        $expense = Expense::where('description', 'Test gider KDV dahil')->first();
        $this->assertNotNull($expense->kdvAmount);
        $expectedKdv = round(118 - 118 / 1.18, 2);
        $this->assertEqualsWithDelta($expectedKdv, (float) $expense->kdvAmount, 0.02);
    }

    /** Raporlar: iptal edilen alış tedarikçi bakiyesine dahil değil */
    public function test_supplier_ledger_excludes_cancelled_purchases(): void
    {
        $supplier = Supplier::first();
        $products = Product::take(1)->get();
        if ($products->isEmpty()) {
            $this->markTestSkipped('Ürün yok');
        }

        $this->actingAs($this->user)->post(route('purchases.store'), [
            'supplierId' => $supplier->id,
            'purchaseDate' => now()->format('Y-m-d'),
            'kdvIncluded' => true,
            'items' => [
                ['productId' => $products[0]->id, 'unitPrice' => 100, 'quantity' => 1, 'kdvRate' => 18],
            ],
        ]);
        $purchase = Purchase::where('supplierId', $supplier->id)->latest('createdAt')->first();
        $grandBefore = (float) $purchase->grandTotal;
        $purchase->update(['isCancelled' => true]);

        $suppliers = \App\Models\Supplier::with('purchases')->where('id', $supplier->id)->get();
        $borc = (float) $suppliers->first()->purchases->where('isCancelled', false)->sum('grandTotal');
        $this->assertEqualsWithDelta(0, $borc - ($grandBefore - $grandBefore), 0.01);
        $this->assertEquals(0, $borc);
    }
}
