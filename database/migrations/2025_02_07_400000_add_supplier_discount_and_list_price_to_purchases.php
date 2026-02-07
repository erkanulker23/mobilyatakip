<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'supplierDiscountRate')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('supplierDiscountRate', 5, 2)->nullable()->after('kdvIncluded');
            });
        }

        if (Schema::hasTable('purchase_items') && !Schema::hasColumn('purchase_items', 'listPrice')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->decimal('listPrice', 14, 2)->nullable()->after('unitPrice');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchases', 'supplierDiscountRate')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('supplierDiscountRate');
            });
        }
        if (Schema::hasColumn('purchase_items', 'listPrice')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->dropColumn('listPrice');
            });
        }
    }
};
