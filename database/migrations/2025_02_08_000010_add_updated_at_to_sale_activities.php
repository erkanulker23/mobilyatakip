<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_activities') && !Schema::hasColumn('sale_activities', 'updatedAt')) {
            Schema::table('sale_activities', function (Blueprint $table) {
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate()->after('createdAt');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_activities') && Schema::hasColumn('sale_activities', 'updatedAt')) {
            Schema::table('sale_activities', function (Blueprint $table) {
                $table->dropColumn('updatedAt');
            });
        }
    }
};
