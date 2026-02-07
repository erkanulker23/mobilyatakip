@extends('layouts.app')
@section('title', 'Yeni Depo')
@section('content')
<div class="header"><h1>Yeni Depo</h1></div>
<form method="POST" action="{{ route('warehouses.store') }}">
    @csrf
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name') }}"></div>
    <div class="form-group"><label>Kod *</label><input name="code" required value="{{ old('code') }}"></div>
    <div class="form-group"><label>Adres</label><textarea name="address">{{ old('address') }}</textarea></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
