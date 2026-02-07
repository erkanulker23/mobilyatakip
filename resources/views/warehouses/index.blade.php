@extends('layouts.app')
@section('title', 'Depolar')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Depolar</h1>
        <p class="text-slate-600 mt-1">Depo listesi ve stok lokasyonları</p>
    </div>
    <a href="{{ route('warehouses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Depo
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (ad, kod, adres)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('warehouses.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Kod</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Adres</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($warehouses as $w)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $w->name }}</td>
                    <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $w->code }}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm">{{ Str::limit($w->address, 50) ?: '-' }}</td>
                    <td class="px-6 py-4">
                        @include('partials.action-buttons', [
                            'show' => route('warehouses.show', $w),
                            'edit' => route('warehouses.edit', $w),
                            'destroy' => route('warehouses.destroy', $w),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $warehouses->links() }}</div>
</div>
@endsection
