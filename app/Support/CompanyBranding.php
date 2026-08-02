<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Str;

class CompanyBranding
{
    private const GENERIC_NAMES = [
        'mobilya takip',
        'mobilyatakip',
    ];

    public static function siteName(?Company $company = null): string
    {
        $company ??= Company::first();

        $appName = trim((string) ($company?->appName ?? ''));
        $firmaName = trim((string) ($company?->name ?? ''));
        $metaTitle = trim((string) ($company?->metaTitle ?? ''));

        if ($appName !== '' && ! self::isGenericName($appName)) {
            return $appName;
        }

        if ($firmaName !== '') {
            return $firmaName;
        }

        if ($appName !== '') {
            return $appName;
        }

        if ($metaTitle !== '') {
            return $metaTitle;
        }

        $configName = trim((string) config('app.name', ''));

        if ($configName !== '' && ! self::isGenericName($configName)) {
            return $configName;
        }

        return 'Mobilya Takip';
    }

    public static function shareTitle(?Company $company = null): string
    {
        $company ??= Company::first();
        $metaTitle = trim((string) ($company?->metaTitle ?? ''));

        if ($metaTitle !== '') {
            return $metaTitle;
        }

        return self::siteName($company);
    }

    public static function metaDescription(?Company $company = null, ?string $override = null): string
    {
        $override = trim((string) ($override ?? ''));

        if ($override !== '') {
            return self::limit($override);
        }

        $company ??= Company::first();
        $stored = trim((string) ($company?->metaDescription ?? ''));

        if ($stored !== '') {
            return self::limit($stored);
        }

        return self::limit(self::siteName($company) . ' — sipariş, müşteri, stok ve cari yönetim paneli.');
    }

    public static function documentTitle(string $pageTitle = '', ?Company $company = null): string
    {
        $pageTitle = trim($pageTitle);
        $siteName = self::siteName($company);

        if ($pageTitle !== '') {
            return $pageTitle . ' | ' . $siteName;
        }

        return self::shareTitle($company);
    }

    public static function isGenericName(string $name): bool
    {
        return in_array(mb_strtolower(trim($name)), self::GENERIC_NAMES, true);
    }

    private static function limit(string $text, int $max = 160): string
    {
        $text = preg_replace('/\s+/', ' ', trim(strip_tags($text))) ?? '';

        return Str::limit($text, $max);
    }
}
