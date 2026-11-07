<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || Schema::hasColumn('expenses', 'supplierId')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $after = Schema::hasColumn('expenses', 'saleId') ? 'saleId' : 'kasaId';
            $table->string('supplierId', 36)->nullable()->after($after)->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasColumn('expenses', 'supplierId')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('supplierId');
        });
    }
};
