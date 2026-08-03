@php
    $company = $company ?? \App\Models\Company::first();
@endphp
<header class="print-brand-header print-section-lg">
    <div class="print-brand-header__company">
        @if($company?->logoUrl)
        <img src="{{ $company->logoDisplayUrl() }}" alt="{{ $company->name ?? 'Logo' }}" class="print-brand-logo">
        @endif
        <p class="print-brand-name">{{ $company?->name ?? 'Firma Adı' }}</p>
        @if(full_address($company ?? null))
        <p class="print-brand-meta whitespace-pre-wrap">{{ full_address($company) }}</p>
        @endif
        <div class="print-brand-contacts">
            @if($company?->phone)<span>Tel: {{ $company->phone }}</span>@endif
            @if($company?->email)<span>{{ $company->email }}</span>@endif
            @if($company?->website)<span>{{ $company->website }}</span>@endif
            @if($company?->taxNumber)
            <span>VKN: {{ $company->taxNumber }}@if($company->taxOffice) · {{ $company->taxOffice }}@endif</span>
            @endif
        </div>
    </div>
    <div class="print-doc-meta">
        <span class="print-doc-type">{{ $documentTitle ?? 'BELGE' }}</span>
        <span class="print-doc-no">{{ $documentNumber ?? '—' }}</span>
        @if(!empty($documentSubtitle))
        <span class="print-doc-sub">{{ $documentSubtitle }}</span>
        @endif
        @if(!empty($documentDate))
        <span class="print-doc-sub">
            Tarih: {{ $documentDate instanceof \DateTimeInterface ? $documentDate->format('d.m.Y') : $documentDate }}
        </span>
        @endif
    </div>
</header>
