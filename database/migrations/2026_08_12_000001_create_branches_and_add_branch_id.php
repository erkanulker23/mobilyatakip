<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->unsignedBigInteger('cityId')->nullable();
                $table->unsignedBigInteger('districtId')->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamp('createdAt')->useCurrent();
                $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
                $table->index('isActive');
            });
        }

        if (Schema::hasTable('sales') && ! Schema::hasColumn('sales', 'branchId')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('branchId', 36)->nullable()->index()->after('customerId');
            });
        }

        if (Schema::hasTable('service_tickets') && ! Schema::hasColumn('service_tickets', 'branchId')) {
            Schema::table('service_tickets', function (Blueprint $table) {
                $table->string('branchId', 36)->nullable()->index()->after('customerId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'branchId')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('branchId');
            });
        }

        if (Schema::hasTable('service_tickets') && Schema::hasColumn('service_tickets', 'branchId')) {
            Schema::table('service_tickets', function (Blueprint $table) {
                $table->dropColumn('branchId');
            });
        }

        Schema::dropIfExists('branches');
    }
};
