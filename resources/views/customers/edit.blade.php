@extends('layouts.app')
@section('title', 'Düzenle: ' . $customer->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('customers.index') }}" class="hover:text-slate-700">Müşteriler</a>
        <span>/</span>
        <a href="{{ route('customers.show', $customer) }}" class="hover:text-slate-700">{{ $customer->name }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Müşteri Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $customer->name }}</p>
    @if($customer->sales->count() > 0 || $customer->quotes->count() > 0)
    <p class="text-sm text-slate-500 mt-2">
        <a href="{{ route('customers.show', $customer) }}" class="text-green-600 hover:text-green-700 font-medium">→ Siparişler, teklifler ve borç detayı için tıklayın</a>
        ({{ $customer->sales->count() }} satış, {{ $customer->quotes->count() }} teklif)
    </p>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="name" required value="{{ old('name', $customer->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-input form-textarea">{{ old('address', $customer->address) }}</textarea>
            @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">TC Kimlik No</label>
                <input type="text" name="identityNumber" value="{{ old('identityNumber', $customer->identityNumber) }}" class="form-input" maxlength="11" pattern="[0-9]*">
                @error('identityNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Vergi No</label>
                <input type="text" name="taxNumber" value="{{ old('taxNumber', $customer->taxNumber) }}" class="form-input">
                @error('taxNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Vergi Dairesi</label>
            <input type="text" name="taxOffice" value="{{ old('taxOffice', $customer->taxOffice) }}" class="form-input">
            @error('taxOffice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $customer->isActive) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
