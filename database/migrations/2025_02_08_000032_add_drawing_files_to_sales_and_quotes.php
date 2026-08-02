<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales') && ! Schema::hasColumn('sales', 'drawingFiles')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->json('drawingFiles')->nullable()->after('notes');
            });
        }

        if (Schema::hasTable('quotes') && ! Schema::hasColumn('quotes', 'drawingFiles')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->json('drawingFiles')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'drawingFiles')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('drawingFiles');
            });
        }

        if (Schema::hasTable('quotes') && Schema::hasColumn('quotes', 'drawingFiles')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropColumn('drawingFiles');
            });
        }
    }
};
