<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['quotes', 'sales', 'purchases'] as $t) {
            if (Schema::hasTable($t) && !Schema::hasColumn($t, 'kdvIncluded')) {
                Schema::table($t, function (Blueprint $blueprint) {
                    $blueprint->boolean('kdvIncluded')->default(true);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['quotes', 'sales', 'purchases'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'kdvIncluded')) {
                Schema::table($t, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('kdvIncluded');
                });
            }
        }
    }
};
