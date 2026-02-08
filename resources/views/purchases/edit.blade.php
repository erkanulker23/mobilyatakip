@extends('layouts.app')
@section('title', 'Düzenle: ' . $purchase->purchaseNumber)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('purchases.index') }}" class="hover:text-slate-700">Alışlar</a>
        <span>/</span>
        <a href="{{ route('purchases.show', $purchase) }}" class="hover:text-slate-700">{{ $purchase->purchaseNumber }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Alış Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $purchase->purchaseNumber }} @if($purchase->warehouse)<span class="text-slate-500">· Depo: {{ $purchase->warehouse->name }}</span>@endif</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-4xl">
    <form method="POST" action="{{ route('purchases.update', $purchase) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">KDV</label>
            <select name="kdvIncluded" class="form-select">
                <option value="1" {{ old('kdvIncluded', $purchase->kdvIncluded ?? true) ? 'selected' : '' }}>KDV Dahil</option>
                <option value="0" {{ !old('kdvIncluded', $purchase->kdvIncluded ?? true) ? 'selected' : '' }}>KDV Hariç</option>
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tedarikçi *</label>
                <select name="supplierId" required class="form-select">
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplierId', $purchase->supplierId) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Tedarikçi iskonto oranı %</label>
                <input type="number" step="0.01" min="0" max="100" name="supplierDiscountRate" value="{{ old('supplierDiscountRate', $purchase->supplierDiscountRate) }}" class="form-input w-32" placeholder="0">
            </div>
            <div>
                <label class="form-label">Alış Tarihi *</label>
                <input type="date" name="purchaseDate" required value="{{ old('purchaseDate', $purchase->purchaseDate?->format('Y-m-d')) }}" class="form-input">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Vade Tarihi</label>
                <input type="date" name="dueDate" value="{{ old('dueDate', $purchase->dueDate?->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Notlar</label>
                <input type="text" name="notes" value="{{ old('notes', $purchase->notes) }}" class="form-input">
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-600">
            <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-3">Nakliye bilgileri</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="form-label">Nakliye firması</label>
                    <select name="shippingCompanyId" class="form-select">
                        <option value="">Seçiniz</option>
                        @foreach($shippingCompanies ?? [] as $sc)
                        <option value="{{ $sc->id }}" {{ old('shippingCompanyId', $purchase->shippingCompanyId) == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Araç plakası</label>
                    <input type="text" name="vehiclePlate" value="{{ old('vehiclePlate', $purchase->vehiclePlate) }}" class="form-input" placeholder="34 ABC 123">
                </div>
                <div>
                    <label class="form-label">Şoför adı</label>
                    <input type="text" name="driverName" value="{{ old('driverName', $purchase->driverName) }}" class="form-input" placeholder="Ahmet Yılmaz">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Şoför telefonu</label>
                    <input type="tel" name="driverPhone" value="{{ old('driverPhone', $purchase->driverPhone) }}" class="form-input" placeholder="0555 123 45 67">
                </div>
            </div>
        </div>

        <div class="form-items-section-box mt-5">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Alış Kalemleri</h3>
            <div id="items" class="space-y-3">
                @foreach($purchase->items as $idx => $item)
                <div class="item-row form-item-row grid grid-cols-1 md:grid-cols-[1fr_100px_100px_100px_80px_40px] gap-3 items-end">
                    <div>
                        <label class="form-label">Ürün *</label>
                        <select name="items[{{ $idx }}][productId]" required class="form-select item-product">
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}" {{ $item->productId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Liste fiyatı</label><input type="number" step="0.01" min="0" name="items[{{ $idx }}][listPrice]" value="{{ old("items.{$idx}.listPrice", $item->listPrice) }}" class="form-input item-listprice" placeholder="—"></div>
                    <div><label class="form-label">İskontolu fiyat *</label><input type="number" step="0.01" min="0" name="items[{{ $idx }}][unitPrice]" required value="{{ old("items.{$idx}.unitPrice", $item->unitPrice) }}" class="form-input item-price"></div>
                    <div><label class="form-label">Adet *</label><input type="number" name="items[{{ $idx }}][quantity]" value="{{ old("items.{$idx}.quantity", $item->quantity) }}" required min="1" class="form-input item-qty"></div>
                    <div><label class="form-label">KDV %</label><input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][kdvRate]" value="{{ old("items.{$idx}.kdvRate", $item->kdvRate ?? 18) }}" class="form-input item-kdv"></div>
                    <div><button type="button" onclick="removeRow(this)" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">−</button></div>
                </div>
                @endforeach
            </div>
            <button type="button" onclick="addRow()" class="mt-3 inline-flex items-center gap-2 px-3 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 text-sm font-medium">+ Kalem Ekle</button>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('purchases.show', $purchase) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
let idx = {{ $purchase->items->count() }};
function addRow() {
    const t = document.querySelector('.item-row');
    const c = t.cloneNode(true);
    c.querySelectorAll('select, input').forEach(e => {
        if (e.name) e.name = e.name.replace(/\[\d+\]/, '[' + idx + ']');
        if (e.classList.contains('item-listprice')) e.value = '';
        if (e.classList.contains('item-price')) e.value = '';
        if (e.classList.contains('item-qty')) e.value = '1';
        if (e.classList.contains('item-kdv')) e.value = '18';
    });
    c.querySelector('button')?.setAttribute('onclick', 'removeRow(this)');
    document.getElementById('items').appendChild(c);
    idx++;
}
function removeRow(btn) {
    if (document.querySelectorAll('.item-row').length <= 1) return;
    btn.closest('.item-row').remove();
}
document.querySelectorAll('.item-product').forEach(s => {
    s.addEventListener('change', function() {
        const row = this.closest('.item-row');
        const o = this.selectedOptions[0];
        if (o && o.dataset.price) {
            if (row.querySelector('.item-listprice')) row.querySelector('.item-listprice').value = o.dataset.price;
            if (row.querySelector('.item-price')) row.querySelector('.item-price').value = o.dataset.price;
        }
        if (o && o.dataset.kdv && row.querySelector('.item-kdv')) row.querySelector('.item-kdv').value = o.dataset.kdv;
    });
});
</script>
@endsection
