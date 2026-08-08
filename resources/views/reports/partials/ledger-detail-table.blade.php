@php $print = $print ?? false; @endphp
<table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
    <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
        <tr>
            <th class="table-th">Tarih</th>
            <th class="table-th">Açıklama</th>
            <th class="table-th text-right">Borç</th>
            <th class="table-th text-right">Alacak</th>
            <th class="table-th text-right">Bakiye</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        @if(($from || $to) && $openingBalance != 0)
        <tr class="{{ $print ? '' : 'bg-amber-50' }}">
            <td class="table-td">—</td>
            <td class="table-td font-medium">Açılış bakiyesi</td>
            <td class="table-td text-right">—</td>
            <td class="table-td text-right">—</td>
            <td class="table-td text-right font-medium">{{ number_format($openingBalance, 0, ',', '.') }} ₺</td>
        </tr>
        @endif
        @forelse($filteredRows as $r)
        @php
            $isHighlighted = ($highlightRefId ?? null) && ($highlightType ?? null)
                && ($r->refId ?? null) === $highlightRefId
                && ($r->type ?? null) === $highlightType;
        @endphp
        <tr class="{{ $isHighlighted ? 'bg-emerald-50 dark:bg-emerald-950/20' : '' }}">
            <td class="table-td">{{ $r->date->format('d.m.Y') }}</td>
            <td class="table-td">
                @if(!$print && !empty($r->refRoute) && !empty($r->refId))
                <a href="{{ route($r->refRoute, $r->refId) }}" class="font-medium text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300">
                    {{ $r->aciklama }}
                </a>
                @else
                {{ $r->aciklama }}
                @endif
            </td>
            <td class="table-td text-right">{{ $r->borc > 0 ? number_format($r->borc, 0, ',', '.') . ' ₺' : '—' }}</td>
            <td class="table-td text-right">{{ $r->alacak > 0 ? number_format($r->alacak, 0, ',', '.') . ' ₺' : '—' }}</td>
            <td class="table-td text-right font-medium">{{ number_format($r->bakiye, 0, ',', '.') }} ₺</td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-6 py-8 text-center text-neutral-500">Bu tarih aralığında hareket yok.</td></tr>
        @endforelse
    </tbody>
</table>
