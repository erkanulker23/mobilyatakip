@extends('layouts.app')
@section('title', 'Kasa')
@section('content')
<div class="header"><h1>Kasa</h1><a href="{{ route('kasa.create') }}" class="btn btn-primary">Yeni Kasa</a></div>
<div class="card">
    <table>
        <thead><tr><th>Ad</th><th>Tip</th><th>Bakiye</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($kasalar as $k)
            <tr><td><a href="{{ route('kasa.show', $k) }}">{{ $k->name }}</a></td><td>{{ $k->type }}</td><td>{{ number_format($k->balance, 2) }} ₺</td><td><a href="{{ route('kasa.show', $k) }}" class="btn btn-secondary">Detay</a></td></tr>
            @empty<tr><td colspan="4">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $kasalar->links() }}
</div>
@endsection
