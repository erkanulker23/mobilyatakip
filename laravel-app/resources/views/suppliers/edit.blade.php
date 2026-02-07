@extends('layouts.app')
@section('title', 'Düzenle: ' . $supplier->name)
@section('content')
<div class="header"><h1>Tedarikçi Düzenle</h1></div>
<form method="POST" action="{{ route('suppliers.update', $supplier) }}">
    @csrf @method('PUT')
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name', $supplier->name) }}"></div>
    <div class="form-group"><label>E-posta</label><input type="email" name="email" value="{{ old('email', $supplier->email) }}"></div>
    <div class="form-group"><label>Telefon</label><input name="phone" value="{{ old('phone', $supplier->phone) }}"></div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
