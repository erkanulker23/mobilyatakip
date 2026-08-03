@extends('layouts.app')
@section('title', 'Nakliye Ödemesi Yap')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@endpush
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('shipping-companies.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Nakliye Firmaları</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-neutral-300">Nakliye Ödemesi Yap</span>
    </div>
    <h1 class="page-title">Nakliye Ödemesi Yap</h1>
    <p class="page-desc">Nakliye firmasına ödeme kaydı — ürün teslimatında sipariş, SSH ödemesinde servis kaydı isteğe bağlı seçilebilir</p>
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
        <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200 dark:border-neutral-700">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Bu firmaya toplam ödenen</p>
            <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ number_format($totalPaid, 0, ',', '.') }} ₺</p>
        </div>
        @endif

        @include('partials.shipping-payment-link-fields', [
            'purchasesWithShipping' => $purchasesWithShipping,
            'sales' => $sales,
            'serviceTickets' => $serviceTickets,
            'linkType' => $linkType,
            'preselectedSaleId' => $preselectedSaleId ?? null,
            'preselectedServiceTicketId' => $preselectedServiceTicketId ?? null,
            'preselectedPurchaseId' => $preselectedPurchaseId ?? null,
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) <span class="text-red-500">*</span></label>
                <input type="text" inputmode="decimal" name="amount" required value="{{ old('amount') }}" class="form-input min-h-[44px] money-input" placeholder="0" autocomplete="off">
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
                    @foreach(\App\Support\PaymentType::SELECTABLE as $value => $label)
                    <option value="{{ $value }}" {{ old('paymentType', 'nakit') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
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
            <label class="form-label">Ödeme referansı</label>
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
    const sel = document.getElementById('shippingCompanySelect');
    if (!sel || typeof TomSelect === 'undefined') return;
    const createUrl = sel.getAttribute('data-create-url') || window.location.pathname;
    new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Nakliye firması ara veya seçin...',
        searchField: ['text'],
        onChange: function(value) {
            const params = new URLSearchParams(window.location.search);
            if (value) {
                params.set('shippingCompanyId', value);
            } else {
                params.delete('shippingCompanyId');
            }
            const linkTypeEl = document.getElementById('payment-link-type');
            if (linkTypeEl?.value) {
                params.set('linkType', linkTypeEl.value);
            }
            const saleEl = document.getElementById('payment-sale-id');
            if (saleEl?.value) {
                params.set('saleId', saleEl.value);
            }
            const sshEl = document.getElementById('payment-service-ticket-id');
            if (sshEl?.value) {
                params.set('serviceTicketId', sshEl.value);
            }
            const qs = params.toString();
            window.location = createUrl + (qs ? '?' + qs : '');
        }
    });
});
</script>
@endsection
