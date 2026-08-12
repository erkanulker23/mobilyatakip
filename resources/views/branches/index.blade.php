@extends('layouts.app')
@section('title', 'Şubeler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Şubeler</h1>
        <p class="page-desc">Satış, teklif ve SSH kayıtlarının bağlı olduğu şubeler</p>
    </div>
    <a href="{{ route('branches.create') }}" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Şube
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
@endif

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[200px] flex-1">
                <label class="form-label">Ara (ad, kod, telefon, adres)</label>
                <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
            </div>
            <div class="min-w-[140px]">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <option value="">Tümü</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filtrele</button>
                <a href="{{ route('branches.index') }}" class="btn-secondary">Temizle</a>
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">Şube</th>
                    <th class="table-th">Kod</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th">Adres</th>
                    <th class="table-th text-right">Satış</th>
                    <th class="table-th text-right">Teklif</th>
                    <th class="table-th text-right">SSH</th>
                    <th class="table-th text-right">Personel</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th text-center w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                @forelse($branches as $branch)
                <tr class="hover:bg-slate-50 dark:hover:bg-neutral-900/40 {{ $branch->isActive ? '' : 'opacity-60' }}">
                    <td class="table-td font-medium text-neutral-900 dark:text-neutral-100">
                        <a href="{{ route('branches.show', $branch) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ $branch->name }}</a>
                    </td>
                    <td class="table-td font-mono text-sm text-slate-600 dark:text-neutral-400">{{ $branch->code ?: '—' }}</td>
                    <td class="table-td text-sm">{{ $branch->phone ?: '—' }}</td>
                    <td class="table-td text-slate-600 dark:text-neutral-400 text-sm">{{ Str::limit($branch->full_address, 50) ?: '—' }}</td>
                    <td class="table-td text-right tabular-nums">{{ $branch->sales_count }}</td>
                    <td class="table-td text-right tabular-nums">{{ $branch->quotes_count }}</td>
                    <td class="table-td text-right tabular-nums">{{ $branch->service_tickets_count }}</td>
                    <td class="table-td text-right tabular-nums">{{ $branch->personnel_count }}</td>
                    <td class="table-td">
                        @if($branch->isActive)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">Aktif</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">Pasif</span>
                        @endif
                    </td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('branches.show', $branch),
                            'edit' => route('branches.edit', $branch),
                            'destroy' => route('branches.destroy', $branch),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-6 py-12 text-center text-neutral-500">Henüz şube yok. Yeni şube ekleyerek satış, teklif ve SSH kayıtlarını ayırabilirsiniz.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-neutral-200 dark:border-neutral-800">{{ $branches->links() }}</div>
</div>
@endsection
