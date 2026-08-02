@php $print = $print ?? false; @endphp
<table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
    <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
        <tr>
            <th class="table-th">No</th>
            <th class="table-th">Müşteri</th>
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
            $status = \App\Support\CustomerBalance::saleStatus($s);
            $dueClass = $s->dueDate && $s->dueDate->isPast() ? 'text-red-600 font-medium' : ($s->dueDate && $s->dueDate->lte(now()->addDays(7)) ? 'text-amber-600 font-medium' : 'text-slate-600');
        @endphp
        <tr class="{{ $print ? '' : 'hover:bg-slate-50' }}">
            <td class="table-td font-medium">{{ $s->saleNumber }}</td>
            <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
            <td class="table-td">{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</td>
            <td class="table-td {{ $dueClass }}">{{ $s->dueDate?->format('d.m.Y') ?? '—' }}</td>
            <td class="table-td text-right font-medium">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right text-emerald-600">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right {{ $remaining > 0 ? 'text-red-600 font-medium' : 'text-slate-600' }}">{{ number_format($remaining, 0, ',', '.') }} ₺</td>
            <td class="table-td">{{ $status['label'] ?? '—' }}</td>
            @if(!$print)
            <td class="table-td">
                <a href="{{ route('sales.show', $s) }}" class="text-primary-600 hover:underline text-sm">Detay</a>
            </td>
            @endif
        </tr>
        @empty
        <tr><td colspan="{{ $print ? 8 : 9 }}" class="px-6 py-8 text-center text-neutral-500">Seçilen dönemde satış yok.</td></tr>
        @endforelse
    </tbody>
    @if($sales->isNotEmpty())
    <tfoot class="{{ $print ? '' : 'bg-slate-50 border-t-2 border-neutral-200' }}">
        <tr class="font-semibold">
            <td class="table-td" colspan="4">Toplam ({{ $totals->count }} satış)</td>
            <td class="table-td text-right">{{ number_format($totals->grandTotal, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right text-emerald-600">{{ number_format($totals->paidAmount, 0, ',', '.') }} ₺</td>
            <td class="table-td text-right">{{ number_format($totals->remaining, 0, ',', '.') }} ₺</td>
            <td class="table-td" colspan="{{ $print ? 1 : 2 }}"></td>
        </tr>
    </tfoot>
    @endif
</table>
