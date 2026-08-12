<?php

namespace App\Support;

class UserRole
{
    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::STAFF => 'Personel — standart erişim',
            self::ADMIN => 'Süper Admin — tüm menü ve işlemler',
        ];
    }

    public static function label(?string $role): string
    {
        return match ($role) {
            self::ADMIN => 'Süper Admin',
            self::STAFF => 'Personel',
            default => $role ? ucfirst($role) : '—',
        };
    }

    public static function isAdmin(?string $role): bool
    {
        return $role === self::ADMIN;
    }
}
