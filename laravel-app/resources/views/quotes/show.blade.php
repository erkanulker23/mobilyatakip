@extends('layouts.app')
@section('title', 'Teklif ' . $quote->quoteNumber)
@section('content')
<div class="header">
    <h1>{{ $quote->quoteNumber }}</h1>
    @if(!$quote->convertedSaleId && $quote->status == 'taslak')
    <form method="POST" action="{{ route('quotes.convert', $quote) }}" style="display:inline;" onsubmit="return confirm('Bu teklifi satışa dönüştürmek istediğinize emin misiniz?');">@csrf
        <button type="submit" class="btn btn-primary">Satışa Dönüştür</button>
    </form>
    @endif
</div>
<div class="card">
    <p><strong>Müşteri:</strong> {{ $quote->customer?->name }}</p>
    <p><strong>Durum:</strong> {{ $quote->status }}</p>
    <p><strong>Tutar:</strong> {{ number_format($quote->grandTotal, 2) }} ₺</p>
</div>
<div class="card">
    <h3>Kalemler</h3>
    <table>
        <thead><tr><th>Ürün</th><th>Fiyat</th><th>Adet</th><th>KDV</th><th>Toplam</th></tr></thead>
        <tbody>
            @foreach($quote->items as $i)
            <tr><td>{{ $i->product?->name }}</td><td>{{ number_format($i->unitPrice, 2) }}</td><td>{{ $i->quantity }}</td><td>%{{ $i->kdvRate }}</td><td>{{ number_format($i->lineTotal, 2) }} ₺</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
