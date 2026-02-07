@extends('layouts.app')
@section('title', 'Yeni Teklif')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
.form-create-section { padding: 1.5rem 0; border-bottom: 1px solid #e2e8f0; }
.form-create-section:last-of-type { border-bottom: 0; }
.dark .form-create-section { border-color: #334155; }
.form-create-section-title { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.25rem; }
.dark .form-create-section-title { color: #94a3b8; }
.quote-item-card { background: #fff; border-radius: 0.75rem; padding: 1.25rem; border: 1px solid #e2e8f0; margin-bottom: 1rem; }
.dark .quote-item-card { background: #334155; border-color: #475569; }
.quote-item-card .form-label { margin-bottom: 0.5rem; }
.quote-item-card .form-input { min-width: 0; }
.quote-totals-box { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 1rem; padding: 1.25rem; border: 1px solid #a7f3d0; }
.dark .quote-totals-box { background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(5,150,105,0.2) 100%); border-color: #047857; }
.form-actions-sticky { padding: 1rem 0; margin: 0 -1.5rem -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; background: #fff; border-top: 1px solid #f1f5f9; }
.dark .form-actions-sticky { background: #1e293b; border-color: #334155; }
@media (min-width: 768px) { .form-actions-sticky { margin: 0; padding: 0; border: 0; background: transparent; } }
@media (max-width: 767px) { .form-actions-sticky { margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; margin-bottom: -1rem; padding-bottom: max(1rem, env(safe-area-inset-bottom)); } }
</style>
@endpush
@section('content')
<div class="mb-6 md:mb-8">
    <nav class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('quotes.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Teklifler</a>
        <span aria-hidden="true">/</span>
        <span class="text-slate-700 dark:text-slate-300">Yeni Teklif</span>
    </nav>
    <h1 class="page-title">Yeni Teklif</h1>
    <p class="page-desc">Yeni teklif oluşturun</p>
</div>

@php
    $customersQuoteJson = $customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
    $productsQuoteJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ' (' . number_format($p->unitPrice, 0, ',', '.') . ' ₺)', 'price' => (float)$p->unitPrice, 'kdv' => (float)($p->kdvRate ?? 18)])->values();
@endphp
<div class="w-full max-w-6xl mx-auto px-1" x-data="quoteCreateForm()" @open-quick-add-product.window="showQuickAddProduct = true">
    <form method="POST" action="{{ route('quotes.store') }}" id="quoteForm">
        @csrf
        <div class="card overflow-hidden">
            {{-- Genel bilgiler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pt-6">
                <h2 class="form-create-section-title">Genel bilgiler</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Müşteri <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <select name="customerId" required class="form-select min-h-[44px] md:min-h-[42px]" id="customerSelect" placeholder="Müşteri ara veya seçin...">
                                <option value="">Seçiniz</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="showQuickAddCustomer = true" class="shrink-0 w-11 h-11 md:w-10 md:h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors touch-manipulation" title="Hızlı müşteri ekle" aria-label="Hızlı müşteri ekle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>
                        @error('customerId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Teklifi oluşturan</label>
                        <select name="personnelId" class="form-select min-h-[44px] md:min-h-[42px]" id="personnelSelect">
                            <option value="">Seçiniz</option>
                            @foreach($personnel as $p)
                            <option value="{{ $p->id }}" {{ old('personnelId') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Teklifi hazırlayan personel</p>
                    </div>
                </div>
            </div>

            {{-- Tarihler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <h2 class="form-create-section-title">Tarihler</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Geçerlilik Tarihi</label>
                        <input type="date" name="validUntil" value="{{ old('validUntil') }}" class="form-input min-h-[44px] md:min-h-[42px]">
                    </div>
                </div>
            </div>

            {{-- Kalemler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pb-6">
                <div class="form-items-section-box">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                        <h2 class="form-create-section-title mb-0">Teklif kalemleri</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 sm:max-w-sm">Ürün seçin veya hızlı ekle ile yeni ürün/hizmet ekleyin.</p>
                    </div>
                    <template id="quote-item-template">
                    <div class="item-row quote-item-card" data-row-idx="__IDX__">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-5 lg:items-end">
                            <div class="lg:col-span-4 flex gap-2">
                                <div class="flex-1 min-w-0">
                                    <label class="form-label">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                    <select name="items[__IDX__][productId]" required class="form-select item-product min-h-[44px] md:min-h-[42px]" data-placeholder="Ürün ara veya seçin...">
                                        <option value="">Seçiniz</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" onclick="window.openQuickAddQuoteProduct && window.openQuickAddQuoteProduct(this)" class="shrink-0 self-end flex items-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 text-sm font-medium touch-manipulation min-h-[44px] md:min-h-[42px]" title="Ürün/hizmet hızlı ekle">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    <span class="hidden sm:inline">Hızlı ekle</span>
                                </button>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="form-label">Fiyat <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" name="items[__IDX__][unitPrice]" required class="form-input item-price min-h-[44px] md:min-h-[42px]" placeholder="0">
                            </div>
                            <div class="lg:col-span-1">
                                <label class="form-label">Adet <span class="text-red-500">*</span></label>
                                <input type="number" name="items[__IDX__][quantity]" value="1" required min="1" class="form-input item-qty min-h-[44px] md:min-h-[42px]" placeholder="1">
                            </div>
                            <div class="lg:col-span-1">
                                <label class="form-label">İsk. %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][lineDiscountPercent]" value="" class="form-input item-disc-pct min-h-[44px] md:min-h-[42px]" placeholder="0" title="Satır iskontosu % (opsiyonel)">
                            </div>
                            <div class="lg:col-span-2 border-l border-slate-200 dark:border-slate-600 pl-4">
                                <label class="form-label">İsk. ₺</label>
                                <input type="number" step="0.01" min="0" name="items[__IDX__][lineDiscountAmount]" value="" class="form-input item-disc-amt min-h-[44px] md:min-h-[42px]" placeholder="0" title="Satır iskontosu tutar (opsiyonel)">
                            </div>
                            <div class="lg:col-span-1">
                                <label class="form-label">KDV %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][kdvRate]" value="18" class="form-input item-kdv min-h-[44px] md:min-h-[42px]" placeholder="18">
                            </div>
                            <div class="lg:col-span-1 flex items-end">
                                <button type="button" onclick="addQuoteRow()" class="w-11 h-11 md:w-12 md:h-12 flex items-center justify-center rounded-xl bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 font-medium touch-manipulation text-lg" aria-label="Satır ekle">+</button>
                            </div>
                        </div>
                    </div>
                    </template>
                    <div id="items" class="space-y-3"></div>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Kalem iskontoları (İsk. % / İsk. ₺) opsiyoneldir.</p>
                </div>
            </div>

            {{-- Notlar --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <label class="form-label">Notlar</label>
                <textarea name="notes" rows="2" class="form-input form-textarea" placeholder="Opsiyonel not...">{{ old('notes') }}</textarea>
            </div>

            {{-- KDV ve Özet (en altta) --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pb-2">
                <h2 class="form-create-section-title">Fiyatlandırma</h2>
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div class="max-w-xs">
                        <label class="form-label">Birim fiyatlar KDV</label>
                        <select name="kdvIncluded" class="form-select min-h-[44px] md:min-h-[42px]">
                            <option value="1" {{ old('kdvIncluded', '1') == '1' ? 'selected' : '' }}>KDV Dahil</option>
                            <option value="0" {{ old('kdvIncluded') === '0' ? 'selected' : '' }}>KDV Hariç</option>
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Kalemlerdeki fiyatlar KDV dahil mi hariç mi?</p>
                    </div>
                    <div id="quoteTotals" class="quote-totals-box w-full lg:w-72 shrink-0">
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">Ara Toplam</span> <span id="subtotalDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                            <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">KDV Toplam</span> <span id="kdvDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                            <div class="flex justify-between pt-2 border-t border-emerald-200 dark:border-emerald-800"><span class="font-medium text-slate-800 dark:text-slate-200">Genel Toplam</span> <span id="grandTotalDisplay" class="font-semibold text-emerald-700 dark:text-emerald-300 text-lg">0 ₺</span></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aksiyonlar --}}
            <div class="form-actions-sticky px-4 sm:px-6 lg:px-8 pt-5 pb-6 md:pb-6">
                <div class="flex flex-col-reverse sm:flex-row gap-3 sm:gap-2">
                    <a href="{{ route('quotes.index') }}" class="btn-secondary justify-center min-h-[44px] md:min-h-[42px] order-2 sm:order-1">İptal</a>
                    <button type="submit" class="btn-primary justify-center min-h-[44px] md:min-h-[42px] flex-1 sm:flex-initial order-1 sm:order-2">Teklif Oluştur</button>
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

    {{-- Hızlı Müşteri Ekle Modal --}}
    <div x-show="showQuickAddCustomer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quick-add-customer-title">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showQuickAddCustomer = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 pt-5 pb-1">
                <h2 id="quick-add-customer-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Hızlı Müşteri Ekle</h2>
            </div>
            <form @submit.prevent="quickAddCustomer()" class="p-5 space-y-4">
                <div>
                    <label class="form-label">Müşteri Adı <span class="text-red-500">*</span></label>
                    <input type="text" x-model="quickCustomer.name" required class="form-input min-h-[44px]" placeholder="Müşteri adı">
                </div>
                <div>
                    <label class="form-label">Telefon</label>
                    <input type="tel" x-model="quickCustomer.phone" class="form-input min-h-[44px]" placeholder="0555 123 45 67">
                </div>
                <div>
                    <label class="form-label">E-posta</label>
                    <input type="email" x-model="quickCustomer.email" class="form-input min-h-[44px]" placeholder="ornek@email.com">
                </div>
                <div>
                    <label class="form-label">Adres</label>
                    <textarea x-model="quickCustomer.address" rows="2" class="form-input form-textarea"></textarea>
                </div>
                <p x-show="quickAddCustomerError" x-text="quickAddCustomerError" class="text-sm text-red-600 dark:text-red-400"></p>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" @click="showQuickAddCustomer = false" class="btn-secondary min-h-[44px]">İptal</button>
                    <button type="submit" :disabled="quickAddCustomerLoading" class="btn-primary min-h-[44px] disabled:opacity-70">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const customersQuote = @json($customersQuoteJson);
const productsQuoteData = @json($productsQuoteJson);
function quoteCreateForm() {
    return {
        showQuickAddCustomer: false,
        quickCustomer: { name: '', phone: '', email: '', address: '' },
        quickAddCustomerLoading: false,
        quickAddCustomerError: '',
        showQuickAddProduct: false,
        quickProduct: { name: '', unitPrice: '', kdvRate: '18' },
        quickAddProductLoading: false,
        quickAddProductError: '',
        async quickAddCustomer() {
            this.quickAddCustomerError = '';
            this.quickAddCustomerLoading = true;
            try {
                const res = await fetch('{{ route("api.customers.quick-store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.quickCustomer)
                });
                const data = await res.json();
                if (res.ok) {
                    customersQuote.push({ id: data.id, name: data.name });
                    if (window.customerQuoteTomSelect) {
                        window.customerQuoteTomSelect.addOption({ value: data.id, text: data.name });
                        window.customerQuoteTomSelect.setValue(data.id);
                    }
                    this.showQuickAddCustomer = false;
                    this.quickCustomer = { name: '', phone: '', email: '', address: '' };
                } else {
                    this.quickAddCustomerError = data.message || 'Hata oluştu';
                }
            } catch (e) {
                this.quickAddCustomerError = 'Bağlantı hatası';
            }
            this.quickAddCustomerLoading = false;
        },
        async quickAddProduct() {
            this.quickAddProductError = '';
            this.quickAddProductLoading = true;
            try {
                const res = await fetch('{{ route("api.products.quick-store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.quickProduct.name, unitPrice: parseFloat(this.quickProduct.unitPrice) || 0, kdvRate: parseFloat(this.quickProduct.kdvRate) || 18 })
                });
                const data = await res.json();
                if (res.ok) {
                    const text = data.name + ' (' + fmt(data.price) + ' ₺)';
                    productsQuoteData.push({ id: String(data.id), name: text, price: data.price, kdv: data.kdv });
                    const tmplSelect = document.getElementById('quote-item-template')?.content?.querySelector('.item-product');
                    if (tmplSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.setAttribute('data-price', data.price);
                        opt.setAttribute('data-kdv', data.kdv);
                        opt.textContent = text;
                        tmplSelect.appendChild(opt);
                    }
                    (window.quoteProductSelects || []).forEach(function(ts, i) {
                        if (ts) {
                            ts.addOption({ value: String(data.id), text: text });
                            if (i === (window.quickAddQuoteProductForRowIndex || 0)) ts.setValue(data.id);
                        }
                    });
                    this.showQuickAddProduct = false;
                    this.quickProduct = { name: '', unitPrice: '', kdvRate: '18' };
                    updateQuoteTotals();
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
function updateQuoteTotals() {
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    let subtotal = 0, kdvTotal = 0;
    document.querySelectorAll('#items .item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.item-price')?.value || 0);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 18, 10);
        const discPct = parseFloat(row.querySelector('.item-disc-pct')?.value || 0, 10);
        const discAmt = parseFloat(row.querySelector('.item-disc-amt')?.value || 0, 10);
        if (price <= 0 || qty <= 0) return;
        let lineTotal;
        if (kdvIncl) {
            lineTotal = price * qty / (1 + kdv / 100);
        } else {
            lineTotal = price * qty;
        }
        lineTotal = lineTotal * (1 - discPct / 100) - discAmt;
        lineTotal = Math.max(0, lineTotal);
        const lineKdv = lineTotal * (kdv / 100);
        subtotal += lineTotal;
        kdvTotal += lineKdv;
    });
    const subtotalEl = document.getElementById('subtotalDisplay');
    if (subtotalEl) {
        subtotalEl.textContent = fmt(subtotal) + ' ₺';
        document.getElementById('kdvDisplay').textContent = fmt(kdvTotal) + ' ₺';
        document.getElementById('grandTotalDisplay').textContent = fmt(subtotal + kdvTotal) + ' ₺';
    }
}
window.openQuickAddQuoteProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddQuoteProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
let quoteIdx = 0;
function addQuoteRow() {
    const tmpl = document.getElementById('quote-item-template');
    if (!tmpl) return;
    const c = tmpl.content.cloneNode(true);
    c.querySelectorAll('[name]').forEach(e => { e.name = e.name.replace(/__IDX__/g, quoteIdx); });
    const row = c.querySelector('.item-row');
    if (row) row.setAttribute('data-row-idx', String(quoteIdx));
    c.querySelector('.item-price').value = '';
    c.querySelector('.item-qty').value = '1';
    c.querySelector('.item-kdv').value = '18';
    c.querySelector('.item-disc-pct').value = '';
    c.querySelector('.item-disc-amt').value = '';
    const addBtn = c.querySelector('button[onclick*="addQuoteRow"]');
    if (addBtn) addBtn.setAttribute('onclick', 'addQuoteRow()');
    document.getElementById('items').appendChild(c);
    const sel = document.getElementById('items').lastElementChild.querySelector('.item-product');
    initQuoteProductSelect(sel, quoteIdx);
    quoteIdx++;
}
function initQuoteProductSelect(sel, rowIdx) {
    if (!sel || typeof TomSelect === 'undefined') return;
    window.quoteProductSelects = window.quoteProductSelects || [];
    const row = sel.closest('.item-row');
    const ts = new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Ürün ara veya seçin...',
        searchField: ['text'],
        onChange: function(v) {
            if (!v) return;
            const opt = Array.from(sel.options).find(o => o.value === v);
            if (opt?.dataset?.price) {
                row.querySelector('.item-price').value = opt.dataset.price;
                row.querySelector('.item-kdv').value = opt.dataset.kdv || 18;
            }
            updateQuoteTotals();
        }
    });
    window.quoteProductSelects[rowIdx] = ts;
}
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = initQuoteForm;
        document.head.appendChild(s);
    } else initQuoteForm();
});
function initQuoteForm() {
    const customerSel = document.getElementById('customerSelect');
    if (customerSel && typeof TomSelect !== 'undefined') {
        window.customerQuoteTomSelect = new TomSelect(customerSel, { maxOptions: 100, placeholder: 'Müşteri ara veya seçin...', searchField: ['text'] });
    }
    addQuoteRow();
    document.getElementById('quoteForm')?.addEventListener('input', updateQuoteTotals);
    document.getElementById('quoteForm')?.addEventListener('change', updateQuoteTotals);
    updateQuoteTotals();
}
</script>
@endsection
