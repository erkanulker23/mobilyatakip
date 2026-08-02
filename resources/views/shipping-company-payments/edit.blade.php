@extends('layouts.app')
@section('title', 'Nakliye Ödemesi Düzenle')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@endpush
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('shipping-companies.show', $shippingCompanyPayment->shippingCompany) }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Nakliye Firması</a>
        <span>/</span>
        <a href="{{ route('shipping-company-payments.show', $shippingCompanyPayment) }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Ödeme</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-neutral-300">Düzenle</span>
    </div>
    <h1 class="page-title">Nakliye Ödemesi Düzenle</h1>
    <p class="page-desc">{{ $shippingCompanyPayment->shippingCompany?->name ?? 'Nakliye' }} · {{ number_format($shippingCompanyPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">{{ session('error') }}</div>
@endif

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('shipping-company-payments.update', $shippingCompanyPayment) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Nakliye Firması</label>
            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $shippingCompanyPayment->shippingCompany?->name ?? '—' }}</p>
        </div>

        @include('partials.shipping-payment-link-fields', [
            'purchasesWithShipping' => $purchasesWithShipping,
            'sales' => $sales,
            'serviceTickets' => $serviceTickets,
            'linkType' => $linkType,
            'selectedPurchaseId' => $shippingCompanyPayment->purchaseId,
            'selectedSaleId' => $shippingCompanyPayment->saleId,
            'selectedServiceTicketId' => $shippingCompanyPayment->serviceTicketId,
            'selectedPaymentFor' => $shippingCompanyPayment->paymentFor,
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) <span class="text-red-500">*</span></label>
                <input type="text" inputmode="decimal" name="amount" required value="{{ old('amount', money($shippingCompanyPayment->amount)) }}" class="form-input min-h-[44px] money-input" placeholder="0" autocomplete="off">
                @error('amount')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih <span class="text-red-500">*</span></label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', $shippingCompanyPayment->paymentDate?->format('Y-m-d')) }}" class="form-input min-h-[44px]">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select min-h-[44px]">
                    @foreach(\App\Support\PaymentType::SELECTABLE as $value => $label)
                    <option value="{{ $value }}" {{ old('paymentType', $shippingCompanyPayment->paymentType) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Kasa</label>
                <select name="kasaId" class="form-select min-h-[44px]">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId', $shippingCompanyPayment->kasaId) == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="form-label">Ödeme referansı</label>
            <input type="text" name="reference" value="{{ old('reference', $shippingCompanyPayment->reference) }}" class="form-input min-h-[44px]" placeholder="Havale dekont no, çek no vb.">
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="2" class="form-input form-textarea">{{ old('notes', $shippingCompanyPayment->notes) }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('shipping-company-payments.show', $shippingCompanyPayment) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
