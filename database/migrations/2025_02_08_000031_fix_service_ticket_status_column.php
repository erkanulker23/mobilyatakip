<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_tickets') || ! Schema::hasColumn('service_tickets', 'status')) {
            return;
        }

        DB::statement("UPDATE service_tickets SET status = 'devam_ediyor' WHERE status IN ('incelemede', 'parca_bekliyor')");
        DB::statement("UPDATE service_tickets SET status = 'tamamlandi' WHERE status IN ('cozuldu', 'kapandi')");
        DB::statement("UPDATE service_tickets SET status = 'acildi' WHERE status IS NULL OR status = ''");
        DB::statement("ALTER TABLE service_tickets MODIFY status VARCHAR(50) NOT NULL DEFAULT 'acildi'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_tickets') || ! Schema::hasColumn('service_tickets', 'status')) {
            return;
        }

        DB::statement("UPDATE service_tickets SET status = 'incelemede' WHERE status = 'devam_ediyor'");
        DB::statement("UPDATE service_tickets SET status = 'cozuldu' WHERE status = 'tamamlandi'");
        DB::statement("UPDATE service_tickets SET status = 'kapandi' WHERE status = 'iptal'");
        DB::statement("ALTER TABLE service_tickets MODIFY status ENUM('acildi','incelemede','parca_bekliyor','cozuldu','kapandi') NOT NULL DEFAULT 'acildi'");
    }
};
