@extends('layouts.app')
@section('title', 'SSH - Servis Kayıtları')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Servis Kayıtları (SSH)</h1>
        <p class="text-slate-600 mt-1">Servis ve garanti takibi</p>
    </div>
    <a href="{{ route('service-tickets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Servis Kaydı
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (no, müşteri, sorun)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Müşteri</label>
            <select name="customerId" class="form-select">
                <option value="">Tümü</option>
                @foreach($customers ?? [] as $c)
                <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Durum</label>
            <select name="status" class="form-select">
                <option value="">Tümü</option>
                <option value="acildi" {{ request('status') === 'acildi' ? 'selected' : '' }}>Açıldı</option>
                <option value="devam_ediyor" {{ request('status') === 'devam_ediyor' ? 'selected' : '' }}>Devam Ediyor</option>
                <option value="tamamlandi" {{ request('status') === 'tamamlandi' ? 'selected' : '' }}>Tamamlandı</option>
                <option value="iptal" {{ request('status') === 'iptal' ? 'selected' : '' }}>İptal</option>
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Başlangıç</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Bitiş</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('service-tickets.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">No</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Satış</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Müşteri</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Sorun</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($tickets as $t)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $t->ticketNumber }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $t->sale?->saleNumber ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $t->customer?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ Str::limit($t->issueType, 30) }}</td>
                    <td class="px-6 py-4">
                        @php $status = $t->status ?? 'acildi'; @endphp
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $status === 'tamamlandi' ? 'bg-green-100 text-green-800' : ($status === 'devam_ediyor' ? 'bg-amber-100 text-amber-800' : ($status === 'iptal' ? 'bg-slate-100 text-slate-600' : 'bg-blue-100 text-blue-800')) }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-600">{{ $t->createdAt?->format('d.m.Y') ?? '-' }}</td>
                    <td class="px-6 py-4">
                        @include('partials.action-buttons', [
                            'show' => route('service-tickets.show', $t),
                            'edit' => route('service-tickets.edit', $t),
                            'print' => route('service-tickets.print', $t),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $tickets->links() }}</div>
</div>
@endsection
