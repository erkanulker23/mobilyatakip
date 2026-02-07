@extends('layouts.app')
@section('title', $kasa->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('kasa.index') }}" class="hover:text-slate-700">Kasa</a>
                <span>/</span>
                <span class="text-slate-700">{{ $kasa->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $kasa->name }}</h1>
            <p class="text-slate-600 mt-1">{{ $kasa->type === 'banka' ? 'Banka Hesabı' : 'Kasa' }} • {{ $kasa->bankName ?? '' }}</p>
        </div>
        @include('partials.action-buttons', [
            'edit' => route('kasa.edit', $kasa),
        ])
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Hesap Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">Tip</dt><dd><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $kasa->type === 'banka' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">{{ $kasa->type === 'banka' ? 'Banka' : 'Kasa' }}</span></dd></div>
                <div><dt class="text-sm text-slate-500">Açılış Bakiyesi</dt><dd class="font-bold text-lg {{ ($kasa->openingBalance ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($kasa->openingBalance ?? 0, 2, ',', '.') }} ₺</dd></div>
                @if($kasa->bankName)
                <div><dt class="text-sm text-slate-500">Banka</dt><dd class="font-medium">{{ $kasa->bankName }}</dd></div>
                @endif
                @if($kasa->iban)
                <div><dt class="text-sm text-slate-500">IBAN</dt><dd class="font-mono text-sm break-all">{{ $kasa->iban }}</dd></div>
                @endif
                @if($kasa->accountNumber)
                <div><dt class="text-sm text-slate-500">Hesap No</dt><dd class="font-mono text-sm">{{ $kasa->accountNumber }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Hareketler</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $hareketler->total() }} hareket</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Açıklama</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Tutar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($hareketler as $h)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">{{ $h->movementDate?->format('d.m.Y H:i') }}</td>
                            <td class="px-6 py-4"><span class="text-sm">{{ ucfirst($h->type ?? '-') }}</span></td>
                            <td class="px-6 py-4 text-slate-600">{{ $h->description ?? '-' }}</td>
                            <td class="px-6 py-4 text-right font-medium {{ ($h->amount ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($h->amount ?? 0, 2, ',', '.') }} ₺</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Henüz hareket yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($hareketler->hasPages())
            <div class="px-6 py-3 border-t border-slate-200">{{ $hareketler->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
