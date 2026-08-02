@php
    $linkedUser = $personnel->user ?? null;
    $canAccess = (bool) old('canAccessSystem', $linkedUser?->isActive ?? false);
    $systemRole = old('systemRole', $linkedUser?->role ?? 'staff');
    $isAdmin = auth()->user()?->isAdmin();
@endphp

<div class="pt-5 border-t border-neutral-100 dark:border-slate-700">
    <div class="rounded-xl border border-neutral-200 dark:border-slate-700 bg-neutral-50/80 dark:bg-slate-800/40 p-5 space-y-4">
        <div>
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Sistem girişi</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400 leading-relaxed">
                Personelin uygulamaya e-posta ve şifre ile giriş yapabilmesi için aşağıdaki ayarları kullanın.
                Giriş adresi, yukarıdaki <strong>e-posta</strong> alanıdır — sistem erişimi açılmadan önce e-posta girilmelidir.
            </p>
        </div>

        @if($isAdmin)
        <div x-data="{ systemAccess: {{ $canAccess ? 'true' : 'false' }} }" class="space-y-4">
            <div class="flex items-start gap-3 p-3 rounded-lg bg-white dark:bg-slate-900/50 border border-neutral-100 dark:border-slate-700">
                <input type="hidden" name="canAccessSystem" value="0">
                <input type="checkbox" id="canAccessSystem" name="canAccessSystem" value="1" x-model="systemAccess"
                    {{ $canAccess ? 'checked' : '' }}
                    class="mt-0.5 rounded border-slate-300 text-green-600 focus:ring-green-500">
                <div>
                    <label for="canAccessSystem" class="text-sm font-medium text-neutral-900 dark:text-white cursor-pointer">Sisteme giriş yapabilir</label>
                    <p class="text-xs text-neutral-500 dark:text-slate-400 mt-0.5">İşaretlenirse bu personel için kullanıcı hesabı oluşturulur veya yeniden etkinleştirilir.</p>
                </div>
            </div>

            <div x-show="systemAccess" x-cloak class="space-y-4 pt-1">
                <div>
                    <label class="form-label">Sistem yetkisi</label>
                    <select name="systemRole" class="form-select max-w-md">
                        <option value="staff" {{ $systemRole === 'staff' ? 'selected' : '' }}>Personel — standart erişim</option>
                        <option value="admin" {{ $systemRole === 'admin' ? 'selected' : '' }}>Yönetici — tüm yetkiler</option>
                    </select>
                    @error('systemRole')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">
                            {{ $linkedUser ? 'Yeni şifre' : 'Giriş şifresi' }}
                            @if(! $linkedUser)<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="password" name="password" class="form-input" autocomplete="new-password" placeholder="{{ $linkedUser ? 'Değiştirmek için yazın' : 'En az 8 karakter' }}">
                        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">
                            Şifre tekrar
                            @if(! $linkedUser)
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password" placeholder="Şifreyi tekrar girin">
                    </div>
                </div>

                @if($linkedUser)
                <p class="text-xs text-neutral-500 dark:text-slate-400">Şifre alanını boş bırakırsanız mevcut şifre korunur.</p>
                @else
                <p class="text-xs text-amber-700 dark:text-amber-400">İlk giriş için şifre zorunludur. Personel, tanımladığınız e-posta ve bu şifre ile giriş yapar.</p>
                @endif

                @if($linkedUser?->isActive)
                <div class="flex items-center gap-2 text-xs text-emerald-700 dark:text-emerald-400">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Aktif hesap: {{ $linkedUser->email }} · {{ $linkedUser->role === 'admin' ? 'Yönetici' : 'Personel' }}
                </div>
                @elseif($linkedUser)
                <p class="text-xs text-amber-700 dark:text-amber-400">Bağlı hesap devre dışı. «Sisteme giriş yapabilir» işaretleyip kaydedince tekrar giriş yapabilir.</p>
                @endif
            </div>

            <div x-show="!systemAccess" x-cloak class="text-xs text-neutral-500 dark:text-slate-400">
                @if($linkedUser)
                Sistem girişi kapalı. Kaydettiğinizde bağlı kullanıcı hesabı devre dışı bırakılır.
                @else
                Personel şu an yalnızca satış/teklif kayıtlarında isim olarak kullanılır; uygulamaya giriş yapamaz.
                @endif
            </div>
        </div>
        @else
        <div class="p-3 rounded-lg bg-white dark:bg-slate-900/50 border border-neutral-100 dark:border-slate-700 text-sm">
            @if($personnel->exists && $personnel->hasSystemAccess())
                <p class="font-medium text-emerald-700 dark:text-emerald-400">Giriş açık</p>
                <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">{{ $personnel->user?->email }} · {{ $personnel->user?->role === 'admin' ? 'Yönetici' : 'Personel' }}</p>
            @elseif($linkedUser)
                <p class="font-medium text-neutral-700 dark:text-slate-300">Hesap var, giriş kapalı</p>
            @else
                <p class="font-medium text-neutral-700 dark:text-slate-300">Sistem kullanıcısı değil</p>
            @endif
            <p class="text-xs text-neutral-500 dark:text-slate-400 mt-2">Şifre ve giriş ayarlarını yalnızca yönetici hesabı değiştirebilir.</p>
        </div>
        @endif
    </div>
</div>
