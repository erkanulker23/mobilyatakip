@if(auth()->user()?->isAdmin())
@php
    $linkedUser = $personnel->user ?? null;
    $canAccess = (bool) old('canAccessSystem', $linkedUser?->isActive ?? false);
    $systemRole = old('systemRole', $linkedUser?->role ?? 'staff');
@endphp
<div class="pt-5 border-t border-neutral-100 dark:border-slate-700 space-y-4" x-data="{ systemAccess: {{ $canAccess ? 'true' : 'false' }} }">
    <div>
        <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Sistem erişimi</h2>
        <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Personelin uygulamaya giriş yapıp yapamayacağını ve yetkisini belirleyin. Giriş için yukarıdaki e-posta kullanılır.</p>
    </div>

    <div class="flex items-center gap-2">
        <input type="hidden" name="canAccessSystem" value="0">
        <input type="checkbox" id="canAccessSystem" name="canAccessSystem" value="1" x-model="systemAccess"
            {{ $canAccess ? 'checked' : '' }}
            class="rounded border-slate-300 text-green-600 focus:ring-green-500">
        <label for="canAccessSystem" class="form-label mb-0">Sistemi kullanabilir (giriş yapabilir)</label>
    </div>

    <div x-show="systemAccess" x-cloak class="space-y-4 pl-1">
        <div>
            <label class="form-label">Sistem yetkisi</label>
            <select name="systemRole" class="form-select max-w-xs">
                <option value="staff" {{ $systemRole === 'staff' ? 'selected' : '' }}>Personel — standart erişim</option>
                <option value="admin" {{ $systemRole === 'admin' ? 'selected' : '' }}>Yönetici — tüm yetkiler (ayarlar dahil)</option>
            </select>
            @error('systemRole')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">{{ $linkedUser ? 'Yeni şifre' : 'Giriş şifresi' }}{{ $linkedUser ? '' : ' *' }}</label>
                <input type="password" name="password" class="form-input" autocomplete="new-password">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Şifre tekrar</label>
                <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
            </div>
        </div>
        @if($linkedUser)
        <p class="text-xs text-neutral-500 dark:text-slate-400">Şifre alanını boş bırakırsanız mevcut şifre korunur.</p>
        @endif

        @if($linkedUser?->isActive)
        <p class="text-xs text-emerald-700 dark:text-emerald-400">Aktif hesap: {{ $linkedUser->email }} · {{ $linkedUser->role === 'admin' ? 'Yönetici' : 'Personel' }}</p>
        @elseif($linkedUser)
        <p class="text-xs text-amber-700 dark:text-amber-400">Bağlı hesap devre dışı. «Sistemi kullanabilir» işaretleyip kaydedince tekrar giriş yapabilir.</p>
        @endif
    </div>
</div>
@endif
