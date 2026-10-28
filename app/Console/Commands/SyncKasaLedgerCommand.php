<?php

namespace App\Console\Commands;

use App\Services\KasaLedgerSync;
use Illuminate\Console\Command;

class SyncKasaLedgerCommand extends Command
{
    protected $signature = 'kasa:sync-ledger';

    protected $description = 'Tahsilat, gider, tedarikçi, nakliye ve avans kayıtlarını kasa defterine birer kez hizalar';

    public function handle(KasaLedgerSync $sync): int
    {
        $stats = $sync->sync();
        $this->info(sprintf(
            'Kasa defteri hizalandı. Eklenen: %d, güncellenen: %d, silinen fazla hareket: %d',
            $stats['created'],
            $stats['updated'],
            $stats['removed'],
        ));

        return self::SUCCESS;
    }
}
