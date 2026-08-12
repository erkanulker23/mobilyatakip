@extends('layouts.app')
@section('title', 'Satış Raporu')
@section('content')
@php
    $periodLabel = ! empty($applyDateFilter)
        ? \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null)
        : 'Tüm dönem';
    $filterDesc = $filters['label'] ?? null;
    $queryKeys = ['from', 'to', 'year', 'month', 'period', 'personnelId', 'branchId', 'odeme', 'deliveryStatus', 'allTime'];
    $reportChip = fn (array $params) => route('reports.sales', array_filter(array_merge(
        request()->only($queryKeys),
        $params
    ), fn ($v) => $v !== null && $v !== ''));
    $printChip = fn (array $params) => route('reports.sales.print', array_filter(array_merge(
        request()->only($queryKeys),
        $params
    ), fn ($v) => $v !== null && $v !== ''));
    $periodPresets = [
        ['label' => 'Bu ay', 'params' => ['period' => 'this_month', 'from' => null, 'to' => null, 'year' => null, 'month' => null, 'allTime' => null], 'active' => request('period') === 'this_month' || (! request()->hasAny(['period', 'from', 'to', 'year', 'month', 'allTime', 'deliveryStatus', 'odeme']) && empty($statusOnlyList))],
        ['label' => 'Geçen ay', 'params' => ['period' => 'last_month', 'from' => null, 'to' => null, 'year' => null, 'month' => null, 'allTime' => null], 'active' => request('period') === 'last_month'],
        ['label' => 'Bu yıl', 'params' => ['period' => 'this_year', 'from' => null, 'to' => null, 'year' => null, 'month' => null, 'allTime' => null], 'active' => request('period') === 'this_year'],
    ];
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Satış Raporu</h1>
        <p class="page-desc">
            Dönemsel satış hasılatı ve detay listesi — {{ $periodLabel }}
            @if($filterDesc)
            <span class="text-neutral-500">· {{ $filterDesc }}</span>
            @endif
        </p>
    </div>
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.sales.print',
        'printParams' => request()->query(),
    ])
</div>

<div class="card p-6 mb-6">
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400 mb-3">Dönem seçimi</p>
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @foreach($periodPresets as $preset)
        <a href="{{ $reportChip(array_merge($preset['params'], ['deliveryStatus' => null, 'odeme' => null])) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $preset['active'] && empty($statusOnlyList) ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>

    <form method="get" action="{{ route('reports.sales') }}" class="flex flex-wrap gap-4 items-end">
        @include('reports.partials.date-filters', [
            'embedded' => true,
            'submitLabel' => 'Hesapla',
            'showMonth' => true,
            'dateFrom' => $from,
            'dateTo' => $to,
        ])
        @include('reports.partials.sales-filters')
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Hesapla</button>
            <a href="{{ route('reports.sales', ['period' => 'this_month']) }}" class="btn-secondary">Bu aya dön</a>
        </div>
    </form>

    <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
        <span class="text-xs text-neutral-400 self-center mr-1">Operasyon listesi (tüm dönem):</span>
        @php
            $quickLists = [
                ['label' => 'Ölçüye gidilecekler', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT, 'odeme' => null, 'allTime' => 1, 'period' => null, 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::FINAL_MEASUREMENT && request()->boolean('allTime'), 'tone' => 'amber'],
                ['label' => 'Üretimde', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::IN_PRODUCTION, 'odeme' => null, 'allTime' => 1, 'period' => null, 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::IN_PRODUCTION && request()->boolean('allTime'), 'tone' => 'violet'],
                ['label' => 'Teslim bekleyenler', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::PENDING, 'odeme' => null, 'allTime' => 1, 'period' => null, 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::PENDING && request()->boolean('allTime') && !request('odeme'), 'tone' => 'neutral'],
                ['label' => 'Halen görüşülüyor', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::IN_DISCUSSION, 'odeme' => null, 'allTime' => 1, 'period' => null, 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::IN_DISCUSSION && request()->boolean('allTime'), 'tone' => 'sky'],
                ['label' => 'SSH var', 'params' => ['deliveryStatus' => \App\Support\SaleDelivery::SSH, 'odeme' => null, 'allTime' => 1, 'period' => null, 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('deliveryStatus') === \App\Support\SaleDelivery::SSH && request()->boolean('allTime'), 'tone' => 'orange'],
                ['label' => 'Borçlu', 'params' => ['odeme' => 'borclu', 'deliveryStatus' => null, 'allTime' => 1, 'period' => null, 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('odeme') === 'borclu' && request()->boolean('allTime') && !request('deliveryStatus'), 'tone' => 'red'],
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

@if($sales->isNotEmpty() || ! empty($applyDateFilter))
@include('reports.partials.sales-summary', compact('periodLabel'))
@endif

<div class="card overflow-hidden">
    <div class="card-header flex flex-wrap items-center justify-between gap-2">
        <span>Satış detay listesi</span>
        @if(! empty($applyDateFilter))
        <span class="text-xs font-normal text-neutral-500">Satış tarihine göre · {{ $periodLabel }}</span>
        @endif
    </div>
    @include('reports.partials.sales-table')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-report-year-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            var year = select.value;
            if (!year || !select.form) {
                return;
            }
            var fromInput = select.form.querySelector('[data-report-from]');
            var toInput = select.form.querySelector('[data-report-to]');
            var monthSelect = select.form.querySelector('[data-report-month-select]');
            if (monthSelect) {
                monthSelect.value = '';
            }
            if (fromInput) {
                fromInput.value = year + '-01-01';
            }
            if (toInput) {
                toInput.value = year + '-12-31';
            }
        });
    });

    document.querySelectorAll('[data-report-month-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            var month = select.value;
            var form = select.form;
            if (!form) {
                return;
            }
            var yearSelect = form.querySelector('[data-report-year-select]');
            var year = yearSelect && yearSelect.value ? yearSelect.value : String(new Date().getFullYear());
            if (!month) {
                return;
            }
            var padded = String(month).padStart(2, '0');
            var lastDay = new Date(Number(year), Number(month), 0).getDate();
            var fromInput = form.querySelector('[data-report-from]');
            var toInput = form.querySelector('[data-report-to]');
            if (fromInput) {
                fromInput.value = year + '-' + padded + '-01';
            }
            if (toInput) {
                toInput.value = year + '-' + padded + '-' + String(lastDay).padStart(2, '0');
            }
        });
    });
});
</script>
@endpush
@endsection
