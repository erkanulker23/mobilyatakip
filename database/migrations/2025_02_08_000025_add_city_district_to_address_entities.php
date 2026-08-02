<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'customers',
        'suppliers',
        'companies',
        'warehouses',
        'shipping_companies',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedSmallInteger('cityId')->nullable()->after('address');
                $table->unsignedMediumInteger('districtId')->nullable()->after('cityId');

                $table->foreign('cityId')->references('id')->on('turkey_cities')->nullOnDelete();
                $table->foreign('districtId')->references('id')->on('turkey_districts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['cityId']);
                $table->dropForeign(['districtId']);
                $table->dropColumn(['cityId', 'districtId']);
            });
        }
    }
};
