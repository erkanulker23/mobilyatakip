<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSortOrder('quote_items', 'quoteId');
        $this->addSortOrder('sale_items', 'saleId');
    }

    public function down(): void
    {
        foreach (['quote_items', 'sale_items'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sortOrder')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('sortOrder');
                });
            }
        }
    }

    private function addSortOrder(string $table, string $parentColumn): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'sortOrder')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('sortOrder')->default(0);
        });

        // Ağır satır satır backfill yok — varsayılan 0 yeter; sonraki kayıt sırayı yazar.
        // (Büyük tablolarda timeout / yarım migration riskini önler.)
    }
};
