@extends('layouts.app')
@section('title', 'Giderler')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Giderler</h1>
        <p class="text-slate-600 mt-1">Gider kayıtları ve filtreleme</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">Yeni Gider</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
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
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <p class="text-slate-700"><strong>Toplam (filtrelenen):</strong> <span class="text-lg font-semibold">{{ number_format($total, 0, ',', '.') }} ₺</span></p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Tarih</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Açıklama</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Kategori</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Kasa</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Tutar</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">İşlem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($expenses as $e)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 text-slate-700">{{ $e->expenseDate?->format('d.m.Y') }}</td>
                <td class="px-6 py-4">
                    <a href="{{ route('expenses.show', $e) }}" class="text-primary-600 hover:underline font-medium">{{ Str::limit($e->description, 50) }}</a>
                </td>
                <td class="px-6 py-4 text-slate-600">{{ $e->category ?? '—' }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $e->kasa?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-right font-medium text-slate-900">{{ number_format($e->amount, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 text-right">
                    @include('partials.action-buttons', [
                        'show' => route('expenses.show', $e),
                        'edit' => route('expenses.edit', $e),
                        'destroy' => route('expenses.destroy', $e),
                    ])
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-3 border-t border-slate-200">{{ $expenses->links() }}</div>
</div>
@endsection
