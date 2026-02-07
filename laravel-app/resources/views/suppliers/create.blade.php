@extends('layouts.app')
@section('title', 'Yeni Tedarikçi')
@section('content')
<div class="header"><h1>Yeni Tedarikçi</h1></div>
<form method="POST" action="{{ route('suppliers.store') }}">
    @csrf
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name') }}"></div>
    <div class="form-group"><label>E-posta</label><input type="email" name="email" value="{{ old('email') }}"></div>
    <div class="form-group"><label>Telefon</label><input name="phone" value="{{ old('phone') }}"></div>
    <div class="form-group"><label>Adres</label><textarea name="address">{{ old('address') }}</textarea></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
