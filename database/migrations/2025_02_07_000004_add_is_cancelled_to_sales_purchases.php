<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sales', 'purchases', 'quotes'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'isCancelled')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('isCancelled')->default(false);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['sales', 'purchases', 'quotes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'isCancelled')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('isCancelled');
                });
            }
        }
    }
};
