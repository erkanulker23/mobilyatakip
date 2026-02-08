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
                <label class="block text-sm font-medium text-slate-700 mb-1">Tedarikçi (opsiyonel)</label>
                <p class="text-xs text-slate-500 mb-2">İsterseniz mevcut bir tedarikçi seçin. Boş bırakırsanız ürün çekerken feed'deki tedarikçiler otomatik eşleştirilir veya yoksa oluşturulur.</p>
                <select name="supplierId" id="supplierId" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">-- Tedarikçi seçin (boş bırakılabilir) --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplierId') == $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->code ? ' (' . $s->code . ')' : '' }}</option>
                    @endforeach
                </select>
                @error('supplierId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-start gap-3">
                <input type="checkbox" name="create_suppliers" id="create_suppliers" value="1" {{ old('create_suppliers', true) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-green-600 focus:ring-green-500">
                <label for="create_suppliers" class="text-sm text-slate-700">Olmayan tedarikçiler tedarikçilere kaydedilsin mi? (Ürün çekerken sistemde bulunmayan tedarikçiler otomatik eklenir)</label>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('xml-feeds.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
