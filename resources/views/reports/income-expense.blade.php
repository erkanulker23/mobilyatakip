@extends('layouts.app')
@section('title', 'Gelir – Gider Raporu')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Gelir – Gider Raporu</h1>
        <p class="text-slate-600 mt-1">Tarih aralığına göre gelir ve gider özeti</p>
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

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Kalem</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Tutar</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            <tr><td class="px-6 py-4 text-slate-700">Satış hasılatı (dönem)</td><td class="px-6 py-4 text-right font-medium">{{ number_format($gelir, 2) }} ₺</td></tr>
            <tr><td class="px-6 py-4 text-slate-700">Tahsilat (dönem)</td><td class="px-6 py-4 text-right font-medium">{{ number_format($tahsilat, 2) }} ₺</td></tr>
            <tr><td class="px-6 py-4 text-slate-700">Gider</td><td class="px-6 py-4 text-right font-medium">- {{ number_format($gider, 2) }} ₺</td></tr>
            <tr><td class="px-6 py-4 text-slate-700">Tedarikçi ödemesi</td><td class="px-6 py-4 text-right font-medium">- {{ number_format($tedarikciOdeme, 2) }} ₺</td></tr>
            <tr class="bg-slate-50 font-semibold"><td class="px-6 py-4 text-slate-800">Net nakit etkisi (tahsilat − gider − tedarikçi ödemesi)</td><td class="px-6 py-4 text-right">{{ number_format($tahsilat - $gider - $tedarikciOdeme, 2) }} ₺</td></tr>
        </tbody>
    </table>
</div>
@endsection
