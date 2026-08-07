<?php

use App\Support\QuoteCreator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        QuoteCreator::backfillFromAuditLogs();
    }

    public function down(): void
    {
        // Geri alınmaz — yalnızca eksik oluşturan bilgisini audit logdan doldurur.
    }
};
