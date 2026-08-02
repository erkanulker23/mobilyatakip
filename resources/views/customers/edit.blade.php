@extends('layouts.app')
@section('title', 'Düzenle: ' . $customer->name)
@section('content')
@php
    $balanceKey = $customerBalance['key'] ?? 'siparis_yok';
    $hasSales = ($stats['salesCount'] ?? 0) > 0;
    $hasSsh = ($stats['sshCount'] ?? 0) > 0;
    $hasQuotes = ($stats['quotesCount'] ?? 0) > 0;
@endphp

<div class="mb-6">
    <nav class="flex items-center gap-2 text-sm text-neutral-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
        <a href="{{ route('customers.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Müşteriler</a>
        <span>/</span>
        <a href="{{ route('customers.show', $customer) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ $customer->name }}</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-slate-300 font-medium">Düzenle</span>
    </nav>
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="page-title">Müşteri Düzenle</h1>
            <p class="page-desc">{{ $customer->name }}</p>
        </div>
        <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border border-neutral-200 dark:border-slate-600 text-neutral-700 dark:text-slate-200 hover:bg-neutral-50 dark:hover:bg-slate-800 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            Detay Sayfası
        </a>
    </div>
</div>

{{-- Hızlı özet --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider mb-2">Borç / Alacak</p>
        <div class="flex items-start justify-between gap-3">
            <div>
                @include('partials.payment-status-badge', ['status' => ['key' => $balanceKey, 'label' => $customerBalance['label'] ?? '—']])
                <p class="text-lg font-semibold mt-2 tracking-tight {{ $balanceKey === 'borclu' ? 'text-red-600 dark:text-red-400' : ($balanceKey === 'alacakli' ? 'text-blue-600 dark:text-blue-400' : ($balanceKey === 'siparis_yok' ? 'text-neutral-400 dark:text-slate-500' : 'text-emerald-600 dark:text-emerald-400')) }}">
                    @if($balanceKey === 'borclu' || $balanceKey === 'alacakli')
                        {{ number_format($customerBalance['amount'] ?? 0, 0, ',', '.') }} ₺
                    @elseif($balanceKey === 'siparis_yok')
                        Sipariş yok
                    @else
                        Borcu yok
                    @endif
                </p>
                <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">{{ $customerBalance['description'] ?? '' }}</p>
            </div>
            <div class="p-2.5 rounded-xl shrink-0 {{ $balanceKey === 'borclu' ? 'bg-red-50 dark:bg-red-900/20' : ($balanceKey === 'alacakli' ? 'bg-blue-50 dark:bg-blue-900/20' : ($balanceKey === 'siparis_yok' ? 'bg-slate-100 dark:bg-slate-700' : 'bg-emerald-50 dark:bg-emerald-900/30')) }}">
                <svg class="w-5 h-5 {{ $balanceKey === 'borclu' ? 'text-red-600 dark:text-red-400' : ($balanceKey === 'alacakli' ? 'text-blue-600 dark:text-blue-400' : ($balanceKey === 'siparis_yok' ? 'text-neutral-400' : 'text-emerald-600 dark:text-emerald-400')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75m15.75 0h.75.75v-.75c0-.414-.336-.75-.75-.75h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
        </div>
        @if($hasSales)
        <dl class="mt-3 pt-3 border-t border-neutral-100 dark:border-slate-700 grid grid-cols-2 gap-2 text-xs">
            <div><dt class="text-neutral-500 dark:text-slate-400">Toplam satış</dt><dd class="font-semibold text-neutral-900 dark:text-white">{{ number_format($stats['totalSales'] ?? 0, 0, ',', '.') }} ₺</dd></div>
            <div><dt class="text-neutral-500 dark:text-slate-400">Toplam ödenen</dt><dd class="font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['totalPaid'] ?? 0, 0, ',', '.') }} ₺</dd></div>
        </dl>
        @endif
    </div>

    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider mb-2">Sipariş</p>
        <div class="flex items-start justify-between gap-3">
            <div>
                @if($hasSales)
                    <p class="text-lg font-semibold text-neutral-900 dark:text-white">Var</p>
                    <p class="text-sm text-neutral-600 dark:text-slate-300 mt-1">{{ $stats['salesCount'] }} aktif sipariş</p>
                @else
                    <p class="text-lg font-semibold text-neutral-400 dark:text-slate-500">Yok</p>
                    <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Henüz sipariş kaydı yok</p>
                @endif
            </div>
            <div class="p-2.5 rounded-xl shrink-0 {{ $hasSales ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700' }}">
                <svg class="w-5 h-5 {{ $hasSales ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
        </div>
        @if($hasSales)
        <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Siparişleri gör →</a>
        @endif
    </div>

    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider mb-2">SSH (Servis)</p>
        <div class="flex items-start justify-between gap-3">
            <div>
                @if($hasSsh)
                    <p class="text-lg font-semibold text-neutral-900 dark:text-white">Var</p>
                    <p class="text-sm text-neutral-600 dark:text-slate-300 mt-1">
                        {{ $stats['sshCount'] }} kayıt
                        @if(($stats['openSshCount'] ?? 0) > 0)
                            · <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $stats['openSshCount'] }} açık</span>
                        @endif
                    </p>
                @else
                    <p class="text-lg font-semibold text-neutral-400 dark:text-slate-500">Yok</p>
                    <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Servis kaydı bulunmuyor</p>
                @endif
            </div>
            <div class="p-2.5 rounded-xl shrink-0 {{ $hasSsh ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-slate-100 dark:bg-slate-700' }}">
                <svg class="w-5 h-5 {{ $hasSsh ? 'text-amber-600 dark:text-amber-400' : 'text-neutral-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"></path></svg>
            </div>
        </div>
        @if($hasSsh)
        <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">SSH kayıtları →</a>
        @else
        <a href="{{ route('service-tickets.create') }}?customerId={{ $customer->id }}" class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-neutral-600 dark:text-slate-300 hover:underline">+ Yeni SSH aç</a>
        @endif
    </div>

    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider mb-2">Teklif</p>
        <div class="flex items-start justify-between gap-3">
            <div>
                @if($hasQuotes)
                    <p class="text-lg font-semibold text-neutral-900 dark:text-white">Var</p>
                    <p class="text-sm text-neutral-600 dark:text-slate-300 mt-1">{{ $stats['quotesCount'] }} teklif</p>
                @else
                    <p class="text-lg font-semibold text-neutral-400 dark:text-slate-500">Yok</p>
                    <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Teklif kaydı yok</p>
                @endif
            </div>
            <div class="p-2.5 rounded-xl shrink-0 {{ $hasQuotes ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-slate-100 dark:bg-slate-700' }}">
                <svg class="w-5 h-5 {{ $hasQuotes ? 'text-blue-600 dark:text-blue-400' : 'text-neutral-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path></svg>
            </div>
        </div>
    </div>
</div>

@if($hasSsh)
<div class="card overflow-hidden mb-6">
    <div class="card-header flex items-center justify-between">
        <span>Son SSH Kayıtları</span>
        <a href="{{ route('customers.show', $customer) }}" class="text-xs font-normal text-neutral-500 hover:text-neutral-900 dark:hover:text-slate-200">Tümünü gör</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead><tr class="border-b border-neutral-100 dark:border-slate-700"><th class="table-th">No</th><th class="table-th">Durum</th><th class="table-th">Tarih</th></tr></thead>
            <tbody>
                @foreach($serviceTickets->take(5) as $ticket)
                @php
                    $statusLabels = ['open' => 'Açık', 'in_progress' => 'İşlemde', 'closed' => 'Kapalı', 'cancelled' => 'İptal'];
                    $statusLabel = $statusLabels[$ticket->status ?? ''] ?? ucfirst($ticket->status ?? '—');
                    $statusClass = match($ticket->status ?? '') {
                        'closed' => 'badge-green',
                        'in_progress' => 'badge-blue',
                        'cancelled' => 'badge-red',
                        default => 'badge-amber',
                    };
                @endphp
                <tr class="border-b border-slate-50 dark:border-slate-700/50">
                    <td class="table-td"><a href="{{ route('service-tickets.show', $ticket) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $ticket->ticketNumber }}</a></td>
                    <td class="table-td"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td class="table-td text-neutral-500 dark:text-slate-400">{{ $ticket->openedAt?->format('d.m.Y') ?? $ticket->createdAt?->format('d.m.Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-base font-semibold text-neutral-900 dark:text-white mb-5">İletişim ve Kimlik Bilgileri</h2>
            <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-5">
                @csrf @method('PUT')
                <div>
                    <label class="form-label">Ad Soyad *</label>
                    <input type="text" name="name" required value="{{ old('name', $customer->name) }}" class="form-input">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Telefon 1</label>
                        <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Telefon 2</label>
                        <input type="tel" name="phone2" value="{{ old('phone2', $customer->phone2) }}" class="form-input" placeholder="0216 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67 veya +90 555 123 45 67">
                        @error('phone2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    @php($addressIds = \App\Support\AddressFormat::fieldIds($customer))
                    @include('partials.address-fields', [
                        'address' => old('address', $customer->address),
                        'cityId' => $addressIds['cityId'],
                        'districtId' => $addressIds['districtId'],
                    ])
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">TC Kimlik No</label>
                        <input type="text" name="identityNumber" value="{{ old('identityNumber', $customer->identityNumber) }}" class="form-input" placeholder="11 haneli TC kimlik no" inputmode="numeric" maxlength="11" pattern="[0-9]{0,11}" title="Sadece 11 rakam giriniz">
                        @error('identityNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Vergi No</label>
                        <input type="text" name="taxNumber" value="{{ old('taxNumber', $customer->taxNumber) }}" class="form-input">
                        @error('taxNumber')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="form-label">Vergi Dairesi</label>
                    <input type="text" name="taxOffice" value="{{ old('taxOffice', $customer->taxOffice) }}" class="form-input">
                    @error('taxOffice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="isActive" value="1" {{ old('isActive', $customer->isActive) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                    <label class="form-label mb-0">Aktif müşteri</label>
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn-primary">Güncelle</button>
                    <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-neutral-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 font-medium">İptal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-3">Hızlı İşlemler</h3>
            <div class="space-y-2">
                <a href="{{ route('customers.show', $customer) }}" class="flex items-center gap-3 p-3 rounded-xl border border-neutral-200 dark:border-slate-600 hover:bg-neutral-50 dark:hover:bg-slate-800 transition-colors text-sm">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-neutral-100 dark:bg-slate-700 shrink-0">
                        <svg class="w-4 h-4 text-neutral-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </span>
                    <span><span class="font-medium text-neutral-900 dark:text-white block">Müşteri detayı</span><span class="text-xs text-neutral-500 dark:text-slate-400">Siparişler, tahsilatlar, SSH</span></span>
                </a>
                @if($balanceKey === 'borclu')
                <a href="{{ route('customer-payments.create') }}?customerId={{ $customer->id }}" class="flex items-center gap-3 p-3 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors text-sm">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 shrink-0">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </span>
                    <span><span class="font-medium text-emerald-800 dark:text-emerald-300 block">Tahsilat al</span><span class="text-xs text-emerald-600 dark:text-emerald-400">{{ number_format($customerBalance['amount'] ?? 0, 0, ',', '.') }} ₺ kalan borç</span></span>
                </a>
                @endif
                <a href="{{ route('service-tickets.create') }}?customerId={{ $customer->id }}" class="flex items-center gap-3 p-3 rounded-xl border border-neutral-200 dark:border-slate-600 hover:bg-neutral-50 dark:hover:bg-slate-800 transition-colors text-sm">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/20 shrink-0">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </span>
                    <span><span class="font-medium text-neutral-900 dark:text-white block">Yeni SSH aç</span><span class="text-xs text-neutral-500 dark:text-slate-400">Servis kaydı oluştur</span></span>
                </a>
                <a href="{{ route('customers.print', $customer) }}" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 rounded-xl border border-neutral-200 dark:border-slate-600 hover:bg-neutral-50 dark:hover:bg-slate-800 transition-colors text-sm">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-neutral-100 dark:bg-slate-700 shrink-0">
                        <svg class="w-4 h-4 text-neutral-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </span>
                    <span><span class="font-medium text-neutral-900 dark:text-white block">Extre yazdır</span><span class="text-xs text-neutral-500 dark:text-slate-400">Müşteri hesap özeti</span></span>
                </a>
            </div>
        </div>

        @if($hasSales)
        <div class="card overflow-hidden">
            <div class="card-header">Son Siparişler</div>
            <div class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($customer->sales->where('isCancelled', false)->sortByDesc('saleDate')->take(3) as $sale)
                @php $saleStatus = \App\Support\CustomerBalance::saleStatus($sale); @endphp
                <a href="{{ route('sales.show', $sale) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="min-w-0">
                        <p class="font-medium text-sm text-neutral-900 dark:text-white truncate">{{ $sale->saleNumber }}</p>
                        <p class="text-xs text-neutral-500 dark:text-slate-400">{{ $sale->saleDate?->format('d.m.Y') }} · {{ number_format($sale->grandTotal, 0, ',', '.') }} ₺</p>
                    </div>
                    @include('partials.payment-status-badge', ['status' => $saleStatus])
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
