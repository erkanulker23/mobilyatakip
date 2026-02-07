@extends('layouts.app')
@section('title', 'Gelir – Gider Raporu')
@section('content')
<div class="header">
    <h1>Gelir – Gider Raporu</h1>
    <a href="{{ route('reports.index') }}" class="btn btn-secondary">Raporlar</a>
</div>
<form method="get" class="card" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-bottom:1rem;">
    <div class="form-group" style="margin:0;"><label>Başlangıç</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
    <div class="form-group" style="margin:0;"><label>Bitiş</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
    <button type="submit" class="btn btn-primary">Hesapla</button>
</form>
<div class="card">
    <table>
        <thead><tr><th>Kalem</th><th>Tutar</th></tr></thead>
        <tbody>
            <tr><td>Satış hasılatı (dönem)</td><td>{{ number_format($gelir, 2) }} ₺</td></tr>
            <tr><td>Tahsilat (dönem)</td><td>{{ number_format($tahsilat, 2) }} ₺</td></tr>
            <tr><td>Gider</td><td>- {{ number_format($gider, 2) }} ₺</td></tr>
            <tr><td>Tedarikçi ödemesi</td><td>- {{ number_format($tedarikciOdeme, 2) }} ₺</td></tr>
            <tr style="font-weight:600;"><td>Net nakit etkisi (tahsilat − gider − tedarikçi ödemesi)</td><td>{{ number_format($tahsilat - $gider - $tedarikciOdeme, 2) }} ₺</td></tr>
        </tbody>
    </table>
</div>
@endsection
