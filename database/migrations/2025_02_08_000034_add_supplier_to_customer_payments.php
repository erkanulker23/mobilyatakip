<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_payments', 'supplierId')) {
                $table->string('supplierId', 36)->nullable()->index()->after('saleId');
            }
            if (! Schema::hasColumn('customer_payments', 'linkedSupplierPaymentId')) {
                $table->string('linkedSupplierPaymentId', 36)->nullable()->index()->after('supplierId');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_payments')) {
            return;
        }

        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'linkedSupplierPaymentId')) {
                $table->dropColumn('linkedSupplierPaymentId');
            }
            if (Schema::hasColumn('customer_payments', 'supplierId')) {
                $table->dropColumn('supplierId');
            }
        });
    }
};
