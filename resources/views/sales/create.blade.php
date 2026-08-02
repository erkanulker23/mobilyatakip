@extends('layouts.app')
@section('title', 'Yeni Satış')
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
.sale-items-table-head { display: none; }
@media (min-width: 1024px) {
    .sale-items-table-head { display: grid; grid-template-columns: 2rem 1fr 7rem 5rem 5rem 7rem 2.5rem; gap: 0.75rem; padding: 0 0.25rem 0.5rem; font-size: 0.6875rem; font-weight: 600; color: #a3a3a3; text-transform: uppercase; letter-spacing: .06em; }
}
.sale-item-row { border: 1px solid #e5e5e5; border-radius: 0.75rem; padding: 1rem; background: #fafafa; transition: border-color .15s, box-shadow .15s; }
.sale-item-row:focus-within { border-color: #a3a3a3; box-shadow: 0 0 0 3px rgba(0,0,0,.04); background: #fff; }
.dark .sale-item-row { background: #262626; border-color: #404040; }
.dark .sale-item-row:focus-within { background: #171717; border-color: #525252; }
.sale-item-row .form-label { margin-bottom: 0.25rem; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .04em; color: #737373; }
.sale-item-row .form-input, .sale-item-row .form-select { min-height: 40px; padding: 0.5rem 0.625rem; font-size: 0.875rem; }
.row-no { flex-shrink: 0; width: 1.75rem; height: 1.75rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; background: #171717; color: #fff; font-size: 0.75rem; font-weight: 600; }
.dark .row-no { background: #404040; }
.item-line-total { font-variant-numeric: tabular-nums; font-weight: 600; color: #171717; font-size: 0.9375rem; }
.dark .item-line-total { color: #f5f5f5; }
.icon-btn { flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; border: 1px solid #e5e5e5; background: #fff; color: #525252; transition: background .15s, border-color .15s, color .15s; }
.icon-btn:hover { background: #f5f5f5; color: #171717; }
.icon-btn-danger { border-color: #fecaca; color: #dc2626; background: #fff; }
.icon-btn-danger:hover { background: #fef2f2; }
.add-row-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.875rem; border: 1.5px dashed #d4d4d4; border-radius: 0.75rem; color: #525252; font-size: 0.875rem; font-weight: 500; background: transparent; transition: background .15s, border-color .15s, color .15s; }
.add-row-btn:hover { border-color: #171717; color: #171717; background: #fafafa; }
.sale-summary-panel { background: #171717; color: #fff; border-radius: 1rem; padding: 1.25rem; }
.sale-summary-row { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.875rem; padding: 0.375rem 0; color: #d4d4d4; }
.sale-summary-row strong { color: #fff; font-weight: 600; font-variant-numeric: tabular-nums; }
.sale-summary-total { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #404040; display: flex; justify-content: space-between; align-items: baseline; }
.sale-summary-total span:last-child { font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.sale-meta-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 640px) { .sale-meta-grid { grid-template-columns: 1fr 1fr; } }
.customer-info-panel { margin-top: 1rem; padding: 1rem; border-radius: 0.75rem; background: #fafafa; border: 1px solid #e5e5e5; }
.dark .customer-info-panel { background: #262626; border-color: #404040; }
.customer-info-panel dt { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .04em; color: #a3a3a3; }
.customer-info-panel dd { font-size: 0.875rem; color: #171717; margin-top: 0.125rem; }
.dark .customer-info-panel dd { color: #f5f5f5; }
.kbd { display: inline-block; padding: 0.05rem 0.35rem; border-radius: 0.25rem; border: 1px solid #e5e5e5; background: #fafafa; font-size: 0.6875rem; font-family: ui-monospace, monospace; color: #737373; }
.ts-wrapper .ts-control .item { display: flex; align-items: center; gap: 0.5rem; }
.ts-wrapper .ts-control .item img { flex-shrink: 0; }
.ts-dropdown.dropup { bottom: 100%; top: auto !important; margin-top: 0; margin-bottom: 4px; }
</style>
@endpush
@section('content')
<div class="mb-6">
    <nav class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('sales.index') }}" class="hover:text-neutral-900 transition-colors">Satışlar</a>
        <span aria-hidden="true">/</span>
        <span class="text-neutral-700">Yeni Satış</span>
    </nav>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="page-title">Yeni Satış</h1>
            <p class="page-desc">Kurumsal satış faturası oluşturun</p>
        </div>
        <a href="{{ route('sales.index') }}" class="btn-secondary text-sm self-start">← Satış listesi</a>
    </div>
</div>

<div class="max-w-7xl" x-data="salesCreateForm()" @open-quick-add-product.window="showQuickAddProduct = true">
    <form method="POST" action="{{ route('sales.store') }}" id="saleForm" enctype="multipart/form-data" @submit="submitting = true">
        @csrf
        @if(request('returnTo') === 'service-tickets/create')
        <input type="hidden" name="returnTo" value="service-tickets/create">
        @endif
        @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
            <p class="font-medium mb-1">Formda hatalar var — girdiğiniz bilgiler korundu, lütfen düzeltip tekrar deneyin.</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @if(request('returnTo') === 'service-tickets/create')
        <div class="mb-4 p-4 rounded-xl bg-blue-50 text-blue-800 text-sm border border-blue-100">
            Sipariş kaydedildikten sonra servis kaydı formuna geri döneceksiniz.
            <a href="{{ route('service-tickets.create', ['customerId' => request('customerId')]) }}" class="underline ml-1">Servis kaydına dön</a>
        </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start">
            <div class="space-y-5 min-w-0">
                {{-- Müşteri & meta --}}
                <div class="sale-form-section">
                    <div class="sale-form-section-head">
                        <h2 class="sale-form-section-title">Müşteri & Satış Bilgileri</h2>
                    </div>
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
                                    <button type="button" @click="showQuickAddCustomer = true" class="icon-btn shrink-0 w-11 h-11 touch-manipulation" title="Hızlı müşteri ekle" aria-label="Hızlı müşteri ekle">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </div>
                                @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">Satış Tarihi <span class="text-red-500">*</span></label>
                                <input type="date" name="saleDate" required value="{{ old('saleDate', date('Y-m-d')) }}" class="form-input min-h-[44px]">
                                @error('saleDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">Tahmini Teslim Tarihi</label>
                                <input type="date" name="dueDate" value="{{ old('dueDate') }}" class="form-input min-h-[44px]">
                                @error('dueDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">Satışı Yapan Personel</label>
                                <select name="personnelId" class="form-select min-h-[44px]">
                                    <option value="">Seçiniz</option>
                                    @foreach($personnel as $p)
                                    <option value="{{ $p->id }}" {{ old('personnelId') == $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->title ? ' — ' . $p->title : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <input type="hidden" name="needsFinalMeasurement" value="0">
                                <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors {{ old('needsFinalMeasurement') ? 'border-amber-400 bg-amber-50' : 'border-amber-200 bg-amber-50/60 hover:border-amber-300' }}">
                                    <input type="checkbox" name="needsFinalMeasurement" value="1" class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500" {{ old('needsFinalMeasurement') ? 'checked' : '' }}>
                                    <span>
                                        <span class="block font-semibold text-amber-950">Kesin ölçüye gidilecek</span>
                                        <span class="block text-sm text-amber-900/80 mt-1">Bu sipariş için saha ölçüsü alınacak. Üretim ve teslimat kesin ölçü sonrası planlanır; siparişte ve yazdırmalarda belirgin şekilde görünür.</span>
                                    </span>
                                </label>
                                @error('needsFinalMeasurement')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div id="customerInfoBox" class="customer-info-panel hidden">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <p id="customerName" class="text-sm font-semibold text-neutral-900">—</p>
                                <a href="#" id="customerEditLink" target="_blank" class="text-xs text-neutral-500 hover:text-neutral-900 shrink-0">Müşteri kartı →</a>
                            </div>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div id="customerPhoneRow"><dt>Telefon</dt><dd id="customerPhone">—</dd></div>
                                <div id="customerEmailRow"><dt>E-posta</dt><dd id="customerEmail" class="truncate">—</dd></div>
                                <div id="customerAddressRow" class="sm:col-span-2"><dt>Adres</dt><dd id="customerAddress">—</dd></div>
                                <div id="customerTaxRow"><dt>Vergi</dt><dd id="customerTax">—</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Kalemler --}}
                <div class="sale-form-section">
                    <div class="sale-form-section-head">
                        <div class="flex items-center gap-2">
                            <h2 class="sale-form-section-title">Satış Kalemleri</h2>
                            <span id="itemCountBadge" class="inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 text-xs font-semibold">0</span>
                        </div>
                        <p class="text-xs text-neutral-400 hidden sm:block"><span class="kbd">Enter</span> sonraki · <span class="kbd">⌘</span>+<span class="kbd">Enter</span> kaydet</p>
                    </div>
                    <div class="sale-form-section-body">
                        <div class="sale-items-table-head">
                            <span>#</span><span>Ürün / Hizmet</span><span>Fiyat</span><span>Adet</span><span>KDV</span><span>Toplam</span><span></span>
                        </div>
                    <template id="item-template">
                    <div class="item-row sale-item-row mb-3" data-row-idx="__IDX__">
                        <div class="flex flex-wrap items-start gap-2 mb-2">
                            <span class="row-no" aria-hidden="true">1</span>
                            <div class="item-product-wrap flex-1 min-w-0">
                                <label class="form-label lg:sr-only">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                <select class="form-select item-product" data-placeholder="Ara veya yaz...">
                                    <option value="">— Manuel gir —</option>
                                    @foreach($products as $p)
                                    @php $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null); @endphp
                                    <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 10 }}" data-image="{{ $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : '' }}">{{ $p->name }} ({{ number_format($p->unitPrice, 0, ',', '.') }} ₺)</option>
                                    @endforeach
                                </select>
                                <input type="hidden" class="item-product-id" name="items[__IDX__][productId]" value="">
                                <input type="hidden" class="item-product-name" name="items[__IDX__][productName]" value="">
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" onclick="window.openQuickAddProduct && window.openQuickAddProduct(this)" class="icon-btn touch-manipulation" title="Yeni ürün" aria-label="Yeni ürün">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button type="button" onclick="duplicateSaleRow(this)" class="icon-btn touch-manipulation" title="Çoğalt" aria-label="Çoğalt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                                <button type="button" onclick="removeSaleRow(this)" class="btn-remove-row icon-btn icon-btn-danger touch-manipulation" aria-label="Sil" title="Sil">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div>
                                <label class="form-label">Fiyat ₺</label>
                                <input type="text" inputmode="decimal" name="items[__IDX__][unitPrice]" required class="form-input item-price" placeholder="20.000" data-raw="">
                            </div>
                            <div>
                                <label class="form-label">Adet</label>
                                <input type="number" name="items[__IDX__][quantity]" value="1" required min="1" class="form-input item-qty">
                            </div>
                            <div>
                                <label class="form-label">KDV %</label>
                                <input type="number" step="0.01" min="0" max="100" name="items[__IDX__][kdvRate]" value="10" class="form-input item-kdv">
                            </div>
                            <div class="flex flex-col justify-end text-right">
                                <span class="form-label mb-0">Toplam</span>
                                <span class="item-line-total">0 ₺</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            @include('partials.item-description-fields')
                        </div>
                    </div>
                    </template>
                    @include('partials.item-description-form-assets')
                    <div id="items" class="space-y-0"></div>
                    <button type="button" onclick="addRow(true)" class="add-row-btn mt-3 touch-manipulation">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Kalem Ekle
                    </button>
                    </div>
                </div>

                {{-- İlk tahsilat --}}
                @php
                    $initialPaymentMode = old('initialPaymentMode', 'none');
                @endphp
                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">İlk Tahsilat (Opsiyonel)</h2></div>
                    <div class="sale-form-section-body">
                        <p class="text-sm text-neutral-600 mb-4">Kapora veya siparişin tamamı tahsil edilebilir. Ödeme alınmayacaksa «Ödeme yok» bırakın.</p>
                        <div class="flex flex-wrap gap-2 mb-5">
                            <label class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer text-sm font-medium transition-colors {{ $initialPaymentMode === 'none' ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' }}">
                                <input type="radio" name="initialPaymentMode" value="none" class="sr-only" {{ $initialPaymentMode === 'none' ? 'checked' : '' }}>
                                Ödeme yok
                            </label>
                            <label class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer text-sm font-medium transition-colors {{ $initialPaymentMode === 'kapora' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' }}">
                                <input type="radio" name="initialPaymentMode" value="kapora" class="sr-only" {{ $initialPaymentMode === 'kapora' ? 'checked' : '' }}>
                                Kapora
                            </label>
                            <label class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer text-sm font-medium transition-colors {{ $initialPaymentMode === 'full' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' }}">
                                <input type="radio" name="initialPaymentMode" value="full" class="sr-only" {{ $initialPaymentMode === 'full' ? 'checked' : '' }}>
                                Hepsi ödendi
                            </label>
                        </div>
                        @error('initialPaymentMode')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror

                        <div id="initialPaymentDetails" class="{{ in_array($initialPaymentMode, ['kapora', 'full'], true) ? '' : 'hidden' }}">
                            <div id="depositAmountWrap" class="mb-4 {{ $initialPaymentMode === 'kapora' ? '' : 'hidden' }}">
                                <label class="form-label">Kapora Tutarı (₺)</label>
                                <input type="text" inputmode="decimal" name="depositAmount" id="depositAmount" value="{{ old('depositAmount') ? money(old('depositAmount')) : '' }}" class="form-input min-h-[44px] money-input" placeholder="0" autocomplete="off">
                                @error('depositAmount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div id="fullPaymentHint" class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-900 {{ $initialPaymentMode === 'full' ? '' : 'hidden' }}">
                                <span class="font-semibold">Hepsi ödendi:</span> Genel toplamın tamamı tahsil edilecek — <span id="fullPaymentAmountDisplay" class="font-semibold tabular-nums">0 ₺</span>
                            </div>
                            <div class="sale-meta-grid">
                                <div>
                                    <label class="form-label">Ödeme Tipi</label>
                                    <select name="depositPaymentType" id="depositPaymentType" class="form-select min-h-[44px]">
                                        @foreach(\App\Support\PaymentType::SELECTABLE as $value => $label)
                                        <option value="{{ $value }}" {{ old('depositPaymentType', 'nakit') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    @include('partials.payment-kasa-field', [
                                        'kasalar' => $kasalar,
                                        'name' => 'depositKasaId',
                                        'id' => 'depositKasaId',
                                        'paymentTypeId' => 'depositPaymentType',
                                        'amountId' => 'depositAmount',
                                        'paymentModeName' => 'initialPaymentMode',
                                        'errorName' => 'depositKasaId',
                                        'wrapperClass' => '',
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('partials.drawing-files-fields', ['drawingFiles' => []])

                {{-- Notlar --}}
                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Notlar</h2></div>
                    <div class="sale-form-section-body">
                        <textarea name="notes" rows="3" class="form-input form-textarea" placeholder="Satışa özel notlar (opsiyonel)...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Sağ panel: özet --}}
            <div class="space-y-5 xl:sticky xl:top-24">
                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Fiyatlandırma</h2></div>
                    <div class="sale-form-section-body space-y-4">
                        <div>
                            <label class="form-label">KDV</label>
                            <select name="kdvIncluded" class="form-select min-h-[44px]">
                                <option value="1" {{ old('kdvIncluded', '1') == '1' ? 'selected' : '' }}>KDV Dahil</option>
                                <option value="0" {{ old('kdvIncluded') === '0' ? 'selected' : '' }}>KDV Hariç</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Genel İndirim %</label>
                            <input type="number" step="0.01" min="0" max="100" name="saleDiscountPercent" id="saleDiscountPercent" value="{{ old('saleDiscountPercent', '0') }}" class="form-input min-h-[44px]" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="sale-summary-panel" id="saleTotals">
                    <p class="text-xs uppercase tracking-wider text-neutral-400 mb-3">Sipariş Özeti</p>
                    <div class="sale-summary-row"><span>Ara Toplam</span><strong id="subtotalBeforeDiscDisplay">0 ₺</strong></div>
                    <div class="sale-summary-row"><span>KDV Toplam</span><strong id="kdvDisplay">0 ₺</strong></div>
                    <div id="saleDiscountGeneralRow" class="sale-summary-row hidden"><span>İndirim</span><strong id="saleDiscountGeneralDisplay" class="text-amber-300">0 ₺</strong></div>
                    <div class="sale-summary-total">
                        <span>Genel Toplam</span>
                        <span id="grandTotalCalculated">0 ₺</span>
                    </div>
                    <div id="depositSummaryRow" class="sale-summary-row hidden mt-2">
                        <span id="depositSummaryLabel" class="text-emerald-300">Kapora</span>
                        <strong id="depositSummaryDisplay" class="text-emerald-300">0 ₺</strong>
                    </div>
                    <div id="remainingSummaryRow" class="sale-summary-row hidden">
                        <span>Kalan Tahsilat</span>
                        <strong id="remainingSummaryDisplay" class="text-amber-300">0 ₺</strong>
                    </div>
                    <p id="depositOverTotalWarning" class="hidden mt-2 text-xs text-red-400">Kapora, genel toplamdan büyük olamaz.</p>
                    <div class="mt-4 pt-4 border-t border-neutral-700">
                        <label for="grandTotalOverride" class="text-xs text-neutral-400 block mb-1">Hedef toplam (indirim otomatik)</label>
                        <div class="flex items-center gap-2">
                            <input type="text" inputmode="decimal" id="grandTotalOverride" name="grandTotalOverride" value="{{ old('grandTotalOverride') !== null && old('grandTotalOverride') !== '' ? money(old('grandTotalOverride')) : '' }}" class="flex-1 text-right font-semibold bg-neutral-800 border border-neutral-600 rounded-lg px-3 py-2 text-sm text-white tabular-nums focus:outline-none focus:ring-2 focus:ring-neutral-500" placeholder="—">
                            <span class="text-neutral-400 text-sm">₺</span>
                        </div>
                    </div>
                </div>

                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Sipariş Gönderimi</h2></div>
                    <div class="sale-form-section-body space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="sendCustomerEmail" value="1" {{ old('sendCustomerEmail') ? 'checked' : '' }} class="mt-1 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-500">
                            <span>
                                <span class="text-sm font-medium text-neutral-900 block">Oluşturulduktan sonra müşteriye e-posta gönder</span>
                                <span class="text-xs text-neutral-500">Müşteri kartında e-posta tanımlı olmalıdır.</span>
                            </span>
                        </label>
                        <div>
                            <label class="form-label">E-posta notu (opsiyonel)</label>
                            <textarea name="customerEmailNote" rows="2" class="form-input form-textarea text-sm" placeholder="Müşteriye iletilecek kısa mesaj...">{{ old('customerEmailNote') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="sale-form-section">
                    <div class="sale-form-section-body space-y-3">
                        <button type="submit" :disabled="submitting" class="btn-primary w-full justify-center min-h-[48px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <span x-show="submitting" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" aria-hidden="true"></span>
                            <span x-text="submitting ? 'Oluşturuluyor...' : 'Satış Oluştur'">Satış Oluştur</span>
                        </button>
                        <a href="{{ route('sales.index') }}" class="btn-secondary w-full justify-center min-h-[44px]">İptal</a>
                        <p class="text-center text-xs text-neutral-400">Mobil özet: <strong id="stickyTotal" class="text-neutral-700">0 ₺</strong> <span id="stickyRemainingWrap" class="hidden"> · Kalan <strong id="stickyRemaining" class="text-neutral-700">0 ₺</strong></span></p>
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
                    <input type="number" step="0.01" min="0" max="100" x-model="quickProduct.kdvRate" class="form-input min-h-[44px]" placeholder="10">
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
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 pt-5 pb-1">
                <h2 id="quick-add-title" class="text-lg font-semibold text-neutral-900">Hızlı Müşteri Ekle</h2>
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
                    @include('partials.address-fields-alpine')
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
        'phone' => $c->phone ?? '', 'email' => $c->email ?? '', 'address' => $c->full_address,
        'cityId' => $c->cityId, 'districtId' => $c->districtId,
        'taxNumber' => $c->taxNumber ?? '', 'taxOffice' => $c->taxOffice ?? '', 'identityNumber' => $c->identityNumber ?? ''
    ])->values();
    $productsJson = $products->map(function($p) {
        $img = is_array($p->images ?? null) ? ($p->images[0] ?? null) : ($p->images ?? null);
        return ['id' => $p->id, 'name' => $p->name . ' (' . number_format($p->unitPrice, 0, ',', '.') . ' ₺)', 'price' => (float)$p->unitPrice, 'kdv' => (float)($p->kdvRate ?? 10), 'image' => $img ? (Str::startsWith($img, 'http') ? $img : url($img)) : null];
    })->values();
    $oldSaleItems = collect(old('items', []))->values()->all();
@endphp
const customers = @json($customersJson);
const productsData = @json($productsJson);
const oldSaleItems = @json($oldSaleItems);
function salesCreateForm() {
    return {
        customerId: '{{ old("customerId", request("customerId")) }}',
        showQuickAddCustomer: false,
        quickCustomer: { name: '', phone: '', email: '', address: '', cityId: '', districtId: '' },
        quickAddLoading: false,
        quickAddError: '',
        showQuickAddProduct: false,
        quickProduct: { name: '', unitPrice: '', kdvRate: '10' },
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
                        unitPrice: (window.parseMoney || parseFloat)(this.quickProduct.unitPrice) || 0,
                        kdvRate: parseFloat(this.quickProduct.kdvRate) || 10,
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
                    (window.salesProductSelects || []).forEach(function(ts) {
                        if (ts) ts.addOption({ value: String(data.id), text: text });
                    });
                    const targetRow = resolveSaleRowForQuickProduct(window.quickAddProductForRowIndex ?? 0);
                    applyProductToSaleRow(targetRow, data, text);
                    targetRow.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    this.showQuickAddProduct = false;
                    this.quickProduct = { name: '', unitPrice: '', kdvRate: '10' };
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
                    customers.push({ id: data.id, name: data.name, phone: data.phone || '', email: data.email || '', address: data.address || '', cityId: data.cityId || '', districtId: data.districtId || '', taxNumber: data.taxNumber || '', taxOffice: data.taxOffice || '', identityNumber: '' });
                    if (window.customerTomSelect) {
                        window.customerTomSelect.addOption({ value: data.id, text: data.name });
                        window.customerTomSelect.setValue(data.id);
                    }
                    this.customerId = data.id;
                    this.showQuickAddCustomer = false;
                    this.quickCustomer = { name: '', phone: '', email: '', address: '', cityId: '', districtId: '' };
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
    if (window.salesProductSelects) {
        const arr = [];
        container.querySelectorAll('.item-product').forEach((sel, i) => { arr[i] = sel.tomselect; });
        window.salesProductSelects = arr;
    }
}
function addRow(focusNew) {
    const tmpl = document.getElementById('item-template');
    if (!tmpl) return null;
    const c = tmpl.content.cloneNode(true);
    c.querySelectorAll('[name]').forEach(e => {
        e.name = e.name.replace(/__IDX__/g, idx);
    });
    const row = c.querySelector('.item-row');
    if (row) row.setAttribute('data-row-idx', String(idx));
    c.querySelector('.item-price').value = '';
    c.querySelector('.item-qty').value = '1';
    c.querySelector('.item-kdv').value = '10';
    document.getElementById('items').appendChild(c);
    const rowEl = document.getElementById('items').lastElementChild;
    const sel = rowEl.querySelector('.item-product');
    initProductSelect(sel, idx);
    if (window.ItemDescriptionLines) ItemDescriptionLines.initRow(rowEl, null);
    idx++;
    reindexSaleRows();
    updateSaleTotals();
    if (focusNew) {
        const ts = sel && sel.tomselect;
        if (ts) { ts.focus(); }
        rowEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    return rowEl;
}
function saleItemHasContent(item) {
    if (!item || typeof item !== 'object') return false;
    const name = String(item.productName || '').trim();
    const price = parseTrNum(item.unitPrice);
    return !!(item.productId || name || (!isNaN(price) && price > 0));
}
function restoreSaleRow(item) {
    const rowEl = addRow(false);
    if (!rowEl || !item) return;

    const qtyEl = rowEl.querySelector('.item-qty');
    const kdvEl = rowEl.querySelector('.item-kdv');
    const priceEl = rowEl.querySelector('.item-price');
    const idInput = rowEl.querySelector('.item-product-id');
    const nameInput = rowEl.querySelector('.item-product-name');

    if (qtyEl) qtyEl.value = item.quantity ?? 1;
    if (kdvEl) kdvEl.value = item.kdvRate ?? 10;
    if (window.ItemDescriptionLines) ItemDescriptionLines.initRow(rowEl, item);

    const productId = item.productId ? String(item.productId) : '';
    const productName = String(item.productName || '').trim();
    const ts = rowEl.querySelector('.item-product')?.tomselect;

    if (productId) {
        const product = productsData.find(p => String(p.id) === productId);
        const text = product ? product.name : (productName || productId);
        if (idInput) idInput.value = productId;
        if (nameInput) nameInput.value = '';
        if (ts) {
            if (!ts.options[productId]) ts.addOption({ value: productId, text: text });
            ts.setValue(productId, true);
        }
    } else if (productName) {
        if (idInput) idInput.value = '';
        if (nameInput) nameInput.value = productName;
        if (ts) {
            if (!ts.options[productName]) ts.addOption({ value: productName, text: productName });
            ts.setValue(productName, true);
        }
    }

    if (priceEl && item.unitPrice != null && item.unitPrice !== '') {
        const priceNum = parseTrNum(item.unitPrice);
        if (!isNaN(priceNum)) {
            priceEl.value = fmt(priceNum);
            priceEl.setAttribute('data-raw', String(priceNum));
        }
    }
}
function duplicateSaleRow(btn) {
    const src = btn.closest('.item-row');
    if (!src) return;
    const newRow = addRow(false);
    if (!newRow) return;
    ['.item-price', '.item-qty', '.item-kdv'].forEach(function(cls) {
        const from = src.querySelector(cls), to = newRow.querySelector(cls);
        if (from && to) {
            to.value = from.value;
            if (from.hasAttribute('data-raw')) to.setAttribute('data-raw', from.getAttribute('data-raw'));
        }
    });
    if (window.ItemDescriptionLines) ItemDescriptionLines.duplicateLines(src, newRow);
    const srcTs = src.querySelector('.item-product')?.tomselect;
    const newTs = newRow.querySelector('.item-product')?.tomselect;
    const val = srcTs && srcTs.getValue();
    if (newTs && val) {
        newTs.setValue(val, true);
        newRow.querySelector('.item-product-id').value = src.querySelector('.item-product-id').value;
        newRow.querySelector('.item-product-name').value = src.querySelector('.item-product-name').value;
        // setValue silent olduğu için fiyat/kdv kopyalanan değerlerde kalır
        ['.item-price', '.item-kdv'].forEach(function(cls) {
            const from = src.querySelector(cls), to = newRow.querySelector(cls);
            if (from && to) to.value = from.value;
        });
    }
    updateSaleTotals();
    newRow.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}
window.openQuickAddProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
function saleRowIsEmpty(row) {
    if (!row) return true;
    const productId = row.querySelector('.item-product-id')?.value;
    const productName = row.querySelector('.item-product-name')?.value;
    const tsVal = row.querySelector('.item-product')?.tomselect?.getValue();
    const price = parseTrNum(row.querySelector('.item-price')?.value ?? row.querySelector('.item-price')?.getAttribute('data-raw') ?? 0);
    return !productId && !productName && !tsVal && !(price > 0);
}
function resolveSaleRowForQuickProduct(rowIndex) {
    const rows = document.querySelectorAll('#items .item-row');
    let row = rows[rowIndex] || null;
    if (!row || !saleRowIsEmpty(row)) {
        row = addRow(false);
    }
    return row;
}
function applyProductToSaleRow(rowEl, data, text) {
    if (!rowEl || !data) return;
    const id = String(data.id);
    const price = parseFloat(data.price) || 0;
    const kdv = parseFloat(data.kdv) ?? 10;
    const idInput = rowEl.querySelector('.item-product-id');
    const nameInput = rowEl.querySelector('.item-product-name');
    const priceEl = rowEl.querySelector('.item-price');
    const kdvEl = rowEl.querySelector('.item-kdv');
    const qtyEl = rowEl.querySelector('.item-qty');
    if (idInput) idInput.value = id;
    if (nameInput) nameInput.value = '';
    if (priceEl) {
        priceEl.value = fmt(price);
        priceEl.setAttribute('data-raw', String(price));
    }
    if (kdvEl) kdvEl.value = kdv;
    if (qtyEl && (!qtyEl.value || parseInt(qtyEl.value, 10) < 1)) qtyEl.value = '1';
    const ts = rowEl.querySelector('.item-product')?.tomselect;
    if (ts) {
        if (!ts.options[id]) ts.addOption({ value: id, text: text });
        ts.setValue(id, true);
    }
}
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
                row.querySelector('.item-kdv').value = opt.dataset.kdv || 10;
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
    if (typeof s === 'number') return s;
    if (window.parseLinePrice) {
        var v = window.parseLinePrice(s);
        if (!isNaN(v)) return v;
    }
    return window.parseMoney ? window.parseMoney(s) : NaN;
}
function updateSaleTotals() {
    const subtotalEl = document.getElementById('subtotalBeforeDiscDisplay');
    if (!subtotalEl) return;
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    const saleDiscPctInp = document.getElementById('saleDiscountPercent');
    const grandTotalOverrideInp = document.getElementById('grandTotalOverride');
    const grandTotalCalculatedEl = document.getElementById('grandTotalCalculated');
    let subtotalBeforeDisc = 0, subtotal = 0, kdvTotal = 0, validRows = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const priceEl = row.querySelector('.item-price');
        const lineTotalEl = row.querySelector('.item-line-total');
        const price = parseTrNum(priceEl?.value ?? priceEl?.getAttribute('data-raw') ?? 0);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 10, 10);
        if (price <= 0 || qty <= 0) {
            if (lineTotalEl) lineTotalEl.textContent = '0 ₺';
            return;
        }
        validRows++;
        const lineTotal = kdvIncl ? price * qty / (1 + kdv / 100) : price * qty;
        const lineKdv = lineTotal * (kdv / 100);
        if (lineTotalEl) lineTotalEl.textContent = fmt(lineTotal + lineKdv) + ' ₺';
        subtotalBeforeDisc += lineTotal;
        subtotal += lineTotal;
        kdvTotal += lineKdv;
    });
    const calculatedBeforeSaleDisc = subtotal + kdvTotal;
    const saleDiscPct = parseFloat(saleDiscPctInp?.value || 0, 10);
    const grandTotalFromDisc = Math.round(calculatedBeforeSaleDisc * (1 - saleDiscPct / 100) * 100) / 100;
    document.getElementById('subtotalBeforeDiscDisplay').textContent = fmt(subtotalBeforeDisc) + ' ₺';
    document.getElementById('kdvDisplay').textContent = fmt(kdvTotal) + ' ₺';
    const genRow = document.getElementById('saleDiscountGeneralRow');
    const genDisp = document.getElementById('saleDiscountGeneralDisplay');
    if (genRow && genDisp) {
        const genDiscValue = calculatedBeforeSaleDisc - grandTotalFromDisc;
        genRow.classList.toggle('hidden', !(saleDiscPct > 0 && genDiscValue > 0));
        genDisp.textContent = '-' + fmt(genDiscValue) + ' ₺';
    }
    if (grandTotalCalculatedEl) grandTotalCalculatedEl.textContent = fmt(grandTotalFromDisc) + ' ₺';
    const override = parseTrNum(grandTotalOverrideInp?.value);
    const finalGrand = !isNaN(override) && override > 0 ? override : grandTotalFromDisc;
    const paymentMode = document.querySelector('input[name="initialPaymentMode"]:checked')?.value || 'none';
    let deposit = 0;
    if (paymentMode === 'kapora') {
        deposit = parseTrNum(document.getElementById('depositAmount')?.value) || 0;
    } else if (paymentMode === 'full') {
        deposit = finalGrand;
    }
    const remaining = Math.round((finalGrand - deposit) * 100) / 100;
    const depositRow = document.getElementById('depositSummaryRow');
    const depositLabel = document.getElementById('depositSummaryLabel');
    const depositDisp = document.getElementById('depositSummaryDisplay');
    const remainingRow = document.getElementById('remainingSummaryRow');
    const remainingDisp = document.getElementById('remainingSummaryDisplay');
    const depositWarn = document.getElementById('depositOverTotalWarning');
    const fullPaymentAmountDisplay = document.getElementById('fullPaymentAmountDisplay');
    if (fullPaymentAmountDisplay) fullPaymentAmountDisplay.textContent = fmt(finalGrand) + ' ₺';
    if (depositRow && depositDisp) {
        depositRow.classList.toggle('hidden', !(deposit > 0));
        if (depositLabel) depositLabel.textContent = paymentMode === 'full' ? 'Tahsilat (Tam)' : 'Kapora';
        depositDisp.textContent = fmt(deposit) + ' ₺';
    }
    if (remainingRow && remainingDisp) {
        remainingRow.classList.toggle('hidden', !(deposit > 0) || paymentMode === 'full');
        remainingDisp.textContent = fmt(Math.max(0, remaining)) + ' ₺';
        remainingDisp.classList.toggle('text-amber-300', remaining > 0);
        remainingDisp.classList.toggle('text-emerald-300', remaining <= 0);
    }
    if (depositWarn) {
        depositWarn.classList.toggle('hidden', !(paymentMode === 'kapora' && deposit > 0 && deposit > finalGrand));
        depositWarn.textContent = 'Kapora, genel toplamdan büyük olamaz.';
    }
    const badge = document.getElementById('itemCountBadge');
    if (badge) badge.textContent = String(validRows);
    const sticky = document.getElementById('stickyTotal');
    const stickyRemainingWrap = document.getElementById('stickyRemainingWrap');
    const stickyRemaining = document.getElementById('stickyRemaining');
    if (sticky) {
        sticky.textContent = fmt(finalGrand) + ' ₺';
    }
    if (stickyRemainingWrap && stickyRemaining) {
        stickyRemainingWrap.classList.toggle('hidden', !(deposit > 0) || paymentMode === 'full');
        stickyRemaining.textContent = fmt(Math.max(0, remaining)) + ' ₺';
    }
}
function updateInitialPaymentMode() {
    const mode = document.querySelector('input[name="initialPaymentMode"]:checked')?.value || 'none';
    const details = document.getElementById('initialPaymentDetails');
    const amountWrap = document.getElementById('depositAmountWrap');
    const fullHint = document.getElementById('fullPaymentHint');
    const amountInput = document.getElementById('depositAmount');
    if (details) details.classList.toggle('hidden', mode === 'none');
    if (amountWrap) amountWrap.classList.toggle('hidden', mode !== 'kapora');
    if (fullHint) fullHint.classList.toggle('hidden', mode !== 'full');
    if (amountInput) {
        amountInput.required = mode === 'kapora';
        if (mode !== 'kapora') amountInput.removeAttribute('required');
    }
    document.querySelectorAll('input[name="initialPaymentMode"]').forEach(function(radio) {
        const label = radio.closest('label');
        if (!label) return;
        const active = radio.checked;
        const isNone = radio.value === 'none';
        label.classList.toggle('border-neutral-900', active && isNone);
        label.classList.toggle('bg-neutral-900', active && isNone);
        label.classList.toggle('text-white', active && isNone);
        label.classList.toggle('border-emerald-600', active && !isNone);
        label.classList.toggle('bg-emerald-600', active && !isNone);
        label.classList.toggle('text-white', active && !isNone);
        label.classList.toggle('border-neutral-200', !active);
        label.classList.toggle('bg-white', !active);
        label.classList.toggle('text-neutral-700', !active);
    });
    if (window.initPaymentKasaFields) window.initPaymentKasaFields();
    updateSaleTotals();
}
function onGrandTotalOverrideInput() {
    const overrideInp = document.getElementById('grandTotalOverride');
    const saleDiscInp = document.getElementById('saleDiscountPercent');
    if (!overrideInp || !saleDiscInp) return;
    const override = parseTrNum(overrideInp.value) || 0;
    let subtotal = 0, kdvTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const priceEl = row.querySelector('.item-price');
        const price = parseTrNum(priceEl?.value ?? priceEl?.getAttribute('data-raw') ?? 0);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 10, 10);
        if (price <= 0 || qty <= 0) return;
        const lineTotal = document.querySelector('select[name="kdvIncluded"]')?.value === '1'
            ? price * qty / (1 + kdv / 100) : price * qty;
        subtotal += lineTotal;
        kdvTotal += lineTotal * (kdv / 100);
    });
    const calculated = subtotal + kdvTotal;
    if (override > 0 && calculated > 0) {
        const pct = Math.max(0, Math.min(100, (1 - override / calculated) * 100));
        saleDiscInp.value = Math.round(pct * 100) / 100;
    }
    updateSaleTotals();
}
document.getElementById('saleForm')?.addEventListener('input', function(e) {
    if (e.target.id === 'grandTotalOverride') { onGrandTotalOverrideInput(); return; }
    updateSaleTotals();
});
document.getElementById('saleForm')?.addEventListener('change', function(e) {
    if (e.target.id === 'grandTotalOverride') { onGrandTotalOverrideInput(); return; }
    updateSaleTotals();
});
document.getElementById('saleForm')?.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    const form = this;
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        if (form.reportValidity()) form.requestSubmit();
        return;
    }
    const t = e.target;
    if (!t || t.tagName === 'TEXTAREA' || t.tagName === 'BUTTON') return;
    const row = t.closest && t.closest('.item-row');
    if (!row) return;
    e.preventDefault();
    const rows = Array.from(document.querySelectorAll('.item-row'));
    const isLast = rows[rows.length - 1] === row;
    const hasPrice = parseTrNum(row.querySelector('.item-price')?.value) > 0;
    if (isLast) {
        if (hasPrice) addRow(true);
        return;
    }
    const next = rows[rows.indexOf(row) + 1];
    const ts = next?.querySelector('.item-product')?.tomselect;
    if (ts) ts.focus(); else next?.querySelector('.item-price')?.focus();
});
document.getElementById('saleForm')?.addEventListener('submit', function() {
    document.querySelectorAll('.item-price').forEach(function(inp) {
        const v = parseTrNum(inp.value);
        inp.value = isNaN(v) || v < 0 ? '' : String(v);
    });
    const overrideInp = document.getElementById('grandTotalOverride');
    if (overrideInp && overrideInp.value) {
        const v = parseTrNum(overrideInp.value);
        overrideInp.value = isNaN(v) || v < 0 ? '' : String(v);
    }
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
    const link = document.getElementById('customerEditLink');
    if (link) {
        link.href = '{{ url("customers") }}/' + encodeURIComponent(c.id);
        link.classList.remove('hidden');
    }
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
    document.querySelectorAll('input[name="initialPaymentMode"]').forEach(function(radio) {
        radio.addEventListener('change', updateInitialPaymentMode);
    });
    const depositAmount = document.getElementById('depositAmount');
    if (depositAmount) {
        depositAmount.addEventListener('input', updateSaleTotals);
        depositAmount.addEventListener('change', updateSaleTotals);
    }
    updateInitialPaymentMode();
    if (window.initPaymentKasaFields) window.initPaymentKasaFields();
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
    const itemsToRestore = Array.isArray(oldSaleItems) ? oldSaleItems.filter(saleItemHasContent) : [];
    if (itemsToRestore.length > 0) {
        itemsToRestore.forEach(function(item) { restoreSaleRow(item); });
    }
    if (document.querySelectorAll('#items .item-row').length === 0) {
        addRow();
    }
    updateSaleTotals();
}
</script>
@endsection
