@extends('layouts.app')
@section('title', 'Yeni Alış')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
.form-create-section { padding: 1.5rem 0; border-bottom: 1px solid #e2e8f0; }
.form-create-section:last-of-type { border-bottom: 0; }
.dark .form-create-section { border-color: #334155; }
.form-create-section-title { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.25rem; }
.dark .form-create-section-title { color: #94a3b8; }
.purchase-item-card { background: #fff; border-radius: 0.75rem; padding: 1.25rem; border: 1px solid #e2e8f0; margin-bottom: 1rem; }
.dark .purchase-item-card { background: #334155; border-color: #475569; }
.purchase-item-card .form-label { margin-bottom: 0.5rem; white-space: nowrap; }
.purchase-item-card .form-input { min-width: 0; width: 100%; }
.purchase-item-card .form-select { width: 100%; }
.purchase-totals-box { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 1rem; padding: 1.25rem; border: 1px solid #a7f3d0; }
.dark .purchase-totals-box { background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(5,150,105,0.2) 100%); border-color: #047857; }
.form-actions-sticky { padding: 1rem 0; margin: 0 -1.5rem -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; background: #fff; border-top: 1px solid #f1f5f9; }
.dark .form-actions-sticky { background: #1e293b; border-color: #334155; }
@media (min-width: 768px) { .form-actions-sticky { margin: 0; padding: 0; border: 0; background: transparent; } }
@media (max-width: 767px) { .form-actions-sticky { margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; margin-bottom: -1rem; padding-bottom: max(1rem, env(safe-area-inset-bottom)); } }
</style>
@endpush
@section('content')
<div class="mb-6 md:mb-8">
    <nav class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('purchases.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Alışlar</a>
        <span aria-hidden="true">/</span>
        <span class="text-slate-700 dark:text-slate-300">Yeni Alış</span>
    </nav>
    <h1 class="page-title">Yeni Alış</h1>
    <p class="page-desc">Tedarikçiden alış kaydı oluşturun</p>
</div>

@php
    $productsPurchaseJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ' (' . number_format($p->unitPrice, 0, ',', '.') . ' ₺)', 'price' => (float)$p->unitPrice, 'kdv' => (float)($p->kdvRate ?? 18)])->values();
@endphp
<div class="w-full max-w-7xl mx-auto px-1" x-data="purchaseCreateForm()" @open-quick-add-product.window="showQuickAddProduct = true">
    <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
        @csrf
        <div class="card overflow-hidden">
            {{-- Tedarikçi & ayarlar --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pt-6">
                <h2 class="form-create-section-title">Tedarikçi & ayarlar</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="form-label">Tedarikçi <span class="text-red-500">*</span></label>
                        <select name="supplierId" required class="form-select min-h-[44px] md:min-h-[42px]" id="supplierSelect" placeholder="Tedarikçi ara veya seçin...">
                            <option value="">Seçiniz</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ old('supplierId') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplierId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Depo <span class="text-red-500">*</span></label>
                        <select name="warehouseId" required class="form-select min-h-[44px] md:min-h-[42px]">
                            <option value="">Seçiniz</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouseId') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Malın gireceği depo</p>
                        @error('warehouseId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">KDV</label>
                        <select name="kdvIncluded" class="form-select min-h-[44px] md:min-h-[42px]">
                            <option value="1" {{ old('kdvIncluded', '1') == '1' ? 'selected' : '' }}>KDV Dahil</option>
                            <option value="0" {{ old('kdvIncluded') === '0' ? 'selected' : '' }}>KDV Hariç</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Birim fiyat KDV dahil mi?</p>
                    </div>
                    <div>
                        <label class="form-label">Tedarikçi iskonto %</label>
                        <input type="number" step="0.01" min="0" max="100" name="supplierDiscountRate" value="{{ old('supplierDiscountRate') }}" class="form-input min-h-[44px] md:min-h-[42px]" placeholder="0">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Bu alış için geçerli (opsiyonel)</p>
                    </div>
                </div>
            </div>

            {{-- Tarihler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <h2 class="form-create-section-title">Tarihler</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Alış Tarihi <span class="text-red-500">*</span></label>
                        <input type="date" name="purchaseDate" required value="{{ old('purchaseDate', date('Y-m-d')) }}" class="form-input min-h-[44px] md:min-h-[42px]">
                        @error('purchaseDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Vade Tarihi</label>
                        <input type="date" name="dueDate" value="{{ old('dueDate') }}" class="form-input min-h-[44px] md:min-h-[42px]">
                        @error('dueDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Kalemler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pb-6">
                <div class="form-items-section-box">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                        <h2 class="form-create-section-title mb-0">Alış kalemleri</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 sm:max-w-sm">Ürün seçin, liste ve iskontolu fiyat girin.</p>
                    </div>
                    <template id="purchase-item-template">
                    <div class="item-row purchase-item-card" data-row-idx="__IDX__">
                        <div class="space-y-4">
                            {{-- Ürün + Fiyat/Adet/KDV satırı — her hücre border ile ayrı --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-4 gap-y-4 xl:gap-5 xl:items-end">
                                <div class="xl:col-span-4 flex gap-2 xl:pr-2">
                                    <div class="flex-1 min-w-0">
                                        <label class="form-label">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                        <select name="items[__IDX__][productId]" required class="form-select item-product min-h-[44px] md:min-h-[42px]" data-placeholder="Ürün ara veya seçin...">
                                            <option value="">Seçiniz</option>
                                            @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" onclick="window.openQuickAddPurchaseProduct && window.openQuickAddPurchaseProduct(this)" class="shrink-0 self-end flex items-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 text-sm font-medium touch-manipulation min-h-[44px] md:min-h-[42px]" title="Ürün/hizmet hızlı ekle">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span class="hidden sm:inline">Hızlı ekle</span>
                                    </button>
                                </div>
                                <div class="xl:col-span-2 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">Liste fiyatı</label>
                                    <input type="number" step="0.01" min="0" name="items[__IDX__][listPrice]" class="form-input item-listprice min-h-[44px] md:min-h-[42px] w-full" placeholder="—">
                                </div>
                                <div class="xl:col-span-2 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">İskontolu fiyat <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" name="items[__IDX__][unitPrice]" required class="form-input item-price min-h-[44px] md:min-h-[42px] w-full" placeholder="0">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">Adet <span class="text-red-500">*</span></label>
                                    <input type="number" name="items[__IDX__][quantity]" value="1" required min="1" class="form-input item-qty min-h-[44px] md:min-h-[42px] w-full" placeholder="1">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">KDV %</label>
                                    <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][kdvRate]" value="18" class="form-input item-kdv min-h-[44px] md:min-h-[42px] w-full" placeholder="18">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4 flex items-end pb-0.5">
                                    <button type="button" onclick="addPurchaseRow()" class="w-11 h-11 md:w-12 md:h-12 flex items-center justify-center rounded-xl bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 font-medium touch-manipulation text-lg" aria-label="Satır ekle">+</button>
                                </div>
                            </div>
                            {{-- İskonto alanları — ayrı blok, üst çizgi + arka plan --}}
                            <div class="pt-4 mt-1 border-t border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800/50 px-4 py-3">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-3">Kalem iskontosu (opsiyonel)</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-md">
                                    <div>
                                        <label class="form-label">İsk. %</label>
                                        <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][lineDiscountPercent]" value="" class="form-input item-disc-pct min-h-[44px] md:min-h-[42px] w-full" placeholder="0" title="İskonto yüzdesi (opsiyonel)">
                                    </div>
                                    <div class="sm:border-l sm:border-slate-200 dark:sm:border-slate-600 sm:pl-4">
                                        <label class="form-label">İsk. ₺</label>
                                        <input type="number" step="0.01" min="0" name="items[__IDX__][lineDiscountAmount]" value="" class="form-input item-disc-amt min-h-[44px] md:min-h-[42px] w-full" placeholder="0" title="İskonto tutarı ₺ (opsiyonel)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </template>
                    <div id="items" class="space-y-3"></div>
                    <div class="mt-6 flex flex-col lg:flex-row lg:items-start gap-4">
                        <div class="flex-1 min-w-0"></div>
                        <div id="purchaseTotals" class="purchase-totals-box w-full lg:min-w-[280px] shrink-0">
                            <div class="space-y-1.5 text-sm">
                                <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">Ara Toplam</span> <span id="subtotalDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                                <div class="flex justify-between" id="discountRow"><span class="text-slate-600 dark:text-slate-300">İskonto Toplam</span> <span id="discountDisplay" class="font-medium text-amber-600 dark:text-amber-400">0 ₺</span></div>
                                <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">KDV Toplam</span> <span id="kdvDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                                <div class="flex justify-between pt-2 border-t border-emerald-200 dark:border-emerald-800"><span class="font-medium text-slate-800 dark:text-slate-200">Genel Toplam</span> <span id="grandTotalDisplay" class="font-semibold text-emerald-700 dark:text-emerald-300 text-lg">0 ₺</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aksiyonlar --}}
            <div class="form-actions-sticky px-4 sm:px-6 lg:px-8 pt-5 pb-6 md:pb-6">
                <div class="flex flex-col-reverse sm:flex-row gap-3 sm:gap-2">
                    <a href="{{ route('purchases.index') }}" class="btn-secondary justify-center min-h-[44px] md:min-h-[42px] order-2 sm:order-1">İptal</a>
                    <button type="submit" class="btn-primary justify-center min-h-[44px] md:min-h-[42px] flex-1 sm:flex-initial order-1 sm:order-2">Kaydet</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Hızlı Ürün/Hizmet Ekle Modal --}}
    <div x-show="showQuickAddProduct" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quick-add-product-title">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showQuickAddProduct = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 pt-5 pb-1">
                <h2 id="quick-add-product-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Hızlı Ürün / Hizmet Ekle</h2>
            </div>
            <form @submit.prevent="quickAddProduct()" class="p-5 space-y-4">
                <div>
                    <label class="form-label">Ad <span class="text-red-500">*</span></label>
                    <input type="text" x-model="quickProduct.name" required class="form-input min-h-[44px]" placeholder="Örn: Montaj hizmeti">
                </div>
                <div>
                    <label class="form-label">Birim fiyat (₺) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" x-model="quickProduct.unitPrice" required class="form-input min-h-[44px]" placeholder="0">
                </div>
                <div>
                    <label class="form-label">KDV %</label>
                    <input type="number" step="0.01" min="0" max="100" x-model="quickProduct.kdvRate" class="form-input min-h-[44px]" placeholder="18">
                </div>
                <p x-show="quickAddProductError" x-text="quickAddProductError" class="text-sm text-red-600 dark:text-red-400"></p>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" @click="showQuickAddProduct = false" class="btn-secondary min-h-[44px]">İptal</button>
                    <button type="submit" :disabled="quickAddProductLoading" class="btn-primary min-h-[44px] disabled:opacity-70">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const productsPurchaseData = @json($productsPurchaseJson);
function purchaseCreateForm() {
    return {
        showQuickAddProduct: false,
        quickProduct: { name: '', unitPrice: '', kdvRate: '18' },
        quickAddProductLoading: false,
        quickAddProductError: '',
        async quickAddProduct() {
            this.quickAddProductError = '';
            this.quickAddProductLoading = true;
            try {
                const res = await fetch('{{ route("api.products.quick-store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.quickProduct.name,
                        unitPrice: parseFloat(this.quickProduct.unitPrice) || 0,
                        kdvRate: parseFloat(this.quickProduct.kdvRate) || 18,
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    const text = data.name + ' (' + fmt(data.price) + ' ₺)';
                    productsPurchaseData.push({ id: String(data.id), name: text, price: data.price, kdv: data.kdv });
                    const tmplSelect = document.getElementById('purchase-item-template')?.content?.querySelector('.item-product');
                    if (tmplSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.setAttribute('data-price', data.price);
                        opt.setAttribute('data-kdv', data.kdv);
                        opt.textContent = text;
                        tmplSelect.appendChild(opt);
                    }
                    (window.purchaseProductSelects || []).forEach(function(ts, i) {
                        if (ts) {
                            ts.addOption({ value: String(data.id), text: text });
                            if (i === (window.quickAddPurchaseProductForRowIndex || 0)) {
                                ts.setValue(data.id);
                            }
                        }
                    });
                    this.showQuickAddProduct = false;
                    this.quickProduct = { name: '', unitPrice: '', kdvRate: '18' };
                    updateTotals();
                } else {
                    this.quickAddProductError = data.message || 'Hata oluştu';
                }
            } catch (e) {
                this.quickAddProductError = 'Bağlantı hatası';
            }
            this.quickAddProductLoading = false;
        }
    };
}
function fmt(n) { return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0); }
function updateTotals() {
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    let subtotal = 0, kdvTotal = 0, totalDiscount = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.item-price')?.value || 0);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 18, 10);
        const discPct = parseFloat(row.querySelector('.item-disc-pct')?.value || 0, 10);
        const discAmt = parseFloat(row.querySelector('.item-disc-amt')?.value || 0, 10);
        if (price <= 0 || qty <= 0) return;
        let lineNetBeforeDisc;
        if (kdvIncl) {
            lineNetBeforeDisc = price * qty / (1 + kdv / 100);
        } else {
            lineNetBeforeDisc = price * qty;
        }
        const lineDiscValue = lineNetBeforeDisc * (discPct / 100) + discAmt;
        let lineNet = lineNetBeforeDisc - lineDiscValue;
        lineNet = Math.max(0, lineNet);
        totalDiscount += lineDiscValue;
        const lineKdv = lineNet * (kdv / 100);
        subtotal += lineNet;
        kdvTotal += lineKdv;
    });
    document.getElementById('subtotalDisplay').textContent = fmt(subtotal) + ' ₺';
    document.getElementById('discountDisplay').textContent = '-' + fmt(totalDiscount) + ' ₺';
    document.getElementById('discountRow').style.display = totalDiscount > 0 ? '' : 'none';
    document.getElementById('kdvDisplay').textContent = fmt(kdvTotal) + ' ₺';
    document.getElementById('grandTotalDisplay').textContent = fmt(subtotal + kdvTotal) + ' ₺';
}
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = initPurchaseForm;
        document.head.appendChild(s);
    } else initPurchaseForm();
});
function initPurchaseForm() {
    const sel = document.getElementById('supplierSelect');
    if (sel && typeof TomSelect !== 'undefined') new TomSelect(sel, { maxOptions: 100, placeholder: 'Tedarikçi ara veya seçin...', searchField: ['text'] });
    addPurchaseRow();
    document.getElementById('purchaseForm')?.addEventListener('input', updateTotals);
    document.getElementById('purchaseForm')?.addEventListener('change', updateTotals);
    updateTotals();
}
window.openQuickAddPurchaseProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddPurchaseProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
let purchaseIdx = 0;
function addPurchaseRow() {
    const tmpl = document.getElementById('purchase-item-template');
    if (!tmpl) return;
    const c = tmpl.content.cloneNode(true);
    c.querySelectorAll('[name]').forEach(e => { e.name = e.name.replace(/__IDX__/g, purchaseIdx); });
    const row = c.querySelector('.item-row');
    if (row) row.setAttribute('data-row-idx', String(purchaseIdx));
    c.querySelector('.item-listprice').value = '';
    c.querySelector('.item-price').value = '';
    c.querySelector('.item-qty').value = '1';
    c.querySelector('.item-kdv').value = '18';
    c.querySelector('.item-disc-pct').value = '';
    c.querySelector('.item-disc-amt').value = '';
    document.getElementById('items').appendChild(c);
    const prodSel = document.getElementById('items').lastElementChild.querySelector('.item-product');
    initPurchaseProductSelect(prodSel, purchaseIdx);
    purchaseIdx++;
}
function initPurchaseProductSelect(sel, rowIdx) {
    if (!sel || typeof TomSelect === 'undefined') return;
    window.purchaseProductSelects = window.purchaseProductSelects || [];
    const row = sel.closest('.item-row');
    const ts = new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Ürün ara veya seçin...',
        searchField: ['text'],
        onChange: function(v) {
            if (!v) return;
            const opt = Array.from(sel.options).find(o => o.value === v);
            if (opt?.dataset?.price) {
                row.querySelector('.item-listprice').value = opt.dataset.price;
                row.querySelector('.item-price').value = opt.dataset.price;
            }
            if (opt?.dataset?.kdv) row.querySelector('.item-kdv').value = opt.dataset.kdv;
            updateTotals();
        }
    });
    window.purchaseProductSelects[rowIdx] = ts;
}
</script>
@endsection
