@extends('layouts.app')
@section('title', 'Stok Düzenle')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('stock.index') }}" class="hover:text-neutral-900">Stok</a>
        <span>/</span>
        <span class="text-neutral-700">{{ $stock->product?->name }}</span>
    </div>
    <h1 class="page-title">Stok Düzenle</h1>
    <p class="page-desc">{{ $stock->product?->name }} - {{ $stock->warehouse?->name }}</p>
</div>

<div class="card p-6 max-w-md">
    <form method="POST" action="{{ route('stock.update', $stock) }}" class="space-y-5">
        @csrf @method('PUT')
        <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
        <div>
            <label class="form-label">Ürün</label>
            <p class="font-medium text-neutral-900">{{ $stock->product?->name }} ({{ $stock->product?->sku ?? '-' }})</p>
        </div>
        <div>
            <label class="form-label">Depo</label>
            <p class="font-medium text-neutral-900">{{ $stock->warehouse?->name }}</p>
        </div>
        <div>
            <label class="form-label">Miktar *</label>
            <input type="number" name="quantity" required min="0" value="{{ old('quantity', $stock->quantity ?? 0) }}" class="form-input">
            @error('quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Rezerve Miktar *</label>
            <input type="number" name="reservedQuantity" required min="0" value="{{ old('reservedQuantity', $stock->reservedQuantity ?? 0) }}" class="form-input">
            @error('reservedQuantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('stock.index', ['warehouse_id' => request('warehouse_id')]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
