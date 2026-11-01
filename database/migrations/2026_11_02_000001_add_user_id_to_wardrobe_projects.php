<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wardrobe_projects') || Schema::hasColumn('wardrobe_projects', 'userId')) {
            return;
        }

        Schema::table('wardrobe_projects', function (Blueprint $table) {
            $table->string('userId', 36)->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wardrobe_projects') || ! Schema::hasColumn('wardrobe_projects', 'userId')) {
            return;
        }

        Schema::table('wardrobe_projects', function (Blueprint $table) {
            $table->dropIndex(['userId']);
            $table->dropColumn('userId');
        });
    }
};
