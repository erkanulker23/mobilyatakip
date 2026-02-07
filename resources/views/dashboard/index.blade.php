@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="mb-8">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-desc">Genel bakış ve son hareketler</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Satış</p>
                <p class="text-2xl font-semibold text-slate-900 mt-1 tracking-tight">{{ $stats['salesCount'] }}</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Teklif</p>
                <p class="text-2xl font-semibold text-slate-900 mt-1 tracking-tight">{{ $stats['quotesCount'] }}</p>
            </div>
            <div class="p-3 rounded-xl bg-sky-50">
                <svg class="w-7 h-7 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Alış</p>
                <p class="text-2xl font-semibold text-slate-900 mt-1 tracking-tight">{{ $stats['purchasesCount'] }}</p>
            </div>
            <div class="p-3 rounded-xl bg-amber-50">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <a href="{{ route('stock.low') }}" class="block">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kritik Stok</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1 tracking-tight">{{ $stats['lowStockCount'] }}</p>
                </div>
                <div class="p-3 rounded-xl {{ $stats['lowStockCount'] > 0 ? 'bg-red-50' : 'bg-slate-100' }}">
                    <svg class="w-7 h-7 {{ $stats['lowStockCount'] > 0 ? 'text-red-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">Son Satışlar</div>
    <div class="overflow-x-auto">
        @if($recentSales->isEmpty())
            <div class="p-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto">
                    <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="mt-4 text-slate-500 text-sm">Henüz satış kaydı yok.</p>
                <a href="{{ route('sales.create') }}" class="btn-primary mt-4">İlk satışı oluştur</a>
            </div>
        @else
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="table-th">No</th>
                        <th class="table-th">Müşteri</th>
                        <th class="table-th">Tarih</th>
                        <th class="table-th">Tutar</th>
                        <th class="table-th text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSales as $s)
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="table-td"><span class="font-medium text-slate-900">{{ $s->saleNumber }}</span></td>
                        <td class="table-td">{{ $s->customer?->name }}</td>
                        <td class="table-td">{{ $s->saleDate?->format('d.m.Y') }}</td>
                        <td class="table-td font-medium text-slate-900">{{ number_format($s->grandTotal, 0, ',', '.') }} ₺</td>
                        <td class="table-td text-right">
                            <a href="{{ route('sales.show', $s) }}" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">Detay</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
