@extends('layouts.app')
@section('title', 'Alışlar')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Alışlar</h1>
        <p class="text-slate-600 mt-1">Alış faturaları ve tedarikçi borç takibi</p>
    </div>
    <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Alış
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (fatura no, tedarikçi)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Tedarikçi</label>
            <select name="supplierId" class="form-select">
                <option value="">Tümü</option>
                @foreach($suppliers ?? [] as $sup)
                <option value="{{ $sup->id }}" {{ request('supplierId') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
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
            <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">No</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tedarikçi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Toplam</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($purchases as $p)
                <tr class="hover:bg-slate-50 {{ ($p->isCancelled ?? false) ? 'opacity-60 bg-slate-50' : '' }}">
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $p->purchaseNumber }} @if($p->isCancelled ?? false)<span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700">İptal</span>@endif</td>
                    <td class="px-6 py-4 text-slate-600">{{ $p->supplier?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $p->purchaseDate?->format('d.m.Y') ?? '-' }}</td>
                    <td class="px-6 py-4 text-right font-medium">{{ number_format($p->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="px-6 py-4">
                        @include('partials.action-buttons', [
                            'show' => route('purchases.show', $p),
                            'edit' => route('purchases.edit', $p),
                            'print' => route('purchases.print', $p),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $purchases->links() }}</div>
</div>
@endsection
