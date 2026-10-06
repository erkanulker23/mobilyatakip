@extends('layouts.app')
@section('title', 'Giderler')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="page-title">Giderler</h1>
        <p class="page-desc">Gider kayıtları, personel avansları ve filtreleme</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('personnel-advances.create') }}" class="btn-secondary">Personel Avans</a>
        <a href="{{ route('expenses.create') }}" class="btn-primary">Yeni Gider</a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (açıklama)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Başlangıç</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Bitiş</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Kategori</label>
            <input type="text" name="category" value="{{ request('category') }}" placeholder="Kategori" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Kasa</label>
            <select name="kasaId" class="form-select">
                <option value="">Tümü</option>
                @foreach($kasalar ?? [] as $k)
                <option value="{{ $k->id }}" {{ request('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('expenses.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
    </div>
    <div class="p-4 border-b border-neutral-100 bg-neutral-50/50 dark:bg-slate-800/40 flex flex-wrap gap-x-6 gap-y-2">
        <p class="text-neutral-700 dark:text-neutral-200"><strong>Gider toplamı:</strong> <span class="text-lg font-semibold">{{ number_format($total, 0, ',', '.') }} ₺</span></p>
        @if(($advanceTotal ?? 0) > 0.005)
        <p class="text-neutral-700 dark:text-neutral-200"><strong>Personel avansı:</strong> <span class="text-lg font-semibold text-amber-600 dark:text-amber-400">{{ number_format($advanceTotal, 0, ',', '.') }} ₺</span> <span class="text-xs text-neutral-500">(kasa çıkışı, gider değil)</span></p>
        @endif
    </div>

    @if(($advances ?? collect())->isNotEmpty())
    <div class="px-4 pt-4 pb-2 border-b border-neutral-100 dark:border-slate-700">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Personel avansları</h2>
        <p class="text-xs text-neutral-500 mt-1">Kasadan çıkan avanslar · gider kaydı değildir</p>
    </div>
    <div class="overflow-x-auto border-b border-neutral-200 dark:border-slate-700">
        <table class="w-full">
            <thead class="bg-amber-50/60 dark:bg-amber-950/20 border-b border-neutral-200 dark:border-slate-700">
                <tr>
                    <th class="table-th">Tarih</th>
                    <th class="table-th">Açıklama</th>
                    <th class="table-th">Kategori</th>
                    <th class="table-th">Kasa</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($advances as $a)
                <tr class="hover:bg-amber-50/40 dark:hover:bg-amber-950/10">
                    <td class="table-td text-neutral-700 dark:text-neutral-300">{{ $a->advanceDate?->format('d.m.Y') }}</td>
                    <td class="table-td">
                        <a href="{{ route('personnel.show', $a->personnelId) }}" class="text-primary-600 hover:underline font-medium">{{ $a->cariLabel() }}</a>
                        @if($a->note)
                        <span class="block text-xs text-neutral-500 mt-0.5">{{ Str::limit($a->note, 60) }}</span>
                        @endif
                    </td>
                    <td class="table-td">
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Personel Avansı · {{ $a->periodLabel() }}</span>
                    </td>
                    <td class="table-td text-slate-600 dark:text-slate-400">{{ $a->kasa?->name ?? '—' }}</td>
                    <td class="table-td text-right font-medium text-amber-700 dark:text-amber-400">{{ number_format($a->amount, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right">
                        <div class="inline-flex items-center gap-2 justify-end">
                            <a href="{{ route('personnel.show', $a->personnelId) }}" class="text-sm text-primary-600 hover:underline">Cari</a>
                            <form method="POST" action="{{ route('personnel-advances.destroy', $a) }}" onsubmit="return confirm('Bu avans silinsin mi?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Sil</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="px-4 pt-4 pb-2">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-neutral-500">Gider kayıtları</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
        <thead class="bg-slate-50 border-b border-neutral-200">
            <tr>
                <th class="table-th">Tarih</th>
                <th class="table-th">Açıklama</th>
                <th class="table-th">Sipariş</th>
                <th class="table-th">Kategori</th>
                <th class="table-th">Kasa</th>
                <th class="table-th text-right">Tutar</th>
                <th class="table-th text-right">İşlem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($expenses as $e)
            <tr class="hover:bg-slate-50">
                <td class="table-td text-neutral-700">{{ $e->expenseDate?->format('d.m.Y') }}</td>
                <td class="table-td">
                    <a href="{{ route('expenses.show', $e) }}" class="text-primary-600 hover:underline font-medium">{{ Str::limit($e->description, 50) }}</a>
                </td>
                <td class="table-td text-slate-600">
                    @if($e->sale)
                    <a href="{{ route('sales.show', $e->sale) }}" class="text-emerald-600 hover:underline font-mono text-sm">{{ $e->sale->saleNumber }}</a>
                    @else
                    <span class="text-neutral-400">—</span>
                    @endif
                </td>
                <td class="table-td text-slate-600">{{ $e->category ?? '—' }}</td>
                <td class="table-td text-slate-600">{{ $e->kasa?->name ?? '—' }}</td>
                <td class="table-td text-right font-medium text-neutral-900">{{ number_format($e->amount, 0, ',', '.') }} ₺</td>
                <td class="table-td text-right">
                    @include('partials.action-buttons', [
                        'show' => route('expenses.show', $e),
                        'edit' => route('expenses.edit', $e),
                        'destroy' => route('expenses.destroy', $e),
                    ])
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-8 text-center text-neutral-500">Gider kaydı yok.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-3 border-t border-neutral-200">{{ $expenses->links() }}</div>
</div>
@endsection
