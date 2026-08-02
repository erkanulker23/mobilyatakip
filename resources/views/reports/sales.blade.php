@extends('layouts.app')
@section('title', 'Satış Raporu')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Satış Raporu</h1>
        <p class="page-desc">Tarih ve yıla göre satış listesi — {{ $periodLabel }}</p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.sales.print'])
</div>

<div class="card p-6 mb-6">
    @include('reports.partials.date-filters', ['submitLabel' => 'Listele'])
</div>

<div class="card overflow-hidden">
    @include('reports.partials.sales-table')
</div>
@endsection
