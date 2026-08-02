<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey(), $user->getKeyName())],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ];

        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules, [
            'current_password.current_password' => 'Mevcut şifre hatalı.',
            'photo.image' => 'Profil resmi geçerli bir görsel dosyası olmalıdır.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->hasFile('photo') && Schema::hasColumn('users', 'photoUrl')) {
            $this->removePhotoFile($user);
            $user->photoUrl = '/storage/' . $request->file('photo')->store('users/avatars', 'public');
        }

        if (! empty($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profiliniz güncellendi.');
    }

    public function deletePhoto()
    {
        /** @var User $user */
        $user = Auth::user();

        $this->removePhotoFile($user);

        if (Schema::hasColumn('users', 'photoUrl')) {
            $user->photoUrl = null;
            $user->save();
        }

        return redirect()->route('profile.edit')->with('success', 'Profil resminiz silindi.');
    }

    private function removePhotoFile(User $user): void
    {
        if (! $user->photoUrl) {
            return;
        }

        $path = ltrim(str_replace('/storage/', '', parse_url($user->photoUrl, PHP_URL_PATH) ?: ''), '/');
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
