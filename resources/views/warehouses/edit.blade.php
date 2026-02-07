@extends('layouts.app')
@section('title', 'Düzenle: ' . $warehouse->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('warehouses.index') }}" class="hover:text-slate-700">Depolar</a>
        <span>/</span>
        <a href="{{ route('warehouses.show', $warehouse) }}" class="hover:text-slate-700">{{ $warehouse->name }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Depo Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $warehouse->name }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('warehouses.update', $warehouse) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Depo Adı *</label>
            <input type="text" name="name" required value="{{ old('name', $warehouse->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Depo Kodu *</label>
            <input type="text" name="code" required value="{{ old('code', $warehouse->code) }}" class="form-input">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-input form-textarea">{{ old('address', $warehouse->address) }}</textarea>
            @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('warehouses.show', $warehouse) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
