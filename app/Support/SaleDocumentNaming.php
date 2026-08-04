<?php

namespace App\Support;

use App\Models\Sale;
use Illuminate\Support\Str;

class SaleDocumentNaming
{
    public const TYPE_ORDER = 'siparis';

    public const TYPE_SHIPMENT = 'sevkiyat';

    public const TYPE_WORKSHOP_KOLTUK = 'koltuk-atolye';

    public const TYPE_WORKSHOP_MOBILYA = 'mobilya-atolye';

    public static function customerSlug(Sale $sale): string
    {
        $name = trim((string) ($sale->customer?->name ?? ''));
        $slug = Str::slug($name, '-');

        return $slug !== '' ? $slug : 'musteri';
    }

    public static function saleNumberSlug(Sale $sale): string
    {
        $number = preg_replace('/[^a-zA-Z0-9\-]/', '-', (string) ($sale->saleNumber ?? ''));
        $number = trim((string) $number, '-');

        return $number !== '' ? $number : 'belge';
    }

    /** Yazdırma sekmesi / tarayıcı PDF kaydı için başlık */
    public static function pageTitle(Sale $sale, string $documentLabel): string
    {
        $customer = trim((string) ($sale->customer?->name ?? 'Müşteri'));
        $number = trim((string) ($sale->saleNumber ?? ''));

        return $number !== ''
            ? $customer . ' — ' . $documentLabel . ' ' . $number
            : $customer . ' — ' . $documentLabel;
    }

    /** PDF indirme dosya adı: erkan-ulker-siparis-SAT-2026-00049.pdf */
    public static function downloadFilename(Sale $sale, string $type): string
    {
        return self::customerSlug($sale) . '-' . $type . '-' . self::saleNumberSlug($sale) . '.pdf';
    }

    public static function orderPageTitle(Sale $sale): string
    {
        return self::pageTitle($sale, 'Sipariş Fişi');
    }

    public static function shipmentPageTitle(Sale $sale): string
    {
        return self::pageTitle($sale, 'Sevkiyat Fişi');
    }

    public static function workshopPageTitle(Sale $sale, string $variant): string
    {
        $label = $variant === 'koltuk' ? 'Koltuk Atölye Fişi' : 'Mobilya Atölye Fişi';

        return self::pageTitle($sale, $label);
    }

    public static function workshopDownloadType(string $variant): string
    {
        return $variant === 'koltuk' ? self::TYPE_WORKSHOP_KOLTUK : self::TYPE_WORKSHOP_MOBILYA;
    }
}
