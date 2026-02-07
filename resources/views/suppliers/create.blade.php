@extends('layouts.app')
@section('title', 'Yeni Tedarikçi')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('suppliers.index') }}" class="hover:text-slate-700">Tedarikçiler</a>
        <span>/</span>
        <span class="text-slate-700">Yeni Tedarikçi</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Yeni Tedarikçi</h1>
    <p class="text-slate-600 mt-1">Yeni tedarikçi bilgilerini girin</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Firma / Ad *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Tedarikçi adı">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="ornek@email.com">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="0555 123 45 67">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-input form-textarea" placeholder="Açık adres">{{ old('address') }}</textarea>
            @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Vergi No</label>
                <input type="text" name="taxNumber" value="{{ old('taxNumber') }}" class="form-input" placeholder="10 haneli vergi no">
                @error('taxNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Vergi Dairesi</label>
                <input type="text" name="taxOffice" value="{{ old('taxOffice') }}" class="form-input" placeholder="Örn: Kadıköy VD">
                @error('taxOffice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('suppliers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
