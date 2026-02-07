@extends('layouts.app')
@section('title', 'Müşteri Cari Özeti')
@section('content')
<div class="header">
    <h1>Müşteri Cari Hesap Özeti</h1>
    <a href="{{ route('reports.index') }}" class="btn btn-secondary">Raporlar</a>
</div>
<form method="get" class="card" style="margin-bottom:1rem;">
    <label>Filtre:</label>
    <select name="tip" onchange="this.form.submit()">
        <option value="">Tümü</option>
        <option value="borclu" {{ request('tip') === 'borclu' ? 'selected' : '' }}>Sadece borçlular</option>
        <option value="alacakli" {{ request('tip') === 'alacakli' ? 'selected' : '' }}>Sadece alacaklılar</option>
    </select>
</form>
<div class="card">
    <table>
        <thead><tr><th>Müşteri</th><th>Borç (satış)</th><th>Alacak (tahsilat)</th><th>Bakiye</th><th></th></tr></thead>
        <tbody>
            @forelse($customers as $r)
            <tr>
                <td>{{ $r->customer->name }}</td>
                <td>{{ number_format($r->borc, 2) }} ₺</td>
                <td>{{ number_format($r->alacak, 2) }} ₺</td>
                <td style="color:{{ $r->bakiye > 0 ? '#b91c1c' : ($r->bakiye < 0 ? '#166534' : '#64748b') }}">{{ number_format($r->bakiye, 2) }} ₺</td>
                <td><a href="{{ route('customers.show', $r->customer) }}" class="btn btn-secondary">Detay</a></td>
            </tr>
            @empty
            <tr><td colspan="5">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
