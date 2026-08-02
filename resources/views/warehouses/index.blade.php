@extends('layouts.app')
@section('title', 'Depolar')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Depolar</h1>
        <p class="page-desc">Depo listesi ve stok lokasyonları</p>
    </div>
    <a href="{{ route('warehouses.create') }}" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Depo
    </a>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (ad, kod, adres)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('warehouses.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">Ad</th>
                    <th class="table-th">Kod</th>
                    <th class="table-th">Adres</th>
                    <th class="table-th text-center w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($warehouses as $w)
                <tr class="hover:bg-slate-50">
                    <td class="table-td font-medium text-neutral-900">{{ $w->name }}</td>
                    <td class="table-td font-mono text-sm text-slate-600">{{ $w->code }}</td>
                    <td class="table-td text-slate-600 text-sm">{{ Str::limit($w->address, 50) ?: '-' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('warehouses.show', $w),
                            'edit' => route('warehouses.edit', $w),
                            'destroy' => route('warehouses.destroy', $w),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-neutral-200">{{ $warehouses->links() }}</div>
</div>
@endsection
