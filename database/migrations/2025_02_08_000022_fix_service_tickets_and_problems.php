<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_tickets') && Schema::hasColumn('service_tickets', 'saleId')) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $fks = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [DB::getDatabaseName(), 'service_tickets', 'saleId']
                );
                foreach ($fks as $fk) {
                    DB::statement("ALTER TABLE service_tickets DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }

                DB::statement('ALTER TABLE service_tickets MODIFY saleId VARCHAR(36) NULL');

                $remaining = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [DB::getDatabaseName(), 'service_tickets', 'saleId']
                );
                if (empty($remaining)) {
                    DB::statement('ALTER TABLE service_tickets ADD CONSTRAINT service_tickets_saleid_foreign FOREIGN KEY (saleId) REFERENCES sales(id) ON DELETE SET NULL');
                }
            }
        }

        if (Schema::hasTable('service_tickets') && ! Schema::hasColumn('service_tickets', 'reportedProblems')) {
            Schema::table('service_tickets', function (Blueprint $table) {
                $table->json('reportedProblems')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_tickets') && Schema::hasColumn('service_tickets', 'reportedProblems')) {
            Schema::table('service_tickets', function (Blueprint $table) {
                $table->dropColumn('reportedProblems');
            });
        }
    }
};
