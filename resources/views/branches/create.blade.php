@extends('layouts.app')
@section('title', 'Yeni Şube')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('branches.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Şubeler</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-neutral-300">Yeni Şube</span>
    </div>
    <h1 class="page-title">Yeni Şube</h1>
    <p class="page-desc">Satış ve SSH kayıtlarının bağlanacağı şubeyi ekleyin</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('branches.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Şube Adı *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Merkez, Kadıköy, Showroom...">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Şube Kodu</label>
            <input type="text" name="code" value="{{ old('code') }}" class="form-input" placeholder="MRK">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="0212 000 00 00">
            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @include('partials.address-fields', [
            'address' => old('address'),
            'cityId' => old('cityId'),
            'districtId' => old('districtId'),
            'addressPlaceholder' => 'Şube adresi',
        ])
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('branches.index') }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
