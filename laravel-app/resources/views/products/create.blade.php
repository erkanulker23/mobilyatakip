@extends('layouts.app')
@section('title', 'Yeni Ürün')
@section('content')
<div class="header"><h1>Yeni Ürün</h1></div>
<form method="POST" action="{{ route('products.store') }}">
    @csrf
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name') }}"></div>
    <div class="form-group"><label>SKU</label><input name="sku" value="{{ old('sku') }}"></div>
    <div class="form-group"><label>Birim Fiyat *</label><input type="number" step="0.01" name="unitPrice" required value="{{ old('unitPrice') }}"></div>
    <div class="form-group"><label>KDV Oranı %</label><input type="number" step="0.01" name="kdvRate" value="{{ old('kdvRate', 18) }}"></div>
    <div class="form-group"><label>Tedarikçi</label><select name="supplierId"><option value="">-</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
