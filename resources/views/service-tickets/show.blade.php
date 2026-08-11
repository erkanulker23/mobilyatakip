@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::serviceTicket($serviceTicket))
@section('content')
@php
    use App\Support\ServiceTicketStatus;
    $status = $serviceTicket->status ?? 'acildi';
    $statusClass = ServiceTicketStatus::badgeClass($status);
    $problems = ServiceTicketStatus::normalizeProblems($serviceTicket->reportedProblems ?? []);
    if ($problems === [] && $serviceTicket->issueType) {
        $problems = [['description' => $serviceTicket->issueType, 'status' => 'bekliyor']];
    }
    $ticketCustomer = $serviceTicket->customer ?? $serviceTicket->sale?->customer;
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <nav class="flex items-center gap-2 text-sm text-neutral-500 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('service-tickets.index') }}" class="hover:text-neutral-900">Servis Kayıtları</a>
            <span>/</span>
            <span class="text-neutral-700 font-medium">{{ $serviceTicket->ticketNumber }}</span>
        </nav>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="page-title mb-0">{{ $serviceTicket->ticketNumber }}</h1>
            <span class="badge {{ $statusClass }}">{{ ServiceTicketStatus::label($status) }}</span>
        </div>
        <p class="page-desc">
            {{ ServiceTicketStatus::problemSummary($problems) }}
            @if($showCustomerNames && $ticketCustomer?->name)
            · Müşteri:
            @if(empty($hideCommercialData))
            <a href="{{ route('customers.show', $ticketCustomer) }}" class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">{{ $ticketCustomer->name }}</a>
            @else
            {{ $ticketCustomer->name }}
            @endif
            @endif
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button"
            class="btn-secondary text-sm"
            data-track-url="{{ route('tracking.show', $serviceTicket->ticketNumber) }}"
            onclick="navigator.clipboard.writeText(this.dataset.trackUrl).then(() => { const t = this.textContent; this.textContent = 'Takip linki kopyalandı'; setTimeout(() => { this.textContent = t; }, 1800); })"
        >
            Takip Linkini Kopyala
        </button>
        @if(empty($hideCommercialData) && $ticketCustomer)
        <a href="{{ route('customers.show', $ticketCustomer) }}" class="btn-secondary">Müşteri Detayı</a>
        @endif
        @if(empty($hideCommercialData))
        <a href="{{ route('service-tickets.print', $serviceTicket) }}" target="_blank" rel="noopener" class="btn-print">Sevkiyat Formu Yazdır</a>
        @endif
        <a href="{{ route('service-tickets.edit', $serviceTicket) }}" class="btn-edit">Düzenle</a>
        @if(empty($hideCommercialData) && $serviceTicket->saleId && $serviceTicket->sale)
        <a href="{{ route('sales.show', $serviceTicket->sale) }}" class="btn-secondary">Satış Detayı</a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
@endif
@if(session('info'))
<div class="mb-4 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">{{ session('info') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 space-y-5">
        @if(!empty($hideCommercialData))
            @include('service-tickets.partials.workshop-finished', ['serviceTicket' => $serviceTicket])
        @endif
        <div class="card overflow-hidden">
            <div class="card-header">Müşteri Problemleri</div>
            <div class="divide-y divide-neutral-100">
                @foreach($problems as $index => $problem)
                @php
                    $pStatus = $problem['status'] ?? 'bekliyor';
                    $pClass = $pStatus === 'duzeltildi' ? 'badge-green' : ($pStatus === 'duzeltilemedi' ? 'badge-red' : 'badge-amber');
                @endphp
                <div class="p-5">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold text-neutral-400">#{{ $index + 1 }}</span>
                                <span class="badge {{ $pClass }}">{{ ServiceTicketStatus::problemLabel($pStatus) }}</span>
                            </div>
                            <p class="font-medium text-neutral-900">{{ $problem['description'] }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            @foreach(ServiceTicketStatus::PROBLEM_STATUSES as $value => $label)
                            @if($value !== $pStatus)
                            <form method="POST" action="{{ route('service-tickets.problem-status', $serviceTicket) }}">
                                @csrf
                                <input type="hidden" name="problemIndex" value="{{ $index }}">
                                <input type="hidden" name="status" value="{{ $value }}">
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-neutral-200 hover:bg-neutral-50">{{ $label }}</button>
                            </form>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if($serviceTicket->description)
        <div class="card overflow-hidden">
            <div class="card-header">Genel Açıklama</div>
            <div class="p-5">
                <p class="text-neutral-600 whitespace-pre-wrap">{{ $serviceTicket->description }}</p>
            </div>
        </div>
        @endif

        @php $ticketImages = is_array($serviceTicket->images ?? null) ? $serviceTicket->images : []; @endphp
        @if(count($ticketImages) > 0)
        <div class="card overflow-hidden">
            <div class="card-header">Resimler</div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($ticketImages as $img)
                    <a href="{{ storage_url($img) }}" target="_blank" rel="noopener" class="block rounded-xl overflow-hidden border border-neutral-200 hover:border-neutral-400 transition-colors aspect-square">
                        <img src="{{ storage_url($img) }}" alt="Servis" class="w-full h-full object-cover">
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="card overflow-hidden">
            <div class="card-header">İşlem Geçmişi</div>
            <div class="p-5 space-y-4">
                @forelse($serviceTicket->details->sortByDesc('actionDate') as $i => $d)
                <div class="flex gap-4 pb-4 {{ !$loop->last ? 'border-b border-neutral-100' : '' }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-semibold shrink-0 bg-neutral-100 text-neutral-600">{{ $loop->iteration }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-neutral-900">{{ ServiceTicketStatus::detailActionLabel($d->action) }}</p>
                        <p class="text-xs text-neutral-500 mt-0.5">{{ $d->actionDate?->format('d.m.Y H:i') ?? '—' }} · {{ $d->user?->name ?? '—' }}</p>
                        @if($d->notes)<p class="text-sm text-neutral-600 mt-2 whitespace-pre-wrap">{{ $d->notes }}</p>@endif
                    </div>
                </div>
                @empty
                <p class="text-neutral-500 text-sm">Henüz işlem kaydı yok.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card overflow-hidden">
            <div class="card-header">Servis Bilgileri</div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    @if($showCustomerNames && $ticketCustomer)
                    <div><dt class="form-label">Müşteri</dt><dd class="font-medium">
                        @if(empty($hideCommercialData))
                        <a href="{{ route('customers.show', $ticketCustomer) }}" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 hover:underline">{{ $ticketCustomer->name }}</a>
                        @else
                        {{ $ticketCustomer->name }}
                        @endif
                    </dd></div>
                    @if(empty($hideCommercialData))
                    <div><dt class="form-label">Telefon</dt><dd>{{ $ticketCustomer->phone ?: '—' }}</dd></div>
                    <div><dt class="form-label">Adres</dt><dd class="whitespace-pre-wrap">{{ $ticketCustomer->full_address ?: '—' }}</dd></div>
                    @endif
                    @endif
                    @if($serviceTicket->sale)
                    <div><dt class="form-label">Satış</dt><dd class="font-medium">
                        @if(empty($hideCommercialData))
                        <a href="{{ route('sales.show', $serviceTicket->sale) }}" class="hover:underline">{{ $serviceTicket->sale->saleNumber }}</a>
                        @else
                        {{ $serviceTicket->sale->saleNumber }}
                        @endif
                    </dd></div>
                    @endif
                    @if(empty($hideCommercialData))
                    <div><dt class="form-label">Garanti</dt><dd>{{ $serviceTicket->underWarranty ? 'Evet' : 'Hayır' }}</dd></div>
                    <div><dt class="form-label">Teknisyen</dt><dd>{{ $serviceTicket->assignedUser?->name ?? '—' }}</dd></div>
                    @if($serviceTicket->serviceChargeAmount)
                    <div><dt class="form-label">Servis Ücreti</dt><dd class="font-semibold">₺{{ number_format($serviceTicket->serviceChargeAmount, 0, ',', '.') }}</dd></div>
                    @endif
                    @endif
                    <div><dt class="form-label">Açan</dt><dd class="font-medium">{{ $serviceTicket->openingDetail?->user?->name ?? '—' }}</dd></div>
                    @if($closedBy = $serviceTicket->closedByUserName())
                    <div><dt class="form-label">Kapatan</dt><dd class="font-medium">{{ $closedBy }}</dd></div>
                    @endif
                    <div><dt class="form-label">Açılış</dt><dd>{{ $serviceTicket->openedAt?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    @if($serviceTicket->dueDate)
                    @php
                        $sshDaysLeft = (int) now()->startOfDay()->diffInDays($serviceTicket->dueDate, false);
                        $sshTerminClass = $sshDaysLeft < 0 ? 'text-red-600 font-medium' : ($sshDaysLeft <= 3 ? 'text-amber-600 font-medium' : 'text-neutral-900');
                        $sshDaysSuffix = null;
                        if (! in_array($status, ['tamamlandi', 'iptal'], true)) {
                            if ($sshDaysLeft < 0) {
                                $sshDaysSuffix = abs($sshDaysLeft) . ' gün gecikti';
                            } elseif ($sshDaysLeft === 0) {
                                $sshDaysSuffix = 'bugün';
                            } else {
                                $sshDaysSuffix = $sshDaysLeft . ' gün kaldı';
                            }
                        }
                    @endphp
                    <div><dt class="form-label">Açılacak Servis Tarihi</dt><dd class="{{ $sshTerminClass }}">{{ $serviceTicket->dueDate->format('d.m.Y') }}@if($sshDaysSuffix) · {{ $sshDaysSuffix }}@endif</dd></div>
                    @endif
                    @if($serviceTicket->closedAt)<div><dt class="form-label">Kapanış</dt><dd>{{ $serviceTicket->closedAt->format('d.m.Y H:i') }}</dd></div>@endif
                </dl>
            </div>
        </div>

        @if(empty($hideCommercialData))
        <div class="card overflow-hidden">
            <div class="card-header">Sevkiyatçı</div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div><dt class="form-label">Nakliye Firması</dt><dd>{{ $serviceTicket->shippingCompany?->name ?: '—' }}</dd></div>
                    <div><dt class="form-label">Sürücü</dt><dd>{{ $serviceTicket->assignedDriverName ?: '—' }}</dd></div>
                    <div><dt class="form-label">Telefon</dt><dd>{{ $serviceTicket->assignedDriverPhone ?: '—' }}</dd></div>
                    <div><dt class="form-label">Araç Plakası</dt><dd>{{ $serviceTicket->assignedVehiclePlate ?: '—' }}</dd></div>
                </dl>
                <a href="{{ route('service-tickets.print', $serviceTicket) }}" target="_blank" class="btn-print w-full justify-center mt-4">Sevkiyat Formu Yazdır</a>
            </div>
        </div>
        @endif

        @if($serviceTicket->notes)
        <div class="card overflow-hidden">
            <div class="card-header">Notlar</div>
            <div class="p-5">
                <p class="text-sm text-neutral-600 whitespace-pre-wrap">{{ $serviceTicket->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
