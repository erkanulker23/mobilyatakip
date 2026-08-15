@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::personnel($personnel))
@section('content')
@php
    $collectedTotal = max(0, (float) ($salesStats->total ?? 0) - (float) ($salesStats->totalReceivable ?? 0));
    $collectionRate = ($salesStats->total ?? 0) > 0
        ? min(100, round(($collectedTotal / $salesStats->total) * 100))
        : 0;
    $perf = $monthlyPerformance ?? null;
    $thisMonth = $perf['thisMonth'] ?? null;
    $lastMonth = $perf['lastMonth'] ?? null;
    $commissionRate = (float) ($personnel->commissionRate ?? 0);
    $overdueDueCount = $upcomingDueSales->filter(function ($sale) {
        return $sale->dueDate && $sale->dueDate->lt(now()->startOfDay());
    })->count();
    $openTaskCount = ($personnelTasks ?? collect())->where('isCompleted', false)->count();
    $isAdmin = auth()->user()?->isAdmin();
@endphp

{{-- Başlık --}}
<div class="mb-5">
    <div class="flex items-start justify-between flex-wrap gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                @if($isAdmin)
                <a href="{{ route('personnel.index') }}" class="hover:text-neutral-900 dark:hover:text-white">Personel</a>
                <span>/</span>
                @endif
                <span class="text-neutral-700 dark:text-neutral-300 truncate">{{ $personnel->name }}</span>
            </div>
            <h1 class="page-title">{{ $viewingOwnProfile ? 'Siparişlerim' : $personnel->name }}</h1>
            <p class="page-desc">
                @if($viewingOwnProfile)
                    Size atanmış siparişler ve performans özeti
                @else
                    {{ $personnel->title ?: 'Personel' }}
                    @if($personnel->branch)
                        · {{ $personnel->branch->displayName() }}
                    @endif
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($personnel->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $personnel->phone) }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                Ara
            </a>
            @endif
            @if($personnel->userId && ($isAdmin || ($viewingOwnProfile ?? false)))
            <a href="{{ route('personnel.activities', $personnel) }}" class="btn-secondary">Log</a>
            @endif
            @if($isAdmin)
            <a href="{{ route('sales.index', ['personnelId' => $personnel->id]) }}" class="btn-secondary">Satışları filtrele</a>
            <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit">Düzenle</a>
            @endif
        </div>
    </div>
</div>

{{-- Profil + bu ay özeti --}}
<div class="card overflow-hidden mb-5">
    <div class="p-5 sm:p-6 flex flex-col lg:flex-row gap-6 lg:items-center">
        <div class="flex items-center gap-4 min-w-0 lg:w-[22rem] shrink-0">
            @if($personnel->photoUrl)
                <img src="{{ storage_url($personnel->photoUrl) }}" alt="{{ $personnel->name }}" class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover border border-neutral-200 dark:border-neutral-700 shrink-0">
            @else
                <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-slate-100 dark:bg-slate-700 border border-neutral-200 dark:border-slate-600 flex items-center justify-center text-2xl font-semibold text-slate-400 shrink-0">
                    {{ mb_strtoupper(mb_substr($personnel->name, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-lg font-semibold text-neutral-900 dark:text-white truncate">{{ $personnel->name }}</p>
                <p class="text-sm text-neutral-500 truncate">{{ $personnel->title ?: \App\Support\PersonnelCategory::label($personnel->category) }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if($personnel->isActive)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-neutral-100 text-neutral-600 dark:bg-slate-700 dark:text-slate-300">Pasif</span>
                    @endif
                    @if($personnel->branch)
                        <a href="{{ route('branches.show', $personnel->branch) }}" class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-sky-50 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 hover:underline">{{ $personnel->branch->displayName() }}</a>
                    @endif
                    @if($commissionRate > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300">Prim %{{ number_format($commissionRate, $commissionRate == (int) $commissionRate ? 0 : 2, ',', '.') }}</span>
                    @endif
                    @if($isAdmin)
                        @if($personnel->hasSystemAccess())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ \App\Support\UserRole::label($personnel->user?->role) }}</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-neutral-100 text-neutral-500 dark:bg-slate-800 dark:text-slate-400">Giriş yok</span>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3 min-w-0">
            <div class="rounded-xl border border-neutral-200 dark:border-slate-700 bg-neutral-50/70 dark:bg-slate-900/40 p-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Bu ay ciro</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">₺{{ number_format($thisMonth['total'] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-neutral-500">{{ $thisMonth['count'] ?? 0 }} sipariş · {{ $thisMonth['label'] ?? '' }}</p>
            </div>
            <div class="rounded-xl border border-violet-200/80 dark:border-violet-800/50 bg-violet-50/60 dark:bg-violet-950/25 p-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">Bu ay prim</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-violet-700 dark:text-violet-300">₺{{ number_format($thisMonth['commission'] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-violet-700/70 dark:text-violet-300/70">
                    @if($commissionRate > 0)
                        Ciro × %{{ number_format($commissionRate, $commissionRate == (int) $commissionRate ? 0 : 2, ',', '.') }}
                    @else
                        Prim oranı girilmedi
                    @endif
                </p>
            </div>
            <div class="rounded-xl border border-amber-200/80 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-950/20 p-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300">Alınacak</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-300">₺{{ number_format($salesStats->totalReceivable ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-amber-800/70 dark:text-amber-300/70">Tüm siparişler</p>
            </div>
            <a href="#termin" class="rounded-xl border border-red-200/80 dark:border-red-800/50 bg-red-50/40 dark:bg-red-950/20 p-3.5 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700 dark:text-red-300">Yakın termin</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-red-700 dark:text-red-300">{{ $upcomingDueSales->count() }}</p>
                <p class="mt-1 text-xs text-red-700/70 dark:text-red-300/70">
                    @if($overdueDueCount > 0)
                        {{ $overdueDueCount }} gecikmiş
                    @else
                        7 gün içinde
                    @endif
                </p>
            </a>
        </div>
    </div>

    <div class="px-5 sm:px-6 py-3 border-t border-neutral-100 dark:border-slate-800 flex flex-wrap gap-x-5 gap-y-2 text-sm">
        @if($personnel->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $personnel->phone) }}" class="inline-flex items-center gap-1.5 text-neutral-700 dark:text-neutral-300 hover:text-emerald-600">
                <span class="text-neutral-400">Tel</span>
                <span class="font-medium">{{ $personnel->phone }}</span>
            </a>
        @endif
        @if($personnel->email)
            <a href="mailto:{{ $personnel->email }}" class="inline-flex items-center gap-1.5 text-neutral-700 dark:text-neutral-300 hover:text-emerald-600">
                <span class="text-neutral-400">E-posta</span>
                <span class="font-medium truncate max-w-[16rem]">{{ $personnel->email }}</span>
            </a>
        @endif
        <span class="inline-flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
            <span class="text-neutral-400">Kategori</span>
            <span class="font-medium text-neutral-800 dark:text-neutral-200">{{ \App\Support\PersonnelCategory::label($personnel->category) }}</span>
        </span>
        <span class="inline-flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
            <span class="text-neutral-400">Toplam ciro</span>
            <span class="font-medium tabular-nums text-neutral-800 dark:text-neutral-200">₺{{ number_format($salesStats->total, 0, ',', '.') }}</span>
            <span class="text-neutral-400">· %{{ $collectionRate }} tahsil</span>
        </span>
    </div>
</div>

{{-- Bölüm navigasyonu --}}
<nav class="mb-6 -mx-1 px-1 overflow-x-auto">
    <div class="inline-flex min-w-full sm:min-w-0 items-center gap-1 p-1 rounded-xl bg-neutral-100/80 dark:bg-slate-900/60 border border-neutral-200/80 dark:border-slate-800">
        <a href="#ozet" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Özet</a>
        <a href="#performans" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Performans</a>
        @if($openTaskCount > 0)
        <a href="#gorevler" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">
            Görevler
            <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">{{ $openTaskCount }}</span>
        </a>
        @endif
        <a href="#termin" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">
            Termin
            @if($upcomingDueSales->isNotEmpty())
            <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-semibold {{ $overdueDueCount > 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' }}">{{ $upcomingDueSales->count() }}</span>
            @endif
        </a>
        <a href="#siparisler" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Siparişler</a>
        @if($quotes->isNotEmpty())
        <a href="#teklifler" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Teklifler</a>
        @endif
    </div>
</nav>

{{-- Sipariş özeti --}}
<div id="ozet" class="card overflow-hidden mb-6 scroll-mt-24">
    <div class="px-5 py-4 border-b border-neutral-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Sipariş Özeti</h2>
            <p class="text-xs text-neutral-500 mt-0.5">İptal edilmeyen siparişler · tüm dönem</p>
        </div>
        <span class="text-xs font-medium text-neutral-600 dark:text-slate-300">Bu ay {{ $salesStats->monthCount }} sipariş</span>
    </div>
    <div class="p-5 space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="rounded-xl border border-neutral-200/80 dark:border-slate-700 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Sipariş</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">{{ $salesStats->count }}</p>
                <p class="mt-1 text-xs text-neutral-500">{{ $salesStats->activeCount }} aktif</p>
            </div>
            <div class="rounded-xl border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/15 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700/80 dark:text-emerald-300/80">Ciro</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300">₺{{ number_format($salesStats->total, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-emerald-700/70 dark:text-emerald-400/70">Tahsil ₺{{ number_format($collectedTotal, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-red-200/80 dark:border-red-900/40 bg-red-50/40 dark:bg-red-950/15 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700/80 dark:text-red-300/80">Alınacak</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-red-700 dark:text-red-300">₺{{ number_format($salesStats->totalReceivable ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-red-700/70 dark:text-red-400/70">Bekleyen tahsilat</p>
            </div>
            <div class="rounded-xl border border-neutral-200/80 dark:border-slate-700 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Tahsilat oranı</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">%{{ $collectionRate }}</p>
                <p class="mt-1 text-xs text-neutral-500">Ciroya göre</p>
            </div>
        </div>

        @if(($salesStats->total ?? 0) > 0)
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2 text-xs">
                <span class="font-semibold uppercase tracking-wider text-neutral-500">Tahsilat durumu</span>
                <span class="tabular-nums text-neutral-600 dark:text-slate-300">
                    <span class="font-semibold text-emerald-600">₺{{ number_format($collectedTotal, 0, ',', '.') }}</span>
                    <span class="text-neutral-400 mx-1">/</span>
                    ₺{{ number_format($salesStats->total, 0, ',', '.') }}
                </span>
            </div>
            <div class="h-2 rounded-full bg-neutral-200 dark:bg-slate-700 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $collectionRate }}%"></div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Aylık performans --}}
@if($perf)
@php
    $maxCount = max(1, ($thisMonth['count'] ?? 0), ($lastMonth['count'] ?? 0));
    $maxTotal = max(1, ($thisMonth['total'] ?? 0), ($lastMonth['total'] ?? 0));
@endphp
<div id="performans" class="card overflow-hidden mb-6 scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Aylık Performans</h2>
            <p class="text-sm text-neutral-500 mt-1">Geçen ay ↔ bu ay · prim = ciro × oran</p>
        </div>
        @if(($perf['totalChange'] ?? 0) != 0)
            @include('partials.performance-change-badge', ['change' => $perf['totalChange']])
        @endif
    </div>
    <div class="p-5 sm:p-6 space-y-5">
        <div class="overflow-x-auto -mx-1 px-1">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-100 dark:border-slate-800">
                        <th class="py-2 pr-4 font-semibold">Metrik</th>
                        <th class="py-2 pr-4 font-semibold">{{ $lastMonth['label'] ?? 'Geçen ay' }}</th>
                        <th class="py-2 pr-4 font-semibold text-emerald-700 dark:text-emerald-300">{{ $thisMonth['label'] ?? 'Bu ay' }}</th>
                        <th class="py-2 font-semibold">Değişim</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-slate-800">
                    <tr>
                        <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Sipariş</td>
                        <td class="py-3 pr-4 tabular-nums font-medium">{{ $lastMonth['count'] ?? 0 }}</td>
                        <td class="py-3 pr-4 tabular-nums font-semibold text-neutral-900 dark:text-white">{{ $thisMonth['count'] ?? 0 }}</td>
                        <td class="py-3">@include('partials.performance-change-badge', ['change' => $perf['countChange']])</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Ciro</td>
                        <td class="py-3 pr-4 tabular-nums font-medium">₺{{ number_format($lastMonth['total'] ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 tabular-nums font-semibold text-neutral-900 dark:text-white">₺{{ number_format($thisMonth['total'] ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3">@include('partials.performance-change-badge', ['change' => $perf['totalChange']])</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Tahsilat</td>
                        <td class="py-3 pr-4 tabular-nums font-medium text-emerald-600">₺{{ number_format($lastMonth['collected'] ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 tabular-nums font-semibold text-emerald-700 dark:text-emerald-300">₺{{ number_format($thisMonth['collected'] ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3">@include('partials.performance-change-badge', ['change' => $perf['collectedChange']])</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Prim</td>
                        <td class="py-3 pr-4 tabular-nums font-medium text-violet-700 dark:text-violet-300">₺{{ number_format($lastMonth['commission'] ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 tabular-nums font-bold text-violet-700 dark:text-violet-300">₺{{ number_format($thisMonth['commission'] ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3">@include('partials.performance-change-badge', ['change' => $perf['commissionChange'] ?? 0])</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-neutral-500">Sipariş sayısı</span>
                    <span class="tabular-nums text-neutral-600">{{ $thisMonth['count'] ?? 0 }} / {{ $lastMonth['count'] ?? 0 }}</span>
                </div>
                <div class="h-2 rounded-full bg-neutral-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, (($thisMonth['count'] ?? 0) / $maxCount) * 100) }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-neutral-500">Ciro</span>
                    <span class="tabular-nums text-neutral-600">₺{{ number_format($thisMonth['total'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="h-2 rounded-full bg-neutral-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-600" style="width: {{ min(100, (($thisMonth['total'] ?? 0) / $maxTotal) * 100) }}%"></div>
                </div>
            </div>
        </div>

        <p class="text-sm text-neutral-600 dark:text-slate-400 pt-1 border-t border-neutral-100 dark:border-slate-800">
            @if(($thisMonth['count'] ?? 0) === 0 && ($lastMonth['count'] ?? 0) === 0)
                Bu personel için karşılaştırılacak aylık satış verisi henüz yok.
            @elseif(($perf['totalChange'] ?? 0) > 0)
                Bu ay cirosu geçen aya göre <span class="font-semibold text-emerald-600">%{{ abs($perf['totalChange']) }} artış</span> gösteriyor.
            @elseif(($perf['totalChange'] ?? 0) < 0)
                Bu ay cirosu geçen aya göre <span class="font-semibold text-red-600">%{{ abs($perf['totalChange']) }} azalış</span> gösteriyor.
            @else
                Bu ay cirosu geçen ay ile aynı seviyede.
            @endif
            @if($commissionRate <= 0 && $isAdmin)
                <a href="{{ route('personnel.edit', $personnel) }}" class="ml-1 text-violet-600 hover:underline">Prim oranı ekle →</a>
            @endif
        </p>
    </div>
</div>
@endif

<div id="gorevler" class="scroll-mt-24">
@include('partials.personnel-tasks-list')
</div>

{{-- Termin --}}
<div id="termin" class="card overflow-hidden mb-6 w-full scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b {{ $overdueDueCount > 0 ? 'border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/25' : 'border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-950/25' }} flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Yaklaşan Terminler</h2>
            <p class="text-sm text-neutral-600 dark:text-slate-400 mt-1">Önümüzdeki 7 gün · gecikenler dahil</p>
        </div>
        @if($upcomingDueSales->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @if($overdueDueCount > 0)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ $overdueDueCount }} gecikmiş</span>
            @endif
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ $upcomingDueSales->count() }} sipariş</span>
        </div>
        @endif
    </div>
    <div class="overflow-x-auto">
        @if($upcomingDueSales->isEmpty())
        <div class="px-6 py-10 text-center text-neutral-500 dark:text-slate-400">
            Önümüzdeki 7 gün içinde termin tarihi olan sipariş yok.
        </div>
        @else
        <table class="min-w-full w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th">Kalan</th>
                    <th class="table-th">Teslimat</th>
                    <th class="table-th text-right w-24">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($upcomingDueSales as $sale)
                @php($daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null)
                @php($terminClass = $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 dark:text-red-400 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-neutral-600 dark:text-slate-300'))
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 transition-colors {{ $daysLeft !== null && $daysLeft < 0 ? 'bg-red-50/40 dark:bg-red-950/10' : '' }}">
                    <td class="table-td">
                        <a href="{{ route('sales.show', $sale) }}" class="font-medium text-neutral-900 dark:text-white hover:text-emerald-600">{{ $sale->saleNumber }}</a>
                        @if($sale->branch)
                        <span class="block text-xs text-emerald-700/80 dark:text-emerald-400/80 font-normal mt-0.5">{{ $sale->branch->name }}</span>
                        @endif
                    </td>
                    <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">
                        @if($sale->customer?->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $sale->customer->phone) }}" class="hover:text-emerald-600">{{ $sale->customer->phone }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="table-td {{ $terminClass }}">{{ $sale->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td {{ $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 dark:text-red-400 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-neutral-600 dark:text-slate-300') }}">
                        @if($daysLeft === null)—
                        @elseif($daysLeft < 0){{ abs($daysLeft) }} gün gecikti
                        @elseif($daysLeft === 0)Bugün
                        @else{{ $daysLeft }} gün
                        @endif
                    </td>
                    <td class="table-td">@include('partials.delivery-status-badge', ['sale' => $sale])</td>
                    <td class="table-td text-right">
                        <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Gör</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Siparişler --}}
<div id="siparisler" class="card overflow-hidden mb-6 w-full scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Siparişler</h2>
            <p class="text-sm text-neutral-500 mt-1">{{ $salesStats->count }} kayıt · sayfalı liste</p>
        </div>
        @if($isAdmin)
        <a href="{{ route('sales.index', ['personnelId' => $personnel->id]) }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium shrink-0">Satış listesinde aç →</a>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th text-right">Toplam</th>
                    <th class="table-th">Ödeme</th>
                    <th class="table-th">Teslimat</th>
                    <th class="table-th text-right w-24">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @forelse($sales as $sale)
                @php($saleStatus = \App\Support\CustomerBalance::saleStatus($sale))
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 transition-colors {{ ($sale->isCancelled ?? false) ? 'opacity-60' : '' }}">
                    <td class="table-td">
                        <a href="{{ route('sales.show', $sale) }}" class="font-medium text-neutral-900 dark:text-white hover:text-emerald-600">{{ $sale->saleNumber }}</a>
                        @if($sale->isCancelled ?? false)
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 font-medium">İptal</span>
                        @endif
                        @if($sale->branch)
                        <span class="block text-xs text-emerald-700/80 dark:text-emerald-400/80 font-normal mt-0.5">{{ $sale->branch->name }}</span>
                        @endif
                        @if($sale->needsFinalMeasurement ?? false)
                        <span class="block mt-1">@include('partials.final-measurement-badge', ['sale' => $sale])</span>
                        @endif
                    </td>
                    <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">{{ $sale->saleDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">{{ $sale->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td text-right font-medium tabular-nums">₺{{ number_format($sale->grandTotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td">
                        @if(!($sale->isCancelled ?? false))
                        @include('partials.payment-status-badge', ['status' => $saleStatus])
                        @else
                        —
                        @endif
                    </td>
                    <td class="table-td">
                        @if(!($sale->isCancelled ?? false))
                        @include('partials.delivery-status-badge', ['sale' => $sale])
                        @else
                        —
                        @endif
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Gör</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-10 text-center text-neutral-500 dark:text-slate-400">
                        Bu personele atanmış sipariş yok.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sales->hasPages())
    <div class="px-6 py-3 border-t border-neutral-100 dark:border-slate-700">{{ $sales->links() }}</div>
    @endif
</div>

@if($quotes->isNotEmpty())
<div id="teklifler" class="card overflow-hidden w-full scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Teklifler</h2>
        <p class="text-sm text-neutral-500 mt-1">Son {{ $quotes->count() }} teklif</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th">Tarih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($quotes as $q)
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40">
                    <td class="table-td">
                        <a href="{{ route('quotes.show', $q) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $q->quoteNumber }}</a>
                        @if($q->branch)
                        <span class="block text-xs text-emerald-700/80 dark:text-emerald-400/80 font-normal mt-0.5">{{ $q->branch->name }}</span>
                        @endif
                    </td>
                    <td class="table-td">{{ $q->customer?->name ?? '—' }}</td>
                    <td class="table-td text-right font-medium tabular-nums">₺{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">{{ $q->createdAt?->format('d.m.Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
