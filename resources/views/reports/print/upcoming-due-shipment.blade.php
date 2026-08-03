@extends('layouts.print')
@section('title', 'Termin Yaklaşanlar - Sevkiyat')
@section('content')
@php
    $printSubtitle = 'Son tarih: ' . $horizon->format('d.m.Y') . ' · Fiyat bilgisi içermez';
    if (!empty($filters['label'])) {
        $printSubtitle .= ' · ' . $filters['label'];
    }
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'SEVKİYAT — TERMİN YAKLAŞANLAR',
            'documentNumber' => $days . ' gün',
            'documentDate' => now(),
            'documentSubtitle' => $printSubtitle,
        ])
        @include('reports.partials.upcoming-due-content', ['print' => true, 'forShipment' => true])
        @include('partials.print-document-footer', ['documentRef' => 'Sevkiyat Termin · ' . $days . ' gün', 'footerNote' => 'Fiyat bilgisi içermez.'])
        </div>
    </div>
</div>
@endsection
