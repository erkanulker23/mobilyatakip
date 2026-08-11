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
    $isClosed = ServiceTicketStatus::isClosed($status);
    $fixedCount = collect($problems)->where('status', 'duzeltildi')->count();
    $problemTotal = count($problems);

    $stageOrder = ['acildi', 'devam_ediyor', 'parca_bekleniyor', 'sevkiyatci_bekleniyor', 'tamamlandi'];
    if ($status === 'iptal') {
        $stages = [
            ['key' => 'acildi', 'label' => 'Açıldı', 'done' => true, 'current' => false],
            ['key' => 'iptal', 'label' => 'İptal', 'done' => true, 'current' => true],
        ];
    } else {
        $idx = array_search($status, $stageOrder, true);
        if ($idx === false) {
            $idx = 0;
        }
        $stages = [];
        foreach ($stageOrder as $i => $key) {
            if (in_array($key, ['parca_bekleniyor', 'sevkiyatci_bekleniyor'], true) && $status !== $key && $i > $idx) {
                continue;
            }
            $stages[] = [
                'key' => $key,
                'label' => ServiceTicketStatus::label($key),
                'done' => $i <= $idx,
                'current' => $status === $key,
            ];
        }
    }

    $sshDaysLeft = null;
    $sshDaysSuffix = null;
    $sshTerminClass = 'text-neutral-900 dark:text-neutral-100';
    if ($serviceTicket->dueDate) {
        $sshDaysLeft = (int) now()->startOfDay()->diffInDays($serviceTicket->dueDate, false);
        if (! $isClosed) {
            if ($sshDaysLeft < 0) {
                $sshDaysSuffix = abs($sshDaysLeft) . ' gün gecikti';
                $sshTerminClass = 'text-red-600 dark:text-red-400 font-semibold';
            } elseif ($sshDaysLeft === 0) {
                $sshDaysSuffix = 'Termin bugün';
                $sshTerminClass = 'text-amber-600 dark:text-amber-400 font-semibold';
            } elseif ($sshDaysLeft <= 3) {
                $sshDaysSuffix = $sshDaysLeft . ' gün kaldı';
                $sshTerminClass = 'text-amber-600 dark:text-amber-400 font-medium';
            } else {
                $sshDaysSuffix = $sshDaysLeft . ' gün kaldı';
            }
        }
    }
@endphp

{{-- Başlık --}}
<div class="mb-6">
    <nav class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400 mb-2" aria-label="Breadcrumb">
        <a href="{{ route('service-tickets.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Servis Kayıtları</a>
        <span aria-hidden="true">/</span>
        <span class="text-neutral-700 dark:text-neutral-300 font-medium">{{ $serviceTicket->ticketNumber }}</span>
    </nav>

    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="page-title mb-0">{{ $serviceTicket->ticketNumber }}</h1>
                <span class="badge {{ $statusClass }}">{{ ServiceTicketStatus::label($status) }}</span>
                @if($serviceTicket->underWarranty)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">Garantili</span>
                @endif
            </div>
            <p class="page-desc mt-1.5">
                {{ ServiceTicketStatus::problemSummary($problems) }}
                @if($showCustomerNames && $ticketCustomer?->name)
                <span class="text-neutral-300 dark:text-neutral-600 mx-1">·</span>
                @if(empty($hideCommercialData))
                <a href="{{ route('customers.show', $ticketCustomer) }}" class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">{{ $ticketCustomer->name }}</a>
                @else
                {{ $ticketCustomer->name }}
                @endif
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button type="button"
                class="btn-secondary text-sm font-mono"
                data-track-code="{{ $serviceTicket->ticketNumber }}"
                title="Takip kodunu kopyala"
                onclick="(function(btn){ navigator.clipboard.writeText(btn.dataset.trackCode).then(function(){ var t = btn.textContent; btn.textContent = 'Kopyalandı'; setTimeout(function(){ btn.textContent = t; }, 1800); }); })(this)"
            >{{ $serviceTicket->ticketNumber }}</button>
            @if(empty($hideCommercialData))
            <a href="{{ route('service-tickets.print', $serviceTicket) }}" target="_blank" rel="noopener" class="btn-print text-sm">Sevkiyat Formu</a>
            @endif
            <a href="{{ route('service-tickets.edit', $serviceTicket) }}" class="btn-edit text-sm">Düzenle</a>
            @if(empty($hideCommercialData) && $serviceTicket->sale)
            <a href="{{ route('sales.show', $serviceTicket->sale) }}" class="btn-secondary text-sm">Satış</a>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
@endif
@if(session('info'))
<div class="mb-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 text-sm">{{ session('info') }}</div>
@endif

{{-- Termin uyarısı --}}
@if($sshDaysSuffix && $sshDaysLeft !== null && $sshDaysLeft <= 3)
<div class="mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 {{ $sshDaysLeft < 0 ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200' : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200' }}">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div>
        <p class="font-medium text-sm">Servis tarihi: {{ $serviceTicket->dueDate->format('d.m.Y') }}</p>
        <p class="text-sm opacity-90">{{ $sshDaysSuffix }}</p>
    </div>
</div>
@endif

{{-- Süreç + durum güncelle --}}
<div class="card overflow-hidden mb-5">
    <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center gap-5">
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-3">Süreç</p>
            <ol class="flex flex-wrap items-center gap-2">
                @foreach($stages as $i => $stage)
                <li class="inline-flex items-center gap-2">
                    <span @class([
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                        'bg-emerald-600 text-white' => ($stage['done'] ?? false) && !($stage['current'] ?? false),
                        'bg-emerald-100 text-emerald-800 ring-2 ring-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-200' => $stage['current'] ?? false,
                        'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400' => !($stage['done'] ?? false) && !($stage['current'] ?? false),
                    ])>
                        @if(($stage['done'] ?? false) && !($stage['current'] ?? false))
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        @endif
                        {{ $stage['label'] }}
                    </span>
                    @if(!$loop->last)
                    <span class="text-neutral-300 dark:text-neutral-600 hidden sm:inline" aria-hidden="true">→</span>
                    @endif
                </li>
                @endforeach
            </ol>
        </div>
        <form method="POST" action="{{ route('service-tickets.update-status', $serviceTicket) }}" class="flex flex-col sm:flex-row sm:items-end gap-2 shrink-0 lg:border-l lg:border-neutral-100 dark:lg:border-neutral-800 lg:pl-5">
            @csrf
            @method('PATCH')
            <div>
                <label class="form-label" for="sshStatusSelect">Durumu güncelle</label>
                <select name="status" id="sshStatusSelect" class="form-select min-w-[14rem]" onchange="this.form.submit()">
                    @foreach(ServiceTicketStatus::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 space-y-5">
        @if(!empty($hideCommercialData))
            @include('service-tickets.partials.workshop-finished', ['serviceTicket' => $serviceTicket])
        @endif

        {{-- Problemler --}}
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between gap-3">
                <span>Müşteri Problemleri</span>
                <span class="text-xs font-normal text-neutral-500">{{ $fixedCount }}/{{ $problemTotal }} düzeltildi</span>
            </div>
            <div class="p-4 sm:p-5">
                <div class="h-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden mb-5">
                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $problemTotal > 0 ? round($fixedCount / $problemTotal * 100) : 0 }}%"></div>
                </div>
                <div class="space-y-3">
                    @foreach($problems as $index => $problem)
                    @php
                        $pStatus = $problem['status'] ?? 'bekliyor';
                        $pClass = $pStatus === 'duzeltildi' ? 'badge-green' : ($pStatus === 'duzeltilemedi' ? 'badge-red' : 'badge-amber');
                        $cardTone = $pStatus === 'duzeltildi'
                            ? 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                            : ($pStatus === 'duzeltilemedi'
                                ? 'border-red-200 bg-red-50/40 dark:border-red-900/40 dark:bg-red-950/20'
                                : 'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900');
                    @endphp
                    <div class="rounded-xl border p-4 {{ $cardTone }}">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800 text-xs font-semibold text-neutral-500">{{ $index + 1 }}</span>
                                    <span class="badge {{ $pClass }}">{{ ServiceTicketStatus::problemLabel($pStatus) }}</span>
                                </div>
                                <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $problem['description'] }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 shrink-0">
                                @foreach(ServiceTicketStatus::PROBLEM_STATUSES as $value => $label)
                                @if($value !== $pStatus)
                                <form method="POST" action="{{ route('service-tickets.problem-status', $serviceTicket) }}">
                                    @csrf
                                    <input type="hidden" name="problemIndex" value="{{ $index }}">
                                    <input type="hidden" name="status" value="{{ $value }}">
                                    <button type="submit" @class([
                                        'text-xs px-3 py-1.5 rounded-lg font-medium transition-colors',
                                        'bg-emerald-600 text-white hover:bg-emerald-700' => $value === 'duzeltildi',
                                        'bg-red-600 text-white hover:bg-red-700' => $value === 'duzeltilemedi',
                                        'border border-neutral-200 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800' => $value === 'bekliyor',
                                    ])>{{ $label }}</button>
                                </form>
                                @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($serviceTicket->description)
        <div class="card overflow-hidden">
            <div class="card-header">Genel Açıklama</div>
            <div class="p-5">
                <p class="text-neutral-600 dark:text-neutral-300 whitespace-pre-wrap leading-relaxed">{{ $serviceTicket->description }}</p>
            </div>
        </div>
        @endif

        @php $ticketImages = is_array($serviceTicket->images ?? null) ? $serviceTicket->images : []; @endphp
        @if(count($ticketImages) > 0)
        <div class="card overflow-hidden">
            <div class="card-header">Resimler <span class="text-xs font-normal text-neutral-500">({{ count($ticketImages) }})</span></div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($ticketImages as $img)
                    <a href="{{ storage_url($img) }}" target="_blank" rel="noopener" class="block rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-700 hover:border-emerald-400 dark:hover:border-emerald-500 transition-colors aspect-square bg-neutral-50 dark:bg-neutral-800">
                        <img src="{{ storage_url($img) }}" alt="Servis görseli" class="w-full h-full object-cover">
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Zaman çizelgesi --}}
        <div class="card overflow-hidden">
            <div class="card-header">İşlem Geçmişi</div>
            <div class="p-5">
                @forelse($serviceTicket->details->sortByDesc('actionDate') as $d)
                <div class="flex gap-4 pb-5 last:pb-0">
                    <div class="flex flex-col items-center">
                        <span @class([
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => in_array($d->action, ['acildi', 'kapatildi', 'atolyede_is_bitti'], true),
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $d->action === 'problem_durumu',
                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => $d->action === 'durum_guncelleme',
                            'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' => ! in_array($d->action, ['acildi', 'kapatildi', 'atolyede_is_bitti', 'problem_durumu', 'durum_guncelleme'], true),
                        ])>
                            @if($d->action === 'acildi') +
                            @elseif($d->action === 'kapatildi') ✓
                            @elseif($d->action === 'problem_durumu') !
                            @elseif($d->action === 'durum_guncelleme') ↕
                            @else •
                            @endif
                        </span>
                        @if(!$loop->last)
                        <div class="mt-1 w-px flex-1 bg-neutral-200 dark:bg-neutral-700 min-h-[20px]"></div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 pt-1">
                        <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ ServiceTicketStatus::detailActionLabel($d->action) }}</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $d->actionDate?->format('d.m.Y H:i') ?? '—' }} · {{ $d->user?->name ?? '—' }}</p>
                        @if($d->notes)
                        <p class="text-sm text-neutral-600 dark:text-neutral-300 mt-2 whitespace-pre-wrap rounded-lg bg-neutral-50 dark:bg-neutral-800/60 px-3 py-2">{{ $d->notes }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-neutral-500 text-sm">Henüz işlem kaydı yok.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sağ kolon --}}
    <div class="space-y-5 xl:sticky xl:top-20 xl:self-start">
        <div class="card overflow-hidden">
            <div class="card-header">Servis Bilgileri</div>
            <div class="p-5">
                <dl class="space-y-3.5 text-sm">
                    @if($showCustomerNames && $ticketCustomer)
                    <div>
                        <dt class="form-label">Müşteri</dt>
                        <dd class="font-medium text-neutral-900 dark:text-neutral-100">
                            @if(empty($hideCommercialData))
                            <a href="{{ route('customers.show', $ticketCustomer) }}" class="text-emerald-600 hover:underline dark:text-emerald-400">{{ $ticketCustomer->name }}</a>
                            @else
                            {{ $ticketCustomer->name }}
                            @endif
                        </dd>
                    </div>
                    @if(empty($hideCommercialData))
                    <div>
                        <dt class="form-label">Telefon</dt>
                        <dd>
                            @if($ticketCustomer->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $ticketCustomer->phone) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $ticketCustomer->phone }}</a>
                            @else
                            —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="form-label">Adres</dt>
                        <dd class="text-neutral-700 dark:text-neutral-300 whitespace-pre-wrap">{{ $ticketCustomer->full_address ?: '—' }}</dd>
                    </div>
                    @endif
                    @endif
                    @if($serviceTicket->sale)
                    <div>
                        <dt class="form-label">Satış</dt>
                        <dd class="font-medium">
                            @if(empty($hideCommercialData))
                            <a href="{{ route('sales.show', $serviceTicket->sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $serviceTicket->sale->saleNumber }}</a>
                            @else
                            {{ $serviceTicket->sale->saleNumber }}
                            @endif
                        </dd>
                    </div>
                    @endif
                    @if(empty($hideCommercialData))
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <dt class="form-label">Garanti</dt>
                            <dd class="font-medium">{{ $serviceTicket->underWarranty ? 'Evet' : 'Hayır' }}</dd>
                        </div>
                        <div>
                            <dt class="form-label">Teknisyen</dt>
                            <dd class="font-medium">{{ $serviceTicket->assignedUser?->name ?? '—' }}</dd>
                        </div>
                    </div>
                    @if($serviceTicket->serviceChargeAmount)
                    <div>
                        <dt class="form-label">Servis Ücreti</dt>
                        <dd class="font-semibold text-neutral-900 dark:text-neutral-100">₺{{ number_format($serviceTicket->serviceChargeAmount, 0, ',', '.') }}</dd>
                    </div>
                    @endif
                    @endif
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-neutral-100 dark:border-neutral-800">
                        <div>
                            <dt class="form-label">Açan</dt>
                            <dd class="font-medium">{{ $serviceTicket->openingDetail?->user?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="form-label">Kapatan</dt>
                            <dd class="font-medium">{{ $serviceTicket->closedByUserName() ?? '—' }}</dd>
                        </div>
                    </div>
                    <div>
                        <dt class="form-label">Açılış</dt>
                        <dd>{{ $serviceTicket->openedAt?->format('d.m.Y H:i') ?? '—' }}</dd>
                    </div>
                    @if($serviceTicket->dueDate)
                    <div>
                        <dt class="form-label">Servis Tarihi</dt>
                        <dd class="{{ $sshTerminClass }}">
                            {{ $serviceTicket->dueDate->format('d.m.Y') }}
                            @if($sshDaysSuffix)<span class="block text-xs mt-0.5 opacity-90">{{ $sshDaysSuffix }}</span>@endif
                        </dd>
                    </div>
                    @endif
                    @if($serviceTicket->closedAt)
                    <div>
                        <dt class="form-label">Kapanış</dt>
                        <dd>{{ $serviceTicket->closedAt->format('d.m.Y H:i') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        @if(empty($hideCommercialData))
        <div class="card overflow-hidden">
            <div class="card-header">Sevkiyatçı</div>
            <div class="p-5">
                @if($serviceTicket->assignedDriverName || $serviceTicket->shippingCompany)
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="form-label">Nakliye Firması</dt>
                        <dd class="font-medium">{{ $serviceTicket->shippingCompany?->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">Sürücü</dt>
                        <dd class="font-medium">{{ $serviceTicket->assignedDriverName ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">Telefon</dt>
                        <dd>
                            @if($serviceTicket->assignedDriverPhone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $serviceTicket->assignedDriverPhone) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ $serviceTicket->assignedDriverPhone }}</a>
                            @else
                            —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="form-label">Araç Plakası</dt>
                        <dd class="font-mono font-medium">{{ $serviceTicket->assignedVehiclePlate ?: '—' }}</dd>
                    </div>
                </dl>
                @else
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-3">Henüz sevkiyatçı atanmadı.</p>
                <a href="{{ route('service-tickets.edit', $serviceTicket) }}" class="btn-secondary text-sm w-full justify-center">Sevkiyatçı Ata</a>
                @endif
                <a href="{{ route('service-tickets.print', $serviceTicket) }}" target="_blank" class="btn-print w-full justify-center mt-4">Sevkiyat Formu Yazdır</a>
            </div>
        </div>
        @endif

        @if($serviceTicket->notes)
        <div class="card overflow-hidden">
            <div class="card-header">Notlar</div>
            <div class="p-5">
                <p class="text-sm text-neutral-600 dark:text-neutral-300 whitespace-pre-wrap leading-relaxed">{{ $serviceTicket->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
