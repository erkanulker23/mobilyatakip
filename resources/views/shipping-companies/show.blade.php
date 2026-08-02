@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::shippingCompany($shippingCompany))
@section('content')
@php
    $totalPaid = $shippingCompany->payments->sum('amount');
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <nav class="flex items-center gap-2 text-sm text-neutral-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('shipping-companies.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Nakliye Firmaları</a>
            <span>/</span>
            <span class="text-neutral-700 dark:text-slate-300 font-medium">{{ $shippingCompany->name }}</span>
        </nav>
        <h1 class="page-title">{{ $shippingCompany->name }}</h1>
        <p class="page-desc">Nakliye firması detayları ve ödeme geçmişi</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @include('partials.action-buttons', ['edit' => route('shipping-companies.edit', $shippingCompany)])
        <a href="{{ route('shipping-company-payments.create', ['shippingCompanyId' => $shippingCompany->id]) }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            Nakliye Ödemesi Yap
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Toplam Ödenen</p>
                <p class="text-xl font-semibold text-emerald-600 dark:text-emerald-400 mt-1 tracking-tight">{{ number_format($totalPaid ?? 0, 0, ',', '.') }} ₺</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
    <div class="card overflow-hidden">
        <div class="card-header">İletişim Bilgileri</div>
        <div class="p-5">
            <dl class="space-y-3 text-sm">
                <div><dt class="form-label">Telefon</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $shippingCompany->phone ?: '—' }}</dd></div>
                <div><dt class="form-label">E-posta</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $shippingCompany->email ?: '—' }}</dd></div>
                <div><dt class="form-label">Adres</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $shippingCompany->address ?: '—' }}</dd></div>
                <div><dt class="form-label">Durum</dt><dd><span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full {{ $shippingCompany->isActive ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-neutral-500' }}">{{ $shippingCompany->isActive ? 'Aktif' : 'Pasif' }}</span></dd></div>
            </dl>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>Araç Filosu</span>
            <span class="text-xs font-normal text-neutral-500 dark:text-slate-400">{{ $shippingCompany->vehicles->count() }} araç</span>
        </div>
        <div class="p-5 space-y-6">
            <form method="POST" action="{{ route('shipping-companies.vehicles.store', $shippingCompany) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                @csrf
                <div>
                    <label class="form-label">Plaka *</label>
                    <input type="text" name="vehiclePlate" value="{{ old('vehiclePlate') }}" required class="form-input" placeholder="34 ABC 123">
                    @error('vehiclePlate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Sürücü Adı</label>
                    <input type="text" name="driverName" value="{{ old('driverName') }}" class="form-input" placeholder="Ad Soyad">
                </div>
                <div>
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="driverPhone" value="{{ old('driverPhone') }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel">
                    @error('driverPhone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Not</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="Opsiyonel">
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full md:w-auto">Araç Ekle</button>
                </div>
            </form>

            <div class="space-y-4">
                @forelse($shippingCompany->vehicles as $vehicle)
                <div class="rounded-xl border border-neutral-200 dark:border-slate-700 p-4">
                    <form method="POST" action="{{ route('shipping-companies.vehicles.update', [$shippingCompany, $vehicle]) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                        @csrf @method('PUT')
                        <div>
                            <label class="form-label">Plaka</label>
                            <input type="text" name="vehiclePlate" value="{{ old('vehiclePlate_'.$vehicle->id, $vehicle->vehiclePlate) }}" required class="form-input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Sürücü</label>
                            <input type="text" name="driverName" value="{{ old('driverName_'.$vehicle->id, $vehicle->driverName) }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="driverPhone" value="{{ old('driverPhone_'.$vehicle->id, $vehicle->driverPhone) }}" class="form-input" inputmode="tel">
                        </div>
                        <div>
                            <label class="form-label">Not</label>
                            <input type="text" name="notes" value="{{ old('notes_'.$vehicle->id, $vehicle->notes) }}" class="form-input">
                        </div>
                        <div class="flex items-center gap-2 pb-2">
                            <input type="checkbox" name="isActive" value="1" id="vehicleActive{{ $vehicle->id }}" {{ $vehicle->isActive ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-500 text-green-600 focus:ring-green-500">
                            <label for="vehicleActive{{ $vehicle->id }}" class="form-label mb-0">Aktif</label>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn-secondary">Kaydet</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('shipping-companies.vehicles.destroy', [$shippingCompany, $vehicle]) }}" class="mt-3" onsubmit="return confirm('Bu aracı silmek istediğinize emin misiniz?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">Aracı Sil</button>
                    </form>
                </div>
                @empty
                <p class="text-sm text-neutral-500 dark:text-slate-400 text-center py-6">Henüz araç tanımlanmamış. Yukarıdaki formdan plaka ve sürücü bilgisi ekleyin.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>Bu firmayla yapılan alışlar</span>
            <span class="text-xs font-normal text-neutral-500 dark:text-slate-400">{{ $shippingCompany->purchases->count() }} alış</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead><tr class="border-b border-neutral-100 dark:border-slate-700"><th class="table-th">Alış No</th><th class="table-th">Tedarikçi</th><th class="table-th">Tarih</th><th class="table-th">Plaka</th><th class="table-th">Şoför</th><th class="table-th text-right">Tutar</th><th class="table-th text-right">İşlem</th></tr></thead>
                <tbody>
                    @forelse($shippingCompany->purchases->where('isCancelled', false)->sortByDesc('purchaseDate') as $p)
                    <tr class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="table-td"><a href="{{ route('purchases.show', $p) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $p->purchaseNumber }}</a></td>
                        <td class="table-td">{{ $p->supplier?->name ?? '—' }}</td>
                        <td class="table-td">{{ $p->purchaseDate?->format('d.m.Y') }}</td>
                        <td class="table-td font-mono">{{ $p->vehiclePlate ?? '—' }}</td>
                        <td class="table-td">{{ $p->driverName ?? '—' }} @if($p->driverPhone)<span class="text-neutral-500">({{ $p->driverPhone }})</span>@endif</td>
                        <td class="table-td text-right font-medium">{{ number_format($p->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                        <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('purchases.show', $p), 'edit' => route('purchases.edit', $p), 'print' => route('purchases.print', $p)])</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="table-td text-center text-neutral-500 dark:text-slate-400 py-8">Henüz bu firmayla alış yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>Yapılan Ödemeler</span>
            <span class="text-xs font-normal text-neutral-500 dark:text-slate-400">{{ $shippingCompany->payments->count() }} ödeme</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead><tr class="border-b border-neutral-100 dark:border-neutral-800"><th class="table-th">Tarih</th><th class="table-th text-right">Tutar</th><th class="table-th">Tip</th><th class="table-th">Ne için</th><th class="table-th">Not</th><th class="table-th text-right w-40">İşlem</th></tr></thead>
                <tbody>
                    @php $pt = \App\Support\PaymentType::labels(); @endphp
                    @forelse($shippingCompany->payments->sortByDesc('paymentDate') as $pm)
                    <tr class="border-b border-neutral-50 dark:border-neutral-800 hover:bg-neutral-50/50 dark:hover:bg-neutral-800/50 transition-colors">
                        <td class="table-td">{{ $pm->paymentDate?->format('d.m.Y') }}</td>
                        <td class="table-td text-right font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($pm->amount ?? 0, 0, ',', '.') }} ₺</td>
                        <td class="table-td">{{ $pt[$pm->paymentType ?? ''] ?? ucfirst($pm->paymentType ?? '—') }}</td>
                        <td class="table-td text-sm">
                            @if($pm->purchase)
                            <a href="{{ route('purchases.show', $pm->purchase) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Alış: {{ $pm->purchase->purchaseNumber }}</a>
                            @elseif($pm->sale)
                            <a href="{{ route('sales.show', $pm->sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Satış: {{ $pm->sale->saleNumber }}</a>
                            @elseif($pm->serviceTicket)
                            <a href="{{ route('service-tickets.show', $pm->serviceTicket) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">SSH: {{ $pm->serviceTicket->ticketNumber }}</a>
                            @elseif($pm->paymentFor)
                            {{ $pm->paymentFor }}
                            @elseif($pm->reference)
                            {{ $pm->reference }}
                            @else
                            —
                            @endif
                        </td>
                        <td class="table-td text-neutral-500 text-sm">{{ Str::limit($pm->notes ?? '—', 30) }}</td>
                        <td class="table-td text-right">@include('partials.action-buttons', ['show' => route('shipping-company-payments.show', $pm), 'edit' => route('shipping-company-payments.edit', $pm)])</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="table-td text-center text-neutral-500 dark:text-slate-400 py-8">Henüz ödeme yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
