<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'photoUrl')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('photoUrl', 500)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'photoUrl')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photoUrl');
        });
    }
};
