@extends('layouts.app')
@section('title', 'Satışlar')
@section('content')
<div class="header"><h1>Satışlar</h1></div>
<div class="card">
    <table>
        <thead><tr><th>No</th><th>Müşteri</th><th>Tarih</th><th>Tutar</th><th>Ödenen</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($sales as $s)
            <tr>
                <td><a href="{{ route('sales.show', $s) }}">{{ $s->saleNumber }}</a></td>
                <td>{{ $s->customer?->name }}</td>
                <td>{{ $s->saleDate?->format('d.m.Y') }}</td>
                <td>{{ number_format($s->grandTotal, 2) }} ₺</td>
                <td>{{ number_format($s->paidAmount ?? 0, 2) }} ₺</td>
                <td><a href="{{ route('sales.show', $s) }}" class="btn btn-secondary">Detay</a></td>
            </tr>
            @empty<tr><td colspan="6">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $sales->links() }}
</div>
@endsection
