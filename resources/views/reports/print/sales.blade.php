@extends('layouts.print')
@section('title', 'Satış Raporu - Yazdır')
@section('content')
@php
    $periodLabel = ! empty($applyDateFilter)
        ? \App\Support\ReportFilters::periodLabel($from, $to, $year)
        : 'Tüm dönem';
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
        @include('partials.print-document-footer', ['documentRef' => 'Satış Raporu · ' . $printSubtitle, 'footerNote' => null])
        </div>
    </div>
</div>
@endsection
