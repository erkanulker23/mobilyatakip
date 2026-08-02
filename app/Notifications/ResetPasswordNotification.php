<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Şifre Sıfırlama Talebi')
            ->greeting('Merhaba ' . ($notifiable->name ?? ''))
            ->line('Hesabınız için şifre sıfırlama talebi aldık.')
            ->action('Şifremi Sıfırla', $url)
            ->line('Bu bağlantı ' . config('auth.passwords.users.expire', 60) . ' dakika geçerlidir.')
            ->line('Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.');
    }
}
