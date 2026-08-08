@php
    $personnel = $personnelDashboard['personnel'] ?? null;
    $stats = $personnelDashboard['stats'] ?? [];
    $recentSales = $personnelDashboard['recentSales'] ?? collect();
    $recentPayments = $personnelDashboard['recentPayments'] ?? collect();
    $upcomingDueSales = $personnelDashboard['upcomingDueSales'] ?? collect();
    $upcomingDueCount = $personnelDashboard['upcomingDueCount'] ?? 0;
    $paymentLabels = [
        'nakit' => 'Nakit',
        'havale' => 'Havale',
        'kredi_karti' => 'Kredi Kartı',
        'diger' => 'Diğer',
    ];
@endphp

@if($personnel)
<div class="space-y-5 mb-8">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="card p-4 border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/40 dark:bg-emerald-950/20">
            <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">Bu ay sipariş</p>
            <p class="text-2xl sm:text-3xl font-semibold text-emerald-700 dark:text-emerald-400 mt-1 tabular-nums">{{ $stats['monthCount'] ?? 0 }}</p>
            <p class="text-xs text-neutral-500 mt-1">{{ $stats['activeCount'] ?? 0 }} aktif sipariş</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Bu ay ciro</p>
            <p class="text-2xl sm:text-3xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1 tabular-nums">₺{{ number_format($stats['monthTotal'] ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-neutral-500 mt-1">İptal edilmeyen satışlar</p>
        </div>
        <div class="card p-4 border-sky-200 dark:border-sky-800/60 bg-sky-50/40 dark:bg-sky-950/20">
            <p class="text-xs font-medium text-sky-800 dark:text-sky-300 uppercase tracking-wide">Bu ay tahsilat</p>
            <p class="text-2xl sm:text-3xl font-semibold text-sky-700 dark:text-sky-400 mt-1 tabular-nums">₺{{ number_format($stats['monthCollected'] ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-neutral-500 mt-1">Siparişlerden alınan</p>
        </div>
        <div class="card p-4 {{ ($stats['totalReceivable'] ?? 0) > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/60' : '' }}">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Alınacak</p>
            <p class="text-2xl sm:text-3xl font-semibold {{ ($stats['totalReceivable'] ?? 0) > 0 ? 'text-amber-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1 tabular-nums">₺{{ number_format($stats['totalReceivable'] ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-neutral-500 mt-1">Bekleyen tahsilat</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Son siparişlerim</span>
                <a href="{{ route('personnel.show', $personnel) }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">Tümü →</a>
            </div>
            @if($recentSales->isEmpty())
                <div class="p-10 text-center text-sm text-neutral-500">Henüz size atanmış sipariş yok.</div>
            @else
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach($recentSales as $sale)
                    @php
                        $saleStatus = \App\Support\CustomerBalance::saleStatus($sale);
                    @endphp
                    <a href="{{ route('sales.show', $sale) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $sale->saleNumber }}</p>
                            <p class="text-sm text-neutral-500 truncate">{{ $sale->customer?->name ?? '—' }}</p>
                            <p class="text-xs text-neutral-400 mt-0.5">{{ $sale->saleDate?->format('d.m.Y') ?? '—' }}@if($sale->dueDate) · Termin {{ $sale->dueDate->format('d.m.Y') }}@endif</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-medium tabular-nums">₺{{ number_format($sale->grandTotal ?? 0, 0, ',', '.') }}</p>
                            @include('partials.payment-status-badge', ['status' => $saleStatus])
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Son tahsilatlarım</span>
                <a href="{{ route('personnel.show', $personnel) }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">Detay →</a>
            </div>
            @if($recentPayments->isEmpty())
                <div class="p-10 text-center text-sm text-neutral-500">Siparişlerinize bağlı tahsilat kaydı yok.</div>
            @else
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach($recentPayments as $payment)
                    <a href="{{ route('sales.show', $payment->sale) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-emerald-700 dark:text-emerald-400 tabular-nums">₺{{ number_format($payment->amount ?? 0, 0, ',', '.') }}</p>
                            <p class="text-sm text-neutral-500 truncate">{{ $payment->sale?->customer?->name ?? $payment->customer?->name ?? '—' }}</p>
                            <p class="text-xs text-neutral-400 mt-0.5">
                                {{ $payment->paymentDate?->format('d.m.Y') ?? '—' }}
                                · {{ $paymentLabels[$payment->paymentType ?? ''] ?? ucfirst($payment->paymentType ?? '—') }}
                                @if($payment->sale?->saleNumber)
                                    · {{ $payment->sale->saleNumber }}
                                @endif
                            </p>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <span>Termini yaklaşan siparişlerim</span>
                <p class="text-xs font-normal text-neutral-500 mt-0.5">Önümüzdeki 14 gün içinde termin tarihi gelen siparişler</p>
            </div>
            @if($upcomingDueCount > 0)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 shrink-0">{{ $upcomingDueCount }} sipariş</span>
            @endif
        </div>
        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
            @if($upcomingDueSales->isEmpty())
                <div class="p-10 text-center text-sm text-neutral-500">Yaklaşan termin tarihi olan siparişiniz yok.</div>
            @else
                @foreach($upcomingDueSales as $sale)
                @php
                    $daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null;
                    if ($daysLeft === null) {
                        $daysLabel = '—';
                        $daysClass = 'text-neutral-600 bg-neutral-100 dark:bg-neutral-800';
                    } elseif ($daysLeft < 0) {
                        $daysLabel = abs($daysLeft) . ' gün gecikti';
                        $daysClass = 'text-red-600 bg-red-50 dark:bg-red-950/40';
                    } elseif ($daysLeft === 0) {
                        $daysLabel = 'Bugün';
                        $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                    } elseif ($daysLeft <= ($terminAlertDays ?? 3)) {
                        $daysLabel = $daysLeft . ' gün';
                        $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                    } else {
                        $daysLabel = $daysLeft . ' gün';
                        $daysClass = 'text-neutral-600 bg-neutral-100 dark:bg-neutral-800';
                    }
                @endphp
                <a href="{{ route('sales.show', $sale) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $sale->saleNumber }}</p>
                        <p class="text-sm text-neutral-500 truncate">{{ $sale->customer?->name ?? '—' }}</p>
                        @if($sale->customer?->phone)
                            <p class="text-xs text-neutral-400 mt-0.5">{{ $sale->customer->phone }}</p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $sale->dueDate?->format('d.m.Y') }}</p>
                        <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-md {{ $daysClass }}">{{ $daysLabel }}</span>
                    </div>
                </a>
                @endforeach
                @if($upcomingDueCount > $upcomingDueSales->count())
                    <div class="p-3 text-center border-t border-neutral-100 dark:border-neutral-800">
                        <a href="{{ route('personnel.show', $personnel) }}" class="text-sm text-neutral-600 hover:text-neutral-900 dark:hover:text-neutral-100">+{{ $upcomingDueCount - $upcomingDueSales->count() }} sipariş daha →</a>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endif
