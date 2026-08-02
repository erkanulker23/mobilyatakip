<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_tasks')) {
            return;
        }

        Schema::create('user_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userId', 36)->index();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->date('dueDate')->nullable()->index();
            $table->string('color', 20)->default('emerald');
            $table->boolean('isCompleted')->default(false);
            $table->timestamp('completedAt')->nullable();
            $table->unsignedInteger('sortOrder')->default(0);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};
