@extends('layouts.app')
@section('title', 'SSH - Servis Kayıtları')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Servis Kayıtları (SSH)</h1>
        <p class="page-desc">Servis ve garanti takibi</p>
    </div>
    <a href="{{ route('service-tickets.create') }}" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Servis Kaydı
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
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
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('service-tickets.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">No</th>
                    <th class="table-th">Satış</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Problemler</th>
                    <th class="table-th">Sevkiyatçı</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-center w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($tickets as $t)
                @php
                    $problems = \App\Support\ServiceTicketStatus::normalizeProblems($t->reportedProblems ?? []);
                    if ($problems === [] && $t->issueType) {
                        $problems = [['description' => $t->issueType, 'status' => 'bekliyor']];
                    }
                    $status = $t->status ?? 'acildi';
                    $statusClass = $status === 'tamamlandi' ? 'badge-green' : ($status === 'devam_ediyor' ? 'badge-amber' : ($status === 'iptal' ? 'badge-dark' : 'badge-blue'));
                @endphp
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="table-td"><a href="{{ route('service-tickets.show', $t) }}" class="font-medium text-neutral-900 hover:underline">{{ $t->ticketNumber }}</a></td>
                    <td class="table-td text-slate-600">{{ $t->sale?->saleNumber ?? '—' }}</td>
                    <td class="table-td text-slate-600">{{ $t->customer?->name ?? '—' }}</td>
                    <td class="table-td text-slate-600">
                        <span class="block">{{ Str::limit($problems[0]['description'] ?? '—', 28) }}</span>
                        <span class="text-xs text-neutral-500">{{ \App\Support\ServiceTicketStatus::problemSummary($problems) }}</span>
                    </td>
                    <td class="table-td text-slate-600">{{ $t->assignedDriverName ?: '—' }}</td>
                    <td class="table-td">
                        <form method="POST" action="{{ route('service-tickets.update-status', $t) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="form-select text-xs py-1.5 px-2 min-w-[8.5rem] max-w-[10rem]" onchange="this.form.submit()">
                                @foreach(\App\Support\ServiceTicketStatus::STATUSES as $value => $label)
                                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="table-td text-slate-600">{{ $t->createdAt?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('service-tickets.show', $t),
                            'edit' => route('service-tickets.edit', $t),
                            'print' => route('service-tickets.print', $t),
                            'destroy' => route('service-tickets.destroy', $t),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-neutral-200">{{ $tickets->links() }}</div>
</div>
@endsection
