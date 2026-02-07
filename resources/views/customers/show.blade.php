@extends('layouts.app')
@section('title', $customer->name)
@section('content')
@php
    $totalSales = $customer->sales->where('isCancelled', false)->sum('grandTotal');
    $totalPaid = $customer->payments->sum('amount');
    $totalDebt = $totalSales - $totalPaid;
    $soldProducts = collect();
    foreach ($customer->sales->where('isCancelled', false) as $sale) {
        foreach ($sale->items ?? [] as $item) {
            if ($item->product) $soldProducts->push(['product' => $item->product->name, 'quantity' => $item->quantity, 'sale' => $sale->saleNumber, 'saleId' => $sale->id]);
        }
    }
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('customers.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Müşteriler</a>
            <span>/</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $customer->name }}</span>
        </nav>
        <h1 class="page-title">{{ $customer->name }}</h1>
        <p class="page-desc">Müşteri detayları ve borç özeti</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @include('partials.action-buttons', ['edit' => route('customers.edit', $customer), 'print' => route('customers.print', $customer)])
        <a href="{{ route('customer-payments.create') }}?customerId={{ $customer->id }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Ödeme Al
        </a>
    </div>
</div>

{{-- Özet kartları (dashboard ile aynı stil) --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Toplam Satış</p>
                <p class="text-xl font-semibold text-slate-900 dark:text-white mt-1 tracking-tight">{{ number_format($totalSales ?? 0, 0, ',', '.') }} ₺</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-slate-700">
                <svg class="w-6 h-6 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Toplam Ödenen</p>
                <p class="text-xl font-semibold text-emerald-600 dark:text-emerald-400 mt-1 tracking-tight">{{ number_format($totalPaid ?? 0, 0, ',', '.') }} ₺</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kalan Borç</p>
                <p class="text-xl font-semibold mt-1 tracking-tight {{ ($totalDebt ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">{{ number_format($totalDebt ?? 0, 0, ',', '.') }} ₺</p>
            </div>
            <div class="p-3 rounded-xl {{ ($totalDebt ?? 0) > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-slate-100 dark:bg-slate-700' }}">
                <svg class="w-6 h-6 {{ ($totalDebt ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-header">İletişim Bilgileri</div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div><dt class="form-label">E-posta</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customer->email ?: '—' }}</dd></div>
                    <div><dt class="form-label">Telefon</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customer->phone ?: '—' }}</dd></div>
                    <div><dt class="form-label">Adres</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customer->address ?: '—' }}</dd></div>
                    <div><dt class="form-label">TC / Vergi No · Dairesi</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customer->identityNumber ?: '—' }} @if($customer->taxNumber || $customer->taxOffice) · {{ trim(($customer->taxNumber ?? '') . ' ' . ($customer->taxOffice ?? '')) }}@endif</dd></div>
                    <div><dt class="form-label">Durum</dt><dd><span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full {{ $customer->isActive ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">{{ $customer->isActive ? 'Aktif' : 'Pasif' }}</span></dd></div>
                </dl>
            </div>
        </div>
        @if($customer->quotes->count() > 0)
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Teklifler</span>
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $customer->quotes->count() }} teklif</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">No</th><th class="table-th">Durum</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">İşlem</th></tr></thead>
                    <tbody>
                        @foreach($customer->quotes->take(5) as $q)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td"><a href="{{ route('quotes.show', $q) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $q->quoteNumber }}</a></td>
                            <td class="table-td"><span class="text-xs px-2 py-1 rounded-full {{ ($q->status ?? '') === 'taslak' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' }}">{{ ucfirst($q->status ?? '—') }}</span></td>
                            <td class="table-td text-right font-medium">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('quotes.show', $q)])</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @if($customer->payments->count() > 0)
        <div class="card overflow-hidden" id="tahsilatlar">
            <div class="card-header flex items-center justify-between">
                <span>Tahsilatlar</span>
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $customer->payments->count() }} tahsilat</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">Tarih</th><th class="table-th">Tutar</th><th class="table-th">Tip</th><th class="table-th">İlgili Fatura</th><th class="table-th text-right">İşlem</th></tr></thead>
                    <tbody>
                        @php $pt = ['nakit'=>'Nakit','havale'=>'Havale','kredi_karti'=>'Kredi Kartı','cek'=>'Çek','senet'=>'Senet','diger'=>'Diğer']; @endphp
                        @foreach($customer->payments->sortByDesc('paymentDate')->take(15) as $p)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td"><a href="{{ route('customer-payments.show', $p) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $p->paymentDate?->format('d.m.Y') ?? '—' }}</a></td>
                            <td class="table-td"><a href="{{ route('customer-payments.show', $p) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</a></td>
                            <td class="table-td">{{ $pt[$p->paymentType ?? ''] ?? ucfirst($p->paymentType ?? '—') }}</td>
                            <td class="table-td">@if($p->saleId)<a href="{{ route('sales.show', $p->sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $p->sale?->saleNumber ?? '—' }}</a>@else—@endif</td>
                            <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('customer-payments.show', $p), 'edit' => route('customer-payments.edit', $p), 'print' => route('customer-payments.print', $p)])</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @if($soldProducts->count() > 0)
        <div class="card overflow-hidden">
            <div class="card-header">Satın Alınan Ürünler</div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">Ürün</th><th class="table-th text-right">Adet</th><th class="table-th">Satış No</th></tr></thead>
                    <tbody>
                        @foreach($soldProducts->take(10) as $sp)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td font-medium">{{ $sp['product'] ?? '—' }}</td>
                            <td class="table-td text-right">{{ $sp['quantity'] ?? 0 }}</td>
                            <td class="table-td">@if(!empty($sp['saleId']))<a href="{{ route('sales.show', $sp['saleId']) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $sp['sale'] ?? '—' }}</a>@else{{ $sp['sale'] ?? '—' }}@endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Siparişler (Satışlar)</span>
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $customer->sales->count() }} satış</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">No</th><th class="table-th">Tarih</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">Ödenen</th><th class="table-th text-right">Kalan</th></tr></thead>
                    <tbody>
                        @forelse($customer->sales->where('isCancelled', false)->take(10) as $s)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td"><a href="{{ route('sales.show', $s) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $s->saleNumber }}</a></td>
                            <td class="table-td">{{ $s->saleDate?->format('d.m.Y') }}</td>
                            <td class="table-td text-right font-medium">{{ number_format($s->grandTotal, 0, ',', '.') }} ₺</td>
                            <td class="table-td text-right text-emerald-600 dark:text-emerald-400">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="table-td text-right">{{ number_format(($s->grandTotal ?? 0) - ($s->paidAmount ?? 0), 0, ',', '.') }} ₺</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="table-td text-center text-slate-500 dark:text-slate-400 py-8">Henüz satış yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customer->sales->where('isCancelled', false)->count() > 10)
            <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-700 text-center">
                <a href="{{ route('sales.index') }}?customerId={{ $customer->id }}" class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Tüm satışları görüntüle</a>
            </div>
            @endif
        </div>
</div>
@endsection
