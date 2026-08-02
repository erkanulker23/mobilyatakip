<?php

use App\Support\MigrationForeignKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_tickets') && Schema::hasColumn('service_tickets', 'saleId')) {
            if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasTable('sales')) {
                MigrationForeignKeys::dropOnColumn('service_tickets', 'saleId');
                MigrationForeignKeys::alignColumn('service_tickets', 'saleId', 'sales', 'id');
                MigrationForeignKeys::addIfMissing(
                    'service_tickets',
                    'saleId',
                    'sales',
                    'id',
                    'service_tickets_saleid_foreign'
                );
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
