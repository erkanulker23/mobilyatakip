@extends('layouts.app')
@section('title', 'Profil Ayarları')
@section('content')
<div class="mb-6">
    <h1 class="page-title">Profil Ayarları</h1>
    <p class="page-desc">Profil resminizi, adınızı, e-posta adresinizi ve şifrenizi yönetin</p>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
@endif

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6" id="profileUpdateForm">
        @csrf
        @method('PUT')

        <div>
            <label class="form-label">Profil Resmi</label>
            <div class="flex items-center gap-4 flex-wrap">
                @if($user->photoDisplayUrl())
                    <img src="{{ $user->photoDisplayUrl() }}" alt="{{ $user->name }}" class="h-24 w-24 rounded-full object-cover border border-neutral-200 dark:border-slate-600">
                @else
                    <div class="h-24 w-24 rounded-full bg-neutral-900 dark:bg-neutral-700 text-white flex items-center justify-center text-2xl font-semibold shrink-0">
                        {{ $user->initials() }}
                    </div>
                @endif
                <div class="flex-1 min-w-[200px]">
                    <input type="file" name="photo" id="profilePhoto" accept="image/*" class="form-input py-2" data-compress-image data-max-bytes="921600">
                    <p id="profilePhotoHint" class="mt-1 text-xs text-neutral-500 dark:text-slate-400"></p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">PNG, JPG, WEBP · en fazla 2MB. Büyük fotoğraflar otomatik küçültülür.</p>
                    @if($user->photoUrl)
                    <button type="submit" form="deletePhotoForm" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 dark:bg-red-950/40 rounded-lg hover:bg-red-100 dark:hover:bg-red-950/60">
                        Resmi sil
                    </button>
                    @endif
                </div>
            </div>
            @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label" for="profileName">Ad Soyad</label>
            <input type="text" name="name" id="profileName" required value="{{ old('name', $user->name) }}" class="form-input" autocomplete="name">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label" for="profileEmail">E-posta</label>
            <input type="email" name="email" id="profileEmail" required value="{{ old('email', $user->email) }}" class="form-input" autocomplete="email">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4 border-t border-neutral-100 dark:border-slate-700 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Şifre değiştir</h2>
                <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Boş bırakırsanız mevcut şifreniz korunur.</p>
            </div>
            <div>
                <label class="form-label" for="profileCurrentPassword">Mevcut şifre</label>
                <input type="password" name="current_password" id="profileCurrentPassword" class="form-input" autocomplete="current-password">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="profilePassword">Yeni şifre</label>
                    <input type="password" name="password" id="profilePassword" class="form-input" autocomplete="new-password">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="profilePasswordConfirmation">Yeni şifre (tekrar)</label>
                    <input type="password" name="password_confirmation" id="profilePasswordConfirmation" class="form-input" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('dashboard') }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>

<div class="card p-6 max-w-2xl mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Şifremi unuttum</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400 leading-relaxed">
                Mevcut şifrenizi hatırlamıyorsanız <strong>{{ $user->email }}</strong> adresine sıfırlama bağlantısı gönderebiliriz.
            </p>
        </div>
        <form method="POST" action="{{ route('profile.password.email') }}" class="shrink-0" onsubmit="return confirm('Şifre sıfırlama bağlantısı e-posta adresinize gönderilsin mi?');">
            @csrf
            <button type="submit" class="btn-secondary whitespace-nowrap">Sıfırlama bağlantısı gönder</button>
        </form>
    </div>
</div>

<div class="card p-6 max-w-2xl mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Oturumu kapat</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400 leading-relaxed">
                Hesabınızdan güvenli şekilde çıkış yapın.
            </p>
        </div>
        <a href="{{ route('logout') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-[0.625rem] hover:bg-red-100 transition-colors dark:text-red-300 dark:bg-red-950/40 dark:border-red-900/50 dark:hover:bg-red-950/60 whitespace-nowrap shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Çıkış Yap
        </a>
    </div>
</div>

@if($user->photoUrl)
<form id="deletePhotoForm" method="POST" action="{{ route('profile.delete-photo') }}" class="hidden">
    @csrf
</form>
@endif
@endsection
