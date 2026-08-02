<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password
                            {email=erkanulker0@gmail.com : Kullanıcı e-postası}
                            {--password=password : Yeni şifre}';

    protected $description = 'Belirtilen kullanıcının şifresini sıfırlar (Forge / acil giriş için)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Kullanıcı bulunamadı: {$email}");

            return self::FAILURE;
        }

        $user->name = $user->name ?: 'Süper Admin';
        $user->role = 'admin';
        $user->isActive = true;
        $user->password = (string) $this->option('password');
        $user->save();

        $this->info("Şifre güncellendi: {$email}");

        return self::SUCCESS;
    }
}
