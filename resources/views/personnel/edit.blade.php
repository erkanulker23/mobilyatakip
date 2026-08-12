@extends('layouts.app')
@section('title', 'Düzenle: ' . $personnel->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('personnel.index') }}" class="hover:text-neutral-900">Personel</a>
        <span>/</span>
        <a href="{{ route('personnel.show', $personnel) }}" class="hover:text-neutral-900">{{ $personnel->name }}</a>
        <span>/</span>
        <span class="text-neutral-700">Düzenle</span>
    </div>
    <h1 class="page-title">Personel Düzenle</h1>
    <p class="page-desc">{{ $personnel->name }}</p>
</div>

<div class="card p-6 max-w-2xl">
    @if($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-1">Kayıt güncellenemedi</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form method="POST" action="{{ route('personnel.update', $personnel) }}" enctype="multipart/form-data" class="space-y-5" novalidate>
        @csrf @method('PUT')
        <div>
            <label class="form-label">Personel Resmi</label>
            <div class="flex items-center gap-4 flex-wrap">
                @if($personnel->photoUrl)
                    <img src="{{ storage_url($personnel->photoUrl) }}" alt="{{ $personnel->name }}" class="h-24 w-24 rounded-full object-cover border border-neutral-200">
                @else
                    <div class="h-24 w-24 rounded-full bg-slate-100 border border-neutral-200 flex items-center justify-center text-2xl font-semibold text-slate-400">
                        {{ mb_strtoupper(mb_substr($personnel->name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-[200px]">
                    <input type="file" name="photo" accept="image/*" class="form-input py-2" data-compress-image data-max-bytes="921600">
                    <p class="mt-1 text-xs text-neutral-500">PNG, JPG, WEBP · max 2MB. Büyük fotoğraflar otomatik küçültülür.</p>
                    @if($personnel->photoUrl)
                    <button type="submit" form="deletePhotoForm" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">
                        Resmi sil
                    </button>
                    @endif
                </div>
            </div>
            @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="name" required value="{{ old('name', $personnel->name) }}" class="form-input">
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
                <input type="email" name="email" value="{{ old('email', $personnel->email) }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="phone" value="{{ old('phone', $personnel->phone) }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
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
                <select name="category" class="form-select">
                    <option value="">— Seçiniz —</option>
                    @foreach($categoryOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('category', $personnel->category) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        @include('partials.branch-select', [
            'branches' => $branches ?? collect(),
            'selectedBranchId' => $personnel->branchId,
            'emptyLabel' => 'Şube seçilmedi',
            'hint' => 'Personelin görev yaptığı şube. Boş bırakılabilir.',
        ])
        <div class="flex items-center gap-2">
            <input type="hidden" name="isActive" value="0">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $personnel->isActive ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>

        @include('partials.personnel-system-access-fields', ['personnel' => $personnel])

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('personnel.show', $personnel) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
    @if($personnel->photoUrl)
    <form id="deletePhotoForm" method="POST" action="{{ route('personnel.delete-photo', $personnel) }}" onsubmit="return confirm('Personel resmini silmek istediğinize emin misiniz?');">
        @csrf @method('DELETE')
    </form>
    @endif
</div>
@endsection
