@extends('layouts.print')
@section('title', 'Termin Yaklaşanlar - Yazdır')
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'TERMİN YAKLAŞANLAR',
            'documentNumber' => $days . ' gün',
            'documentDate' => now(),
            'documentSubtitle' => 'Son tarih: ' . $horizon->format('d.m.Y'),
        ])
        @include('reports.partials.upcoming-due-content', ['print' => true])
    </div>
</div>
@endsection
