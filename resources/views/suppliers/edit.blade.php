@extends('layouts.app')
@section('title', 'Düzenle: ' . $supplier->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('suppliers.index') }}" class="hover:text-neutral-900">Tedarikçiler</a>
        <span>/</span>
        <a href="{{ route('suppliers.show', $supplier) }}" class="hover:text-neutral-900">{{ $supplier->name }}</a>
        <span>/</span>
        <span class="text-neutral-700">Düzenle</span>
    </div>
    <h1 class="page-title">Tedarikçi Düzenle</h1>
    <p class="page-desc">{{ $supplier->name }}</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Kod</label>
            <input type="text" name="code" value="{{ old('code', $supplier->code) }}" class="form-input" placeholder="Örn: TED-001" maxlength="50">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Firma / Ad *</label>
            <input type="text" name="name" required value="{{ old('name', $supplier->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        @php($addressIds = \App\Support\AddressFormat::fieldIds($supplier))
        @include('partials.address-fields', [
            'address' => old('address', $supplier->address),
            'cityId' => $addressIds['cityId'],
            'districtId' => $addressIds['districtId'],
        ])
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Vergi No</label>
                <input type="text" name="taxNumber" value="{{ old('taxNumber', $supplier->taxNumber) }}" class="form-input">
                @error('taxNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Vergi Dairesi</label>
                <input type="text" name="taxOffice" value="{{ old('taxOffice', $supplier->taxOffice) }}" class="form-input">
                @error('taxOffice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Marj (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="marginPercent" value="{{ old('marginPercent', $supplier->marginPercent) }}" class="form-input" placeholder="Örn: 60">
            <p class="mt-1 text-xs text-neutral-500">Ürün satış fiyatı hesaplamasında kullanılır: Net Alış × 100 ÷ Marj</p>
            @error('marginPercent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $supplier->isActive) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('suppliers.show', $supplier) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
