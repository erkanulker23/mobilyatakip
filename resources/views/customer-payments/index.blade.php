@extends('layouts.app')
@section('title', 'Tahsilat Kayıtları')
@section('content')
@php
    $paymentLabels = \App\Support\PaymentType::labels();
    $hasFilters = request()->hasAny(['search', 'customerId', 'paymentType', 'kasaId', 'from', 'to']);
    $today = now()->format('Y-m-d');
    $monthStart = now()->startOfMonth()->format('Y-m-d');
    $filterChip = fn (array $params) => route('customer-payments.create', array_filter(array_merge(['list' => 1], request()->only(['search', 'customerId', 'paymentType', 'kasaId', 'from', 'to']), $params)));
    $paymentBadgeClass = fn (?string $type) => match ($type) {
        'nakit' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        'havale' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'kredi_karti' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
        'tedarikciye_ode' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    };
@endphp

<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="page-title">Tahsilat Kayıtları</h1>
            <p class="page-desc mt-1">Müşterilerden alınan tahsilatları görüntüleyin, filtreleyin ve yönetin</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('customer-payments.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 text-sm font-medium text-neutral-700 dark:text-neutral-200 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Ödeme Al
            </a>
            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-neutral-900 dark:bg-emerald-600 text-white text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Tahsilat Kayıtları
            </span>
            <a href="{{ route('customer-payments.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                Yeni Tahsilat
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <a href="{{ $filterChip(['from' => $today, 'to' => $today]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ request('from') === $today && request('to') === $today ? 'ring-2 ring-emerald-300 dark:ring-emerald-700' : ($todayTotal > 0 ? 'ring-1 ring-emerald-200 dark:ring-emerald-800/60' : '') }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Bugün</p>
        <p class="text-xl sm:text-2xl font-semibold tabular-nums {{ $todayTotal > 0 ? 'text-emerald-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ number_format($todayTotal, 0, ',', '.') }} ₺</p>
    </a>
    <a href="{{ $filterChip(['from' => $monthStart, 'to' => $today]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ request('from') === $monthStart && request('to') === $today ? 'ring-2 ring-blue-300 dark:ring-blue-700' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Bu ay</p>
        <p class="text-xl sm:text-2xl font-semibold tabular-nums text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($monthTotal, 0, ',', '.') }} ₺</p>
    </a>
    <div class="card p-4 {{ $hasFilters ? 'ring-1 ring-neutral-300 dark:ring-neutral-600' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">{{ $hasFilters ? 'Filtrelenen' : 'Liste toplamı' }}</p>
        <p class="text-xl sm:text-2xl font-semibold tabular-nums text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($totalAmount, 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Kayıt</p>
        <p class="text-xl sm:text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($payments->total(), 0, ',', '.') }}</p>
        @if($payments->hasPages())
        <p class="text-xs text-neutral-400 mt-1">Sayfa {{ $payments->currentPage() }}/{{ $payments->lastPage() }}</p>
        @endif
    </div>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" action="{{ route('customer-payments.create') }}" id="paymentFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
            <input type="hidden" name="list" value="1">
            <div class="sm:col-span-2 xl:col-span-2">
                <label for="paymentSearchInput" class="form-label">Ara</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="search" name="search" id="paymentSearchInput" value="{{ request('search') }}" placeholder="Müşteri, fatura no, referans..." class="form-input pl-10 w-full" autocomplete="off">
                </div>
            </div>
            <div>
                <label class="form-label">Müşteri</label>
                <select name="customerId" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Ödeme tipi</label>
                <select name="paymentType" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach(\App\Support\PaymentType::labels() as $value => $label)
                    <option value="{{ $value }}" {{ request('paymentType') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Başlangıç</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input w-full">
            </div>
            <div>
                <label class="form-label">Bitiş</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input w-full">
            </div>
            <div>
                <label class="form-label">Kasa</label>
                <select name="kasaId" class="form-select w-full">
                    <option value="">Tüm kasalar</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ request('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:col-span-2 xl:col-span-6">
                <button type="submit" class="btn-primary w-full sm:w-auto justify-center">Filtrele</button>
                <a href="{{ route('customer-payments.create', ['list' => 1]) }}" class="btn-secondary w-full sm:w-auto justify-center">Temizle</a>
            </div>
        </form>

        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
            <span class="text-xs text-neutral-400 self-center mr-1">Hızlı filtre:</span>
            <a href="{{ route('customer-payments.create', ['list' => 1]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ !$hasFilters ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Tümü</a>
            <a href="{{ $filterChip(['from' => $today, 'to' => $today, 'paymentType' => null, 'customerId' => null, 'kasaId' => null, 'search' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('from') === $today && request('to') === $today && !request('search') && !request('customerId') && !request('paymentType') && !request('kasaId') ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Bugün</a>
            <a href="{{ $filterChip(['from' => $monthStart, 'to' => $today, 'paymentType' => null, 'customerId' => null, 'kasaId' => null, 'search' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('from') === $monthStart && request('to') === $today && !request('search') && !request('customerId') && !request('paymentType') && !request('kasaId') ? 'bg-blue-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Bu ay</a>
            @foreach(['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı'] as $ptKey => $ptLabel)
            <a href="{{ $filterChip(['paymentType' => $ptKey, 'from' => null, 'to' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('paymentType') === $ptKey ? 'bg-violet-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">{{ $ptLabel }}</a>
            @endforeach
        </div>
    </div>

    <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3 text-sm text-neutral-500">
        <span>
            @if($payments->total() === 0)
                Kayıt bulunamadı
            @elseif($payments->total() === 1)
                1 tahsilat
            @else
                {{ number_format($payments->total(), 0, ',', '.') }} tahsilat
                @if($payments->hasPages())
                    · sayfa {{ $payments->currentPage() }}/{{ $payments->lastPage() }}
                @endif
            @endif
        </span>
        @if($hasFilters)
            <span class="text-xs text-neutral-400">Filtre uygulanıyor</span>
        @endif
    </div>

    <div class="overflow-x-auto -mx-px">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th">Tarih</th>
                    <th class="table-th min-w-[10rem]">Müşteri</th>
                    <th class="table-th col-hide-mobile">Fatura</th>
                    <th class="table-th col-hide-mobile">Ödeme tipi</th>
                    <th class="table-th col-hide-mobile">Kasa</th>
                    <th class="table-th col-hide-mobile">Referans</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th text-right w-36 sm:w-44">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                @php
                    $customerName = $payment->customer?->name ?? '';
                    $initial = $customerName !== '' ? mb_strtoupper(mb_substr($customerName, 0, 1)) : '?';
                    $avatarHue = $customerName !== '' ? crc32($customerName) % 360 : 0;
                @endphp
                <tr class="border-b border-neutral-50 dark:border-neutral-800/60 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/40 transition-colors">
                    <td class="table-td whitespace-nowrap">
                        <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $payment->paymentDate?->format('d.m.Y') ?? '—' }}</span>
                    </td>
                    <td class="table-td min-w-[10rem]">
                        @if($payment->customer)
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full shrink-0 flex items-center justify-center text-sm font-semibold text-white col-hide-mobile" style="background-color: hsl({{ $avatarHue }}, 45%, 42%);">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('customers.show', $payment->customer) }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:underline truncate block">{{ $payment->customer->name }}</a>
                                @if($payment->sale)
                                <a href="{{ route('sales.show', $payment->sale) }}" class="block mt-0.5 text-xs text-neutral-400 font-mono md:hidden truncate max-w-[12rem]">{{ $payment->sale->saleNumber }}</a>
                                @endif
                            </div>
                        </div>
                        @else
                        <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                    <td class="table-td col-hide-mobile">
                        @if($payment->sale)
                        <a href="{{ route('sales.show', $payment->sale) }}" class="text-neutral-700 dark:text-neutral-300 hover:underline font-mono text-sm">{{ $payment->sale->saleNumber }}</a>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">Genel</span>
                        @endif
                    </td>
                    <td class="table-td col-hide-mobile">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium {{ $paymentBadgeClass($payment->paymentType) }}">
                            {{ $paymentLabels[$payment->paymentType ?? ''] ?? ucfirst($payment->paymentType ?? '—') }}
                        </span>
                    </td>
                    <td class="table-td text-neutral-600 dark:text-neutral-400 col-hide-mobile">{{ $payment->kasa?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-500 dark:text-neutral-400 max-w-[10rem] truncate col-hide-mobile" title="{{ $payment->reference }}">{{ $payment->reference ?: '—' }}</td>
                    <td class="table-td text-right font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums whitespace-nowrap">{{ number_format($payment->amount ?? 0, 0, ',', '.') }} ₺</td>
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
                        <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <p class="text-neutral-600 dark:text-neutral-300 font-medium">Tahsilat kaydı bulunamadı</p>
                        <p class="text-sm text-neutral-500 mt-1">{{ $hasFilters ? 'Filtreleri değiştirmeyi veya temizlemeyi deneyin.' : 'İlk tahsilat kaydını oluşturun.' }}</p>
                        @if($hasFilters)
                            <a href="{{ route('customer-payments.create', ['list' => 1]) }}" class="btn-secondary mt-4 text-sm">Filtreleri temizle</a>
                        @else
                            <a href="{{ route('customer-payments.create') }}" class="btn-primary mt-4 text-sm">Tahsilat Al</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 sm:px-5 py-3 border-t border-neutral-100 dark:border-neutral-800">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>

<script>
(function () {
    const input = document.getElementById('paymentSearchInput');
    const form = document.getElementById('paymentFilterForm');
    if (!input || !form) return;

    let timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 450);
    });

    if (input.value !== '') {
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }
})();
</script>
@endsection
