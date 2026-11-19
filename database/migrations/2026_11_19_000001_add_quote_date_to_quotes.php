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
            $table->date('quoteDate')->nullable();
        });

        // Mevcut tekliflerde belge tarihi = oluşturulma günü.
        $createdCol = Schema::hasColumn('quotes', 'createdAt')
            ? 'createdAt'
            : (Schema::hasColumn('quotes', 'created_at') ? 'created_at' : null);
        if ($createdCol) {
            DB::table('quotes')
                ->whereNull('quoteDate')
                ->whereNotNull($createdCol)
                ->update(['quoteDate' => DB::raw('DATE(`'.$createdCol.'`)')]);
        }
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
