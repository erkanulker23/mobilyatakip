@php $print = $print ?? false; @endphp
<table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
    <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
        <tr>
            <th class="table-th">No</th>
            <th class="table-th">Müşteri</th>
            <th class="table-th">Şube</th>
            <th class="table-th">Satışı Yapan</th>
            <th class="table-th">Satış Tarihi</th>
            <th class="table-th">Termin</th>
            <th class="table-th text-right">Toplam</th>
            <th class="table-th text-right">Ödenen</th>
            <th class="table-th text-right">Kalan</th>
            <th class="table-th">Durum</th>
            @if(!$print)<th class="table-th"></th>@endif
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        @forelse($sales as $s)
        @php
            $remaining = \App\Support\CustomerBalance::saleRemaining($s);
            $paymentStatus = \App\Support\CustomerBalance::saleStatus($s);
            $deliveryStatus = \App\Support\SaleDelivery::currentStatus($s);
            $isDelivered = \App\Support\SaleDelivery::isDelivered($s);
            $showDeliveryStatus = !empty($filters['deliveryStatus']);
            $dueClass = !$isDelivered && $s->dueDate && $s->dueDate->isPast()
                ? 'text-red-600 font-medium'
                : ($s->dueDate && !$isDelivered && $s->dueDate->lte(now()->addDays(7))
                    ? 'text-amber-600 font-medium'
                    : 'text-slate-600');
        @endphp
        <tr class="{{ $print ? '' : 'hover:bg-slate-50' }}">
            <td class="table-td font-medium">{{ $s->saleNumber }}</td>
            <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
            <td class="table-td text-neutral-600">{{ $s->branch?->name ?? '—' }}</td>
            <td class="table-td text-neutral-600">{{ $s->personnel?->name ?? '—' }}</td>
            <td class="table-td">{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</td>
            <td class="table-td {{ $dueClass }}">{{ $s->dueDate?->format('d.m.Y') ?? '—' }}</td>
            <td class="table-td text-right font-medium">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right text-emerald-600">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right {{ $remaining > 0 ? 'text-red-600 font-medium' : ($remaining < -0.005 ? 'text-blue-600 font-medium' : 'text-slate-600') }}">{{ number_format($remaining, 0, ',', '.') }} ₺</td>
            <td class="table-td">
                @if($showDeliveryStatus)
                    {{ \App\Support\SaleDelivery::label($deliveryStatus) }}
                    @if($paymentStatus['key'] === 'borclu')
                        <span class="block text-xs text-red-600 mt-0.5">Borçlu</span>
                    @endif
                @else
                    {{ $paymentStatus['label'] ?? '—' }}
                    @if($deliveryStatus !== \App\Support\SaleDelivery::PENDING && $deliveryStatus !== \App\Support\SaleDelivery::DELIVERED)
                        <span class="block text-xs text-neutral-500 mt-0.5">{{ \App\Support\SaleDelivery::label($deliveryStatus) }}</span>
                    @endif
                @endif
            </td>
            @if(!$print)
            <td class="table-td">
                <a href="{{ route('sales.show', $s) }}" class="text-primary-600 hover:underline text-sm">Detay</a>
            </td>
            @endif
        </tr>
        @empty
        <tr><td colspan="{{ $print ? 10 : 11 }}" class="px-6 py-8 text-center text-neutral-500">Seçilen filtreye uygun satış bulunamadı.</td></tr>
        @endforelse
    </tbody>
    @if($sales->isNotEmpty())
    <tfoot class="{{ $print ? '' : 'bg-slate-50 border-t-2 border-neutral-200' }}">
        <tr class="font-semibold">
            <td class="table-td" colspan="6">
                Dönem toplamı ({{ $totals->count }} satış)
                @if(!$print)
                <span class="block text-xs font-normal text-neutral-500 mt-0.5">Satış tarihine göre filtrelenir · tablodaki tüm satırların toplamı</span>
                @endif
            </td>
            <td class="table-td text-right tabular-nums">{{ number_format($totals->grandTotal, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right text-emerald-600 tabular-nums">{{ number_format($totals->paidAmount, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right tabular-nums {{ ($totals->netRemaining ?? $totals->remaining) > 0 ? 'text-red-600' : (($totals->netRemaining ?? $totals->remaining) < -0.005 ? 'text-blue-600' : '') }}">{{ number_format($totals->netRemaining ?? $totals->remaining, 0, ',', '.') }} ₺</td>
            <td class="table-td" colspan="{{ $print ? 1 : 2 }}"></td>
        </tr>
    </tfoot>
    @endif
</table>
