@extends('layouts.app')
@section('title', 'Tedarikçi Cari Ekstre - ' . $supplier->name)
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
            <a href="{{ route('reports.supplier-ledger') }}" class="hover:text-neutral-900">Tedarikçi Cari</a>
            <span>/</span>
            <span class="text-neutral-700">{{ $supplier->name }}</span>
        </div>
        <h1 class="page-title">Tedarikçi Cari Ekstre</h1>
        <p class="page-desc">{{ $supplier->name }} — Hareket detayı</p>
    </div>
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.supplier-ledger-detail.print',
        'printParams' => array_merge(['supplier' => $supplier], request()->query()),
        'extraLinks' => [
            ['url' => route('suppliers.show', $supplier), 'label' => 'Tedarikçi Detay'],
        ],
    ])
</div>

<div class="card p-6 mb-6">
    @include('reports.partials.date-filters', ['showYear' => false])
</div>

<div class="card overflow-hidden">
    @include('reports.partials.ledger-detail-table')
</div>
@endsection
