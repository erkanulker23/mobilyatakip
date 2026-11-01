<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wardrobe_projects')) {
            return;
        }

        Schema::create('wardrobe_projects', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('token', 40)->unique();
            $table->string('customerName');
            $table->string('phone', 40);
            $table->string('projectName', 80);
            $table->string('note', 2000)->nullable();
            $table->json('design');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wardrobe_projects');
    }
};
