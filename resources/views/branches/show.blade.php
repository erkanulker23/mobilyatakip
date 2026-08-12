@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::branch($branch))
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('branches.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Şubeler</a>
                <span>/</span>
                <span class="text-neutral-700 dark:text-neutral-300">{{ $branch->name }}</span>
            </div>
            <h1 class="page-title flex items-center gap-2">
                {{ $branch->name }}
                @if(! $branch->isActive)
                <span class="text-sm font-normal px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">Pasif</span>
                @endif
            </h1>
            <p class="page-desc">Şube detayı, satış ve SSH özeti</p>
        </div>
        <a href="{{ route('branches.edit', $branch) }}" class="btn-edit">Düzenle</a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Şube Bilgileri</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-neutral-500">Kod</dt><dd class="font-medium font-mono">{{ $branch->code ?: '—' }}</dd></div>
                <div><dt class="text-neutral-500">Telefon</dt><dd class="font-medium">{{ $branch->phone ?: '—' }}</dd></div>
                <div><dt class="text-neutral-500">Adres</dt><dd class="font-medium">{{ $branch->full_address ?: '—' }}</dd></div>
            </dl>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('sales.index', ['branchId' => $branch->id]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50">
                <p class="text-xs text-neutral-500 uppercase tracking-wide">Satış</p>
                <p class="text-2xl font-semibold mt-1 tabular-nums">{{ $branch->sales_count }}</p>
            </a>
            <a href="{{ route('service-tickets.index', ['branchId' => $branch->id]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50">
                <p class="text-xs text-neutral-500 uppercase tracking-wide">SSH</p>
                <p class="text-2xl font-semibold mt-1 tabular-nums">{{ $branch->service_tickets_count }}</p>
            </a>
        </div>
    </div>
    <div class="lg:col-span-2 space-y-6">
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Son satışlar</h2>
                <a href="{{ route('sales.index', ['branchId' => $branch->id]) }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Tümü</a>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse($recentSales as $sale)
                <a href="{{ route('sales.show', $sale) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-neutral-50 dark:hover:bg-neutral-900/40">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $sale->saleNumber }}</p>
                        <p class="text-xs text-neutral-500 truncate">{{ $sale->customer?->name ?? '—' }}</p>
                    </div>
                    <span class="text-xs text-neutral-400 whitespace-nowrap">{{ $sale->saleDate?->format('d.m.Y') }}</span>
                </a>
                @empty
                <p class="px-6 py-8 text-sm text-neutral-500 text-center">Bu şubeye henüz satış bağlanmamış.</p>
                @endforelse
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Son SSH kayıtları</h2>
                <a href="{{ route('service-tickets.index', ['branchId' => $branch->id]) }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Tümü</a>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse($recentTickets as $ticket)
                <a href="{{ route('service-tickets.show', $ticket) }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-neutral-50 dark:hover:bg-neutral-900/40">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $ticket->ticketNumber }}</p>
                        <p class="text-xs text-neutral-500 truncate">{{ $ticket->customer?->name ?? '—' }}</p>
                    </div>
                    <span class="text-xs text-neutral-400 whitespace-nowrap">{{ $ticket->openedAt?->format('d.m.Y') }}</span>
                </a>
                @empty
                <p class="px-6 py-8 text-sm text-neutral-500 text-center">Bu şubeye henüz SSH bağlanmamış.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
