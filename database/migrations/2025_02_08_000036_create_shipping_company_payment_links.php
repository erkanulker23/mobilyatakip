<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_company_payment_sales', function (Blueprint $table) {
            $table->string('shippingCompanyPaymentId', 36);
            $table->string('saleId', 36);
            $table->primary(['shippingCompanyPaymentId', 'saleId'], 'scp_sales_primary');
            $table->index('saleId');
        });

        Schema::create('shipping_company_payment_service_tickets', function (Blueprint $table) {
            $table->string('shippingCompanyPaymentId', 36);
            $table->string('serviceTicketId', 36);
            $table->primary(['shippingCompanyPaymentId', 'serviceTicketId'], 'scp_ssh_primary');
            $table->index('serviceTicketId');
        });

        DB::table('shipping_company_payments')
            ->whereNotNull('saleId')
            ->orderBy('createdAt')
            ->lazy()
            ->each(function ($payment) {
                DB::table('shipping_company_payment_sales')->insertOrIgnore([
                    'shippingCompanyPaymentId' => $payment->id,
                    'saleId' => $payment->saleId,
                ]);
            });

        DB::table('shipping_company_payments')
            ->whereNotNull('serviceTicketId')
            ->orderBy('createdAt')
            ->lazy()
            ->each(function ($payment) {
                DB::table('shipping_company_payment_service_tickets')->insertOrIgnore([
                    'shippingCompanyPaymentId' => $payment->id,
                    'serviceTicketId' => $payment->serviceTicketId,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_company_payment_service_tickets');
        Schema::dropIfExists('shipping_company_payment_sales');
    }
};
