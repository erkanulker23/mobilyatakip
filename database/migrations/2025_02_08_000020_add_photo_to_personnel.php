<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personnel') && !Schema::hasColumn('personnel', 'photoUrl')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->string('photoUrl')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('personnel') && Schema::hasColumn('personnel', 'photoUrl')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->dropColumn('photoUrl');
            });
        }
    }
};
