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
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => 'SSH Servis / Sevkiyat Formu',
                'documentNumber' => $serviceTicket->ticketNumber,
                'documentDate' => $serviceTicket->openedAt,
                'documentSubtitle' => ServiceTicketStatus::label($serviceTicket->status),
            ])

            <div class="print-info-banner print-section">
                Bu belge sevkiyat / servis ekibine verilir. Müşteri adresindeki problemler giderilir; sonuç işaretlenir ve imza alınır.
            </div>

            <div class="print-meta-grid print-section-lg">
                <div class="print-card">
                    <p class="print-label">Müşteri / Servis Adresi</p>
                    <p class="print-party-name">{{ $serviceTicket->customer?->name ?? '—' }}</p>
                    @if($serviceTicket->customer?->full_address)
                    <p class="print-muted mt-1 whitespace-pre-wrap">{{ $serviceTicket->customer->full_address }}</p>
                    @else
                    <p class="print-muted mt-1">Adres tanımlı değil.</p>
                    @endif
                    @if($serviceTicket->customer?->phone)<p class="print-muted mt-1">Tel: {{ $serviceTicket->customer->phone }}</p>@endif
                    @if($serviceTicket->customer?->phone2 ?? null)<p class="print-muted">Tel 2: {{ $serviceTicket->customer->phone2 }}</p>@endif
                </div>
                <div class="print-card">
                    <p class="print-label">Sevkiyat Ekibi</p>
                    <dl class="space-y-1">
                        <div class="flex justify-between gap-2 text-sm"><dt class="print-muted">Nakliye Firması</dt><dd class="font-medium text-right">{{ $serviceTicket->shippingCompany?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-2 text-sm"><dt class="print-muted">Sürücü</dt><dd class="font-semibold text-right">{{ $serviceTicket->assignedDriverName ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-2 text-sm"><dt class="print-muted">Telefon</dt><dd class="font-medium text-right">{{ $serviceTicket->assignedDriverPhone ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-2 text-sm"><dt class="print-muted">Plaka</dt><dd class="font-medium text-right">{{ $serviceTicket->assignedVehiclePlate ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-2 text-sm"><dt class="print-muted">Satış No</dt><dd class="font-medium text-right">{{ $serviceTicket->sale?->saleNumber ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-2 text-sm"><dt class="print-muted">Teknisyen</dt><dd class="font-medium text-right">{{ $serviceTicket->assignedUser?->name ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            @if($serviceTicket->description)
            <div class="print-card print-section">
                <p class="print-label">Genel Açıklama</p>
                <p class="whitespace-pre-wrap">{{ $serviceTicket->description }}</p>
            </div>
            @endif

            <div class="print-section-lg">
                <p class="print-section-title">Müşteri Problemleri</p>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th class="print-col-no text-left">#</th>
                            <th class="text-left">Problem Açıklaması</th>
                            <th class="text-center" style="width:12%">Bekliyor</th>
                            <th class="text-center" style="width:14%">Düzeltildi</th>
                            <th class="text-center" style="width:16%">Düzeltilemedi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($problems as $index => $problem)
                        @php $pStatus = $problem['status'] ?? 'bekliyor'; @endphp
                        <tr>
                            <td class="print-col-no font-medium">{{ $index + 1 }}</td>
                            <td>{{ $problem['description'] }}</td>
                            <td class="text-center">{!! $pStatus === 'bekliyor' ? '■' : '□' !!}</td>
                            <td class="text-center">{!! $pStatus === 'duzeltildi' ? '■' : '□' !!}</td>
                            <td class="text-center">{!! $pStatus === 'duzeltilemedi' ? '■' : '□' !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($serviceTicket->notes)
            <div class="print-notes-block print-section">
                <p class="print-label">Notlar</p>
                <p class="whitespace-pre-wrap">{{ $serviceTicket->notes }}</p>
            </div>
            @endif

            <div class="print-signatures grid grid-cols-2 gap-8">
                <div>
                    <p class="print-label mb-10">Servis / Sevkiyat Görevlisi</p>
                    <div class="sig-line">Ad Soyad / İmza / Tarih</div>
                </div>
                <div>
                    <p class="print-label mb-10">Müşteri Onayı</p>
                    <div class="sig-line">Ad Soyad / İmza / Tarih</div>
                </div>
            </div>

            <p class="print-footer-note text-center">{{ $serviceTicket->ticketNumber }} · {{ now()->format('d.m.Y H:i') }} · {{ \App\Models\Company::first()?->name }}</p>
        </div>
    </div>
</div>
@endsection
