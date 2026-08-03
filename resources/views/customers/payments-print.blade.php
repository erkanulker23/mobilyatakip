@extends('layouts.print')
@section('title', 'Tahsilat Kayıtları - ' . $customer->name)
@section('content')
@php
    $pt = \App\Support\PaymentType::labels();
    $payments = $customer->payments;
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => 'Tahsilat Kayıtları',
                'documentNumber' => $customer->name,
                'documentDate' => now(),
            ])

            <div class="print-card print-section-lg">
                <p class="print-label">Müşteri</p>
                <p class="print-party-name">{{ $customer->name }}</p>
                @if($customer->full_address)<p class="print-muted mt-1 whitespace-pre-wrap">{{ $customer->full_address }}</p>@endif
                <p class="print-muted mt-1">
                    {{ $customer->phone }}{{ $customer->phone2 ? ' / ' . $customer->phone2 : '' }}{{ $customer->email ? ' · ' . $customer->email : '' }}
                </p>
            </div>

            <div class="print-section-lg">
                <p class="print-section-title">Tahsilat Geçmişi ({{ $payments->count() }} kayıt)</p>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th class="print-col-no text-left">#</th>
                            <th class="text-left">Tahsilat Tarihi</th>
                            <th class="text-right">Tutar</th>
                            <th class="text-left">Ödeme Tipi</th>
                            <th class="text-left">İlgili Fatura</th>
                            <th class="text-left">Kasa / Hesap</th>
                            <th class="text-left">Not</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $index => $p)
                        <tr>
                            <td class="print-col-no print-muted">{{ $index + 1 }}</td>
                            <td class="font-medium">{{ $p->paymentDate?->format('d.m.Y') ?? '—' }}</td>
                            <td class="text-right font-medium">{{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</td>
                            <td>{{ $pt[$p->paymentType ?? ''] ?? ucfirst($p->paymentType ?? '—') }}</td>
                            <td>{{ $p->sale?->saleNumber ?? '—' }}</td>
                            <td>{{ $p->kasa?->name ?? '—' }}</td>
                            <td class="print-muted">{{ $p->notes ?: ($p->reference ?: '—') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center print-muted py-4">Tahsilat kaydı bulunmuyor.</td></tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2">Toplam Tahsilat</td>
                            <td class="text-right">{{ number_format($totalPaid, 0, ',', '.') }} ₺</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <div class="print-footer-note">
                <p>Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur. Tahsilat tarihleri, ödemenin alındığı günü gösterir.</p>
            </div>
        </div>
    </div>
</div>
@endsection
