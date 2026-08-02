<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('orderStatus', 20)->default('pending')->after('deliveredAt');
        });

        DB::table('sales')->whereNotNull('deliveredAt')->update(['orderStatus' => 'delivered']);

        $sshSaleIds = DB::table('service_tickets')
            ->whereNotNull('saleId')
            ->whereNotIn('status', ['tamamlandi', 'iptal'])
            ->pluck('saleId')
            ->unique()
            ->filter()
            ->all();

        if ($sshSaleIds !== []) {
            DB::table('sales')->whereIn('id', $sshSaleIds)->update(['orderStatus' => 'ssh']);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('orderStatus');
        });
    }
};
