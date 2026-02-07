<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('userId', 36)->nullable()->index();
                $table->string('entity', 100)->index();
                $table->string('entityId', 36)->nullable()->index();
                $table->string('action', 50)->index();
                $table->json('oldValue')->nullable();
                $table->json('newValue')->nullable();
                $table->string('ipAddress', 45)->nullable();
                $table->text('userAgent')->nullable();
                $table->timestamp('createdAt')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
