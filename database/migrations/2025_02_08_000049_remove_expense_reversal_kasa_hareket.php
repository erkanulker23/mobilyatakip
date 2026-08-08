<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kasa_hareket')) {
            return;
        }

        DB::table('kasa_hareket')
            ->where('description', 'like', 'Gider iptal%')
            ->delete();
    }

    public function down(): void
    {
        // Geri alınamaz — eski gider iptal kayıtları kasa defterini yapay olarak şişiriyordu.
    }
};
