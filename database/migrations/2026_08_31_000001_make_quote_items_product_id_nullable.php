<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quote_items') || ! Schema::hasColumn('quote_items', 'productId')) {
            return;
        }

        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'quote_items'
             AND REFERENCED_TABLE_NAME IS NOT NULL AND COLUMN_NAME = 'productId'",
            [DB::getDatabaseName()]
        );

        foreach ($fks as $fk) {
            $name = $fk->CONSTRAINT_NAME ?? $fk->constraint_name ?? null;
            if ($name) {
                DB::statement('ALTER TABLE quote_items DROP FOREIGN KEY `'.$name.'`');
            }
        }

        DB::statement('ALTER TABLE quote_items MODIFY productId VARCHAR(36) NULL');

        if (Schema::hasTable('products')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $table->foreign('productId')->references('id')->on('products')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('quote_items') || ! Schema::hasColumn('quote_items', 'productId')) {
            return;
        }

        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'quote_items'
             AND REFERENCED_TABLE_NAME IS NOT NULL AND COLUMN_NAME = 'productId'",
            [DB::getDatabaseName()]
        );

        foreach ($fks as $fk) {
            $name = $fk->CONSTRAINT_NAME ?? $fk->constraint_name ?? null;
            if ($name) {
                DB::statement('ALTER TABLE quote_items DROP FOREIGN KEY `'.$name.'`');
            }
        }

        DB::table('quote_items')->whereNull('productId')->delete();
        DB::statement('ALTER TABLE quote_items MODIFY productId VARCHAR(36) NOT NULL');
    }
};
