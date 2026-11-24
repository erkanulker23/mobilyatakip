<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSortOrder('quote_items', 'quoteId');
        $this->addSortOrder('sale_items', 'saleId');
    }

    public function down(): void
    {
        foreach (['quote_items', 'sale_items'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sortOrder')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('sortOrder');
                });
            }
        }
    }

    private function addSortOrder(string $table, string $parentColumn): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'sortOrder')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('sortOrder')->default(0);
        });

        $parentIds = DB::table($table)->distinct()->pluck($parentColumn);
        foreach ($parentIds as $parentId) {
            $query = DB::table($table)->where($parentColumn, $parentId);
            if (Schema::hasColumn($table, 'createdAt')) {
                $query->orderBy('createdAt')->orderBy('id');
            } else {
                $query->orderBy('id');
            }
            foreach ($query->get(['id']) as $index => $row) {
                DB::table($table)->where('id', $row->id)->update(['sortOrder' => $index]);
            }
        }
    }
};
