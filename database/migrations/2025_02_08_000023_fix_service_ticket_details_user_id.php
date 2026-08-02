<?php

use App\Support\MigrationForeignKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_ticket_details') && Schema::hasColumn('service_ticket_details', 'userId')) {
            if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasTable('users')) {
                MigrationForeignKeys::dropOnColumn('service_ticket_details', 'userId');
                MigrationForeignKeys::alignColumn('service_ticket_details', 'userId', 'users', 'id');
                MigrationForeignKeys::addIfMissing(
                    'service_ticket_details',
                    'userId',
                    'users',
                    'id',
                    'service_ticket_details_userid_foreign'
                );
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
