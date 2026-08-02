<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_tasks') || Schema::hasColumn('user_tasks', 'personnelId')) {
            return;
        }

        Schema::table('user_tasks', function (Blueprint $table) {
            $table->string('personnelId', 36)->nullable()->index()->after('userId');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_tasks') || ! Schema::hasColumn('user_tasks', 'personnelId')) {
            return;
        }

        Schema::table('user_tasks', function (Blueprint $table) {
            $table->dropColumn('personnelId');
        });
    }
};
