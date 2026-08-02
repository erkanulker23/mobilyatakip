<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_tickets') && ! Schema::hasColumn('service_tickets', 'dueDate')) {
            Schema::table('service_tickets', function (Blueprint $table) {
                $table->date('dueDate')->nullable()->after('openedAt');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_tickets') && Schema::hasColumn('service_tickets', 'dueDate')) {
            Schema::table('service_tickets', function (Blueprint $table) {
                $table->dropColumn('dueDate');
            });
        }
    }
};
