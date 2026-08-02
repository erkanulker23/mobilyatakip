<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImages
{
    private const MIN_BYTES = 1024;
    private const MIN_WIDTH = 32;
    private const MIN_HEIGHT = 32;

    /** @return string[] */
    public static function paths(Product $product): array
    {
        $images = $product->images;
        if (is_string($images) && $images !== '') {
            return [$images];
        }

        return is_array($images) ? array_values($images) : [];
    }

    /** @return string[] */
    public static function validPaths(Product $product): array
    {
        return array_values(array_filter(self::paths($product), [self::class, 'isValidStoredPath']));
    }

    /** @return string[] */
    public static function urls(Product $product): array
    {
        return array_values(array_filter(array_map(
            fn (string $path) => storage_url($path),
            self::validPaths($product)
        )));
    }

    public static function primaryUrl(Product $product): ?string
    {
        $urls = self::urls($product);

        return $urls[0] ?? null;
    }

    public static function isValidStoredPath(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return false;
        }

        $relative = ltrim(str_replace('/storage/', '', parse_url($path, PHP_URL_PATH) ?: $path), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return false;
        }

        if (! Storage::disk('public')->exists($relative)) {
            return false;
        }

        $fullPath = Storage::disk('public')->path($relative);
        $bytes = @filesize($fullPath);
        if ($bytes === false || $bytes < self::MIN_BYTES) {
            return false;
        }

        $info = @getimagesize($fullPath);
        if ($info === false) {
            return false;
        }

        return ($info[0] ?? 0) >= self::MIN_WIDTH && ($info[1] ?? 0) >= self::MIN_HEIGHT;
    }

    public static function isValidUpload(UploadedFile $file): bool
    {
        if (! $file->isValid()) {
            return false;
        }

        if ($file->getSize() < self::MIN_BYTES) {
            return false;
        }

        $info = @getimagesize($file->getPathname());
        if ($info === false) {
            return false;
        }

        return ($info[0] ?? 0) >= self::MIN_WIDTH && ($info[1] ?? 0) >= self::MIN_HEIGHT;
    }

    /** @param  string[]  $paths */
    public static function pruneInvalidPaths(array $paths): array
    {
        return array_values(array_filter($paths, [self::class, 'isValidStoredPath']));
    }
}
