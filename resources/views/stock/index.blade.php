@extends('layouts.app')
@section('title', 'Stok')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Stok</h1>
        <p class="text-slate-600 mt-1">Depo stok durumu</p>
    </div>
    <a href="{{ route('stock.low') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        Kritik Stok
    </a>
</div>

<form method="GET" class="mb-6">
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="form-label mb-0">Depo Seç</label>
            <select name="warehouse_id" onchange="this.form.submit()" class="form-select w-48">
                <option value="">-- Depo seçin --</option>
                @foreach($warehouses as $w)
                <option value="{{ $w->id }}" {{ $warehouseId == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        @if($warehouseId)
        <div>
            <label class="form-label mb-0">Tedarikçi</label>
            <select name="supplier_id" onchange="this.form.submit()" class="form-select w-48">
                <option value="">Tümü</option>
                @foreach($suppliers ?? [] as $s)
                <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label mb-0">Ürün ara</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ürün adı, SKU" class="form-input w-48">
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
        @endif
    </div>
</form>

@if($warehouseId)
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ürün</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tedarikçi</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Miktar</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Rezerve</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Müsait</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($stocks as $s)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('products.show', $s->product) }}" class="font-medium text-slate-900 hover:text-green-600">{{ $s->product?->name }}</a>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $s->product?->sku ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">
                        @if($s->product?->supplier)
                        <a href="{{ route('suppliers.show', $s->product->supplier) }}" class="text-green-600 hover:text-green-700">{{ $s->product->supplier->name }}</a>
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right font-medium">{{ $s->quantity ?? 0 }}</td>
                    <td class="px-6 py-4 text-right text-amber-600">{{ $s->reservedQuantity ?? 0 }}</td>
                    <td class="px-6 py-4 text-right font-medium text-green-600">{{ ($s->quantity ?? 0) - ($s->reservedQuantity ?? 0) }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('stock.edit', ['stock' => $s->id]) }}?warehouse_id={{ $warehouseId }}" class="text-slate-600 hover:text-slate-900 font-medium text-sm">Düzenle</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Bu depoda stok kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
    <p class="text-slate-500">Stok listesini görmek için yukarıdan bir depo seçin.</p>
</div>
@endif
@endsection
