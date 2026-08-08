@extends('layouts.app')
@section('title', 'Satış Raporu')
@section('content')
@php
    $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year);
    if (!empty($skipDateFilter)) {
        $periodLabel = 'Tüm dönem';
    }
    $filterDesc = $filters['label'] ?? null;
    $reportChip = fn (array $params) => route('reports.sales', array_filter(array_merge(
        request()->only(['from', 'to', 'year', 'personnelId', 'odeme', 'deliveryStatus']),
        $params
    )));
    $printChip = fn (array $params) => route('reports.sales.print', array_filter(array_merge(
        request()->only(['from', 'to', 'year', 'personnelId', 'odeme', 'deliveryStatus']),
        $params
    )));
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Satış Raporu</h1>
        <p class="page-desc">
            Tarih ve filtreye göre satış listesi — {{ $periodLabel }}
            @if($filterDesc)
            <span class="text-neutral-500">· {{ $filterDesc }}</span>
            @endif
        </p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.sales.print'])
</div>

<div class="card p-6 mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        @include('reports.partials.date-filters', ['embedded' => true, 'submitLabel' => 'Listele'])
        @include('reports.partials.sales-filters')
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Listele</button>
            <a href="{{ route('reports.sales') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>

    <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
        <span class="text-xs text-neutral-400 self-center mr-1">Hızlı liste:</span>
        @php
            $quickLists = [
                ['label' => 'Borçlu', 'params' => ['odeme' => 'borclu', 'deliveryStatus' => null], 'active' => request('odeme') === 'borclu' && !request('deliveryStatus'), 'tone' => 'red'],
                ['label' => 'Ölçü bekliyor', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT, 'odeme' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::FINAL_MEASUREMENT, 'tone' => 'amber'],
                ['label' => 'Üretimde', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::IN_PRODUCTION, 'odeme' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::IN_PRODUCTION, 'tone' => 'violet'],
                ['label' => 'Teslim bekliyor', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::PENDING, 'odeme' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::PENDING && !request('odeme'), 'tone' => 'neutral'],
            ];
        @endphp
        @foreach($quickLists as $list)
        <div class="inline-flex rounded-lg overflow-hidden border border-neutral-200 dark:border-neutral-700">
            <a href="{{ $reportChip($list['params']) }}" class="px-2.5 py-1.5 text-xs font-medium transition-colors {{ $list['active'] ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
                {{ $list['label'] }}
            </a>
            <a href="{{ $printChip($list['params']) }}" target="_blank" rel="noopener" class="px-2 py-1.5 text-xs font-medium border-l border-neutral-200 dark:border-neutral-700 bg-white text-neutral-500 hover:text-emerald-600 hover:bg-neutral-50 dark:bg-neutral-900 dark:hover:bg-neutral-800" title="{{ $list['label'] }} listesini yazdır">
                Yazdır
            </a>
        </div>
        @endforeach
    </div>
</div>

@if($sales->isNotEmpty())
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam ciro</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1 tabular-nums">{{ number_format($totals->grandTotal, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $totals->count }} satış · {{ $periodLabel }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Tahsil edilen</p>
        <p class="text-2xl font-semibold text-emerald-600 mt-1 tabular-nums">{{ number_format($totals->paidAmount, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">Siparişe işlenen ödemeler</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Kalan borç</p>
        <p class="text-2xl font-semibold {{ $totals->remaining > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1 tabular-nums">{{ number_format($totals->remaining, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ number_format($totals->paidAmount + $totals->remaining, 0, ',', '.') }} ₺ = tahsil + kalan</p>
    </div>
</div>
@endif

<div class="card overflow-hidden">
    @include('reports.partials.sales-table')
</div>
@endsection
