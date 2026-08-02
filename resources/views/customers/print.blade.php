@extends('layouts.print')
@section('title', 'Müşteri Extresi - ' . $customer->name)
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'Müşteri Extresi',
            'documentNumber' => $customer->name,
            'documentDate' => now(),
        ])

        <div class="print-section-lg mb-4 p-3 border border-neutral-200">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Müşteri Bilgileri</h3>
            <p class="font-bold text-neutral-900">{{ $customer->name }}</p>
            @if($customer->full_address)<p class="text-[11px] text-neutral-600 mt-1 whitespace-pre-wrap">{{ $customer->full_address }}</p>@endif
            <p class="text-[11px] text-neutral-600 mt-1">{{ $customer->phone }}{{ $customer->phone2 ? ' / ' . $customer->phone2 : '' }}{{ $customer->email ? ' · ' . $customer->email : '' }}</p>
            @if($customer->taxNumber)<p class="text-[11px] text-neutral-600">Vergi No: {{ $customer->taxNumber }}@if($customer->taxOffice) / {{ $customer->taxOffice }}@endif</p>@endif
        </div>

        <div class="print-section mb-4 p-3 print-info-banner">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Bakiye Özeti</h3>
            <table class="w-full text-[11px]">
                <tr><td class="py-1 text-neutral-600">Toplam Satış</td><td class="text-right font-medium">{{ number_format($totalSales ?? 0, 0, ',', '.') }} ₺</td></tr>
                <tr><td class="py-1 text-neutral-600">Toplam Ödenen</td><td class="text-right font-medium">{{ number_format($totalPaid ?? 0, 0, ',', '.') }} ₺</td></tr>
                <tr><td class="py-1 font-semibold">Cari Durum</td><td class="text-right font-semibold">{{ $customerBalance['label'] ?? '—' }}</td></tr>
                <tr><td class="py-1 font-semibold">{{ $customerBalance['amountLabel'] ?? 'Bakiye' }}</td><td class="text-right font-bold text-base">{{ number_format($customerBalance['amount'] ?? 0, 0, ',', '.') }} ₺</td></tr>
            </table>
        </div>

        <div class="print-section-lg mb-4">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Satışlar</h3>
            <table class="print-table min-w-full border border-neutral-300 text-[11px]">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left">No</th>
                        <th class="px-2 py-2 text-left">Tarih</th>
                        <th class="px-2 py-2 text-right">Toplam</th>
                        <th class="px-2 py-2 text-right">Ödenen</th>
                        <th class="px-2 py-2 text-right">Kalan</th>
                        <th class="px-2 py-2 text-left">Durum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->sales as $s)
                    @php $saleStatus = \App\Support\CustomerBalance::saleStatus($s); @endphp
                    <tr class="border-t border-neutral-200">
                        <td class="px-2 py-2">{{ $s->saleNumber }}</td>
                        <td class="px-2 py-2">{{ $s->saleDate?->format('d.m.Y') }}</td>
                        <td class="px-2 py-2 text-right">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                        <td class="px-2 py-2 text-right">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺</td>
                        <td class="px-2 py-2 text-right">{{ number_format(\App\Support\CustomerBalance::saleRemaining($s), 0, ',', '.') }} ₺</td>
                        <td class="px-2 py-2">{{ $saleStatus['label'] }}</td>
                    </tr>
                    @endforeach
                    @if($customer->sales->isEmpty())<tr><td colspan="6" class="px-2 py-4 text-center text-neutral-500">Satış yok.</td></tr>@endif
                </tbody>
            </table>
        </div>

        <div class="print-section">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Ödemeler</h3>
            <table class="print-table min-w-full border border-neutral-300 text-[11px]">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left">Tarih</th>
                        <th class="px-2 py-2 text-left">Tip</th>
                        <th class="px-2 py-2 text-right">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->payments as $p)
                    <tr class="border-t border-neutral-200">
                        <td class="px-2 py-2">{{ $p->paymentDate?->format('d.m.Y') }}</td>
                        <td class="px-2 py-2">{{ ucfirst($p->paymentType ?? '-') }}</td>
                        <td class="px-2 py-2 text-right font-medium">{{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</td>
                    </tr>
                    @endforeach
                    @if($customer->payments->isEmpty())<tr><td colspan="3" class="px-2 py-4 text-center text-neutral-500">Ödeme yok.</td></tr>@endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
