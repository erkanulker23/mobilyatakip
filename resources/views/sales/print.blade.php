@extends('layouts.print')
@section('title', \App\Support\SaleDocumentNaming::orderPageTitle($sale))
@section('content')
@include('partials.invoice-document-print', array_merge(compact('sale'), \App\Support\SaleDocument::invoiceParams($sale), [
    'printVariant' => 'quote',
    'termsTitle' => 'TEKLİF VE SİPARİŞ KOŞULLARI',
    'terms' => \App\Support\QuotePrintTerms::items(),
]))

@include('partials.drawing-files-print', ['entries' => \App\Support\DrawingFiles::entriesForSale($sale)])
@endsection
