@extends('layouts.app')
@section('title', 'Düzenle: ' . $product->name)
@push('head')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierSel = document.querySelector('select[name="supplierId"]');
    const netPriceInp = document.querySelector('input[name="netPurchasePrice"]');
    const unitPriceInp = document.querySelector('input[name="unitPrice"]');
    if (!supplierSel || !netPriceInp || !unitPriceInp) return;
    const supplierMargins = @json($suppliers->pluck('marginPercent', 'id'));
    function calcUnitPrice() {
        const supplierId = supplierSel.value;
        const margin = supplierMargins[supplierId];
        const net = (window.parseMoney || parseFloat)(netPriceInp.value) || 0;
        if (!supplierId || !margin || margin <= 0 || net <= 0) return;
        const salePrice = Math.round((net * 100 / margin) * 100) / 100;
        unitPriceInp.value = (window.fmtMoney || String)(salePrice, 2);
        if (window.formatMoneyInput) window.formatMoneyInput(unitPriceInp);
    }
    supplierSel.addEventListener('change', calcUnitPrice);
    netPriceInp.addEventListener('input', calcUnitPrice);
});
</script>
@endpush
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('products.index') }}" class="hover:text-neutral-900">Ürünler</a>
        <span>/</span>
        <a href="{{ route('products.show', $product) }}" class="hover:text-neutral-900">{{ $product->name }}</a>
        <span>/</span>
        <span class="text-neutral-700">Düzenle</span>
    </div>
    <h1 class="page-title">Ürün Düzenle</h1>
    <p class="page-desc">{{ $product->name }}</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')
        @php $existingImages = \App\Support\ProductImages::paths($product); @endphp
        <div>
            <label class="form-label">Ürün Resimleri</label>
            @if(count($existingImages) > 0)
            <div class="flex flex-wrap gap-4 mb-3">
                @foreach($existingImages as $img)
                @php $isValidImage = \App\Support\ProductImages::isValidStoredPath($img); @endphp
                <div class="text-center">
                    @if($isValidImage)
                    <img src="{{ storage_url($img) }}" alt="" class="w-20 h-20 object-cover rounded-lg border border-neutral-200 mx-auto">
                    @else
                    <div class="w-20 h-20 rounded-lg border border-dashed border-amber-300 bg-amber-50 flex items-center justify-center mx-auto">
                        <span class="text-[10px] text-amber-700 px-1">Bozuk</span>
                    </div>
                    @endif
                    <label class="inline-flex items-center gap-1 mt-1.5 text-xs text-red-600 cursor-pointer">
                        <input type="checkbox" name="remove_images[]" value="{{ $img }}" class="rounded border-neutral-300 text-red-600 focus:ring-red-500" {{ $isValidImage ? '' : 'checked' }}>
                        Sil
                    </label>
                </div>
                @endforeach
            </div>
            @else
            <div class="w-20 h-20 rounded-lg bg-slate-100 border border-neutral-200 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            @endif
            <input type="file" name="images[]" multiple accept="image/*" class="form-input py-2">
            <p class="mt-1 text-xs text-neutral-500">PNG, JPG, WEBP · max 5MB · birden fazla resim seçebilirsiniz</p>
            @error('images')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
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
                <label class="form-label">Tedarikçi</label>
                <select name="supplierId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" data-margin="{{ $s->marginPercent ?? '' }}" {{ old('supplierId', $product->supplierId) == $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->marginPercent ? ' (Marj %'.$s->marginPercent.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Net Alış Fiyatı (₺)</label>
                <input type="text" inputmode="decimal" name="netPurchasePrice" value="{{ old('netPurchasePrice') !== null && old('netPurchasePrice') !== '' ? money(money_parse(old('netPurchasePrice')), 2) : ($product->netPurchasePrice ? money($product->netPurchasePrice, 2) : '') }}" class="form-input money-input" placeholder="0" autocomplete="off" data-money-decimals="2">
                <p class="mt-1 text-xs text-neutral-500">Tedarikçi marjı varsa satış fiyatı otomatik hesaplanır</p>
                @error('netPurchasePrice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Satış Fiyatı (₺) *</label>
                <input type="text" inputmode="decimal" name="unitPrice" required value="{{ old('unitPrice') !== null && old('unitPrice') !== '' ? money(money_parse(old('unitPrice')), 2) : money($product->unitPrice, 2) }}" class="form-input money-input" placeholder="0" autocomplete="off" data-money-decimals="2">
                @error('unitPrice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">KDV Oranı (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="kdvRate" value="{{ old('kdvRate', $product->kdvRate ?? 10) }}" class="form-input">
                @error('kdvRate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('products.show', $product) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
