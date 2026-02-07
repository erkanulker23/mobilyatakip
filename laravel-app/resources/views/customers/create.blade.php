@extends('layouts.app')
@section('title', 'Yeni Müşteri')
@section('content')
<div class="header"><h1>Yeni Müşteri</h1></div>
<form method="POST" action="{{ route('customers.store') }}">
    @csrf
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name') }}"></div>
    <div class="form-group"><label>E-posta</label><input type="email" name="email" value="{{ old('email') }}"></div>
    <div class="form-group"><label>Telefon</label><input name="phone" value="{{ old('phone') }}"></div>
    <div class="form-group"><label>Adres</label><textarea name="address">{{ old('address') }}</textarea></div>
    <div class="form-group"><label>Vergi No</label><input name="taxNumber" value="{{ old('taxNumber') }}"></div>
    <div class="form-group"><label>Vergi Dairesi</label><input name="taxOffice" value="{{ old('taxOffice') }}"></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
