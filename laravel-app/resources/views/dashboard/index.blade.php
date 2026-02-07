@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="header">
    <h1>Dashboard</h1>
</div>
<div class="card" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">
    <div><strong>{{ $stats['salesCount'] }}</strong> Satış</div>
    <div><strong>{{ $stats['quotesCount'] }}</strong> Teklif</div>
    <div><strong>{{ $stats['purchasesCount'] }}</strong> Alış</div>
    <div><strong>{{ $stats['lowStockCount'] }}</strong> Kritik Stok</div>
</div>
<div class="card">
    <h3>Mali Özet (Bu Ay)</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
        <div><strong>Gelir (satış):</strong><br>{{ number_format($gelirAy, 2) }} ₺</div>
        <div><strong>Gider:</strong><br>{{ number_format($giderAy, 2) }} ₺</div>
        <div><strong>Toplam kasa bakiye:</strong><br>{{ number_format($toplamKasaBakiye, 2) }} ₺</div>
    </div>
</div>
@if($vadesiGecenAlacaklar->isNotEmpty())
<div class="card" style="border-left:4px solid #b91c1c;">
    <h3>Vadesi Geçen Alacaklar (Toplam: {{ number_format($vadesiGecenToplam ?? 0, 2) }} ₺)</h3>
    <table>
        <thead><tr><th>No</th><th>Müşteri</th><th>Vade</th><th>Kalan</th><th></th></tr></thead>
        <tbody>
            @foreach($vadesiGecenAlacaklar as $s)
            @php $kalan = (float)$s->grandTotal - (float)$s->paidAmount; @endphp
            <tr>
                <td>{{ $s->saleNumber }}</td>
                <td>{{ $s->customer?->name }}</td>
                <td>{{ $s->dueDate?->format('d.m.Y') }}</td>
                <td>{{ number_format($kalan, 2) }} ₺</td>
                <td><a href="{{ route('sales.show', $s) }}" class="btn btn-secondary">Detay</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
<div class="card">
    <h3>Son Satışlar</h3>
    @if($recentSales->isEmpty())
        <p>Henüz satış yok.</p>
    @else
        <table>
            <thead>
                <tr><th>No</th><th>Müşteri</th><th>Tarih</th><th>Tutar</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($recentSales as $s)
                <tr>
                    <td>{{ $s->saleNumber }}</td>
                    <td>{{ $s->customer?->name }}</td>
                    <td>{{ $s->saleDate?->format('d.m.Y') }}</td>
                    <td>{{ number_format($s->grandTotal, 2, ',', '.') }} ₺</td>
                    <td><a href="{{ route('sales.show', $s) }}" class="btn btn-secondary">Detay</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
