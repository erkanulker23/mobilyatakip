<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('saleDiscountPercent', 8, 2)->nullable()->after('kdvIncluded');
            $table->decimal('grandTotalOverride', 14, 2)->nullable()->after('saleDiscountPercent');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['saleDiscountPercent', 'grandTotalOverride']);
        });
    }
};
