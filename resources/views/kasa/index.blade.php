@extends('layouts.app')
@section('title', 'Kasa')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="page-title">Kasa</h1>
        <p class="page-desc">Kasa ve banka hesapları – nakit akış takibi</p>
    </div>
    <a href="{{ route('kasa.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Yeni Kasa
    </a>
</div>

<div class="card p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (ad, banka, IBAN)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Tip</label>
            <select name="type" class="form-select">
                <option value="">Tümü</option>
                <option value="kasa" {{ request('type') === 'kasa' ? 'selected' : '' }}>Kasa</option>
                <option value="banka" {{ request('type') === 'banka' ? 'selected' : '' }}>Banka</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('kasa.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="table-th">Ad</th>
                    <th class="table-th">Tip</th>
                    <th class="table-th">IBAN / Hesap</th>
                    <th class="table-th text-right">Açılış Bakiyesi</th>
                    <th class="table-th text-right w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kasalar as $k)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                    <td class="table-td">
                        <span class="font-medium text-slate-900">{{ $k->name }}</span>
                        @if($k->bankName)<span class="block text-xs text-slate-500 mt-0.5">{{ $k->bankName }}</span>@endif
                    </td>
                    <td class="table-td">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-lg {{ $k->type === 'banka' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $k->type === 'banka' ? 'Banka' : 'Kasa' }}</span>
                    </td>
                    <td class="table-td font-mono text-sm">{{ $k->iban ?? $k->accountNumber ?? '-' }}</td>
                    <td class="table-td text-right font-medium {{ ($k->openingBalance ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($k->openingBalance ?? 0, 2, ',', '.') }} ₺</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('kasa.show', $k),
                            'edit' => route('kasa.edit', $k),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-16 text-center text-slate-500 text-sm">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 text-sm text-slate-500">{{ $kasalar->links() }}</div>
</div>
@endsection
