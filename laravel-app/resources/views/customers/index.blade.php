@extends('layouts.app')
@section('title', 'Müşteriler')
@section('content')
<div class="header">
    <h1>Müşteriler</h1>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">Yeni Müşteri</a>
</div>
<form method="GET" class="form-group" style="max-width:300px;margin-bottom:1rem;">
    <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}">
    <button type="submit" class="btn btn-secondary" style="margin-top:0.5rem;">Ara</button>
</form>
<div class="card">
    <table>
        <thead>
            <tr><th>Ad</th><th>E-posta</th><th>Telefon</th><th>İşlem</th></tr>
        </thead>
        <tbody>
            @forelse($customers as $c)
            <tr>
                <td><a href="{{ route('customers.show', $c) }}">{{ $c->name }}</a></td>
                <td>{{ $c->email }}</td>
                <td>{{ $c->phone }}</td>
                <td><a href="{{ route('customers.edit', $c) }}" class="btn btn-secondary">Düzenle</a></td>
            </tr>
            @empty
            <tr><td colspan="4">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $customers->links() }}
</div>
@endsection
