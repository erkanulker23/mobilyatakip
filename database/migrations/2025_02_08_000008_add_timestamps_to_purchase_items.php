<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_items', 'createdAt')) {
                    $table->timestamp('createdAt')->useCurrent()->after('lineTotal');
                }
                if (!Schema::hasColumn('purchase_items', 'updatedAt')) {
                    $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate()->after('createdAt');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('purchase_items', 'updatedAt')) {
                    $columns[] = 'updatedAt';
                }
                if (Schema::hasColumn('purchase_items', 'createdAt')) {
                    $columns[] = 'createdAt';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
