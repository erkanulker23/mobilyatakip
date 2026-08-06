@extends('layouts.app')
@section('title', 'Atölye - Üretimdeki Siparişler')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="page-title">Atölye</h1>
        <p class="page-desc">Üretimde olan siparişler — aşama ve eksiklik takibi</p>
    </div>
    <a href="{{ route('reports.upcoming-due') }}" class="btn-secondary">
        Termin Yaklaşanlar
    </a>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[200px] flex-1">
                <label class="form-label">Ara</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Sipariş no veya müşteri..." class="form-input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filtrele</button>
                <a href="{{ route('workshop.index') }}" class="btn-secondary">Temizle</a>
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th">Sipariş No</th>
                    @if(empty($hideCommercialData))<th class="table-th">Müşteri</th>@endif
                    <th class="table-th">Termin</th>
                    @if(empty($hideCommercialData))<th class="table-th">Satış Temsilcisi</th>@endif
                    <th class="table-th">Kayıt</th>
                    <th class="table-th">Eksiklik</th>
                    <th class="table-th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse($sales as $sale)
                @php $termin = \App\Support\SaleDelivery::terminListMeta($sale); @endphp
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                    <td class="table-td font-medium text-neutral-900 dark:text-white">{{ $sale->saleNumber }}</td>
                    @if(empty($hideCommercialData))<td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>@endif
                    <td class="table-td">
                        @if($sale->dueDate)
                        <span class="{{ $termin['class'] ?? '' }}">{{ $sale->dueDate->format('d.m.Y') }}</span>
                        @if($termin['suffix'] ?? null)
                        <span class="block text-xs text-neutral-500">{{ $termin['suffix'] }}</span>
                        @endif
                        @else
                        —
                        @endif
                    </td>
                    @if(empty($hideCommercialData))<td class="table-td">{{ $sale->personnel?->name ?? '—' }}</td>@endif
                    <td class="table-td">
                        @if($sale->production_stages_count > 0)
                        <span class="badge badge-blue">{{ $sale->production_stages_count }} kayıt</span>
                        @else
                        <span class="text-neutral-400 text-sm">Kayıt yok</span>
                        @endif
                    </td>
                    <td class="table-td">
                        @if(($sale->open_deficiencies_count ?? 0) > 0)
                        <span class="badge badge-amber">{{ $sale->open_deficiencies_count }} açık</span>
                        @else
                        <span class="text-neutral-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('workshop.show', $sale) }}" class="btn-view text-sm py-2 px-3">Detay</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="table-td text-center text-neutral-500 py-12">Üretimde sipariş bulunmuyor.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sales->hasPages())
    <div class="p-4 border-t border-neutral-100 dark:border-neutral-800">
        {{ $sales->links() }}
    </div>
    @endif
</div>
@endsection
