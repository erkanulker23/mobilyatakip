<?php

namespace App\Support;

use App\Models\User;
use DateTimeInterface;

class UserGreeting
{
    public static function message(?User $user = null, ?DateTimeInterface $at = null): string
    {
        $user = $user ?? auth()->user();
        $name = trim((string) ($user?->name ?? '')) ?: 'Kullanıcı';
        $hour = (int) ($at ?? now())->format('G');

        $prefix = match (true) {
            $hour >= 5 && $hour < 12 => 'Günaydın',
            $hour >= 12 && $hour < 18 => 'İyi günler',
            $hour >= 18 && $hour < 22 => 'İyi akşamlar',
            default => 'İyi geceler',
        };

        return $prefix . ' ' . $name;
    }
}
