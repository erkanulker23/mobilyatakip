<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales', 'efaturaUuid')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('efaturaUuid', 100)->nullable()->after('isCancelled');
                $table->string('efaturaStatus', 50)->nullable()->after('efaturaUuid'); // pending, sent, accepted, rejected
                $table->timestamp('efaturaSentAt')->nullable()->after('efaturaStatus');
                $table->string('efaturaEnvelopeId', 100)->nullable()->after('efaturaSentAt');
                $table->text('efaturaResponse')->nullable()->after('efaturaEnvelopeId');
            });
        }

        if (!Schema::hasColumn('purchases', 'efaturaUuid')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('efaturaUuid', 100)->nullable()->after('isCancelled');
                $table->string('efaturaStatus', 50)->nullable()->after('efaturaUuid');
                $table->timestamp('efaturaSentAt')->nullable()->after('efaturaStatus');
                $table->string('efaturaEnvelopeId', 100)->nullable()->after('efaturaSentAt');
                $table->text('efaturaResponse')->nullable()->after('efaturaEnvelopeId');
            });
        }

        if (!Schema::hasColumn('companies', 'efaturaProvider')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('efaturaProvider', 50)->nullable()->after('mailSecure'); // gib, entegrator, fitbulut, etc.
                $table->string('efaturaEndpoint', 500)->nullable()->after('efaturaProvider');
                $table->string('efaturaUsername', 255)->nullable()->after('efaturaEndpoint');
                $table->string('efaturaPassword', 255)->nullable()->after('efaturaUsername');
                $table->boolean('efaturaTestMode')->default(true)->after('efaturaPassword');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['efaturaUuid', 'efaturaStatus', 'efaturaSentAt', 'efaturaEnvelopeId', 'efaturaResponse']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['efaturaUuid', 'efaturaStatus', 'efaturaSentAt', 'efaturaEnvelopeId', 'efaturaResponse']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['efaturaProvider', 'efaturaEndpoint', 'efaturaUsername', 'efaturaPassword', 'efaturaTestMode']);
        });
    }
};
