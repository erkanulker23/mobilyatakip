@extends('layouts.app')
@section('title', 'Tedarikçi Cari Özeti')
@section('content')
<div class="header">
    <h1>Tedarikçi Cari Hesap Özeti</h1>
    <a href="{{ route('reports.index') }}" class="btn btn-secondary">Raporlar</a>
</div>
<form method="get" class="card" style="margin-bottom:1rem;">
    <label>Filtre:</label>
    <select name="tip" onchange="this.form.submit()">
        <option value="">Tümü</option>
        <option value="borclu" {{ request('tip') === 'borclu' ? 'selected' : '' }}>Biz borçluyuz (tedarikçi alacaklı)</option>
        <option value="alacakli" {{ request('tip') === 'alacakli' ? 'selected' : '' }}>Biz alacaklıyız</option>
    </select>
</form>
<div class="card">
    <table>
        <thead><tr><th>Tedarikçi</th><th>Borç (alış)</th><th>Alacak (ödeme)</th><th>Bakiye</th><th></th></tr></thead>
        <tbody>
            @forelse($suppliers as $r)
            <tr>
                <td>{{ $r->supplier->name }}</td>
                <td>{{ number_format($r->borc, 2) }} ₺</td>
                <td>{{ number_format($r->alacak, 2) }} ₺</td>
                <td style="color:{{ $r->bakiye > 0 ? '#b91c1c' : ($r->bakiye < 0 ? '#166534' : '#64748b') }}">{{ number_format($r->bakiye, 2) }} ₺</td>
                <td><a href="{{ route('suppliers.show', $r->supplier) }}" class="btn btn-secondary">Detay</a></td>
            </tr>
            @empty
            <tr><td colspan="5">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
