@extends('layouts.app')
@section('title', 'Atölye - Üretim Takibi')
@section('content')
@php
    use App\Models\SaleProductionStage;
    use App\Support\SaleDelivery;

    $scope = $scope ?? 'uretim';
@endphp
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="page-title">Atölye</h1>
        <p class="page-desc">Sipariş bazında ürünler, atölye aşamaları ve eksiklik kayıtları</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(auth()->user()?->isWorkshop() && ! auth()->user()?->isAdmin())
        <a href="{{ route('workshop.dashboard') }}" class="btn-secondary text-sm">Atölyem</a>
        @endif
        <a href="{{ route('reports.upcoming-due') }}" class="btn-secondary text-sm">Termin Yaklaşanlar</a>
    </div>
</div>

@if(empty($productionStagesReady))
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Üretim aşaması kayıtları henüz aktif değil. Sistem yöneticisinin <code class="text-xs">php artisan migrate --force</code> çalıştırması gerekiyor.
</div>
@endif

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif

<div class="card overflow-hidden mb-6">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800 space-y-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('workshop.index', array_merge(request()->except('scope', 'page'), ['scope' => 'uretim'])) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $scope === 'uretim' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                Üretimde
            </a>
            <a href="{{ route('workshop.index', array_merge(request()->except('scope', 'page'), ['scope' => 'tumu'])) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $scope === 'tumu' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                Tüm kayıtlar
            </a>
            <a href="{{ route('workshop.index', array_merge(request()->except('scope', 'page'), ['scope' => 'tamamlanan'])) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $scope === 'tamamlanan' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                Atölyeden çıkanlar
            </a>
        </div>
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <div class="min-w-[200px] flex-1">
                <label class="form-label">Ara</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Sipariş, müşteri veya not..." class="form-input">
            </div>
            @if(!empty($productionStagesReady))
            <div class="min-w-[140px]">
                <label class="form-label">Kayıt türü</label>
                <select name="type" class="form-select">
                    <option value="">Tümü</option>
                    <option value="{{ SaleProductionStage::TYPE_STAGE }}" {{ request('type') === SaleProductionStage::TYPE_STAGE ? 'selected' : '' }}>Aşama</option>
                    <option value="{{ SaleProductionStage::TYPE_DEFICIENCY }}" {{ request('type') === SaleProductionStage::TYPE_DEFICIENCY ? 'selected' : '' }}>Eksiklik</option>
                </select>
            </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filtrele</button>
                <a href="{{ route('workshop.index', ['scope' => $scope]) }}" class="btn-secondary">Temizle</a>
            </div>
        </form>
    </div>

    @if($sales->isEmpty())
    <div class="px-6 py-16 text-center">
        <p class="text-neutral-500">
            @if($scope === 'tamamlanan')
            Atölyeden çıkmış sipariş bulunmuyor.
            @elseif($scope === 'tumu')
            Atölye kaydı olan sipariş bulunmuyor.
            @else
            Üretimde sipariş bulunmuyor.
            @endif
        </p>
    </div>
    @else
    <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
        @foreach($sales as $sale)
            @include('workshop.partials.sale-workshop-summary', ['sale' => $sale])
        @endforeach
    </div>
    @endif

    @if($sales->hasPages())
    <div class="p-4 border-t border-neutral-100 dark:border-neutral-800">
        {{ $sales->links() }}
    </div>
    @endif
</div>
@endsection
