@extends('layouts.print')
@section('title', 'SSH Sevkiyat Formu - ' . $serviceTicket->ticketNumber)
@section('content')
@php
    use App\Support\ServiceTicketStatus;
    $problems = ServiceTicketStatus::normalizeProblems($serviceTicket->reportedProblems ?? []);
    if ($problems === [] && $serviceTicket->issueType) {
        $problems = [['description' => $serviceTicket->issueType, 'status' => 'bekliyor']];
    }
@endphp
<div class="print-document print-document--fit print-document--compact bg-white overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'SSH Servis / Sevkiyat Formu',
            'documentNumber' => $serviceTicket->ticketNumber,
            'documentDate' => $serviceTicket->openedAt,
            'documentSubtitle' => ServiceTicketStatus::label($serviceTicket->status),
        ])

        <p class="print-info-banner print-section text-[11px] text-neutral-700 p-3 mb-4">
            Bu belge sevkiyat / servis ekibine verilir. Müşteri adresindeki problemler giderilir; sonuç işaretlenir ve imza alınır.
        </p>

        <div class="print-section-lg grid grid-cols-2 gap-4 mb-4">
            <div class="p-3 border-2 border-neutral-900 bg-neutral-50">
                <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Müşteri / Servis Adresi</h3>
                <p class="font-bold text-sm text-neutral-900">{{ $serviceTicket->customer?->name ?? '—' }}</p>
                @if($serviceTicket->customer?->full_address)
                <p class="text-[11px] text-neutral-700 mt-1 whitespace-pre-wrap leading-snug">{{ $serviceTicket->customer->full_address }}</p>
                @else
                <p class="text-[11px] text-amber-800 mt-1">Adres tanımlı değil.</p>
                @endif
                @if($serviceTicket->customer?->phone)<p class="text-[11px] text-neutral-600 mt-2">Tel: {{ $serviceTicket->customer->phone }}</p>@endif
                @if($serviceTicket->customer?->phone2 ?? null)<p class="text-[11px] text-neutral-600">Tel 2: {{ $serviceTicket->customer->phone2 }}</p>@endif
            </div>
            <div class="p-3 border border-neutral-300">
                <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Sevkiyat Ekibi</h3>
                <dl class="space-y-1.5 text-[11px]">
                    <div class="flex justify-between gap-2"><dt class="text-neutral-500">Nakliye Firması</dt><dd class="font-medium text-neutral-900 text-right">{{ $serviceTicket->shippingCompany?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-500">Sürücü</dt><dd class="font-semibold text-neutral-900 text-right">{{ $serviceTicket->assignedDriverName ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-500">Telefon</dt><dd class="font-medium text-neutral-900 text-right">{{ $serviceTicket->assignedDriverPhone ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-500">Plaka</dt><dd class="font-medium text-neutral-900 text-right">{{ $serviceTicket->assignedVehiclePlate ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-500">Satış No</dt><dd class="font-medium text-neutral-900 text-right">{{ $serviceTicket->sale?->saleNumber ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-500">Teknisyen</dt><dd class="font-medium text-neutral-900 text-right">{{ $serviceTicket->assignedUser?->name ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        @if($serviceTicket->description)
        <div class="print-section mb-4 p-3 border border-neutral-200">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-1">Genel Açıklama</h3>
            <p class="text-[11px] text-neutral-800 whitespace-pre-wrap leading-relaxed">{{ $serviceTicket->description }}</p>
        </div>
        @endif

        <div class="print-section-lg mb-4">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-2">Müşteri Problemleri</h3>
            <table class="print-table min-w-full border border-neutral-300 text-[11px]">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left w-8">#</th>
                        <th class="px-2 py-2 text-left">Problem Açıklaması</th>
                        <th class="px-2 py-2 text-center w-16">Bekliyor</th>
                        <th class="px-2 py-2 text-center w-20">Düzeltildi</th>
                        <th class="px-2 py-2 text-center w-24">Düzeltilemedi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($problems as $index => $problem)
                    @php $pStatus = $problem['status'] ?? 'bekliyor'; @endphp
                    <tr class="border-t border-neutral-200">
                        <td class="px-2 py-2 align-top font-medium">{{ $index + 1 }}</td>
                        <td class="px-2 py-2 align-top">{{ $problem['description'] }}</td>
                        <td class="px-2 py-2 text-center align-top">{!! $pStatus === 'bekliyor' ? '■' : '□' !!}</td>
                        <td class="px-2 py-2 text-center align-top">{!! $pStatus === 'duzeltildi' ? '■' : '□' !!}</td>
                        <td class="px-2 py-2 text-center align-top">{!! $pStatus === 'duzeltilemedi' ? '■' : '□' !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($serviceTicket->notes)
        <div class="print-section mb-4 p-3 border border-neutral-200">
            <h3 class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-1">Notlar</h3>
            <p class="text-[11px] text-neutral-700 whitespace-pre-wrap">{{ $serviceTicket->notes }}</p>
        </div>
        @endif

        <div class="print-signatures print-section-lg grid grid-cols-2 gap-8 mt-6">
            <div>
                <p class="text-[10px] uppercase tracking-wider text-neutral-500 mb-10">Servis / Sevkiyat Görevlisi</p>
                <div class="sig-line">Ad Soyad / İmza / Tarih</div>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider text-neutral-500 mb-10">Müşteri Onayı</p>
                <div class="sig-line">Ad Soyad / İmza / Tarih</div>
            </div>
        </div>

        <p class="text-[9px] text-neutral-400 mt-6 text-center tracking-wide">{{ $serviceTicket->ticketNumber }} · {{ now()->format('d.m.Y H:i') }} · {{ \App\Models\Company::first()?->name }}</p>
    </div>
</div>
@endsection
