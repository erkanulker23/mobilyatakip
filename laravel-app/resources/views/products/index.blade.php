@extends('layouts.app')
@section('title', 'Ürünler')
@section('content')
<div class="header"><h1>Ürünler</h1><a href="{{ route('products.create') }}" class="btn btn-primary">Yeni Ürün</a></div>
<div class="card">
    <table>
        <thead><tr><th>Ad</th><th>SKU</th><th>Fiyat</th><th>Tedarikçi</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($products as $p)
            <tr><td><a href="{{ route('products.show', $p) }}">{{ $p->name }}</a></td><td>{{ $p->sku }}</td><td>{{ number_format($p->unitPrice, 2) }} ₺</td><td>{{ $p->supplier?->name }}</td><td><a href="{{ route('products.edit', $p) }}" class="btn btn-secondary">Düzenle</a></td></tr>
            @empty<tr><td colspan="5">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $products->links() }}
</div>
@endsection
