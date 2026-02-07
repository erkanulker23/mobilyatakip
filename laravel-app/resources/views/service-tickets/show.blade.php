@extends('layouts.app')
@section('title', 'Servis ' . $serviceTicket->ticketNumber)
@section('content')
<div class="header"><h1>{{ $serviceTicket->ticketNumber }}</h1></div>
<div class="card">
    <p><strong>Satış:</strong> {{ $serviceTicket->sale?->saleNumber }}</p>
    <p><strong>Müşteri:</strong> {{ $serviceTicket->customer?->name }}</p>
    <p><strong>Durum:</strong> {{ $serviceTicket->status }}</p>
    <p><strong>Sorun:</strong> {{ $serviceTicket->issueType }}</p>
    <p><strong>Açıklama:</strong> {{ $serviceTicket->description }}</p>
</div>
@endsection
