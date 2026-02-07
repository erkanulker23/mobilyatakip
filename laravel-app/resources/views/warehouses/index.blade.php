@extends('layouts.app')
@section('title', 'Depolar')
@section('content')
<div class="header"><h1>Depolar</h1><a href="{{ route('warehouses.create') }}" class="btn btn-primary">Yeni Depo</a></div>
<div class="card">
    <table>
        <thead><tr><th>Ad</th><th>Kod</th><th>Adres</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($warehouses as $w)
            <tr><td><a href="{{ route('warehouses.show', $w) }}">{{ $w->name }}</a></td><td>{{ $w->code }}</td><td>{{ $w->address }}</td><td><a href="{{ route('warehouses.edit', $w) }}" class="btn btn-secondary">Düzenle</a></td></tr>
            @empty<tr><td colspan="4">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $warehouses->links() }}
</div>
@endsection
