@php $print = $print ?? false; @endphp

@if($tahsilatByType->isNotEmpty() || $giderByCategory->isNotEmpty() || $tedarikciBySupplier->isNotEmpty())
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    @if($tahsilatByType->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="card-header">Tahsilat — ödeme tipi</div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead><tr class="border-b border-neutral-100 dark:border-slate-700"><th class="table-th">Tip</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">Adet</th></tr></thead>
                <tbody>
                    @foreach($tahsilatByType as $row)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50">
                        <td class="table-td">{{ $row->label }}</td>
                        <td class="table-td text-right font-medium text-emerald-600 dark:text-emerald-400 tabular-nums">{{ number_format($row->total, 0, ',', '.') }} ₺</td>
                        <td class="table-td text-right text-neutral-500">{{ $row->count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($giderByCategory->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="card-header">Gider — kategori</div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead><tr class="border-b border-neutral-100 dark:border-slate-700"><th class="table-th">Kategori</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">Adet</th></tr></thead>
                <tbody>
                    @foreach($giderByCategory as $row)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50">
                        <td class="table-td">{{ $row->category }}</td>
                        <td class="table-td text-right font-medium text-red-600 dark:text-red-400 tabular-nums">{{ number_format($row->total, 0, ',', '.') }} ₺</td>
                        <td class="table-td text-right text-neutral-500">{{ $row->count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($tedarikciBySupplier->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="card-header">Tedarikçi ödemesi — firma</div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead><tr class="border-b border-neutral-100 dark:border-slate-700"><th class="table-th">Tedarikçi</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">Adet</th></tr></thead>
                <tbody>
                    @foreach($tedarikciBySupplier as $row)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50">
                        <td class="table-td">{{ $row->name }}</td>
                        <td class="table-td text-right font-medium tabular-nums">{{ number_format($row->total, 0, ',', '.') }} ₺</td>
                        <td class="table-td text-right text-neutral-500">{{ $row->count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif

<div class="space-y-6">
    <div class="card overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span>Tahsilat hareketleri</span>
            <span class="text-xs font-normal text-neutral-500">{{ $payments->count() }} kayıt · {{ number_format($tahsilat, 0, ',', '.') }} ₺</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-slate-700">
                        <th class="table-th">Tarih</th>
                        <th class="table-th">Müşteri</th>
                        <th class="table-th">Sipariş</th>
                        <th class="table-th">Tip</th>
                        <th class="table-th">Kasa / Hesap</th>
                        <th class="table-th text-right">Tutar</th>
                        @if(!$print)<th class="table-th"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50 {{ $print ? '' : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/30' }}">
                        <td class="table-td whitespace-nowrap">{{ $p->paymentDate?->format('d.m.Y') }}</td>
                        <td class="table-td">{{ $p->customer?->name ?? '—' }}</td>
                        <td class="table-td font-mono text-sm">{{ $p->sale?->saleNumber ?? '—' }}</td>
                        <td class="table-td text-sm">{{ \App\Support\PaymentType::label($p->paymentType) }}</td>
                        <td class="table-td text-sm text-neutral-600 dark:text-slate-400">{{ $p->kasa?->name ?? '—' }}</td>
                        <td class="table-td text-right font-medium text-emerald-600 dark:text-emerald-400 tabular-nums">{{ number_format($p->amount, 0, ',', '.') }} ₺</td>
                        @if(!$print)
                        <td class="table-td text-right">
                            <a href="{{ route('customer-payments.show', $p) }}" class="text-sm text-emerald-600 hover:underline">Detay</a>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $print ? 6 : 7 }}" class="table-td text-center text-neutral-500 py-8">Bu dönemde tahsilat yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span>Gider hareketleri</span>
            <span class="text-xs font-normal text-neutral-500">{{ $expenses->count() }} kayıt · {{ number_format($gider, 0, ',', '.') }} ₺</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-slate-700">
                        <th class="table-th">Tarih</th>
                        <th class="table-th">Kategori</th>
                        <th class="table-th">Açıklama</th>
                        <th class="table-th">Kasa</th>
                        <th class="table-th text-right">Tutar</th>
                        @if(!$print)<th class="table-th"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50 {{ $print ? '' : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/30' }}">
                        <td class="table-td whitespace-nowrap">{{ $e->expenseDate?->format('d.m.Y') }}</td>
                        <td class="table-td">{{ $e->category ?: '—' }}</td>
                        <td class="table-td max-w-xs truncate" title="{{ $e->description }}">{{ $e->description ?: '—' }}</td>
                        <td class="table-td text-sm text-neutral-600 dark:text-slate-400">{{ $e->kasa?->name ?? '—' }}</td>
                        <td class="table-td text-right font-medium text-red-600 dark:text-red-400 tabular-nums">{{ number_format($e->amount, 0, ',', '.') }} ₺</td>
                        @if(!$print)
                        <td class="table-td text-right">
                            <a href="{{ route('expenses.show', $e) }}" class="text-sm text-emerald-600 hover:underline">Detay</a>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $print ? 5 : 6 }}" class="table-td text-center text-neutral-500 py-8">Bu dönemde gider yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span>Tedarikçi ödemeleri</span>
            <span class="text-xs font-normal text-neutral-500">{{ $supplierPayments->count() }} kayıt · {{ number_format($tedarikciOdeme, 0, ',', '.') }} ₺</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-slate-700">
                        <th class="table-th">Tarih</th>
                        <th class="table-th">Tedarikçi</th>
                        <th class="table-th">Alış</th>
                        <th class="table-th">Tip</th>
                        <th class="table-th">Kasa</th>
                        <th class="table-th text-right">Tutar</th>
                        @if(!$print)<th class="table-th"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplierPayments as $sp)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50 {{ $print ? '' : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/30' }}">
                        <td class="table-td whitespace-nowrap">{{ $sp->paymentDate?->format('d.m.Y') }}</td>
                        <td class="table-td">{{ $sp->supplier?->name ?? '—' }}</td>
                        <td class="table-td font-mono text-sm">{{ $sp->purchase?->purchaseNumber ?? '—' }}</td>
                        <td class="table-td text-sm">{{ \App\Support\PaymentType::label($sp->paymentType) }}</td>
                        <td class="table-td text-sm text-neutral-600 dark:text-slate-400">{{ $sp->kasa?->name ?? '—' }}</td>
                        <td class="table-td text-right font-medium tabular-nums">{{ number_format($sp->amount, 0, ',', '.') }} ₺</td>
                        @if(!$print)
                        <td class="table-td text-right">
                            @if($sp->purchase)
                            <a href="{{ route('purchases.show', $sp->purchase) }}" class="text-sm text-emerald-600 hover:underline">Alış</a>
                            @else
                            <span class="text-neutral-400">—</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $print ? 6 : 7 }}" class="table-td text-center text-neutral-500 py-8">Bu dönemde tedarikçi ödemesi yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
