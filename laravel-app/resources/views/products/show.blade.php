@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="header"><h1>{{ $product->name }}</h1><a href="{{ route('products.edit', $product) }}" class="btn btn-primary">Düzenle</a></div>
<div class="card">
    <p><strong>SKU:</strong> {{ $product->sku }}</p>
    <p><strong>Fiyat:</strong> {{ number_format($product->unitPrice, 2) }} ₺</p>
    <p><strong>Tedarikçi:</strong> {{ $product->supplier?->name }}</p>
</div>
@endsection
