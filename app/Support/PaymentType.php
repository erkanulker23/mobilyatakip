<?php

namespace App\Support;

use App\Models\Kasa;

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

    /** @return list<string> */
    public static function allowedKasaTypes(string $paymentType): array
    {
        return match ($paymentType) {
            'nakit' => ['kasa'],
            'havale', 'kredi_karti' => ['banka'],
            default => [],
        };
    }

    public static function kasaFieldLabel(string $paymentType): string
    {
        return match ($paymentType) {
            'nakit' => 'Nakit Kasası',
            'havale' => 'Banka Hesabı',
            'kredi_karti' => 'Banka / POS Hesabı',
            default => 'Kasa',
        };
    }

    public static function kasaMatchesPaymentType(string $paymentType, Kasa $kasa): bool
    {
        $allowed = self::allowedKasaTypes($paymentType);
        if ($allowed === []) {
            return true;
        }

        return in_array($kasa->type, $allowed, true);
    }

    public static function validateKasaSelection(?string $kasaId, string $paymentType, bool $required = true): ?string
    {
        if (!$required || !self::requiresKasa($paymentType)) {
            return null;
        }

        if (empty($kasaId)) {
            return match ($paymentType) {
                'nakit' => 'Nakit ödeme için nakit kasası seçin.',
                'havale' => 'Havale için banka hesabı seçin.',
                'kredi_karti' => 'Kredi kartı tahsilatı için banka hesabı seçin.',
                default => 'Bu ödeme tipi için kasa/hesap seçimi zorunludur.',
            };
        }

        $kasa = Kasa::find($kasaId);
        if (!$kasa || !$kasa->isActive) {
            return 'Seçilen kasa/hesap bulunamadı veya pasif durumda.';
        }

        if (!self::kasaMatchesPaymentType($paymentType, $kasa)) {
            return match ($paymentType) {
                'nakit' => 'Nakit ödemeler için «Nakit Kasa» tipinde hesap seçin.',
                'havale' => 'Havale ödemeleri için «Banka» tipinde hesap seçin.',
                'kredi_karti' => 'Kredi kartı tahsilatları için «Banka» tipinde hesap seçin.',
                default => 'Seçilen hesap bu ödeme tipi için uygun değil.',
            };
        }

        return null;
    }
}
