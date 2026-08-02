@php
    $print = $print ?? false;
    $forShipment = $forShipment ?? false;
    $today = now()->startOfDay();
    $salesColspan = 6 + ($forShipment ? 0 : 1) + ($print ? 0 : 1);
    $sshColspan = 7 + ($print ? 0 : 1);
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
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">İl</th>
                    <th class="table-th">İlçe</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th">Kalan Gün</th>
                    @if(!$forShipment)
                    <th class="table-th text-right">Tutar</th>
                    @endif
                    @if(!$print)<th class="table-th"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($upcomingSales as $s)
                @php
                    $daysLeft = $s->dueDate ? $today->diffInDays($s->dueDate, false) : null;
                    $rowClass = $daysLeft !== null && $daysLeft < 0 ? 'text-red-600' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-700' : 'text-slate-600');
                @endphp
                <tr>
                    <td class="table-td font-medium text-neutral-900">{{ $s->saleNumber }}</td>
                    <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
                    <td class="table-td">{{ $s->customer?->city?->name ?? '—' }}</td>
                    <td class="table-td">{{ $s->customer?->district?->name ?? '—' }}</td>
                    <td class="table-td {{ $rowClass }}">{{ $s->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td {{ $rowClass }}">
                        @if($daysLeft === null)—
                        @elseif($daysLeft < 0){{ abs($daysLeft) }} gün gecikti
                        @elseif($daysLeft === 0)Bugün
                        @else{{ $daysLeft }} gün
                        @endif
                    </td>
                    @if(!$forShipment)
                    <td class="table-td text-right font-medium">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    @endif
                    @if(!$print)
                    <td class="table-td"><a href="{{ route('sales.show', $s) }}" class="text-primary-600 hover:underline text-sm">Detay</a></td>
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
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">İl</th>
                    <th class="table-th">İlçe</th>
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
                    <td class="table-td font-medium">{{ $t->ticketNumber }}</td>
                    <td class="table-td">{{ $t->customer?->name ?? '—' }}</td>
                    <td class="table-td">{{ $t->customer?->city?->name ?? '—' }}</td>
                    <td class="table-td">{{ $t->customer?->district?->name ?? '—' }}</td>
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
