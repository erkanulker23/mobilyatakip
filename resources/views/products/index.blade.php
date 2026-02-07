@extends('layouts.app')
@section('title', 'Ürünler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Ürünler</h1>
        <p class="text-slate-600 mt-1">Ürün listesi, stok ve fiyat bilgisi</p>
    </div>
    <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Ürün
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (ad, SKU, açıklama)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Tedarikçi</label>
            <select name="supplierId" class="form-select">
                <option value="">Tümü</option>
                @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" {{ request('supplierId') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Min Fiyat (₺)</label>
            <input type="number" step="0.01" name="minPrice" value="{{ request('minPrice') }}" placeholder="0" class="form-input">
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Max Fiyat (₺)</label>
            <input type="number" step="0.01" name="maxPrice" value="{{ request('maxPrice') }}" placeholder="0" class="form-input">
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Durum</label>
            <select name="isActive" class="form-select">
                <option value="">Tümü</option>
                <option value="1" {{ request('isActive') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('isActive') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('products.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ürün</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">SKU</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Fiyat</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tedarikçi</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($products as $p)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @php $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null); @endphp
                            @if($img)
                            <img src="{{ Str::startsWith($img, 'http') ? $img : asset($img) }}" alt="{{ $p->name }}" class="w-12 h-12 object-cover rounded-lg border border-slate-200 shrink-0">
                            @else
                            <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            @endif
                            <div>
                                <span class="font-medium text-slate-900">{{ $p->name }}</span>
                                @if($p->externalSource)<span class="ml-1 text-xs text-slate-400" title="XML Feed">●</span>@endif
                                @if(!($p->isActive ?? true))<span class="ml-1 text-xs text-slate-400">(Pasif)</span>@endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600 font-mono text-sm">{{ $p->sku ?? '-' }}</td>
                    <td class="px-6 py-4 text-right font-medium text-slate-900">{{ number_format($p->unitPrice ?? 0, 2, ',', '.') }} ₺</td>
                    <td class="px-6 py-4 text-slate-600">{{ $p->supplier?->name ?? '-' }}</td>
                    <td class="px-6 py-4">
                        @include('partials.action-buttons', [
                            'show' => route('products.show', $p),
                            'edit' => route('products.edit', $p),
                            'destroy' => route('products.destroy', $p),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı. <a href="{{ route('xml-feeds.index') }}" class="text-green-600 hover:underline">XML Feed</a> ile ürün çekebilirsiniz.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $products->links() }}</div>
</div>
@endsection
