@extends('layouts.app')
@section('title', 'Tedarikçi Cari Özeti')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Tedarikçi Cari Hesap Özeti</h1>
        <p class="page-desc">Tedarikçi bakiyeleri</p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.supplier-ledger.print'])
</div>

<div class="card p-6 mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px]">
            <label class="form-label">Filtre</label>
            <select name="tip" class="form-select">
                <option value="">Tümü</option>
                <option value="borclu" {{ request('tip') === 'borclu' ? 'selected' : '' }}>Biz borçluyuz</option>
                <option value="alacakli" {{ request('tip') === 'alacakli' ? 'selected' : '' }}>Fazla ödeme (alacaklı)</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrele</button>
    </form>
</div>

<div class="card overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-neutral-200">
            <tr>
                <th class="table-th">Tedarikçi</th>
                <th class="table-th text-right">Borç (alış)</th>
                <th class="table-th text-right">Alacak (ödeme)</th>
                <th class="table-th text-right">Bakiye</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($suppliers as $r)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-medium text-neutral-900">{{ $r->supplier->name }}</td>
                <td class="px-6 py-4 text-right text-neutral-700">{{ number_format($r->borc, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 text-right text-neutral-700">{{ number_format($r->alacak, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 text-right font-medium {{ $r->bakiye > 0 ? 'text-red-600' : ($r->bakiye < 0 ? 'text-green-600' : 'text-slate-600') }}">{{ number_format($r->bakiye, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 flex gap-2">
                    <a href="{{ route('reports.supplier-ledger-detail', $r->supplier) }}" class="text-primary-600 hover:underline text-sm">Ekstre</a>
                    <a href="{{ route('suppliers.show', $r->supplier) }}" class="text-slate-600 hover:underline text-sm">Detay</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-neutral-500">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
