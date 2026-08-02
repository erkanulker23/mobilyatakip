<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->string('shippingCompanyId', 36)->nullable()->after('assignedDriverPhone')->index();
            $table->string('shippingVehicleId', 36)->nullable()->after('shippingCompanyId')->index();
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn(['shippingCompanyId', 'shippingVehicleId']);
        });
    }
};
