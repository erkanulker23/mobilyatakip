@extends('layouts.app')
@section('title', 'Şube Raporu')
@section('content')
@php
    $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null);
    $queryKeys = ['from', 'to', 'year', 'month', 'period', 'branchId'];
    $reportChip = fn (array $params) => route('reports.branches', array_filter(array_merge(
        request()->only($queryKeys),
        $params
    ), fn ($v) => $v !== null && $v !== ''));
    $periodPresets = [
        ['label' => 'Bu ay', 'params' => ['period' => 'this_month', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'this_month' || ! request()->hasAny(['period', 'from', 'to', 'year', 'month'])],
        ['label' => 'Geçen ay', 'params' => ['period' => 'last_month', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'last_month'],
        ['label' => 'Bu yıl', 'params' => ['period' => 'this_year', 'from' => null, 'to' => null, 'year' => null, 'month' => null], 'active' => request('period') === 'this_year'],
    ];
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Şube Raporu</h1>
        <p class="page-desc">
            Sipariş ve SSH özeti — {{ $periodLabel }}
            @if(!empty($selectedLabel))
            <span class="text-neutral-500">· {{ $selectedLabel }}</span>
            @endif
        </p>
    </div>
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.branches.print',
        'printParams' => request()->query(),
    ])
</div>

<div class="card p-6 mb-6">
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400 mb-3">Dönem seçimi</p>
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @foreach($periodPresets as $preset)
        <a href="{{ $reportChip($preset['params']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $preset['active'] ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>

    <form method="get" action="{{ route('reports.branches') }}" class="flex flex-wrap gap-4 items-end">
        @include('reports.partials.date-filters', [
            'embedded' => true,
            'submitLabel' => 'Hesapla',
            'showMonth' => true,
            'dateFrom' => $from,
            'dateTo' => $to,
        ])
        <div class="min-w-[180px]">
            <label class="form-label">Şube</label>
            <select name="branchId" class="form-select">
                <option value="">Tüm şubeler</option>
                <option value="none" {{ ($selectedBranchId ?? '') === 'none' ? 'selected' : '' }}>Şube belirtilmemiş</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Hesapla</button>
            <a href="{{ route('reports.branches', ['period' => 'this_month']) }}" class="btn-secondary">Bu aya dön</a>
        </div>
    </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Sipariş</p>
        <p class="text-2xl font-semibold mt-1 tabular-nums">{{ number_format($summaryTotals['sale_count'], 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">₺{{ number_format($summaryTotals['grand_total'], 0, ',', '.') }} ciro</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Tahsil / kalan</p>
        <p class="text-xl font-semibold mt-1 tabular-nums text-emerald-600">₺{{ number_format($summaryTotals['paid_amount'], 0, ',', '.') }}</p>
        <p class="text-xs {{ $summaryTotals['remaining'] > 0 ? 'text-red-600' : 'text-neutral-400' }} mt-1">Kalan ₺{{ number_format($summaryTotals['remaining'], 0, ',', '.') }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">SSH</p>
        <p class="text-2xl font-semibold mt-1 tabular-nums">{{ number_format($summaryTotals['ticket_count'], 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">Dönemde açılan</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Açık SSH</p>
        <p class="text-2xl font-semibold mt-1 tabular-nums {{ $summaryTotals['open_count'] > 0 ? 'text-amber-600' : '' }}">{{ number_format($summaryTotals['open_count'], 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">{{ number_format($summaryTotals['done_count'], 0, ',', '.') }} tamamlanan</p>
    </div>
</div>

<div class="card overflow-hidden mb-6">
    <div class="card-header">Şube karşılaştırması</div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">Şube</th>
                    <th class="table-th text-right">Sipariş</th>
                    <th class="table-th text-right">Ciro</th>
                    <th class="table-th text-right">Tahsil</th>
                    <th class="table-th text-right">Kalan</th>
                    <th class="table-th text-right">SSH</th>
                    <th class="table-th text-right">Açık</th>
                    <th class="table-th text-right">Tamamlanan</th>
                    <th class="table-th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse($branchRows as $row)
                <tr class="hover:bg-neutral-50/80 dark:hover:bg-neutral-900/40 {{ (string) ($selectedBranchId ?? '') === (string) $row['id'] ? 'bg-teal-50/60 dark:bg-teal-950/20' : '' }} {{ empty($row['isActive']) ? 'opacity-70' : '' }}">
                    <td class="table-td font-medium">
                        {{ $row['name'] }}
                        @if(empty($row['isActive']))
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-500 dark:bg-neutral-800">Pasif</span>
                        @endif
                    </td>
                    <td class="table-td text-right tabular-nums">{{ number_format($row['sale_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">₺{{ number_format($row['grand_total'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums text-emerald-600">₺{{ number_format($row['paid_amount'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums {{ $row['remaining'] > 0 ? 'text-red-600' : '' }}">₺{{ number_format($row['remaining'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">{{ number_format($row['ticket_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums {{ $row['open_count'] > 0 ? 'text-amber-600 font-medium' : '' }}">{{ number_format($row['open_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">{{ number_format($row['done_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">
                        <a href="{{ $reportChip(['branchId' => $row['id']]) }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Detay</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-6 py-10 text-center text-neutral-500">Henüz şube veya bu döneme ait kayıt yok.</td></tr>
                @endforelse
            </tbody>
            @if($branchRows !== [])
            <tfoot class="bg-neutral-50 dark:bg-neutral-900/40 border-t-2 border-neutral-200 dark:border-neutral-700">
                <tr class="font-semibold">
                    <td class="table-td">Toplam</td>
                    <td class="table-td text-right tabular-nums">{{ number_format($branchTotals['sale_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">₺{{ number_format($branchTotals['grand_total'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums text-emerald-600">₺{{ number_format($branchTotals['paid_amount'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums {{ $branchTotals['remaining'] > 0 ? 'text-red-600' : '' }}">₺{{ number_format($branchTotals['remaining'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">{{ number_format($branchTotals['ticket_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">{{ number_format($branchTotals['open_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right tabular-nums">{{ number_format($branchTotals['done_count'], 0, ',', '.') }}</td>
                    <td class="table-td"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@if(filled($selectedBranchId))
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
            <span>Siparişler — {{ $selectedLabel }}</span>
            <a href="{{ route('sales.index', ['branchId' => $selectedBranchId === 'none' ? null : $selectedBranchId]) }}" class="text-xs font-normal text-emerald-600 hover:underline">Satış listesi</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="table-th">No</th>
                        <th class="table-th">Müşteri</th>
                        <th class="table-th">Tarih</th>
                        <th class="table-th text-right">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse($detailSales as $sale)
                    <tr>
                        <td class="table-td font-mono text-sm"><a href="{{ route('sales.show', $sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $sale->saleNumber }}</a></td>
                        <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                        <td class="table-td whitespace-nowrap">{{ $sale->saleDate?->format('d.m.Y') }}</td>
                        <td class="table-td text-right tabular-nums">₺{{ number_format($sale->grandTotal, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Bu dönemde sipariş yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
            <span>SSH kayıtları — {{ $selectedLabel }}</span>
            <a href="{{ route('service-tickets.index', ['branchId' => $selectedBranchId === 'none' ? null : $selectedBranchId]) }}" class="text-xs font-normal text-emerald-600 hover:underline">SSH listesi</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="table-th">No</th>
                        <th class="table-th">Müşteri</th>
                        <th class="table-th">Durum</th>
                        <th class="table-th">Açılış</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse($detailTickets as $ticket)
                    <tr>
                        <td class="table-td font-mono text-sm"><a href="{{ route('service-tickets.show', $ticket) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $ticket->ticketNumber }}</a></td>
                        <td class="table-td">{{ $ticket->customer?->name ?? '—' }}</td>
                        <td class="table-td"><span class="badge {{ \App\Support\ServiceTicketStatus::badgeClass($ticket->status) }}">{{ \App\Support\ServiceTicketStatus::label($ticket->status) }}</span></td>
                        <td class="table-td whitespace-nowrap">{{ $ticket->openedAt?->format('d.m.Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Bu dönemde SSH yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-report-year-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            var year = select.value;
            if (!year || !select.form) return;
            var fromInput = select.form.querySelector('[data-report-from]');
            var toInput = select.form.querySelector('[data-report-to]');
            var monthSelect = select.form.querySelector('[data-report-month-select]');
            if (monthSelect) monthSelect.value = '';
            if (fromInput) fromInput.value = year + '-01-01';
            if (toInput) toInput.value = year + '-12-31';
        });
    });
    document.querySelectorAll('[data-report-month-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            var month = select.value;
            var form = select.form;
            if (!form || !month) return;
            var yearSelect = form.querySelector('[data-report-year-select]');
            var year = yearSelect && yearSelect.value ? yearSelect.value : String(new Date().getFullYear());
            var padded = String(month).padStart(2, '0');
            var lastDay = new Date(Number(year), Number(month), 0).getDate();
            var fromInput = form.querySelector('[data-report-from]');
            var toInput = form.querySelector('[data-report-to]');
            if (fromInput) fromInput.value = year + '-' + padded + '-01';
            if (toInput) toInput.value = year + '-' + padded + '-' + String(lastDay).padStart(2, '0');
        });
    });
});
</script>
@endpush
@endsection
