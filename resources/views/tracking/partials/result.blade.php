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
    if ($isSale && $stageKey === \App\Support\SaleDelivery::FINAL_MEASUREMENT) {
        $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
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
            @if(!empty($result['branchName']))
                <p class="text-sm text-neutral-600 dark:text-neutral-300 mt-0.5">{{ $result['branchName'] }} şubesi</p>
            @endif
        </div>
        <span class="inline-flex self-start sm:self-auto items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $badgeClass }}">
            {{ $result['currentStage']['label'] ?? '—' }}
        </span>
    </div>

    {{-- Tarih özeti --}}
    <div @class([
        'px-5 sm:px-6 py-4 grid grid-cols-2 gap-4 text-sm border-b border-neutral-100 dark:border-neutral-800',
        'sm:grid-cols-3' => $isSale,
        'sm:grid-cols-4' => ! $isSale,
    ])>
        @if($isSale)
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Sipariş tarihi</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['saleDate'] ?? '—' }}</p>
                <p class="text-[11px] text-neutral-400 mt-0.5">Ne zaman sipariş verdi</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Tahmini teslim (termin)</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['dueDate'] ?? '—' }}</p>
                @if(!empty($result['dueHint']))
                    <p class="text-[11px] mt-0.5 {{ str_contains($result['dueHint'], 'gecik') ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $result['dueHint'] }}</p>
                @elseif(empty($result['dueDate']))
                    <p class="text-[11px] text-neutral-400 mt-0.5">Henüz belirlenmedi</p>
                @else
                    <p class="text-[11px] text-neutral-400 mt-0.5">Ne zaman gelecek</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Teslim tarihi</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['deliveredAt'] ?? 'Henüz teslim edilmedi' }}</p>
            </div>
        @else
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Açılış</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['openedAt'] ?? '—' }}</p>
                @if(!empty($result['openedAtFull']) && ($result['openedAtFull'] ?? '') !== ($result['openedAt'] ?? ''))
                    <p class="text-[11px] text-neutral-400 mt-0.5">{{ $result['openedAtFull'] }}</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Servis termin</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['dueDate'] ?? '—' }}</p>
                @if(!empty($result['dueHint']))
                    <p class="text-[11px] mt-0.5 {{ str_contains($result['dueHint'], 'gecik') ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $result['dueHint'] }}</p>
                @elseif(empty($result['dueDate']))
                    <p class="text-[11px] text-neutral-400 mt-0.5">Henüz belirlenmedi</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Kapanış</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['closedAt'] ?? 'Henüz kapanmadı' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Garanti</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ !empty($result['underWarranty']) ? 'Garanti kapsamında' : 'Garanti dışı' }}</p>
                @if(!empty($result['workshopFinished']))
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">Atölye işi bitti{{ !empty($result['workshopFinishedAt']) ? ' · '.$result['workshopFinishedAt'] : '' }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- SSH özet: açıklama, ücret, problem ilerleme --}}
    @if(!$isSale && (!empty($result['description']) || !empty($result['serviceCharge']) || !empty($result['problemProgress']['total']) || !empty($result['problemSummary'])))
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/70 dark:bg-neutral-950/40 space-y-4">
        @if(!empty($result['description']))
        <div>
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-1.5">Servis açıklaması</h3>
            <p class="text-sm text-neutral-700 dark:text-neutral-300 whitespace-pre-wrap">{{ $result['description'] }}</p>
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @if(!empty($result['serviceCharge']))
            <div class="rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Servis ücreti</p>
                <p class="font-semibold mt-1 tabular-nums text-neutral-900 dark:text-neutral-100">{{ $result['serviceCharge']['label'] }}</p>
            </div>
            @endif
            @php $progress = $result['problemProgress'] ?? null; @endphp
            @if($progress && ($progress['total'] ?? 0) > 0)
            <div class="rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-neutral-500">Problem ilerleme</p>
                    <p class="text-xs font-medium tabular-nums text-neutral-700 dark:text-neutral-300">{{ $progress['fixed'] }}/{{ $progress['total'] }}</p>
                </div>
                <div class="mt-2 h-2 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(0, min(100, (int) ($progress['percent'] ?? 0))) }}%"></div>
                </div>
                <p class="text-[11px] text-neutral-500 mt-1.5">{{ $result['problemSummary'] ?? '' }}</p>
            </div>
            @elseif(!empty($result['problemSummary']))
            <div class="rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Problemler</p>
                <p class="font-medium mt-1 text-neutral-900 dark:text-neutral-100">{{ $result['problemSummary'] }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Ödeme özeti (satış) --}}
    @if($isSale && !empty($result['totals']))
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/70 dark:bg-neutral-950/40">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Ödeme durumu</h3>
        <div class="grid grid-cols-3 gap-3 text-sm">
            <div class="rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Sipariş tutarı</p>
                <p class="font-semibold mt-1 tabular-nums text-neutral-900 dark:text-neutral-100">{{ $result['totals']['grandTotalLabel'] }}</p>
            </div>
            <div class="rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Ödenen</p>
                <p class="font-semibold mt-1 tabular-nums text-emerald-600 dark:text-emerald-400">{{ $result['totals']['paidAmountLabel'] }}</p>
            </div>
            <div class="rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">{{ ($result['totals']['remaining'] ?? 0) < -0.005 ? 'Fazla ödeme' : 'Kalan' }}</p>
                <p class="font-semibold mt-1 tabular-nums {{ ($result['totals']['hasDebt'] ?? false) ? 'text-red-600 dark:text-red-400' : 'text-neutral-900 dark:text-neutral-100' }}">
                    {{ $result['totals']['remainingLabel'] }}
                </p>
            </div>
        </div>
        @if(!empty($result['payments']))
        <ul class="mt-3 space-y-1.5">
            @foreach($result['payments'] as $payment)
            <li class="flex items-center justify-between gap-3 text-sm px-1">
                <span class="text-neutral-600 dark:text-neutral-400">
                    {{ $payment['date'] ?? '—' }}
                    @if(!empty($payment['type']))
                        <span class="text-neutral-400">· {{ $payment['type'] }}</span>
                    @endif
                </span>
                <span class="font-medium text-emerald-700 dark:text-emerald-400 tabular-nums">{{ \App\Support\Money::format($payment['amount']) }} ₺</span>
            </li>
            @endforeach
        </ul>
        @elseif(($result['totals']['paidAmount'] ?? 0) <= 0)
        <p class="mt-3 text-xs text-neutral-500">Henüz ödeme kaydı yok.</p>
        @endif
    </div>
    @endif

    {{-- Sipariş kalemleri --}}
    @if($isSale && !empty($result['items']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Sipariş detayı</h3>
        <div class="overflow-x-auto -mx-1">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 border-b border-neutral-100 dark:border-neutral-800">
                        <th class="py-2 pr-3 font-medium">Ürün / açıklama</th>
                        <th class="py-2 px-2 font-medium text-center whitespace-nowrap">Adet</th>
                        <th class="py-2 pl-2 font-medium text-right whitespace-nowrap">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach($result['items'] as $item)
                    <tr>
                        <td class="py-2.5 pr-3">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $item['name'] }}</p>
                            @if(!empty($item['description']))
                            <p class="text-xs text-neutral-500 mt-0.5">{{ $item['description'] }}</p>
                            @endif
                        </td>
                        <td class="py-2.5 px-2 text-center tabular-nums text-neutral-700 dark:text-neutral-300">{{ $item['quantity'] }}</td>
                        <td class="py-2.5 pl-2 text-right tabular-nums font-medium text-neutral-900 dark:text-neutral-100 whitespace-nowrap">{{ \App\Support\Money::format($item['lineTotal']) }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

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

    {{-- SSH sevkiyat --}}
    @if(!$isSale && !empty($result['shipping']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Sevkiyat bilgisi</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @if(!empty($result['shipping']['company']))
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Firma</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['shipping']['company'] }}</p>
            </div>
            @endif
            @if(!empty($result['shipping']['driver']))
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Şoför</p>
                <p class="font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['shipping']['driver'] }}</p>
            </div>
            @endif
            @if(!empty($result['shipping']['phone']))
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Telefon</p>
                <a href="tel:{{ preg_replace('/\s+/', '', $result['shipping']['phone']) }}" class="font-medium mt-0.5 block text-emerald-600 dark:text-emerald-400 hover:underline">{{ $result['shipping']['phone'] }}</a>
            </div>
            @endif
            @if(!empty($result['shipping']['plate']))
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 px-3 py-3">
                <p class="text-xs text-neutral-500">Plaka</p>
                <p class="font-mono font-medium mt-0.5 text-neutral-900 dark:text-neutral-100">{{ $result['shipping']['plate'] }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- SSH görseller --}}
    @if(!$isSale && !empty($result['images']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Görseller</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach($result['images'] as $imageUrl)
            <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="block aspect-square rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800">
                <img src="{{ $imageUrl }}" alt="SSH görseli" class="w-full h-full object-cover" loading="lazy">
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($isSale && !empty($result['serviceTickets']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">SSH kayıtları</h3>
        <ul class="space-y-2">
            @foreach($result['serviceTickets'] as $ticket)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-neutral-100 dark:border-neutral-800 px-3 py-2.5">
                    <div>
                        <a href="{{ url('/takip/'.$ticket['ticketNumber']) }}" class="font-mono text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $ticket['ticketNumber'] }}</a>
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

    {{-- Bağlı sipariş (SSH) --}}
    @if(!$isSale && !empty($result['linkedSale']['saleNumber']))
    <div class="px-5 sm:px-6 py-5 border-b border-neutral-100 dark:border-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Bağlı sipariş</h3>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 px-3 py-3 mb-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <a href="{{ url('/takip/'.$result['linkedSale']['saleNumber']) }}" class="font-mono text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                    {{ $result['linkedSale']['saleNumber'] }}
                </a>
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                    {{ $result['linkedSale']['currentStage']['label'] ?? '—' }}
                </span>
            </div>
            <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs text-neutral-500">
                @if(!empty($result['linkedSale']['saleDate']))
                <p>Sipariş: <span class="text-neutral-800 dark:text-neutral-200">{{ $result['linkedSale']['saleDate'] }}</span></p>
                @endif
                @if(!empty($result['linkedSale']['dueDate']))
                <p>Termin: <span class="text-neutral-800 dark:text-neutral-200">{{ $result['linkedSale']['dueDate'] }}</span></p>
                @endif
                @if(!empty($result['linkedSale']['deliveredAt']))
                <p>Teslim: <span class="text-neutral-800 dark:text-neutral-200">{{ $result['linkedSale']['deliveredAt'] }}</span></p>
                @endif
            </div>
        </div>
        @if(!empty($result['linkedSale']['items']))
        <div class="overflow-x-auto -mx-1">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 border-b border-neutral-100 dark:border-neutral-800">
                        <th class="py-2 pr-3 font-medium">Ürün / açıklama</th>
                        <th class="py-2 px-2 font-medium text-center whitespace-nowrap">Adet</th>
                        <th class="py-2 pl-2 font-medium text-right whitespace-nowrap">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach($result['linkedSale']['items'] as $item)
                    <tr>
                        <td class="py-2.5 pr-3">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $item['name'] }}</p>
                            @if(!empty($item['description']))
                            <p class="text-xs text-neutral-500 mt-0.5">{{ $item['description'] }}</p>
                            @endif
                        </td>
                        <td class="py-2.5 px-2 text-center tabular-nums text-neutral-700 dark:text-neutral-300">{{ $item['quantity'] }}</td>
                        <td class="py-2.5 pl-2 text-right tabular-nums font-medium text-neutral-900 dark:text-neutral-100 whitespace-nowrap">{{ \App\Support\Money::format($item['lineTotal']) }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
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
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => ($entry['source'] ?? '') === 'payment',
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
