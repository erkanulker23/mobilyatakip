<?php

namespace App\Support;

class KasaType
{
    public const KASA = 'kasa';

    public const BANKA = 'banka';

    public const KREDI_KARTI = 'kredi_karti';

    /** @var array<string, string> */
    public const LABELS = [
        self::KASA => 'Kasa',
        self::BANKA => 'Banka',
        self::KREDI_KARTI => 'Kredi Kartı',
    ];

    public static function labels(): array
    {
        return self::LABELS;
    }

    public static function label(?string $type): string
    {
        if (! $type) {
            return 'Kasa';
        }

        return self::LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function validationRule(): string
    {
        return 'nullable|in:' . implode(',', array_keys(self::LABELS));
    }

    public static function showsBankFields(?string $type): bool
    {
        return in_array($type, [self::BANKA, self::KREDI_KARTI], true);
    }

    public static function badgeClasses(?string $type): string
    {
        return match ($type) {
            self::BANKA => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
            self::KREDI_KARTI => 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300',
            default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        };
    }
}
