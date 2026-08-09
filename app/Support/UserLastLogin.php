<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class UserLastLogin
{
    public static function record(User $user, bool $force = false, int $staleMinutes = 15): void
    {
        if (! Schema::hasColumn($user->getTable(), 'lastLoginAt')) {
            return;
        }

        if (! $force && $user->lastLoginAt !== null) {
            $last = $user->lastLoginAt->timezone(config('app.timezone'));
            if ($last->greaterThan(now()->subMinutes($staleMinutes))) {
                return;
            }
        }

        User::whereKey($user->getKey())->update([
            'lastLoginAt' => now(),
        ]);
    }
}
