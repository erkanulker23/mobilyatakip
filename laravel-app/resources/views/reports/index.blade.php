@extends('layouts.app')
@section('title', 'Raporlar')
@section('content')
<div class="header"><h1>Raporlar</h1></div>
<div class="card">
    <h3>Mali Raporlar</h3>
    <ul style="list-style:none;padding:0;">
        <li style="margin-bottom:0.5rem;"><a href="{{ route('reports.income-expense') }}">Gelir – Gider Raporu</a></li>
        <li style="margin-bottom:0.5rem;"><a href="{{ route('reports.customer-ledger') }}">Müşteri Cari Hesap Özeti</a></li>
        <li><a href="{{ route('reports.supplier-ledger') }}">Tedarikçi Cari Hesap Özeti</a></li>
    </ul>
</div>
@endsection
