@extends('layouts.app')
@section('title', $kasa->name)
@section('content')
<div class="header"><h1>{{ $kasa->name }}</h1></div>
<div class="card">
    <p><strong>Tip:</strong> {{ $kasa->type }}</p>
    <p><strong>Açılış bakiyesi:</strong> {{ number_format($kasa->openingBalance ?? 0, 2) }} ₺</p>
    <p><strong>Güncel bakiye:</strong> {{ number_format($kasa->balance, 2) }} ₺</p>
</div>
<div class="card"><h3>Hareketler</h3><table><thead><tr><th>Tarih</th><th>Tip</th><th>Tutar</th><th>Açıklama</th></tr></thead><tbody>@foreach($kasa->hareketler ?? [] as $h)<tr><td>{{ $h->movementDate?->format('d.m.Y') }}</td><td>{{ $h->type }}</td><td>{{ number_format($h->amount, 2) }} ₺</td><td>{{ $h->description }}</td></tr>@endforeach</tbody></table></div>
@endsection
