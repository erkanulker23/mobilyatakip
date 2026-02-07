@extends('layouts.app')
@section('title', 'Tedarikçi Cari Özeti')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Tedarikçi Cari Hesap Özeti</h1>
        <p class="text-slate-600 mt-1">Tedarikçi bakiyeleri</p>
    </div>
    <a href="{{ route('reports.index') }}" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 font-medium">Raporlar</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <form method="get">
        <label class="form-label">Filtre:</label>
        <select name="tip" onchange="this.form.submit()" class="form-select max-w-xs">
            <option value="">Tümü</option>
            <option value="borclu" {{ request('tip') === 'borclu' ? 'selected' : '' }}>Biz borçluyuz (tedarikçi alacaklı)</option>
            <option value="alacakli" {{ request('tip') === 'alacakli' ? 'selected' : '' }}>Biz alacaklıyız</option>
        </select>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Tedarikçi</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Borç (alış)</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Alacak (ödeme)</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Bakiye</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($suppliers as $r)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-medium text-slate-900">{{ $r->supplier->name }}</td>
                <td class="px-6 py-4 text-right text-slate-700">{{ number_format($r->borc, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 text-right text-slate-700">{{ number_format($r->alacak, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 text-right font-medium {{ $r->bakiye > 0 ? 'text-red-600' : ($r->bakiye < 0 ? 'text-green-600' : 'text-slate-600') }}">{{ number_format($r->bakiye, 0, ',', '.') }} ₺</td>
                <td class="px-6 py-4 flex gap-2">
                    <a href="{{ route('reports.supplier-ledger-detail', $r->supplier) }}" class="text-primary-600 hover:underline text-sm">Ekstre</a>
                    <a href="{{ route('suppliers.show', $r->supplier) }}" class="text-slate-600 hover:underline text-sm">Detay</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Kayıt yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
