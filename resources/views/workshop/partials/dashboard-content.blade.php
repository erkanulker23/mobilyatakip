@php
    use App\Models\SaleProductionStage;
    use App\Support\SaleDelivery;
    use App\Support\ServiceTicketStatus;
    use App\Support\UserGreeting;

    $terminDays = $terminDays ?? 14;
    $terminAlertDays = $terminAlertDays ?? 3;
    $defaultTab = $productionSales->isNotEmpty()
        ? 'uretim'
        : (($workshopStats->overdueCount ?? 0) > 0 || ($urgentTerminCount ?? 0) > 0 ? 'termin' : 'uretim');
@endphp

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        @if(auth()->user()?->isAdmin() && !($viewingOwnProfile ?? true))
        <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
            <a href="{{ route('personnel.index') }}" class="hover:text-neutral-900 dark:hover:text-white">Personel</a>
            <span>/</span>
            <span class="text-neutral-700 dark:text-neutral-300">{{ $personnel->name }}</span>
        </div>
        @endif
        <h1 class="page-title">{{ ($viewingOwnProfile ?? true) ? UserGreeting::message() : $personnel->name . ' — Atölye' }}</h1>
        <p class="page-desc mt-1">Üretim takibi, terminler ve servis kayıtları — bugün odaklanmanız gereken işler{{ $personnel->branch ? ' · '.$personnel->branch->name : '' }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('reports.upcoming-due', ['days' => $terminDays]) }}" class="btn-secondary text-sm">Termin Raporu</a>
        <a href="{{ route('service-tickets.create') }}" class="btn-primary text-sm">Yeni SSH</a>
        @if(auth()->user()?->isAdmin())
        <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit text-sm">Düzenle</a>
        @endif
    </div>
</div>

@if(empty($productionStagesReady))
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Üretim aşaması kayıtları henüz aktif değil. Sistem yöneticisinin <code class="text-xs">php artisan migrate --force</code> çalıştırması gerekiyor.
</div>
@endif

@if(($workshopStats->overdueCount ?? 0) > 0)
<a href="#termin" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100 transition-all hover:shadow-md hover:border-red-300 dark:hover:border-red-800 cursor-pointer block">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    <div>
        <p class="font-semibold">{{ $workshopStats->overdueCount }} siparişin termin tarihi geçmiş</p>
        <p class="mt-0.5 text-red-800/80 dark:text-red-200/80">Termin sekmesinden gecikmiş siparişleri inceleyin →</p>
    </div>
</a>
@endif

<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('workshop.index') }}" class="card p-4 text-left w-full transition-all hover:shadow-md cursor-pointer block {{ $workshopStats->productionCount > 0 ? 'ring-1 ring-violet-200 dark:ring-violet-800/50' : '' }}">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Üretimde</p>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
            </span>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mt-2">{{ $workshopStats->productionCount }}</p>
        <p class="text-xs text-neutral-500 mt-1">Not ve eksiklik eklenebilir</p>
    </a>

    <a href="{{ route('reports.upcoming-due', ['days' => $terminDays]) }}" class="card p-4 text-left w-full transition-all hover:shadow-md cursor-pointer block {{ ($workshopStats->overdueCount ?? 0) > 0 ? 'ring-1 ring-red-200 dark:ring-red-800/50' : (($urgentTerminCount ?? 0) > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/50' : '') }}">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Termin ({{ $terminDays }} gün)</p>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </span>
        </div>
        <p class="text-2xl sm:text-3xl font-bold {{ ($workshopStats->overdueCount ?? 0) > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-white' }} mt-2">{{ $workshopStats->upcomingTerminCount }}</p>
        <p class="text-xs {{ ($workshopStats->overdueCount ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-neutral-500' }} mt-1">
            @if(($workshopStats->overdueCount ?? 0) > 0)
            {{ $workshopStats->overdueCount }} gecikmiş
            @elseif(($urgentTerminCount ?? 0) > 0)
            {{ $urgentTerminCount }} acil (≤{{ $terminAlertDays }} gün)
            @else
            {{ $upcomingInProductionCount ?? 0 }} tanesi üretimde
            @endif
        </p>
    </a>

    <a href="{{ route('service-tickets.index') }}" class="card p-4 text-left w-full transition-all hover:shadow-md cursor-pointer block {{ $workshopStats->openSshCount > 0 ? 'ring-1 ring-orange-200 dark:ring-orange-800/50' : '' }}">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Açık SSH</p>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </span>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mt-2">{{ $workshopStats->openSshCount }}</p>
        <p class="text-xs text-neutral-500 mt-1">Açık servis kaydı</p>
    </a>

    <a href="{{ route('workshop.index', ['type' => SaleProductionStage::TYPE_DEFICIENCY]) }}" class="card p-4 text-left w-full transition-all hover:shadow-md cursor-pointer block border-red-200/80 dark:border-red-900/40 bg-red-50/40 dark:bg-red-950/20">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-red-700/80 dark:text-red-300/80 uppercase tracking-wide">Açık Eksiklik</p>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </span>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-red-800 dark:text-red-200 mt-2">{{ $workshopStats->openDeficienciesCount }}</p>
        <p class="text-xs text-red-700/70 dark:text-red-400/70 mt-1">Çözülmemiş parça sorunu</p>
    </a>
</div>

<div id="atolyem-list" class="card overflow-hidden mb-6 scroll-mt-24" data-workshop-tabs data-default-tab="{{ e($defaultTab) }}">
    <div class="px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-1.5">
            <a href="#uretim" data-workshop-tab-btn="uretim" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $defaultTab === 'uretim' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                Üretimde
                @if($productionSales->isNotEmpty())
                <span class="ml-1 opacity-80">({{ $productionSales->count() }})</span>
                @endif
            </a>
            <a href="#termin" data-workshop-tab-btn="termin" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $defaultTab === 'termin' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                Termin
                @if($upcomingDueSales->isNotEmpty())
                <span class="ml-1 opacity-80">({{ $upcomingDueSales->count() }})</span>
                @endif
            </a>
            <a href="#ssh" data-workshop-tab-btn="ssh" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $defaultTab === 'ssh' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                SSH
                @if($openServiceTickets->isNotEmpty())
                <span class="ml-1 opacity-80">({{ $openServiceTickets->count() }})</span>
                @endif
            </a>
        </div>
        <a href="{{ route('reports.upcoming-due', ['days' => $terminDays]) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 shrink-0">Detaylı termin raporu →</a>
    </div>

    {{-- Üretimde --}}
    <div data-workshop-tab-panel="uretim" class="divide-y divide-neutral-100 dark:divide-slate-800{{ $defaultTab !== 'uretim' ? ' hidden' : '' }}">
        @if($productionSales->isEmpty())
        <div class="px-6 py-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-500">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
            </div>
            <p class="mt-4 text-neutral-600 dark:text-neutral-400 font-medium">Şu an üretimde sipariş yok</p>
            <p class="mt-1 text-sm text-neutral-500">Üretime alınan siparişler burada listelenir.</p>
        </div>
        @else
        @foreach($productionSales as $sale)
        @php
            $termin = SaleDelivery::terminListMeta($sale);
            $hasDeficiency = ($sale->open_deficiencies_count ?? 0) > 0;
        @endphp
        <div class="p-4 sm:p-5 hover:bg-neutral-50/70 dark:hover:bg-slate-900/30 transition-colors {{ $hasDeficiency ? 'bg-red-50/30 dark:bg-red-950/10' : '' }}">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-neutral-900 dark:text-white">{{ $sale->saleNumber }}</p>
                        <span class="badge badge-blue">Üretimde</span>
                        @if($hasDeficiency)
                        <span class="badge badge-amber">{{ $sale->open_deficiencies_count }} eksiklik</span>
                        @endif
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-sm text-neutral-600 dark:text-neutral-400">
                        @if($showCustomerNames && $sale->customer?->name)
                        <span>{{ $sale->customer->name }}</span>
                        @endif
                        @if($showSalesPersonnel && $sale->personnel?->name)
                        <span class="text-neutral-400">·</span>
                        <span>Satış: {{ $sale->personnel->name }}</span>
                        @endif
                        @if($sale->branch?->name)
                        <span class="text-neutral-400">·</span>
                        <span>{{ $sale->branch->name }}</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        @if($sale->dueDate)
                        <span class="inline-flex items-center gap-1 text-xs {{ $termin['class'] ?? 'text-neutral-600' }}">
                            Termin {{ $sale->dueDate->format('d.m.Y') }}
                            @if($termin['suffix'] ?? null)
                            · {{ $termin['suffix'] }}
                            @endif
                        </span>
                        @else
                        <span class="text-neutral-400">Termin yok</span>
                        @endif
                        @if(($sale->production_stages_count ?? 0) > 0)
                        <span class="badge badge-blue">{{ $sale->production_stages_count }} kayıt</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('workshop.show', $sale) }}" class="btn-view w-full sm:w-auto justify-center shrink-0">
                    Siparişi Aç
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>
        @endforeach
        @endif
    </div>

    {{-- Termin --}}
    <div data-workshop-tab-panel="termin" class="divide-y divide-neutral-100 dark:divide-slate-800{{ $defaultTab !== 'termin' ? ' hidden' : '' }}">
        @if($upcomingDueSales->isEmpty())
        <div class="px-6 py-14 text-center">
            <p class="text-neutral-600 dark:text-neutral-400 font-medium">Yaklaşan termin yok</p>
            <p class="mt-1 text-sm text-neutral-500">Önümüzdeki {{ $terminDays }} gün içinde termin tarihi olan sipariş bulunmuyor.</p>
        </div>
        @else
        @foreach($upcomingDueSales as $sale)
        @php
            $daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null;
            $orderStatus = SaleDelivery::currentStatus($sale);
            $inProduction = $orderStatus === SaleDelivery::IN_PRODUCTION;
            if ($daysLeft === null) {
                $daysLabel = '—';
                $daysClass = 'text-neutral-600 bg-neutral-100 dark:bg-neutral-800';
            } elseif ($daysLeft < 0) {
                $daysLabel = abs($daysLeft) . ' gün gecikti';
                $daysClass = 'text-red-700 bg-red-50 dark:bg-red-950/40';
            } elseif ($daysLeft === 0) {
                $daysLabel = 'Bugün';
                $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
            } elseif ($daysLeft <= $terminAlertDays) {
                $daysLabel = $daysLeft . ' gün kaldı';
                $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
            } else {
                $daysLabel = $daysLeft . ' gün';
                $daysClass = 'text-neutral-600 bg-neutral-100 dark:bg-neutral-800';
            }
        @endphp
        <div class="p-4 sm:p-5 hover:bg-neutral-50/70 dark:hover:bg-slate-900/30 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-neutral-900 dark:text-white">{{ $sale->saleNumber }}</p>
                        <span class="badge {{ $inProduction ? 'badge-blue' : 'badge-neutral' }}">{{ SaleDelivery::label($orderStatus) }}</span>
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-sm text-neutral-600 dark:text-neutral-400">
                        @if($showCustomerNames && $sale->customer?->name)
                        <span>{{ $sale->customer->name }}</span>
                        @endif
                        @if($showSalesPersonnel && $sale->personnel?->name)
                        <span class="text-neutral-400">·</span>
                        <span>Satış: {{ $sale->personnel->name }}</span>
                        @endif
                        @if($sale->branch?->name)
                        <span class="text-neutral-400">·</span>
                        <span>{{ $sale->branch->name }}</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-neutral-600 dark:text-neutral-400">{{ $sale->dueDate?->format('d.m.Y') ?? '—' }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-md {{ $daysClass }}">{{ $daysLabel }}</span>
                    </div>
                </div>
                @if($inProduction)
                <a href="{{ route('workshop.show', $sale) }}" class="btn-view w-full sm:w-auto justify-center shrink-0">Aç</a>
                @else
                <a href="{{ route('workshop.show', ['sale' => $sale, 'from' => 'termin', 'days' => $terminDays ?? 14]) }}" class="btn-secondary w-full sm:w-auto justify-center shrink-0 text-sm py-2.5">Detay</a>
                @endif
            </div>
        </div>
        @endforeach
        @endif
    </div>

    {{-- SSH --}}
    <div data-workshop-tab-panel="ssh" class="divide-y divide-neutral-100 dark:divide-slate-800{{ $defaultTab !== 'ssh' ? ' hidden' : '' }}">
        @if($openServiceTickets->isEmpty())
        <div class="px-6 py-14 text-center">
            <p class="text-neutral-600 dark:text-neutral-400 font-medium">Açık SSH kaydı yok</p>
            <a href="{{ route('service-tickets.create') }}" class="inline-block mt-3 text-sm font-medium text-emerald-600 hover:text-emerald-700">Yeni servis kaydı oluştur →</a>
        </div>
        @else
        @foreach($openServiceTickets as $ticket)
        @php
            $problems = ServiceTicketStatus::normalizeProblems($ticket->reportedProblems ?? []);
            if ($problems === [] && $ticket->issueType) {
                $problems = [['description' => $ticket->issueType, 'status' => 'bekliyor']];
            }
            $sshDaysLeft = $ticket->dueDate ? (int) now()->startOfDay()->diffInDays($ticket->dueDate, false) : null;
        @endphp
        <div class="p-4 sm:p-5 hover:bg-neutral-50/70 dark:hover:bg-slate-900/30 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-neutral-900 dark:text-white">{{ $ticket->ticketNumber }}</p>
                        <span class="badge badge-amber">{{ ServiceTicketStatus::label($ticket->status ?? 'acildi') }}</span>
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-sm text-neutral-600 dark:text-neutral-400">
                        @if($showCustomerNames && $ticket->customer?->name)
                        <span>{{ $ticket->customer->name }}</span>
                        @endif
                        @if($ticket->sale?->saleNumber)
                        <span class="text-neutral-400">·</span>
                        <span>{{ $ticket->sale->saleNumber }}</span>
                        @endif
                        @if($ticket->branch?->name)
                        <span class="text-neutral-400">·</span>
                        <span>{{ $ticket->branch->name }}</span>
                        @endif
                    </div>
                    @if($problems[0]['description'] ?? null)
                    <p class="mt-2 text-sm text-neutral-500 line-clamp-1">{{ $problems[0]['description'] }}</p>
                    @endif
                    <div class="mt-2 text-xs text-neutral-500">
                        Termin: {{ $ticket->dueDate?->format('d.m.Y') ?? '—' }}
                        @if($sshDaysLeft !== null && $sshDaysLeft < 0)
                        <span class="text-red-600 font-medium"> · {{ abs($sshDaysLeft) }} gün gecikti</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <a href="{{ route('service-tickets.show', $ticket) }}" class="btn-secondary text-sm py-2.5">Gör</a>
                    <a href="{{ route('service-tickets.edit', $ticket) }}" class="btn-view text-sm py-2.5">Düzenle</a>
                </div>
            </div>
        </div>
        @endforeach
        <div class="px-6 py-3 border-t border-neutral-100 dark:border-slate-800 bg-neutral-50/50 dark:bg-neutral-900/30">
            <a href="{{ route('service-tickets.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Tüm SSH kayıtları →</a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-workshop-tabs]');
    if (!root) return;

    var tabs = ['uretim', 'termin', 'ssh'];
    var defaultTab = root.getAttribute('data-default-tab') || 'uretim';
    var btnActive = ['bg-neutral-900', 'text-white', 'dark:bg-emerald-600'];
    var btnInactive = ['bg-neutral-100', 'text-neutral-600', 'dark:bg-neutral-800', 'dark:text-neutral-300'];

    function setBtnState(btn, active) {
        btnActive.forEach(function (c) { btn.classList.toggle(c, active); });
        btnInactive.forEach(function (c) { btn.classList.toggle(c, !active); });
    }

    function showTab(name, scroll) {
        if (tabs.indexOf(name) === -1) name = defaultTab;

        root.querySelectorAll('[data-workshop-tab-panel]').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-workshop-tab-panel') !== name);
        });

        root.querySelectorAll('[data-workshop-tab-btn]').forEach(function (btn) {
            setBtnState(btn, btn.getAttribute('data-workshop-tab-btn') === name);
        });

        if (scroll) {
            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function syncFromHash(scroll) {
        var hash = (window.location.hash || '').replace('#', '');
        showTab(tabs.indexOf(hash) >= 0 ? hash : defaultTab, scroll);
    }

    root.querySelectorAll('[data-workshop-tab-btn]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.setTimeout(function () { syncFromHash(true); }, 0);
        });
    });

    syncFromHash(window.location.hash.length > 1);
    window.addEventListener('hashchange', function () { syncFromHash(true); });
})();
</script>
@endpush

@include('partials.personnel-tasks-list')
