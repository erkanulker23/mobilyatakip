@extends('layouts.app')
@section('title', $warehouse->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('warehouses.index') }}" class="hover:text-slate-700">Depolar</a>
                <span>/</span>
                <span class="text-slate-700">{{ $warehouse->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $warehouse->name }}</h1>
            <p class="text-slate-600 mt-1">Depo detayları ve stok listesi</p>
        </div>
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Düzenle
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Depo Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">Kod</dt><dd class="font-medium font-mono">{{ $warehouse->code }}</dd></div>
                <div><dt class="text-sm text-slate-500">Adres</dt><dd class="font-medium">{{ $warehouse->address ?: '-' }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Stok Listesi</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $warehouse->stocks->count() }} ürün</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ürün</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">SKU</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Miktar</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Rezerve</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Müsait</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($warehouse->stocks as $st)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('products.show', $st->product) }}" class="font-medium text-green-600 hover:text-green-700">{{ $st->product?->name }}</a>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $st->product?->sku ?? '-' }}</td>
                            <td class="px-6 py-4 text-right">{{ $st->quantity ?? 0 }}</td>
                            <td class="px-6 py-4 text-right text-amber-600">{{ $st->reservedQuantity ?? 0 }}</td>
                            <td class="px-6 py-4 text-right font-medium text-green-600">{{ ($st->quantity ?? 0) - ($st->reservedQuantity ?? 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Bu depoda stok kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
