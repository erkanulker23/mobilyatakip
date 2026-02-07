<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'metaTitle')) {
                $table->string('metaTitle', 70)->nullable()->after('website');
            }
            if (!Schema::hasColumn('companies', 'metaDescription')) {
                $table->string('metaDescription', 160)->nullable()->after('metaTitle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['metaTitle', 'metaDescription']);
        });
    }
};
