@php
    $customer = $customerLedger['customer'] ?? null;
    $filteredRows = $customerLedger['filteredRows'] ?? collect();
    $openingBalance = $customerLedger['openingBalance'] ?? 0;
    $from = $customerLedger['from'] ?? null;
    $to = $customerLedger['to'] ?? null;
    $totalSales = $customerLedger['totalSales'] ?? 0;
    $totalPaid = $customerLedger['totalPaid'] ?? 0;
    $customerBalance = $customerLedger['customerBalance'] ?? null;
    $highlightRefId = $highlightRefId ?? null;
    $highlightType = $highlightType ?? null;
    $panelId = $panelId ?? 'musteri-ekstresi';
@endphp

@if($customer && $customerBalance)
<div id="{{ $panelId }}" class="mt-8 card overflow-hidden scroll-mt-24">
    <div class="card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Müşteri Ekstresi</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $customer->name }} — borç, tahsilat ve bakiye hareketleri</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('reports.customer-ledger-detail', $customer) }}" class="btn-secondary text-sm">Detaylı Ekstre</a>
            <a href="{{ route('reports.customer-ledger-detail.print', $customer) }}" target="_blank" rel="noopener" class="btn-secondary text-sm">Yazdır</a>
            <a href="{{ route('customers.print', $customer) }}" target="_blank" rel="noopener" class="btn-secondary text-sm">Müşteri Özeti</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-px bg-neutral-200 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-800">
        <div class="bg-white dark:bg-neutral-900 p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam satış</p>
            <p class="text-xl font-semibold tabular-nums text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($totalSales, 0, ',', '.') }} ₺</p>
        </div>
        <div class="bg-white dark:bg-neutral-900 p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam tahsilat</p>
            <p class="text-xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($totalPaid, 0, ',', '.') }} ₺</p>
        </div>
        <div class="bg-white dark:bg-neutral-900 p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Cari durum</p>
            <div class="mt-1">
                @include('partials.payment-status-badge', ['status' => ['key' => $customerBalance['key'], 'label' => $customerBalance['label']]])
            </div>
            <p class="text-lg font-semibold tabular-nums mt-2 {{ $customerBalance['key'] === 'borclu' ? 'text-red-600 dark:text-red-400' : ($customerBalance['key'] === 'alacakli' ? 'text-blue-600 dark:text-blue-400' : 'text-neutral-900 dark:text-neutral-100') }}">
                @if(in_array($customerBalance['key'], ['borclu', 'alacakli'], true))
                    {{ number_format($customerBalance['amount'], 0, ',', '.') }} ₺
                @elseif($customerBalance['key'] === 'siparis_yok')
                    —
                @else
                    Borcu yok
                @endif
            </p>
        </div>
    </div>

    <div class="overflow-x-auto">
        @include('reports.partials.ledger-detail-table', compact('filteredRows', 'from', 'to', 'openingBalance', 'highlightRefId', 'highlightType'))
    </div>
</div>
@endif
