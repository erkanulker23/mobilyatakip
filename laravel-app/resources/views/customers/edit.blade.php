@extends('layouts.app')
@section('title', 'Düzenle: ' . $customer->name)
@section('content')
<div class="header"><h1>Müşteri Düzenle</h1></div>
<form method="POST" action="{{ route('customers.update', $customer) }}">
    @csrf @method('PUT')
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name', $customer->name) }}"></div>
    <div class="form-group"><label>E-posta</label><input type="email" name="email" value="{{ old('email', $customer->email) }}"></div>
    <div class="form-group"><label>Telefon</label><input name="phone" value="{{ old('phone', $customer->phone) }}"></div>
    <div class="form-group"><label>Adres</label><textarea name="address">{{ old('address', $customer->address) }}</textarea></div>
    <div class="form-group"><label>Vergi No</label><input name="taxNumber" value="{{ old('taxNumber', $customer->taxNumber) }}"></div>
    <div class="form-group"><label>Vergi Dairesi</label><input name="taxOffice" value="{{ old('taxOffice', $customer->taxOffice) }}"></div>
    <div class="form-group"><label><input type="checkbox" name="isActive" value="1" {{ $customer->isActive ? 'checked' : '' }}> Aktif</label></div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
