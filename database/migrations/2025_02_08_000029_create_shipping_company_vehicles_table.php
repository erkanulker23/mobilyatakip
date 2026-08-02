<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_company_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shippingCompanyId', 36)->index();
            $table->string('vehiclePlate', 20);
            $table->string('driverName', 100)->nullable();
            $table->string('driverPhone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_company_vehicles');
    }
};
