@extends('layouts.app')
@section('title', 'Satış Raporu')
@section('content')
@php
    $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year);
    $filterDesc = $filters['label'] ?? null;
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
