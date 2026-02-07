@extends('layouts.app')
@section('title', 'KDV Raporu')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">KDV Raporu</h1>
        <p class="text-slate-600 mt-1">Oran bazlı KDV özeti (satış ve alış)</p>
    </div>
    <a href="{{ route('reports.index') }}" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 font-medium">Raporlar</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[140px]">
            <label class="form-label">Başlangıç</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-input">
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Bitiş</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-input">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">Hesapla</button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-green-50">
            <h2 class="text-lg font-semibold text-slate-900">Satış KDV Dağılımı</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">KDV %</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Net</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">KDV</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($salesByRate as $rate => $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-medium">%{{ number_format($rate, 0) }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($row['net'], 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 text-right text-green-600 font-medium">{{ number_format($row['kdv'], 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 text-right font-medium">{{ number_format($row['total'], 2, ',', '.') }} ₺</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Bu dönemde satış yok.</td></tr>
                    @endforelse
                </tbody>
                @if(count($salesByRate) > 0)
                <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                    <tr class="font-semibold">
                        <td class="px-6 py-3">Toplam</td>
                        <td class="px-6 py-3 text-right">{{ number_format(collect($salesByRate)->sum('net'), 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-3 text-right text-green-600">{{ number_format(collect($salesByRate)->sum('kdv'), 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-3 text-right">{{ number_format(collect($salesByRate)->sum('total'), 2, ',', '.') }} ₺</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-amber-50">
            <h2 class="text-lg font-semibold text-slate-900">Alış KDV Dağılımı</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">KDV %</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Net</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">KDV</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($purchasesByRate as $rate => $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-medium">%{{ number_format($rate, 0) }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($row['net'], 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 text-right text-amber-600 font-medium">{{ number_format($row['kdv'], 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 text-right font-medium">{{ number_format($row['total'], 2, ',', '.') }} ₺</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Bu dönemde alış yok.</td></tr>
                    @endforelse
                </tbody>
                @if(count($purchasesByRate) > 0)
                <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                    <tr class="font-semibold">
                        <td class="px-6 py-3">Toplam</td>
                        <td class="px-6 py-3 text-right">{{ number_format(collect($purchasesByRate)->sum('net'), 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-3 text-right text-amber-600">{{ number_format(collect($purchasesByRate)->sum('kdv'), 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-3 text-right">{{ number_format(collect($purchasesByRate)->sum('total'), 2, ',', '.') }} ₺</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">Gider KDV Dağılımı (İndirilebilir)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">KDV %</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Net</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">KDV</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($expensesByRate as $rate => $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-medium">%{{ number_format($rate, 0) }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($row['net'], 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 text-right text-slate-600 font-medium">{{ number_format($row['kdv'], 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 text-right font-medium">{{ number_format($row['total'], 2, ',', '.') }} ₺</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Bu dönemde KDV’li gider yok.</td></tr>
                    @endforelse
                </tbody>
                @if(count($expensesByRate) > 0)
                <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                    <tr class="font-semibold">
                        <td class="px-6 py-3">Toplam</td>
                        <td class="px-6 py-3 text-right">{{ number_format(collect($expensesByRate)->sum('net'), 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-3 text-right text-slate-600">{{ number_format(collect($expensesByRate)->sum('kdv'), 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-3 text-right">{{ number_format(collect($expensesByRate)->sum('total'), 2, ',', '.') }} ₺</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@if(count($salesByRate) > 0 || count($purchasesByRate) > 0 || count($expensesByRate) > 0)
<div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Özet</h3>
    @php
        $giderKdv = collect($expensesByRate)->sum('kdv');
    @endphp
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><dt class="text-sm text-slate-500">Satış KDV Toplamı</dt><dd class="font-bold text-green-600">{{ number_format(collect($salesByRate)->sum('kdv'), 2, ',', '.') }} ₺</dd></div>
        <div><dt class="text-sm text-slate-500">Alış KDV Toplamı (İndirilebilir)</dt><dd class="font-bold text-amber-600">{{ number_format(collect($purchasesByRate)->sum('kdv'), 2, ',', '.') }} ₺</dd></div>
        <div><dt class="text-sm text-slate-500">Gider KDV Toplamı (İndirilebilir)</dt><dd class="font-bold text-slate-600">{{ number_format($giderKdv, 2, ',', '.') }} ₺</dd></div>
        <div><dt class="text-sm text-slate-500">Ödenecek KDV</dt><dd class="font-bold text-slate-900">{{ number_format(collect($salesByRate)->sum('kdv') - collect($purchasesByRate)->sum('kdv') - $giderKdv, 2, ',', '.') }} ₺</dd></div>
    </dl>
</div>
@endif
@endsection
