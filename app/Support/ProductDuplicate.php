<?php

namespace App\Support;

use App\Models\Product;

final class ProductDuplicate
{
    public static function findExisting(string $name, ?string $sku = null, ?string $ignoreId = null): ?Product
    {
        $name = trim($name);
        $sku = trim((string) $sku);

        if ($name === '' && $sku === '') {
            return null;
        }

        $query = Product::query();
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->where(function ($q) use ($name, $sku) {
            if ($name !== '') {
                $q->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)]);
            }
            if ($sku !== '') {
                $method = $name !== '' ? 'orWhereRaw' : 'whereRaw';
                $q->{$method}('LOWER(TRIM(sku)) = ?', [mb_strtolower($sku)]);
            }
        })->first();
    }

    public static function message(string $name, ?string $sku = null, ?string $ignoreId = null): ?string
    {
        $existing = self::findExisting($name, $sku, $ignoreId);
        if (! $existing) {
            return null;
        }

        $nameNorm = mb_strtolower(trim($name));
        $skuNorm = mb_strtolower(trim((string) $sku));
        $suffix = ! ($existing->isActive ?? true) ? ' (pasif kayıt)' : '';

        if ($nameNorm !== '' && mb_strtolower(trim((string) $existing->name)) === $nameNorm) {
            return 'Bu isimde bir ürün zaten kayıtlı: ' . $existing->name . $suffix;
        }

        if ($skuNorm !== '' && mb_strtolower(trim((string) $existing->sku)) === $skuNorm) {
            return 'Bu SKU / barkod ile bir ürün zaten kayıtlı: ' . ($existing->sku ?: $existing->name) . $suffix;
        }

        return 'Bu ürün zaten kayıtlı.' . $suffix;
    }
}
