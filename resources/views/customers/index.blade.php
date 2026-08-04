@extends('layouts.app')
@section('title', 'Müşteriler')

@section('content')
<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Müşteriler</h1>
        <p class="page-desc mt-1">Müşteri kayıtları, iletişim bilgileri ve cari durumu</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('customers.excel.export') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Excel'e Aktar
        </a>
        <form action="{{ route('customers.excel.import') }}" method="POST" enctype="multipart/form-data" class="inline-flex">
            @csrf
            <label class="btn-secondary cursor-pointer text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 16m4-4v12"></path></svg>
                Excel'den Aktar
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" onchange="this.form.submit()">
            </label>
        </form>
        <a href="{{ route('customers.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
            Yeni Müşteri
        </a>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam müşteri</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Aktif</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1">{{ $stats['active'] }}</p>
    </div>
    <a href="{{ route('customers.index', ['balance' => 'borclu']) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ request('balance') === 'borclu' ? 'ring-2 ring-red-300 dark:ring-red-700' : ($stats['debtors'] > 0 ? 'ring-1 ring-red-200 dark:ring-red-800/60' : '') }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Borçlu</p>
        <p class="text-2xl font-semibold {{ $stats['debtors'] > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ $stats['debtors'] }}</p>
    </a>
    <a href="{{ route('customers.index', ['balance' => 'alacakli']) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ request('balance') === 'alacakli' ? 'ring-2 ring-blue-300 dark:ring-blue-700' : ($stats['creditors'] > 0 ? 'ring-1 ring-blue-200 dark:ring-blue-800/60' : '') }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Alacaklı</p>
        <p class="text-2xl font-semibold {{ $stats['creditors'] > 0 ? 'text-blue-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ $stats['creditors'] }}</p>
    </a>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" id="customerFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 items-end">
            <div class="sm:col-span-2 xl:col-span-2">
                <label for="customerSearchInput" class="form-label">Ara</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" name="search" id="customerSearchInput" placeholder="Ad, telefon, e-posta, il, ilçe..." value="{{ request('search') }}" class="form-input pl-10 w-full" autocomplete="off">
                </div>
            </div>
            <div>
                <label class="form-label">Cari durum</label>
                <select name="balance" class="form-select w-full">
                    <option value="">Tümü</option>
                    <option value="borclu" {{ request('balance') === 'borclu' ? 'selected' : '' }}>Borçlu</option>
                    <option value="alacakli" {{ request('balance') === 'alacakli' ? 'selected' : '' }}>Alacaklı</option>
                    <option value="borcu_yok" {{ request('balance') === 'borcu_yok' ? 'selected' : '' }}>Borcu yok</option>
                    <option value="siparis_yok" {{ request('balance') === 'siparis_yok' ? 'selected' : '' }}>Sipariş yok</option>
                </select>
            </div>
            <div>
                <label class="form-label">Kayıt durumu</label>
                <select name="isActive" class="form-select w-full">
                    <option value="">Tümü</option>
                    <option value="1" {{ request('isActive') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('isActive') === '0' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:col-span-2 xl:col-span-1">
                <button type="submit" class="btn-primary w-full sm:flex-1 justify-center">Filtrele</button>
                <a href="{{ route('customers.index') }}" class="btn-secondary w-full sm:flex-1 justify-center">Temizle</a>
            </div>
        </form>
    </div>

    <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3 text-sm text-neutral-500">
        <span>
            @if($customers->total() === 0)
                Kayıt bulunamadı
            @elseif($customers->total() === 1)
                1 müşteri
            @else
                {{ number_format($customers->total(), 0, ',', '.') }} müşteri
                @if($customers->hasPages())
                    · sayfa {{ $customers->currentPage() }}/{{ $customers->lastPage() }}
                @endif
            @endif
        </span>
        @if(request()->hasAny(['search', 'balance', 'isActive']))
            <span class="text-xs text-neutral-400">Filtre uygulanıyor</span>
        @endif
    </div>

    <div class="overflow-x-auto -mx-px">
        <table class="min-w-full customers-index-table">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th">Müşteri</th>
                    <th class="table-th col-hide-mobile">Telefon</th>
                    <th class="table-th col-hide-mobile">İl / İlçe</th>
                    <th class="table-th col-hide-mobile text-center">Sipariş</th>
                    <th class="table-th">Cari</th>
                    <th class="table-th col-hide-mobile">Kayıt</th>
                    <th class="table-th text-right w-36 sm:w-44">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                @php
                    $cari = \App\Support\CustomerBalance::customerStatus((float) ($c->totalSales ?? 0), (float) ($c->totalPaid ?? 0));
                    $initial = mb_strtoupper(mb_substr($c->name, 0, 1));
                    $avatarHue = crc32($c->name) % 360;
                    $location = trim(collect([$c->city?->name, $c->district?->name])->filter()->implode(' / '));
                @endphp
                <tr class="border-b border-neutral-50 dark:border-neutral-800/60 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/40 transition-colors {{ !($c->isActive ?? true) ? 'opacity-70' : '' }}">
                    <td class="table-td min-w-[10rem]">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full shrink-0 flex items-center justify-center text-sm font-semibold text-white" style="background-color: hsl({{ $avatarHue }}, 45%, 42%);">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('customers.show', $c) }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:underline truncate block">{{ $c->name }}</a>
                                @if(!($c->isActive ?? true))
                                    <span class="text-xs text-neutral-400">Pasif</span>
                                @endif
                                @if($c->phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}" class="block mt-0.5 text-xs text-neutral-500 md:hidden cell-phone">{{ $c->phone }}</a>
                                @endif
                                @if($location)
                                    <span class="block mt-0.5 text-xs text-neutral-400 truncate md:hidden max-w-[14rem]">{{ $location }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="table-td col-hide-mobile cell-phone">
                        @if($c->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}" class="text-neutral-700 dark:text-neutral-300 hover:underline">{{ $c->phone }}</a>
                            @if($c->phone2)
                                <span class="block text-xs text-neutral-400 mt-0.5">{{ $c->phone2 }}</span>
                            @endif
                        @else
                            <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                    <td class="table-td text-neutral-500 dark:text-neutral-400 whitespace-nowrap col-hide-mobile">{{ $location ?: '—' }}</td>
                    <td class="table-td text-center text-neutral-600 dark:text-neutral-400 col-hide-mobile">{{ (int) ($c->ordersCount ?? 0) }}</td>
                    <td class="table-td min-w-[6.5rem]">
                        <div class="flex flex-col items-start gap-1">
                            @include('partials.payment-status-badge', ['status' => ['key' => $cari['key'], 'label' => $cari['label']]])
                            @if($cari['amount'] > 0)
                                <span class="text-xs font-medium tabular-nums {{ $cari['key'] === 'borclu' ? 'text-red-600 dark:text-red-400' : ($cari['key'] === 'alacakli' ? 'text-blue-600 dark:text-blue-400' : 'text-neutral-500') }}">
                                    {{ number_format($cari['amount'], 0, ',', '.') }} ₺
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="table-td text-neutral-500 dark:text-neutral-400 whitespace-nowrap col-hide-mobile">{{ $c->createdAt?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('customers.show', $c),
                            'edit' => route('customers.edit', $c),
                            'destroy' => route('customers.destroy', $c),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <p class="text-neutral-500 text-sm">Aramanıza uygun müşteri bulunamadı.</p>
                        @if(request()->hasAny(['search', 'balance', 'isActive']))
                            <a href="{{ route('customers.index') }}" class="btn-secondary mt-4 text-sm">Filtreleri temizle</a>
                        @else
                            <a href="{{ route('customers.create') }}" class="btn-primary mt-4 text-sm">İlk müşteriyi ekle</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
    <div class="px-4 sm:px-5 py-3 border-t border-neutral-100 dark:border-neutral-800">{{ $customers->links() }}</div>
    @endif
</div>

<script>
(function () {
    const input = document.getElementById('customerSearchInput');
    const form = document.getElementById('customerFilterForm');
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
