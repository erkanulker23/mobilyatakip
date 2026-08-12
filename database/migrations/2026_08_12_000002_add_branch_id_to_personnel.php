<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personnel') && ! Schema::hasColumn('personnel', 'branchId')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->string('branchId', 36)->nullable()->index()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('personnel') && Schema::hasColumn('personnel', 'branchId')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->dropColumn('branchId');
            });
        }
    }
};
