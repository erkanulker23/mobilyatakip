@extends('layouts.app')
@section('title', 'Tedarikçiler')
@section('content')
<div class="header"><h1>Tedarikçiler</h1><a href="{{ route('suppliers.create') }}" class="btn btn-primary">Yeni Tedarikçi</a></div>
<div class="card">
    <table>
        <thead><tr><th>Ad</th><th>E-posta</th><th>Telefon</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($suppliers as $s)
            <tr><td><a href="{{ route('suppliers.show', $s) }}">{{ $s->name }}</a></td><td>{{ $s->email }}</td><td>{{ $s->phone }}</td><td><a href="{{ route('suppliers.edit', $s) }}" class="btn btn-secondary">Düzenle</a></td></tr>
            @empty<tr><td colspan="4">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $suppliers->links() }}
</div>
@endsection
