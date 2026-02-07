@extends('layouts.app')
@section('title', 'Müşteri Cari Ekstre - ' . $customer->name)
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
            <a href="{{ route('reports.customer-ledger') }}" class="hover:text-slate-700">Müşteri Cari</a>
            <span>/</span>
            <span class="text-slate-700">{{ $customer->name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Müşteri Cari Ekstre</h1>
        <p class="text-slate-600 mt-1">{{ $customer->name }} — Hareket detayı</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('customers.show', $customer) }}" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 font-medium">Müşteri Detay</a>
        <a href="{{ route('reports.index') }}" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 font-medium">Raporlar</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[140px]">
            <label class="form-label">Başlangıç</label>
            <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="form-input">
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Bitiş</label>
            <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="form-input">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">Filtrele</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Tarih</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Açıklama</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Borç</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Alacak</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Bakiye</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @if(($from || $to) && $openingBalance != 0)
            <tr class="bg-amber-50">
                <td class="px-6 py-4 text-slate-600">—</td>
                <td class="px-6 py-4 font-medium text-slate-700">Açılış bakiyesi</td>
                <td class="px-6 py-4 text-right">—</td>
                <td class="px-6 py-4 text-right">—</td>
                <td class="px-6 py-4 text-right font-medium {{ $openingBalance > 0 ? 'text-red-600' : ($openingBalance < 0 ? 'text-green-600' : 'text-slate-600') }}">{{ number_format($openingBalance, 0, ',', '.') }} ₺</td>
            </tr>
            @endif
            @forelse($filteredRows as $r)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 text-slate-600">{{ $r->date->format('d.m.Y') }}</td>
                <td class="px-6 py-4">
                    @if($r->refRoute && $r->refId)
                    <a href="{{ route($r->refRoute, $r->refId) }}" class="text-primary-600 hover:underline font-medium">{{ $r->aciklama }}</a>
                    @else
                    {{ $r->aciklama }}
                    @endif
                </td>
                <td class="px-6 py-4 text-right {{ $r->borc > 0 ? 'font-medium text-slate-800' : 'text-slate-400' }}">{{ $r->borc > 0 ? number_format($r->borc, 0, ',', '.') . ' ₺' : '—' }}</td>
                <td class="px-6 py-4 text-right {{ $r->alacak > 0 ? 'font-medium text-green-600' : 'text-slate-400' }}">{{ $r->alacak > 0 ? number_format($r->alacak, 0, ',', '.') . ' ₺' : '—' }}</td>
                <td class="px-6 py-4 text-right font-medium {{ $r->bakiye > 0 ? 'text-red-600' : ($r->bakiye < 0 ? 'text-green-600' : 'text-slate-600') }}">{{ number_format($r->bakiye, 0, ',', '.') }} ₺</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Bu tarih aralığında hareket yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
