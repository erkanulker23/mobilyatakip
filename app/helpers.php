<?php

use App\Support\Money;

if (! function_exists('money')) {
    function money(float|int|string|null $amount, int $decimals = 0): string
    {
        return Money::format($amount, $decimals);
    }
}

if (! function_exists('money_parse')) {
    function money_parse(?string $value): float
    {
        return Money::parse($value);
    }
}

if (! function_exists('full_address')) {
    function full_address(?object $entity): string
    {
        if (! $entity) {
            return '';
        }

        return \App\Support\AddressFormat::format($entity);
    }
}

if (! function_exists('storage_url')) {
    function storage_url(?string $path): ?string
    {
        return \App\Support\StorageUrl::url($path);
    }
}
