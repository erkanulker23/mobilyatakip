<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_company_payments', function (Blueprint $table) {
            $table->string('saleId', 36)->nullable()->after('purchaseId')->index();
            $table->string('serviceTicketId', 36)->nullable()->after('saleId')->index();
            $table->string('paymentFor', 255)->nullable()->after('serviceTicketId');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_company_payments', function (Blueprint $table) {
            $table->dropColumn(['saleId', 'serviceTicketId', 'paymentFor']);
        });
    }
};
