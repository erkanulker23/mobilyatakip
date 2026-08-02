@php $print = $print ?? false; @endphp
<table class="print-table min-w-full {{ $print ? '' : 'w-full' }}">
    <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
        <tr>
            <th class="table-th">Kalem</th>
            <th class="table-th text-right">Tutar</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        <tr><td class="table-td">Satış hasılatı (dönem)</td><td class="table-td text-right font-medium">{{ number_format($gelir, 0, ',', '.') }} ₺</td></tr>
        <tr><td class="table-td">Tahsilat (dönem)</td><td class="table-td text-right font-medium">{{ number_format($tahsilat, 0, ',', '.') }} ₺</td></tr>
        <tr><td class="table-td">Gider</td><td class="table-td text-right font-medium">- {{ number_format($gider, 0, ',', '.') }} ₺</td></tr>
        <tr><td class="table-td">Tedarikçi ödemesi</td><td class="table-td text-right font-medium">- {{ number_format($tedarikciOdeme, 0, ',', '.') }} ₺</td></tr>
        <tr class="font-semibold {{ $print ? '' : 'bg-slate-50' }}"><td class="table-td">Net nakit etkisi (tahsilat − gider − tedarikçi ödemesi)</td><td class="table-td text-right">{{ number_format($tahsilat - $gider - $tedarikciOdeme, 0, ',', '.') }} ₺</td></tr>
    </tbody>
</table>
