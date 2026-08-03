@extends('layouts.print')
@section('title', 'KDV Raporu - Yazdır')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'KDV RAPORU',
            'documentNumber' => $periodLabel,
            'documentDate' => now(),
        ])
        @include('reports.partials.kdv-content', ['print' => true])
        </div>
    </div>
</div>
@endsection
