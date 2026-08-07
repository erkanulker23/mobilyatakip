<?php

use App\Support\MigrationForeignKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotes') || Schema::hasColumn('quotes', 'createdBy')) {
            return;
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('createdBy', 36)->nullable()->index()->after('personnelId');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasTable('users')) {
            MigrationForeignKeys::alignColumn('quotes', 'createdBy', 'users', 'id');
            MigrationForeignKeys::addIfMissing(
                'quotes',
                'createdBy',
                'users',
                'id',
                'quotes_createdby_foreign'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotes') || ! Schema::hasColumn('quotes', 'createdBy')) {
            return;
        }

        MigrationForeignKeys::dropOnColumn('quotes', 'createdBy');

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('createdBy');
        });
    }
};
