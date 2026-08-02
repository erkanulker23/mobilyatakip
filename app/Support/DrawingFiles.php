<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DrawingFiles
{
    public const MAX_BYTES = 10485760; // 10 MB

    /** @return array<int, array{path: string, name: string}> */
    public static function entries(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $entries = [];
        foreach ($raw as $item) {
            if (is_string($item) && $item !== '') {
                $entries[] = ['path' => $item, 'name' => basename($item)];
                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $path = trim((string) ($item['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $entries[] = [
                'path' => $path,
                'name' => trim((string) ($item['name'] ?? '')) ?: basename($path),
            ];
        }

        return $entries;
    }

    /** @param  array<int, array{path: string, name: string}>  $entries */
    public static function existingEntries(array $entries): array
    {
        return array_values(array_filter($entries, function (array $entry) {
            $relative = self::relativePath($entry['path'] ?? '');

            return $relative !== '' && Storage::disk('public')->exists($relative);
        }));
    }

    public static function url(?string $path): ?string
    {
        return storage_url($path);
    }

    public static function isImage(array $entry): bool
    {
        $name = strtolower($entry['name'] ?? basename($entry['path'] ?? ''));

        return Str::endsWith($name, ['.jpg', '.jpeg', '.png', '.gif', '.webp']);
    }

    public static function isPdf(array $entry): bool
    {
        $name = strtolower($entry['name'] ?? basename($entry['path'] ?? ''));

        return Str::endsWith($name, '.pdf');
    }

    /** @param  array<int, array{path: string, name: string}>  $current */
    public static function syncFromRequest(Request $request, array $current, string $folder): array
    {
        $entries = self::existingEntries($current);
        $remove = $request->input('remove_drawing_files', []);

        if (is_array($remove) && $remove !== []) {
            $entries = array_values(array_filter($entries, function (array $entry) use ($remove) {
                if (in_array($entry['path'], $remove, true)) {
                    self::deleteStoredFile($entry['path']);

                    return false;
                }

                return true;
            }));
        }

        return array_values(array_merge($entries, self::storeUploads($request, $folder)));
    }

    /** @return array<int, array{path: string, name: string}> */
    public static function storeUploads(Request $request, string $folder, string $field = 'drawing_files'): array
    {
        $files = $request->file($field);
        if (! $files) {
            return [];
        }
        if (! is_array($files)) {
            $files = [$files];
        }

        $stored = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! self::isValidUpload($file)) {
                continue;
            }
            $stored[] = [
                'path' => '/storage/' . $file->store($folder . '/' . date('Y-m-d'), 'public'),
                'name' => $file->getClientOriginalName() ?: basename($file->hashName()),
            ];
        }

        return $stored;
    }

    public static function isValidUpload(UploadedFile $file): bool
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
            return false;
        }

        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

        return in_array(strtolower($file->getClientOriginalExtension()), $allowed, true);
    }

    public static function deleteStoredFile(?string $path): void
    {
        if (! $path || Str::startsWith($path, 'http')) {
            return;
        }

        $relative = self::relativePath($path);
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    public static function relativePath(?string $path): string
    {
        if ($path === null || trim($path) === '') {
            return '';
        }

        $relative = ltrim(str_replace('/storage/', '', parse_url($path, PHP_URL_PATH) ?: $path), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return '';
        }

        return $relative;
    }

    public static function validationRules(): array
    {
        return [
            'drawing_files' => 'nullable|array',
            'drawing_files.*' => 'file|mimes:pdf,jpeg,jpg,png,gif,webp|max:10240',
            'remove_drawing_files' => 'nullable|array',
            'remove_drawing_files.*' => 'string',
        ];
    }
}
