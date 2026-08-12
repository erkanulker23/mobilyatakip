@extends('layouts.print')
@section('title', 'Şube Raporu - Yazdır')
@section('content')
@php
    $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null);
    $printSubtitle = trim($periodLabel . (! empty($selectedLabel) ? ' · ' . $selectedLabel : ''));
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
        @include('partials.print-brand-header', [
            'documentTitle' => 'ŞUBE RAPORU',
            'documentNumber' => $printSubtitle,
            'documentDate' => now(),
        ])

        <div class="mb-4 grid grid-cols-4 gap-3 text-sm">
            <div class="border border-neutral-300 rounded p-3">
                <p class="text-xs text-neutral-500 uppercase">Sipariş</p>
                <p class="text-lg font-semibold mt-1">{{ number_format($summaryTotals['sale_count'], 0, ',', '.') }}</p>
                <p class="text-xs text-neutral-500">₺{{ number_format($summaryTotals['grand_total'], 0, ',', '.') }}</p>
            </div>
            <div class="border border-neutral-300 rounded p-3">
                <p class="text-xs text-neutral-500 uppercase">Tahsil</p>
                <p class="text-lg font-semibold mt-1 text-emerald-700">₺{{ number_format($summaryTotals['paid_amount'], 0, ',', '.') }}</p>
                <p class="text-xs text-neutral-500">Kalan ₺{{ number_format($summaryTotals['remaining'], 0, ',', '.') }}</p>
            </div>
            <div class="border border-neutral-300 rounded p-3">
                <p class="text-xs text-neutral-500 uppercase">SSH</p>
                <p class="text-lg font-semibold mt-1">{{ number_format($summaryTotals['ticket_count'], 0, ',', '.') }}</p>
            </div>
            <div class="border border-neutral-300 rounded p-3">
                <p class="text-xs text-neutral-500 uppercase">Açık SSH</p>
                <p class="text-lg font-semibold mt-1">{{ number_format($summaryTotals['open_count'], 0, ',', '.') }}</p>
                <p class="text-xs text-neutral-500">{{ number_format($summaryTotals['done_count'], 0, ',', '.') }} tamamlanan</p>
            </div>
        </div>

        <table class="print-table min-w-full">
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
                </tr>
            </thead>
            <tbody>
                @forelse($branchRows as $row)
                <tr>
                    <td class="table-td">{{ $row['name'] }}</td>
                    <td class="table-td text-right">{{ number_format($row['sale_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">₺{{ number_format($row['grand_total'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">₺{{ number_format($row['paid_amount'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">₺{{ number_format($row['remaining'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">{{ number_format($row['ticket_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">{{ number_format($row['open_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">{{ number_format($row['done_count'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-neutral-500">Kayıt yok.</td></tr>
                @endforelse
            </tbody>
            @if($branchRows !== [])
            <tfoot>
                <tr class="font-semibold">
                    <td class="table-td">Toplam</td>
                    <td class="table-td text-right">{{ number_format($branchTotals['sale_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">₺{{ number_format($branchTotals['grand_total'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">₺{{ number_format($branchTotals['paid_amount'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">₺{{ number_format($branchTotals['remaining'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">{{ number_format($branchTotals['ticket_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">{{ number_format($branchTotals['open_count'], 0, ',', '.') }}</td>
                    <td class="table-td text-right">{{ number_format($branchTotals['done_count'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>

        @if(filled($selectedBranchId) && ($detailSales->isNotEmpty() || $detailTickets->isNotEmpty()))
        <p class="print-section-title mt-6">Siparişler</p>
        <table class="print-table min-w-full mb-4">
            <thead>
                <tr>
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-right">Tutar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailSales as $sale)
                <tr>
                    <td class="table-td">{{ $sale->saleNumber }}</td>
                    <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="table-td">{{ $sale->saleDate?->format('d.m.Y') }}</td>
                    <td class="table-td text-right">₺{{ number_format($sale->grandTotal, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-3 text-neutral-500">Sipariş yok.</td></tr>
                @endforelse
            </tbody>
        </table>

        <p class="print-section-title">SSH kayıtları</p>
        <table class="print-table min-w-full">
            <thead>
                <tr>
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th">Açılış</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailTickets as $ticket)
                <tr>
                    <td class="table-td">{{ $ticket->ticketNumber }}</td>
                    <td class="table-td">{{ $ticket->customer?->name ?? '—' }}</td>
                    <td class="table-td">{{ \App\Support\ServiceTicketStatus::label($ticket->status) }}</td>
                    <td class="table-td">{{ $ticket->openedAt?->format('d.m.Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-3 text-neutral-500">SSH yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @endif

        @include('partials.print-document-footer', ['documentRef' => 'Şube Raporu · ' . $printSubtitle, 'footerNote' => null])
        </div>
    </div>
</div>
@endsection
