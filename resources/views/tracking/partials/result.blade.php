@php
    $isSale = ($result['type'] ?? '') === 'sale';
    $stageKey = $result['currentStage']['key'] ?? '';
    $badgeClass = $isSale
        ? \App\Support\SaleDelivery::badgeClass($stageKey === 'cancelled' ? 'pending' : $stageKey)
        : match ($stageKey) {
            'tamamlandi' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
            'devam_ediyor' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
            'parca_bekleniyor' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'sevkiyatci_bekleniyor' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            'iptal' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            default => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        };
@endphp

<div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 shadow-sm overflow-hidden">
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                {{ $isSale ? 'Sipariş' : 'SSH / Servis' }}
            </p>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100 mt-0.5 font-mono tracking-tight">{{ $result['code'] }}</h2>
            @if(!empty($result['customerName']))
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">{{ $result['customerName'] }}</p>
            @endif
        </div>
        <span class="inline-flex self-start sm:self-auto items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $badgeClass }}">
            {{ $result['currentStage']['label'] ?? '—' }}
        </span>
    </div>

    <div class="px-5 sm:px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm border-b border-neutral-100 dark:border-neutral-800">
        @if($isSale)
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Sipariş tarihi</p>
                <p class="font-medium mt-0.5">{{ $result['saleDate'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Termin</p>
                <p class="font-medium mt-0.5">{{ $result['dueDate'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Teslim</p>
                <p class="font-medium mt-0.5">{{ $result['deliveredAt'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">SSH</p>
                <p class="font-medium mt-0.5">{{ count($result['serviceTickets'] ?? []) }} kayıt</p>
            </div>
        @else
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Açılış</p>
                <p class="font-medium mt-0.5">{{ $result['openedAt'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Termin</p>
                <p class="font-medium mt-0.5">{{ $result['dueDate'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Kapanış</p>
                <p class="font-medium mt-0.5">{{ $result['closedAt'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Problemler</p>
                <p class="font-medium mt-0.5">{{ $result['problemSummary'] ?? '—' }}</p>
            </div>
        @endif
    </div>

    {{-- Aşama çubuğu --}}
    @if(!empty($result['stages']))
    <div class="px-5 sm:px-6 py-6 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Süreç aşamaları</h3>
        <div class="hidden sm:flex items-start gap-0">
            @foreach($result['stages'] as $i => $stage)
                <div class="flex items-center {{ $i < count($result['stages']) - 1 ? 'flex-1' : '' }} min-w-0">
                    <div class="flex flex-col items-center text-center w-[4.5rem] shrink-0">
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold border-2',
                            'bg-emerald-600 border-emerald-600 text-white' => $stage['done'] && !($stage['current'] ?? false),
                            'bg-emerald-50 border-emerald-600 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $stage['current'] ?? false,
                            'bg-neutral-100 border-neutral-300 text-neutral-400 dark:bg-neutral-800 dark:border-neutral-600' => !($stage['done'] ?? false) && !($stage['current'] ?? false),
                        ])>
                            @if(($stage['done'] ?? false) && !($stage['current'] ?? false))
                                ✓
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="mt-2 text-[11px] leading-tight font-medium {{ ($stage['current'] ?? false) ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-600 dark:text-neutral-400' }}">
                            {{ $stage['label'] }}
                        </span>
                        @if(!empty($stage['at']))
                            <span class="text-[10px] text-neutral-400 mt-0.5">{{ $stage['at'] }}</span>
                        @endif
                    </div>
                    @if($i < count($result['stages']) - 1)
                        <div class="stage-line mx-1 mt-4 {{ ($stage['done'] ?? false) ? 'bg-emerald-500' : 'bg-neutral-200 dark:bg-neutral-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <ol class="sm:hidden space-y-3">
            @foreach($result['stages'] as $stage)
                <li class="flex gap-3">
                    <span @class([
                        'mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                        'bg-emerald-600 text-white' => $stage['done'] ?? false,
                        'bg-neutral-200 text-neutral-500 dark:bg-neutral-700 dark:text-neutral-300' => !($stage['done'] ?? false),
                        'ring-2 ring-emerald-400 ring-offset-2 dark:ring-offset-neutral-900' => $stage['current'] ?? false,
                    ])>
                        @if($stage['done'] ?? false) ✓ @else • @endif
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium {{ ($stage['current'] ?? false) ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-800 dark:text-neutral-200' }}">{{ $stage['label'] }}</p>
                        @if(!empty($stage['at']))
                            <p class="text-xs text-neutral-500">{{ $stage['at'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
    @endif

    @if(!$isSale && !empty($result['problems']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Bildirilen problemler</h3>
        <ul class="space-y-2">
            @foreach($result['problems'] as $problem)
                <li class="flex items-start justify-between gap-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 px-3 py-2.5">
                    <span class="text-sm text-neutral-800 dark:text-neutral-200">{{ $problem['description'] }}</span>
                    <span class="shrink-0 text-xs font-medium px-2 py-1 rounded-full
                        {{ ($problem['status'] ?? '') === 'duzeltildi' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : (($problem['status'] ?? '') === 'duzeltilemedi' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300') }}">
                        {{ $problem['statusLabel'] }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($isSale && !empty($result['serviceTickets']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">SSH kayıtları</h3>
        <ul class="space-y-2">
            @foreach($result['serviceTickets'] as $ticket)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-neutral-100 dark:border-neutral-800 px-3 py-2.5">
                    <div>
                        <a href="{{ route('tracking.show', $ticket['ticketNumber']) }}" class="font-mono text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $ticket['ticketNumber'] }}</a>
                        <p class="text-xs text-neutral-500 mt-0.5">{{ $ticket['problemSummary'] }}</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $ticket['isOpen'] ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                        {{ $ticket['statusLabel'] }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(!$isSale && !empty($result['linkedSale']['saleNumber']))
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-100 dark:border-neutral-800 text-sm">
        Bağlı sipariş:
        <a href="{{ route('tracking.show', $result['linkedSale']['saleNumber']) }}" class="font-mono font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
            {{ $result['linkedSale']['saleNumber'] }}
        </a>
        <span class="text-neutral-500">· {{ $result['linkedSale']['currentStage']['label'] ?? '' }}</span>
    </div>
    @endif

    {{-- Geçmiş --}}
    <div class="px-5 sm:px-6 py-5">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Süreç geçmişi</h3>
        @if(empty($result['history']))
            <p class="text-sm text-neutral-500">Henüz görüntülenecek bir süreç kaydı yok.</p>
        @else
            <div class="relative space-y-0">
                @foreach($result['history'] as $entry)
                    <div class="flex gap-4 pb-5 last:pb-0">
                        <div class="flex flex-col items-center">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm',
                                'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300' => ($entry['source'] ?? '') === 'production',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => ($entry['source'] ?? '') === 'ssh',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => ($entry['source'] ?? '') === 'activity',
                            ])>•</span>
                            @if(!$loop->last)
                                <div class="mt-1 w-px flex-1 bg-neutral-200 dark:bg-neutral-700 min-h-[20px]"></div>
                            @endif
                        </div>
                        <div class="min-w-0 pt-0.5 flex-1">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $entry['title'] }}</p>
                            @if(!empty($entry['detail']))
                                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-0.5">{{ $entry['detail'] }}</p>
                            @endif
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{{ $entry['at'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
