<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personnel_advances')) {
            return;
        }

        Schema::create('personnel_advances', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('personnelId');
            $table->string('kasaId')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('advanceDate');
            $table->string('period', 16)->default('gunluk');
            $table->string('note', 500)->nullable();
            $table->string('createdBy')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->index('personnelId');
            $table->index('advanceDate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_advances');
    }
};
