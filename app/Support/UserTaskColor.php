<?php

namespace App\Support;

class UserTaskColor
{
    public const DEFAULT = 'blue';

    /** Aktif öncelik seçenekleri (form + hızlı seçim). */
    public const PALETTE = [
        'red' => [
            'label' => 'Acil',
            'hint' => 'Hemen yapılmalı',
            'bg' => 'bg-red-50 dark:bg-red-950/40',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-900 dark:text-red-200',
            'dot' => 'bg-red-500',
            'ring' => 'ring-red-400',
        ],
        'amber' => [
            'label' => 'Önemli',
            'hint' => 'Öncelikli takip',
            'bg' => 'bg-amber-50 dark:bg-amber-950/40',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-900 dark:text-amber-200',
            'dot' => 'bg-amber-500',
            'ring' => 'ring-amber-400',
        ],
        'blue' => [
            'label' => 'Normal',
            'hint' => 'Standart görev',
            'bg' => 'bg-blue-50 dark:bg-blue-950/40',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-900 dark:text-blue-200',
            'dot' => 'bg-blue-500',
            'ring' => 'ring-blue-400',
        ],
        'slate' => [
            'label' => 'Düşük',
            'hint' => 'Zaman olduğunda',
            'bg' => 'bg-neutral-50 dark:bg-neutral-900/60',
            'border' => 'border-neutral-200 dark:border-neutral-700',
            'text' => 'text-neutral-800 dark:text-neutral-200',
            'dot' => 'bg-neutral-500',
            'ring' => 'ring-neutral-400',
        ],
    ];

    /** Eski kayıtlar — görüntüleme ve güncelleme uyumluluğu. */
    private const LEGACY_PALETTE = [
        'emerald' => [
            'label' => 'Normal',
            'hint' => 'Standart görev',
            'bg' => 'bg-emerald-50 dark:bg-emerald-950/40',
            'border' => 'border-emerald-200 dark:border-emerald-800',
            'text' => 'text-emerald-900 dark:text-emerald-200',
            'dot' => 'bg-emerald-500',
            'ring' => 'ring-emerald-400',
        ],
        'orange' => [
            'label' => 'Önemli',
            'hint' => 'Öncelikli takip',
            'bg' => 'bg-orange-50 dark:bg-orange-950/40',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text' => 'text-orange-900 dark:text-orange-200',
            'dot' => 'bg-orange-500',
            'ring' => 'ring-orange-400',
        ],
        'purple' => [
            'label' => 'Normal',
            'hint' => 'Standart görev',
            'bg' => 'bg-purple-50 dark:bg-purple-950/40',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text' => 'text-purple-900 dark:text-purple-200',
            'dot' => 'bg-purple-500',
            'ring' => 'ring-purple-400',
        ],
        'pink' => [
            'label' => 'Normal',
            'hint' => 'Standart görev',
            'bg' => 'bg-pink-50 dark:bg-pink-950/40',
            'border' => 'border-pink-200 dark:border-pink-800',
            'text' => 'text-pink-900 dark:text-pink-200',
            'dot' => 'bg-pink-500',
            'ring' => 'ring-pink-400',
        ],
    ];

    public static function keys(): array
    {
        return array_keys(self::PALETTE);
    }

    public static function allowedKeys(): array
    {
        return array_merge(array_keys(self::PALETTE), array_keys(self::LEGACY_PALETTE));
    }

    public static function isValid(?string $color): bool
    {
        return $color !== null && array_key_exists($color, self::PALETTE);
    }

    public static function isAllowed(?string $color): bool
    {
        return $color !== null && (array_key_exists($color, self::PALETTE) || array_key_exists($color, self::LEGACY_PALETTE));
    }

    public static function normalize(?string $color): string
    {
        if ($color !== null && self::isAllowed($color)) {
            return $color;
        }

        return self::DEFAULT;
    }

    public static function classes(?string $color): array
    {
        $key = self::normalize($color);

        return self::PALETTE[$key] ?? self::LEGACY_PALETTE[$key] ?? self::PALETTE[self::DEFAULT];
    }

    public static function label(?string $color): string
    {
        return self::classes($color)['label'];
    }

    /** Tailwind CDN dinamik sınıfları üretmediği için ekran stilleri (data-task-color). */
    public const THEME = [
        'red' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#7f1d1d', 'dot' => '#ef4444', 'bgDark' => 'rgba(69, 10, 10, 0.45)', 'borderDark' => '#991b1b', 'textDark' => '#fecaca'],
        'amber' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#78350f', 'dot' => '#f59e0b', 'bgDark' => 'rgba(69, 26, 3, 0.45)', 'borderDark' => '#92400e', 'textDark' => '#fde68a'],
        'blue' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a', 'dot' => '#3b82f6', 'bgDark' => 'rgba(23, 37, 84, 0.45)', 'borderDark' => '#1e40af', 'textDark' => '#bfdbfe'],
        'slate' => ['bg' => '#fafafa', 'border' => '#e5e5e5', 'text' => '#262626', 'dot' => '#737373', 'bgDark' => 'rgba(23, 23, 23, 0.6)', 'borderDark' => '#404040', 'textDark' => '#e5e5e5'],
        'emerald' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#064e3b', 'dot' => '#10b981', 'bgDark' => 'rgba(6, 78, 59, 0.4)', 'borderDark' => '#065f46', 'textDark' => '#a7f3d0'],
        'orange' => ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12', 'dot' => '#f97316', 'bgDark' => 'rgba(67, 20, 7, 0.45)', 'borderDark' => '#9a3412', 'textDark' => '#fed7aa'],
        'purple' => ['bg' => '#faf5ff', 'border' => '#e9d5ff', 'text' => '#581c87', 'dot' => '#a855f7', 'bgDark' => 'rgba(59, 7, 100, 0.45)', 'borderDark' => '#6b21a8', 'textDark' => '#e9d5ff'],
        'pink' => ['bg' => '#fdf2f8', 'border' => '#fbcfe8', 'text' => '#831843', 'dot' => '#ec4899', 'bgDark' => 'rgba(80, 7, 36, 0.45)', 'borderDark' => '#9d174d', 'textDark' => '#fbcfe8'],
    ];
}
