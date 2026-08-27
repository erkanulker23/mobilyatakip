<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personnel')) {
            return;
        }

        Schema::table('personnel', function (Blueprint $table) {
            if (! Schema::hasColumn('personnel', 'hiredAt')) {
                $table->date('hiredAt')->nullable()->after('isActive');
            }
            if (! Schema::hasColumn('personnel', 'leftAt')) {
                $table->date('leftAt')->nullable()->after('hiredAt');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('personnel')) {
            return;
        }

        Schema::table('personnel', function (Blueprint $table) {
            if (Schema::hasColumn('personnel', 'leftAt')) {
                $table->dropColumn('leftAt');
            }
            if (Schema::hasColumn('personnel', 'hiredAt')) {
                $table->dropColumn('hiredAt');
            }
        });
    }
};
