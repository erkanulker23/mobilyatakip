@extends('layouts.app')
@section('title', 'Ödeme Yap (Tedarikçi)')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
@endpush
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('suppliers.index') }}" class="hover:text-slate-700">Tedarikçiler</a>
        <span>/</span>
        <span class="text-slate-700">Ödeme Yap</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Ödeme Yap (Tedarikçi)</h1>
    <p class="text-slate-600 mt-1">Tedarikçiye ödeme kaydı oluşturun</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300">{{ session('error') }}</div>
@endif

<div class="bg-white dark:bg-slate-800 p-6 max-w-2xl">
    <form method="POST" action="{{ route('supplier-payments.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Tedarikçi *</label>
            <select name="supplierId" required class="form-select" id="supplierSelect" placeholder="Tedarikçi ara veya seçin..." data-create-url="{{ route('supplier-payments.create') }}">
                <option value="">Seçiniz</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ old('supplierId', $supplierId ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('supplierId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @if($supplierBalance !== null)
        <div class="grid grid-cols-3 gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Borç (toplam alış)</p>
                <p class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5">{{ number_format($supplierBalance->borc, 0, ',', '.') }} ₺</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alacak (ödenen)</p>
                <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ number_format($supplierBalance->alacak, 0, ',', '.') }} ₺</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bakiye</p>
                <p class="text-lg font-semibold mt-0.5 {{ $supplierBalance->bakiye > 0 ? 'text-red-600 dark:text-red-400' : ($supplierBalance->bakiye < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">{{ number_format($supplierBalance->bakiye, 0, ',', '.') }} ₺</p>
                @if($supplierBalance->bakiye != 0)<p class="text-xs text-slate-500 dark:text-slate-400">{{ $supplierBalance->bakiye > 0 ? 'Tedarikçiye borç' : 'Tedarikçiden alacak' }}</p>@endif
            </div>
        </div>
        @endif
        <div>
            <label class="form-label">Alış faturası (opsiyonel)</label>
            <select name="purchaseId" class="form-select">
                <option value="">— Genel ödeme —</option>
                @foreach($openPurchases as $p)
                @php $kalan = (float)$p->grandTotal - (float)($p->paidAmount ?? 0); @endphp
                <option value="{{ $p->id }}" {{ old('purchaseId') == $p->id ? 'selected' : '' }}>
                    {{ $p->purchaseNumber }} — {{ $p->supplier?->name }} (Kalan: {{ number_format($kalan, 0, ',', '.') }} ₺)
                </option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount') }}" class="form-input" placeholder="0.00">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih *</label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', date('Y-m-d')) }}" class="form-input">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select">
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
                <select name="kasaId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference') }}" class="form-input" placeholder="Havale dekont no, çek no vb.">
        </div>
        <div>
            <label class="form-label">Not</label>
            <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Ödeme Kaydet</button>
            <a href="{{ request()->get('supplierId') ? route('suppliers.show', request('supplierId')) : route('suppliers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = initSupplierSelect;
        document.head.appendChild(s);
    } else initSupplierSelect();
});
function initSupplierSelect() {
    const sel = document.getElementById('supplierSelect');
    if (!sel || typeof TomSelect === 'undefined') return;
    const createUrl = sel.getAttribute('data-create-url') || (window.location.pathname + '');
    new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Tedarikçi ara veya seçin...',
        searchField: ['text'],
        onChange: function(value) {
            if (value) {
                var url = createUrl + (createUrl.indexOf('?') >= 0 ? '&' : '?') + 'supplierId=' + encodeURIComponent(value);
                window.location = url;
            } else {
                window.location = createUrl;
            }
        }
    });
}
</script>
@endsection
