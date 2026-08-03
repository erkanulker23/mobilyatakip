<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'lastLoginAt')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('lastLoginAt')->nullable()->after('isActive');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'lastLoginAt')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lastLoginAt');
        });
    }
};
