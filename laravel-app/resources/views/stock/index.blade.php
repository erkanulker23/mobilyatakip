@extends('layouts.app')
@section('title', 'Stok')
@section('content')
<div class="header">
    <h1>Stok</h1>
    <a href="{{ route('stock.low') }}" class="btn btn-secondary">Kritik Stok</a>
</div>
<form method="GET" class="form-group" style="margin-bottom:1rem;">
    <label>Depo Seç</label>
    <select name="warehouse_id" onchange="this.form.submit()">
        <option value="">-- Depo seçin --</option>
        @foreach($warehouses as $w)
        <option value="{{ $w->id }}" {{ $warehouseId == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
        @endforeach
    </select>
</form>
@if($warehouseId)
<div class="card">
    <table>
        <thead><tr><th>Ürün</th><th>SKU</th><th>Miktar</th><th>Rezerve</th><th>Müsait</th></tr></thead>
        <tbody>
            @forelse($stocks as $s)
            <tr>
                <td>{{ $s->product?->name }}</td>
                <td>{{ $s->product?->sku }}</td>
                <td>{{ $s->quantity }}</td>
                <td>{{ $s->reservedQuantity ?? 0 }}</td>
                <td>{{ ($s->quantity ?? 0) - ($s->reservedQuantity ?? 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="5">Bu depoda stok kaydı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif
@endsection
