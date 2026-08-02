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
        if ($value === null || $value === '') {
            return 0.0;
        }

        $normalized = trim($value);
        if (preg_match('/^\d+\.\d+$/', $normalized)) {
            return (float) $normalized;
        }

        $normalized = str_replace([' ', '.'], '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }
}
