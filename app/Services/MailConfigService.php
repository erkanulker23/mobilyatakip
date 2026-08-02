<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Ayarlar ekranında kayıtlı SMTP bilgilerini çalışma anında mailer'a uygular.
 * SMTP tanımlı değilse .env'deki varsayılan mailer (log) kullanılmaya devam eder.
 */
class MailConfigService
{
    public function apply(): bool
    {
        $company = Company::first();
        if (!$company || !$company->mailHost) {
            return false;
        }

        $from = $company->mailFrom ?: $company->email;

        Config::set('mail.mailers.smtp.host', $company->mailHost);
        Config::set('mail.mailers.smtp.port', $company->mailPort ?: 587);
        Config::set('mail.mailers.smtp.username', $company->mailUser ?: null);
        Config::set('mail.mailers.smtp.password', $company->mailPassword ?: null);
        Config::set('mail.mailers.smtp.encryption', $company->mailSecure ? 'ssl' : 'tls');
        Config::set('mail.default', 'smtp');
        if ($from) {
            Config::set('mail.from.address', $from);
            Config::set('mail.from.name', $company->name ?: config('app.name'));
        }

        // Mailer'ın önbelleğe alınmış örneğini at ki yeni ayarlar geçerli olsun
        Mail::purge('smtp');

        return true;
    }

    public function isConfigured(): bool
    {
        $company = Company::first();
        return (bool) ($company && $company->mailHost);
    }
}
