@extends('layouts.app')
@section('title', 'Düzenle: ' . $shippingCompany->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('shipping-companies.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Nakliye Firmaları</a>
        <span>/</span>
        <a href="{{ route('shipping-companies.show', $shippingCompany) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ $shippingCompany->name }}</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300">Düzenle</span>
    </div>
    <h1 class="page-title">Nakliye Firması Düzenle</h1>
    <p class="page-desc">{{ $shippingCompany->name }}</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('shipping-companies.update', $shippingCompany) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Firma Adı <span class="text-red-500">*</span></label>
            <input type="text" name="name" required value="{{ old('name', $shippingCompany->name) }}" class="form-input min-h-[44px]">
            @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="phone" value="{{ old('phone', $shippingCompany->phone) }}" class="form-input min-h-[44px]" placeholder="0555 123 45 67" inputmode="tel">
                @error('phone')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $shippingCompany->email) }}" class="form-input min-h-[44px]" placeholder="ornek@email.com" inputmode="email">
                @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-input form-textarea">{{ old('address', $shippingCompany->address) }}</textarea>
            @error('address')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $shippingCompany->isActive) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('shipping-companies.show', $shippingCompany) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
