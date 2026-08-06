<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_production_stages', function (Blueprint $table) {
            $table->string('saleItemId', 36)->nullable()->after('saleId')->index();
        });
    }

    public function down(): void
    {
        Schema::table('sale_production_stages', function (Blueprint $table) {
            $table->dropColumn('saleItemId');
        });
    }
};
