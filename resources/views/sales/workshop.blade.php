@extends('layouts.print')
@section('title', \App\Support\SaleDocumentNaming::workshopPageTitle($sale, $variant))
@section('printBodyClass', 'p-2 md:p-4')
@section('content')
@include('partials.shipment-document', \App\Support\SaleDocument::slipParams($sale, $variant))
@endsection
