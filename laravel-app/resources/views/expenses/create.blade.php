@extends('layouts.app')
@section('title', 'Yeni Gider')
@section('content')
<div class="header"><h1>Yeni Gider</h1><a href="{{ route('expenses.index') }}" class="btn btn-secondary">Liste</a></div>
<form method="POST" action="{{ route('expenses.store') }}">
    @csrf
    <div class="form-group"><label>Tutar *</label><input type="number" step="0.01" name="amount" required value="{{ old('amount') }}"></div>
    <div class="form-group"><label>Tarih *</label><input type="date" name="expenseDate" required value="{{ old('expenseDate', date('Y-m-d')) }}"></div>
    <div class="form-group"><label>Açıklama *</label><textarea name="description" rows="3" required>{{ old('description') }}</textarea></div>
    <div class="form-group"><label>Kategori</label><input type="text" name="category" value="{{ old('category') }}" placeholder="Örn: Kira, Elektrik, Personel"></div>
    <div class="form-group"><label>Kasa</label><select name="kasaId"><option value="">-</option>@foreach($kasalar as $k)<option value="{{ $k->id }}">{{ $k->name }}</option>@endforeach</select></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
@endsection
