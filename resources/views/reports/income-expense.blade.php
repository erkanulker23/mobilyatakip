@extends('layouts.app')
@section('title', 'Gelir – Gider Raporu')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Gelir – Gider Raporu</h1>
        <p class="page-desc">Tahakkuk, nakit akışı ve hareket detayları — {{ $periodLabel }}</p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.income-expense.print'])
</div>

<div class="card p-6 mb-6">
    @include('reports.partials.date-filters', ['submitLabel' => 'Hesapla'])
</div>

@include('reports.partials.income-expense-summary-cards')

<div class="card overflow-hidden mb-6">
    <div class="card-header">Özet tablo</div>
    @include('reports.partials.income-expense-table')
</div>

@include('reports.partials.income-expense-details')
@endsection
