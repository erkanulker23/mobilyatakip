@extends('layouts.app')
@section('title', 'Teklif Düzenle - ' . $quote->quoteNumber)
@php
    $initialQuoteItems = $quote->items->map(fn ($i) => [
        'productId' => $i->productId,
        'productName' => $i->productName,
        'description' => $i->description,
        'unitPrice' => (float) $i->unitPrice,
        'quantity' => (int) $i->quantity,
        'kdvRate' => (float) ($i->kdvRate ?? 18),
        'lineDiscountPercent' => (float) ($i->lineDiscountPercent ?? 0),
        'lineDiscountAmount' => (float) ($i->lineDiscountAmount ?? 0),
    ])->values();
@endphp
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
.ts-wrapper .ts-control .item { display: flex; align-items: center; gap: 0.5rem; }
.ts-wrapper .ts-control .item img { flex-shrink: 0; }
.dark .sale-item-row .ts-wrapper .ts-control { background: #262626 !important; border-color: #404040 !important; color: #f5f5f5 !important; }
.dark .sale-item-row .ts-wrapper .ts-control input { color: #f5f5f5 !important; }
.dark .sale-item-row:focus-within .ts-wrapper .ts-control { background: #262626 !important; }
</style>
@endpush
@section('content')
<div class="mb-6">
    <nav class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('quotes.index') }}" class="hover:text-neutral-900 transition-colors">Teklifler</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('quotes.show', $quote) }}" class="hover:text-neutral-900 transition-colors">{{ $quote->quoteNumber }}</a>
        <span aria-hidden="true">/</span>
        <span class="text-neutral-700">Düzenle</span>
    </nav>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="page-title">Teklif Düzenle</h1>
            <p class="page-desc">{{ $quote->quoteNumber }} — teklif kalemleri (satış değil, tahsilat yok)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start">
            @if(!$quote->convertedSaleId && ($quote->status ?? '') == 'taslak')
            <form method="POST" action="{{ route('quotes.convert', $quote) }}" class="inline-flex" onsubmit="return confirm('Bu teklifi siparişe (satışa) dönüştürmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn-primary text-sm">Siparişe Dönüştür</button>
            </form>
            @elseif($quote->convertedSaleId && $quote->convertedSale)
            <a href="{{ route('sales.show', $quote->convertedSale) }}" class="btn-secondary text-sm">Satış #{{ $quote->convertedSale->saleNumber }}</a>
            @endif
            <a href="{{ route('quotes.show', $quote) }}" class="btn-secondary text-sm">← Teklif detayı</a>
        </div>
    </div>
</div>

@php
    $customersQuoteJson = $customers->map(fn($c) => [
        'id' => $c->id, 'name' => $c->name,
        'phone' => $c->phone ?? '', 'email' => $c->email ?? '', 'address' => $c->full_address,
        'taxNumber' => $c->taxNumber ?? '', 'taxOffice' => $c->taxOffice ?? '', 'identityNumber' => $c->identityNumber ?? ''
    ])->values();
@endphp
<div class="max-w-7xl" x-data="quoteCreateForm()" @open-quick-add-product.window="showQuickAddProduct = true">
    <form method="POST" action="{{ route('quotes.update', $quote) }}" id="quoteForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @if(session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="mb-4 p-4 rounded-xl bg-amber-50 text-amber-900 text-sm border border-amber-100">
            Bu bir tekliftir; tahsilat veya kapora alınmaz. Onaylandığında satışa dönüştürülebilir.
        </div>

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
                                        <option value="{{ $c->id }}" {{ old('customerId', $quote->customerId) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
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
                                <input type="date" name="validUntil" value="{{ old('validUntil', $quote->validUntil?->format('Y-m-d')) }}" class="form-input min-h-[44px]">
                            </div>
                            <div>
                                <label class="form-label">Teklifi Hazırlayan</label>
                                <select name="personnelId" class="form-select min-h-[44px]">
                                    <option value="">Seçiniz</option>
                                    @foreach($personnel as $p)
                                    <option value="{{ $p->id }}" {{ old('personnelId', $quote->personnelId) == $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->title ? ' — ' . $p->title : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Durum</label>
                                <select name="status" class="form-select min-h-[44px]">
                                    <option value="taslak" {{ old('status', $quote->status) == 'taslak' ? 'selected' : '' }}>Taslak</option>
                                    <option value="onaylandi" {{ old('status', $quote->status) == 'onaylandi' ? 'selected' : '' }}>Onaylandı</option>
                                    <option value="reddedildi" {{ old('status', $quote->status) == 'reddedildi' ? 'selected' : '' }}>Reddedildi</option>
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
                            <span class="row-no" aria-hidden="true">1</span>
                            <div class="item-product-wrap flex-1 min-w-0">
                                <label class="form-label lg:sr-only">Ürün / Hizmet <span class="text-red-500">*</span></label>
                                <select class="form-select item-product" data-placeholder="Ürün ara...">
                                    <option value=""></option>
                                </select>
                                <input type="hidden" class="item-product-id" name="items[__IDX__][productId]" value="">
                                <input type="hidden" class="item-product-name" name="items[__IDX__][productName]" value="">
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" onclick="window.openQuickAddQuoteProduct && window.openQuickAddQuoteProduct(this)" class="icon-btn touch-manipulation" title="Yeni ürün" aria-label="Yeni ürün">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button type="button" onclick="duplicateQuoteRow(this)" class="icon-btn touch-manipulation" title="Çoğalt" aria-label="Çoğalt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                                <button type="button" onclick="removeQuoteRow(this)" class="btn-remove-row icon-btn icon-btn-danger touch-manipulation" aria-label="Sil" title="Sil">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                            <div>
                                <label class="form-label">Fiyat ₺</label>
                                <input type="text" inputmode="decimal" name="items[__IDX__][unitPrice]" required class="form-input item-price" placeholder="20.000" data-raw="">
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
                            @include('partials.item-description-fields')
                        </div>
                    </div>
                    </template>
                    @include('partials.item-description-form-assets')
                    <div id="items" class="space-y-0"></div>
                    <button type="button" onclick="addQuoteRow()" class="add-row-btn mt-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Kalem Ekle
                    </button>
                    </div>
                </div>

                @include('partials.drawing-files-fields', ['drawingFiles' => old('drawingFiles', $quote->drawingFiles ?? [])])

                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Notlar</h2></div>
                    <div class="sale-form-section-body">
                        <textarea name="notes" rows="3" class="form-input form-textarea" placeholder="Teklife özel notlar (opsiyonel)...">{{ old('notes', $quote->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-5 xl:sticky xl:top-24">
                <div class="sale-form-section">
                    <div class="sale-form-section-head"><h2 class="sale-form-section-title">Fiyatlandırma</h2></div>
                    <div class="sale-form-section-body">
                        <label class="form-label">KDV</label>
                        <select name="kdvIncluded" class="form-select min-h-[44px]">
                            <option value="1" {{ old('kdvIncluded', $quote->kdvIncluded ?? true) ? 'selected' : '' }}>KDV Dahil</option>
                            <option value="0" {{ !old('kdvIncluded', $quote->kdvIncluded ?? true) ? 'selected' : '' }}>KDV Hariç</option>
                        </select>
                        <div class="grid grid-cols-2 gap-3 mt-4">
                            <div>
                                <label class="form-label" for="generalDiscountPercent">Genel İskonto %</label>
                                <input type="number" step="0.01" min="0" max="100" name="generalDiscountPercent" id="generalDiscountPercent" value="{{ old('generalDiscountPercent', $quote->generalDiscountPercent ?? 0) }}" class="form-input min-h-[44px]" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label" for="generalDiscountAmount">Genel İskonto ₺</label>
                                <input type="text" inputmode="decimal" name="generalDiscountAmount" id="generalDiscountAmount" value="{{ old('generalDiscountAmount') !== null && old('generalDiscountAmount') !== '' ? money(old('generalDiscountAmount')) : money($quote->generalDiscountAmount ?? 0) }}" class="form-input min-h-[44px]" placeholder="0" data-raw="{{ old('generalDiscountAmount', $quote->generalDiscountAmount ?? 0) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sale-summary-panel" id="quoteTotals">
                    <p class="text-xs uppercase tracking-wider text-neutral-400 mb-3">Teklif Özeti</p>
                    <div class="sale-summary-row {{ old('kdvIncluded', $quote->kdvIncluded ?? true) ? 'hidden' : '' }}" id="subtotalSummaryRow"><span>Ara Toplam</span><strong id="quoteSubtotalBeforeDiscDisplay">0 ₺</strong></div>
                    <div id="quoteDiscountPctRow" class="sale-summary-row hidden"><span>İskonto %</span><strong id="quoteDiscountPctDisplay" class="text-amber-300">0 ₺</strong></div>
                    <div id="quoteDiscountAmtRow" class="sale-summary-row hidden"><span>İskonto ₺</span><strong id="quoteDiscountAmtDisplay" class="text-amber-300">0 ₺</strong></div>
                    <div id="quoteGeneralDiscountRow" class="sale-summary-row hidden"><span>Genel İskonto</span><strong id="quoteGeneralDiscountDisplay" class="text-amber-300">0 ₺</strong></div>
                    <div class="sale-summary-row {{ old('kdvIncluded', $quote->kdvIncluded ?? true) ? 'hidden' : '' }}" id="kdvSummaryRow"><span>KDV Toplam</span><strong id="kdvDisplay">0 ₺</strong></div>
                    <div class="sale-summary-total">
                        <span>Genel Toplam</span>
                        <span id="grandTotalDisplay">0 ₺</span>
                    </div>
                </div>

                <div class="sale-form-section">
                    <div class="sale-form-section-body space-y-3">
                        <button type="submit" class="btn-primary w-full justify-center min-h-[48px]">Değişiklikleri Kaydet</button>
                        <a href="{{ route('quotes.show', $quote) }}" class="btn-secondary w-full justify-center min-h-[44px]">İptal</a>
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
<script>window.SALE_PRODUCT_SEARCH_URL = @json(route('api.products.search'));</script>
<script src="{{ asset('js/sale-product-select.js') }}"></script>
<script>
@php
    $itemsByProductId = $quote->items->keyBy('productId');
    $productsJson = ($initialProducts ?? collect())->map(function ($p) use ($itemsByProductId) {
        $payload = \App\Support\ProductSelect::payload($p);
        $item = $itemsByProductId->get($p->id);
        if ($item && abs((float) $item->unitPrice - (float) ($payload['price'] ?? 0)) > 0.001) {
            $payload['price'] = (float) $item->unitPrice;
            $payload['label'] = ($payload['name'] ?? $p->name) . ' · ' . number_format((float) $item->unitPrice, 0, ',', '.') . ' ₺';
        }
        return $payload;
    })->values();
@endphp
const customersQuote = @json($customersQuoteJson);
const productsData = @json($productsJson);
const initialQuoteItems = @json($initialQuoteItems);
window.updateSaleTotals = function() { if (typeof updateQuoteTotals === 'function') updateQuoteTotals(); };
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
                    const product = window.registerSaleProduct(data);
                    const targetRow = resolveQuoteRowForQuickProduct(window.quickAddQuoteProductForRowIndex ?? 0);
                    applyProductToQuoteRow(targetRow, product);
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
    if (typeof s === 'number') return s;
    if (window.parseLinePrice) {
        var v = window.parseLinePrice(s);
        if (!isNaN(v)) return v;
    }
    return window.parseMoney ? window.parseMoney(s) : NaN;
}
function readItemPrice(priceEl) {
    if (!priceEl) return NaN;
    return parseTrNum(priceEl.getAttribute('data-raw') || priceEl.value);
}
function roundMoney(n) {
    return Math.round((Number(n) || 0) * 100) / 100;
}
function syncKdvSummaryVisibility() {
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    ['kdvSummaryRow', 'subtotalSummaryRow'].forEach(function (id) {
        const row = document.getElementById(id);
        if (row) row.classList.toggle('hidden', kdvIncl);
    });
}
function updateQuoteTotals() {
    const kdvIncl = document.querySelector('select[name="kdvIncluded"]')?.value === '1';
    const genDiscPct = parseFloat(document.getElementById('generalDiscountPercent')?.value || 0, 10);
    const genDiscAmtEl = document.getElementById('generalDiscountAmount');
    const genDiscAmt = genDiscAmtEl ? readItemPrice(genDiscAmtEl) : 0;
    let subtotalBeforeDisc = 0, totalDiscountPct = 0, totalDiscountAmt = 0, subtotal = 0, lineKdvSum = 0;
    document.querySelectorAll('#items .item-row').forEach(row => {
        const priceEl = row.querySelector('.item-price');
        const price = readItemPrice(priceEl);
        const qty = parseInt(row.querySelector('.item-qty')?.value || 1, 10);
        const kdv = parseFloat(row.querySelector('.item-kdv')?.value || 18, 10);
        const discPct = parseFloat(row.querySelector('.item-disc-pct')?.value || 0, 10);
        const discAmt = readItemPrice(row.querySelector('.item-disc-amt')) || 0;
        if (price <= 0 || qty <= 0) return;
        const rawLineNet = kdvIncl
            ? roundMoney(price * qty / (1 + kdv / 100))
            : roundMoney(price * qty);
        const lineDiscPct = roundMoney(rawLineNet * (discPct / 100));
        const lineDiscAmt = roundMoney(discAmt);
        const lineNet = Math.max(0, roundMoney(rawLineNet - lineDiscPct - lineDiscAmt));
        const lineKdv = roundMoney(lineNet * (kdv / 100));
        const lineTotal = roundMoney(lineNet + lineKdv);
        subtotalBeforeDisc += rawLineNet;
        totalDiscountPct += lineDiscPct;
        totalDiscountAmt += lineDiscAmt;
        subtotal += lineNet;
        lineKdvSum += lineKdv;
        const lineEl = row.querySelector('.item-line-total');
        if (lineEl) lineEl.textContent = fmt(lineTotal) + ' ₺';
    });
    let generalDisc, kdvTotal, grandTotal;
    if (kdvIncl) {
        const grossBeforeGeneralDisc = roundMoney(subtotal + lineKdvSum);
        generalDisc = roundMoney(grossBeforeGeneralDisc * (genDiscPct / 100) + (isNaN(genDiscAmt) ? 0 : genDiscAmt));
        grandTotal = Math.max(0, roundMoney(grossBeforeGeneralDisc - generalDisc));
        const ratio = grossBeforeGeneralDisc > 0 ? grandTotal / grossBeforeGeneralDisc : 0;
        kdvTotal = roundMoney(ratio * lineKdvSum);
    } else {
        generalDisc = roundMoney(subtotal * (genDiscPct / 100) + (isNaN(genDiscAmt) ? 0 : genDiscAmt));
        const afterDisc = Math.max(0, roundMoney(subtotal - generalDisc));
        const ratio = subtotal > 0 ? afterDisc / subtotal : 0;
        kdvTotal = roundMoney(ratio * lineKdvSum);
        grandTotal = roundMoney(afterDisc + kdvTotal);
    }
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
        const genRow = document.getElementById('quoteGeneralDiscountRow');
        const genDisp = document.getElementById('quoteGeneralDiscountDisplay');
        if (genRow && genDisp) {
            genRow.classList.toggle('hidden', generalDisc <= 0);
            genDisp.textContent = '-' + fmt(generalDisc) + ' ₺';
        }
        document.getElementById('kdvDisplay').textContent = fmt(kdvTotal) + ' ₺';
        const grand = fmt(grandTotal) + ' ₺';
        document.getElementById('grandTotalDisplay').textContent = grand;
        const sticky = document.getElementById('stickyTotal');
        if (sticky) sticky.textContent = grand;
    }
    const badge = document.getElementById('quoteItemCountBadge');
    if (badge) badge.textContent = String(document.querySelectorAll('#items .item-row').length);
    syncKdvSummaryVisibility();
}
window.openQuickAddQuoteProduct = function(btn) {
    const row = btn && btn.closest ? btn.closest('.item-row') : null;
    window.quickAddQuoteProductForRowIndex = row ? parseInt(row.getAttribute('data-row-idx'), 10) : 0;
    window.dispatchEvent(new CustomEvent('open-quick-add-product'));
};
function quoteRowIsEmpty(row) {
    if (!row) return true;
    const productId = row.querySelector('.item-product-id')?.value;
    const productName = row.querySelector('.item-product-name')?.value;
    const tsVal = row.querySelector('.item-product')?.tomselect?.getValue();
    const priceEl = row.querySelector('.item-price');
    const price = readItemPrice(priceEl);
    return !productId && !productName && !tsVal && !(price > 0);
}
function resolveQuoteRowForQuickProduct(rowIndex) {
    const rows = document.querySelectorAll('#items .item-row');
    let row = rows[rowIndex] || null;
    if (!row || !quoteRowIsEmpty(row)) {
        row = addQuoteRow(false);
    }
    return row;
}
function applyProductToQuoteRow(rowEl, data) {
    if (!rowEl || !data) return;
    const product = window.registerSaleProduct(data);
    const id = String(product.id);
    const ts = rowEl.querySelector('.item-product')?.tomselect;
    if (window.applySaleProductToRow) {
        window.applySaleProductToRow(rowEl, product, { forcePrice: true });
    }
    if (ts) {
        if (!ts.options[id]) ts.addOption(product);
        ts.setValue(id, true);
    }
}
function restoreQuoteRow(item) {
    const rowEl = addQuoteRow(false);
    if (!rowEl || !item) return;
    rowEl.dataset.restoring = '1';
    const qtyEl = rowEl.querySelector('.item-qty');
    const kdvEl = rowEl.querySelector('.item-kdv');
    const priceEl = rowEl.querySelector('.item-price');
    const discPct = rowEl.querySelector('.item-disc-pct');
    const discAmt = rowEl.querySelector('.item-disc-amt');
    const idInput = rowEl.querySelector('.item-product-id');
    const nameInput = rowEl.querySelector('.item-product-name');
    if (qtyEl) qtyEl.value = item.quantity ?? 1;
    if (kdvEl) kdvEl.value = item.kdvRate ?? 18;
    if (discPct && item.lineDiscountPercent) discPct.value = item.lineDiscountPercent;
    if (discAmt && item.lineDiscountAmount) discAmt.value = fmt(item.lineDiscountAmount);
    if (window.ItemDescriptionLines) ItemDescriptionLines.initRow(rowEl, item);
    const productId = item.productId ? String(item.productId) : '';
    const productName = String(item.productName || '').trim();
    const ts = rowEl.querySelector('.item-product')?.tomselect;
    if (productId) {
        let product = window.getSaleProduct(productId);
        if (!product) {
            product = window.registerSaleProduct({ id: productId, name: productName || productId, price: parseTrNum(item.unitPrice), kdv: item.kdvRate ?? 18 });
        }
        if (idInput) idInput.value = productId;
        if (nameInput) nameInput.value = '';
        if (ts) {
            if (!ts.options[productId]) ts.addOption(product);
            ts.setValue(productId, true);
        }
    } else if (productName && ts) {
        if (idInput) idInput.value = '';
        if (nameInput) nameInput.value = productName;
        ts.addOption({ id: productName, name: productName, label: productName });
        ts.setValue(productName, true);
    }

    if (priceEl && item.unitPrice != null && item.unitPrice !== '') {
        const priceNum = parseTrNum(item.unitPrice);
        if (!isNaN(priceNum)) {
            priceEl.value = fmt(priceNum);
            priceEl.setAttribute('data-raw', String(priceNum));
            const catalogProduct = productId ? window.getSaleProduct(productId) : null;
            const catalogPrice = catalogProduct ? parseTrNum(catalogProduct.price) : NaN;
            if (catalogProduct && !isNaN(catalogPrice) && Math.abs(priceNum - catalogPrice) > 0.001) {
                priceEl.dataset.priceCustom = '1';
            }
            if (catalogProduct && window.syncSaleProductSelectLabel) {
                window.syncSaleProductSelectLabel(rowEl, catalogProduct);
            }
        }
    } else if (priceEl && productName && item.unitPrice != null) {
        const priceNum = parseTrNum(item.unitPrice);
        if (!isNaN(priceNum)) {
            priceEl.value = fmt(priceNum);
            priceEl.setAttribute('data-raw', String(priceNum));
        }
    }
    delete rowEl.dataset.restoring;
    updateQuoteTotals();
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
function duplicateQuoteRow(btn) {
    const src = btn.closest('.item-row');
    if (!src) return;
    const newRow = addQuoteRow(false);
    ['.item-price', '.item-qty', '.item-kdv', '.item-disc-pct', '.item-disc-amt'].forEach(function(cls) {
        const from = src.querySelector(cls), to = newRow.querySelector(cls);
        if (from && to) {
            to.value = from.value;
            if (from.hasAttribute('data-raw')) to.setAttribute('data-raw', from.getAttribute('data-raw'));
            if (from.dataset.priceCustom) to.dataset.priceCustom = from.dataset.priceCustom;
        }
    });
    if (window.ItemDescriptionLines) ItemDescriptionLines.duplicateLines(src, newRow);
    const srcTs = src.querySelector('.item-product')?.tomselect;
    const newTs = newRow.querySelector('.item-product')?.tomselect;
    const val = srcTs && srcTs.getValue();
    if (newTs && val) {
        const product = window.getSaleProduct ? window.getSaleProduct(val) : null;
        if (product && !newTs.options[val]) newTs.addOption(product);
        newTs.setValue(val, true);
        newRow.querySelector('.item-product-id').value = src.querySelector('.item-product-id').value;
        newRow.querySelector('.item-product-name').value = src.querySelector('.item-product-name').value;
        if (product && window.syncSaleProductSelectLabel) {
            window.syncSaleProductSelectLabel(newRow, product);
        }
    }
    updateQuoteTotals();
    newRow.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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
    if (window.salesProductSelects) {
        const arr = [];
        container.querySelectorAll('.item-product').forEach((sel, i) => { arr[i] = sel.tomselect; });
        window.salesProductSelects = arr;
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
    initSaleProductSelect(sel, quoteIdx);
    if (window.ItemDescriptionLines) ItemDescriptionLines.initRow(rowEl, null);
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
    setRow('E-posta', c.email);
    setRow('Address', c.address);
    const taxParts = [c.identityNumber, c.taxNumber, c.taxOffice].filter(Boolean);
    setRow('Tax', taxParts.length ? taxParts.join(' · ') : null);
}
function initQuoteForm() {
    if (window.seedSaleProducts) window.seedSaleProducts(productsData);
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
    const items = Array.isArray(initialQuoteItems) ? initialQuoteItems : [];
    if (items.length) {
        items.forEach(function(item) { restoreQuoteRow(item); });
    } else {
        addQuoteRow();
    }
    document.getElementById('quoteForm')?.addEventListener('input', function() {
        updateQuoteTotals();
    });
    document.getElementById('quoteForm')?.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'kdvIncluded') syncKdvSummaryVisibility();
        updateQuoteTotals();
    });
    document.getElementById('quoteForm')?.addEventListener('submit', function() {
        document.querySelectorAll('.item-price').forEach(function(inp) {
            const v = readItemPrice(inp);
            inp.value = isNaN(v) || v < 0 ? '' : String(v);
        });
        const genDiscAmt = document.getElementById('generalDiscountAmount');
        if (genDiscAmt) {
            const v = readItemPrice(genDiscAmt);
            genDiscAmt.value = isNaN(v) || v < 0 ? '' : String(v);
        }
    });
    updateQuoteTotals();
}
</script>
@endsection
