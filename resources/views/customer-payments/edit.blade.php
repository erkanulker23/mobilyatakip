@extends('layouts.app')
@section('title', 'Tahsilat Düzenle')
@section('content')
@php
    $paymentTypes = \App\Support\PaymentType::CUSTOMER_RECEIVE;
    if ($customerPayment->paymentType && !isset($paymentTypes[$customerPayment->paymentType])) {
        $paymentTypes[$customerPayment->paymentType] = \App\Support\PaymentType::label($customerPayment->paymentType);
    }
@endphp
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('customers.show', $customerPayment->customer) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Müşteri</a>
        <span>/</span>
        <a href="{{ route('customer-payments.show', $customerPayment) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Tahsilat</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-slate-300">Düzenle</span>
    </div>
    <h1 class="page-title">Tahsilat Düzenle</h1>
    <p class="page-desc">{{ $customerPayment->customer?->name ?? 'Müşteri' }} · {{ number_format($customerPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">{{ session('error') }}</div>
@endif
<div class="bg-white dark:bg-slate-800 p-6 max-w-2xl rounded-xl shadow-sm border border-neutral-200 dark:border-slate-700">
    <form method="POST" action="{{ route('customer-payments.update', $customerPayment) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <div>
            <label class="form-label">Müşteri</label>
            <p class="font-medium text-neutral-900 dark:text-white">{{ $customerPayment->customer?->name ?? '—' }}</p>
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
                <input type="text" inputmode="decimal" name="amount" required value="{{ old('amount', money($customerPayment->amount)) }}" class="form-input money-input" placeholder="0" autocomplete="off">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tahsilat Tarihi *</label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', $customerPayment->paymentDate?->format('Y-m-d')) }}" class="form-input" max="{{ date('Y-m-d') }}">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select" id="editPaymentType">
                    @foreach($paymentTypes as $value => $label)
                    <option value="{{ $value }}" {{ old('paymentType', $customerPayment->paymentType) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                @include('partials.payment-kasa-field', [
                    'kasalar' => $kasalar,
                    'paymentTypeId' => 'editPaymentType',
                    'selected' => old('kasaId', $customerPayment->kasaId),
                ])
            </div>
        </div>
        <div id="editSupplierFieldWrap" class="{{ old('paymentType', $customerPayment->paymentType) === 'tedarikciye_ode' ? '' : 'hidden' }}">
            <label class="form-label">Tedarikçi *</label>
            <select name="supplierId" class="form-select" id="editSupplierSelect">
                <option value="">Tedarikçi seçin</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ old('supplierId', $customerPayment->supplierId) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('supplierId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('customer-payments.show', $customerPayment) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleEditSupplierField() {
        const pt = document.getElementById('editPaymentType')?.value || '';
        const wrap = document.getElementById('editSupplierFieldWrap');
        const supplierSelect = document.getElementById('editSupplierSelect');
        const isSupplierPay = pt === 'tedarikciye_ode';
        if (wrap) wrap.classList.toggle('hidden', !isSupplierPay);
        if (supplierSelect) supplierSelect.required = isSupplierPay;
    }
    if (window.initPaymentKasaFields) window.initPaymentKasaFields();
    document.getElementById('editPaymentType')?.addEventListener('change', function() {
        toggleEditSupplierField();
        if (window.initPaymentKasaFields) window.initPaymentKasaFields();
    });
    toggleEditSupplierField();
});
</script>
@endsection
