@extends('layouts.app')
@section('title', 'Düzenle: ' . $warehouse->name)
@section('content')
<div class="header"><h1>Depo Düzenle</h1></div>
<form method="POST" action="{{ route('warehouses.update', $warehouse) }}">
    @csrf @method('PUT')
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name', $warehouse->name) }}"></div>
    <div class="form-group"><label>Kod *</label><input name="code" required value="{{ old('code', $warehouse->code) }}"></div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
