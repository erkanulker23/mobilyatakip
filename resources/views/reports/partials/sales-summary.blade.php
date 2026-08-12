@php
    $print = $print ?? false;
    $periodLabel = $periodLabel ?? \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null);
    $cash = $cashAccounting ?? null;
@endphp
@if(! empty($applyDateFilter))
<div class="{{ $print ? 'mb-4 grid grid-cols-3 gap-3 text-sm' : 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6' }}">
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
    <div class="card p-4 border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/40 dark:bg-indigo-950/20">
        <p class="text-xs font-medium text-indigo-800 dark:text-indigo-300 uppercase tracking-wide">Dönem nakit tahsilat</p>
        <p class="text-2xl font-semibold text-indigo-700 dark:text-indigo-400 mt-1 tabular-nums">{{ number_format($cash['cashCollections'], 0, ',', '.') }} ₺</p>
        @if(($cash['cashOnPriorSales'] ?? 0) > 0.005 || ($cash['cashUnallocated'] ?? 0) > 0.005)
            <p class="text-xs text-indigo-700/80 dark:text-indigo-300/80 mt-1 tabular-nums">
                Dönem satışlarına {{ number_format($cash['cashOnPeriodSales'], 0, ',', '.') }} ₺
                @if(($cash['cashOnPriorSales'] ?? 0) > 0.005)
                · eski satışlara {{ number_format($cash['cashOnPriorSales'], 0, ',', '.') }} ₺
                @endif
                @if(($cash['cashUnallocated'] ?? 0) > 0.005)
                · atanmamış {{ number_format($cash['cashUnallocated'], 0, ',', '.') }} ₺
                @endif
            </p>
        @endif
        <p class="text-xs text-neutral-500 mt-1">Ödeme tarihine göre · hasılat − nakit ≠ alacak olabilir</p>
    </div>
    @endif
</div>
@if(! $print)
<p class="text-xs text-neutral-500 mb-6 -mt-2">
    Doğru denklem: <span class="font-medium text-neutral-700 dark:text-neutral-300">hasılat − siparişe işlenen tahsil ≈ kalan alacak</span>
    (fazla ödeme yoksa birebir). Nakit tahsilat eski sipariş ödemelerini de içerir; bu yüzden paneldeki “bu ay nakit” ile buradaki “sipariş tahsili” farklı olabilir.
</p>
@endif
@endif
