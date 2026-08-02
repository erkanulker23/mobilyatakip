@php $print = $print ?? false; @endphp
<div class="grid grid-cols-1 {{ $print ? '' : 'lg:grid-cols-2' }} gap-6">
    <div class="{{ $print ? 'print-section-lg mb-4' : 'card overflow-hidden' }}">
        <div class="{{ $print ? 'mb-2' : 'px-6 py-4 border-b border-neutral-200 bg-green-50' }}">
            <h2 class="{{ $print ? 'text-xs font-semibold uppercase' : 'text-lg font-semibold text-slate-900' }}">Satış KDV Dağılımı</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="print-table min-w-full w-full">
                <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
                    <tr>
                        <th class="table-th">KDV %</th>
                        <th class="table-th text-right">Net</th>
                        <th class="table-th text-right">KDV</th>
                        <th class="table-th text-right">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($salesByRate as $rate => $row)
                    <tr><td class="table-td font-medium">%{{ number_format($rate, 0) }}</td><td class="table-td text-right">{{ number_format($row['net'], 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format($row['kdv'], 0, ',', '.') }} ₺</td><td class="table-td text-right font-medium">{{ number_format($row['total'], 0, ',', '.') }} ₺</td></tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Bu dönemde satış yok.</td></tr>
                    @endforelse
                </tbody>
                @if(count($salesByRate) > 0)
                <tfoot class="{{ $print ? '' : 'bg-slate-50 border-t-2' }}"><tr class="font-semibold"><td class="table-td">Toplam</td><td class="table-td text-right">{{ number_format(collect($salesByRate)->sum('net'), 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format(collect($salesByRate)->sum('kdv'), 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format(collect($salesByRate)->sum('total'), 0, ',', '.') }} ₺</td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="{{ $print ? 'print-section-lg mb-4' : 'card overflow-hidden' }}">
        <div class="{{ $print ? 'mb-2' : 'px-6 py-4 border-b border-neutral-200 bg-amber-50' }}">
            <h2 class="{{ $print ? 'text-xs font-semibold uppercase' : 'text-lg font-semibold text-slate-900' }}">Alış KDV Dağılımı</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="print-table min-w-full w-full">
                <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
                    <tr><th class="table-th">KDV %</th><th class="table-th text-right">Net</th><th class="table-th text-right">KDV</th><th class="table-th text-right">Toplam</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($purchasesByRate as $rate => $row)
                    <tr><td class="table-td font-medium">%{{ number_format($rate, 0) }}</td><td class="table-td text-right">{{ number_format($row['net'], 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format($row['kdv'], 0, ',', '.') }} ₺</td><td class="table-td text-right font-medium">{{ number_format($row['total'], 0, ',', '.') }} ₺</td></tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Bu dönemde alış yok.</td></tr>
                    @endforelse
                </tbody>
                @if(count($purchasesByRate) > 0)
                <tfoot class="{{ $print ? '' : 'bg-slate-50 border-t-2' }}"><tr class="font-semibold"><td class="table-td">Toplam</td><td class="table-td text-right">{{ number_format(collect($purchasesByRate)->sum('net'), 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format(collect($purchasesByRate)->sum('kdv'), 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format(collect($purchasesByRate)->sum('total'), 0, ',', '.') }} ₺</td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="{{ $print ? 'print-section-lg' : 'card overflow-hidden' }}">
        <div class="{{ $print ? 'mb-2' : 'px-6 py-4 border-b border-neutral-200 bg-slate-100' }}">
            <h2 class="{{ $print ? 'text-xs font-semibold uppercase' : 'text-lg font-semibold text-slate-900' }}">Gider KDV Dağılımı</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="print-table min-w-full w-full">
                <thead class="{{ $print ? '' : 'bg-slate-50 border-b border-neutral-200' }}">
                    <tr><th class="table-th">KDV %</th><th class="table-th text-right">Net</th><th class="table-th text-right">KDV</th><th class="table-th text-right">Toplam</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($expensesByRate as $rate => $row)
                    <tr><td class="table-td font-medium">%{{ number_format($rate, 0) }}</td><td class="table-td text-right">{{ number_format($row['net'], 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format($row['kdv'], 0, ',', '.') }} ₺</td><td class="table-td text-right font-medium">{{ number_format($row['total'], 0, ',', '.') }} ₺</td></tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Bu dönemde KDV’li gider yok.</td></tr>
                    @endforelse
                </tbody>
                @if(count($expensesByRate) > 0)
                <tfoot class="{{ $print ? '' : 'bg-slate-50 border-t-2' }}"><tr class="font-semibold"><td class="table-td">Toplam</td><td class="table-td text-right">{{ number_format(collect($expensesByRate)->sum('net'), 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format(collect($expensesByRate)->sum('kdv'), 0, ',', '.') }} ₺</td><td class="table-td text-right">{{ number_format(collect($expensesByRate)->sum('total'), 0, ',', '.') }} ₺</td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@if(count($salesByRate) > 0 || count($purchasesByRate) > 0 || count($expensesByRate) > 0)
@php $giderKdv = collect($expensesByRate)->sum('kdv'); @endphp
<div class="{{ $print ? 'print-section mt-4' : 'mt-6 card p-6' }}">
    <h3 class="{{ $print ? 'text-xs font-semibold uppercase mb-2' : 'text-lg font-semibold text-slate-900 mb-4' }}">Özet</h3>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-neutral-500">Satış KDV Toplamı</dt><dd class="font-bold text-green-600">{{ number_format(collect($salesByRate)->sum('kdv'), 0, ',', '.') }} ₺</dd></div>
        <div><dt class="text-neutral-500">Alış KDV Toplamı</dt><dd class="font-bold text-amber-600">{{ number_format(collect($purchasesByRate)->sum('kdv'), 0, ',', '.') }} ₺</dd></div>
        <div><dt class="text-neutral-500">Gider KDV Toplamı</dt><dd class="font-bold">{{ number_format($giderKdv, 0, ',', '.') }} ₺</dd></div>
        <div><dt class="text-neutral-500">Ödenecek KDV</dt><dd class="font-bold text-slate-900">{{ number_format(collect($salesByRate)->sum('kdv') - collect($purchasesByRate)->sum('kdv') - $giderKdv, 0, ',', '.') }} ₺</dd></div>
    </dl>
</div>
@endif
