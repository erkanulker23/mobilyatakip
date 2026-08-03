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

<div class="card overflow-hidden">
    @include('reports.partials.sales-table')
</div>
@endsection
