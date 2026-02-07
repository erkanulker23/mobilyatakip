@extends('layouts.app')
@section('title', 'Düzenle: ' . $personnel->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('personnel.index') }}" class="hover:text-slate-700">Personel</a>
        <span>/</span>
        <a href="{{ route('personnel.show', $personnel) }}" class="hover:text-slate-700">{{ $personnel->name }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Personel Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $personnel->name }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('personnel.update', $personnel) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="name" required value="{{ old('name', $personnel->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $personnel->email) }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="phone" value="{{ old('phone', $personnel->phone) }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Unvan</label>
                <input type="text" name="title" value="{{ old('title', $personnel->title) }}" class="form-input">
                @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Kategori</label>
                <input type="text" name="category" value="{{ old('category', $personnel->category) }}" class="form-input">
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $personnel->isActive ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('personnel.show', $personnel) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
