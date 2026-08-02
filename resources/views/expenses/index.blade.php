@extends('layouts.app')
@section('title', 'Giderler')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="page-title">Giderler</h1>
        <p class="page-desc">Gider kayıtları ve filtreleme</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn-primary">Yeni Gider</a>
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
    <div class="p-4 border-b border-neutral-100 bg-neutral-50/50">
    <p class="text-neutral-700"><strong>Toplam (filtrelenen):</strong> <span class="text-lg font-semibold">{{ number_format($total, 0, ',', '.') }} ₺</span></p>
</div>
    <div class="overflow-x-auto">
        <table class="w-full">
        <thead class="bg-slate-50 border-b border-neutral-200">
            <tr>
                <th class="table-th">Tarih</th>
                <th class="table-th">Açıklama</th>
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
            <tr><td colspan="6" class="px-6 py-8 text-center text-neutral-500">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-3 border-t border-neutral-200">{{ $expenses->links() }}</div>
</div>
@endsection
