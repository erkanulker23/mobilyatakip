@extends('layouts.print')
@section('title', \App\Support\SaleDocumentNaming::shipmentPageTitle($sale))
@section('printBodyClass', 'p-2 md:p-4')
@section('content')
@include('partials.shipment-document', \App\Support\SaleDocument::shipmentParams($sale))
@endsection
