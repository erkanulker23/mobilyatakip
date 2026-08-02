@extends('layouts.print')
@section('title', 'Tahsilat Kayıtları - ' . $customer->name)
@section('content')
@php
    $pt = \App\Support\PaymentType::labels();
    $payments = $customer->payments;
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'Tahsilat Kayıtları',
            'documentNumber' => $customer->name,
            'documentDate' => now(),
        ])

        <div class="print-section-lg mb-4 p-3 border border-neutral-200">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Müşteri</h3>
            <p class="font-bold text-neutral-900">{{ $customer->name }}</p>
            @if($customer->full_address)<p class="text-[11px] text-neutral-600 mt-1 whitespace-pre-wrap">{{ $customer->full_address }}</p>@endif
            <p class="text-[11px] text-neutral-600 mt-1">
                {{ $customer->phone }}{{ $customer->phone2 ? ' / ' . $customer->phone2 : '' }}{{ $customer->email ? ' · ' . $customer->email : '' }}
            </p>
        </div>

        <div class="print-section-lg mb-4">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">
                Tahsilat Geçmişi
                <span class="font-normal normal-case text-neutral-400">({{ $payments->count() }} kayıt)</span>
            </h3>
            <table class="print-table min-w-full border border-neutral-300 text-[11px]">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left w-8">#</th>
                        <th class="px-2 py-2 text-left">Tahsilat Tarihi</th>
                        <th class="px-2 py-2 text-right">Tutar</th>
                        <th class="px-2 py-2 text-left">Ödeme Tipi</th>
                        <th class="px-2 py-2 text-left">İlgili Fatura</th>
                        <th class="px-2 py-2 text-left">Kasa / Hesap</th>
                        <th class="px-2 py-2 text-left">Not</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $p)
                    <tr class="border-t border-neutral-200">
                        <td class="px-2 py-2 text-neutral-500">{{ $index + 1 }}</td>
                        <td class="px-2 py-2 font-medium">{{ $p->paymentDate?->format('d.m.Y') ?? '—' }}</td>
                        <td class="px-2 py-2 text-right font-medium">{{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</td>
                        <td class="px-2 py-2">{{ $pt[$p->paymentType ?? ''] ?? ucfirst($p->paymentType ?? '—') }}</td>
                        <td class="px-2 py-2">{{ $p->sale?->saleNumber ?? '—' }}</td>
                        <td class="px-2 py-2">{{ $p->kasa?->name ?? '—' }}</td>
                        <td class="px-2 py-2 text-neutral-600">{{ $p->notes ?: ($p->reference ?: '—') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-2 py-6 text-center text-neutral-500">Tahsilat kaydı bulunmuyor.</td></tr>
                    @endforelse
                </tbody>
                @if($payments->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-neutral-400 font-semibold">
                        <td class="px-2 py-2" colspan="2">Toplam Tahsilat</td>
                        <td class="px-2 py-2 text-right">{{ number_format($totalPaid, 0, ',', '.') }} ₺</td>
                        <td class="px-2 py-2" colspan="4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <div class="pt-4 mt-2 border-t border-neutral-200 text-[10px] text-neutral-500">
            <p>Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur. Tahsilat tarihleri, ödemenin alındığı günü gösterir.</p>
        </div>
    </div>
</div>
@endsection
