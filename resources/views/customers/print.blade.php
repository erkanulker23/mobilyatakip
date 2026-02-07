@extends('layouts.print')
@section('title', 'Müşteri Extresi - ' . $customer->name)
@section('content')
@php $company = \App\Models\Company::first(); @endphp
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-start mb-8 pb-6 border-b-2 border-slate-200">
        <div>
            @if($company?->logoUrl)<img src="{{ asset($company->logoUrl) }}" alt="Logo" class="h-14 mb-2">@endif
            <h1 class="text-xl font-bold text-slate-900">{{ $company?->name ?? 'Firma' }}</h1>
            <p class="text-sm text-slate-600">{{ $company?->address }}</p>
        </div>
        <div class="text-right">
            <h2 class="text-lg font-semibold text-slate-800">MÜŞTERİ EXTRESİ</h2>
            <p class="text-sm text-slate-600">{{ now()->format('d.m.Y H:i') }}</p>
        </div>
    </div>
    <div class="mb-8">
        <h3 class="text-base font-semibold text-slate-900 mb-2">Müşteri Bilgileri</h3>
        <p class="font-bold text-slate-900 text-lg">{{ $customer->name }}</p>
        <p class="text-sm text-slate-600">{{ $customer->address }}</p>
        <p class="text-sm text-slate-600">{{ $customer->phone }} {{ $customer->email ? '| ' . $customer->email : '' }}</p>
        @if($customer->taxNumber)<p class="text-sm text-slate-600">Vergi No: {{ $customer->taxNumber }} @if($customer->taxOffice) / {{ $customer->taxOffice }} @endif</p>@endif
    </div>
    <div class="mb-6 p-4 bg-slate-50 rounded-lg">
        <h3 class="font-semibold text-slate-900 mb-2">Bakiye Özeti</h3>
        <table class="w-full text-sm">
            <tr><td class="py-1">Toplam Satış:</td><td class="text-right font-medium">{{ number_format($totalSales ?? 0, 2, ',', '.') }} ₺</td></tr>
            <tr><td class="py-1">Toplam Ödenen:</td><td class="text-right font-medium text-green-600">{{ number_format($totalPaid ?? 0, 2, ',', '.') }} ₺</td></tr>
            <tr><td class="py-1 font-bold">Kalan Borç:</td><td class="text-right font-bold text-lg {{ ($totalDebt ?? 0) > 0 ? 'text-red-600' : 'text-slate-600' }}">{{ number_format($totalDebt ?? 0, 2, ',', '.') }} ₺</td></tr>
        </table>
    </div>
    <div class="mb-6">
        <h3 class="font-semibold text-slate-900 mb-3">Satışlar</h3>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100"><tr><th class="px-4 py-2 text-left font-semibold">No</th><th class="px-4 py-2 text-left font-semibold">Tarih</th><th class="px-4 py-2 text-right font-semibold">Toplam</th><th class="px-4 py-2 text-right font-semibold">Ödenen</th><th class="px-4 py-2 text-right font-semibold">Kalan</th></tr></thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($customer->sales as $s)
                <tr>
                    <td class="px-4 py-2">{{ $s->saleNumber }}</td>
                    <td class="px-4 py-2">{{ $s->saleDate?->format('d.m.Y') }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($s->grandTotal ?? 0, 2, ',', '.') }} ₺</td>
                    <td class="px-4 py-2 text-right text-green-600">{{ number_format($s->paidAmount ?? 0, 2, ',', '.') }} ₺</td>
                    <td class="px-4 py-2 text-right">{{ number_format(($s->grandTotal ?? 0) - ($s->paidAmount ?? 0), 2, ',', '.') }} ₺</td>
                </tr>
                @endforeach
                @if($customer->sales->isEmpty())<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Satış yok.</td></tr>@endif
            </tbody>
        </table>
    </div>
    <div>
        <h3 class="font-semibold text-slate-900 mb-3">Ödemeler</h3>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100"><tr><th class="px-4 py-2 text-left font-semibold">Tarih</th><th class="px-4 py-2 text-left font-semibold">Tip</th><th class="px-4 py-2 text-right font-semibold">Tutar</th></tr></thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($customer->payments as $p)
                <tr><td class="px-4 py-2">{{ $p->paymentDate?->format('d.m.Y') }}</td><td class="px-4 py-2">{{ ucfirst($p->paymentType ?? '-') }}</td><td class="px-4 py-2 text-right font-medium">{{ number_format($p->amount ?? 0, 2, ',', '.') }} ₺</td></tr>
                @endforeach
                @if($customer->payments->isEmpty())<tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Ödeme yok.</td></tr>@endif
            </tbody>
        </table>
    </div>
</div>
@endsection
