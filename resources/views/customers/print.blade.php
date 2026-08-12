@extends('layouts.print')
@section('title', 'Müşteri Extresi - ' . $customer->name)
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => 'Müşteri Extresi',
                'documentNumber' => $customer->name,
                'documentDate' => now(),
            ])

            <div class="print-card print-section-lg">
                <p class="print-label">Müşteri Bilgileri</p>
                <p class="print-party-name">{{ $customer->name }}</p>
                @if($customer->full_address)<p class="print-muted mt-1 whitespace-pre-wrap">{{ $customer->full_address }}</p>@endif
                <p class="print-muted mt-1">{{ $customer->phone }}{{ $customer->phone2 ? ' / ' . $customer->phone2 : '' }}{{ $customer->email ? ' · ' . $customer->email : '' }}</p>
                @if($customer->taxNumber)<p class="print-muted">Vergi No: {{ $customer->taxNumber }}@if($customer->taxOffice) / {{ $customer->taxOffice }}@endif</p>@endif
            </div>

            <div class="print-card print-section">
                <p class="print-label">Bakiye Özeti</p>
                <div class="print-totals-row"><span>Toplam Satış</span><span>{{ number_format($totalSales ?? 0, 0, ',', '.') }} ₺</span></div>
                <div class="print-totals-row"><span>Toplam Ödenen</span><span>{{ number_format($totalPaid ?? 0, 0, ',', '.') }} ₺</span></div>
                <div class="print-totals-row"><span>Cari Durum</span><span class="font-semibold">{{ $customerBalance['label'] ?? '—' }}</span></div>
                <div class="print-totals-grand"><span>{{ $customerBalance['amountLabel'] ?? 'Bakiye' }}</span><span>{{ number_format($customerBalance['amount'] ?? 0, 0, ',', '.') }} ₺</span></div>
            </div>

            <div class="print-section-lg">
                <p class="print-section-title">Satışlar</p>
                <table class="print-table {{ $customer->sales->count() > 8 ? 'print-table--compact' : '' }}">
                    <thead>
                        <tr>
                            <th class="text-left">No</th>
                            <th class="text-left">Şube</th>
                            <th class="text-left">Tarih</th>
                            <th class="text-right">Toplam</th>
                            <th class="text-right">Ödenen</th>
                            <th class="text-right">Kalan</th>
                            <th class="text-left">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->sales as $s)
                        @php $saleStatus = \App\Support\CustomerBalance::saleStatus($s); @endphp
                        <tr>
                            <td>{{ $s->saleNumber }}</td>
                            <td class="print-muted">{{ $s->branch?->name ?? '—' }}</td>
                            <td class="print-muted">{{ $s->saleDate?->format('d.m.Y') }}</td>
                            <td class="text-right">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="text-right">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="text-right">{{ number_format(\App\Support\CustomerBalance::saleRemaining($s), 0, ',', '.') }} ₺</td>
                            <td>{{ $saleStatus['label'] }}</td>
                        </tr>
                        @endforeach
                        @if($customer->sales->isEmpty())<tr><td colspan="7" class="text-center print-muted py-4">Satış yok.</td></tr>@endif
                    </tbody>
                </table>
            </div>

            <div class="print-section">
                <p class="print-section-title">Ödemeler</p>
                <table class="print-table {{ $customer->payments->count() > 8 ? 'print-table--compact' : '' }}">
                    <thead>
                        <tr>
                            <th class="text-left">Tarih</th>
                            <th class="text-left">Tip</th>
                            <th class="text-right">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->payments as $p)
                        <tr>
                            <td class="print-muted">{{ $p->paymentDate?->format('d.m.Y') }}</td>
                            <td>{{ ucfirst($p->paymentType ?? '-') }}</td>
                            <td class="text-right font-medium">{{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</td>
                        </tr>
                        @endforeach
                        @if($customer->payments->isEmpty())<tr><td colspan="3" class="text-center print-muted py-4">Ödeme yok.</td></tr>@endif
                    </tbody>
                </table>
            </div>

            @include('partials.print-document-footer', [
                'documentRef' => 'Müşteri Extresi · ' . $customer->name,
                'footerNote' => 'Cari extre — borç/alacak özeti bilgilendirme amaçlıdır.',
            ])
        </div>
    </div>
</div>
@endsection
