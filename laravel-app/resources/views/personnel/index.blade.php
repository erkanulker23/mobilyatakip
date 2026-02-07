@extends('layouts.app')
@section('title', 'Personel')
@section('content')
<div class="header"><h1>Personel</h1><a href="{{ route('personnel.create') }}" class="btn btn-primary">Yeni Personel</a></div>
<div class="card">
    <table>
        <thead><tr><th>Ad</th><th>E-posta</th><th>Telefon</th><th>Unvan</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($personnel as $p)
            <tr><td><a href="{{ route('personnel.show', $p) }}">{{ $p->name }}</a></td><td>{{ $p->email }}</td><td>{{ $p->phone }}</td><td>{{ $p->title }}</td><td><a href="{{ route('personnel.edit', $p) }}" class="btn btn-secondary">Düzenle</a></td></tr>
            @empty<tr><td colspan="5">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $personnel->links() }}
</div>
@endsection
