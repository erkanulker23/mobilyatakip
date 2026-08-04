@extends('layouts.print')
@section('title', \App\Support\SaleDocumentNaming::orderPageTitle($sale))
@section('content')
@include('partials.invoice-document-print', array_merge(compact('sale'), \App\Support\SaleDocument::invoiceParams($sale)))
@endsection
