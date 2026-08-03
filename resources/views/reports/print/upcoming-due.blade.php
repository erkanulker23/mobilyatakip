@extends('layouts.print')
@section('title', 'Termin Yaklaşanlar - Yazdır')
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'TERMİN YAKLAŞANLAR',
            'documentNumber' => $days . ' gün',
            'documentDate' => now(),
            'documentSubtitle' => 'Son tarih: ' . $horizon->format('d.m.Y'),
        ])
        @include('reports.partials.upcoming-due-content', ['print' => true, 'forShipment' => false])
        </div>
    </div>
</div>
@endsection
