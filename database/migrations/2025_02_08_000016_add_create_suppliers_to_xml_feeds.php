<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xml_feeds', function (Blueprint $table) {
            $table->boolean('createSuppliers')->default(true)->after('supplierId');
        });
    }

    public function down(): void
    {
        Schema::table('xml_feeds', function (Blueprint $table) {
            $table->dropColumn('createSuppliers');
        });
    }
};
