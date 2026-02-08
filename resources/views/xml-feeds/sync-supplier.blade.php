@extends('layouts.app')
@section('title', 'Ürün Çek')
@section('content')
<div class="mb-6">
    <a href="{{ route('xml-feeds.index') }}" class="text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        XML Feeds
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <h1 class="text-xl font-bold text-slate-900 mb-2">Ürünleri Çek</h1>
    <p class="text-slate-600 text-sm mb-6">"{{ $xmlFeed->name }}" feed'inden ürünler çekilecek. Feed'deki tedarikçiler sistemde yoksa otomatik oluşturulur, varsa mevcut tedarikçiye bağlanır.</p>

    @if(session('error'))
    <p class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('xml-feeds.sync', $xmlFeed) }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Varsayılan tedarikçi (opsiyonel)</label>
                <p class="text-xs text-slate-500 mb-2">İsterseniz feed’e önceden bir tedarikçi atayabilirsiniz. Boş bırakırsanız tedarikçiler XML’den otomatik belirlenir.</p>
                <select name="supplierId" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">-- Boş bırak (XML'den otomatik) --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplierId') == $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->code ? ' (' . $s->code . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-start gap-3">
                <input type="checkbox" name="create_suppliers" id="create_suppliers" value="1" {{ old('create_suppliers', $xmlFeed->createSuppliers ?? true) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-green-600 focus:ring-green-500">
                <label for="create_suppliers" class="text-sm text-slate-700">Olmayan tedarikçiler tedarikçilere kaydedilsin mi? (Sistemde bulunmayan tedarikçiler otomatik eklenir)</label>
            </div>
            <div class="flex items-start gap-3">
                <input type="checkbox" name="run_in_background" id="run_in_background" value="1" {{ old('run_in_background', true) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-green-600 focus:ring-green-500">
                <label for="run_in_background" class="text-sm text-slate-700">Arka planda çalıştır (sayfa donmaz, önerilen)</label>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Ürünleri Çek</button>
            <a href="{{ route('xml-feeds.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
