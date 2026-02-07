@extends('layouts.app')
@section('title', $warehouse->name)
@section('content')
<div class="header"><h1>{{ $warehouse->name }}</h1><a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-primary">Düzenle</a></div>
<div class="card">
    <p><strong>Kod:</strong> {{ $warehouse->code }}</p>
    <p><strong>Adres:</strong> {{ $warehouse->address }}</p>
</div>
@endsection
