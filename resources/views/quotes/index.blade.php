@extends('layouts.app')
@section('title', 'Teklifler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Teklifler</h1>
        <p class="text-slate-600 mt-1">Teklif listesi ve satışa dönüştürme</p>
    </div>
    <a href="{{ route('quotes.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Teklif
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
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
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('quotes.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">No</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Müşteri</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Durum</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Tutar</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-48">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($quotes as $q)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $q->quoteNumber }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $q->customer?->name ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $q->status === 'taslak' ? 'bg-amber-100 text-amber-800' : ($q->status === 'onaylandi' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($q->status ?? '-') }}</span>
                    </td>
                    <td class="px-6 py-4 text-right font-medium">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="px-6 py-4 text-slate-600">{{ $q->createdAt?->format('d.m.Y') ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            @include('partials.action-buttons', [
                                'show' => route('quotes.show', $q),
                                'edit' => route('quotes.edit', $q),
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
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $quotes->links() }}</div>
</div>
@endsection
