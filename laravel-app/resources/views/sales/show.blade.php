@extends('layouts.app')
@section('title', 'Satış ' . $sale->saleNumber)
@section('content')
<div class="header"><h1>{{ $sale->saleNumber }}</h1></div>
<div class="card">
    <p><strong>Müşteri:</strong> {{ $sale->customer?->name }}</p>
    <p><strong>Tarih:</strong> {{ $sale->saleDate?->format('d.m.Y') }}</p>
    <p><strong>Vade:</strong> {{ $sale->dueDate?->format('d.m.Y') }}</p>
    <p><strong>Toplam:</strong> {{ number_format($sale->grandTotal, 2) }} ₺</p>
    <p><strong>Ödenen:</strong> {{ number_format($sale->paidAmount ?? 0, 2) }} ₺</p>
</div>
<div class="card">
    <h3>Kalemler</h3>
    <table>
        <thead><tr><th>Ürün</th><th>Fiyat</th><th>Adet</th><th>KDV</th><th>Toplam</th></tr></thead>
        <tbody>
            @foreach($sale->items ?? [] as $i)
            <tr><td>{{ $i->product?->name }}</td><td>{{ number_format($i->unitPrice, 2) }}</td><td>{{ $i->quantity }}</td><td>%{{ $i->kdvRate }}</td><td>{{ number_format($i->lineTotal, 2) }} ₺</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
