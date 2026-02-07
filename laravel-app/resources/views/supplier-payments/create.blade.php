@extends('layouts.app')
@section('title', 'Ödeme Yap')
@section('content')
<div class="header"><h1>Ödeme Yap (Tedarikçi)</h1></div>
@if(session('error'))
    <div class="card" style="background:#fee2e2;color:#991b1b;">{{ session('error') }}</div>
@endif
<form method="POST" action="{{ route('supplier-payments.store') }}">
    @csrf
    <div class="form-group">
        <label>Tedarikçi *</label>
        <select name="supplierId" required>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ old('supplierId') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Alış faturası (opsiyonel)</label>
        <select name="purchaseId">
            <option value="">— Genel ödeme —</option>
            @foreach($openPurchases as $p)
                @php $kalan = (float)$p->grandTotal - (float)$p->paidAmount; @endphp
                <option value="{{ $p->id }}" {{ old('purchaseId') == $p->id ? 'selected' : '' }}>
                    {{ $p->purchaseNumber }} — {{ $p->supplier?->name }} (Kalan: {{ number_format($kalan, 2, ',', '.') }} ₺)
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group"><label>Tutar *</label><input type="number" step="0.01" name="amount" required value="{{ old('amount') }}"></div>
    <div class="form-group"><label>Tarih *</label><input type="date" name="paymentDate" required value="{{ old('paymentDate', date('Y-m-d')) }}"></div>
    <div class="form-group"><label>Ödeme Tipi</label><select name="paymentType"><option value="nakit">Nakit</option><option value="havale">Havale</option><option value="kredi_karti">Kredi Kartı</option><option value="diger">Diğer</option></select></div>
    <div class="form-group"><label>Kasa</label><select name="kasaId"><option value="">-</option>@foreach($kasalar as $k)<option value="{{ $k->id }}">{{ $k->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Referans</label><input name="reference" value="{{ old('reference') }}"></div>
    <div class="form-group"><label>Not</label><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
@endsection
