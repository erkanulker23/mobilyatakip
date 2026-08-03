<?php

use App\Models\Quote;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('quotes', 'sourceSaleId')) {
                $table->string('sourceSaleId', 36)->nullable()->after('convertedSaleId');
                $table->index('sourceSaleId');
            }
        });

        Quote::query()
            ->whereNull('sourceSaleId')
            ->where('notes', 'like', '%Kaynak satış:%')
            ->orderBy('createdAt')
            ->each(function (Quote $quote) {
                if (! preg_match('/Kaynak satış:\s*(SAT-\d+-\d+)/u', (string) $quote->notes, $matches)) {
                    return;
                }
                $sale = Sale::where('saleNumber', $matches[1])->first();
                if ($sale) {
                    $quote->update(['sourceSaleId' => $sale->id]);
                }
            });

        /** @var SaleService $saleService */
        $saleService = app(SaleService::class);
        Quote::query()
            ->whereNotNull('sourceSaleId')
            ->whereNull('convertedSaleId')
            ->each(function (Quote $quote) use ($saleService) {
                $sale = Sale::find($quote->sourceSaleId);
                if ($sale && ! ($sale->isCancelled ?? false)) {
                    $saleService->archiveSaleAsQuoteSource($sale, $quote->quoteNumber);
                }
            });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'sourceSaleId')) {
                $table->dropIndex(['sourceSaleId']);
                $table->dropColumn('sourceSaleId');
            }
        });
    }
};
