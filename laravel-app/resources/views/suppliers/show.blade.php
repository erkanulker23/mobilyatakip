@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<div class="header">
    <h1>{{ $supplier->name }}</h1>
    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary">Düzenle</a>
    <a href="{{ route('supplier-payments.create') }}" class="btn btn-secondary">Ödeme Yap</a>
</div>
<div class="card">
    <p><strong>E-posta:</strong> {{ $supplier->email }}</p>
    <p><strong>Telefon:</strong> {{ $supplier->phone }}</p>
    <p><strong>Adres:</strong> {{ $supplier->address }}</p>
</div>
@php
    $toplamAlis = $supplier->purchases->sum('grandTotal');
    $toplamOdeme = $supplier->payments->sum('amount');
    $cariBakiye = $toplamAlis - $toplamOdeme;
@endphp
<div class="card">
    <h3>Cari Hesap Özeti</h3>
    <p><strong>Toplam alış (borç):</strong> {{ number_format($toplamAlis, 2) }} ₺</p>
    <p><strong>Toplam ödeme (alacak):</strong> {{ number_format($toplamOdeme, 2) }} ₺</p>
    <p><strong>Cari bakiye:</strong> <span style="color:{{ $cariBakiye > 0 ? '#b91c1c' : ($cariBakiye < 0 ? '#166534' : '#64748b') }}">{{ number_format($cariBakiye, 2) }} ₺</span> ({{ $cariBakiye > 0 ? 'Tedarikçi alacaklı' : ($cariBakiye < 0 ? 'Tedarikçi borçlu' : 'Kapalı') }})</p>
</div>
<div class="card">
    <h3>Son Ödemeler</h3>
    <table>
        <thead><tr><th>Tarih</th><th>Tutar</th><th>Tip</th><th>Alış</th></tr></thead>
        <tbody>
            @forelse($supplier->payments->sortByDesc('paymentDate')->take(10) as $p)
            <tr><td>{{ $p->paymentDate?->format('d.m.Y') }}</td><td>{{ number_format($p->amount, 2) }} ₺</td><td>{{ $p->paymentType ?? '—' }}</td><td>{{ $p->purchase?->purchaseNumber ?? '—' }}</td></tr>
            @empty
            <tr><td colspan="4">Ödeme yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="card">
    <h3>Alışlar</h3>
    <table>
        <thead><tr><th>No</th><th>Tarih</th><th>Tutar</th><th>Ödenen</th><th>Kalan</th></tr></thead>
        <tbody>
            @foreach($supplier->purchases->take(10) as $p)
            <tr>
                <td><a href="{{ route('purchases.show', $p) }}">{{ $p->purchaseNumber }}</a></td>
                <td>{{ $p->purchaseDate?->format('d.m.Y') }}</td>
                <td>{{ number_format($p->grandTotal, 2) }} ₺</td>
                <td>{{ number_format($p->paidAmount ?? 0, 2) }} ₺</td>
                <td>{{ number_format(($p->grandTotal ?? 0) - ($p->paidAmount ?? 0), 2) }} ₺</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
