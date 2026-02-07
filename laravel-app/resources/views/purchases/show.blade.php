@extends('layouts.app')
@section('title', 'Alış ' . $purchase->purchaseNumber)
@section('content')
<div class="header"><h1>{{ $purchase->purchaseNumber }}</h1></div>
<div class="card">
    <p><strong>Tedarikçi:</strong> {{ $purchase->supplier?->name }}</p>
    <p><strong>Tarih:</strong> {{ $purchase->purchaseDate?->format('d.m.Y') }}</p>
    <p><strong>Toplam:</strong> {{ number_format($purchase->grandTotal, 2) }} ₺</p>
</div>
<div class="card">
    <h3>Kalemler</h3>
    <table>
        <thead><tr><th>Ürün</th><th>Fiyat</th><th>Adet</th><th>Toplam</th></tr></thead>
        <tbody>
            @foreach($purchase->items ?? [] as $i)
            <tr><td>{{ $i->product?->name }}</td><td>{{ number_format($i->unitPrice, 2) }}</td><td>{{ $i->quantity }}</td><td>{{ number_format($i->lineTotal, 2) }} ₺</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
