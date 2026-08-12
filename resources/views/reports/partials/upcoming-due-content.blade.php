@php
    $print = $print ?? false;
    $forShipment = $forShipment ?? false;
    $hideCommercialData = $hideCommercialData ?? false;
    $showCustomerNames = $showCustomerNames ?? ! $hideCommercialData;
    $showSalesPersonnel = $showSalesPersonnel ?? ! $hideCommercialData;
    $today = now()->startOfDay();
    $workshopSalesCols = 5;
    if ($showCustomerNames) {
        $workshopSalesCols++;
    }
    if ($showSalesPersonnel) {
        $workshopSalesCols++;
    }
    $workshopSshCols = $showCustomerNames ? 6 : 5;
    $salesColspan = ($hideCommercialData ? $workshopSalesCols : 7) + ($forShipment || $hideCommercialData ? 0 : 2) + ($print ? 0 : 1);
    $sshColspan = ($hideCommercialData ? $workshopSshCols : 7) + ($print ? 0 : 1);
@endphp

<div class="{{ $print ? 'print-section-lg mb-4' : 'card overflow-hidden mb-6' }}">
    <div class="{{ $print ? 'mb-2' : 'px-6 py-4 border-b border-neutral-200 bg-amber-50' }}">
        <h2 class="{{ $print ? 'text-xs font-semibold uppercase text-neutral-700' : 'text-lg font-semibold text-slate-900' }}">Termin Süresi Yaklaşan Siparişler ({{ $upcomingSales->count() }})</h2>
    </div>
    <div class="{{ $print ? '' : 'overflow-x-auto' }}">
        <table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
            <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
                <tr>
                    <th class="table-th">Sipariş</th>
                    @if(!$hideCommercialData)
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Satışı Yapan</th>
                    <th class="table-th">İl</th>
                    <th class="table-th">İlçe</th>
                    @elseif($showCustomerNames || $showSalesPersonnel)
                    @if($showCustomerNames)
                    <th class="table-th">Müşteri</th>
                    @endif
                    @if($showSalesPersonnel)
                    <th class="table-th">Satışı Yapan</th>
                    @endif
                    @endif
                    <th class="table-th">Termin</th>
                    @if($hideCommercialData)
                    <th class="table-th">Durum</th>
                    @endif
                    <th class="table-th">Kalan Gün</th>
                    @if(!$forShipment && !$hideCommercialData)
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th text-right">Kalan Bakiye</th>
                    @endif
                    @if(!$print)<th class="table-th"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($upcomingSales as $s)
                @php
                    $daysLeft = $s->dueDate ? $today->diffInDays($s->dueDate, false) : null;
                    $rowClass = $daysLeft !== null && $daysLeft < 0 ? 'text-red-600' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-700' : 'text-slate-600');
                    $remaining = \App\Support\CustomerBalance::saleRemaining($s);
                    $orderStatus = \App\Support\SaleDelivery::currentStatus($s);
                    $inProduction = $orderStatus === \App\Support\SaleDelivery::IN_PRODUCTION;
                @endphp
                <tr>
                    <td class="table-td font-medium text-neutral-900">{{ $s->saleNumber }}</td>
                    @if(!$hideCommercialData)
                    <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-600">{{ $s->personnel?->name ?? '—' }}@if($s->branch)<span class="block text-xs text-emerald-700/80 dark:text-emerald-400/80">{{ $s->branch->name }}</span>@endif</td>
                    <td class="table-td">{{ $s->customer?->city?->name ?? '—' }}</td>
                    <td class="table-td">{{ $s->customer?->district?->name ?? '—' }}</td>
                    @elseif($showCustomerNames || $showSalesPersonnel)
                    @if($showCustomerNames)
                    <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
                    @endif
                    @if($showSalesPersonnel)
                    <td class="table-td text-neutral-600">{{ $s->personnel?->name ?? '—' }}</td>
                    @endif
                    @endif
                    <td class="table-td {{ $rowClass }}">{{ $s->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    @if($hideCommercialData)
                    <td class="table-td">
                        <span class="badge {{ $inProduction ? 'badge-blue' : 'badge-neutral' }}">{{ \App\Support\SaleDelivery::label($orderStatus) }}</span>
                    </td>
                    @endif
                    <td class="table-td {{ $rowClass }}">
                        @if($daysLeft === null)—
                        @elseif($daysLeft < 0){{ abs($daysLeft) }} gün gecikti
                        @elseif($daysLeft === 0)Bugün
                        @else{{ $daysLeft }} gün
                        @endif
                    </td>
                    @if(!$forShipment && !$hideCommercialData)
                    <td class="table-td text-right font-medium">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right font-medium {{ $remaining > 0 ? 'text-red-600' : 'text-neutral-400' }}">
                        @if($remaining > 0)
                            {{ number_format($remaining, 0, ',', '.') }} ₺
                        @else
                            —
                        @endif
                    </td>
                    @endif
                    @if(!$print)
                    <td class="table-td">
                        @if($hideCommercialData)
                            <a href="{{ route('workshop.show', ['sale' => $s, 'from' => 'termin', 'days' => $days ?? 14]) }}" class="text-primary-600 hover:underline text-sm">Detay</a>
                        @else
                        <a href="{{ route('sales.show', $s) }}" class="text-primary-600 hover:underline text-sm">Detay</a>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $salesColspan }}" class="px-6 py-8 text-center text-neutral-500">Yaklaşan terminli sipariş yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="{{ $print ? 'print-section-lg' : 'card overflow-hidden' }}">
    <div class="{{ $print ? 'mb-2' : 'px-6 py-4 border-b border-neutral-200 bg-slate-100' }}">
        <h2 class="{{ $print ? 'text-xs font-semibold uppercase text-neutral-700' : 'text-lg font-semibold text-slate-900' }}">SSH Termin Süresi Yaklaşan Formlar ({{ $upcomingServiceTickets->count() }})</h2>
    </div>
    <div class="{{ $print ? '' : 'overflow-x-auto' }}">
        <table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
            <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
                <tr>
                    <th class="table-th">Form No</th>
                    @if(!$hideCommercialData)
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">İl</th>
                    <th class="table-th">İlçe</th>
                    @elseif($showCustomerNames)
                    <th class="table-th">Müşteri</th>
                    @endif
                    <th class="table-th">Sipariş</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th">Durum</th>
                    @if(!$print)<th class="table-th"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($upcomingServiceTickets as $t)
                @php $daysLeft = $t->dueDate ? $today->diffInDays($t->dueDate, false) : null; @endphp
                <tr>
                    <td class="table-td font-medium">{{ $t->ticketNumber }}@if($t->branch)<span class="block text-xs text-emerald-700/80 dark:text-emerald-400/80 font-normal">{{ $t->branch->name }}</span>@endif</td>
                    @if(!$hideCommercialData)
                    <td class="table-td">{{ $t->customer?->name ?? '—' }}</td>
                    <td class="table-td">{{ $t->customer?->city?->name ?? '—' }}</td>
                    <td class="table-td">{{ $t->customer?->district?->name ?? '—' }}</td>
                    @elseif($showCustomerNames)
                    <td class="table-td">{{ $t->customer?->name ?? '—' }}</td>
                    @endif
                    <td class="table-td">{{ $t->sale?->saleNumber ?? '—' }}</td>
                    <td class="table-td">{{ $t->dueDate?->format('d.m.Y') ?? '—' }}@if($daysLeft !== null && $daysLeft < 0) <span class="text-red-600">(gecikti)</span>@endif</td>
                    <td class="table-td">{{ ucfirst($t->status ?? '—') }}</td>
                    @if(!$print)
                    <td class="table-td"><a href="{{ route('service-tickets.show', $t) }}" class="text-primary-600 hover:underline text-sm">Detay</a></td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $sshColspan }}" class="px-6 py-8 text-center text-neutral-500">Yaklaşan terminli SSH formu yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
