<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kasa_hareket') && !Schema::hasColumn('kasa_hareket', 'refType')) {
            Schema::table('kasa_hareket', function (Blueprint $table) {
                $table->string('refType', 50)->nullable()->after('createdBy');
                $table->string('refId', 36)->nullable()->after('refType');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kasa_hareket') && Schema::hasColumn('kasa_hareket', 'refType')) {
            Schema::table('kasa_hareket', function (Blueprint $table) {
                $table->dropColumn(['refType', 'refId']);
            });
        }
    }
};
