@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::customer($customer))
@section('content')
@php
    $totalSales = $customer->sales->where('isCancelled', false)->sum('grandTotal');
    $totalPaid = $customer->payments->sum('amount');
    $customerBalance = \App\Support\CustomerBalance::customerStatus((float) $totalSales, (float) $totalPaid);
    $activeSales = $customer->sales->where('isCancelled', false)->sortByDesc(fn ($s) => $s->saleDate?->format('Y-m-d') ?? '');
    $allPayments = $customer->payments->sortByDesc('paymentDate');
    $paymentLabels = \App\Support\PaymentType::labels();
    $paymentBadgeClass = fn (?string $type) => match ($type) {
        'nakit' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        'havale' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'kredi_karti' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
        'tedarikciye_ode' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    };
    $initial = mb_strtoupper(mb_substr($customer->name, 0, 1));
    $avatarHue = crc32($customer->name) % 360;
@endphp

<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
    <div class="flex items-start gap-4 min-w-0">
        <div class="h-14 w-14 rounded-2xl shrink-0 flex items-center justify-center text-xl font-semibold text-white shadow-sm" style="background-color: hsl({{ $avatarHue }}, 45%, 42%);">
            {{ $initial }}
        </div>
        <div class="min-w-0">
            <nav class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('customers.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Müşteriler</a>
                <span>/</span>
                <span class="text-neutral-700 dark:text-neutral-300 font-medium truncate">{{ $customer->name }}</span>
            </nav>
            <h1 class="page-title truncate">{{ $customer->name }}</h1>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                @if($customer->phone)
                <a href="tel:{{ preg_replace('/\s+/', '', $customer->phone) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ $customer->phone }}</a>
                @endif
                @if($customer->phone2)
                <span class="hidden sm:inline text-neutral-300">·</span>
                <span>{{ $customer->phone2 }}</span>
                @endif
                @if($customer->city?->name || $customer->district?->name)
                <span class="hidden sm:inline text-neutral-300">·</span>
                <span>{{ collect([$customer->city?->name, $customer->district?->name])->filter()->implode(' / ') }}</span>
                @endif
                @if(!($customer->isActive ?? true))
                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-neutral-100 dark:bg-neutral-800 text-neutral-500">Pasif</span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <a href="{{ route('customer-payments.create') }}?customerId={{ $customer->id }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Ödeme Al
        </a>
        <a href="{{ route('sales.create') }}?customerId={{ $customer->id }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Yeni Satış
        </a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn-edit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Düzenle
        </a>
        <a href="{{ route('customers.print', $customer) }}" target="_blank" rel="noopener" class="btn-print">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Yazdır
        </a>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam satış</p>
        <p class="text-xl sm:text-2xl font-semibold tabular-nums text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($totalSales ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $activeSales->count() }} sipariş</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam tahsilat</p>
        <p class="text-xl sm:text-2xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($totalPaid ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $allPayments->count() }} kayıt</p>
    </div>
    <div class="card p-4 {{ $customerBalance['key'] === 'borclu' ? 'ring-1 ring-red-200 dark:ring-red-800/60' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Cari durum</p>
        <div class="mt-1">
            @include('partials.payment-status-badge', ['status' => ['key' => $customerBalance['key'], 'label' => $customerBalance['label']]])
        </div>
        <p class="text-lg sm:text-xl font-semibold tabular-nums mt-2 {{ $customerBalance['key'] === 'borclu' ? 'text-red-600 dark:text-red-400' : ($customerBalance['key'] === 'alacakli' ? 'text-blue-600 dark:text-blue-400' : 'text-neutral-900 dark:text-neutral-100') }}">
            @if(in_array($customerBalance['key'], ['borclu', 'alacakli'], true))
                {{ number_format($customerBalance['amount'], 0, ',', '.') }} ₺
            @elseif($customerBalance['key'] === 'siparis_yok')
                —
            @else
                Borcu yok
            @endif
        </p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Diğer</p>
        <div class="mt-2 flex flex-wrap gap-2 text-xs">
            @if($customer->quotes->count() > 0)
            <span class="inline-flex px-2 py-1 rounded-md bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">{{ $customer->quotes->count() }} teklif</span>
            @endif
            @if(($serviceTickets ?? collect())->count() > 0)
            <span class="inline-flex px-2 py-1 rounded-md bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">{{ ($serviceTickets ?? collect())->count() }} SSH</span>
            @endif
            @if($customer->quotes->count() === 0 && ($serviceTickets ?? collect())->count() === 0)
            <span class="text-neutral-400">Teklif / SSH yok</span>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6" x-data="{ tab: window.location.hash === '#tahsilatlar' ? 'payments' : 'sales' }" @hashchange.window="tab = window.location.hash === '#tahsilatlar' ? 'payments' : 'sales'">
    <div class="xl:col-span-2 space-y-4">
        <div class="flex gap-1 p-1 rounded-xl bg-neutral-100 dark:bg-neutral-800/80 border border-neutral-200/60 dark:border-neutral-700">
            <button type="button"
                @click="tab = 'sales'; history.replaceState(null, '', window.location.pathname)"
                :class="tab === 'sales' ? 'bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 shadow-sm' : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-200'"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Satışlar
                <span class="inline-flex min-w-[1.25rem] justify-center px-1.5 py-0.5 rounded-full text-[11px] font-semibold" :class="tab === 'sales' ? 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300' : 'bg-neutral-200/80 dark:bg-neutral-700 text-neutral-500'">{{ $activeSales->count() }}</span>
            </button>
            <button type="button"
                @click="tab = 'payments'; history.replaceState(null, '', '#tahsilatlar')"
                :class="tab === 'payments' ? 'bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 shadow-sm' : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-200'"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Tahsilatlar
                <span class="inline-flex min-w-[1.25rem] justify-center px-1.5 py-0.5 rounded-full text-[11px] font-semibold" :class="tab === 'payments' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-neutral-200/80 dark:bg-neutral-700 text-neutral-500'">{{ $allPayments->count() }}</span>
            </button>
        </div>

        {{-- Satışlar --}}
        <div x-show="tab === 'sales'" x-cloak class="card overflow-hidden">
            <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Satışlar</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Sipariş tutarı, tahsilat durumu ve teslimat</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($activeSales->count() > 0)
                    <a href="{{ route('sales.index') }}?customerId={{ $customer->id }}" class="btn-secondary text-sm">Tümünü listele</a>
                    @endif
                    <a href="{{ route('sales.create') }}?customerId={{ $customer->id }}" class="btn-primary text-sm">Yeni Satış</a>
                </div>
            </div>
            <div class="overflow-x-auto -mx-px">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <th class="table-th">Sipariş</th>
                            <th class="table-th col-hide-mobile">Tarih</th>
                            <th class="table-th text-right">Tutar</th>
                            <th class="table-th col-hide-mobile text-right">Kalan</th>
                            <th class="table-th">Durum</th>
                            <th class="table-th text-right w-28 sm:w-36">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeSales as $s)
                        @php
                            $saleStatus = \App\Support\CustomerBalance::saleStatus($s);
                            $remaining = \App\Support\CustomerBalance::saleRemaining($s);
                            $deliveryStatus = \App\Support\SaleDelivery::currentStatus($s);
                            $saleNumberClass = \App\Support\SaleDelivery::numberClassFor($s);
                        @endphp
                        <tr class="border-b border-neutral-50 dark:border-neutral-800/60 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/40 transition-colors">
                            <td class="table-td min-w-[8rem]">
                                <a href="{{ route('sales.show', $s) }}" class="font-medium hover:underline {{ $saleNumberClass }}">{{ $s->saleNumber }}</a>
                                @if($s->branch)
                                <span class="block mt-0.5 text-xs text-emerald-700/80 dark:text-emerald-400/80">{{ $s->branch->name }}</span>
                                @endif
                                <span class="block mt-1 md:hidden text-xs text-neutral-400">{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</span>
                                <span class="inline-flex mt-1 md:hidden">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium {{ \App\Support\SaleDelivery::badgeClass($deliveryStatus) }}">{{ \App\Support\SaleDelivery::label($deliveryStatus) }}</span>
                                </span>
                            </td>
                            <td class="table-td col-hide-mobile whitespace-nowrap text-neutral-600 dark:text-neutral-400">{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</td>
                            <td class="table-td text-right whitespace-nowrap">
                                <p class="font-semibold tabular-nums text-neutral-900 dark:text-neutral-100">{{ number_format($s->grandTotal, 0, ',', '.') }} ₺</p>
                                @if((float) ($s->paidAmount ?? 0) > 0)
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺ alındı</p>
                                @endif
                            </td>
                            <td class="table-td col-hide-mobile text-right whitespace-nowrap">
                                @if($remaining > 0.005)
                                    <span class="font-medium text-red-600 dark:text-red-400 tabular-nums">{{ number_format($remaining, 0, ',', '.') }} ₺</span>
                                @elseif($remaining < -0.005)
                                    <span class="font-medium text-blue-600 dark:text-blue-400 tabular-nums">{{ number_format(abs($remaining), 0, ',', '.') }} ₺ fazla</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400 text-sm">Ödendi</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex flex-col items-start gap-1">
                                    @include('partials.payment-status-badge', ['status' => $saleStatus])
                                    <span class="hidden md:inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium {{ \App\Support\SaleDelivery::badgeClass($deliveryStatus) }}">{{ \App\Support\SaleDelivery::label($deliveryStatus) }}</span>
                                </div>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center justify-end gap-1">
                                    @if($remaining > 0.005)
                                    <a href="{{ route('customer-payments.create') }}?customerId={{ $customer->id }}&saleId={{ $s->id }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors" title="Tahsilat al">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </a>
                                    @endif
                                    @include('partials.action-buttons', ['show' => route('sales.show', $s)])
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <p class="text-neutral-600 dark:text-neutral-300 font-medium">Henüz satış yok</p>
                                <p class="text-sm text-neutral-500 mt-1">Bu müşteri için ilk siparişi oluşturun.</p>
                                <a href="{{ route('sales.create') }}?customerId={{ $customer->id }}" class="btn-primary mt-4 text-sm inline-flex">Satış Oluştur</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tahsilatlar --}}
        <div x-show="tab === 'payments'" x-cloak id="tahsilatlar" class="card overflow-hidden">
            <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Tahsilatlar</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Müşteriden alınan ödemeler ve fatura eşleşmeleri</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($allPayments->count() > 0)
                    <a href="{{ route('customers.payments.print', $customer) }}" target="_blank" rel="noopener" class="btn-print text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Yazdır
                    </a>
                    @endif
                    <a href="{{ route('customer-payments.create') }}?customerId={{ $customer->id }}" class="btn-primary text-sm">Ödeme Al</a>
                </div>
            </div>
            <div class="overflow-x-auto -mx-px">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <th class="table-th">Tarih</th>
                            <th class="table-th text-right">Tutar</th>
                            <th class="table-th col-hide-mobile">Tip</th>
                            <th class="table-th col-hide-mobile">Fatura</th>
                            <th class="table-th text-right w-36 sm:w-44">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allPayments as $p)
                        <tr class="border-b border-neutral-50 dark:border-neutral-800/60 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/40 transition-colors">
                            <td class="table-td whitespace-nowrap">
                                <a href="{{ route('customer-payments.show', $p) }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:underline">{{ $p->paymentDate?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td class="table-td text-right font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums whitespace-nowrap">{{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="table-td col-hide-mobile">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium {{ $paymentBadgeClass($p->paymentType) }}">
                                    {{ $paymentLabels[$p->paymentType ?? ''] ?? ucfirst($p->paymentType ?? '—') }}
                                </span>
                            </td>
                            <td class="table-td col-hide-mobile">
                                @if($p->sale)
                                <a href="{{ route('sales.show', $p->sale) }}" class="font-mono text-sm text-neutral-700 dark:text-neutral-300 hover:underline">{{ $p->sale->saleNumber }}</a>
                                @else
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">Genel</span>
                                @endif
                            </td>
                            <td class="table-td">
                                @include('partials.action-buttons', [
                                    'show' => route('customer-payments.show', $p),
                                    'edit' => route('customer-payments.edit', $p),
                                    'print' => route('customer-payments.print', $p),
                                    'destroy' => route('customer-payments.destroy', $p),
                                ])
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <p class="text-neutral-600 dark:text-neutral-300 font-medium">Henüz tahsilat yok</p>
                                <p class="text-sm text-neutral-500 mt-1">Müşteriden alınan ödemeleri burada görürsünüz.</p>
                                <a href="{{ route('customer-payments.create') }}?customerId={{ $customer->id }}" class="btn-primary mt-4 text-sm inline-flex">Ödeme Al</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($allPayments->count() > 0)
                    <tfoot>
                        <tr class="border-t border-neutral-200 dark:border-neutral-700 bg-neutral-50/50 dark:bg-neutral-900/30">
                            <td class="table-td font-semibold text-neutral-700 dark:text-neutral-300">Toplam ({{ $allPayments->count() }})</td>
                            <td class="table-td text-right font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ number_format($totalPaid ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="table-td col-hide-mobile" colspan="3"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Yan panel --}}
    <div class="space-y-4">
        <div class="card overflow-hidden">
            <div class="card-header">İletişim Bilgileri</div>
            <div class="p-4 sm:p-5">
                <dl class="space-y-3 text-sm">
                    @if($customer->email)
                    <div>
                        <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wide">E-posta</dt>
                        <dd class="mt-0.5"><a href="mailto:{{ $customer->email }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:text-emerald-600 break-all">{{ $customer->email }}</a></dd>
                    </div>
                    @endif
                    @if($customer->phone || $customer->phone2)
                    <div>
                        <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Telefon</dt>
                        <dd class="mt-0.5 font-medium text-neutral-900 dark:text-neutral-100">
                            @if($customer->phone)<a href="tel:{{ preg_replace('/\s+/', '', $customer->phone) }}" class="hover:text-emerald-600">{{ $customer->phone }}</a>@endif
                            @if($customer->phone && $customer->phone2)<span class="text-neutral-400 mx-1">/</span>@endif
                            @if($customer->phone2)<span>{{ $customer->phone2 }}</span>@endif
                        </dd>
                    </div>
                    @endif
                    @if($customer->full_address)
                    <div>
                        <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Adres</dt>
                        <dd class="mt-0.5 font-medium text-neutral-800 dark:text-neutral-200 whitespace-pre-wrap">{{ $customer->full_address }}</dd>
                    </div>
                    @endif
                    @if($customer->identityNumber || $customer->taxNumber || $customer->taxOffice)
                    <div>
                        <dt class="text-xs font-medium text-neutral-500 uppercase tracking-wide">TC / Vergi</dt>
                        <dd class="mt-0.5 font-medium text-neutral-800 dark:text-neutral-200">
                            {{ $customer->identityNumber ?: '—' }}
                            @if($customer->taxNumber || $customer->taxOffice)
                            <span class="block text-neutral-500 text-xs mt-0.5">{{ trim(($customer->taxNumber ?? '') . ' ' . ($customer->taxOffice ?? '')) }}</span>
                            @endif
                        </dd>
                    </div>
                    @endif
                </dl>
                <a href="{{ route('customers.edit', $customer) }}" class="mt-4 inline-flex text-xs font-medium text-emerald-600 hover:text-emerald-700">Bilgileri düzenle</a>
            </div>
        </div>

        @if($customer->quotes->count() > 0)
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Teklifler</span>
                <span class="text-xs font-normal text-neutral-500">{{ $customer->quotes->count() }}</span>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @foreach($customer->quotes->take(5) as $q)
                <a href="{{ route('quotes.show', $q) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                    <div class="min-w-0">
                        <p class="font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $q->quoteNumber }}</p>
                        <p class="text-xs text-neutral-500 mt-0.5">{{ ucfirst($q->status ?? '—') }}@if($q->branch) · {{ $q->branch->name }}@endif</p>
                    </div>
                    <p class="font-semibold tabular-nums text-neutral-700 dark:text-neutral-300 shrink-0">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</p>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(($serviceTickets ?? collect())->count() > 0)
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Servis (SSH)</span>
                <a href="{{ route('service-tickets.create') }}?customerId={{ $customer->id }}" class="text-xs font-normal text-emerald-600 hover:text-emerald-700">+ Yeni</a>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @foreach($serviceTickets->take(5) as $ticket)
                @php
                    $statusLabel = \App\Support\ServiceTicketStatus::label($ticket->status);
                    $statusClass = \App\Support\ServiceTicketStatus::badgeClass($ticket->status);
                @endphp
                <a href="{{ route('service-tickets.show', $ticket) }}" class="block p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $ticket->ticketNumber }}</p>
                            <p class="text-xs text-neutral-500 mt-0.5 truncate">{{ Str::limit($ticket->description ?? $ticket->issueType ?? '—', 40) }}@if($ticket->branch) · {{ $ticket->branch->name }}@endif</p>
                        </div>
                        <span class="badge {{ $statusClass }} shrink-0">{{ $statusLabel }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
