@extends('layouts.app')
@section('title', 'Müşteri Ödeme Al')
@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
@endpush
@section('content')
@php
    $paymentLabels = \App\Support\PaymentType::labels();
    $selectedSaleId = old('saleId', request('saleId'));
    $salesMeta = $openSales->mapWithKeys(function ($sale) {
        $remaining = \App\Support\CustomerBalance::saleRemaining($sale);
        return [$sale->id => [
            'saleNumber' => $sale->saleNumber,
            'saleDate' => $sale->saleDate?->format('d.m.Y') ?? '—',
            'grandTotal' => (float) $sale->grandTotal,
            'paidAmount' => (float) ($sale->paidAmount ?? 0),
            'remaining' => $remaining,
            'remainingFormatted' => money($remaining > 0 ? $remaining : 0),
            'isPaid' => $remaining <= 0,
        ]];
    });
@endphp

<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <span class="text-neutral-700">Tahsilat</span>
            </div>
            <h1 class="page-title">Müşteri Ödeme Al</h1>
            <p class="page-desc">Müşteriden tahsilat kaydı oluşturun, faturaya bağlayın ve kasaya işleyin</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center px-3 py-2 rounded-lg bg-neutral-900 text-white text-sm font-medium">Yeni Tahsilat</span>
            <a href="{{ route('customer-payments.create', ['list' => 1]) }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-neutral-200 dark:border-slate-600 text-sm font-medium text-neutral-700 dark:text-slate-200 hover:bg-neutral-50 dark:hover:bg-slate-800">Tahsilat Kayıtları</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2">
        <div class="card p-6">
            <form method="POST" action="{{ route('customer-payments.store') }}" class="space-y-5" id="paymentForm">
                @csrf
                <div>
                    <label class="form-label">Müşteri *</label>
                    <select name="customerId" required class="form-select" id="customerSelect" data-placeholder="Müşteri ara veya seçin...">
                        <option value="">Seçiniz</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customerId', $customerId) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">İlgili Fatura</label>
                    <select name="saleId" class="form-select" id="saleSelect" @if($openSales->isEmpty()) disabled @endif>
                        <option value="">Genel tahsilat (faturaya bağlama)</option>
                        @foreach($openSales as $s)
                        @php $meta = $salesMeta[$s->id] ?? null; @endphp
                        <option value="{{ $s->id }}" data-remaining="{{ $meta['remaining'] ?? 0 }}" {{ (string) $selectedSaleId === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->saleNumber }} — {{ $meta['saleDate'] ?? '—' }} — Kalan {{ number_format($meta['remaining'] ?? 0, 0, ',', '.') }} ₺
                        </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Fatura seçilirse tutar, o faturanın kalan borcunu aşamaz. Sağdaki listeden hızlı seçim de yapabilirsiniz.</p>
                    @error('saleId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label class="form-label mb-0">Tutar (₺) *</label>
                            <button type="button" id="fillRemainingBtn" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 hidden">Kalan tutarı doldur</button>
                        </div>
                        <input type="text" inputmode="decimal" name="amount" id="amountInput" required value="{{ old('amount') }}" class="form-input money-input text-lg font-semibold" placeholder="0" autocomplete="off">
                        <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400" id="amountHint"></p>
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
                        <select name="paymentType" class="form-select" id="paymentTypeSelect">
                            @foreach(\App\Support\PaymentType::SELECTABLE as $value => $label)
                            <option value="{{ $value }}" {{ old('paymentType', 'nakit') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        @include('partials.payment-kasa-field', [
                            'kasalar' => $kasalar,
                            'paymentTypeId' => 'paymentTypeSelect',
                            'amountId' => 'amountInput',
                        ])
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Referans</label>
                        <input type="text" name="reference" value="{{ old('reference') }}" class="form-input" placeholder="Havale dekont no, çek no vb.">
                        @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Not</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="İsteğe bağlı açıklama">
                        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2 border-t border-neutral-100 dark:border-slate-700">
                    <button type="submit" class="btn-primary min-h-[44px]">Tahsilat Kaydet</button>
                    <a href="{{ route('dashboard') }}" class="btn-secondary min-h-[44px]">İptal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-5">
        @if($selectedCustomer)
        @php $cariStatus = \App\Support\CustomerBalance::customerStatus((float) ($totalSalesSum ?? 0), (float) ($totalPaidSum ?? 0)); @endphp
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Seçili Müşteri</p>
                    <a href="{{ route('customers.show', $selectedCustomer) }}" class="mt-1 block text-lg font-semibold text-neutral-900 dark:text-white hover:text-emerald-600">{{ $selectedCustomer->name }}</a>
                    @if($selectedCustomer->phone)
                    <p class="mt-1 text-sm text-neutral-500">{{ $selectedCustomer->phone }}</p>
                    @endif
                </div>
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ \App\Support\CustomerBalance::badgeClass($cariStatus['key']) }}">{{ $cariStatus['label'] }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-neutral-50 dark:bg-slate-800/60 p-3">
                    <dt class="text-neutral-500">Toplam satış</dt>
                    <dd class="mt-1 font-semibold tabular-nums">{{ number_format($totalSalesSum ?? 0, 0, ',', '.') }} ₺</dd>
                </div>
                <div class="rounded-lg bg-neutral-50 dark:bg-slate-800/60 p-3">
                    <dt class="text-neutral-500">Toplam tahsilat</dt>
                    <dd class="mt-1 font-semibold tabular-nums">{{ number_format($totalPaidSum ?? 0, 0, ',', '.') }} ₺</dd>
                </div>
            </dl>
            @if(($cariStatus['amount'] ?? 0) > 0)
            <p class="mt-3 text-sm font-medium {{ $cariStatus['key'] === 'borclu' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                {{ $cariStatus['amountLabel'] ?? 'Bakiye' }}: {{ number_format($cariStatus['amount'], 0, ',', '.') }} ₺
            </p>
            @endif
        </div>
        @else
        <div class="card p-5">
            <p class="text-sm text-neutral-500 dark:text-slate-400">Müşteri seçtiğinizde cari durumu, açık faturalar ve son tahsilatlar burada görünür.</p>
        </div>
        @endif

        @if($openSales->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Açık Faturalar</span>
                <span class="text-xs font-normal text-neutral-500">{{ $openSales->count() }} fatura</span>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($openSales as $s)
                @php
                    $meta = $salesMeta[$s->id];
                    $remaining = $meta['remaining'];
                    $isSelected = (string) $selectedSaleId === (string) $s->id;
                @endphp
                <button type="button"
                    class="w-full text-left p-4 hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors invoice-pick {{ $isSelected ? 'bg-emerald-50/70 dark:bg-emerald-900/20' : '' }}"
                    data-sale-id="{{ $s->id }}"
                    data-remaining="{{ $remaining > 0 ? $remaining : 0 }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-neutral-900 dark:text-white">{{ $s->saleNumber }}</p>
                            <p class="text-xs text-neutral-500 mt-0.5">{{ $meta['saleDate'] }}</p>
                        </div>
                        <div class="text-right">
                            @if($remaining > 0)
                            <p class="font-semibold text-red-600 dark:text-red-400 tabular-nums">{{ number_format($remaining, 0, ',', '.') }} ₺</p>
                            <p class="text-[11px] text-neutral-500">kalan</p>
                            @else
                            <p class="text-xs font-medium text-emerald-600">Ödendi</p>
                            @endif
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-neutral-500">Toplam {{ number_format($meta['grandTotal'], 0, ',', '.') }} ₺ · Ödenen {{ number_format($meta['paidAmount'], 0, ',', '.') }} ₺</p>
                </button>
                @endforeach
            </div>
        </div>
        @elseif($selectedCustomer)
        <div class="card p-5">
            <p class="text-sm text-neutral-500">Bu müşterinin açık faturası yok. Genel tahsilat kaydı oluşturabilirsiniz.</p>
        </div>
        @endif

        @if($recentPayments->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="card-header">Son Tahsilatlar</div>
            <div class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($recentPayments as $payment)
                <a href="{{ route('customer-payments.show', $payment) }}" class="block p-4 hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium text-emerald-600 dark:text-emerald-400 tabular-nums">{{ number_format($payment->amount, 0, ',', '.') }} ₺</p>
                            <p class="text-xs text-neutral-500 mt-0.5">{{ $payment->paymentDate?->format('d.m.Y') }} · {{ $paymentLabels[$payment->paymentType] ?? $payment->paymentType }}</p>
                        </div>
                        <div class="text-right text-xs text-neutral-500">
                            @if($payment->sale)
                            {{ $payment->sale->saleNumber }}
                            @else
                            Genel
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
const salesMeta = @json($salesMeta);
const createUrl = @json(route('customer-payments.create'));

document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';
        s.onload = initPaymentPage;
        document.head.appendChild(s);
    } else {
        initPaymentPage();
    }
});

function initPaymentPage() {
    const saleSelect = document.getElementById('saleSelect');
    if (!saleSelect || !saleSelect.value) return null;
    const option = saleSelect.selectedOptions[0];
    const remaining = parseFloat(option?.dataset?.remaining ?? '');
    return Number.isFinite(remaining) ? remaining : null;
}

function updateAmountHint() {
    const hint = document.getElementById('amountHint');
    const fillBtn = document.getElementById('fillRemainingBtn');
    const remaining = selectedSaleRemaining();
    if (!hint || !fillBtn) return;

    if (remaining === null) {
        hint.textContent = 'Fatura seçilmedi — genel tahsilat kaydı.';
        fillBtn.classList.add('hidden');
        return;
    }

    if (remaining <= 0) {
        hint.textContent = 'Seçili fatura tamamen ödenmiş.';
        fillBtn.classList.add('hidden');
        return;
    }

    hint.textContent = 'Bu fatura için en fazla ' + new Intl.NumberFormat('tr-TR').format(remaining) + ' ₺ tahsil edilebilir.';
    fillBtn.classList.remove('hidden');
}

function fillRemainingAmount() {
    const remaining = selectedSaleRemaining();
    const amountInput = document.getElementById('amountInput');
    if (!amountInput || remaining === null || remaining <= 0) return;
    amountInput.value = String(remaining);
    if (window.formatMoneyInput) window.formatMoneyInput(amountInput);
}

function highlightInvoiceButtons() {
    const saleSelect = document.getElementById('saleSelect');
    const current = saleSelect?.value || '';
    document.querySelectorAll('.invoice-pick').forEach(function(btn) {
        btn.classList.toggle('bg-emerald-50/70', btn.dataset.saleId === current);
        btn.classList.toggle('dark:bg-emerald-900/20', btn.dataset.saleId === current);
    });
}

function initPaymentPage() {
    if (window.initPaymentKasaFields) window.initPaymentKasaFields();

    const saleSelect = document.getElementById('saleSelect');
    if (saleSelect) {
        saleSelect.addEventListener('change', function() {
            updateAmountHint();
            highlightInvoiceButtons();
            const remaining = selectedSaleRemaining();
            if (remaining !== null && remaining > 0 && !document.getElementById('amountInput')?.value) {
                fillRemainingAmount();
            }
        });
    }

    document.getElementById('fillRemainingBtn')?.addEventListener('click', fillRemainingAmount);

    document.querySelectorAll('.invoice-pick').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!saleSelect) return;
            saleSelect.value = btn.dataset.saleId || '';
            saleSelect.dispatchEvent(new Event('change'));
            fillRemainingAmount();
            document.getElementById('amountInput')?.focus();
        });
    });

    updateAmountHint();
    highlightInvoiceButtons();

    const sel = document.getElementById('customerSelect');
    if (!sel || typeof TomSelect === 'undefined') return;
    const initialVal = sel.value;
    new TomSelect(sel, {
        maxOptions: 100,
        placeholder: 'Müşteri ara veya seçin...',
        searchField: ['text'],
        onChange: function(v) {
            if (v && v !== initialVal) {
                window.location.href = createUrl + '?customerId=' + encodeURIComponent(v);
            }
        }
    });

    if (saleSelect?.value && selectedSaleRemaining() > 0 && !document.getElementById('amountInput')?.value) {
        fillRemainingAmount();
    }
}
</script>
@endsection
