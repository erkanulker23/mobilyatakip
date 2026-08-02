<?php

namespace App\Services;

use App\Models\Kasa;
use App\Models\KasaHareket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KasaService
{
    /** @return array{opening: float, totalIn: float, totalOut: float, netMovements: float, current: float, count: int} */
    public function summary(Kasa $kasa): array
    {
        $opening = (float) ($kasa->openingBalance ?? 0);
        $netMovements = (float) $kasa->hareketler()->sum('amount');
        $totalIn = (float) $kasa->hareketler()->where('amount', '>', 0)->sum('amount');
        $totalOut = abs((float) $kasa->hareketler()->where('amount', '<', 0)->sum('amount'));

        return [
            'opening' => $opening,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netMovements' => $netMovements,
            'current' => $opening + $netMovements,
            'count' => (int) $kasa->hareketler()->count(),
        ];
    }

    public function transfer(Kasa $from, Kasa $to, float $amount, string $movementDate, ?string $description, ?string $createdBy): string
    {
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Kaynak ve hedef kasa aynı olamaz.');
        }

        $transferId = (string) Str::uuid();
        $descOut = 'Virman → ' . $to->name;
        $descIn = 'Virman ← ' . $from->name;
        if ($description) {
            $descOut .= ' — ' . $description;
            $descIn .= ' — ' . $description;
        }

        DB::transaction(function () use ($from, $to, $amount, $movementDate, $descOut, $descIn, $transferId, $createdBy) {
            KasaHareket::create([
                'kasaId' => $from->id,
                'type' => 'virman_cikis',
                'amount' => -abs($amount),
                'movementDate' => $movementDate,
                'description' => $descOut,
                'fromKasaId' => $from->id,
                'toKasaId' => $to->id,
                'createdBy' => $createdBy,
                'refType' => 'kasa_transfer',
                'refId' => $transferId,
            ]);

            KasaHareket::create([
                'kasaId' => $to->id,
                'type' => 'virman_giris',
                'amount' => abs($amount),
                'movementDate' => $movementDate,
                'description' => $descIn,
                'fromKasaId' => $from->id,
                'toKasaId' => $to->id,
                'createdBy' => $createdBy,
                'refType' => 'kasa_transfer',
                'refId' => $transferId,
            ]);
        });

        return $transferId;
    }
}
