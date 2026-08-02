@extends('layouts.print')
@section('title', 'Termin Yaklaşanlar - Sevkiyat')
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'SEVKİYAT — TERMİN YAKLAŞANLAR',
            'documentNumber' => $days . ' gün',
            'documentDate' => now(),
            'documentSubtitle' => 'Son tarih: ' . $horizon->format('d.m.Y') . ' · Fiyat bilgisi içermez',
        ])
        @include('reports.partials.upcoming-due-content', ['print' => true, 'forShipment' => true])
    </div>
</div>
@endsection
