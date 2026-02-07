@extends('layouts.app')
@section('title', 'Tahsilat Düzenle')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('customers.show', $customerPayment->customer) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Müşteri</a>
        <span>/</span>
        <a href="{{ route('customer-payments.show', $customerPayment) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Tahsilat</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tahsilat Düzenle</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $customerPayment->customer?->name ?? 'Müşteri' }} · {{ number_format($customerPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">{{ session('error') }}</div>
@endif
<div class="bg-white dark:bg-slate-800 p-6 max-w-2xl rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <form method="POST" action="{{ route('customer-payments.update', $customerPayment) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <div>
            <label class="form-label">Müşteri</label>
            <p class="font-medium text-slate-900 dark:text-white">{{ $customerPayment->customer?->name ?? '—' }}</p>
            <input type="hidden" name="customerId" value="{{ $customerPayment->customerId }}">
        </div>
        @if($openSales->isNotEmpty())
        <div>
            <label class="form-label">İlgili Fatura (Opsiyonel)</label>
            <select name="saleId" class="form-select">
                <option value="">Faturaya bağlama</option>
                @foreach($openSales as $s)
                @php $kalan = (float)$s->grandTotal - (float)($s->paidAmount ?? 0); @endphp
                <option value="{{ $s->id }}" {{ old('saleId', $customerPayment->saleId) == $s->id ? 'selected' : '' }}>{{ $s->saleNumber }} — Kalan {{ number_format($kalan, 0, ',', '.') }} ₺</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $customerPayment->amount) }}" class="form-input" placeholder="0.00">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih *</label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', $customerPayment->paymentDate?->format('Y-m-d')) }}" class="form-input">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select" id="editPaymentType">
                    <option value="nakit" {{ old('paymentType', $customerPayment->paymentType) == 'nakit' ? 'selected' : '' }}>Nakit</option>
                    <option value="havale" {{ old('paymentType', $customerPayment->paymentType) == 'havale' ? 'selected' : '' }}>Havale</option>
                    <option value="kredi_karti" {{ old('paymentType', $customerPayment->paymentType) == 'kredi_karti' ? 'selected' : '' }}>Kredi Kartı</option>
                    <option value="cek" {{ old('paymentType', $customerPayment->paymentType) == 'cek' ? 'selected' : '' }}>Çek</option>
                    <option value="senet" {{ old('paymentType', $customerPayment->paymentType) == 'senet' ? 'selected' : '' }}>Senet</option>
                    <option value="diger" {{ old('paymentType', $customerPayment->paymentType) == 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div>
                <label class="form-label" id="editKasaLabel">Kasa <span class="text-amber-600 dark:text-amber-400" id="editKasaRequired">*</span></label>
                <select name="kasaId" class="form-select" id="editKasaId">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId', $customerPayment->kasaId) == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" id="editKasaHint">Nakit ve kredi kartı tahsilatları kasaya giriş olarak yansır.</p>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference', $customerPayment->reference) }}" class="form-input" placeholder="Havale dekont no, çek no vb.">
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="2" class="form-input">{{ old('notes', $customerPayment->notes) }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('customer-payments.show', $customerPayment) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pt = document.getElementById('editPaymentType');
    const kasaRequired = document.getElementById('editKasaRequired');
    if (pt && kasaRequired) {
        function updateKasaRequired() {
            const needsKasa = ['nakit', 'havale', 'kredi_karti'].includes(pt.value);
            kasaRequired.style.display = needsKasa ? 'inline' : 'none';
            document.getElementById('editKasaId').required = needsKasa;
        }
        pt.addEventListener('change', updateKasaRequired);
        updateKasaRequired();
    }
});
</script>
@endsection
