@extends('layouts.print')
@section('title', ($variant === 'koltuk' ? 'Koltuk Atölye Fişi' : 'Mobilya Atölyesi Fişi') . ' - ' . $sale->saleNumber)
@section('printBodyClass', 'p-2 md:p-4')
@section('content')
@include('partials.shipment-document', \App\Support\SaleDocument::slipParams($sale, $variant))
@endsection
