<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personnel') && ! Schema::hasColumn('personnel', 'commissionRate')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->decimal('commissionRate', 5, 2)->nullable()->default(0)->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('personnel') && Schema::hasColumn('personnel', 'commissionRate')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->dropColumn('commissionRate');
            });
        }
    }
};
