@extends('layouts.app')
@section('title', 'Teklifler')
@section('content')
<div class="header"><h1>Teklifler</h1><a href="{{ route('quotes.create') }}" class="btn btn-primary">Yeni Teklif</a></div>
<div class="card">
    <table>
        <thead><tr><th>No</th><th>Müşteri</th><th>Durum</th><th>Tutar</th><th>Tarih</th><th>İşlem</th></tr></thead>
        <tbody>
            @forelse($quotes as $q)
            <tr>
                <td><a href="{{ route('quotes.show', $q) }}">{{ $q->quoteNumber }}</a></td>
                <td>{{ $q->customer?->name }}</td>
                <td>{{ $q->status }}</td>
                <td>{{ number_format($q->grandTotal, 2) }} ₺</td>
                <td>{{ $q->createdAt?->format('d.m.Y') }}</td>
                <td>
                    <a href="{{ route('quotes.show', $q) }}" class="btn btn-secondary">Detay</a>
                    @if(!$q->convertedSaleId && $q->status == 'taslak')
                    <form method="POST" action="{{ route('quotes.convert', $q) }}" style="display:inline;">@csrf
                        <select name="warehouseId" required style="width:120px;display:inline-block;">@foreach(\App\Models\Warehouse::orderBy('name')->get() as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
                        <button type="submit" class="btn btn-primary">Satışa Dönüştür</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty<tr><td colspan="6">Kayıt yok.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $quotes->links() }}
</div>
@endsection
