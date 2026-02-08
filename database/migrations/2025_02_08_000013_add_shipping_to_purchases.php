<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('shippingCompanyId', 36)->nullable()->after('warehouseId')->index();
            $table->string('vehiclePlate', 20)->nullable()->after('shippingCompanyId');
            $table->string('driverName', 100)->nullable()->after('vehiclePlate');
            $table->string('driverPhone', 50)->nullable()->after('driverName');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['shippingCompanyId', 'vehiclePlate', 'driverName', 'driverPhone']);
        });
    }
};
