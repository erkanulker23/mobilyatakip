@extends('layouts.app')
@section('title', $personnel->name)
@section('content')
<div class="header"><h1>{{ $personnel->name }}</h1><a href="{{ route('personnel.edit', $personnel) }}" class="btn btn-primary">Düzenle</a></div>
<div class="card"><p><strong>E-posta:</strong> {{ $personnel->email }}</p><p><strong>Telefon:</strong> {{ $personnel->phone }}</p></div>
@endsection
