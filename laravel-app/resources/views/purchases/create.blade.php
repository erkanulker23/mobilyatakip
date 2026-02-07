@extends('layouts.app')
@section('title', 'Yeni Alış')
@section('content')
<div class="header"><h1>Yeni Alış</h1></div>
<form method="POST" action="{{ route('purchases.store') }}">
    @csrf
    <div class="form-group"><label>Tedarikçi *</label><select name="supplierId" required>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Alış Tarihi *</label><input type="date" name="purchaseDate" required value="{{ date('Y-m-d') }}"></div>
    <div class="form-group"><label>Vade</label><input type="date" name="dueDate"></div>
    <div class="card"><h3>Kalemler</h3>
        <div class="item-row form-group" style="display:grid;grid-template-columns:1fr 100px 80px;gap:0.5rem;">
            <select name="items[0][productId]" required>@foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}">{{ $p->name }}</option>@endforeach</select>
            <input type="number" step="0.01" name="items[0][unitPrice]" required>
            <input type="number" name="items[0][quantity]" value="1" required min="1">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">İptal</a>
</form>
@endsection
