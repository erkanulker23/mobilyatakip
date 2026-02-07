<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * İlk deploy veya boş veritabanında tüm uygulama tablolarını oluşturur.
     * hasTable ile kontrol edilir; mevcut tablo/veri asla silinmez.
     */
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->text('address')->nullable();
                $table->string('taxNumber', 50)->nullable();
                $table->string('taxOffice', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('logoUrl', 500)->nullable();
                $table->string('website', 255)->nullable();
                $table->string('metaTitle', 70)->nullable();
                $table->string('metaDescription', 160)->nullable();
                $table->string('ntgsmUsername', 100)->nullable();
                $table->string('ntgsmPassword', 100)->nullable();
                $table->string('ntgsmOriginator', 50)->nullable();
                $table->string('ntgsmApiUrl', 255)->nullable();
                $table->string('paytrMerchantId', 50)->nullable();
                $table->string('paytrMerchantKey', 100)->nullable();
                $table->string('paytrMerchantSalt', 100)->nullable();
                $table->boolean('paytrTestMode')->default(false);
                $table->string('mailHost', 100)->nullable();
                $table->integer('mailPort')->nullable();
                $table->string('mailUser', 100)->nullable();
                $table->string('mailPassword', 100)->nullable();
                $table->string('mailFrom', 100)->nullable();
                $table->boolean('mailSecure')->default(false);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->text('address')->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('taxNumber', 50)->nullable();
                $table->string('taxOffice', 100)->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('taxNumber', 50)->nullable();
                $table->string('taxOffice', 100)->nullable();
                $table->string('identityNumber', 20)->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('sku', 100)->nullable()->index();
                $table->string('externalId', 100)->nullable();
                $table->string('externalSource', 50)->nullable();
                $table->decimal('unitPrice', 14, 2)->default(0);
                $table->boolean('kdvIncluded')->default(true);
                $table->decimal('kdvRate', 5, 2)->default(18);
                $table->json('images')->nullable();
                $table->string('supplierId', 36)->nullable()->index();
                $table->integer('minStockLevel')->default(0);
                $table->boolean('isActive')->default(true);
                $table->text('description')->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('kasa')) {
            Schema::create('kasa', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type', 50)->nullable();
                $table->string('accountNumber', 50)->nullable();
                $table->string('iban', 50)->nullable();
                $table->string('bankName', 100)->nullable();
                $table->decimal('openingBalance', 14, 2)->default(0);
                $table->string('currency', 10)->default('TRY');
                $table->boolean('isActive')->default(true);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('personnel')) {
            Schema::create('personnel', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('category', 100)->nullable();
                $table->string('title', 100)->nullable();
                $table->string('vehiclePlate', 20)->nullable();
                $table->text('driverInfo')->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('quoteNumber', 50)->index();
                $table->string('customerId', 36)->nullable()->index();
                $table->boolean('kdvIncluded')->default(true);
                $table->string('status', 50)->nullable();
                $table->decimal('generalDiscountPercent', 5, 2)->nullable();
                $table->decimal('generalDiscountAmount', 14, 2)->nullable();
                $table->integer('revision')->default(0);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('kdvTotal', 14, 2)->default(0);
                $table->decimal('grandTotal', 14, 2)->default(0);
                $table->date('validUntil')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('isCancelled')->default(false);
                $table->string('convertedSaleId', 36)->nullable();
                $table->string('personnelId', 36)->nullable();
                $table->string('customerSource', 100)->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('quote_items')) {
            Schema::create('quote_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('quoteId', 36)->index();
                $table->string('productId', 36)->index();
                $table->decimal('unitPrice', 14, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->decimal('lineDiscountPercent', 5, 2)->nullable();
                $table->decimal('lineDiscountAmount', 14, 2)->nullable();
                $table->decimal('kdvRate', 5, 2)->default(18);
                $table->decimal('lineTotal', 14, 2)->default(0);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('saleNumber', 50)->index();
                $table->string('customerId', 36)->nullable()->index();
                $table->boolean('kdvIncluded')->default(true);
                $table->string('quoteId', 36)->nullable();
                $table->date('saleDate')->nullable();
                $table->date('dueDate')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('kdvTotal', 14, 2)->default(0);
                $table->decimal('grandTotal', 14, 2)->default(0);
                $table->decimal('paidAmount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->boolean('isCancelled')->default(false);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('saleId', 36)->index();
                $table->string('productId', 36)->index();
                $table->decimal('unitPrice', 14, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->decimal('kdvRate', 5, 2)->default(18);
                $table->decimal('lineTotal', 14, 2)->default(0);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('purchaseNumber', 50)->index();
                $table->string('supplierId', 36)->nullable()->index();
                $table->boolean('kdvIncluded')->default(true);
                $table->date('purchaseDate')->nullable();
                $table->date('dueDate')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('kdvTotal', 14, 2)->default(0);
                $table->decimal('grandTotal', 14, 2)->default(0);
                $table->decimal('paidAmount', 14, 2)->default(0);
                $table->boolean('isReturn')->default(false);
                $table->text('notes')->nullable();
                $table->boolean('isCancelled')->default(false);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('purchaseId', 36)->index();
                $table->string('productId', 36)->index();
                $table->decimal('unitPrice', 14, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->decimal('kdvRate', 5, 2)->default(18);
                $table->decimal('lineTotal', 14, 2)->default(0);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->decimal('amount', 14, 2)->default(0);
                $table->date('expenseDate')->nullable();
                $table->text('description')->nullable();
                $table->string('category', 100)->nullable();
                $table->string('kasaId', 36)->nullable()->index();
                $table->string('createdBy', 36)->nullable()->index();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('kasa_hareket')) {
            Schema::create('kasa_hareket', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type', 50)->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->date('movementDate')->nullable();
                $table->text('description')->nullable();
                $table->string('kasaId', 36)->nullable()->index();
                $table->string('fromKasaId', 36)->nullable();
                $table->string('toKasaId', 36)->nullable();
                $table->string('createdBy', 36)->nullable()->index();
                $table->string('refType', 50)->nullable();
                $table->string('refId', 36)->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('service_tickets')) {
            Schema::create('service_tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('ticketNumber', 50)->index();
                $table->string('saleId', 36)->nullable()->index();
                $table->string('customerId', 36)->nullable()->index();
                $table->string('status', 50)->nullable();
                $table->boolean('underWarranty')->default(false);
                $table->string('issueType', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('assignedUserId', 36)->nullable();
                $table->string('assignedVehiclePlate', 20)->nullable();
                $table->string('assignedDriverName', 100)->nullable();
                $table->string('assignedDriverPhone', 50)->nullable();
                $table->dateTime('openedAt')->nullable();
                $table->dateTime('closedAt')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('serviceChargeAmount', 14, 2)->nullable();
                $table->json('images')->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('service_ticket_details')) {
            Schema::create('service_ticket_details', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('ticketId', 36)->index();
                $table->string('userId', 36)->nullable()->index();
                $table->string('action', 50)->nullable();
                $table->dateTime('actionDate')->nullable();
                $table->text('notes')->nullable();
                $table->json('images')->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('service_parts')) {
            Schema::create('service_parts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('detailId', 36)->index();
                $table->string('productId', 36)->index();
                $table->integer('quantity')->default(1);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('productId', 36)->index();
                $table->string('warehouseId', 36)->index();
                $table->integer('quantity')->default(0);
                $table->integer('reservedQuantity')->default(0);
            });
        }

        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('productId', 36)->index();
                $table->string('warehouseId', 36)->index();
                $table->string('type', 50)->nullable();
                $table->integer('quantity')->default(0);
                $table->string('refType', 50)->nullable();
                $table->string('refId', 36)->nullable();
                $table->string('userId', 36)->nullable()->index();
                $table->text('description')->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('customerId', 36)->index();
                $table->string('kasaId', 36)->nullable()->index();
                $table->decimal('amount', 14, 2)->default(0);
                $table->date('paymentDate')->nullable();
                $table->string('paymentType', 50)->nullable();
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->string('saleId', 36)->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('supplierId', 36)->index();
                $table->string('kasaId', 36)->nullable()->index();
                $table->decimal('amount', 14, 2)->default(0);
                $table->date('paymentDate')->nullable();
                $table->string('paymentType', 50)->nullable();
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->string('purchaseId', 36)->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('supplier_statements')) {
            Schema::create('supplier_statements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('supplierId', 36)->index();
                $table->date('startDate')->nullable();
                $table->date('endDate')->nullable();
                $table->decimal('openingBalance', 14, 2)->default(0);
                $table->decimal('totalPurchases', 14, 2)->default(0);
                $table->decimal('totalPayments', 14, 2)->default(0);
                $table->decimal('closingBalance', 14, 2)->default(0);
                $table->string('status', 50)->nullable();
                $table->string('pdfUrl', 500)->nullable();
                $table->dateTime('sentAt')->nullable();
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('xml_feeds')) {
            Schema::create('xml_feeds', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('url', 500)->nullable();
                $table->string('supplierId', 36)->nullable()->index();
                $table->timestamp('createdAt')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Aşağıdaki tabloları sadece boş/ilk kurulumda kaldırmak isteyebilirsiniz.
        // Mevcut veriyi silmemek için down() boş bırakıldı.
        // Gerekirse elle drop edebilirsiniz.
    }
};
