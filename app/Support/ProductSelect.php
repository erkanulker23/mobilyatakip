<?php

namespace App\Support;

use App\Models\Product;

class ProductSelect
{
    public static function payload(Product $product): array
    {
        $img = is_array($product->images ?? null)
            ? ($product->images[0] ?? null)
            : ($product->images ?? null);

        if ($img && ! str_starts_with($img, 'http')) {
            $img = url($img);
        }

        $price = (float) $product->unitPrice;
        $priceFormatted = number_format($price, 0, ',', '.');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'label' => $product->name . ' · ' . $priceFormatted . ' ₺',
            'sku' => $product->sku,
            'supplier' => $product->supplier?->name,
            'price' => $price,
            'kdv' => (float) ($product->kdvRate ?? 10),
            'image' => $img,
            'searchText' => self::normalizeSearch(trim(implode(' ', array_filter([
                $product->name,
                $product->sku,
                $product->description,
                $product->supplier?->name,
            ])))),
        ];
    }

    public static function normalizeSearch(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        return strtr($value, [
            'ı' => 'i',
            'İ' => 'i',
            'ş' => 's',
            'Ş' => 's',
            'ğ' => 'g',
            'Ğ' => 'g',
            'ü' => 'u',
            'Ü' => 'u',
            'ö' => 'o',
            'Ö' => 'o',
            'ç' => 'c',
            'Ç' => 'c',
        ]);
    }
}
