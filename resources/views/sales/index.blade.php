@extends('layouts.app')
@section('title', 'Satışlar')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="page-title">Satışlar</h1>
        <p class="page-desc">Satış faturaları ve tahsilat takibi</p>
    </div>
    <a href="{{ route('sales.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Satış Oluştur
    </a>
</div>

<div class="card p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (fatura no, müşteri)</label>
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
            <a href="{{ route('sales.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-right">Toplam</th>
                    <th class="table-th text-right">Ödenen</th>
                    <th class="table-th text-right">Kalan</th>
                    <th class="table-th text-right w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors {{ ($s->isCancelled ?? false) ? 'opacity-60 bg-slate-50' : '' }}">
                    <td class="table-td"><span class="font-medium text-slate-900">{{ $s->saleNumber }}</span> @if($s->isCancelled ?? false)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 font-medium">İptal</span>@endif</td>
                    <td class="table-td">{{ $s->customer?->name ?? '-' }}</td>
                    <td class="table-td">{{ $s->saleDate?->format('d.m.Y') ?? '-' }}</td>
                    <td class="table-td text-right font-medium text-slate-900">{{ number_format($s->grandTotal ?? 0, 2, ',', '.') }} ₺</td>
                    <td class="table-td text-right text-emerald-600">{{ number_format($s->paidAmount ?? 0, 2, ',', '.') }} ₺</td>
                    <td class="table-td text-right {{ (($s->grandTotal ?? 0) - ($s->paidAmount ?? 0)) > 0 ? 'text-red-600 font-medium' : 'text-slate-500' }}">{{ number_format(($s->grandTotal ?? 0) - ($s->paidAmount ?? 0), 2, ',', '.') }} ₺</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('sales.show', $s),
                            'print' => route('sales.print', $s),
                            'destroy' => route('sales.destroy', $s),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="max-w-sm mx-auto">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="mt-4 text-slate-500 text-sm">Filtreye uygun satış bulunamadı.</p>
                            <p class="mt-1 text-sm text-slate-400">Yeni satış eklemek için aşağıdaki butonu kullanın.</p>
                            <a href="{{ route('sales.create') }}" class="btn-primary mt-4">Satış oluştur</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 text-sm text-slate-500">{{ $sales->links() }}</div>
</div>
@endsection
