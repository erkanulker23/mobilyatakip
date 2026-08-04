<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount, int $decimals = 0): string
    {
        return number_format((float) ($amount ?? 0), $decimals, ',', '.');
    }

    public static function parse(?string $value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }

        $normalized = str_replace(' ', '', trim($value));
        if ($normalized === '') {
            return 0.0;
        }

        // Türk formatı: 1.234.567,89 veya 50,5
        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);

            return (float) $normalized;
        }

        // Virgül yok: noktalar binlik ayraç (23.000, 2.3000 → 23000)
        if (str_contains($normalized, '.')) {
            return (float) str_replace('.', '', $normalized);
        }

        return (float) $normalized;
    }
}
