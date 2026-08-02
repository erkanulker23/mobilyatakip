@extends('layouts.app')
@section('title', 'Kritik Stok')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Kritik Stok</h1>
        <p class="page-desc">Minimum stok seviyesinin altındaki ürünler</p>
    </div>
    <a href="{{ route('stock.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">
        Stok Listesi
    </a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">Ürün</th>
                    <th class="table-th">SKU</th>
                    <th class="table-th">Depo</th>
                    <th class="table-th text-right">Mevcut</th>
                    <th class="table-th text-right">Minimum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($lowStocks as $s)
                <tr class="hover:bg-slate-50">
                    <td class="table-td">
                        <a href="{{ route('products.show', $s->product) }}" class="font-medium text-neutral-900 hover:text-green-600">{{ $s->product?->name }}</a>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $s->product?->sku ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $s->warehouse?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-right font-medium text-amber-600">{{ ($s->quantity ?? 0) - ($s->reservedQuantity ?? 0) }}</td>
                    <td class="px-6 py-4 text-right font-medium">{{ $s->product?->minStockLevel ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-neutral-500">Kritik stok bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
