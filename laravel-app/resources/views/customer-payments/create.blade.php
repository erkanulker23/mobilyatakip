@extends('layouts.app')
@section('title', 'Ödeme Al')
@section('content')
<div class="header"><h1>Ödeme Al (Tahsilat)</h1></div>
@if(session('error'))
    <div class="card" style="background:#fee2e2;color:#991b1b;">{{ session('error') }}</div>
@endif
<form method="POST" action="{{ route('customer-payments.store') }}">
    @csrf
    <div class="form-group">
        <label>Müşteri *</label>
        <select name="customerId" required>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Fatura (opsiyonel)</label>
        <select name="saleId">
            <option value="">— Genel tahsilat —</option>
            @foreach($openSales as $s)
                @php $kalan = (float)$s->grandTotal - (float)$s->paidAmount; @endphp
                <option value="{{ $s->id }}" {{ old('saleId') == $s->id ? 'selected' : '' }}
                    data-customer="{{ $s->customerId }}" data-kalan="{{ $kalan }}">
                    {{ $s->saleNumber }} — {{ $s->customer?->name }} (Kalan: {{ number_format($kalan, 2, ',', '.') }} ₺)
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group"><label>Tutar *</label><input type="number" step="0.01" name="amount" required value="{{ old('amount') }}" id="amount"></div>
    <div class="form-group"><label>Tarih *</label><input type="date" name="paymentDate" required value="{{ old('paymentDate', date('Y-m-d')) }}"></div>
    <div class="form-group"><label>Ödeme Tipi</label><select name="paymentType"><option value="nakit">Nakit</option><option value="havale">Havale</option><option value="kredi_karti">Kredi Kartı</option><option value="diger">Diğer</option></select></div>
    <div class="form-group"><label>Kasa</label><select name="kasaId"><option value="">-</option>@foreach($kasalar as $k)<option value="{{ $k->id }}">{{ $k->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Referans</label><input name="reference" value="{{ old('reference') }}"></div>
    <div class="form-group"><label>Not</label><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
@endsection
