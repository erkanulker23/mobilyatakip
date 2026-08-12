@extends('layouts.app')
@section('title', 'Düzenle: ' . $branch->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('branches.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Şubeler</a>
        <span>/</span>
        <a href="{{ route('branches.show', $branch) }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">{{ $branch->name }}</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-neutral-300">Düzenle</span>
    </div>
    <h1 class="page-title">Şube Düzenle</h1>
    <p class="page-desc">{{ $branch->name }}</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('branches.update', $branch) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Şube Adı *</label>
            <input type="text" name="name" required value="{{ old('name', $branch->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Şube Kodu</label>
            <input type="text" name="code" value="{{ old('code', $branch->code) }}" class="form-input">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-input">
            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @php($addressIds = \App\Support\AddressFormat::fieldIds($branch))
        @include('partials.address-fields', [
            'address' => old('address', $branch->address),
            'cityId' => $addressIds['cityId'],
            'districtId' => $addressIds['districtId'],
        ])
        <label class="flex items-center gap-2">
            <input type="hidden" name="isActive" value="0">
            <input type="checkbox" name="isActive" value="1" class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500" {{ old('isActive', $branch->isActive) ? 'checked' : '' }}>
            <span class="text-sm text-neutral-700 dark:text-neutral-300">Aktif şube</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('branches.show', $branch) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
