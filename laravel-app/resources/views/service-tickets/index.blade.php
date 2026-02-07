@extends('layouts.app')
@section('title', 'SSH - Servis Kayıtları')
@section('content')
<div class="header"><h1>Servis Kayıtları</h1><a href="{{ route('service-tickets.create') }}" class="btn btn-primary">Yeni Kayıt</a></div>
<div class="card">
    <table>
        <thead><tr><th>No</th><th>Satış</th><th>Müşteri</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($tickets as $t)
            <tr><td><a href="{{ route('service-tickets.show', $t) }}">{{ $t->ticketNumber }}</a></td><td>{{ $t->sale?->saleNumber }}</td><td>{{ $t->customer?->name }}</td><td>{{ $t->status }}</td><td>{{ $t->createdAt?->format('d.m.Y') }}</td><td><a href="{{ route('service-tickets.show', $t) }}" class="btn btn-secondary">Detay</a></td></tr>
            @empty<tr><td colspan="6">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $tickets->links() }}
</div>
@endsection
