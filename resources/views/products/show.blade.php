@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::product($product))
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-neutral-900">Ürünler</a>
                <span>/</span>
                <span class="text-neutral-700">{{ $product->name }}</span>
            </div>
            <h1 class="page-title">{{ $product->name }}</h1>
            <p class="page-desc">Ürün detayları ve stok bilgisi</p>
        </div>
        @include('partials.action-buttons', [
            'edit' => route('products.edit', $product),
        ])
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="card p-6">
            @php $imageUrls = \App\Support\ProductImages::urls($product); @endphp
            @if(count($imageUrls) > 0)
            <div class="mb-5" x-data="{ active: 0, images: @json($imageUrls) }">
                <div class="aspect-square rounded-xl border border-neutral-200 dark:border-slate-700 bg-neutral-50 dark:bg-slate-800/50 overflow-hidden">
                    <img :src="images[active]" :alt="@json($product->name)" class="w-full h-full object-contain p-2">
                </div>
                @if(count($imageUrls) > 1)
                <div class="flex gap-2 mt-3 overflow-x-auto pb-1">
                    @foreach($imageUrls as $index => $url)
                    <button type="button"
                        @click="active = {{ $index }}"
                        :class="active === {{ $index }} ? 'ring-2 ring-emerald-500 ring-offset-2 dark:ring-offset-slate-900' : 'opacity-70 hover:opacity-100'"
                        class="shrink-0 rounded-lg overflow-hidden border border-neutral-200 dark:border-slate-700 transition">
                        <img src="{{ $url }}" alt="" class="w-16 h-16 object-cover">
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Ürün Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-neutral-500">SKU</dt><dd class="font-medium font-mono">{{ $product->sku ?: '-' }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Birim Fiyat</dt><dd class="font-medium text-green-700">{{ money($product->unitPrice, 2) }} ₺</dd></div>
                <div><dt class="text-sm text-neutral-500">KDV Oranı</dt><dd class="font-medium">%{{ number_format($product->kdvRate ?? 18, 2) }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Tedarikçi</dt><dd class="font-medium">@if($product->supplier)<a href="{{ route('suppliers.show', $product->supplier) }}" class="text-green-600 hover:text-green-700">{{ $product->supplier->name }}</a>@else—@endif</dd></div>
                <div><dt class="text-sm text-neutral-500">Min. Stok</dt><dd class="font-medium">{{ $product->minStockLevel ?? 0 }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Durum</dt><dd><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $product->isActive ?? true ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ $product->isActive ?? true ? 'Aktif' : 'Pasif' }}</span></dd></div>
                @if($product->description)
                <div><dt class="text-sm text-neutral-500">Açıklama</dt><dd class="text-slate-600">{{ $product->description }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
    <div class="lg:col-span-2 space-y-6">
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-semibold text-slate-900">Depo Stokları</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="table-th">Depo</th>
                            <th class="table-th text-right">Miktar</th>
                            <th class="table-th text-right">Rezerve</th>
                            <th class="table-th text-right">Müsait</th>
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
                        <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Depo stok kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
