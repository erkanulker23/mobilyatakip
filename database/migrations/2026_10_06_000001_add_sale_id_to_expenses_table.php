<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || Schema::hasColumn('expenses', 'saleId')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('saleId', 36)->nullable()->after('kasaId')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasColumn('expenses', 'saleId')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('saleId');
        });
    }
};
