@extends('layouts.app')
@section('title', 'SSH - Servis Kayıtları')
@section('content')
@php
    $statusFilter = request('status');
    $showCompletedOnly = $statusFilter === 'tamamlandi';
    $showActiveOnly = in_array($statusFilter, ['acildi', 'devam_ediyor', 'iptal'], true);

    $activeTickets = $tickets->filter(fn ($ticket) => ($ticket->status ?? 'acildi') !== 'tamamlandi');
    $completedTickets = $tickets->filter(fn ($ticket) => ($ticket->status ?? '') === 'tamamlandi');

    $colspan = ($showCustomerNames ?? !($hideCommercialData ?? false)) ? 8 : 7;
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Servis Kayıtları (SSH)</h1>
        <p class="page-desc">Servis ve garanti takibi</p>
    </div>
    @if(empty($hideCommercialData))
    <a href="{{ route('service-tickets.create') }}" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Servis Kaydı
    </a>
    @endif
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (no{{ ($showCustomerNames ?? false) ? ', müşteri' : '' }}, sorun)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        @if(empty($hideCommercialData))
        <div class="min-w-[160px]">
            <label class="form-label">Müşteri</label>
            <select name="customerId" class="form-select">
                <option value="">Tümü</option>
                @foreach($customers ?? [] as $c)
                <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
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

    @if($showCompletedOnly)
        @if($completedTickets->isEmpty())
        <div class="px-6 py-16 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="mt-4 text-neutral-500">Tamamlanan kayıt bulunamadı.</p>
        </div>
        @else
        <div class="px-4 py-4 sm:px-6 border-b border-neutral-100 dark:border-neutral-800 bg-emerald-50/50 dark:bg-emerald-950/20">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Tamamlanan SSH Kayıtları</h2>
                <span class="text-xs font-medium text-emerald-700/80 dark:text-emerald-400/80">({{ $completedTickets->count() }})</span>
            </div>
        </div>
        <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 bg-neutral-50/50 dark:bg-neutral-950/30">
            @foreach($completedTickets as $ticket)
                @include('service-tickets.partials.index-completed-card', ['ticket' => $ticket])
            @endforeach
        </div>
        @endif
    @else
        @if($activeTickets->isNotEmpty() || ($showActiveOnly && $tickets->isNotEmpty()))
        @if(!$showActiveOnly && $completedTickets->isNotEmpty())
        <div class="px-4 py-3 sm:px-6 bg-white dark:bg-neutral-900 border-b border-neutral-100 dark:border-neutral-800">
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Açık Kayıtlar</h2>
        </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="table-th">No</th>
                        <th class="table-th">Satış</th>
                        @if($showCustomerNames)<th class="table-th">Müşteri</th>@endif
                        <th class="table-th">Problemler</th>
                        <th class="table-th">Sevkiyatçı</th>
                        <th class="table-th">Durum</th>
                        <th class="table-th">Tarih</th>
                        <th class="table-th text-center w-40">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                    @forelse($activeTickets as $ticket)
                        @include('service-tickets.partials.index-row', ['ticket' => $ticket])
                    @empty
                        <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @elseif($tickets->isEmpty())
        <div class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</div>
        @endif

        @if(!$showActiveOnly && $completedTickets->isNotEmpty())
        <div class="border-t border-neutral-200 dark:border-neutral-800">
            <div class="px-4 py-3 sm:px-6 bg-emerald-50/60 dark:bg-emerald-950/20 border-b border-emerald-100 dark:border-emerald-900/30">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Tamamlanan Kayıtlar</h2>
                    <span class="text-xs font-medium text-emerald-700/80 dark:text-emerald-400/80">({{ $completedTickets->count() }})</span>
                </div>
            </div>
            <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 bg-neutral-50/50 dark:bg-neutral-950/30">
                @foreach($completedTickets as $ticket)
                    @include('service-tickets.partials.index-completed-card', ['ticket' => $ticket])
                @endforeach
            </div>
        </div>
        @endif
    @endif

    <div class="px-6 py-3 border-t border-neutral-200 dark:border-neutral-800">{{ $tickets->links() }}</div>
</div>
@endsection
