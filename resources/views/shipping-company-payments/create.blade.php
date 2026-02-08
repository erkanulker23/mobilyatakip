@extends('layouts.app')
@section('title', 'Nakliye Ödemesi Yap')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
@endpush
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('shipping-companies.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Nakliye Firmaları</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300">Nakliye Ödemesi Yap</span>
    </div>
    <h1 class="page-title">Nakliye Ödemesi Yap</h1>
    <p class="page-desc">Nakliye firmasına ödeme kaydı oluşturun</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 rounded-xl">{{ session('error') }}</div>
@endif

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('shipping-company-payments.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Nakliye Firması <span class="text-red-500">*</span></label>
            <select name="shippingCompanyId" required class="form-select min-h-[44px]" id="shippingCompanySelect" data-create-url="{{ route('shipping-company-payments.create') }}">
                <option value="">Seçiniz</option>
                @foreach($shippingCompanies as $sc)
                <option value="{{ $sc->id }}" {{ old('shippingCompanyId', $shippingCompanyId ?? '') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                @endforeach
            </select>
            @error('shippingCompanyId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        @if($totalPaid !== null)
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bu firmaya toplam ödenen</p>
            <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ number_format($totalPaid, 0, ',', '.') }} ₺</p>
        </div>
        @endif
        <div>
            <label class="form-label">Alış faturası (opsiyonel) — ne için ödendi?</label>
            <select name="purchaseId" class="form-select min-h-[44px]">
                <option value="">— Genel ödeme —</option>
                @foreach($purchasesWithShipping as $p)
                <option value="{{ $p->id }}" {{ old('purchaseId') == $p->id ? 'selected' : '' }}>
                    {{ $p->purchaseNumber }} — {{ $p->supplier?->name }} ({{ $p->purchaseDate?->format('d.m.Y') }})
                </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Bu ödemenin hangi alış nakliyesi için yapıldığını seçebilirsiniz</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount') }}" class="form-input min-h-[44px]" placeholder="0.00">
                @error('amount')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih <span class="text-red-500">*</span></label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', date('Y-m-d')) }}" class="form-input min-h-[44px]">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select min-h-[44px]">
                    <option value="nakit" {{ old('paymentType', 'nakit') == 'nakit' ? 'selected' : '' }}>Nakit</option>
                    <option value="havale" {{ old('paymentType') == 'havale' ? 'selected' : '' }}>Havale</option>
                    <option value="kredi_karti" {{ old('paymentType') == 'kredi_karti' ? 'selected' : '' }}>Kredi Kartı</option>
                    <option value="cek" {{ old('paymentType') == 'cek' ? 'selected' : '' }}>Çek</option>
                    <option value="senet" {{ old('paymentType') == 'senet' ? 'selected' : '' }}>Senet</option>
                    <option value="diger" {{ old('paymentType') == 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div>
                <label class="form-label">Kasa</label>
                <select name="kasaId" class="form-select min-h-[44px]">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference') }}" class="form-input min-h-[44px]" placeholder="Havale dekont no, çek no vb.">
        </div>
        <div>
            <label class="form-label">Not</label>
            <textarea name="notes" rows="2" class="form-input form-textarea">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Ödeme Kaydet</button>
            <a href="{{ $shippingCompanyId ? route('shipping-companies.show', $shippingCompanyId) : route('shipping-companies.index') }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = initShippingSelect;
        document.head.appendChild(s);
    } else initShippingSelect();
});
function initShippingSelect() {
    const sel = document.getElementById('shippingCompanySelect');
    if (!sel || typeof TomSelect === 'undefined') return;
    const createUrl = sel.getAttribute('data-create-url') || window.location.pathname;
    new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Nakliye firması ara veya seçin...',
        searchField: ['text'],
        onChange: function(value) {
            if (value) {
                const url = createUrl + (createUrl.indexOf('?') >= 0 ? '&' : '?') + 'shippingCompanyId=' + encodeURIComponent(value);
                window.location = url;
            } else {
                window.location = createUrl;
            }
        }
    });
}
</script>
@endsection
