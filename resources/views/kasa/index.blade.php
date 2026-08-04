@extends('layouts.app')
@section('title', 'Kasa')
@section('content')
@if(session('success'))
<div class="mb-4 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 text-sm">{{ session('error') }}</div>
@endif

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="page-title">Kasa</h1>
        <p class="page-desc">Kasa ve banka hesapları – nakit akış takibi. Kasa detayında hareketleri ödeme tipine ve cariye (müşteri/tedarikçi) göre filtreleyebilirsiniz.</p>
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
                @foreach(\App\Support\KasaType::labels() as $value => $label)
                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
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
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th">Ad</th>
                    <th class="table-th">Tip</th>
                    <th class="table-th">IBAN / Hesap</th>
                    <th class="table-th text-right">Açılış Bakiyesi</th>
                    <th class="table-th text-right">Güncel Bakiye</th>
                    <th class="table-th text-right w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kasalar as $k)
                <tr class="border-b border-neutral-50 dark:border-neutral-800/80 hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 transition-colors">
                    <td class="table-td">
                        <span class="font-medium text-neutral-900">{{ $k->name }}</span>
                        @if($k->bankName)<span class="block text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $k->bankName }}</span>@endif
                    </td>
                    <td class="table-td">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-lg {{ \App\Support\KasaType::badgeClasses($k->type) }}">{{ \App\Support\KasaType::label($k->type) }}</span>
                    </td>
                    <td class="table-td text-sm tabular-nums tracking-wide text-neutral-600 dark:text-neutral-300">{{ $k->iban ?? $k->accountNumber ?? '-' }}</td>
                    <td class="table-td text-right font-medium {{ ($k->openingBalance ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format((float)($k->openingBalance ?? 0), 0, ',', '.') }} ₺</td>
                    @php $guncelBakiye = (float)($k->openingBalance ?? 0) + (float)($k->hareketler_sum_amount ?? 0); @endphp
                    <td class="table-td text-right font-semibold {{ $guncelBakiye >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($guncelBakiye, 0, ',', '.') }} ₺</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('kasa.show', $k),
                            'edit' => route('kasa.edit', $k),
                            'destroy' => route('kasa.destroy', $k),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-16 text-center text-neutral-500 text-sm">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 dark:border-neutral-800 text-sm text-neutral-500 dark:text-neutral-400">{{ $kasalar->links() }}</div>
</div>
@endsection
