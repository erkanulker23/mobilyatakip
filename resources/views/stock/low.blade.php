@extends('layouts.app')
@section('title', 'Kritik Stok')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kritik Stok</h1>
        <p class="text-slate-600 mt-1">Minimum stok seviyesinin altındaki ürünler</p>
    </div>
    <a href="{{ route('stock.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">
        Stok Listesi
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ürün</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Depo</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Mevcut</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Minimum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($lowStocks as $s)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('products.show', $s->product) }}" class="font-medium text-slate-900 hover:text-green-600">{{ $s->product?->name }}</a>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $s->product?->sku ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $s->warehouse?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-right font-medium text-amber-600">{{ ($s->quantity ?? 0) - ($s->reservedQuantity ?? 0) }}</td>
                    <td class="px-6 py-4 text-right font-medium">{{ $s->product?->minStockLevel ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Kritik stok bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
