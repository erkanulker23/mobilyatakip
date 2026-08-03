@extends('layouts.print')
@section('title', 'Satış ' . $sale->saleNumber . ' - Yazdır')
@section('content')
@include('partials.invoice-document-print', array_merge(compact('sale'), \App\Support\SaleDocument::invoiceParams($sale)))
@endsection
