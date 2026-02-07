@extends('layouts.app')
@section('title', 'Yeni Kasa')
@section('content')
<div class="header"><h1>Yeni Kasa</h1></div>
<form method="POST" action="{{ route('kasa.store') }}">
    @csrf
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name') }}"></div>
    <div class="form-group"><label>Tip</label><select name="type"><option value="kasa">Kasa</option><option value="banka">Banka</option></select></div>
    <div class="form-group"><label>Açılış Bakiyesi</label><input type="number" step="0.01" name="openingBalance" value="0"></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('kasa.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
