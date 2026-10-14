@extends('layouts.app')
@section('title', 'Satış Raporu')
@section('content')
@php
    $periodLabel = ! empty($applyDateFilter)
        ? \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null)
        : 'Tüm dönem';
    $filterDesc = $filters['label'] ?? null;
    $queryKeys = ['from', 'to', 'period', 'personnelId', 'branchId', 'odeme'];
    $hasPresetPeriod = in_array(request('period'), ['this_month', 'last_month', 'this_year', 'last_year'], true);
    $isDefaultPeriod = ! request()->hasAny(['period', 'from', 'to', 'year', 'month']);
    $isCustomPeriod = ! $hasPresetPeriod && ! $isDefaultPeriod;
    $baseFilterParams = array_filter([
        'personnelId' => request('personnelId'),
        'branchId' => request('branchId'),
        'odeme' => request('odeme'),
    ], fn ($v) => $v !== null && $v !== '');
    $periodParams = $isCustomPeriod
        ? ['from' => $from?->format('Y-m-d'), 'to' => $to?->format('Y-m-d')]
        : ['period' => request('period', 'this_month')];
    $reportChip = fn (array $params) => route('reports.sales', array_filter(array_merge(
        $baseFilterParams,
        $params
    ), fn ($v) => $v !== null && $v !== ''));
    $periodPresets = [
        ['label' => 'Bu ay', 'params' => ['period' => 'this_month'], 'active' => ! $isCustomPeriod && (request('period') === 'this_month' || $isDefaultPeriod)],
        ['label' => 'Geçen ay', 'params' => ['period' => 'last_month'], 'active' => request('period') === 'last_month'],
        ['label' => 'Bu yıl', 'params' => ['period' => 'this_year'], 'active' => request('period') === 'this_year'],
        ['label' => 'Geçen yıl', 'params' => ['period' => 'last_year'], 'active' => request('period') === 'last_year'],
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

<div class="card p-5 mb-6 space-y-5" x-data="{ custom: @js($isCustomPeriod) }">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-neutral-400 mr-1 shrink-0">Dönem</span>
        @foreach($periodPresets as $preset)
        <a href="{{ $reportChip($preset['params']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $preset['active'] ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
        <button type="button"
                @click="custom = true"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                :class="custom ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700'">
            Özel tarih
        </button>
        @if(! $isDefaultPeriod)
        <a href="{{ route('reports.sales', ['period' => 'this_month']) }}" class="ml-auto text-xs text-neutral-500 hover:text-emerald-600">Sıfırla</a>
        @endif
    </div>

    <form method="get" action="{{ route('reports.sales') }}" class="space-y-4" x-show="custom" x-cloak>
        <div class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[150px]">
                <label class="form-label">Başlangıç</label>
                <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="form-input" required>
            </div>
            <div class="min-w-[150px]">
                <label class="form-label">Bitiş</label>
                <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="form-input" required>
            </div>
            @if(request('odeme'))
            <input type="hidden" name="odeme" value="{{ request('odeme') }}">
            @endif
            @if(request('personnelId'))
            <input type="hidden" name="personnelId" value="{{ request('personnelId') }}">
            @endif
            @if(request('branchId'))
            <input type="hidden" name="branchId" value="{{ request('branchId') }}">
            @endif
            <button type="submit" class="btn-primary">Uygula</button>
        </div>
    </form>

    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-neutral-400 mr-1 shrink-0">Ödeme</span>
        @foreach($odemePresets as $preset)
        @php $isActive = ($preset['value'] === null && ! request('odeme')) || request('odeme') === $preset['value']; @endphp
        <a href="{{ $reportChip(array_merge($periodParams, ['odeme' => $preset['value']])) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>

    <form method="get" action="{{ route('reports.sales') }}" class="flex flex-wrap gap-4 items-end">
        @if($isCustomPeriod)
            <input type="hidden" name="from" value="{{ $from?->format('Y-m-d') }}">
            <input type="hidden" name="to" value="{{ $to?->format('Y-m-d') }}">
        @else
            <input type="hidden" name="period" value="{{ request('period', 'this_month') }}">
        @endif
        @if(request('odeme'))
        <input type="hidden" name="odeme" value="{{ request('odeme') }}">
        @endif
        @include('reports.partials.sales-filters', [
            'showOdemeFilter' => false,
            'showDeliveryFilter' => false,
        ])
        <button type="submit" class="btn-primary">Uygula</button>
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
@endsection
