<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function __construct(private MailConfigService $mailConfig) {}

    public function showForgotForm()
    {
        return view('auth.forgot-password', [
            'company' => Company::first(),
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return $this->dispatchResetLink($request->input('email'), $request);
    }

    public function sendResetLinkFromProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $this->dispatchResetLink($user->email, $request, route('profile.edit'));
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
            'company' => Company::first(),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
        }

        throw ValidationException::withMessages([
            'email' => [$this->resetStatusMessage($status)],
        ]);
    }

    private function dispatchResetLink(string $email, Request $request, ?string $redirectRoute = null)
    {
        if (! $this->mailConfig->isConfigured()) {
            return redirect($redirectRoute ?? back())
                ->with('error', 'E-posta ayarları yapılandırılmamış. Yönetici, Ayarlar bölümünden SMTP bilgilerini girmelidir.');
        }

        $this->mailConfig->apply();

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            $message = 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi. Gelen kutunuzu ve spam klasörünü kontrol edin.';

            if ($redirectRoute) {
                return redirect($redirectRoute)->with('success', $message);
            }

            return back()->with('success', $message);
        }

        throw ValidationException::withMessages([
            'email' => [$this->resetStatusMessage($status)],
        ]);
    }

    private function resetStatusMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Bu e-posta adresiyle kayıtlı aktif kullanıcı bulunamadı.',
            Password::INVALID_TOKEN => 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş. Lütfen yeni bağlantı isteyin.',
            Password::RESET_THROTTLED => 'Çok sık deneme yaptınız. Lütfen bir süre sonra tekrar deneyin.',
            default => 'Şifre sıfırlama işlemi tamamlanamadı. Lütfen tekrar deneyin.',
        };
    }
}
