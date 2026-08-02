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
            DB::statement('ALTER TABLE service_tickets DROP FOREIGN KEY FK_7d3196dd9eb6ddc354907833901');
            DB::statement('ALTER TABLE service_tickets MODIFY saleId VARCHAR(36) NULL');
            DB::statement('ALTER TABLE service_tickets ADD CONSTRAINT FK_7d3196dd9eb6ddc354907833901 FOREIGN KEY (saleId) REFERENCES sales(id) ON DELETE SET NULL');
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
