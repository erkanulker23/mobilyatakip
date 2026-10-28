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
        <p class="page-desc">Her hesap ayrıdır. Güncel bakiye = açılış + müşteri tahsilatı ve diğer girişler − gider, tedarikçi, nakliye ve avans. Alış faturası bu bakiyeye yazılmaz.</p>
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
                    <th class="table-th text-right">Açılış</th>
                    <th class="table-th text-right">Giriş</th>
                    <th class="table-th text-right">Çıkış</th>
                    <th class="table-th text-right">Güncel bakiye</th>
                    <th class="table-th text-right w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kasalar as $k)
                <tr class="border-b border-neutral-50 dark:border-neutral-800/80 hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 transition-colors">
                    <td class="table-td">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $k->name }}</span>
                            @if($k->isDefault)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-md bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-800">Varsayılan</span>
                            @endif
                        </div>
                        @if($k->bankName)<span class="block text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $k->bankName }}</span>@endif
                    </td>
                    <td class="table-td">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-lg {{ \App\Support\KasaType::badgeClasses($k->type) }}">{{ \App\Support\KasaType::label($k->type) }}</span>
                    </td>
                    <td class="table-td text-sm tabular-nums tracking-wide text-neutral-600 dark:text-neutral-300">{{ $k->iban ?? $k->accountNumber ?? '-' }}</td>
                    @php
                        $opening = (float) ($k->openingBalance ?? 0);
                        $giris = (float) ($k->ledger_in ?? 0);
                        $cikis = abs((float) ($k->ledger_out ?? 0));
                        $guncelBakiye = $opening + $giris - $cikis;
                    @endphp
                    <td class="table-td text-right font-medium tabular-nums {{ $opening >= 0 ? 'text-neutral-700 dark:text-neutral-200' : 'text-red-600' }}">{{ number_format($opening, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right font-medium tabular-nums text-emerald-600">+{{ number_format($giris, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right font-medium tabular-nums text-rose-600">−{{ number_format($cikis, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right font-semibold tabular-nums {{ $guncelBakiye >= 0 ? 'text-emerald-600' : 'text-red-600' }}" title="Açılış + giriş − çıkış">{{ number_format($guncelBakiye, 0, ',', '.') }} ₺</td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-1">
                            @if(! $k->isDefault && ($k->isActive ?? true))
                            <form method="POST" action="{{ route('kasa.set-default', $k) }}" class="inline">
                                @csrf
                                <button type="submit" aria-label="Varsayılan yap" title="Varsayılan yap" class="p-2 rounded-xl text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                </button>
                            </form>
                            @elseif($k->isDefault)
                            <span class="p-2 text-amber-500" title="Varsayılan kasa" aria-label="Varsayılan kasa">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                            </span>
                            @endif
                            @include('partials.action-buttons', [
                                'show' => route('kasa.show', $k),
                                'edit' => route('kasa.edit', $k),
                                'destroy' => route('kasa.destroy', $k),
                            ])
                        </div>
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
