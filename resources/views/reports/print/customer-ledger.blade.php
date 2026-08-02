@extends('layouts.print')
@section('title', 'Müşteri Cari Özeti - Yazdır')
@section('content')
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'MÜŞTERİ CARİ ÖZETİ',
            'documentNumber' => $tip === 'borclu' ? 'Sadece borçlular' : ($tip === 'alacakli' ? 'Sadece alacaklılar' : 'Tümü'),
            'documentDate' => now(),
        ])
        <table class="print-table min-w-full">
            <thead><tr><th class="table-th">Müşteri</th><th class="table-th text-right">Borç</th><th class="table-th text-right">Alacak</th><th class="table-th text-right">Bakiye</th></tr></thead>
            <tbody>
                @foreach($customers as $r)
                <tr>
                    <td class="table-td">{{ $r->customer->name }}</td>
                    <td class="table-td text-right">{{ number_format($r->borc, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right">{{ number_format($r->alacak, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right font-medium">{{ number_format($r->bakiye, 0, ',', '.') }} ₺</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
