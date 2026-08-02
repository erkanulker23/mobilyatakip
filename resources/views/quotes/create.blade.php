@extends('layouts.app')
@section('title', 'Yeni Teklif')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
.sale-form-section { background: #fff; border: 1px solid #f0f0f0; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden; }
.dark .sale-form-section { background: #171717; border-color: #262626; }
.sale-form-section-head { padding: 1rem 1.25rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.dark .sale-form-section-head { border-color: #262626; }
.sale-form-section-title { font-size: 0.9375rem; font-weight: 600; color: #171717; }
.dark .sale-form-section-title { color: #f5f5f5; }
.sale-form-section-body { padding: 1.25rem; }
.sale-item-row { border: 1px solid #e5e5e5; border-radius: 0.75rem; padding: 1rem; background: #fafafa; transition: border-color .15s, box-shadow .15s; }
.sale-item-row:focus-within { border-color: #a3a3a3; box-shadow: 0 0 0 3px rgba(0,0,0,.04); background: #fff; }
.dark .sale-item-row { background: #262626; border-color: #404040; }
.sale-item-row .form-label { margin-bottom: 0.25rem; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .04em; color: #737373; }
.row-no { flex-shrink: 0; width: 1.75rem; height: 1.75rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; background: #171717; color: #fff; font-size: 0.75rem; font-weight: 600; }
.icon-btn { flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; border: 1px solid #e5e5e5; background: #fff; color: #525252; }
.icon-btn-danger { border-color: #fecaca; color: #dc2626; }
.add-row-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.875rem; border: 1.5px dashed #d4d4d4; border-radius: 0.75rem; color: #525252; font-size: 0.875rem; font-weight: 500; background: transparent; }
.sale-summary-panel { background: #171717; color: #fff; border-radius: 1rem; padding: 1.25rem; }
.sale-summary-row { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.875rem; padding: 0.375rem 0; color: #d4d4d4; }
.sale-summary-row strong { color: #fff; font-weight: 600; font-variant-numeric: tabular-nums; }
.sale-summary-total { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #404040; display: flex; justify-content: space-between; align-items: baseline; }
.sale-summary-total span:last-child { font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.sale-meta-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 640px) { .sale-meta-grid { grid-template-columns: 1fr 1fr; } }
.customer-info-panel { margin-top: 1rem; padding: 1rem; border-radius: 0.75rem; background: #fafafa; border: 1px solid #e5e5e5; }
.customer-info-panel dt { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .04em; color: #a3a3a3; }
.customer-info-panel dd { font-size: 0.875rem; color: #171717; margin-top: 0.125rem; }
.ts-wrapper .ts-control .item { display: flex; align-items: center; gap: 0.5rem; }
.ts-dropdown.dropup { bottom: 100%; top: auto !important; margin-top: 0; margin-bottom: 4px; }
</style>
@endpush
@section('content')
<div class="mb-6">
    <nav class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('quotes.index') }}" class="hover:text-neutral-900 transition-colors">Teklifler</a>
        <span aria-hidden="true">/</span>
        <span class="text-neutral-700">Yeni Teklif</span>
    </nav>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="page-title">Yeni Teklif</h1>
            <p class="page-desc">Kurumsal teklif oluşturun</p>
        </div>
        <a href="{{ route('quotes.index') }}" class="btn-secondary text-sm self-start">← Teklif listesi</a>
    </div>
</div>

@php
    $customersQuoteJson = $customers->map(fn($c) => [
        'id' => $c->id, 'name' => $c->name,
        'phone' => $c->phone ?? '', 'email' => $c->email ?? '', 'address' => $c->full_address,
        'taxNumber' => $c->taxNumber ?? '', 'taxOffice' => $c->taxOffice ?? '', 'identityNumber' => $c->identityNumber ?? ''
    ])->values();
    $productsQuoteJson = $products->map(function($p) {
        $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null);
        return ['id' => $p->id, 'name' => $p->name . ' (' . number_format($p->unitPrice, 0, ',', '.') . ' ₺)', 'price' => (float)$p->unitPrice, 'kdv' => (float)($p->kdvRate ?? 18), 'image' => $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : null];
    })->values();
@endphp
<div class="max-w-7xl" x-data="quoteCreateForm()" @open-quick-add-product.window="showQuickAddProduct = true">
    <form method="POST" action="{{ route('quotes.store') }}" id="quoteForm" enctype="multipart/form-data">
        @csrf
        @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start">
            <div class="space-y-5 min-w-0">
                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Müşteri & Teklif Bilgileri</h2></div>
                    <div class="sale-form-section-body">
                        <div class="sale-meta-grid">
                            <div class="sm:col-span-2">
                                <label class="form-label">Müşteri <span class="text-red-500">*</span></label>
                                <div class="flex gap-2">
                                    <select name="customerId" required class="form-select min-h-[44px] flex-1" id="customerSelect" placeholder="Müşteri ara veya seçin...">
                                        <option value="">Seçiniz</option>
                                        @foreach($customers as $c)
                                        <option value="{{ $c->id }}" {{ old('customerId', request('customerId')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="showQuickAddCustomer = true" class="icon-btn shrink-0 w-11 h-11" title="Hızlı müşteri ekle" aria-label="Hızlı müşteri ekle">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </div>
                                @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">Geçerlilik Tarihi</label>
                                <input type="date" name="validUntil" value="{{ old('validUntil') }}" class="form-input min-h-[44px]">
                            </div>
                            <div>
                                <label class="form-label">Teklifi Hazırlayan</label>
                                <select name="personnelId" class="form-select min-h-[44px]">
                                    <option value="">Seçiniz</option>
                                    @foreach($personnel as $p)
                                    <option value="{{ $p->id }}" {{ old('personnelId') == $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->title ? ' — ' . $p->title : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="quoteCustomerInfoBox" class="customer-info-panel hidden">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <p id="quoteCustomerName" class="text-sm font-semibold text-neutral-900">—</p>
                            </div>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div id="quoteCustomerPhoneRow"><dt>Telefon</dt><dd id="quoteCustomerPhone">—</dd></div>
                                <div id="quoteCustomerEmailRow"><dt>E-posta</dt><dd id="quoteCustomerEmail" class="truncate">—</dd></div>
                                <div id="quoteCustomerAddressRow" class="sm:col-span-2"><dt>Adres</dt><dd id="quoteCustomerAddress">—</dd></div>
                                <div id="quoteCustomerTaxRow"><dt>Vergi</dt><dd id="quoteCustomerTax">—</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="sale-form-section">
                    <div class="sale-form-section-head">
                        <div class="flex items-center gap-2">
                            <h2 class="sale-form-section-title">Teklif Kalemleri</h2>
                            <span id="quoteItemCountBadge" class="inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 text-xs font-semibold">0</span>
                        </div>
                    </div>
                    <div class="sale-form-section-body">
                    <template id="quote-item-template">
                    <div class="item-row sale-item-row mb-3" data-row-idx="__IDX__">
                        <div class="flex flex-wrap items-start gap-2 mb-2">
                            <span class="row-no">1</span>
                            <div class="flex-1 min-w-0">
                                <label class="form-label">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                <select name="items[__IDX__][productId]" required class="form-select item-product" data-placeholder="Ara veya seç...">
                                    <option value="">Seçiniz</option>
                                    @foreach($products as $p)
                                    @php $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null); @endphp
                                    <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}" data-image="{{ $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : '' }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" onclick="window.openQuickAddQuoteProduct && window.openQuickAddQuoteProduct(this)" class="icon-btn" title="Yeni ürün" aria-label="Yeni ürün">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button type="button" onclick="removeQuoteRow(this)" class="btn-remove-row icon-btn icon-btn-danger" aria-label="Sil" title="Sil">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                            <div>
                                <label class="form-label">Fiyat ₺</label>
                                <input type="text" inputmode="decimal" name="items[__IDX__][unitPrice]" required class="form-input item-price" placeholder="20.000">
                            </div>
                            <div>
                                <label class="form-label">Adet</label>
                                <input type="number" name="items[__IDX__][quantity]" value="1" required min="1" class="form-input item-qty">
                            </div>
                            <div>
                                <label class="form-label">İsk. %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][lineDiscountPercent]" class="form-input item-disc-pct" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label">İsk. ₺</label>
                                <input type="text" inputmode="decimal" name="items[__IDX__][lineDiscountAmount]" class="form-input item-disc-amt" placeholder="0" autocomplete="off">
                            </div>
                            <div>
                                <label class="form-label">KDV %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][kdvRate]" value="18" class="form-input item-kdv">
                            </div>
                            <div class="flex flex-col justify-end text-right">
                                <span class="form-label mb-0">Toplam</span>
                                <span class="item-line-total font-semibold text-sm">0 ₺</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label">Açıklama</label>
                            <textarea name="items[__IDX__][description]" rows="2" class="form-input form-textarea item-desc w-full text-sm" placeholder="Renk, ölçü, kumaş vb."></textarea>
                        </div>
                    </div>
                    </template>
                    <div id="items" class="space-y-0"></div>
                    <button type="button" onclick="addQuoteRow()" class="add-row-btn mt-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Kalem Ekle
                    </button>
                    </div>
                </div>

                @include('partials.drawing-files-fields', ['drawingFiles' => []])

                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Notlar</h2></div>
                    <div class="sale-form-section-body">
                        <textarea name="notes" rows="3" class="form-input form-textarea" placeholder="Teklife özel notlar (opsiyonel)...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-5 xl:sticky xl:top-24">
                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Fiyatlandırma</h2></div>
                    <div class="sale-form-section-body">
                        <label class="form-label">KDV</label>
                        <select name="kdvIncluded" class="form-select min-h-[44px]">
                            <option value="1" {{ old('kdvIncluded', '1') == '1' ? 'selected' : '' }}>KDV Dahil</option>
                            <option value="0" {{ old('kdvIncluded') === '0' ? 'selected' : '' }}>KDV Hariç</option>
                        </select>
                    </div>
                </div>

                <div class="sale-summary-panel" id="quoteTotals">
                    <p class="text-xs uppercase tracking-wider text-neutral-400 mb-3">Teklif Özeti</p>
                    <div class="sale-summary-row"><span>Ara Toplam</span><strong id="quoteSubtotalBeforeDiscDisplay">0 ₺</strong></div>
                    <div id="quoteDiscountPctRow" class="sale-summary-row hidden"><span>İskonto %</span><strong id="quoteDiscountPctDisplay" class="text-amber-300">0 ₺</strong></div>
                    <div id="quoteDiscountAmtRow" class="sale-summary-row hidden"><span>İskonto ₺</span><strong id="quoteDiscountAmtDisplay" class="text-amber-300">0 ₺</strong></div>
                    <div class="sale-summary-row"><span>KDV Toplam</span><strong id="kdvDisplay">0 ₺</strong></div>
                    <div class="sale-summary-total">
                        <span>Genel Toplam</span>
                        <span id="grandTotalDisplay">0 ₺</span>
                    </div>
                </div>

                <div class="sale-form-section">
                    <div class="sale-form-section-body space-y-3">
                        <button type="submit" class="btn-primary w-full justify-center min-h-[48px]">Teklif Oluştur</button>
                        <a href="{{ route('quotes.index') }}" class="btn-secondary w-full justify-center min-h-[44px]">İptal</a>
                        <p class="text-center text-xs text-neutral-400">Mobil özet: <strong id="stickyTotal" class="text-neutral-700">0 ₺</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Hızlı Ürün/Hizmet Ekle Modal --}}
    <div x-show="showQuickAddProduct" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quick-add-product-title">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showQuickAddProduct = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 pt-5 pb-1">
                <h2 id="quick-add-product-title" class="text-lg font-semibold text-neutral-900">Hızlı Ürün / Hizmet Ekle</h2>
            </div>
            <form @submit.prevent="quickAddProduct()" class="p-5 space-y-4">
                <div>
                    <label class="form-label">Ad <span class="text-red-500">*</span></label>
                    <input type="text" x-model="quickProduct.name" required class="form-input min-h-[44px]" placeholder="Örn: Montaj hizmeti">
                </div>
                <div>
                    <label class="form-label">Birim fiyat (₺) <span class="text-red-500">*</span></label>
                    <input type="text" inputmode="decimal" data-input="money" x-model="quickProduct.unitPrice" required class="form-input min-h-[44px]" placeholder="0" autocomplete="off">
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
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 pt-5 pb-1">
                <h2 id="quick-add-customer-title" class="text-lg font-semibold text-neutral-900">Hızlı Müşteri Ekle</h2>
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
                    @include('partials.address-fields-alpine')
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
        quickCustomer: { name: '', phone: '', email: '', address: '', cityId: '', districtId: '' },
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
                    customersQuote.push({ id: data.id, name: data.name, phone: data.phone || '', email: data.email || '', address: data.address || '', taxNumber: data.taxNumber || '', taxOffice: data.taxOffice || '', identityNumber: data.identityNumber || '' });
                    if (window.customerQuoteTomSelect) {
                        window.customerQuoteTomSelect.addOption({ value: data.id, text: data.name });
                        window.customerQuoteTomSelect.setValue(data.id);
                    }
                    this.showQuickAddCustomer = false;
                    this.quickCustomer = { name: '', phone: '', email: '', address: '', cityId: '', districtId: '' };
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
                    body: JSON.stringify({ name: this.quickProduct.name, unitPrice: (window.parseMoney || parseFloat)(this.quickProduct.unitPrice) || 0, kdvRate: parseFloat(this.quickProduct.kdvRate) || 18 })
                });
                const data = await res.json();
                if (res.ok) {
                    const text = data.name + ' (' + fmt(data.price) + ' ₺)';
                    productsQuoteData.push({ id: String(data.id), name: text, price: data.price, kdv: data.kdv, image: data.image || null });
                    const tmplSelect = document.getElementById('quote-item-template')?.content?.querySelector('.item-product');
                    if (tmplSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.setAttribute('data-price', data.price);
                        opt.setAttribute('data-kdv', data.kdv);
                        if (data.image) opt.setAttribute('data-image', data.image);
                        opt.textContent = text;
                        tmplSelect.appendChild(opt);
                    }
                    (window.quoteProductSelects || []).forEach(function(ts) {
                        if (ts) ts.addOption({ value: String(data.id), text: text });
                    });
                    const targetRow = resolveQuoteRowForQuickProduct(window.quickAddQuoteProductForRowIndex ?? 0);
                    applyProductToQuoteRow(targetRow, data, text);
                    targetRow.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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
function parseTrNum(s) {
    if (s == null || s === '') return NaN;
    const t = String(s).replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
    return parseFloat(t) || NaN;
}
function formatPriceInput(inp) {
    if (!inp || !inp.classList.contains('item-price')) return;
    const v = parseTrNum(inp.value);
    if (!isNaN(v) && v >= 0) inp.value = fmt(v);
}
function updateQuoteTotals() {
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    let subtotalBeforeDisc = 0, totalDiscountPct = 0, totalDiscountAmt = 0, subtotal = 0, kdvTotal = 0;
    document.querySelectorAll('#items .item-row').forEach(row => {
        const price = parseTrNum(row.querySelector('.item-price')?.value || 0);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 18, 10);
        const discPct = parseFloat(row.querySelector('.item-disc-pct')?.value || 0, 10);
        const discAmt = parseFloat(row.querySelector('.item-disc-amt')?.value || 0, 10);
        if (price <= 0 || qty <= 0) return;
        let lineBeforeDisc;
        if (kdvIncl) {
            lineBeforeDisc = price * qty / (1 + kdv / 100);
        } else {
            lineBeforeDisc = price * qty;
        }
        const lineDiscPct = lineBeforeDisc * (discPct / 100);
        const lineDiscAmt = discAmt;
        let lineTotal = lineBeforeDisc - lineDiscPct - lineDiscAmt;
        lineTotal = Math.max(0, lineTotal);
        const lineKdv = lineTotal * (kdv / 100);
        subtotalBeforeDisc += lineBeforeDisc;
        totalDiscountPct += lineDiscPct;
        totalDiscountAmt += lineDiscAmt;
        subtotal += lineTotal;
        kdvTotal += lineKdv;
        const lineEl = row.querySelector('.item-line-total');
        if (lineEl) lineEl.textContent = fmt(lineTotal + lineKdv) + ' ₺';
    });
    const beforeDiscEl = document.getElementById('quoteSubtotalBeforeDiscDisplay');
    if (beforeDiscEl) {
        beforeDiscEl.textContent = fmt(subtotalBeforeDisc) + ' ₺';
        const discPctRow = document.getElementById('quoteDiscountPctRow');
        const discPctDisp = document.getElementById('quoteDiscountPctDisplay');
        const discAmtRow = document.getElementById('quoteDiscountAmtRow');
        const discAmtDisp = document.getElementById('quoteDiscountAmtDisplay');
        if (discPctRow && discPctDisp) {
            discPctRow.classList.toggle('hidden', totalDiscountPct <= 0);
            discPctDisp.textContent = '-' + fmt(totalDiscountPct) + ' ₺';
        }
        if (discAmtRow && discAmtDisp) {
            discAmtRow.classList.toggle('hidden', totalDiscountAmt <= 0);
            discAmtDisp.textContent = '-' + fmt(totalDiscountAmt) + ' ₺';
        }
        document.getElementById('kdvDisplay').textContent = fmt(kdvTotal) + ' ₺';
        const grand = fmt(subtotal + kdvTotal) + ' ₺';
        document.getElementById('grandTotalDisplay').textContent = grand;
        const sticky = document.getElementById('stickyTotal');
        if (sticky) sticky.textContent = grand;
    }
    const badge = document.getElementById('quoteItemCountBadge');
    if (badge) badge.textContent = String(document.querySelectorAll('#items .item-row').length);
}
window.openQuickAddQuoteProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddQuoteProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
function quoteRowIsEmpty(row) {
    if (!row) return true;
    const tsVal = row.querySelector('.item-product')?.tomselect?.getValue();
    const price = parseTrNum(row.querySelector('.item-price')?.value || 0);
    return !tsVal && !(price > 0);
}
function resolveQuoteRowForQuickProduct(rowIndex) {
    const rows = document.querySelectorAll('#items .item-row');
    let row = rows[rowIndex] || null;
    if (!row || !quoteRowIsEmpty(row)) {
        row = addQuoteRow(false);
    }
    return row;
}
function applyProductToQuoteRow(rowEl, data, text) {
    if (!rowEl || !data) return;
    const id = String(data.id);
    const price = parseFloat(data.price) || 0;
    const kdv = parseFloat(data.kdv) ?? 18;
    const priceEl = rowEl.querySelector('.item-price');
    const kdvEl = rowEl.querySelector('.item-kdv');
    const qtyEl = rowEl.querySelector('.item-qty');
    if (priceEl) priceEl.value = fmt(price);
    if (kdvEl) kdvEl.value = kdv;
    if (qtyEl && (!qtyEl.value || parseInt(qtyEl.value, 10) < 1)) qtyEl.value = '1';
    const ts = rowEl.querySelector('.item-product')?.tomselect;
    if (ts) {
        if (!ts.options[id]) ts.addOption({ value: id, text: text });
        ts.setValue(id, true);
    }
}
let quoteIdx = 0;
function removeQuoteRow(btn) {
    const container = document.getElementById('items');
    const rows = container.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    const row = btn.closest('.item-row');
    if (!row) return;
    const ts = row.querySelector('.item-product')?.tomselect;
    if (ts) ts.destroy();
    row.remove();
    reindexQuoteRows();
    updateQuoteTotals();
}
function reindexQuoteRows() {
    const container = document.getElementById('items');
    const total = container.querySelectorAll('.item-row').length;
    container.querySelectorAll('.item-row').forEach((row, i) => {
        row.setAttribute('data-row-idx', String(i));
        row.querySelectorAll('[name]').forEach(el => {
            if (el.name) el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
        });
        const no = row.querySelector('.row-no');
        if (no) no.textContent = String(i + 1);
        const removeBtn = row.querySelector('.btn-remove-row');
        if (removeBtn) removeBtn.style.visibility = total <= 1 ? 'hidden' : '';
    });
    if (window.quoteProductSelects) {
        const arr = [];
        container.querySelectorAll('.item-product').forEach((sel, i) => { arr[i] = sel.tomselect; });
        window.quoteProductSelects = arr;
    }
}
function addQuoteRow(focusNew) {
    const tmpl = document.getElementById('quote-item-template');
    if (!tmpl) return null;
    const c = tmpl.content.cloneNode(true);
    c.querySelectorAll('[name]').forEach(e => { e.name = e.name.replace(/__IDX__/g, quoteIdx); });
    const row = c.querySelector('.item-row');
    if (row) row.setAttribute('data-row-idx', String(quoteIdx));
    c.querySelector('.item-price').value = '';
    c.querySelector('.item-qty').value = '1';
    c.querySelector('.item-kdv').value = '18';
    c.querySelector('.item-disc-pct').value = '';
    c.querySelector('.item-disc-amt').value = '';
    document.getElementById('items').appendChild(c);
    const rowEl = document.getElementById('items').lastElementChild;
    const sel = rowEl.querySelector('.item-product');
    initQuoteProductSelect(sel, quoteIdx);
    quoteIdx++;
    reindexQuoteRows();
    updateQuoteTotals();
    if (focusNew) {
        const ts = sel && sel.tomselect;
        if (ts) ts.focus();
        rowEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    return rowEl;
}
function initQuoteProductSelect(sel, rowIdx) {
    if (!sel || typeof TomSelect === 'undefined') return;
    window.quoteProductSelects = window.quoteProductSelects || [];
    const row = sel.closest('.item-row');
    const ts = new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Ürün ara veya seçin...',
        searchField: ['text'],
        dropdownParent: 'body',
        onDropdownOpen: function() {
            const rect = this.control.getBoundingClientRect();
            const viewportH = window.innerHeight || document.documentElement.clientHeight;
            if (rect.bottom > viewportH - 220) { this.dropdown.classList.add('dropup'); }
        },
        onDropdownClose: function() { this.dropdown.classList.remove('dropup'); },
        render: {
            item: function(data, escape) {
                const p = productsQuoteData.find(x => String(x.id) === String(data.value));
                const img = p?.image;
                const imgHtml = img ? '<img src="' + escape(img) + '" alt="" class="w-8 h-8 object-cover rounded shrink-0 mr-2" onerror="this.style.display=\'none\'">' : '';
                return '<div class="flex items-center gap-2 min-w-0"><span class="shrink-0">' + imgHtml + '</span><span class="truncate">' + escape(data.text) + '</span></div>';
            },
            option: function(data, escape) {
                const p = productsQuoteData.find(x => String(x.id) === String(data.value));
                const img = p?.image;
                const imgHtml = img ? '<img src="' + escape(img) + '" alt="" class="w-8 h-8 object-cover rounded shrink-0 mr-2" onerror="this.style.display=\'none\'">' : '';
                return '<div class="flex items-center gap-2">' + imgHtml + '<span>' + escape(data.text) + '</span></div>';
            }
        },
        onChange: function(v) {
            if (!v) return;
            const opt = Array.from(sel.options).find(o => o.value === v);
            if (opt?.dataset?.price) {
                const priceNum = parseFloat(opt.dataset.price) || 0;
                row.querySelector('.item-price').value = fmt(priceNum);
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
function updateQuoteCustomerInfo(customerId) {
    const box = document.getElementById('quoteCustomerInfoBox');
    if (!box) return;
    if (!customerId) {
        box.classList.add('hidden');
        return;
    }
    const c = customersQuote.find(x => String(x.id) === String(customerId));
    if (!c) {
        box.classList.add('hidden');
        return;
    }
    box.classList.remove('hidden');
    document.getElementById('quoteCustomerName').textContent = c.name || '—';
    const setRow = (id, val) => {
        const row = document.getElementById('quoteCustomer' + id + 'Row');
        const el = document.getElementById('quoteCustomer' + id);
        if (!row || !el) return;
        const v = val || '—';
        el.textContent = v;
        row.classList.toggle('hidden', v === '—');
    };
    setRow('Phone', c.phone);
    setRow('Email', c.email);
    setRow('Address', c.address);
    const taxParts = [c.identityNumber, c.taxNumber, c.taxOffice].filter(Boolean);
    setRow('Tax', taxParts.length ? taxParts.join(' · ') : null);
}
function initQuoteForm() {
    const customerSel = document.getElementById('customerSelect');
    if (customerSel && typeof TomSelect !== 'undefined') {
        window.customerQuoteTomSelect = new TomSelect(customerSel, {
            maxOptions: 100,
            placeholder: 'Müşteri ara veya seçin...',
            searchField: ['text'],
            onChange: function(v) { updateQuoteCustomerInfo(v); }
        });
        setTimeout(function() { updateQuoteCustomerInfo(window.customerQuoteTomSelect?.getValue()); }, 0);
    }
    addQuoteRow();
    document.getElementById('quoteForm')?.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-price')) formatPriceInput(e.target);
        updateQuoteTotals();
    });
    document.getElementById('quoteForm')?.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-price')) formatPriceInput(e.target);
        updateQuoteTotals();
    });
    document.getElementById('quoteForm')?.addEventListener('submit', function() {
        document.querySelectorAll('.item-price').forEach(function(inp) {
            const v = parseTrNum(inp.value);
            inp.value = isNaN(v) || v < 0 ? '' : String(v);
        });
    });
    updateQuoteTotals();
}
</script>
@endsection
