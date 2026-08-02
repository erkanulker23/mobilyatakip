<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class StorageUrl
{
    /** Yerel storage yolu veya tam URL'yi tarayıcıda açılabilir URL'ye çevirir. */
    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $relative = ltrim(str_replace('/storage/', '', parse_url($path, PHP_URL_PATH) ?: $path), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        if (! Route::has('storage.file')) {
            return asset('/storage/' . $relative);
        }

        return route('storage.file', ['path' => $relative]);
    }
}
