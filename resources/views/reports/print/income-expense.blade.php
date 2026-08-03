@extends('layouts.print')
@section('title', 'Gelir Gider Raporu - Yazdır')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="print-document card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-6 space-y-6">
        @include('partials.print-brand-header', [
            'documentTitle' => 'GELİR – GİDER RAPORU',
            'documentNumber' => $periodLabel,
            'documentDate' => now(),
        ])
        @include('reports.partials.income-expense-table', ['print' => true])
        @include('reports.partials.income-expense-details', ['print' => true])
    </div>
</div>
@endsection
