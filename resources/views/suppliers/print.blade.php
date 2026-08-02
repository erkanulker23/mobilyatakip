@extends('layouts.print')
@section('title', 'Tedarikçi Ekstre - ' . $supplier->name)
@section('content')
@php $company = \App\Models\Company::first(); @endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'Tedarikçi Ekstre',
            'documentNumber' => $supplier->name,
            'documentDate' => now(),
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-2">Tedarikçi</h3>
                <p class="font-semibold text-slate-900">{{ $supplier->name }}</p>
                @if($supplier->full_address)<p class="text-sm text-slate-600 mt-1 whitespace-pre-wrap">{{ $supplier->full_address }}</p>@endif
                @if($supplier->phone)<p class="text-sm text-slate-600">{{ $supplier->phone }}</p>@endif
                @if($supplier->email)<p class="text-sm text-slate-600">{{ $supplier->email }}</p>@endif
                @if($supplier->taxNumber)<p class="text-sm text-slate-600">Vergi: {{ $supplier->taxNumber }} @if($supplier->taxOffice)/ {{ $supplier->taxOffice }}@endif</p>@endif
            </div>
            <div class="md:text-right">
                @php
                    $totalPurchases = $supplier->purchases->sum('grandTotal');
                    $totalPayments = $supplier->payments->sum('amount');
                    $balance = $totalPurchases - $totalPayments;
                @endphp
                <p class="text-sm text-slate-600">Toplam Alış: <span class="font-semibold">{{ number_format($totalPurchases, 0, ',', '.') }} ₺</span></p>
                <p class="text-sm text-green-600 mt-1">Toplam Ödenen: <span class="font-semibold">{{ number_format($totalPayments, 0, ',', '.') }} ₺</span></p>
                <p class="text-base font-bold mt-2 {{ $balance > 0 ? 'text-red-600' : ($balance < 0 ? 'text-green-600' : 'text-slate-600') }}">Bakiye: {{ number_format($balance, 0, ',', '.') }} ₺</p>
            </div>
        </div>

        <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Alınan Ürünler</h3>
        <div class="print-section-lg overflow-x-auto mb-6">
            <table class="print-table min-w-full border border-neutral-300 text-[11px]">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Alış No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ürün</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Adet</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Birim Fiyat</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($supplier->purchases as $p)
                        @foreach($p->items as $i)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium">{{ $p->purchaseNumber }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $p->purchaseDate?->format('d.m.Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $i->product?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $i->quantity }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($i->unitPrice ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($i->lineTotal ?? 0, 0, ',', '.') }} ₺</td>
                        </tr>
                        @endforeach
                        <tr class="bg-slate-50">
                            <td colspan="5" class="px-4 py-2 text-right text-sm font-medium">Alış Toplam:</td>
                            <td class="px-4 py-2 text-right font-bold">{{ number_format($p->grandTotal, 0, ',', '.') }} ₺</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Yapılan Ödemeler</h3>
        <div class="overflow-x-auto">
            <table class="print-table min-w-full border border-neutral-300 text-[11px]">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Tutar</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tip</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Referans</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($supplier->payments->sortByDesc('paymentDate') as $pm)
                    <tr>
                        <td class="px-4 py-3 text-slate-600">{{ $pm->paymentDate?->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-600">{{ number_format($pm->amount ?? 0, 0, ',', '.') }} ₺</td>
                        <td class="px-4 py-3 text-slate-600">{{ ucfirst($pm->paymentType ?? '-') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $pm->reference ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-neutral-500">Ödeme kaydı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
