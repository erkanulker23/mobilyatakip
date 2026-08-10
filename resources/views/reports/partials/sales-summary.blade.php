@php
    $print = $print ?? false;
    $periodLabel = $periodLabel ?? \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null);
@endphp
@if(! empty($applyDateFilter))
<div class="{{ $print ? 'mb-4 grid grid-cols-3 gap-3 text-sm' : 'grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6' }}">
    <div class="{{ $print ? 'border border-neutral-300 rounded p-3' : 'card p-4' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Dönem satış hasılatı</p>
        <p class="{{ $print ? 'text-lg' : 'text-2xl' }} font-semibold text-neutral-900 dark:text-neutral-100 mt-1 tabular-nums">{{ number_format($totals->grandTotal, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $totals->count }} satış · {{ $periodLabel }}</p>
    </div>
    <div class="{{ $print ? 'border border-neutral-300 rounded p-3' : 'card p-4' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Tahsil edilen</p>
        <p class="{{ $print ? 'text-lg' : 'text-2xl' }} font-semibold text-emerald-600 mt-1 tabular-nums">{{ number_format($totals->paidAmount, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">Siparişe işlenen ödemeler</p>
    </div>
    <div class="{{ $print ? 'border border-neutral-300 rounded p-3' : 'card p-4' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Kalan alacak</p>
        <p class="{{ $print ? 'text-lg' : 'text-2xl' }} font-semibold {{ $totals->remaining > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1 tabular-nums">{{ number_format($totals->remaining, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">Hasılat − tahsil (dönem satışları)</p>
    </div>
</div>
@endif
