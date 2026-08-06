@extends('layouts.app')
@section('title', 'Termin Yaklaşanlar')
@section('content')
@php $filterDesc = $filters['label'] ?? null; @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Termin Tarihi Yaklaşanlar</h1>
        <p class="page-desc">
            @if(!empty($hideCommercialData))
            Teslim edilmemiş tüm siparişler — önümüzdeki {{ $days }} gün içinde termin gelenler (üretimde olanlarda not eklenebilir)
            @else
            Önümüzdeki {{ $days }} gün içinde termin tarihi gelen sipariş ve SSH formları
            @endif
            @if($filterDesc)
            <span class="text-neutral-500">· {{ $filterDesc }}</span>
            @endif
        </p>
    </div>
    @if(empty($hideCommercialData))
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.upcoming-due.print',
        'extraLinks' => [[
            'url' => route('reports.upcoming-due.shipment-print', request()->query()),
            'label' => 'Sevkiyat için Yazdır',
            'class' => 'btn-secondary',
            'target' => '_blank',
            'rel' => 'noopener',
        ]],
    ])
    @endif
</div>

<div class="card p-6 mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[160px]">
            <label class="form-label">Gün penceresi</label>
            <select name="days" class="form-select">
                @foreach([7, 14, 21, 30, 60, 90] as $d)
                <option value="{{ $d }}" {{ (int) $days === $d ? 'selected' : '' }}>{{ $d }} gün</option>
                @endforeach
            </select>
        </div>
        @if(empty($hideCommercialData))
        @include('reports.partials.sales-filters', ['showOdemeFilter' => false])
        @endif
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('reports.upcoming-due') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
</div>

@include('reports.partials.upcoming-due-content')
@endsection
