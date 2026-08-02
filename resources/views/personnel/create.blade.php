@extends('layouts.app')
@section('title', 'Yeni Personel')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('personnel.index') }}" class="hover:text-neutral-900">Personel</a>
        <span>/</span>
        <span class="text-neutral-700">Yeni Personel</span>
    </div>
    <h1 class="page-title">Yeni Personel</h1>
    <p class="page-desc">Yeni personel bilgilerini girin</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('personnel.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Personel Resmi</label>
            <input type="file" name="photo" accept="image/*" class="form-input py-2" data-compress-image data-max-bytes="921600">
            <p class="mt-1 text-xs text-neutral-500">PNG, JPG, WEBP · max 2MB (büyük dosyalar otomatik küçültülür)</p>
            @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Personel adı soyadı">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">
                    E-posta
                    @if(auth()->user()?->isAdmin())
                    <span class="text-neutral-400 font-normal">(sistem girişi için gerekli)</span>
                    @endif
                </label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Unvan</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-input" placeholder="Örn: Satış Temsilcisi">
                @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Kategori</label>
                <input type="text" name="category" value="{{ old('category') }}" class="form-input" placeholder="Örn: Satış, Teknik">
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        @include('partials.personnel-system-access-fields', ['personnel' => $personnel])

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('personnel.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
