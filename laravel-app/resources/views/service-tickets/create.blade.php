@extends('layouts.app')
@section('title', 'Yeni Servis Kaydı')
@section('content')
<div class="header"><h1>Yeni Servis Kaydı</h1></div>
<form method="POST" action="{{ route('service-tickets.store') }}">
    @csrf
    <div class="form-group"><label>Satış *</label><select name="saleId" required>@foreach($sales as $s)<option value="{{ $s->id }}">{{ $s->saleNumber }} - {{ $s->customer?->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Müşteri *</label><select name="customerId" required>@foreach(\App\Models\Customer::orderBy('name')->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Sorun Tipi *</label><input name="issueType" required value="{{ old('issueType') }}"></div>
    <div class="form-group"><label>Açıklama</label><textarea name="description">{{ old('description') }}</textarea></div>
    <div class="form-group"><label><input type="checkbox" name="underWarranty" value="1"> Garanti kapsamında</label></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('service-tickets.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
