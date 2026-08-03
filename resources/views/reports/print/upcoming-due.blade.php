@extends('layouts.print')
@section('title', 'Termin Yaklaşanlar - Yazdır')
@section('content')
@php
    $printSubtitle = 'Son tarih: ' . $horizon->format('d.m.Y');
    if (!empty($filters['label'])) {
        $printSubtitle .= ' · ' . $filters['label'];
    }
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'TERMİN YAKLAŞANLAR',
            'documentNumber' => $days . ' gün',
            'documentDate' => now(),
            'documentSubtitle' => $printSubtitle,
        ])
        @include('reports.partials.upcoming-due-content', ['print' => true, 'forShipment' => false])
        @include('partials.print-document-footer', ['documentRef' => 'Termin · ' . $days . ' gün', 'footerNote' => null])
        </div>
    </div>
</div>
@endsection
