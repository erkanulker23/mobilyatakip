@extends('layouts.app')
@section('title', 'Yeni Nakliye Firması')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('shipping-companies.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Nakliye Firmaları</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300">Yeni Nakliye Firması</span>
    </div>
    <h1 class="page-title">Yeni Nakliye Firması</h1>
    <p class="page-desc">Nakliye firması bilgilerini girin</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('shipping-companies.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Firma Adı <span class="text-red-500">*</span></label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input min-h-[44px]" placeholder="Nakliye firması adı">
            @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input min-h-[44px]" placeholder="0555 123 45 67" inputmode="tel">
                @error('phone')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input min-h-[44px]" placeholder="ornek@email.com" inputmode="email">
                @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-input form-textarea" placeholder="Açık adres">{{ old('address') }}</textarea>
            @error('address')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('shipping-companies.index') }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
