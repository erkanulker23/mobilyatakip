<?php

namespace App\Support;

class PaymentType
{
    /** Yeni ödemelerde seçilebilir tipler */
    public const SELECTABLE = [
        'nakit' => 'Nakit',
        'havale' => 'Havale',
        'kredi_karti' => 'Kredi Kartı',
        'diger' => 'Diğer',
    ];

    /** Eski kayıtlar için etiket (yeni seçimde kullanılmaz) */
    public const LEGACY = [
        'cek' => 'Çek',
        'senet' => 'Senet',
    ];

    public static function labels(): array
    {
        return self::SELECTABLE + self::LEGACY;
    }

    public static function label(?string $type): string
    {
        if (!$type) {
            return '—';
        }

        return self::labels()[$type] ?? ucfirst($type);
    }

    public static function validationRule(): string
    {
        return 'nullable|in:' . implode(',', array_keys(self::SELECTABLE));
    }

    public static function requiresKasa(string $type): bool
    {
        return in_array($type, ['nakit', 'havale', 'kredi_karti'], true);
    }
}
