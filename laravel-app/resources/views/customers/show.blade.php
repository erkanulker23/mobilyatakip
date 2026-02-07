@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="header">
    <h1>{{ $customer->name }}</h1>
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">Düzenle</a>
    <a href="{{ route('customer-payments.create') }}" class="btn btn-secondary">Ödeme Al</a>
</div>
<div class="card">
    <p><strong>E-posta:</strong> {{ $customer->email }}</p>
    <p><strong>Telefon:</strong> {{ $customer->phone }}</p>
    <p><strong>Adres:</strong> {{ $customer->address }}</p>
</div>
@php
    $toplamSatis = $customer->sales->sum('grandTotal');
    $toplamTahsilat = $customer->payments->sum('amount');
    $cariBakiye = $toplamSatis - $toplamTahsilat;
@endphp
<div class="card">
    <h3>Cari Hesap Özeti</h3>
    <p><strong>Toplam satış (borç):</strong> {{ number_format($toplamSatis, 2) }} ₺</p>
    <p><strong>Toplam tahsilat (alacak):</strong> {{ number_format($toplamTahsilat, 2) }} ₺</p>
    <p><strong>Cari bakiye:</strong> <span style="color:{{ $cariBakiye > 0 ? '#b91c1c' : ($cariBakiye < 0 ? '#166534' : '#64748b') }}">{{ number_format($cariBakiye, 2) }} ₺</span> ({{ $cariBakiye > 0 ? 'Müşteri borçlu' : ($cariBakiye < 0 ? 'Müşteri alacaklı' : 'Kapalı') }})</p>
</div>
<div class="card">
    <h3>Son Tahsilatlar</h3>
    <table>
        <thead><tr><th>Tarih</th><th>Tutar</th><th>Tip</th><th>Fatura</th></tr></thead>
        <tbody>
            @forelse($customer->payments->sortByDesc('paymentDate')->take(10) as $p)
            <tr><td>{{ $p->paymentDate?->format('d.m.Y') }}</td><td>{{ number_format($p->amount, 2) }} ₺</td><td>{{ $p->paymentType ?? '—' }}</td><td>{{ $p->sale?->saleNumber ?? '—' }}</td></tr>
            @empty
            <tr><td colspan="4">Tahsilat yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="card">
    <h3>Satışlar</h3>
    <table>
        <thead><tr><th>No</th><th>Tarih</th><th>Tutar</th><th>Ödenen</th><th>Kalan</th></tr></thead>
        <tbody>
            @foreach($customer->sales->take(10) as $s)
            <tr>
                <td><a href="{{ route('sales.show', $s) }}">{{ $s->saleNumber }}</a></td>
                <td>{{ $s->saleDate?->format('d.m.Y') }}</td>
                <td>{{ number_format($s->grandTotal, 2) }} ₺</td>
                <td>{{ number_format($s->paidAmount ?? 0, 2) }} ₺</td>
                <td>{{ number_format(($s->grandTotal ?? 0) - ($s->paidAmount ?? 0), 2) }} ₺</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
