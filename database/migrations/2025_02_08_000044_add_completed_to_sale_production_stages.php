<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_production_stages', function (Blueprint $table) {
            $table->boolean('isCompleted')->default(false)->after('notes');
            $table->timestamp('completedAt')->nullable()->after('isCompleted');
            $table->string('completedByUserId', 36)->nullable()->after('completedAt');
        });
    }

    public function down(): void
    {
        Schema::table('sale_production_stages', function (Blueprint $table) {
            $table->dropColumn(['isCompleted', 'completedAt', 'completedByUserId']);
        });
    }
};
