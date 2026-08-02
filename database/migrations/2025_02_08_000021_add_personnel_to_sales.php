<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'personnelId')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('personnelId', 36)->nullable()->index()->after('customerId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'personnelId')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('personnelId');
            });
        }
    }
};
