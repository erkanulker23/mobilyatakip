@extends('layouts.app')
@section('title', 'Yeni Alış')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
.form-create-section { padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
.form-create-section:last-of-type { border-bottom: 0; }
.dark .form-create-section { border-color: #334155; }
.form-create-section-title { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; }
.dark .form-create-section-title { color: #94a3b8; }
.purchase-item-card { background: #fff; border-radius: 0.5rem; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; margin-bottom: 0.75rem; min-width: 0; }
@media (max-width: 1279px) { .purchase-item-card { min-width: 540px; } }
.dark .purchase-item-card { background: #334155; border-color: #475569; }
.items-scroll-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
.purchase-item-card .form-label { margin-bottom: 0.25rem; white-space: nowrap; font-size: 0.75rem; }
.purchase-item-card .form-input, .purchase-item-card .form-select { min-width: 0; width: 100%; padding: 0.5rem 0.625rem; min-height: 38px !important; }
.purchase-totals-box { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 0.75rem; padding: 0.75rem 1rem; border: 1px solid #a7f3d0; }
.dark .purchase-totals-box { background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(5,150,105,0.2) 100%); border-color: #047857; }
.form-actions-sticky { padding: 1rem 0; margin: 0 -1.5rem -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; background: #fff; border-top: 1px solid #f1f5f9; }
.dark .form-actions-sticky { background: #1e293b; border-color: #334155; }
@media (min-width: 768px) { .form-actions-sticky { margin: 0; padding: 0; border: 0; background: transparent; } }
@media (max-width: 767px) { .form-actions-sticky { margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; margin-bottom: -1rem; padding-bottom: max(1rem, env(safe-area-inset-bottom)); } }
.ts-wrapper .ts-control .item { display: flex; align-items: center; gap: 0.5rem; }
.ts-wrapper .ts-control .item img { flex-shrink: 0; }
.ts-dropdown.dropup { bottom: 100%; top: auto !important; margin-top: 0; margin-bottom: 4px; }
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
    $suppliersJson = $suppliers->map(fn($s) => [
        'id' => $s->id, 'name' => $s->name,
        'phone' => $s->phone ?? '', 'email' => $s->email ?? '', 'address' => $s->address ?? '',
        'taxNumber' => $s->taxNumber ?? '', 'taxOffice' => $s->taxOffice ?? ''
    ])->values();
    $productsPurchaseJson = $products->map(function($p) {
        $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null);
        return ['id' => $p->id, 'name' => $p->name . ' (' . number_format($p->unitPrice, 0, ',', '.') . ' ₺)', 'price' => (float)$p->unitPrice, 'kdv' => (float)($p->kdvRate ?? 18), 'image' => $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : null];
    })->values();
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
                        <div id="supplierInfoBox" class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-700/60 hidden">
                            <p id="supplierName" class="text-base font-semibold text-slate-800 dark:text-slate-100 mb-3">—</p>
                            <div class="space-y-2.5 text-sm">
                                <div id="supplierPhoneRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">Telefon</span><span id="supplierPhone" class="text-slate-700 dark:text-slate-200">—</span></div>
                                <div id="supplierEmailRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">E-posta</span><span id="supplierEmail" class="text-slate-700 dark:text-slate-200">—</span></div>
                                <div id="supplierAddressRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">Adres</span><span id="supplierAddress" class="text-slate-700 dark:text-slate-200">—</span></div>
                                <div id="supplierTaxRow" class="flex gap-2"><span class="text-slate-400 dark:text-slate-500 w-20 shrink-0">Vergi</span><span id="supplierTax" class="text-slate-700 dark:text-slate-200">—</span></div>
                            </div>
                        </div>
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

            {{-- Nakliye bilgileri --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <h2 class="form-create-section-title">Nakliye bilgileri</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Malı hangi nakliye firması getirdi? Araç plakası, şoför ve iletişim bilgilerini kaydedin. Nakliye ödemelerini firmaya özel takip edebilirsiniz.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="form-label">Nakliye firması</label>
                        <select name="shippingCompanyId" class="form-select min-h-[44px] md:min-h-[42px]">
                            <option value="">Seçiniz</option>
                            @foreach($shippingCompanies ?? [] as $sc)
                            <option value="{{ $sc->id }}" {{ old('shippingCompanyId') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sisteme tanımlı nakliye firmalarından seçin</p>
                        @error('shippingCompanyId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Araç plakası</label>
                        <input type="text" name="vehiclePlate" value="{{ old('vehiclePlate') }}" class="form-input min-h-[44px] md:min-h-[42px]" placeholder="34 ABC 123">
                        @error('vehiclePlate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Şoför adı</label>
                        <input type="text" name="driverName" value="{{ old('driverName') }}" class="form-input min-h-[44px] md:min-h-[42px]" placeholder="Ahmet Yılmaz">
                        @error('driverName')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Şoför telefonu</label>
                        <input type="tel" name="driverPhone" value="{{ old('driverPhone') }}" class="form-input min-h-[44px] md:min-h-[42px]" placeholder="0555 123 45 67" inputmode="tel">
                        @error('driverPhone')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Not --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8">
                <h2 class="form-create-section-title">Not</h2>
                <textarea name="notes" rows="3" class="form-input form-textarea min-h-[80px]" placeholder="Alışla ilgili notlar, açıklamalar...">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            {{-- Kalemler --}}
            <div class="form-create-section px-4 sm:px-6 lg:px-8 pb-6">
                <div class="form-items-section-box">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <h2 class="form-create-section-title mb-0">Alış kalemleri</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 sm:max-w-sm">Ürün seçin, liste ve iskontolu fiyat girin.</p>
                    </div>
                    <template id="purchase-item-template">
                    <div class="item-row purchase-item-card" data-row-idx="__IDX__">
                        <div class="space-y-4">
                            {{-- Ürün + Fiyat/Adet/KDV satırı — her hücre border ile ayrı --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-11 gap-2 xl:gap-2 xl:items-end">
                                <div class="xl:col-span-3 flex gap-1.5 xl:pr-1">
                                    <div class="flex-1 min-w-0">
                                        <label class="form-label">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                        <select name="items[__IDX__][productId]" required class="form-select item-product" data-placeholder="Ürün ara veya seçin...">
                                            <option value="">Seçiniz</option>
                                            @foreach($products as $p)
                                            @php $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null); @endphp
                                            <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}" data-image="{{ $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : '' }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" onclick="window.openQuickAddPurchaseProduct && window.openQuickAddPurchaseProduct(this)" class="shrink-0 self-end flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 touch-manipulation" title="Ürün/hizmet hızlı ekle" aria-label="Hızlı ekle">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </div>
                                <div class="xl:col-span-2 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-2">
                                    <label class="form-label">Liste fiyatı</label>
                                    <input type="text" inputmode="decimal" name="items[__IDX__][listPrice]" class="form-input item-listprice min-h-[44px] md:min-h-[42px] w-full" placeholder="—" title="Örn: 20.000">
                                </div>
                                <div class="xl:col-span-2 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-2">
                                    <label class="form-label">İskontolu fiyat <span class="text-red-500">*</span></label>
                                    <input type="text" inputmode="decimal" name="items[__IDX__][unitPrice]" required class="form-input item-price min-h-[44px] md:min-h-[42px] w-full" placeholder="0" title="Örn: 20.000">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">Adet <span class="text-red-500">*</span></label>
                                    <input type="number" name="items[__IDX__][quantity]" value="1" required min="1" class="form-input item-qty min-h-[44px] md:min-h-[42px] w-full" placeholder="1">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">KDV %</label>
                                    <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][kdvRate]" value="18" class="form-input item-kdv min-h-[44px] md:min-h-[42px] w-full" placeholder="18">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-4">
                                    <label class="form-label">İskonto oranı %</label>
                                    <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][lineDiscountPercent]" value="" class="form-input item-disc-pct min-h-[44px] md:min-h-[42px] w-full" placeholder="0" title="İskontolu fiyat × adet üzerinden %">
                                </div>
                                <div class="xl:col-span-1 xl:border-l xl:border-slate-200 dark:xl:border-slate-600 xl:pl-2 flex items-end gap-1.5 pb-0.5">
                                    <button type="button" onclick="removePurchaseRow(this)" class="btn-remove-row w-8 h-8 flex items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/60 text-sm touch-manipulation" aria-label="Satır sil" title="Kalem sil">−</button>
                                    <button type="button" onclick="addPurchaseRow()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 text-sm touch-manipulation" aria-label="Satır ekle" title="Kalem ekle">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </template>
                    <div id="items" class="space-y-3 items-scroll-wrapper"></div>
                    <div class="mt-6 flex flex-col lg:flex-row lg:items-start gap-4">
                        <div class="flex-1 min-w-0"></div>
                        <div id="purchaseTotals" class="purchase-totals-box w-full lg:min-w-[280px] shrink-0">
                            <div class="space-y-1.5 text-sm">
                                <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">Ara Toplam</span> <span id="subtotalBeforeDiscDisplay" class="font-medium text-slate-900 dark:text-white">0 ₺</span></div>
                                <div id="purchaseDiscountPctRow" class="flex justify-between hidden"><span class="text-slate-600 dark:text-slate-300">İsk. % Toplam</span> <span id="purchaseDiscountPctDisplay" class="font-medium text-amber-600 dark:text-amber-400">0 ₺</span></div>
                                <div id="purchaseDiscountAmtRow" class="flex justify-between hidden"><span class="text-slate-600 dark:text-slate-300">İsk. ₺ Toplam</span> <span id="purchaseDiscountAmtDisplay" class="font-medium text-amber-600 dark:text-amber-400">0 ₺</span></div>
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
const suppliersPurchaseData = @json($suppliersJson);
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
                    productsPurchaseData.push({ id: String(data.id), name: text, price: data.price, kdv: data.kdv, image: data.image || null });
                    const tmplSelect = document.getElementById('purchase-item-template')?.content?.querySelector('.item-product');
                    if (tmplSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.setAttribute('data-price', data.price);
                        opt.setAttribute('data-kdv', data.kdv);
                        if (data.image) opt.setAttribute('data-image', data.image);
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
function parseTrNum(s) {
    if (s == null || s === '') return NaN;
    const t = String(s).replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
    return parseFloat(t) || NaN;
}
function formatPriceInput(inp) {
    if (!inp || (!inp.classList.contains('item-price') && !inp.classList.contains('item-listprice'))) return;
    const v = parseTrNum(inp.value);
    if (!isNaN(v) && v >= 0) inp.value = fmt(v);
}
function updateTotals() {
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    let subtotal = 0, kdvTotal = 0, totalDiscountPct = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseTrNum(row.querySelector('.item-price')?.value || 0);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 18, 10);
        const discPct = parseFloat(row.querySelector('.item-disc-pct')?.value || 0, 10);
        if (price <= 0 || qty <= 0) return;
        let lineNetBeforeDisc;
        if (kdvIncl) {
            lineNetBeforeDisc = price * qty / (1 + kdv / 100);
        } else {
            lineNetBeforeDisc = price * qty;
        }
        const lineDiscPct = lineNetBeforeDisc * (discPct / 100);
        let lineNet = lineNetBeforeDisc - lineDiscPct;
        lineNet = Math.max(0, lineNet);
        totalDiscountPct += lineDiscPct;
        const lineKdv = lineNet * (kdv / 100);
        subtotal += lineNet;
        kdvTotal += lineKdv;
    });
    document.getElementById('subtotalBeforeDiscDisplay').textContent = fmt(subtotal + totalDiscountPct) + ' ₺';
    const discPctRow = document.getElementById('purchaseDiscountPctRow');
    const discPctDisp = document.getElementById('purchaseDiscountPctDisplay');
    if (discPctRow && discPctDisp) {
        discPctRow.classList.toggle('hidden', totalDiscountPct <= 0);
        discPctDisp.textContent = '-' + fmt(totalDiscountPct) + ' ₺';
    }
    const discAmtRow = document.getElementById('purchaseDiscountAmtRow');
    if (discAmtRow) discAmtRow.classList.add('hidden');
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
function updateSupplierInfo(supplierId) {
    const box = document.getElementById('supplierInfoBox');
    const phoneEl = document.getElementById('supplierPhone');
    const emailEl = document.getElementById('supplierEmail');
    const addressEl = document.getElementById('supplierAddress');
    const taxEl = document.getElementById('supplierTax');
    if (!box) return;
    if (!supplierId) { box.classList.add('hidden'); return; }
    const s = suppliersPurchaseData.find(x => String(x.id) === String(supplierId));
    if (!s) { box.classList.add('hidden'); return; }
    box.classList.remove('hidden');
    document.getElementById('supplierName').textContent = s.name || '—';
    const setRow = (id, val) => {
        const row = document.getElementById(id + 'Row');
        const el = document.getElementById(id);
        if (!row || !el) return;
        const v = val || '—';
        el.textContent = v;
        row.classList.toggle('hidden', v === '—');
    };
    setRow('supplierPhone', s.phone);
    setRow('supplierEmail', s.email);
    setRow('supplierAddress', s.address);
    const taxParts = [s.taxNumber, s.taxOffice].filter(Boolean);
    setRow('supplierTax', taxParts.length ? taxParts.join(' · ') : null);
}
function initPurchaseForm() {
    const sel = document.getElementById('supplierSelect');
    if (sel && typeof TomSelect !== 'undefined') {
        window.purchaseSupplierTomSelect = new TomSelect(sel, {
            maxOptions: 100,
            placeholder: 'Tedarikçi ara veya seçin...',
            searchField: ['text'],
            onChange: function(v) { updateSupplierInfo(v); }
        });
        setTimeout(function() { updateSupplierInfo(window.purchaseSupplierTomSelect?.getValue()); }, 0);
    }
    addPurchaseRow();
    document.getElementById('purchaseForm')?.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-price') || e.target.classList.contains('item-listprice')) formatPriceInput(e.target);
        updateTotals();
    });
    document.getElementById('purchaseForm')?.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-price') || e.target.classList.contains('item-listprice')) formatPriceInput(e.target);
        updateTotals();
    });
    document.getElementById('purchaseForm')?.addEventListener('submit', function() {
        document.querySelectorAll('.item-listprice, .item-price').forEach(function(inp) {
            const v = parseTrNum(inp.value);
            inp.value = isNaN(v) || v < 0 ? '' : String(v);
        });
    });
    updateTotals();
}
window.openQuickAddPurchaseProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddPurchaseProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
let purchaseIdx = 0;
function removePurchaseRow(btn) {
    const container = document.getElementById('items');
    const rows = container.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    const row = btn.closest('.item-row');
    if (!row) return;
    const ts = row.querySelector('.item-product')?.tomselect;
    if (ts) ts.destroy();
    row.remove();
    reindexPurchaseRows();
    updateTotals();
}
function reindexPurchaseRows() {
    const container = document.getElementById('items');
    container.querySelectorAll('.item-row').forEach((row, i) => {
        row.setAttribute('data-row-idx', String(i));
        row.querySelectorAll('[name]').forEach(el => {
            if (el.name) el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
        });
        const removeBtn = row.querySelector('.btn-remove-row');
        if (removeBtn) removeBtn.style.visibility = container.querySelectorAll('.item-row').length <= 1 ? 'hidden' : '';
    });
    if (window.purchaseProductSelects) {
        const arr = [];
        container.querySelectorAll('.item-product').forEach((sel, i) => { arr[i] = sel.tomselect; });
        window.purchaseProductSelects = arr;
    }
}
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
    document.getElementById('items').appendChild(c);
    const prodSel = document.getElementById('items').lastElementChild.querySelector('.item-product');
    initPurchaseProductSelect(prodSel, purchaseIdx);
    purchaseIdx++;
    reindexPurchaseRows();
}
function initPurchaseProductSelect(sel, rowIdx) {
    if (!sel || typeof TomSelect === 'undefined') return;
    window.purchaseProductSelects = window.purchaseProductSelects || [];
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
                const p = productsPurchaseData.find(x => String(x.id) === String(data.value));
                const img = p?.image;
                const imgHtml = img ? '<img src="' + escape(img) + '" alt="" class="w-8 h-8 object-cover rounded shrink-0 mr-2" onerror="this.style.display=\'none\'">' : '';
                return '<div class="flex items-center gap-2 min-w-0"><span class="shrink-0">' + imgHtml + '</span><span class="truncate">' + escape(data.text) + '</span></div>';
            },
            option: function(data, escape) {
                const p = productsPurchaseData.find(x => String(x.id) === String(data.value));
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
                row.querySelector('.item-listprice').value = fmt(priceNum);
                row.querySelector('.item-price').value = fmt(priceNum);
            }
            if (opt?.dataset?.kdv) row.querySelector('.item-kdv').value = opt.dataset.kdv;
            updateTotals();
        }
    });
    window.purchaseProductSelects[rowIdx] = ts;
}
</script>
@endsection
