@extends('layouts.app')
@section('title', 'Yeni Ürün')
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
        <span class="text-neutral-700">Yeni Ürün</span>
    </div>
    <h1 class="page-title">Yeni Ürün</h1>
    <p class="page-desc">Yeni ürün bilgilerini girin</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Ürün Resimleri</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-input py-2">
            <p class="mt-1 text-xs text-neutral-500">PNG, JPG, WEBP · max 5MB · birden fazla resim seçebilirsiniz</p>
            @error('images')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Ürün Adı *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Ürün adı">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">SKU / Barkod</label>
            <input type="text" name="sku" value="{{ old('sku') }}" class="form-input" placeholder="Ürün kodu veya barkod">
            @error('sku')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tedarikçi</label>
                <select name="supplierId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" data-margin="{{ $s->marginPercent ?? '' }}" {{ old('supplierId') == $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->marginPercent ? ' (Marj %'.$s->marginPercent.')' : '' }}</option>
                    @endforeach
                </select>
                @error('supplierId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Net Alış Fiyatı (₺)</label>
                <input type="text" inputmode="decimal" name="netPurchasePrice" value="{{ old('netPurchasePrice') !== null && old('netPurchasePrice') !== '' ? money(money_parse(old('netPurchasePrice')), 2) : '' }}" class="form-input money-input" placeholder="0" autocomplete="off" data-money-decimals="2">
                <p class="mt-1 text-xs text-neutral-500">Tedarikçi marjı varsa satış fiyatı otomatik hesaplanır</p>
                @error('netPurchasePrice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Satış Fiyatı (₺) *</label>
                <input type="text" inputmode="decimal" name="unitPrice" required value="{{ old('unitPrice') !== null && old('unitPrice') !== '' ? money(money_parse(old('unitPrice')), 2) : '' }}" class="form-input money-input" placeholder="0" autocomplete="off" data-money-decimals="2">
                @error('unitPrice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">KDV Oranı (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="kdvRate" value="{{ old('kdvRate', 10) }}" class="form-input" placeholder="10">
                @error('kdvRate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Min. Stok Seviyesi</label>
                <input type="number" min="0" name="minStockLevel" value="{{ old('minStockLevel', 0) }}" class="form-input" placeholder="0">
                @error('minStockLevel')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="form-label">Açıklama</label>
            <textarea name="description" class="form-input form-textarea" placeholder="Ürün açıklaması">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
