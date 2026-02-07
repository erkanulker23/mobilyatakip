@extends('layouts.app')
@section('title', 'Yeni Depo')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('warehouses.index') }}" class="hover:text-slate-700">Depolar</a>
        <span>/</span>
        <span class="text-slate-700">Yeni Depo</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Yeni Depo</h1>
    <p class="text-slate-600 mt-1">Yeni depo bilgilerini girin</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('warehouses.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Depo Adı *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Ana Depo">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Depo Kodu *</label>
            <input type="text" name="code" required value="{{ old('code') }}" class="form-input" placeholder="ANA-01">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-input form-textarea" placeholder="Depo adresi">{{ old('address') }}</textarea>
            @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('warehouses.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
