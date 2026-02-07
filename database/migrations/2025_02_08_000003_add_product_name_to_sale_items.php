<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_items')) {
            return;
        }
        if (!Schema::hasColumn('sale_items', 'productName')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->string('productName', 255)->nullable()->after('productId');
            });
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'sale_items' AND REFERENCED_TABLE_NAME IS NOT NULL AND COLUMN_NAME = 'productId'", [DB::getDatabaseName()]);
            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE sale_items DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
            DB::statement('ALTER TABLE sale_items MODIFY productId VARCHAR(36) NULL');
            DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_productid_foreign FOREIGN KEY (productId) REFERENCES products(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_items', 'productName')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('productName');
            });
        }
    }
};
