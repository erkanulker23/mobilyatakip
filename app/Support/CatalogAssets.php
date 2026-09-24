<?php

namespace App\Support;

class CatalogAssets
{
    /**
     * @param  array<string, mixed>  $product
     */
    public static function productImageUrl(string $manufacturer, string $category, array $product, string $viewMode = 'dekor'): ?string
    {
        $relative = null;
        if ($viewMode === 'ic-mekan' && ! empty($product['image_interior'])) {
            $relative = (string) $product['image_interior'];
        } elseif (! empty($product['image'])) {
            $relative = (string) $product['image'];
        } elseif (! empty($product['image_interior'])) {
            $relative = (string) $product['image_interior'];
        }

        return self::resolvePublicUrl($manufacturer, $category, $relative);
    }

    public static function resolvePublicUrl(string $manufacturer, string $category, ?string $relative): ?string
    {
        if ($relative === null || $relative === '') {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $diskBase = public_path('catalog/'.$manufacturer.'/'.$category);

        $full = $diskBase.'/'.$relative;
        if (is_file($full)) {
            return asset('catalog/'.$manufacturer.'/'.$category.'/'.$relative);
        }

        $dir = dirname($relative);
        $filename = pathinfo($relative, PATHINFO_FILENAME);
        if ($filename === '' || $filename === '.') {
            return null;
        }

        $subdir = $dir !== '.' ? $dir.'/' : '';
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $try = $diskBase.'/'.$subdir.$filename.'.'.$ext;
            if (is_file($try)) {
                return asset('catalog/'.$manufacturer.'/'.$category.'/'.$subdir.$filename.'.'.$ext);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return 'acik'|'koyu'|'notr'
     */
    public static function productTone(array $product): string
    {
        $explicit = $product['tone'] ?? null;
        if (is_string($explicit) && in_array($explicit, ['acik', 'koyu', 'notr'], true)) {
            return $explicit;
        }

        $name = mb_strtolower((string) ($product['name'] ?? ''));

        $dark = [
            'koyu', 'antrasit', 'siyah', 'wenge', 'espresso', 'füme', 'fume', 'grafit',
            'obsidyen', 'kül gri', 'kul gri', 'deep', 'bronz', 'kahve', 'ceviz', 'koyu gri',
            'koyu sarı', 'koyu bej', 'mocha', 'tabacco', 'antr', 'gece', 'kakao',
        ];
        $light = [
            'beyaz', 'açık', 'acik', 'krem', 'bej', 'kutup', 'kutup', 'lake beyaz', 'süper beyaz',
            'super beyaz', 'porselen', 'kumtaşı', 'kumtasi', 'aytaşı', 'aytasi', 'ivory', 'kırık beyaz',
            'kirik beyaz', 'buz gri', 'opak beyaz', 'lake', 'sedef', 'latte', 'kaşmir', 'kasmir',
            'açık gri', 'acik gri', 'açık bej', 'acik bej', 'krem', 'vanilya', 'kanvas krem',
        ];

        foreach ($dark as $needle) {
            if (str_contains($name, $needle)) {
                return 'koyu';
            }
        }

        foreach ($light as $needle) {
            if (str_contains($name, $needle)) {
                return 'acik';
            }
        }

        return 'notr';
    }

    /**
     * Aynı dekor kodu farklı panel ürün sayfalarından tekrar içe aktarılmış olabilir.
     * Görsel katalogda (kod + marka) başına tek kart gösterilir.
     *
     * @param  list<array<string, mixed>>  $products
     * @return list<array<string, mixed>>
     */
    public static function dedupeProducts(array $products): array
    {
        $best = [];

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            $code = trim((string) ($product['code'] ?? ''));
            $brand = trim((string) ($product['brand'] ?? ''));
            $key = $code !== ''
                ? mb_strtolower($code).'|'.mb_strtolower($brand)
                : 'row:'.sha1(json_encode($product, JSON_UNESCAPED_UNICODE));

            if (! isset($best[$key])) {
                $best[$key] = $product;

                continue;
            }

            if (self::productDedupeScore($product) > self::productDedupeScore($best[$key])) {
                $best[$key] = $product;
            }
        }

        $out = array_values($best);
        usort($out, fn (array $a, array $b) => (int) ($b['order'] ?? 0) <=> (int) ($a['order'] ?? 0));

        return $out;
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private static function productDedupeScore(array $product): int
    {
        $score = (int) ($product['order'] ?? 0);
        if (! empty($product['image_interior'])) {
            $score += 1_000_000;
        }
        if (! empty($product['image'])) {
            $score += 100_000;
        }

        return $score;
    }
}
