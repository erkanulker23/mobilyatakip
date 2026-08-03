<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
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
        if (Schema::hasColumn($user->getTable(), 'lastLoginAt')) {
            $user->update(['lastLoginAt' => now()]);
        }
        $request->session()->regenerate();
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
