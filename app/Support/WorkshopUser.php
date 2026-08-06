<?php

namespace App\Support;

use App\Models\User;

final class WorkshopUser
{
    public static function isWorkshopCategory(?string $category): bool
    {
        if ($category === null || $category === '') {
            return false;
        }

        if ($category === PersonnelCategory::ATOLYE) {
            return true;
        }

        return in_array(mb_strtolower($category, 'UTF-8'), ['atölye', 'atolye'], true);
    }

    public static function homeUrl(User $user): string
    {
        if ($user->personnel) {
            return route('personnel.show', $user->personnel);
        }

        return route('workshop.index');
    }

    /** @return list<string> */
    public static function allowedRoutePrefixes(): array
    {
        return [
            'dashboard',
            'workshop.',
            'reports.upcoming-due',
            'service-tickets.',
            'tasks.',
            'profile.',
            'notifications.',
            'api.user-tasks.',
            'logout',
            'assets.',
            'storage.',
            'company.',
        ];
    }

    /** @return list<string> */
    public static function blockedRoutes(): array
    {
        return [
            'service-tickets.create',
            'service-tickets.store',
            'service-tickets.print',
            'service-tickets.destroy',
        ];
    }

    public static function canAccessRoute(User $user, ?string $routeName, mixed $personnel = null): bool
    {
        if (! $user->isWorkshop() || $user->isAdmin()) {
            return true;
        }

        if ($routeName === null) {
            return false;
        }

        if (in_array($routeName, self::blockedRoutes(), true)) {
            return false;
        }

        if (in_array($routeName, ['sales.workshop.koltuk', 'sales.workshop.mobilya'], true)) {
            return true;
        }

        foreach (self::allowedRoutePrefixes() as $prefix) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                if (str_starts_with($routeName, 'reports.upcoming-due') && str_contains($routeName, 'print')) {
                    return false;
                }

                return true;
            }
        }

        if ($routeName === 'personnel.show') {
            return $personnel
                && $user->personnel
                && (string) $personnel->id === (string) $user->personnel->id;
        }

        return false;
    }
}
