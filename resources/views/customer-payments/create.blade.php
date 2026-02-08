@extends('layouts.app')
@section('title', 'Müşteri Ödeme Al')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
@endpush
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <span class="text-slate-700">Müşteri Ödeme Al (Tahsilat)</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Müşteri Ödeme Al</h1>
    <p class="text-slate-600 mt-1">Müşteriden tahsilat kaydı oluşturun</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">{{ session('error') }}</div>
@endif
<div class="bg-white dark:bg-slate-800 p-6 max-w-2xl">
    <form method="POST" action="{{ route('customer-payments.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Müşteri *</label>
            <select name="customerId" required class="form-select" id="customerSelect" data-placeholder="Müşteri ara veya seçin...">
                <option value="">Seçiniz</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customerId', request('customerId')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @if($customerId && $totalDebt !== null)
        <div class="p-4 rounded-lg border {{ $totalDebt > 0 ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : 'bg-slate-50 dark:bg-slate-700/50 border-slate-200 dark:border-slate-600' }}">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Bu müşterinin borç özeti</p>
            <p class="mt-1 text-lg font-semibold {{ $totalDebt > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                Toplam kalan borç: {{ number_format($totalDebt, 0, ',', '.') }} ₺
            </p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Toplam satış: {{ number_format($totalSalesSum ?? 0, 0, ',', '.') }} ₺ — Toplam tahsilat: {{ number_format($totalPaidSum ?? 0, 0, ',', '.') }} ₺</p>
        </div>
        @endif
        @if($openSales->isNotEmpty())
        <div>
            <label class="form-label">İlgili Fatura (Opsiyonel)</label>
            <select name="saleId" class="form-select">
                <option value="">Faturaya bağlama</option>
                @foreach($openSales as $s)
                @php $kalan = (float)$s->grandTotal - (float)($s->paidAmount ?? 0); @endphp
                <option value="{{ $s->id }}" {{ old('saleId', request('saleId')) == $s->id ? 'selected' : '' }}>{{ $s->saleNumber }} — Kalan {{ number_format($kalan, 0, ',', '.') }} ₺</option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-slate-500">Seçilirse tahsilat faturaya bağlanır ve satış ödenen tutarı güncellenir.</p>
        </div>
        @endif
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
                    <option value="nakit" {{ old('paymentType') == 'nakit' ? 'selected' : '' }}>Nakit</option>
                    <option value="havale" {{ old('paymentType') == 'havale' ? 'selected' : '' }}>Havale</option>
                    <option value="kredi_karti" {{ old('paymentType') == 'kredi_karti' ? 'selected' : '' }}>Kredi Kartı</option>
                    <option value="cek" {{ old('paymentType') == 'cek' ? 'selected' : '' }}>Çek</option>
                    <option value="senet" {{ old('paymentType') == 'senet' ? 'selected' : '' }}>Senet</option>
                    <option value="diger" {{ old('paymentType') == 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div>
                <label class="form-label">Kasa <span class="text-amber-600 dark:text-amber-400" id="kasaRequiredHint">*</span></label>
                <select name="kasaId" class="form-select" id="kasaId">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nakit, havale ve kredi kartı tahsilatları kasaya giriş olarak yansır. <strong>Bu ödeme tiplerinde kasa zorunludur.</strong></p>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference') }}" class="form-input" placeholder="Havale dekont no, çek no vb.">
            @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Tahsilat Kaydet</button>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = initCustomerSelect;
        document.head.appendChild(s);
    } else initCustomerSelect();
});
function updateKasaRequired() {
    const pt = document.querySelector('select[name="paymentType"]');
    const kasa = document.getElementById('kasaId');
    const hint = document.getElementById('kasaRequiredHint');
    if (pt && kasa && hint) {
        const needsKasa = ['nakit', 'havale', 'kredi_karti'].includes(pt.value);
        hint.style.display = needsKasa ? 'inline' : 'none';
        kasa.required = needsKasa;
    }
}
function initCustomerSelect() {
    const pt = document.querySelector('select[name="paymentType"]');
    if (pt) {
        pt.addEventListener('change', updateKasaRequired);
        updateKasaRequired();
    }
    const sel = document.getElementById('customerSelect');
    if (!sel || typeof TomSelect === 'undefined') return;
    const initialVal = sel.value;
    new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Müşteri ara veya seçin...',
        searchField: ['text'],
        onChange: function(v) {
            if (v && v !== initialVal) window.location.href = '{{ route("customer-payments.create") }}?customerId=' + v;
        }
    });
}
</script>
@endsection
