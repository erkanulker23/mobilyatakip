@extends('layouts.app')
@section('title', 'Yeni Personel')
@section('content')
<div class="header"><h1>Yeni Personel</h1></div>
<form method="POST" action="{{ route('personnel.store') }}">
    @csrf
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name') }}"></div>
    <div class="form-group"><label>E-posta</label><input type="email" name="email" value="{{ old('email') }}"></div>
    <div class="form-group"><label>Telefon</label><input name="phone" value="{{ old('phone') }}"></div>
    <div class="form-group"><label>Unvan</label><input name="title" value="{{ old('title') }}"></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('personnel.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
