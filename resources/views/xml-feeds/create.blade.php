@extends('layouts.app')
@section('title', 'Yeni XML Feed')
@section('content')
<div class="mb-6">
    <a href="{{ route('xml-feeds.index') }}" class="text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Geri
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <h1 class="text-xl font-bold text-slate-900 mb-6">Yeni XML Feed Ekle</h1>

    <form method="POST" action="{{ route('xml-feeds.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Feed Adı *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">XML URL *</label>
                <input type="url" name="url" value="{{ old('url') }}" required placeholder="https://..."
                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                @error('url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tedarikçi</label>
                <p class="text-xs text-slate-500 mb-2">Mevcut tedarikçi seçin veya yeni tedarikçi oluşturun. Ürünler bu tedarikçiye bağlanır.</p>
                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="supplier_mode" value="existing" {{ old('supplier_mode', 'existing') === 'existing' ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm">Mevcut tedarikçi</span>
                    </label>
                    <select name="supplierId" id="supplierId" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500 ml-6">
                        <option value="">-- Tedarikçi seçin --</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplierId') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="supplier_mode" value="new" {{ old('supplier_mode') === 'new' ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm">Yeni tedarikçi oluştur</span>
                    </label>
                    <input type="text" name="newSupplierName" id="newSupplierName" value="{{ old('newSupplierName') }}" placeholder="Yeni tedarikçi adı"
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500 ml-6">
                    @error('newSupplierName')<p class="ml-6 mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('xml-feeds.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
