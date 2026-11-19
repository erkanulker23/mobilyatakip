<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotes') || Schema::hasColumn('quotes', 'quoteDate')) {
            return;
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->date('quoteDate')->nullable()->after('validUntil');
        });

        // Mevcut tekliflerde belge tarihi = oluşturulma günü.
        DB::table('quotes')
            ->whereNull('quoteDate')
            ->whereNotNull('createdAt')
            ->update(['quoteDate' => DB::raw('DATE(createdAt)')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotes') || ! Schema::hasColumn('quotes', 'quoteDate')) {
            return;
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('quoteDate');
        });
    }
};
