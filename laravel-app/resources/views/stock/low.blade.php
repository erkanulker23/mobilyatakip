@extends('layouts.app')
@section('title', 'Kritik Stok')
@section('content')
<div class="header"><h1>Kritik Stok</h1><a href="{{ route('stock.index') }}" class="btn btn-secondary">Stok Listesi</a></div>
<div class="card">
    <table>
        <thead><tr><th>Ürün</th><th>Depo</th><th>Mevcut</th><th>Minimum</th></tr></thead>
        <tbody>
            @forelse($lowStocks as $s)
            <tr>
                <td>{{ $s->product?->name }}</td>
                <td>{{ $s->warehouse?->name }}</td>
                <td>{{ ($s->quantity ?? 0) - ($s->reservedQuantity ?? 0) }}</td>
                <td>{{ $s->product?->minStockLevel ?? 0 }}</td>
            </tr>
            @empty
            <tr><td colspan="4">Kritik stok yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
