<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('saleId', 36)->index();
            $table->string('type', 50)->index(); // created, supplier_email_sent, supplier_email_read, supplier_email_replied
            $table->string('description')->nullable();
            $table->json('metadata')->nullable(); // supplierId, supplierName, email vb.
            $table->timestamp('createdAt')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_activities');
    }
};
