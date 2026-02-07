@extends('layouts.app')
@section('title', 'Düzenle: ' . $personnel->name)
@section('content')
<div class="header"><h1>Personel Düzenle</h1></div>
<form method="POST" action="{{ route('personnel.update', $personnel) }}">
    @csrf @method('PUT')
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name', $personnel->name) }}"></div>
    <div class="form-group"><label>E-posta</label><input type="email" name="email" value="{{ old('email', $personnel->email) }}"></div>
    <div class="form-group"><label>Telefon</label><input name="phone" value="{{ old('phone', $personnel->phone) }}"></div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="{{ route('personnel.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
