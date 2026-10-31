<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wardrobe_quotes')) {
            return;
        }

        Schema::create('wardrobe_quotes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('customerName');
            $table->string('phone', 40);
            $table->string('note', 2000)->nullable();
            $table->json('design');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wardrobe_quotes');
    }
};
