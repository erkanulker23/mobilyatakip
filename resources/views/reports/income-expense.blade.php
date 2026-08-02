@extends('layouts.app')
@section('title', 'Gelir – Gider Raporu')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Gelir – Gider Raporu</h1>
        <p class="page-desc">Tarih aralığına göre gelir ve gider özeti — {{ $periodLabel }}</p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.income-expense.print'])
</div>

<div class="card p-6 mb-6">
    @include('reports.partials.date-filters', ['submitLabel' => 'Hesapla'])
</div>

<div class="card overflow-hidden">
    @include('reports.partials.income-expense-table')
</div>
@endsection
