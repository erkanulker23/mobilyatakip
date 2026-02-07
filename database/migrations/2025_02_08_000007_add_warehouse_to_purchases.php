<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'warehouseId')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('warehouseId', 36)->nullable()->after('supplierId')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'warehouseId')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('warehouseId');
            });
        }
    }
};
