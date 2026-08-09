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
        'nakit' => 'Elden Nakit',
        'kredi_karti' => 'Kredi Kartı',
        'tedarikciye_ode' => 'Tedarikçiye Ödeme',
        'havale' => 'Havale',
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
            'nakit' => [KasaType::KASA],
            'havale' => [KasaType::BANKA],
            'kredi_karti' => [KasaType::KREDI_KARTI, KasaType::BANKA],
            default => [],
        };
    }

    public static function isBankAccount(Kasa $kasa): bool
    {
        if ($kasa->type === KasaType::KREDI_KARTI) {
            return false;
        }

        return $kasa->type === KasaType::BANKA
            || filled($kasa->iban)
            || filled($kasa->bankName);
    }

    public static function kasaTypeLabel(Kasa $kasa): string
    {
        if (self::looksLikeCreditCardKasa($kasa)) {
            return KasaType::label(KasaType::KREDI_KARTI);
        }

        return KasaType::label($kasa->type);
    }

    public static function kasaFieldLabel(string $paymentType): string
    {
        return match ($paymentType) {
            'nakit' => 'Nakit Kasası',
            'havale' => 'Banka Hesabı',
            'kredi_karti' => 'Kredi Kartı Hesabı',
            default => 'Kasa',
        };
    }

    public static function kasaMatchesPaymentType(string $paymentType, Kasa $kasa): bool
    {
        return match ($paymentType) {
            'nakit' => $kasa->type === KasaType::KASA && ! self::looksLikeCreditCardKasa($kasa),
            'havale' => $kasa->type === KasaType::BANKA || self::isBankAccount($kasa),
            'kredi_karti' => in_array($kasa->type, [KasaType::KREDI_KARTI, KasaType::BANKA], true)
                || self::looksLikeCreditCardKasa($kasa),
            default => true,
        };
    }

    public static function looksLikeCreditCardKasa(Kasa $kasa): bool
    {
        if ($kasa->type === KasaType::KREDI_KARTI) {
            return true;
        }

        $name = mb_strtolower($kasa->name ?? '');

        return str_contains($name, 'kredi kart')
            || (str_contains($name, 'pos') && str_contains($name, 'kart'));
    }

    public static function inferTypeFromKasa(?Kasa $kasa): ?string
    {
        if (! $kasa) {
            return null;
        }

        if (self::looksLikeCreditCardKasa($kasa)) {
            return 'kredi_karti';
        }

        if ($kasa->type === KasaType::BANKA || self::isBankAccount($kasa)) {
            return 'havale';
        }

        if ($kasa->type === KasaType::KASA) {
            return 'nakit';
        }

        return null;
    }

    /** Tahsilat raporu için kasa hedefine göre gerçek ödeme tipi. */
    public static function effectiveTypeForKasaMovement(?string $paymentType, ?Kasa $kasa): string
    {
        $inferred = self::inferTypeFromKasa($kasa);

        if ($inferred === null) {
            return $paymentType ?: 'diger';
        }

        if (! $paymentType || ! self::kasaMatchesPaymentType($paymentType, $kasa)) {
            return $inferred;
        }

        return $paymentType;
    }

    public static function syncPaymentTypeWithKasa(string $paymentType, ?Kasa $kasa): string
    {
        if (! $kasa || $paymentType === 'tedarikciye_ode') {
            return $paymentType;
        }

        return self::effectiveTypeForKasaMovement($paymentType, $kasa);
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
                'kredi_karti' => 'Kredi kartı tahsilatı için kredi kartı veya banka hesabı seçin.',
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
                'kredi_karti' => 'Kredi kartı tahsilatı için «Kredi Kartı» veya «Banka» tipinde hesap seçin.',
                default => 'Seçilen hesap bu ödeme tipi için uygun değil.',
            };
        }

        return null;
    }
}
