@extends('layouts.print')
@section('title', 'Gelir Gider Raporu - Yazdır')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'GELİR – GİDER RAPORU',
            'documentNumber' => $periodLabel,
            'documentDate' => now(),
        ])
        @include('reports.partials.income-expense-table', ['print' => true])
        </div>
    </div>
</div>
@endsection
