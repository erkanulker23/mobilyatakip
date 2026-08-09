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
        <form method="GET" action="{{ route('customers.index') }}" id="customerFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 items-end">
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

    @include('customers.partials.index-results', compact('customers'))
</div>

@push('scripts')
<script src="{{ route('assets.js', ['file' => 'list-search.js']) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.initListSearch({
        formId: 'customerFilterForm',
        inputId: 'customerSearchInput',
        resultsId: 'customersListResults',
        debounceMs: 650,
    });
});
</script>
@endpush
@endsection
