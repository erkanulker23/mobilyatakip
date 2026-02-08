@extends('layouts.app')
@section('title', 'Düzenle: ' . $quote->quoteNumber)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('quotes.index') }}" class="hover:text-slate-700">Teklifler</a>
        <span>/</span>
        <a href="{{ route('quotes.show', $quote) }}" class="hover:text-slate-700">{{ $quote->quoteNumber }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Teklif Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $quote->quoteNumber }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-4xl">
    <form method="POST" action="{{ route('quotes.update', $quote) }}" class="space-y-5">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="form-label">KDV</label>
                <select name="kdvIncluded" class="form-select">
                    <option value="1" {{ old('kdvIncluded', $quote->kdvIncluded ?? true) ? 'selected' : '' }}>KDV Dahil</option>
                    <option value="0" {{ !old('kdvIncluded', $quote->kdvIncluded ?? true) ? 'selected' : '' }}>KDV Hariç</option>
                </select>
            </div>
            <div>
                <label class="form-label">Müşteri *</label>
                <select name="customerId" required class="form-select">
                    @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ old('customerId', $quote->customerId) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Personel</label>
                <select name="personnelId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($personnel as $p)
                    <option value="{{ $p->id }}" {{ old('personnelId', $quote->personnelId) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <option value="taslak" {{ old('status', $quote->status) == 'taslak' ? 'selected' : '' }}>Taslak</option>
                    <option value="onaylandi" {{ old('status', $quote->status) == 'onaylandi' ? 'selected' : '' }}>Onaylandı</option>
                    <option value="reddedildi" {{ old('status', $quote->status) == 'reddedildi' ? 'selected' : '' }}>Reddedildi</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Geçerlilik Tarihi</label>
                <input type="date" name="validUntil" value="{{ old('validUntil', $quote->validUntil?->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Genel İndirim %</label>
                <input type="number" step="0.01" min="0" max="100" name="generalDiscountPercent" value="{{ old('generalDiscountPercent', $quote->generalDiscountPercent ?? 0) }}" class="form-input">
            </div>
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="2" class="form-input form-textarea">{{ old('notes', $quote->notes) }}</textarea>
        </div>

        <div class="form-items-section-box mt-5">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Teklif Kalemleri</h3>
            <div id="items" class="space-y-3">
                @foreach($quote->items as $idx => $item)
                <div class="item-row form-item-row grid grid-cols-1 md:grid-cols-[1fr_100px_70px_70px_60px_60px_40px] gap-3 items-end">
                    <div>
                        <label class="form-label">Ürün *</label>
                        <select name="items[{{ $idx }}][productId]" required class="form-select item-product">
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}" {{ $item->productId == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Fiyat *</label>
                        <input type="text" inputmode="decimal" name="items[{{ $idx }}][unitPrice]" required value="{{ old("items.{$idx}.unitPrice", number_format($item->unitPrice ?? 0, 0, ',', '.')) }}" class="form-input item-price" placeholder="0" title="Örn: 20.000">
                    </div>
                    <div>
                        <label class="form-label">Adet *</label>
                        <input type="number" name="items[{{ $idx }}][quantity]" value="{{ old("items.{$idx}.quantity", $item->quantity) }}" required min="1" class="form-input item-qty">
                    </div>
                    <div>
                        <label class="form-label">KDV %</label>
                        <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][kdvRate]" value="{{ old("items.{$idx}.kdvRate", $item->kdvRate ?? 18) }}" class="form-input item-kdv">
                    </div>
                    <div>
                        <label class="form-label text-xs">İnd. %</label>
                        <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][lineDiscountPercent]" value="{{ old("items.{$idx}.lineDiscountPercent", $item->lineDiscountPercent) }}" class="form-input" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label text-xs">İnd. ₺</label>
                        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][lineDiscountAmount]" value="{{ old("items.{$idx}.lineDiscountAmount", $item->lineDiscountAmount) }}" class="form-input" placeholder="0">
                    </div>
                    <div><button type="button" onclick="removeRow(this)" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 font-medium">−</button></div>
                </div>
                @endforeach
                @if($quote->items->isEmpty())
                <div class="item-row form-item-row grid grid-cols-1 md:grid-cols-[1fr_100px_70px_70px_60px_60px_40px] gap-3 items-end">
                    <div>
                        <label class="form-label">Ürün *</label>
                        <select name="items[0][productId]" required class="form-select item-product">
                            <option value="">Seçiniz</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Fiyat *</label><input type="text" inputmode="decimal" name="items[0][unitPrice]" required class="form-input item-price" placeholder="0" title="Örn: 20.000"></div>
                    <div><label class="form-label">Adet *</label><input type="number" name="items[0][quantity]" value="1" required min="1" class="form-input item-qty"></div>
                    <div><label class="form-label">KDV %</label><input type="number" step="0.01" min="0" max="100" name="items[0][kdvRate]" value="18" class="form-input item-kdv"></div>
                    <div><label class="form-label text-xs">İnd. %</label><input type="number" step="0.01" min="0" max="100" name="items[0][lineDiscountPercent]" value="" class="form-input" placeholder="0"></div>
                    <div><label class="form-label text-xs">İnd. ₺</label><input type="number" step="0.01" min="0" name="items[0][lineDiscountAmount]" value="" class="form-input" placeholder="0"></div>
                    <div><button type="button" onclick="addRow()" class="p-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">+</button></div>
                </div>
                @endif
            </div>
            <button type="button" onclick="addRow()" class="mt-3 inline-flex items-center gap-2 px-3 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 text-sm font-medium">+ Kalem Ekle</button>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
function fmt(n) { return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0); }
function parseTrNum(s) {
    if (s == null || s === '') return NaN;
    const t = String(s).replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
    return parseFloat(t) || NaN;
}
let idx = {{ $quote->items->count() ?: 1 }};
function addRow() {
    const t = document.querySelector('.item-row');
    if (!t) return;
    const c = t.cloneNode(true);
    c.querySelectorAll('select, input').forEach(e => {
        if (e.name) e.name = e.name.replace(/items\[\d+\]/, 'items[' + idx + ']');
        if (e.classList.contains('item-price')) e.value = '';
        if (e.classList.contains('item-qty')) e.value = '1';
        if (e.classList.contains('item-kdv')) e.value = '18';
        if (e.name && e.name.includes('lineDiscountPercent')) e.value = '';
        if (e.name && e.name.includes('lineDiscountAmount')) e.value = '';
        if (e.tagName === 'SELECT') e.selectedIndex = 0;
    });
    const btn = c.querySelector('button');
    if (btn) {
        btn.setAttribute('onclick', 'removeRow(this)');
        btn.className = 'p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 font-medium';
        btn.textContent = '−';
    }
    document.getElementById('items').appendChild(c);
    c.querySelector('.item-product')?.addEventListener('change', function() {
        const o = this.selectedOptions[0];
        if (o) {
            const row = this.closest('.item-row');
            if (row) {
                const priceEl = row.querySelector('.item-price');
                const kdvEl = row.querySelector('.item-kdv');
                if (priceEl && o.dataset.price) priceEl.value = fmt(parseFloat(o.dataset.price) || 0);
                if (kdvEl && o.dataset.kdv) kdvEl.value = o.dataset.kdv;
            }
        }
    });
    idx++;
}
function removeRow(btn) {
    if (document.querySelectorAll('.item-row').length <= 1) return;
    btn.closest('.item-row').remove();
}
document.querySelectorAll('.item-product').forEach(s => {
    s.addEventListener('change', function() {
        const o = this.selectedOptions[0];
        if (o) {
            const row = this.closest('.item-row');
            if (o.dataset.price) row.querySelector('.item-price').value = fmt(parseFloat(o.dataset.price) || 0);
            if (o.dataset.kdv) row.querySelector('.item-kdv').value = o.dataset.kdv;
        }
    });
});
document.querySelector('form')?.addEventListener('submit', function() {
    document.querySelectorAll('.item-price').forEach(function(inp) {
        const v = parseTrNum(inp.value);
        inp.value = isNaN(v) || v < 0 ? '' : String(v);
    });
});
</script>
@endsection
