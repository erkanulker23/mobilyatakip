@php
    $print = $print ?? false;
    $periodLabel = $periodLabel ?? \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null);
    $cash = $cashAccounting ?? null;
@endphp
@if(! empty($applyDateFilter))
{{-- Satış özeti --}}
<div class="{{ $print ? 'mb-4 grid grid-cols-3 gap-3 text-sm' : 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4' }}">
    <div class="{{ $print ? 'border border-neutral-300 rounded p-3' : 'card p-4' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Dönem satış hasılatı</p>
        <p class="{{ $print ? 'text-lg' : 'text-2xl' }} font-semibold text-neutral-900 dark:text-neutral-100 mt-1 tabular-nums">{{ number_format($totals->grandTotal, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $totals->count }} satış · {{ $periodLabel }}</p>
    </div>
    <div class="{{ $print ? 'border border-neutral-300 rounded p-3' : 'card p-4' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Siparişe işlenen tahsil</p>
        <p class="{{ $print ? 'text-lg' : 'text-2xl' }} font-semibold text-emerald-600 mt-1 tabular-nums">{{ number_format($totals->paidAmount, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">Satışların ödenen alanı · satış tarihi</p>
    </div>
    <div class="{{ $print ? 'border border-neutral-300 rounded p-3' : 'card p-4' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Kalan alacak</p>
        <p class="{{ $print ? 'text-lg' : 'text-2xl' }} font-semibold {{ $totals->remaining > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1 tabular-nums">{{ number_format($totals->remaining, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">Dönem satışlarının kalan borcu</p>
    </div>
    @if($cash && ! $print)
    <div class="card p-4">
        <p class="text-xs font-medium text-indigo-800 dark:text-indigo-300 uppercase tracking-wide">Kasa tahsilatı</p>
        <p class="text-2xl font-semibold text-indigo-700 dark:text-indigo-400 mt-1 tabular-nums">{{ number_format($cash['cashCollections'], 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">Kasaya giren · ödeme tarihi</p>
    </div>
    @endif
</div>

@if($cash && ! $print)
{{-- Kasadan ödenenler — tür ayrımı --}}
<div class="card p-5 mb-6">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Toplam ödenenler (kasa çıkışı)</p>
            <p class="text-2xl font-semibold text-rose-600 mt-1 tabular-nums">{{ number_format($cash['cashOutflows'], 0, ',', '.') }} ₺</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-medium text-indigo-800 dark:text-indigo-300 uppercase tracking-wide">Dönem kasa neti</p>
            <p class="text-xl font-semibold {{ ($cash['cashNet'] ?? 0) < 0 ? 'text-rose-600' : 'text-indigo-700 dark:text-indigo-400' }} mt-1 tabular-nums">{{ number_format($cash['cashNet'], 0, ',', '.') }} ₺</p>
            <p class="text-xs text-neutral-500 mt-0.5 tabular-nums">{{ number_format($cash['cashCollections'], 0, ',', '.') }} − {{ number_format($cash['cashOutflows'], 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-lg border border-neutral-200 dark:border-slate-700 p-3">
            <p class="text-xs text-neutral-500">Gider</p>
            <p class="text-lg font-semibold text-rose-600 mt-1 tabular-nums">{{ number_format($cash['expenses'], 0, ',', '.') }} ₺</p>
            <p class="text-xs text-neutral-400 mt-1">Reklam, kira, genel…</p>
        </div>
        <div class="rounded-lg border border-neutral-200 dark:border-slate-700 p-3">
            <p class="text-xs text-neutral-500">Personel avansı</p>
            <p class="text-lg font-semibold text-amber-600 dark:text-amber-400 mt-1 tabular-nums">{{ number_format($cash['personnelAdvances'], 0, ',', '.') }} ₺</p>
            <p class="text-xs text-neutral-400 mt-1">Personele verilen</p>
        </div>
        <div class="rounded-lg border border-neutral-200 dark:border-slate-700 p-3">
            <p class="text-xs text-neutral-500">Tedarikçi ödemesi</p>
            <p class="text-lg font-semibold text-amber-700 dark:text-amber-300 mt-1 tabular-nums">{{ number_format($cash['supplierPayments'], 0, ',', '.') }} ₺</p>
            <p class="text-xs text-neutral-400 mt-1">Kasadan çıkan</p>
        </div>
        <div class="rounded-lg border border-neutral-200 dark:border-slate-700 p-3">
            <p class="text-xs text-neutral-500">Nakliye ödemesi</p>
            <p class="text-lg font-semibold text-orange-600 dark:text-orange-400 mt-1 tabular-nums">{{ number_format($cash['shippingPayments'], 0, ',', '.') }} ₺</p>
            <p class="text-xs text-neutral-400 mt-1">Nakliye firmasına</p>
        </div>
    </div>
</div>
@endif

@if(! $print)
<p class="text-xs text-neutral-500 mb-6 -mt-2">
    Satış: <span class="font-medium text-neutral-700 dark:text-neutral-300">hasılat − siparişe işlenen tahsil ≈ kalan alacak</span>.
    Ödenenler yalnızca kasadan çıkan hareketlerdir (gider, avans, tedarikçi, nakliye).
</p>
@endif
@endif
