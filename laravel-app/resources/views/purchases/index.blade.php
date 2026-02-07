@extends('layouts.app')
@section('title', 'Alışlar')
@section('content')
<div class="header"><h1>Alışlar</h1><a href="{{ route('purchases.create') }}" class="btn btn-primary">Yeni Alış</a></div>
<div class="card">
    <table>
        <thead><tr><th>No</th><th>Tedarikçi</th><th>Tarih</th><th>Tutar</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($purchases as $p)
            <tr><td><a href="{{ route('purchases.show', $p) }}">{{ $p->purchaseNumber }}</a></td><td>{{ $p->supplier?->name }}</td><td>{{ $p->purchaseDate?->format('d.m.Y') }}</td><td>{{ number_format($p->grandTotal, 2) }} ₺</td><td><a href="{{ route('purchases.show', $p) }}" class="btn btn-secondary">Detay</a></td></tr>
            @empty<tr><td colspan="5">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $purchases->links() }}
</div>
@endsection
