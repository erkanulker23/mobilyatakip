<?php

namespace App\Services;

use App\Models\TurkeyCity;
use App\Models\TurkeyDistrict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TurkeyLocationService
{
    private const API_BASE = 'https://api.turkiyeapi.dev/v2';

    public function ensureSynced(): void
    {
        if (TurkeyCity::query()->exists()) {
            return;
        }

        $this->sync();
    }

    public function sync(): void
    {
        DB::transaction(function () {
            $this->syncCities();
            $this->syncDistricts();
        });
    }

    private function syncCities(): void
    {
        $response = Http::timeout(30)->get(self::API_BASE . '/provinces', [
            'fields' => 'id,name',
            'limit' => 100,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('İl verileri alınamadı: ' . $response->status());
        }

        foreach ($response->json('data', []) as $city) {
            TurkeyCity::query()->updateOrCreate(
                ['id' => (int) $city['id']],
                ['name' => $city['name']]
            );
        }
    }

    private function syncDistricts(): void
    {
        $offset = 0;
        $total = null;

        do {
            $response = Http::timeout(60)->get(self::API_BASE . '/districts', [
                'fields' => 'id,name,provinceId',
                'limit' => 200,
                'offset' => $offset,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('İlçe verileri alınamadı: ' . $response->status());
            }

            $payload = $response->json();
            $rows = $payload['data'] ?? [];
            $total = $payload['meta']['total'] ?? count($rows);

            foreach ($rows as $district) {
                TurkeyDistrict::query()->updateOrCreate(
                    ['id' => (int) $district['id']],
                    [
                        'cityId' => (int) $district['provinceId'],
                        'name' => $district['name'],
                    ]
                );
            }

            $offset += count($rows);
        } while ($offset < $total);
    }
}
