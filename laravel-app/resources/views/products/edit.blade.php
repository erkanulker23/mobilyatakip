@extends('layouts.app')
@section('title', 'Düzenle: ' . $product->name)
@section('content')
<div class="header"><h1>Ürün Düzenle</h1></div>
<form method="POST" action="{{ route('products.update', $product) }}">
    @csrf @method('PUT')
    <div class="form-group"><label>Ad *</label><input name="name" required value="{{ old('name', $product->name) }}"></div>
    <div class="form-group"><label>SKU</label><input name="sku" value="{{ old('sku', $product->sku) }}"></div>
    <div class="form-group"><label>Birim Fiyat *</label><input type="number" step="0.01" name="unitPrice" required value="{{ old('unitPrice', $product->unitPrice) }}"></div>
    <div class="form-group"><label>Tedarikçi</label><select name="supplierId"><option value="">-</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" {{ $product->supplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach</select></div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
