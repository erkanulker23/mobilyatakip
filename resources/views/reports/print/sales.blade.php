@extends('layouts.print')
@section('title', 'Satış Raporu - Yazdır')
@section('content')
@php
    $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year);
    $printSubtitle = trim($periodLabel . ($filters['label'] ?? '' ? ' · ' . $filters['label'] : ''));
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'SATIŞ RAPORU',
            'documentNumber' => $printSubtitle,
            'documentDate' => now(),
        ])
        @include('reports.partials.sales-table', ['print' => true])
        </div>
    </div>
</div>
@endsection
