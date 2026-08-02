@extends('layouts.print')
@section('title', 'Sevkiyat Fişi - ' . $sale->saleNumber)
@section('printBodyClass', 'p-2 md:p-4')
@section('content')
@include('partials.shipment-document', \App\Support\SaleDocument::shipmentParams($sale))
@endsection
