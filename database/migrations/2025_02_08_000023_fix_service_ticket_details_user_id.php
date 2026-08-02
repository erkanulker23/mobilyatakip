<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_ticket_details') && Schema::hasColumn('service_ticket_details', 'userId')) {
            DB::statement('ALTER TABLE service_ticket_details DROP FOREIGN KEY FK_9447b87d6da1d857d1fc9930be3');
            DB::statement('ALTER TABLE service_ticket_details MODIFY userId VARCHAR(36) NULL');
            DB::statement('ALTER TABLE service_ticket_details ADD CONSTRAINT FK_9447b87d6da1d857d1fc9930be3 FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL');
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
