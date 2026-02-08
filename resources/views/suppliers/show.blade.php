@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
@php
    $totalPurchases = $supplier->purchases->where('isCancelled', false)->sum('grandTotal');
    $totalPayments = $supplier->payments->sum('amount');
    $balance = $totalPurchases - $totalPayments;
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('suppliers.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Tedarikçiler</a>
            <span>/</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $supplier->name }}</span>
        </nav>
        <h1 class="page-title">{{ $supplier->name }}</h1>
        <p class="page-desc">Tedarikçi detayları ve bakiye özeti</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @include('partials.action-buttons', ['edit' => route('suppliers.edit', $supplier), 'print' => route('suppliers.print', $supplier)])
        <a href="{{ route('supplier-payments.create', ['supplierId' => $supplier->id]) }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            Tedarikçi Ödeme Yap
        </a>
    </div>
</div>

{{-- Özet kartları --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Toplam Alış</p>
                <p class="text-xl font-semibold text-slate-900 dark:text-white mt-1 tracking-tight">{{ number_format($totalPurchases ?? 0, 0, ',', '.') }} ₺</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-slate-700">
                <svg class="w-6 h-6 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Toplam Ödenen</p>
                <p class="text-xl font-semibold text-emerald-600 dark:text-emerald-400 mt-1 tracking-tight">{{ number_format($totalPayments ?? 0, 0, ',', '.') }} ₺</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bakiye</p>
                <p class="text-xl font-semibold mt-1 tracking-tight {{ ($balance ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : (($balance ?? 0) < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">{{ number_format($balance ?? 0, 0, ',', '.') }} ₺</p>
                @if(($balance ?? 0) != 0)<p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ ($balance ?? 0) > 0 ? 'Tedarikçiye borç' : 'Tedarikçiden alacak' }}</p>@endif
            </div>
            <div class="p-3 rounded-xl {{ ($balance ?? 0) > 0 ? 'bg-red-50 dark:bg-red-900/20' : (($balance ?? 0) < 0 ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700') }}">
                <svg class="w-6 h-6 {{ ($balance ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : (($balance ?? 0) < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75m15.75 0h.75.75v-.75c0-.414-.336-.75-.75-.75h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"></path></svg>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-header">İletişim Bilgileri</div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div><dt class="form-label">E-posta</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplier->email ?: '—' }}</dd></div>
                    <div><dt class="form-label">Telefon</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplier->phone ?: '—' }}</dd></div>
                    <div><dt class="form-label">Adres</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplier->address ?: '—' }}</dd></div>
                    <div><dt class="form-label">Vergi No</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplier->taxNumber ?: '—' }}</dd></div>
                    <div><dt class="form-label">Vergi Dairesi</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplier->taxOffice ?: '—' }}</dd></div>
                    <div><dt class="form-label">Durum</dt><dd><span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full {{ $supplier->isActive ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">{{ $supplier->isActive ? 'Aktif' : 'Pasif' }}</span></dd></div>
                </dl>
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Tedarikçinin Ürünleri</span>
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $supplier->products->count() }} ürün</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">Ürün</th><th class="table-th">SKU</th><th class="table-th text-right">Fiyat</th><th class="table-th text-right">İşlem</th></tr></thead>
                    <tbody>
                        @forelse($supplier->products as $p)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td"><a href="{{ route('products.show', $p) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $p->name }}</a></td>
                            <td class="table-td font-mono text-sm">{{ $p->sku ?? '—' }}</td>
                            <td class="table-td text-right font-medium">{{ number_format($p->unitPrice ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('products.show', $p), 'edit' => route('products.edit', $p)])</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="table-td text-center text-slate-500 dark:text-slate-400 py-8">Bu tedarikçiye ait ürün yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Alışlar</span>
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $supplier->purchases->count() }} alış</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">No</th><th class="table-th">Tarih</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">İşlem</th></tr></thead>
                    <tbody>
                        @forelse($supplier->purchases->where('isCancelled', false)->take(10) as $p)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td"><a href="{{ route('purchases.show', $p) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $p->purchaseNumber }}</a></td>
                            <td class="table-td">{{ $p->purchaseDate?->format('d.m.Y') }}</td>
                            <td class="table-td text-right font-medium">{{ number_format($p->grandTotal, 0, ',', '.') }} ₺</td>
                            <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('purchases.show', $p), 'edit' => route('purchases.edit', $p), 'print' => route('purchases.print', $p)])</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="table-td text-center text-slate-500 dark:text-slate-400 py-8">Henüz alış yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Yapılan Ödemeler</span>
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $supplier->payments->count() }} ödeme</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr class="border-b border-slate-100 dark:border-slate-700"><th class="table-th">Tarih</th><th class="table-th text-right">Tutar</th><th class="table-th">Tip</th><th class="table-th">İlgili Fatura</th><th class="table-th text-right w-40">İşlem</th></tr></thead>
                    <tbody>
                        @php $pt = ['nakit'=>'Nakit','havale'=>'Havale','kredi_karti'=>'Kredi Kartı','cek'=>'Çek','senet'=>'Senet','diger'=>'Diğer']; @endphp
                        @forelse($supplier->payments->sortByDesc('paymentDate') as $pm)
                        <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="table-td">{{ $pm->paymentDate?->format('d.m.Y') }}</td>
                            <td class="table-td text-right font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($pm->amount ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="table-td">{{ $pt[$pm->paymentType ?? ''] ?? ucfirst($pm->paymentType ?? '—') }}</td>
                            <td class="table-td">@if($pm->purchaseId && $pm->purchase)<a href="{{ route('purchases.show', $pm->purchase) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $pm->purchase->purchaseNumber }}</a>@else{{ $pm->reference ?? '—' }}@endif</td>
                            <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('supplier-payments.show', $pm), 'edit' => route('supplier-payments.edit', $pm)])</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="table-td text-center text-slate-500 dark:text-slate-400 py-8">Henüz ödeme yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
</div>
@endsection
