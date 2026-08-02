@extends('layouts.app')
@section('title', 'Müşteriler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <h1 class="page-title">Müşteriler</h1>
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

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" id="customerFilterForm">
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" id="customerSearchInput" placeholder="Müşteri adı, telefon veya adres ara..." value="{{ request('search') }}" class="form-input pl-11" autocomplete="off">
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100">
                    <th class="table-th">İsim</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th col-hide-mobile">Adres</th>
                    <th class="table-th">Cari</th>
                    <th class="table-th col-hide-mobile">Tarih</th>
                    <th class="table-th text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                @php $cari = \App\Support\CustomerBalance::customerStatus((float) ($c->totalSales ?? 0), (float) ($c->totalPaid ?? 0)); @endphp
                <tr class="border-b border-neutral-50 hover:bg-neutral-50/50 transition-colors">
                    <td class="table-td">
                        <a href="{{ route('customers.show', $c) }}" class="font-medium text-neutral-900 hover:underline">{{ $c->name }}</a>
                    </td>
                    <td class="table-td cell-phone">{{ $c->phone ?? '—' }}</td>
                    <td class="table-td text-neutral-500 max-w-xs truncate col-hide-mobile">{{ $c->full_address ?: '—' }}</td>
                    <td class="table-td">
                        @include('partials.payment-status-badge', ['status' => ['key' => $cari['key'], 'label' => $cari['label']]])
                        @if($cari['amount'] > 0)
                        <span class="block text-xs text-neutral-500 mt-0.5">{{ number_format($cari['amount'], 0, ',', '.') }} ₺</span>
                        @endif
                    </td>
                    <td class="table-td text-neutral-500 col-hide-mobile">{{ $c->createdAt?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'edit' => route('customers.edit', $c),
                            'destroy' => route('customers.destroy', $c),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <p class="text-neutral-500 text-sm">Kayıt bulunamadı.</p>
                        <a href="{{ route('customers.create') }}" class="btn-primary mt-4">Yeni müşteri ekle</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="px-4 sm:px-5 py-3 border-t border-neutral-100">{{ $customers->links() }}</div>
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
        timer = setTimeout(() => form.submit(), 400);
    });

    if (input.value !== '') {
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }
})();
</script>
@endsection
