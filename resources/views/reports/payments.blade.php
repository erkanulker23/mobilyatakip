@extends('layouts.app')
@section('title', 'Ödemeler Raporu')
@section('content')
@php
    $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null);
    $periodPresets = [
        ['label' => 'Bu ay', 'params' => ['period' => 'this_month'], 'active' => request('period') === 'this_month' || (! request()->hasAny(['period', 'from', 'to', 'year', 'month']))],
        ['label' => 'Geçen ay', 'params' => ['period' => 'last_month'], 'active' => request('period') === 'last_month'],
        ['label' => 'Bu yıl', 'params' => ['period' => 'this_year'], 'active' => request('period') === 'this_year'],
        ['label' => 'Geçen yıl', 'params' => ['period' => 'last_year'], 'active' => request('period') === 'last_year'],
    ];
    $chip = fn (array $params) => route('reports.payments', array_filter(array_merge(
        request()->only(['from', 'to', 'year', 'month', 'period', 'kasaId', 'type', 'search']),
        $params
    ), fn ($v) => $v !== null && $v !== ''));
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Ödemeler Raporu</h1>
        <p class="page-desc">Kasadan çıkan tüm hareketler — {{ $periodLabel }}</p>
    </div>
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.payments.print',
        'printParams' => request()->query(),
    ])
</div>

<div class="card p-5 mb-6">
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs text-neutral-400 mr-1">Dönem</span>
        @foreach($periodPresets as $preset)
        <a href="{{ $chip(array_merge($preset['params'], ['from' => null, 'to' => null, 'year' => null, 'month' => null])) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $preset['active'] ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="text-xs text-neutral-400 mr-1">Tür</span>
        @foreach($typeOptions as $value => $label)
        @php $isActive = ((string) ($type ?? '') === (string) $value); @endphp
        <a href="{{ $chip(['type' => $value !== '' ? $value : null]) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <form method="get" action="{{ route('reports.payments') }}" class="flex flex-wrap gap-4 items-end">
        <input type="hidden" name="period" value="{{ request('period') }}">
        <input type="hidden" name="type" value="{{ $type ?? '' }}">
        @include('reports.partials.date-filters', [
            'embedded' => true,
            'showMonth' => true,
            'dateFrom' => $from,
            'dateTo' => $to,
        ])
        <div class="min-w-[180px]">
            <label class="form-label">Kasa</label>
            <select name="kasaId" class="form-select">
                <option value="">Tüm kasalar</option>
                @foreach($kasalar as $k)
                <option value="{{ $k->id }}" {{ request('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Açıklama…">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('reports.payments', ['period' => 'this_month']) }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <div class="card p-4 sm:col-span-2 xl:col-span-1">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam ödeme</p>
        <p class="text-2xl font-semibold text-rose-600 mt-1 tabular-nums">{{ number_format($total, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $count }} hareket</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Gider</p>
        <p class="text-xl font-semibold text-rose-600 mt-1 tabular-nums">{{ number_format($byType['expense'], 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Tedarikçi</p>
        <p class="text-xl font-semibold text-amber-600 mt-1 tabular-nums">{{ number_format($byType['supplier_payment'], 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Avans</p>
        <p class="text-xl font-semibold text-amber-600 mt-1 tabular-nums">{{ number_format($byType['personnel_advance'], 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Nakliye</p>
        <p class="text-xl font-semibold text-orange-600 mt-1 tabular-nums">{{ number_format($byType['shipping_company_payment'], 0, ',', '.') }} ₺</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header flex flex-wrap items-center justify-between gap-2">
        <span>Ödeme listesi</span>
        <span class="text-xs font-normal text-neutral-500">Kasadan çıkan · virman hariç</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-neutral-200 dark:border-slate-700">
                <tr>
                    <th class="table-th">Tarih</th>
                    <th class="table-th">Tür</th>
                    <th class="table-th">Açıklama</th>
                    <th class="table-th">Kasa</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th text-right">Detay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($rows as $row)
                @php $h = $row->movement; $badge = $row->badge; @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <td class="table-td whitespace-nowrap">{{ $h->movementDate?->format('d.m.Y') }}</td>
                    <td class="table-td">
                        <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium {{ \App\Support\KasaMovement::toneClasses($badge['tone']) }}">
                            {{ $badge['label'] }}
                        </span>
                    </td>
                    <td class="table-td max-w-md">
                        <span class="text-sm text-neutral-800 dark:text-neutral-200">{{ $h->description ?: '—' }}</span>
                    </td>
                    <td class="table-td text-neutral-600 dark:text-neutral-400">{{ $h->kasa?->name ?? '—' }}</td>
                    <td class="table-td text-right font-semibold text-rose-600 tabular-nums whitespace-nowrap">{{ number_format($row->amount, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right">
                        @if($row->detail)
                        <a href="{{ $row->detail['url'] }}" class="text-sm text-emerald-600 hover:underline">{{ $row->detail['label'] }}</a>
                        @else
                        <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-neutral-500">Bu dönemde kasadan çıkan ödeme yok.</td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot class="border-t border-neutral-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40">
                <tr>
                    <td colspan="4" class="table-td font-semibold">Toplam</td>
                    <td class="table-td text-right font-semibold text-rose-600 tabular-nums">{{ number_format($total, 0, ',', '.') }} ₺</td>
                    <td class="table-td"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
