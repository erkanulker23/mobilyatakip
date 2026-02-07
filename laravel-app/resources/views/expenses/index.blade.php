@extends('layouts.app')
@section('title', 'Giderler')
@section('content')
<div class="header">
    <h1>Giderler</h1>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">Yeni Gider</a>
</div>
<form method="get" class="card" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-bottom:1rem;">
    <div class="form-group" style="margin:0;"><label>Başlangıç</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div class="form-group" style="margin:0;"><label>Bitiş</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div class="form-group" style="margin:0;"><label>Kategori</label><input type="text" name="category" value="{{ request('category') }}" placeholder="Kategori"></div>
    <button type="submit" class="btn btn-secondary">Filtrele</button>
</form>
<div class="card">
    <p><strong>Toplam (filtrelenen):</strong> {{ number_format($total, 2) }} ₺</p>
</div>
<div class="card">
    <table>
        <thead>
            <tr><th>Tarih</th><th>Açıklama</th><th>Kategori</th><th>Kasa</th><th>Tutar</th></tr>
        </thead>
        <tbody>
            @forelse($expenses as $e)
            <tr>
                <td>{{ $e->expenseDate?->format('d.m.Y') }}</td>
                <td>{{ $e->description }}</td>
                <td>{{ $e->category ?? '—' }}</td>
                <td>{{ $e->kasa?->name ?? '—' }}</td>
                <td>{{ number_format($e->amount, 2) }} ₺</td>
            </tr>
            @empty
            <tr><td colspan="5">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $expenses->links() }}
</div>
@endsection
