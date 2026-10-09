@extends('layouts.print')
@section('title', 'Ödemeler Raporu - Yazdır')
@section('content')
@php $periodLabel = \App\Support\ReportFilters::periodLabel($from, $to, $year ?? null, $month ?? null); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => 'ÖDEMELER RAPORU',
                'documentNumber' => $periodLabel,
                'documentDate' => now(),
            ])
            <p class="text-sm mb-4">Kasadan çıkan hareketler · Toplam {{ number_format($total, 0, ',', '.') }} ₺ · {{ $count }} kayıt</p>
            <table class="print-table min-w-full">
                <thead>
                    <tr>
                        <th class="table-th">Tarih</th>
                        <th class="table-th">Tür</th>
                        <th class="table-th">Açıklama</th>
                        <th class="table-th">Kasa</th>
                        <th class="table-th text-right">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    @php $h = $row->movement; @endphp
                    <tr>
                        <td class="table-td">{{ $h->movementDate?->format('d.m.Y') }}</td>
                        <td class="table-td">{{ $row->badge['label'] }}</td>
                        <td class="table-td">{{ $h->description ?: '—' }}</td>
                        <td class="table-td">{{ $h->kasa?->name ?? '—' }}</td>
                        <td class="table-td text-right tabular-nums">{{ number_format($row->amount, 0, ',', '.') }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="table-td font-semibold">Toplam</td>
                        <td class="table-td text-right font-semibold tabular-nums">{{ number_format($total, 0, ',', '.') }} ₺</td>
                    </tr>
                </tfoot>
            </table>
            @include('partials.print-document-footer', [
                'documentRef' => 'Ödemeler · ' . $periodLabel,
                'footerNote' => 'Virman hariç kasa çıkışları',
            ])
        </div>
    </div>
</div>
@endsection
