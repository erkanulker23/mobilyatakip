<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quote_items') && ! Schema::hasColumn('quote_items', 'productName')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $table->string('productName', 255)->nullable()->after('productId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quote_items') && Schema::hasColumn('quote_items', 'productName')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $table->dropColumn('productName');
            });
        }
    }
};
