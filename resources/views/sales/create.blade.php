@extends('layouts.app')
@section('title', 'Yeni Satış')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
.form-create-section { padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
.form-create-section:last-of-type { border-bottom: 0; }
.dark .form-create-section { border-color: #334155; }
.form-create-section-title { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; }
.dark .form-create-section-title { color: #94a3b8; }
.items-scroll-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
.sale-item-card { background: #fff; border-radius: 0.5rem; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; margin-bottom: 0.75rem; min-width: 0; }
@media (max-width: 1199px) { .sale-item-card { min-width: 520px; } }
.dark .sale-item-card { background: #334155; border-color: #475569; }
.sale-item-card .form-label { margin-bottom: 0.25rem; white-space: nowrap; font-size: 0.75rem; }
.sale-item-card .form-input, .sale-item-card .form-select { min-width: 0; padding: 0.5rem 0.625rem; min-height: 38px !important; }
.sale-totals-box { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 0.75rem; padding: 0.75rem 1rem; border: 1px solid #a7f3d0; }
.dark .sale-totals-box { background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(5,150,105,0.2) 100%); border-color: #047857; }
.form-actions-sticky { padding: 1rem 0; margin: 0 -1.5rem -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; background: #fff; border-top: 1px solid #f1f5f9; }
.dark .form-actions-sticky { background: #1e293b; border-color: #334155; }
@media (min-width: 768px) { .form-actions-sticky { margin: 0; padding: 0; border: 0; background: transparent; } }
@media (max-width: 767px) { .form-actions-sticky { margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; margin-bottom: -1rem; padding-bottom: 1rem; padding-bottom: max(1rem, env(safe-area-inset-bottom)); } }
.ts-wrapper .ts-control .item { display: flex; align-items: center; gap: 0.5rem; }
.ts-wrapper .ts-control .item img { flex-shrink: 0; }
.ts-dropdown.dropup { bottom: 100%; top: auto !important; margin-top: 0; margin-bottom: 4px; }
</style>
@endpush
@section('content')
<div class="mb-6 md:mb-8">
    <nav class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('sales.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Satışlar</a>
        <span aria-hidden="true">/</span>
        <span class="text-slate-700 dark:text-slate-300">Yeni Satış</span>
    </nav>
    <h1 class="page-title">Yeni Satış</h1>
    <p class="page-desc">Doğrudan satış faturası oluşturun</p>
</div>

<div class="w-full max-w-6xl mx-auto px-1" x-data="salesCreateForm()" @open-quick-add-product.window="showQuickAddProduct = true">
    <form method="POST" action="{{ route('sales.store') }}" id="saleForm" @submit="submitting = true">
        @csrf
        @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
        @endif

        <div class="card overflow-hidden">
            {{-- Genel bilgiler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pt-6">
                <h2 class="form-create-section-title">Genel bilgiler</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
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
                        <div id="customerInfoBox" class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-700/60 hidden">
                            <p id="customerName" class="text-base font-semibold text-slate-800 dark:text-slate-100 mb-3">—</p>
                            <div class="space-y-2.5 text-sm">
                                <div id="customerPhoneRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">Telefon</span><span id="customerPhone" class="text-slate-700 dark:text-slate-200">—</span></div>
                                <div id="customerEmailRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">E-posta</span><span id="customerEmail" class="text-slate-700 dark:text-slate-200">—</span></div>
                                <div id="customerAddressRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">Adres</span><span id="customerAddress" class="text-slate-700 dark:text-slate-200">—</span></div>
                                <div id="customerTaxRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">Vergi</span><span id="customerTax" class="text-slate-700 dark:text-slate-200">—</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tarih --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <h2 class="form-create-section-title">Tarih</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Satış Tarihi <span class="text-red-500">*</span></label>
                        <input type="date" name="saleDate" required value="{{ old('saleDate', date('Y-m-d')) }}" class="form-input min-h-[44px] md:min-h-[42px]">
                        @error('saleDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Kalemler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pb-6">
                <div class="form-items-section-box">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <h2 class="form-create-section-title mb-0">Satış kalemleri</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 sm:max-w-sm">Ürün veya hizmet seçin veya yazarak ekleyin.</p>
                    </div>
                    <template id="item-template">
                    <div class="item-row sale-item-card" data-row-idx="__IDX__">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2 lg:gap-2 xl:gap-3 lg:items-end">
                            <div class="sm:col-span-2 lg:col-span-3 flex gap-1.5">
                                <div class="flex-1 min-w-0 item-product-wrap">
                                    <label class="form-label">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                    <select class="form-select item-product" data-placeholder="Ara veya yaz (örn. montaj)...">
                                        <option value="">— Manuel gir —</option>
                                        @foreach($products as $p)
                                        @php $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null); @endphp
                                        <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}" data-image="{{ $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : '' }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" class="item-product-id" name="items[__IDX__][productId]" value="">
                                    <input type="hidden" class="item-product-name" name="items[__IDX__][productName]" value="">
                                </div>
                                <button type="button" onclick="window.openQuickAddProduct && window.openQuickAddProduct(this)" class="shrink-0 self-end flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 touch-manipulation" title="Ürün/hizmet hızlı ekle" aria-label="Hızlı ekle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="form-label">Fiyat <span class="text-red-500">*</span></label>
                                <input type="text" inputmode="decimal" name="items[__IDX__][unitPrice]" required class="form-input item-price min-h-[44px] md:min-h-[42px]" placeholder="0" data-raw="" title="Örn: 20.000">
                            </div>
                            <div class="lg:col-span-1">
                                <label class="form-label">Adet <span class="text-red-500">*</span></label>
                                <input type="number" name="items[__IDX__][quantity]" value="1" required min="1" class="form-input item-qty min-h-[44px] md:min-h-[42px]" placeholder="1">
                            </div>
                            <div class="lg:col-span-1">
                                <label class="form-label">İsk. %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][lineDiscountPercent]" value="" class="form-input item-disc-pct min-h-[44px] md:min-h-[42px]" placeholder="0" title="İskonto yüzdesi (opsiyonel)">
                            </div>
                            <div class="lg:col-span-2 border-l border-slate-200 dark:border-slate-600 pl-2 lg:pl-3">
                                <label class="form-label">İsk. ₺</label>
                                <input type="number" step="0.01" min="0" name="items[__IDX__][lineDiscountAmount]" value="" class="form-input item-disc-amt min-h-[44px] md:min-h-[42px]" placeholder="0" title="İskonto tutarı ₺ (opsiyonel)">
                            </div>
                            <div class="lg:col-span-1">
                                <label class="form-label">KDV %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][kdvRate]" value="18" class="form-input item-kdv min-h-[44px] md:min-h-[42px]" placeholder="18">
                            </div>
                            <div class="sm:col-span-2 lg:col-span-2 flex items-end gap-1.5">
                                <button type="button" onclick="removeSaleRow(this)" class="btn-remove-row w-8 h-8 flex items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/60 text-sm touch-manipulation" aria-label="Satır sil" title="Kalem sil">−</button>
                                <button type="button" onclick="addRow()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 text-sm touch-manipulation" aria-label="Satır ekle" title="Kalem ekle">+</button>
                            </div>
                        </div>
                    </div>
                    </template>
                    <div id="items" class="space-y-3 items-scroll-wrapper"></div>
                    <div class="mt-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                        <div class="max-w-xs">
                            <label class="form-label">KDV</label>
                            <select name="kdvIncluded" class="form-select min-h-[44px] md:min-h-[42px]">
                                <option value="1" {{ old('kdvIncluded', '1') == '1' ? 'selected' : '' }}>KDV Dahil</option>
                                <option value="0" {{ old('kdvIncluded') === '0' ? 'selected' : '' }}>KDV Hariç</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Birim fiyat KDV dahil mi?</p>
                        </div>
                        <div id="saleTotals" class="sale-totals-box w-full lg:w-64 shrink-0">
                            <div class="space-y-1.5 text-sm">
                                <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">Ara Toplam</span> <span id="subtotalBeforeDiscDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                                <div id="saleDiscountPctRow" class="flex justify-between hidden"><span class="text-slate-600 dark:text-slate-300">İsk. % Toplam</span> <span id="saleDiscountPctDisplay" class="font-medium text-amber-600 dark:text-amber-400">0 ₺</span></div>
                                <div id="saleDiscountAmtRow" class="flex justify-between hidden"><span class="text-slate-600 dark:text-slate-300">İsk. ₺ Toplam</span> <span id="saleDiscountAmtDisplay" class="font-medium text-amber-600 dark:text-amber-400">0 ₺</span></div>
                                <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">KDV Toplam</span> <span id="kdvDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                                <div class="flex justify-between pt-2 border-t border-emerald-200 dark:border-emerald-800"><span class="font-medium text-slate-800 dark:text-slate-200">Genel Toplam</span> <span id="grandTotalDisplay" class="font-semibold text-emerald-700 dark:text-emerald-300 text-lg">0 ₺</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notlar --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <label class="form-label">Notlar</label>
                <textarea name="notes" rows="2" class="form-input form-textarea" placeholder="Opsiyonel not...">{{ old('notes') }}</textarea>
            </div>

            {{-- Aksiyonlar --}}
            <div class="form-actions-sticky px-4 sm:px-6 lg:px-8 pt-5 pb-6 md:pb-6">
                <div class="flex flex-col-reverse sm:flex-row gap-3 sm:gap-2">
                    <a href="{{ route('sales.index') }}" class="btn-secondary justify-center min-h-[44px] md:min-h-[42px] order-2 sm:order-1">İptal</a>
                    <button type="submit" :disabled="submitting" class="btn-primary justify-center min-h-[44px] md:min-h-[42px] disabled:opacity-70 disabled:cursor-not-allowed order-1 sm:order-2 flex-1 sm:flex-initial">
                        <span x-show="submitting" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" aria-hidden="true"></span>
                        <span x-text="submitting ? 'Oluşturuluyor...' : 'Satış Oluştur'">Satış Oluştur</span>
                    </button>
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
    <div x-show="showQuickAddCustomer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showQuickAddCustomer = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 pt-5 pb-1">
                <h2 id="quick-add-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Hızlı Müşteri Ekle</h2>
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
                <p x-show="quickAddError" x-text="quickAddError" class="text-sm text-red-600 dark:text-red-400"></p>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" @click="showQuickAddCustomer = false" class="btn-secondary min-h-[44px]">İptal</button>
                    <button type="submit" :disabled="quickAddLoading" class="btn-primary min-h-[44px] disabled:opacity-70">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
@php
    $customersJson = $customers->map(fn($c) => [
        'id' => $c->id, 'name' => $c->name,
        'phone' => $c->phone ?? '', 'email' => $c->email ?? '', 'address' => $c->address ?? '',
        'taxNumber' => $c->taxNumber ?? '', 'taxOffice' => $c->taxOffice ?? '', 'identityNumber' => $c->identityNumber ?? ''
    ])->values();
    $productsJson = $products->map(function($p) {
        $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null);
        return ['id' => $p->id, 'name' => $p->name . ' (' . number_format($p->unitPrice, 0, ',', '.') . ' ₺)', 'price' => (float)$p->unitPrice, 'kdv' => (float)($p->kdvRate ?? 18), 'image' => $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : null];
    })->values();
@endphp
const customers = @json($customersJson);
const productsData = @json($productsJson);
function salesCreateForm() {
    return {
        customerId: '{{ old("customerId") }}',
        showQuickAddCustomer: false,
        quickCustomer: { name: '', phone: '', email: '', address: '' },
        quickAddLoading: false,
        quickAddError: '',
        showQuickAddProduct: false,
        quickProduct: { name: '', unitPrice: '', kdvRate: '18' },
        quickAddProductLoading: false,
        quickAddProductError: '',
        submitting: false,
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
                    productsData.push({ id: String(data.id), name: text, price: data.price, kdv: data.kdv, image: data.image || null });
                    const tmplSelect = document.getElementById('item-template')?.content?.querySelector('.item-product');
                    if (tmplSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.setAttribute('data-price', data.price);
                        opt.setAttribute('data-kdv', data.kdv);
                        if (data.image) opt.setAttribute('data-image', data.image);
                        opt.textContent = text;
                        tmplSelect.appendChild(opt);
                    }
                    (window.salesProductSelects || []).forEach(function(ts, i) {
                        if (ts) {
                            ts.addOption({ value: String(data.id), text: text });
                            if (i === (window.quickAddProductForRowIndex || 0)) {
                                ts.setValue(data.id);
                            }
                        }
                    });
                    this.showQuickAddProduct = false;
                    this.quickProduct = { name: '', unitPrice: '', kdvRate: '18' };
                    updateSaleTotals();
                } else {
                    this.quickAddProductError = data.message || 'Hata oluştu';
                }
            } catch (e) {
                this.quickAddProductError = 'Bağlantı hatası';
            }
            this.quickAddProductLoading = false;
        },
        async quickAddCustomer() {
            this.quickAddError = '';
            this.quickAddLoading = true;
            try {
                const res = await fetch('{{ route("api.customers.quick-store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.quickCustomer)
                });
                const data = await res.json();
                if (res.ok) {
                    customers.push({ id: data.id, name: data.name, phone: data.phone || '', email: data.email || '', address: data.address || '', taxNumber: data.taxNumber || '', taxOffice: data.taxOffice || '', identityNumber: '' });
                    if (window.customerTomSelect) {
                        window.customerTomSelect.addOption({ value: data.id, text: data.name });
                        window.customerTomSelect.setValue(data.id);
                    }
                    this.customerId = data.id;
                    this.showQuickAddCustomer = false;
                    this.quickCustomer = { name: '', phone: '', email: '', address: '' };
                } else {
                    this.quickAddError = data.message || 'Hata oluştu';
                }
            } catch (e) {
                this.quickAddError = 'Bağlantı hatası';
            }
            this.quickAddLoading = false;
        }
    };
}
let idx = 0;
function removeSaleRow(btn) {
    const container = document.getElementById('items');
    const rows = container.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    const row = btn.closest('.item-row');
    if (!row) return;
    const ts = row.querySelector('.item-product')?.tomselect;
    if (ts) ts.destroy();
    row.remove();
    reindexSaleRows();
    updateSaleTotals();
}
function reindexSaleRows() {
    const container = document.getElementById('items');
    container.querySelectorAll('.item-row').forEach((row, i) => {
        row.setAttribute('data-row-idx', String(i));
        row.querySelectorAll('[name]').forEach(el => {
            if (el.name) el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
        });
        const removeBtn = row.querySelector('.btn-remove-row');
        if (removeBtn) removeBtn.style.visibility = container.querySelectorAll('.item-row').length <= 1 ? 'hidden' : '';
    });
    if (window.salesProductSelects) {
        const arr = [];
        container.querySelectorAll('.item-product').forEach((sel, i) => { arr[i] = sel.tomselect; });
        window.salesProductSelects = arr;
    }
}
function addRow() {
    const tmpl = document.getElementById('item-template');
    if (!tmpl) return;
    const c = tmpl.content.cloneNode(true);
    c.querySelectorAll('[name]').forEach(e => {
        e.name = e.name.replace(/__IDX__/g, idx);
    });
    const row = c.querySelector('.item-row');
    if (row) row.setAttribute('data-row-idx', String(idx));
    c.querySelector('.item-price').value = '';
    c.querySelector('.item-qty').value = '1';
    c.querySelector('.item-kdv').value = '18';
    c.querySelector('.item-disc-pct').value = '';
    c.querySelector('.item-disc-amt').value = '';
    document.getElementById('items').appendChild(c);
    const rowEl = document.getElementById('items').lastElementChild;
    const sel = rowEl.querySelector('.item-product');
    initProductSelect(sel, idx);
    idx++;
    reindexSaleRows();
}
window.openQuickAddProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
function initProductSelect(sel, rowIdx) {
    if (!sel || typeof TomSelect === 'undefined') return;
    window.salesProductSelects = window.salesProductSelects || [];
    const idInput = sel.closest('.item-row').querySelector('.item-product-id');
    const nameInput = sel.closest('.item-row').querySelector('.item-product-name');
    const ts = new TomSelect(sel, {
        create: true,
        createOnBlur: true,
        maxOptions: 100,
        placeholder: 'Ara veya yaz (örn. montaj hizmeti)...',
        searchField: ['text'],
        dropdownParent: 'body',
        onDropdownOpen: function() {
            const rect = this.control.getBoundingClientRect();
            const viewportH = window.innerHeight || document.documentElement.clientHeight;
            if (rect.bottom > viewportH - 220) { this.dropdown.classList.add('dropup'); }
        },
        onDropdownClose: function() { this.dropdown.classList.remove('dropup'); },
        render: {
            option_create: (data, escape) => '<div class="create">+ "' + escape(data.input) + '" olarak ekle</div>',
            item: function(data, escape) {
                const p = productsData.find(x => String(x.id) === String(data.value));
                const img = p?.image;
                const imgHtml = img ? '<img src="' + escape(img) + '" alt="" class="w-8 h-8 object-cover rounded shrink-0 mr-2" onerror="this.style.display=\'none\'">' : '';
                return '<div class="flex items-center gap-2 min-w-0"><span class="shrink-0">' + imgHtml + '</span><span class="truncate">' + escape(data.text) + '</span></div>';
            },
            option: function(data, escape) {
                const p = productsData.find(x => String(x.id) === String(data.value));
                const img = p?.image;
                const imgHtml = img ? '<img src="' + escape(img) + '" alt="" class="w-8 h-8 object-cover rounded shrink-0 mr-2" onerror="this.style.display=\'none\'">' : '';
                return '<div class="flex items-center gap-2">' + imgHtml + '<span>' + escape(data.text) + '</span></div>';
            }
        },
        onItemAdd: function(value) {
            const row = sel.closest('.item-row');
            const opt = Array.from(sel.options).find(o => o.value === value);
            if (opt && opt.dataset.price) {
                const priceNum = parseFloat(opt.dataset.price) || 0;
                row.querySelector('.item-price').value = fmt(priceNum);
                row.querySelector('.item-price').setAttribute('data-raw', String(priceNum));
                row.querySelector('.item-kdv').value = opt.dataset.kdv || 18;
            }
            const product = productsData.find(p => p.id === value);
            if (product) {
                idInput.value = value;
                nameInput.value = '';
            } else {
                idInput.value = '';
                nameInput.value = value;
            }
        },
        onClear: function() {
            idInput.value = '';
            nameInput.value = '';
        }
    });
    ts.on('change', function(v) {
        if (v && v !== '') {
            const product = productsData.find(p => p.id === v || p.id === String(v));
            idInput.value = product ? v : '';
            nameInput.value = product ? '' : v;
        } else {
            idInput.value = '';
            nameInput.value = '';
        }
    });
    window.salesProductSelects[rowIdx] = ts;
}
function fmt(n) { return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0); }
function parseTrNum(s) {
    if (s == null || s === '') return NaN;
    const t = String(s).replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
    return parseFloat(t) || NaN;
}
function formatPriceInput(inp) {
    if (!inp || inp.classList && !inp.classList.contains('item-price')) return;
    const v = parseTrNum(inp.value);
    if (!isNaN(v) && v >= 0) { inp.value = fmt(v); inp.setAttribute('data-raw', String(v)); }
}
function updateSaleTotals() {
    const subtotalEl = document.getElementById('subtotalBeforeDiscDisplay');
    if (!subtotalEl) return;
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    let subtotalBeforeDisc = 0, totalDiscountPct = 0, totalDiscountAmt = 0, subtotal = 0, kdvTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const priceEl = row.querySelector('.item-price');
        const price = parseTrNum(priceEl?.value ?? priceEl?.getAttribute('data-raw') ?? 0);
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
        const lineDisc = lineDiscPct + lineDiscAmt;
        let lineTotal = lineBeforeDisc - lineDisc;
        lineTotal = Math.max(0, lineTotal);
        const lineKdv = lineTotal * (kdv / 100);
        subtotalBeforeDisc += lineBeforeDisc;
        totalDiscountPct += lineDiscPct;
        totalDiscountAmt += lineDiscAmt;
        subtotal += lineTotal;
        kdvTotal += lineKdv;
    });
    document.getElementById('subtotalBeforeDiscDisplay').textContent = fmt(subtotalBeforeDisc) + ' ₺';
    const discPctRow = document.getElementById('saleDiscountPctRow');
    const discPctDisp = document.getElementById('saleDiscountPctDisplay');
    const discAmtRow = document.getElementById('saleDiscountAmtRow');
    const discAmtDisp = document.getElementById('saleDiscountAmtDisplay');
    if (discPctRow && discPctDisp) {
        discPctRow.classList.toggle('hidden', totalDiscountPct <= 0);
        discPctDisp.textContent = '-' + fmt(totalDiscountPct) + ' ₺';
    }
    if (discAmtRow && discAmtDisp) {
        discAmtRow.classList.toggle('hidden', totalDiscountAmt <= 0);
        discAmtDisp.textContent = '-' + fmt(totalDiscountAmt) + ' ₺';
    }
    document.getElementById('kdvDisplay').textContent = fmt(kdvTotal) + ' ₺';
    document.getElementById('grandTotalDisplay').textContent = fmt(subtotal + kdvTotal) + ' ₺';
}
document.getElementById('saleForm')?.addEventListener('input', function(e) {
    if (e.target.classList.contains('item-price')) formatPriceInput(e.target);
    updateSaleTotals();
});
document.getElementById('saleForm')?.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-price')) formatPriceInput(e.target);
    updateSaleTotals();
});
document.getElementById('saleForm')?.addEventListener('submit', function() {
    document.querySelectorAll('.item-price').forEach(function(inp) {
        const v = parseTrNum(inp.value);
        inp.value = isNaN(v) || v < 0 ? '' : String(v);
    });
});
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = function() { initSalesForm(); };
        document.head.appendChild(s);
    } else { initSalesForm(); }
});
function updateCustomerInfo(customerId) {
    const box = document.getElementById('customerInfoBox');
    const phoneEl = document.getElementById('customerPhone');
    const emailEl = document.getElementById('customerEmail');
    const addressEl = document.getElementById('customerAddress');
    const taxEl = document.getElementById('customerTax');
    if (!box) return;
    if (!customerId) {
        box.classList.add('hidden');
        return;
    }
    const c = customers.find(x => String(x.id) === String(customerId));
    if (!c) {
        box.classList.add('hidden');
        return;
    }
    box.classList.remove('hidden');
    document.getElementById('customerName').textContent = c.name || '—';
    const setRow = (id, val) => {
        const row = document.getElementById(id + 'Row');
        const el = document.getElementById(id);
        if (!row || !el) return;
        const v = val || '—';
        el.textContent = v;
        row.classList.toggle('hidden', v === '—');
    };
    setRow('customerPhone', c.phone);
    setRow('customerEmail', c.email);
    setRow('customerAddress', c.address);
    const taxParts = [c.identityNumber, c.taxNumber, c.taxOffice].filter(Boolean);
    setRow('customerTax', taxParts.length ? taxParts.join(' · ') : null);
}
function initSalesForm() {
    const customerSel = document.getElementById('customerSelect');
    if (customerSel) {
        window.customerTomSelect = new TomSelect(customerSel, {
            maxOptions: 100,
            placeholder: 'Müşteri ara veya seçin...',
            searchField: ['text'],
            onChange: function(v) { updateCustomerInfo(v); }
        });
        setTimeout(function() { updateCustomerInfo(window.customerTomSelect?.getValue()); }, 0);
    }
    addRow();
    updateSaleTotals();
}
</script>
@endsection
