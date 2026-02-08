<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_company_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shippingCompanyId', 36)->index();
            $table->string('kasaId', 36)->nullable()->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('paymentDate')->nullable();
            $table->string('paymentType', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('purchaseId', 36)->nullable()->index();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_company_payments');
    }
};
