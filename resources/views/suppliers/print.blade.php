@extends('layouts.print')
@section('title', 'Tedarikçi Ekstre - ' . $supplier->name)
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => 'Tedarikçi Ekstre',
                'documentNumber' => $supplier->name,
                'documentDate' => now(),
            ])

            @php
                $totalPurchases = $supplier->purchases->sum('grandTotal');
                $totalPayments = $supplier->payments->sum('amount');
                $balance = $totalPurchases - $totalPayments;
            @endphp
            <div class="print-meta-grid print-section-lg">
                <div class="print-card">
                    <p class="print-label">Tedarikçi</p>
                    <p class="print-party-name">{{ $supplier->name }}</p>
                    @if($supplier->full_address)<p class="print-muted mt-1 whitespace-pre-wrap">{{ $supplier->full_address }}</p>@endif
                    @if($supplier->phone)<p class="print-muted">{{ $supplier->phone }}</p>@endif
                    @if($supplier->email)<p class="print-muted">{{ $supplier->email }}</p>@endif
                    @if($supplier->taxNumber)<p class="print-muted">Vergi: {{ $supplier->taxNumber }} @if($supplier->taxOffice)/ {{ $supplier->taxOffice }}@endif</p>@endif
                </div>
                <div class="print-card print-card--meta">
                    <p class="print-label">Bakiye Özeti</p>
                    <div class="print-totals-row"><span>Toplam Alış</span><span>{{ number_format($totalPurchases, 0, ',', '.') }} ₺</span></div>
                    <div class="print-totals-row"><span>Toplam Ödenen</span><span>{{ number_format($totalPayments, 0, ',', '.') }} ₺</span></div>
                    <div class="print-totals-grand"><span>Bakiye</span><span>{{ number_format($balance, 0, ',', '.') }} ₺</span></div>
                </div>
            </div>

            <div class="print-section-lg">
                <p class="print-section-title">Alınan Ürünler</p>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th class="text-left">Alış No</th>
                            <th class="text-left">Tarih</th>
                            <th class="text-left">Ürün</th>
                            <th class="text-right">Adet</th>
                            <th class="text-right">Birim Fiyat</th>
                            <th class="text-right">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supplier->purchases as $p)
                            @foreach($p->items as $i)
                            <tr>
                                <td class="font-medium">{{ $p->purchaseNumber }}</td>
                                <td class="print-muted">{{ $p->purchaseDate?->format('d.m.Y') }}</td>
                                <td>{{ $i->product?->name ?? '-' }}</td>
                                <td class="text-right">{{ $i->quantity }}</td>
                                <td class="text-right print-muted">{{ number_format($i->unitPrice ?? 0, 0, ',', '.') }} ₺</td>
                                <td class="text-right font-medium">{{ number_format($i->lineTotal ?? 0, 0, ',', '.') }} ₺</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="5" class="text-right font-medium print-muted">Alış Toplam</td>
                                <td class="text-right font-bold">{{ number_format($p->grandTotal, 0, ',', '.') }} ₺</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="print-section">
                <p class="print-section-title">Yapılan Ödemeler</p>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th class="text-left">Tarih</th>
                            <th class="text-right">Tutar</th>
                            <th class="text-left">Tip</th>
                            <th class="text-left">Referans</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->payments->sortByDesc('paymentDate') as $pm)
                        <tr>
                            <td class="print-muted">{{ $pm->paymentDate?->format('d.m.Y') }}</td>
                            <td class="text-right font-medium">{{ number_format($pm->amount ?? 0, 0, ',', '.') }} ₺</td>
                            <td>{{ ucfirst($pm->paymentType ?? '-') }}</td>
                            <td class="print-muted">@include('partials.supplier-payment-source', ['payment' => $pm])</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center print-muted py-4">Ödeme kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
