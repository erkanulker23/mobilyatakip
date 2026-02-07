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
        $user = User::firstOrNew(['email' => 'erkanulker0@gmail.com']);
        $user->name     = 'Süper Admin';
        $user->role     = 'admin';
        $user->isActive = true;
        $user->password = 'password'; // mutator passwordHash'e bcrypt yazar
        $user->save();

        // passwordHash kolonu varsa ve hâlâ boşsa (mutator tetiklenmediyse) doğrudan yaz
        if (Schema::hasColumn('users', 'passwordHash') && empty($user->getRawOriginal('passwordHash'))) {
            $user->forceFill(['passwordHash' => Hash::make('password')])->save();
        }
    }
}
