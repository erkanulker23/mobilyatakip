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
                <div><dt class="text-sm text-slate-500">Açılış Bakiyesi</dt><dd class="font-bold text-lg {{ ($kasa->openingBalance ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format((float)($kasa->openingBalance ?? 0), 0, ',', '.') }} ₺</dd></div>
                <div><dt class="text-sm text-slate-500">Güncel Bakiye</dt><dd class="font-bold text-xl {{ ($guncelBakiye ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($guncelBakiye ?? 0, 0, ',', '.') }} ₺</dd></div>
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
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="min-w-[140px]">
                        <label for="kasa-filter-payment_type" class="form-label text-xs">Ödeme tipi</label>
                        <select id="kasa-filter-payment_type" name="payment_type" class="form-select text-sm">
                            <option value="">Tümü</option>
                            @foreach($paymentTypes ?? [] as $value => $label)
                            <option value="{{ $value }}" {{ request('payment_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[180px] flex-1">
                        <label for="kasa-filter-cari" class="form-label text-xs">Cari (müşteri / tedarikçi)</label>
                        <input id="kasa-filter-cari" type="text" name="cari" value="{{ request('cari') }}" placeholder="İsimle ara..." class="form-input text-sm">
                    </div>
                    <div class="min-w-[120px]">
                        <label for="kasa-filter-date_from" class="form-label text-xs">Tarih başlangıç</label>
                        <input id="kasa-filter-date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
                    </div>
                    <div class="min-w-[120px]">
                        <label for="kasa-filter-date_to" class="form-label text-xs">Tarih bitiş</label>
                        <input id="kasa-filter-date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary text-sm">Filtrele</button>
                        <a href="{{ route('kasa.show', $kasa) }}" class="btn-secondary text-sm">Temizle</a>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Cari</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ödeme tipi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Açıklama</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Tutar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($hareketler as $h)
                        @php
                            $cariName = null;
                            $cariUrl = null;
                            $paymentTypeLabel = null;
                            $paymentTypeValue = null;
                            $refId = $h->refId !== null && $h->refId !== '' ? (is_numeric($h->refId) ? (int) $h->refId : $h->refId) : null;
                            if ($h->refType === 'customer_payment' && $refId !== null && isset($customerPayments[$refId])) {
                                $cp = $customerPayments[$refId];
                                $cariName = $cp->customer?->name ?? 'Müşteri';
                                $cariUrl = $cp->customer ? route('customers.show', $cp->customer) : null;
                                $paymentTypeValue = $cp->paymentType ?? null;
                                $paymentTypeLabel = $paymentTypes[$paymentTypeValue ?? ''] ?? $paymentTypeValue ?? '—';
                            } elseif ($h->refType === 'supplier_payment' && $refId !== null && isset($supplierPayments[$refId])) {
                                $sp = $supplierPayments[$refId];
                                $cariName = $sp->supplier?->name ?? 'Tedarikçi';
                                $cariUrl = $sp->supplier ? route('suppliers.show', $sp->supplier) : null;
                                $paymentTypeValue = $sp->paymentType ?? null;
                                $paymentTypeLabel = $paymentTypes[$paymentTypeValue ?? ''] ?? $paymentTypeValue ?? '—';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">{{ $h->movementDate?->format('d.m.Y') }}</td>
                            <td class="px-6 py-4"><span class="text-sm">{{ ucfirst($h->type ?? '-') }}</span></td>
                            <td class="px-6 py-4 text-slate-700">
                                @if($cariUrl)
                                <a href="{{ $cariUrl }}" class="text-sky-600 hover:underline font-medium">{{ $cariName }}</a>
                                @elseif($cariName)
                                <span>{{ $cariName }}</span>
                                @else
                                <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($paymentTypeLabel && $paymentTypeLabel !== '—')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                    @if($paymentTypeValue === 'nakit') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                                    @elseif($paymentTypeValue === 'havale') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                    @elseif($paymentTypeValue === 'kredi_karti') bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300
                                    @elseif($paymentTypeValue === 'cek') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                                    @elseif($paymentTypeValue === 'senet') bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300
                                    @endif
                                ">{{ $paymentTypeLabel }}</span>
                                @else
                                <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $h->description ?? '-' }}</td>
                            @php $tutar = (float)($h->amount ?? 0); @endphp
                            <td class="px-6 py-4 text-right font-medium {{ $tutar >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($tutar, 0, ',', '.') }} ₺</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Henüz hareket yok.</td></tr>
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
