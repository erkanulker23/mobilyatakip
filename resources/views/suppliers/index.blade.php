@extends('layouts.app')
@section('title', 'Tedarikçiler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Tedarikçiler</h1>
        <p class="text-slate-600 mt-1">Tedarikçi listesi ve borç takibi</p>
    </div>
    <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Tedarikçi
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (ad, e-posta, telefon, vergi no)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Durum</label>
            <select name="isActive" class="form-select">
                <option value="">Tümü</option>
                <option value="1" {{ request('isActive') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('isActive') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">E-posta</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Telefon</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Vergi No</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($suppliers as $s)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <span class="font-medium text-slate-900">{{ $s->name }}</span>
                        @if(!($s->isActive ?? true))<span class="ml-1 text-xs text-slate-400">(Pasif)</span>@endif
                    </td>
                    <td class="px-6 py-4 text-slate-600">{{ $s->email ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $s->phone ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600 font-mono text-sm">{{ $s->taxNumber ?? '-' }}</td>
                    <td class="px-6 py-4">
                        @include('partials.action-buttons', [
                            'show' => route('suppliers.show', $s),
                            'edit' => route('suppliers.edit', $s),
                            'print' => route('suppliers.print', $s),
                            'destroy' => route('suppliers.destroy', $s),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $suppliers->links() }}</div>
</div>
@endsection
