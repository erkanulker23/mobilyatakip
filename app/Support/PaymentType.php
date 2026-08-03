<?php

namespace App\Support;

use App\Models\Kasa;

class PaymentType
{
    /** Yeni ödemelerde seçilebilir tipler (tedarikçi / nakliye ödemeleri vb.) */
    public const SELECTABLE = [
        'nakit' => 'Nakit Elden',
        'havale' => 'Havale',
        'kredi_karti' => 'Kredi Kartı',
        'diger' => 'Diğer',
    ];

    /** Müşteriden tahsilat (Ödeme Al) ekranı */
    public const CUSTOMER_RECEIVE = [
        'nakit' => 'Nakit Elden',
        'havale' => 'Havale',
        'kredi_karti' => 'Kredi Kartı',
        'tedarikciye_ode' => 'Tedarikçiye Öde',
    ];

    /** Eski kayıtlar için etiket (yeni seçimde kullanılmaz) */
    public const LEGACY = [
        'cek' => 'Çek',
        'senet' => 'Senet',
    ];

    public static function labels(): array
    {
        return self::SELECTABLE + self::CUSTOMER_RECEIVE + self::LEGACY;
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

    public static function customerReceiveValidationRule(): string
    {
        return 'nullable|in:' . implode(',', array_keys(self::CUSTOMER_RECEIVE));
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
            'havale' => ['banka'],
            'kredi_karti' => ['banka', 'kasa'],
            default => [],
        };
    }

    public static function isBankAccount(Kasa $kasa): bool
    {
        return $kasa->type === 'banka'
            || filled($kasa->iban)
            || filled($kasa->bankName);
    }

    public static function kasaTypeLabel(Kasa $kasa): string
    {
        if ($kasa->type === 'banka' || self::isBankAccount($kasa)) {
            return 'Banka';
        }

        return 'Nakit Kasa';
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
        return match ($paymentType) {
            'nakit' => $kasa->type === 'kasa',
            'havale' => $kasa->type === 'banka' || self::isBankAccount($kasa),
            'kredi_karti' => in_array($kasa->type, ['banka', 'kasa'], true),
            default => true,
        };
    }

    public static function validateKasaSelection(?string $kasaId, string $paymentType, bool $required = true): ?string
    {
        if (!$required || !self::requiresKasa($paymentType)) {
            return null;
        }

        if (empty($kasaId)) {
            return match ($paymentType) {
                'nakit' => 'Nakit elden ödeme için nakit kasası seçin.',
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
                'nakit' => 'Nakit elden ödemeler için «Nakit Kasa» tipinde hesap seçin (banka hesabı kullanılamaz).',
                'havale' => 'Havale için geçerli bir hesap seçin.',
                'kredi_karti' => 'Kredi kartı tahsilatı için geçerli bir hesap seçin.',
                default => 'Seçilen hesap bu ödeme tipi için uygun değil.',
            };
        }

        return null;
    }
}
