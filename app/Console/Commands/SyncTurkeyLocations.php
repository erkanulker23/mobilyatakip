<?php

namespace App\Console\Commands;

use App\Services\TurkeyLocationService;
use Illuminate\Console\Command;

class SyncTurkeyLocations extends Command
{
    protected $signature = 'turkey-locations:sync';

    protected $description = 'TurkiyeAPI üzerinden il ve ilçe verilerini senkronize eder';

    public function handle(TurkeyLocationService $locationService): int
    {
        $this->info('İl ve ilçe verileri indiriliyor...');

        try {
            $locationService->sync();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Senkronizasyon tamamlandı.');

        return self::SUCCESS;
    }
}
