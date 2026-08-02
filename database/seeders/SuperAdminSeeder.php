<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Production-safe: yalnızca admin kullanıcı yoksa oluşturur.
 * Mevcut kullanıcının şifresini ve profil bilgilerini deploy sırasında değiştirmez.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'erkanulker0@gmail.com';
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = new User([
                'email' => $email,
                'name' => 'Süper Admin',
                'role' => 'admin',
                'isActive' => true,
            ]);
            $user->password = 'password';
            $user->save();

            return;
        }

        $passwordHash = Schema::hasColumn('users', 'passwordHash')
            ? ($user->getRawOriginal('passwordHash') ?? null)
            : null;
        $legacyPassword = Schema::hasColumn('users', 'password')
            ? ($user->getRawOriginal('password') ?? null)
            : null;

        if (empty($passwordHash) && empty($legacyPassword)) {
            $user->password = 'password';
            $user->save();
        }
    }
}
