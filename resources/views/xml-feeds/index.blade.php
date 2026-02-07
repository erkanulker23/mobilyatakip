@extends('layouts.app')
@section('title', 'XML Ürün Çekme')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-slate-900">XML Ürün Çekme</h1>
    <a href="{{ route('xml-feeds.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Feed Ekle
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">URL</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tedarikçi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($feeds as $feed)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $feed->name }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate" title="{{ $feed->url }}">{{ $feed->url }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $feed->supplier?->name ?? '-' }}</td>
                    <td class="px-6 py-4 flex gap-2">
                        <form method="POST" action="{{ route('xml-feeds.sync', $feed) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200">
                                Ürün Çek
                            </button>
                        </form>
                        <form method="POST" action="{{ route('xml-feeds.destroy', $feed) }}" class="inline" onsubmit="return confirm('Silinecek. Ürünleri de silmek ister misiniz?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="deleteProducts" value="0">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200">
                                Sil
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">Henüz XML feed eklenmemiş. "Yeni Feed Ekle" ile başlayın.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 p-4 bg-slate-50 rounded-lg text-sm text-slate-600">
    <strong>Nasıl çalışır?</strong> XML feed URL'si ekleyin (ör. tedarikçinin ürün kataloğu). "Ürün Çek" butonu ile ürünler otomatik olarak sisteme aktarılır. Google Merchant / g:item veya standart product/item formatları desteklenir.
</div>
@endsection
