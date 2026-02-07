@extends('layouts.app')
@section('title', 'Düzenle: ' . $product->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('products.index') }}" class="hover:text-slate-700">Ürünler</a>
        <span>/</span>
        <a href="{{ route('products.show', $product) }}" class="hover:text-slate-700">{{ $product->name }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Ürün Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $product->name }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Ürün Adı *</label>
            <input type="text" name="name" required value="{{ old('name', $product->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">SKU / Barkod</label>
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-input">
            @error('sku')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Birim Fiyat (₺) *</label>
                <input type="number" step="0.01" min="0" name="unitPrice" required value="{{ old('unitPrice', $product->unitPrice) }}" class="form-input">
                @error('unitPrice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">KDV Oranı (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="kdvRate" value="{{ old('kdvRate', $product->kdvRate ?? 18) }}" class="form-input">
                @error('kdvRate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tedarikçi</label>
                <select name="supplierId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplierId', $product->supplierId) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Min. Stok Seviyesi</label>
                <input type="number" min="0" name="minStockLevel" value="{{ old('minStockLevel', $product->minStockLevel ?? 0) }}" class="form-input">
                @error('minStockLevel')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Açıklama</label>
            <textarea name="description" class="form-input form-textarea">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $product->isActive ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('products.show', $product) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
