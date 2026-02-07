<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            if (!Schema::hasColumn('expenses', 'kdvIncluded')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->boolean('kdvIncluded')->default(true)->after('amount');
                });
            }
            if (!Schema::hasColumn('expenses', 'kdvRate')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->decimal('kdvRate', 5, 2)->nullable()->default(18)->after('kdvIncluded');
                });
            }
            if (!Schema::hasColumn('expenses', 'kdvAmount')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->decimal('kdvAmount', 14, 2)->nullable()->after('kdvRate');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn(['kdvIncluded', 'kdvRate', 'kdvAmount']);
            });
        }
    }
};
