@extends('layouts.app')
@section('title', 'Satış Raporu')
@section('content')
@php
    $periodLabel = ! empty($applyDateFilter)
        ? \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null)
        : 'Tüm dönem';
    $filterDesc = $filters['label'] ?? null;
    $queryKeys = ['from', 'to', 'year', 'month', 'period', 'personnelId', 'branchId', 'odeme'];
    $reportChip = fn (array $params) => route('reports.sales', array_filter(array_merge(
        request()->only($queryKeys),
        $params
    ), fn ($v) => $v !== null && $v !== ''));
    $periodPresets = [
        ['label' => 'Bu ay', 'params' => ['period' => 'this_month', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'this_month' || (! request()->hasAny(['period', 'from', 'to', 'year', 'month']))],
        ['label' => 'Geçen ay', 'params' => ['period' => 'last_month', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'last_month'],
        ['label' => 'Bu yıl', 'params' => ['period' => 'this_year', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'this_year'],
        ['label' => 'Geçen yıl', 'params' => ['period' => 'last_year', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'last_year'],
    ];
    $odemePresets = [
        ['label' => 'Tümü', 'value' => null],
        ['label' => 'Borçlu', 'value' => 'borclu'],
        ['label' => 'Kapalı', 'value' => 'borcsuz'],
    ];
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Satış Raporu</h1>
        <p class="page-desc">
            Dönemsel hasılat, tahsilat ve kasa ödemeleri — {{ $periodLabel }}
            @if($filterDesc)
            <span class="text-neutral-500">· {{ $filterDesc }}</span>
            @endif
        </p>
    </div>
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.sales.print',
        'printParams' => request()->only($queryKeys),
    ])
</div>

<div class="card p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Filtreleme</p>
        <a href="{{ route('reports.sales', ['period' => 'this_year']) }}" class="text-xs text-neutral-500 hover:text-emerald-600">Filtreleri sıfırla</a>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs text-neutral-400 mr-1">Dönem</span>
        @foreach($periodPresets as $preset)
        <a href="{{ $reportChip($preset['params']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $preset['active'] ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="text-xs text-neutral-400 mr-1">Ödeme</span>
        @foreach($odemePresets as $preset)
        @php $isActive = ($preset['value'] === null && ! request('odeme')) || request('odeme') === $preset['value']; @endphp
        <a href="{{ $reportChip(['odeme' => $preset['value']]) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>

    <form method="get" action="{{ route('reports.sales') }}" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 items-end">
        <input type="hidden" name="period" value="{{ request('period') }}">
        @include('reports.partials.date-filters', [
            'embedded' => true,
            'showMonth' => true,
            'dateFrom' => $from,
            'dateTo' => $to,
        ])
        @include('reports.partials.sales-filters', [
            'showOdemeFilter' => false,
            'showDeliveryFilter' => false,
        ])
        <div class="flex gap-2 xl:col-span-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('reports.sales', ['period' => 'this_year']) }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
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
            var periodInput = select.form.querySelector('input[name="period"]');
            if (periodInput) {
                periodInput.value = '';
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
            var periodInput = form.querySelector('input[name="period"]');
            if (periodInput) {
                periodInput.value = '';
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
