<?php

namespace App\Support;

class UserTaskColor
{
    public const DEFAULT = 'emerald';

    /** @var array<string, array{label: string, bg: string, border: string, text: string, dot: string, ring: string}> */
    public const PALETTE = [
        'emerald' => [
            'label' => 'Yeşil',
            'bg' => 'bg-emerald-50 dark:bg-emerald-950/40',
            'border' => 'border-emerald-200 dark:border-emerald-800',
            'text' => 'text-emerald-900 dark:text-emerald-200',
            'dot' => 'bg-emerald-500',
            'ring' => 'ring-emerald-400',
        ],
        'blue' => [
            'label' => 'Mavi',
            'bg' => 'bg-blue-50 dark:bg-blue-950/40',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-900 dark:text-blue-200',
            'dot' => 'bg-blue-500',
            'ring' => 'ring-blue-400',
        ],
        'amber' => [
            'label' => 'Sarı',
            'bg' => 'bg-amber-50 dark:bg-amber-950/40',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-900 dark:text-amber-200',
            'dot' => 'bg-amber-500',
            'ring' => 'ring-amber-400',
        ],
        'red' => [
            'label' => 'Kırmızı',
            'bg' => 'bg-red-50 dark:bg-red-950/40',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-900 dark:text-red-200',
            'dot' => 'bg-red-500',
            'ring' => 'ring-red-400',
        ],
        'purple' => [
            'label' => 'Mor',
            'bg' => 'bg-purple-50 dark:bg-purple-950/40',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text' => 'text-purple-900 dark:text-purple-200',
            'dot' => 'bg-purple-500',
            'ring' => 'ring-purple-400',
        ],
        'pink' => [
            'label' => 'Pembe',
            'bg' => 'bg-pink-50 dark:bg-pink-950/40',
            'border' => 'border-pink-200 dark:border-pink-800',
            'text' => 'text-pink-900 dark:text-pink-200',
            'dot' => 'bg-pink-500',
            'ring' => 'ring-pink-400',
        ],
        'orange' => [
            'label' => 'Turuncu',
            'bg' => 'bg-orange-50 dark:bg-orange-950/40',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text' => 'text-orange-900 dark:text-orange-200',
            'dot' => 'bg-orange-500',
            'ring' => 'ring-orange-400',
        ],
        'slate' => [
            'label' => 'Gri',
            'bg' => 'bg-neutral-50 dark:bg-neutral-900/60',
            'border' => 'border-neutral-200 dark:border-neutral-700',
            'text' => 'text-neutral-800 dark:text-neutral-200',
            'dot' => 'bg-neutral-500',
            'ring' => 'ring-neutral-400',
        ],
    ];

    public static function keys(): array
    {
        return array_keys(self::PALETTE);
    }

    public static function isValid(?string $color): bool
    {
        return $color !== null && array_key_exists($color, self::PALETTE);
    }

    public static function normalize(?string $color): string
    {
        return self::isValid($color) ? $color : self::DEFAULT;
    }

    public static function classes(?string $color): array
    {
        return self::PALETTE[self::normalize($color)];
    }
}
