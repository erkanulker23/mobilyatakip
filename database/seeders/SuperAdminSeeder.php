<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'erkanulker0@gmail.com';

        $user = User::firstOrNew(['email' => $email]);
        $user->name = 'Süper Admin';
        $user->role = 'admin';
        $user->isActive = true;

        $passwordHash = Schema::hasColumn('users', 'passwordHash')
            ? ($user->exists ? ($user->getRawOriginal('passwordHash') ?? null) : null)
            : null;

        if (! $user->exists || empty($passwordHash)) {
            $user->password = 'password';
        }

        $user->save();

        if (Schema::hasColumn('users', 'passwordHash')) {
            $user->refresh();
            if (empty($user->getRawOriginal('passwordHash'))) {
                $user->forceFill(['passwordHash' => Hash::make('password')])->save();
            }
        }
    }
}
