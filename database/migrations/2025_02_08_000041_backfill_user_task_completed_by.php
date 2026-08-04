<?php

use App\Support\UserTaskCompletion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        UserTaskCompletion::backfillFromAuditLogs();
    }

    public function down(): void
    {
        // Geri alınmaz — yalnızca eksik tamamlayan bilgisini audit logdan doldurur.
    }
};
