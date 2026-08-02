@extends('layouts.app')
@section('title', 'Tahsilat Kayıtları')
@section('content')
@php
    $paymentLabels = \App\Support\PaymentType::labels();
    $hasFilters = request()->hasAny(['search', 'customerId', 'paymentType', 'kasaId', 'from', 'to']);
@endphp

<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
            <a href="{{ route('customer-payments.create') }}" class="hover:text-neutral-900">Ödeme Al</a>
            <span>/</span>
            <span class="text-neutral-700">Tahsilat Kayıtları</span>
        </div>
        <h1 class="page-title">Tahsilat Kayıtları</h1>
        <p class="page-desc">Müşterilerden alınan tüm tahsilatları görüntüleyin ve yönetin</p>
    </div>
    <a href="{{ route('customer-payments.create') }}" class="btn-primary shrink-0 self-start">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Tahsilat
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Bugün</p>
        <p class="mt-2 text-2xl font-semibold text-neutral-900 tabular-nums">{{ number_format($todayTotal, 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Bu Ay</p>
        <p class="mt-2 text-2xl font-semibold text-neutral-900 tabular-nums">{{ number_format($monthTotal, 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-5 border-neutral-900/10 bg-neutral-50">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ $hasFilters ? 'Filtrelenen Toplam' : 'Liste Toplamı' }}</p>
        <p class="mt-2 text-2xl font-semibold text-neutral-900 tabular-nums">{{ number_format($totalAmount, 0, ',', '.') }} ₺</p>
        <p class="mt-1 text-xs text-neutral-500">{{ $payments->total() }} kayıt</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="p-4 md:p-5 border-b border-neutral-100 bg-white">
        <form method="GET" action="{{ route('customer-payments.create') }}" class="space-y-4">
            <input type="hidden" name="list" value="1">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="xl:col-span-2">
                    <label class="form-label">Ara</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Müşteri, fatura no, referans..." class="form-input pl-10">
                    </div>
                </div>
                <div>
                    <label class="form-label">Müşteri</label>
                    <select name="customerId" class="form-select">
                        <option value="">Tüm müşteriler</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Ödeme tipi</label>
                    <select name="paymentType" class="form-select">
                        <option value="">Tümü</option>
                        @foreach(\App\Support\PaymentType::labels() as $value => $label)
                        <option value="{{ $value }}" {{ request('paymentType') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Başlangıç</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Bitiş</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Kasa</label>
                    <select name="kasaId" class="form-select">
                        <option value="">Tüm kasalar</option>
                        @foreach($kasalar as $k)
                        <option value="{{ $k->id }}" {{ request('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary flex-1">Filtrele</button>
                    <a href="{{ route('customer-payments.create', ['list' => 1]) }}" class="btn-secondary shrink-0">Temizle</a>
                </div>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100">
                    <th class="table-th">Tarih</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Fatura</th>
                    <th class="table-th">Ödeme tipi</th>
                    <th class="table-th">Kasa</th>
                    <th class="table-th">Referans</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th text-right w-36">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr class="border-b border-neutral-50 hover:bg-neutral-50/60 transition-colors">
                    <td class="table-td whitespace-nowrap">
                        <span class="font-medium text-neutral-900">{{ $payment->paymentDate?->format('d.m.Y') ?? '—' }}</span>
                    </td>
                    <td class="table-td">
                        @if($payment->customer)
                        <a href="{{ route('customers.show', $payment->customer) }}" class="font-medium text-neutral-900 hover:underline">{{ $payment->customer->name }}</a>
                        @else
                        <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                    <td class="table-td">
                        @if($payment->sale)
                        <a href="{{ route('sales.show', $payment->sale) }}" class="text-neutral-700 hover:underline font-mono text-sm">{{ $payment->sale->saleNumber }}</a>
                        @else
                        <span class="text-neutral-400 text-sm">Genel</span>
                        @endif
                    </td>
                    <td class="table-td">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-neutral-100 text-neutral-700">
                            {{ $paymentLabels[$payment->paymentType ?? ''] ?? ucfirst($payment->paymentType ?? '—') }}
                        </span>
                    </td>
                    <td class="table-td text-neutral-600">{{ $payment->kasa?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-500 max-w-[10rem] truncate" title="{{ $payment->reference }}">{{ $payment->reference ?: '—' }}</td>
                    <td class="table-td text-right font-semibold text-neutral-900 tabular-nums whitespace-nowrap">{{ number_format($payment->amount ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('customer-payments.show', $payment),
                            'edit' => route('customer-payments.edit', $payment),
                            'print' => route('customer-payments.print', $payment),
                            'destroy' => route('customer-payments.destroy', $payment),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <p class="text-neutral-600 font-medium">Tahsilat kaydı bulunamadı</p>
                        <p class="text-sm text-neutral-500 mt-1">{{ $hasFilters ? 'Filtreleri değiştirmeyi deneyin.' : 'İlk tahsilat kaydını oluşturun.' }}</p>
                        <a href="{{ route('customer-payments.create') }}" class="btn-primary mt-4 inline-flex">Tahsilat Al</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-5 py-4 border-t border-neutral-100">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
