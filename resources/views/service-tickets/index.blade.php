@extends('layouts.app')
@section('title', 'SSH - Servis Kayıtları')
@section('content')
@php
    use App\Support\ServiceTicketStatus;

    $statusFilter = request('status');
    $showCompletedOnly = $statusFilter === 'tamamlandi';
    $showActiveOnly = $statusFilter && $statusFilter !== 'tamamlandi';
    $showCustomerNames = $showCustomerNames ?? !($hideCommercialData ?? false);

    $activeTickets = $tickets->filter(fn ($ticket) => ($ticket->status ?? 'acildi') !== 'tamamlandi');
    $completedTickets = $tickets->filter(fn ($ticket) => ($ticket->status ?? '') === 'tamamlandi');

    $colspan = $showCustomerNames ? 11 : 10;
    $hasFilters = request()->filled('search')
        || request()->filled('status')
        || request()->filled('customerId')
        || request()->filled('branchId')
        || request()->filled('from')
        || request()->filled('to');
    $selectedBranchId = request('branchId');
    $selectedBranch = ($branches ?? collect())->first(fn ($b) => (string) $b->id === (string) $selectedBranchId);
    $branchLabel = $selectedBranch ? ' · '.$selectedBranch->name : ($selectedBranchId === 'none' ? ' · Şube belirtilmemiş' : '');

    $filterChip = fn (array $params) => route('service-tickets.index', array_filter(
        array_merge(request()->only(['search', 'customerId', 'from', 'to', 'status', 'branchId']), $params),
        fn ($v) => $v !== null && $v !== ''
    ));
@endphp

<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Servis Kayıtları (SSH)</h1>
        <p class="page-desc">Servis ve garanti takibi — açık kayıtları hızlı yönetin{{ $branchLabel }}</p>
    </div>
    @if(empty($hideCommercialData))
    <a href="{{ route('service-tickets.create') }}" class="btn-primary shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Servis Kaydı
    </a>
    @endif
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
@endif

{{-- Özet kartları --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
    <a href="{{ $filterChip(['status' => null]) }}" class="card p-4 hover:border-neutral-300 dark:hover:border-neutral-600 transition-colors {{ !$statusFilter ? 'ring-2 ring-neutral-900/10 dark:ring-white/10' : '' }}">
        <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Açık</p>
        <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-neutral-100 tabular-nums">{{ $stats['open'] ?? 0 }}</p>
    </a>
    <a href="{{ $filterChip(['status' => 'parca_bekleniyor']) }}" class="card p-4 hover:border-amber-300 dark:hover:border-amber-700 transition-colors {{ $statusFilter === 'parca_bekleniyor' ? 'ring-2 ring-amber-500/30' : '' }}">
        <p class="text-xs font-medium text-amber-700/80 dark:text-amber-400/80 uppercase tracking-wider">Parça bekleniyor</p>
        <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-300 tabular-nums">{{ $stats['parca_bekleniyor'] ?? 0 }}</p>
    </a>
    <a href="{{ $filterChip(['status' => 'sevkiyatci_bekleniyor']) }}" class="card p-4 hover:border-orange-300 dark:hover:border-orange-700 transition-colors {{ $statusFilter === 'sevkiyatci_bekleniyor' ? 'ring-2 ring-orange-500/30' : '' }}">
        <p class="text-xs font-medium text-orange-700/80 dark:text-orange-400/80 uppercase tracking-wider">Sevkiyatçı bekleniyor</p>
        <p class="mt-1 text-2xl font-semibold text-orange-700 dark:text-orange-300 tabular-nums">{{ $stats['sevkiyatci_bekleniyor'] ?? 0 }}</p>
    </a>
    <a href="{{ $filterChip(['status' => 'tamamlandi']) }}" class="card p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors {{ $statusFilter === 'tamamlandi' ? 'ring-2 ring-emerald-500/30' : '' }}">
        <p class="text-xs font-medium text-emerald-700/80 dark:text-emerald-400/80 uppercase tracking-wider">Tamamlanan</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300 tabular-nums">{{ $stats['tamamlandi'] ?? 0 }}</p>
    </a>
</div>

{{-- Hızlı şube çipleri --}}
@if(($branches ?? collect())->isNotEmpty())
<div class="flex flex-wrap items-center gap-2 mb-3">
    <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mr-1">Şube:</span>
    <a href="{{ $filterChip(['branchId' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ !$selectedBranchId ? 'bg-teal-700 text-white dark:bg-teal-500 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Tümü</a>
    @foreach($branches as $branch)
    <a href="{{ $filterChip(['branchId' => $branch->id]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ (string) $selectedBranchId === (string) $branch->id ? 'bg-teal-700 text-white dark:bg-teal-500 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">{{ $branch->name }}</a>
    @endforeach
    <a href="{{ $filterChip(['branchId' => 'none']) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $selectedBranchId === 'none' ? 'bg-teal-700 text-white dark:bg-teal-500 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Şube belirtilmemiş</a>
</div>
@endif

{{-- Hızlı durum çipleri --}}
<div class="flex flex-wrap items-center gap-2 mb-5">
    <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mr-1">Durum:</span>
    <a href="{{ route('service-tickets.index', request()->only(['search', 'customerId', 'from', 'to', 'branchId'])) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ !$statusFilter ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Tümü</a>
    @foreach(ServiceTicketStatus::STATUSES as $value => $label)
    <a href="{{ $filterChip(['status' => $value]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $statusFilter === $value ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}" title="{{ $label }}">
        {{ Str::limit($label, 28) }}
    </a>
    @endforeach
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[180px] flex-1">
                <label class="form-label">Ara (no{{ $showCustomerNames ? ', müşteri' : '' }}, sorun)</label>
                <input type="text" name="search" placeholder="SSH no, müşteri veya problem..." value="{{ request('search') }}" class="form-input">
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
            <div class="min-w-[150px]">
                <label class="form-label">Şube</label>
                <select name="branchId" class="form-select">
                    <option value="">Tüm şubeler</option>
                    <option value="none" {{ $selectedBranchId === 'none' ? 'selected' : '' }}>Şube belirtilmemiş</option>
                    @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <option value="">Tümü</option>
                    @foreach(ServiceTicketStatus::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
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
                @if($hasFilters)
                <a href="{{ route('service-tickets.index') }}" class="btn-secondary">Temizle</a>
                @endif
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
            <p class="mt-4 text-neutral-500 dark:text-neutral-400">Tamamlanan kayıt bulunamadı.</p>
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
        <div class="px-4 py-3 sm:px-6 bg-white dark:bg-neutral-900 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Açık Kayıtlar</h2>
            <span class="text-xs text-neutral-500">{{ $activeTickets->count() }} kayıt</span>
        </div>
        @endif

        {{-- Mobil kartlar --}}
        <div class="md:hidden divide-y divide-neutral-100 dark:divide-neutral-800">
            @forelse($activeTickets as $ticket)
                @include('service-tickets.partials.index-mobile-card', ['ticket' => $ticket])
            @empty
                <div class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</div>
            @endforelse
        </div>

        {{-- Masaüstü tablo --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-neutral-800">
                        <th class="table-th">No</th>
                        <th class="table-th">Şube</th>
                        <th class="table-th">Satış</th>
                        @if($showCustomerNames)<th class="table-th">Müşteri</th>@endif
                        <th class="table-th">Problemler</th>
                        <th class="table-th">Sevkiyatçı</th>
                        <th class="table-th">Açan</th>
                        <th class="table-th">Kapatan</th>
                        <th class="table-th">Durum</th>
                        <th class="table-th">Tarih</th>
                        <th class="table-th text-center w-40">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse($activeTickets as $ticket)
                        @include('service-tickets.partials.index-row', ['ticket' => $ticket])
                    @empty
                        <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @elseif($tickets->isEmpty())
        <div class="px-6 py-16 text-center">
            <p class="text-neutral-500 dark:text-neutral-400">Kayıt bulunamadı.</p>
            @if(empty($hideCommercialData))
            <a href="{{ route('service-tickets.create') }}" class="btn-primary mt-4 inline-flex">Yeni servis kaydı</a>
            @endif
        </div>
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

    @if($tickets->hasPages())
    <div class="px-6 py-3 border-t border-neutral-200 dark:border-neutral-800">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
