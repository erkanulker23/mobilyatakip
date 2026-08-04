<?php

namespace App\Console\Commands;

use App\Services\AuditLogPruner;
use Illuminate\Console\Command;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune';

    protected $description = '5 günden eski audit logları siler; kullanıcı başına en fazla 15 kayıt bırakır';

    public function handle(): int
    {
        $deleted = AuditLogPruner::pruneAll();

        $this->info("Audit log temizliği tamamlandı. Silinen kayıt: {$deleted}");

        return self::SUCCESS;
    }
}
