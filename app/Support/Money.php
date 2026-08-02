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

        $dotCount = substr_count($normalized, '.');

        // 1.234.567 — nokta binlik ayraç
        if ($dotCount > 1) {
            return (float) str_replace('.', '', $normalized);
        }

        if ($dotCount === 1) {
            [$int, $frac] = explode('.', $normalized, 2);

            if ($frac === '') {
                return (float) $int;
            }

            // 50.000 / 50.00000 — binlik (ondalık kısım sadece sıfır, en az 3 hane)
            if (strlen($frac) >= 3 && preg_match('/^0+$/', $frac)) {
                return (float) ($int.substr($frac, 0, 3));
            }

            // Tam 3 hane: 50.000 → 50000
            if (strlen($frac) === 3 && ctype_digit($frac)) {
                return (float) ($int.$frac);
            }

            // 1–2 hane: Laravel/DB ondalığı 1234.56 veya 80.00
            if (strlen($frac) <= 2 && ctype_digit($frac)) {
                return (float) ($int.'.'.$frac);
            }
        }

        return (float) $normalized;
    }
}
