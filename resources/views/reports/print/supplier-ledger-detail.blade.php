@extends('layouts.print')
@section('title', 'Tedarikçi Cari Ekstre - Yazdır')
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'TEDARİKÇİ CARİ EKSTRE',
            'documentNumber' => $supplier->name,
            'documentDate' => now(),
            'documentSubtitle' => ($from || $to) ? (($from?->format('d.m.Y') ?? '…') . ' – ' . ($to?->format('d.m.Y') ?? '…')) : 'Tüm hareketler',
        ])
        @include('reports.partials.ledger-detail-table', ['print' => true])
    </div>
</div>
@endsection
