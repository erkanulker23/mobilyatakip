@extends('layouts.print')
@section('title', 'Müşteri Cari Ekstre - Yazdır')
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'MÜŞTERİ CARİ EKSTRE',
            'documentNumber' => $customer->name,
            'documentDate' => now(),
            'documentSubtitle' => ($from || $to) ? (($from?->format('d.m.Y') ?? '…') . ' – ' . ($to?->format('d.m.Y') ?? '…')) : 'Tüm hareketler',
        ])
        @include('reports.partials.ledger-detail-table', ['print' => true])
        @include('partials.print-document-footer', ['documentRef' => 'Cari Ekstre · ' . $customer->name, 'footerNote' => null])
        </div>
    </div>
</div>
@endsection
