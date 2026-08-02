<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_ticket_details') && Schema::hasColumn('service_ticket_details', 'userId')) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $fks = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [DB::getDatabaseName(), 'service_ticket_details', 'userId']
                );
                foreach ($fks as $fk) {
                    DB::statement("ALTER TABLE service_ticket_details DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }

                DB::statement('ALTER TABLE service_ticket_details MODIFY userId VARCHAR(36) NULL');

                $remaining = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [DB::getDatabaseName(), 'service_ticket_details', 'userId']
                );
                if (empty($remaining)) {
                    DB::statement('ALTER TABLE service_ticket_details ADD CONSTRAINT service_ticket_details_userid_foreign FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL');
                }
            }
        }

        if (Schema::hasTable('service_ticket_details') && ! Schema::hasColumn('service_ticket_details', 'updatedAt')) {
            DB::statement('ALTER TABLE service_ticket_details ADD updatedAt TIMESTAMP NULL DEFAULT NULL AFTER createdAt');
        }
    }

    public function down(): void
    {
        //
    }
};
