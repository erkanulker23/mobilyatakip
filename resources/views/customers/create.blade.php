@extends('layouts.app')
@section('title', 'Yeni Müşteri')
@section('content')
<div class="mb-6">
    <x-breadcrumb :items="[
        ['label' => 'Müşteriler', 'url' => route('customers.index')],
        ['label' => 'Yeni Müşteri'],
    ]" />
    <h1 class="page-title">Yeni Müşteri</h1>
    <p class="page-desc">Yeni müşteri bilgilerini girin</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('customers.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Müşteri adı soyadı">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon 1</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon 2</label>
                <input type="tel" name="phone2" value="{{ old('phone2') }}" class="form-input" placeholder="0216 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                @error('phone2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            @include('partials.address-fields', [
                'address' => old('address'),
                'cityId' => old('cityId'),
                'districtId' => old('districtId'),
            ])
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">TC Kimlik No</label>
                <input type="text" name="identityNumber" value="{{ old('identityNumber') }}" class="form-input" placeholder="11 haneli TC kimlik no" inputmode="numeric" maxlength="11" pattern="[0-9]{0,11}" title="Sadece 11 rakam giriniz">
                @error('identityNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Vergi No</label>
                <input type="text" name="taxNumber" value="{{ old('taxNumber') }}" class="form-input" placeholder="10 haneli vergi no">
                @error('taxNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Vergi Dairesi</label>
            <input type="text" name="taxOffice" value="{{ old('taxOffice') }}" class="form-input" placeholder="Örn: Kadıköy VD">
            @error('taxOffice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Kaydet
            </button>
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
