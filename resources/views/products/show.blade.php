@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-slate-700">Ürünler</a>
                <span>/</span>
                <span class="text-slate-700">{{ $product->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $product->name }}</h1>
            <p class="text-slate-600 mt-1">Ürün detayları ve stok bilgisi</p>
        </div>
        @include('partials.action-buttons', [
            'edit' => route('products.edit', $product),
        ])
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            @php $imgs = is_array($product->images ?? null) ? ($product->images ?? []) : ($product->images ? [$product->images] : []); @endphp
            @if(count($imgs) > 0)
            <div class="mb-4">
                <img src="{{ Str::startsWith($imgs[0], 'http') ? $imgs[0] : asset($imgs[0]) }}" alt="{{ $product->name }}" class="w-full max-h-48 object-contain rounded-lg border border-slate-200">
                @if(count($imgs) > 1)
                <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                    @foreach(array_slice($imgs, 1, 4) as $img)
                    <img src="{{ Str::startsWith($img, 'http') ? $img : asset($img) }}" alt="" class="w-16 h-16 object-cover rounded border border-slate-200 shrink-0">
                    @endforeach
                </div>
                @endif
            </div>
            @endif
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Ürün Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">SKU</dt><dd class="font-medium font-mono">{{ $product->sku ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Birim Fiyat</dt><dd class="font-medium text-green-700">{{ number_format($product->unitPrice, 0, ',', '.') }} ₺</dd></div>
                <div><dt class="text-sm text-slate-500">KDV Oranı</dt><dd class="font-medium">%{{ number_format($product->kdvRate ?? 18, 2) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Tedarikçi</dt><dd class="font-medium">@if($product->supplier)<a href="{{ route('suppliers.show', $product->supplier) }}" class="text-green-600 hover:text-green-700">{{ $product->supplier->name }}</a>@else—@endif</dd></div>
                <div><dt class="text-sm text-slate-500">Min. Stok</dt><dd class="font-medium">{{ $product->minStockLevel ?? 0 }}</dd></div>
                <div><dt class="text-sm text-slate-500">Durum</dt><dd><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $product->isActive ?? true ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ $product->isActive ?? true ? 'Aktif' : 'Pasif' }}</span></dd></div>
                @if($product->description)
                <div><dt class="text-sm text-slate-500">Açıklama</dt><dd class="text-slate-600">{{ $product->description }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Depo Stokları</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Depo</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Miktar</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Rezerve</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Müsait</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($product->stocks as $st)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium">{{ $st->warehouse?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-right">{{ $st->quantity ?? 0 }}</td>
                            <td class="px-6 py-4 text-right text-amber-600">{{ $st->reservedQuantity ?? 0 }}</td>
                            <td class="px-6 py-4 text-right font-medium text-green-600">{{ ($st->quantity ?? 0) - ($st->reservedQuantity ?? 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Depo stok kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
