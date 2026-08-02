<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_items') && !Schema::hasColumn('sale_items', 'description')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->text('description')->nullable()->after('productName');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'description')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
