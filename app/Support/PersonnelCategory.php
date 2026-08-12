<?php

namespace App\Support;

class PersonnelCategory
{
    public const ATOLYE = 'atolye';

    public const MIMAR = 'mimar';

    public const MUHASEBE = 'muhasebe';

    public const PATRON = 'patron';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::ATOLYE => 'Atölye',
            self::MIMAR => 'Mimar',
            self::MUHASEBE => 'Muhasebe',
            self::PATRON => 'Patron',
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::options()[$value] ?? $value;
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && array_key_exists($value, self::options());
    }
}
