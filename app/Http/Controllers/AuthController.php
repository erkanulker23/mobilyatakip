<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Support\WorkshopUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (! $request->session()->isStarted()) {
            $request->session()->start();
        }

        $company = Company::first();

        return view('auth.login', compact('company'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $hash = $user?->getAuthPassword() ?? '';
        if (! $user || $hash === '' || ! password_verify($credentials['password'], $hash)) {
            throw ValidationException::withMessages([
                'email' => ['E-posta veya şifre hatalı.'],
            ]);
        }
        if (!$user->isActive) {
            throw ValidationException::withMessages([
                'email' => ['Hesap devre dışı.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isWorkshop() && ! $user->isAdmin()) {
            return redirect(WorkshopUser::homeUrl($user));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
