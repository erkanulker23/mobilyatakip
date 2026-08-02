@extends('layouts.app')
@section('title', 'Teklifler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Teklifler</h1>
        <p class="page-desc">Teklif listesi ve satışa dönüştürme</p>
    </div>
    <a href="{{ route('quotes.create') }}" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Teklif
    </a>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (no, müşteri)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Müşteri</label>
            <select name="customerId" class="form-select">
                <option value="">Tümü</option>
                @foreach($customers ?? [] as $c)
                <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Durum</label>
            <select name="status" class="form-select">
                <option value="">Tümü</option>
                <option value="taslak" {{ request('status') === 'taslak' ? 'selected' : '' }}>Taslak</option>
                <option value="onaylandi" {{ request('status') === 'onaylandi' ? 'selected' : '' }}>Onaylandı</option>
                <option value="reddedildi" {{ request('status') === 'reddedildi' ? 'selected' : '' }}>Reddedildi</option>
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Başlangıç</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Bitiş</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('quotes.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-center w-48">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($quotes as $q)
                <tr class="hover:bg-slate-50">
                    <td class="table-td font-medium text-neutral-900">{{ $q->quoteNumber }}</td>
                    <td class="table-td text-slate-600">{{ $q->customer?->name ?? '-' }}</td>
                    <td class="table-td">
                        @if($q->convertedSaleId)
                        <a href="{{ route('sales.show', $q->convertedSale) }}" class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 hover:bg-emerald-200">
                            Siparişe dönüştürüldü
                        </a>
                        @else
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $q->status === 'taslak' ? 'bg-amber-100 text-amber-800' : ($q->status === 'onaylandi' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($q->status ?? '-') }}</span>
                        @endif
                    </td>
                    <td class="table-td text-right font-medium">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-slate-600">{{ $q->createdAt?->format('d.m.Y') ?? '-' }}</td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-1">
                            @include('partials.action-buttons', [
                                'show' => route('quotes.show', $q),
                                'edit' => !$q->convertedSaleId ? route('quotes.edit', $q) : null,
                                'print' => route('quotes.print', $q),
                                'destroy' => route('quotes.destroy', $q),
                            ])
                            @if(!$q->convertedSaleId && ($q->status ?? '') == 'taslak')
                            <form method="POST" action="{{ route('quotes.convert', $q) }}" class="inline-flex ml-1" onsubmit="return confirm('Bu teklifi satışa dönüştürmek istediğinize emin misiniz?');">
                                @csrf
                                <button type="submit" title="Satışa Dönüştür" class="p-2 rounded-lg bg-green-100 text-green-700 hover:bg-green-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-neutral-200">{{ $quotes->links() }}</div>
</div>
@endsection
