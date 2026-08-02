<?php

namespace App\Console\Commands;

use App\Services\TurkeyLocationService;
use Illuminate\Console\Command;

class SyncTurkeyLocations extends Command
{
    protected $signature = 'turkey-locations:sync
                            {--if-empty : Sadece il tablosu boşsa API\'den indir (deploy için önerilir)}';

    protected $description = 'TurkiyeAPI üzerinden il ve ilçe verilerini senkronize eder';

    public function handle(TurkeyLocationService $locationService): int
    {
        if ($this->option('if-empty')) {
            try {
                $locationService->ensureSynced();
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->info('İl/ilçe referans verisi hazır.');

            return self::SUCCESS;
        }

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
