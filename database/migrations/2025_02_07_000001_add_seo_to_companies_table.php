<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fallback: companies tablosu yoksa oluştur (2024_12_01 henüz çalışmamışsa veya eksik deploy)
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
            return;
        }

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
        if (!Schema::hasTable('companies')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'metaTitle')) {
                $table->dropColumn('metaTitle');
            }
            if (Schema::hasColumn('companies', 'metaDescription')) {
                $table->dropColumn('metaDescription');
            }
        });
    }
};
