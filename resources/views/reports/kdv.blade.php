@extends('layouts.app')
@section('title', 'KDV Raporu')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">KDV Raporu</h1>
        <p class="page-desc">Oran bazlı KDV özeti — {{ $periodLabel }}</p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.kdv.print'])
</div>

<div class="card p-6 mb-6">
    @include('reports.partials.date-filters', ['submitLabel' => 'Hesapla'])
</div>

@include('reports.partials.kdv-content')
@endsection
