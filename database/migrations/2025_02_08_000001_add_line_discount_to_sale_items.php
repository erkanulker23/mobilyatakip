<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_items')) {
            return;
        }
        if (!Schema::hasColumn('sale_items', 'lineDiscountPercent')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('lineDiscountPercent', 5, 2)->nullable()->after('kdvRate');
                $table->decimal('lineDiscountAmount', 14, 2)->nullable()->after('lineDiscountPercent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_items', 'lineDiscountPercent')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn(['lineDiscountPercent', 'lineDiscountAmount']);
            });
        }
    }
};
