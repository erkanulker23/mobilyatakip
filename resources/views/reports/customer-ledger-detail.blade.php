@extends('layouts.app')
@section('title', 'Müşteri Cari Ekstre - ' . $customer->name)
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
            <a href="{{ route('reports.customer-ledger') }}" class="hover:text-neutral-900">Müşteri Cari</a>
            <span>/</span>
            <span class="text-neutral-700">{{ $customer->name }}</span>
        </div>
        <h1 class="page-title">Müşteri Cari Ekstre</h1>
        <p class="page-desc">{{ $customer->name }} — Hareket detayı</p>
    </div>
    @include('reports.partials.toolbar', [
        'printRoute' => 'reports.customer-ledger-detail.print',
        'printParams' => array_merge(['customer' => $customer], request()->query()),
        'extraLinks' => [
            ['url' => route('customers.show', $customer), 'label' => 'Müşteri Detay'],
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
