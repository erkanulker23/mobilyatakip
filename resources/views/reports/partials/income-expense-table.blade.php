@php $print = $print ?? false; @endphp
<table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
    <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200 dark:bg-slate-800/50 dark:border-slate-700' }}">
        <tr>
            <th class="table-th">Kalem</th>
            <th class="table-th text-right">Tutar</th>
            @if(!$print)<th class="table-th text-right hidden md:table-cell">Adet</th>@endif
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
        <tr>
            <td class="table-td font-medium">Satış hasılatı (tahakkuk)</td>
            <td class="table-td text-right font-medium tabular-nums">{{ number_format($gelir, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td text-right text-neutral-500 hidden md:table-cell">{{ $salesCount ?? 0 }}</td>@endif
        </tr>
        <tr>
            <td class="table-td font-medium text-emerald-700 dark:text-emerald-400">Tahsilat (nakit giriş)</td>
            <td class="table-td text-right font-medium text-emerald-600 dark:text-emerald-400 tabular-nums">{{ number_format($tahsilat, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td text-right text-neutral-500 hidden md:table-cell">{{ $payments->count() }}</td>@endif
        </tr>
        <tr>
            <td class="table-td">Alış tutarı (maliyet)</td>
            <td class="table-td text-right tabular-nums">{{ number_format($alis ?? 0, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td text-right text-neutral-500 hidden md:table-cell">{{ $alisCount ?? 0 }}</td>@endif
        </tr>
        <tr>
            <td class="table-td">Gider</td>
            <td class="table-td text-right text-red-600 dark:text-red-400 tabular-nums">− {{ number_format($gider, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td text-right text-neutral-500 hidden md:table-cell">{{ $expenses->count() }}</td>@endif
        </tr>
        <tr>
            <td class="table-td">Tedarikçi ödemesi</td>
            <td class="table-td text-right text-red-600 dark:text-red-400 tabular-nums">− {{ number_format($tedarikciOdeme, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td text-right text-neutral-500 hidden md:table-cell">{{ $supplierPayments->count() }}</td>@endif
        </tr>
        <tr class="font-semibold {{ $print ? '' : 'bg-slate-50 dark:bg-slate-800/40' }}">
            <td class="table-td">Dönem operasyon sonucu (hasılat − alış − gider)</td>
            <td class="table-td text-right tabular-nums {{ ($donemKar ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($donemKar ?? 0, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td hidden md:table-cell"></td>@endif
        </tr>
        <tr class="font-semibold {{ $print ? '' : 'bg-indigo-50 dark:bg-indigo-900/20' }}">
            <td class="table-td">Net nakit akışı (tahsilat − gider − tedarikçi)</td>
            <td class="table-td text-right tabular-nums {{ ($netNakit ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($netNakit ?? 0, 0, ',', '.') }} ₺</td>
            @if(!$print)<td class="table-td hidden md:table-cell"></td>@endif
        </tr>
    </tbody>
</table>
