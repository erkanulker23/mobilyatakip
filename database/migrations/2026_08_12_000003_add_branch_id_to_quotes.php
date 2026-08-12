<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotes') && ! Schema::hasColumn('quotes', 'branchId')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->string('branchId', 36)->nullable()->index()->after('customerId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotes') && Schema::hasColumn('quotes', 'branchId')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropColumn('branchId');
            });
        }
    }
};
