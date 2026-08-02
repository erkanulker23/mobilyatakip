@php
    $company = $company ?? \App\Models\Company::first();
@endphp
<div class="print-brand-header print-section-lg flex flex-row justify-between items-start gap-6 pb-4 mb-4 border-b-2 border-neutral-900">
    <div class="flex-1 min-w-0">
        @if($company?->logoUrl)
        <img src="{{ $company->logoDisplayUrl() }}" alt="Logo" class="print-brand-logo h-12 mb-2 object-contain object-left">
        @endif
        <p class="print-brand-name text-base font-semibold tracking-[0.12em] text-neutral-900 uppercase">{{ $company?->name ?? 'Firma Adı' }}</p>
        @if(full_address($company ?? null))<p class="text-[11px] text-neutral-600 mt-1 leading-snug whitespace-pre-wrap">{{ full_address($company) }}</p>@endif
        <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1 text-[11px] text-neutral-600">
            @if($company?->phone)<span>Tel: {{ $company->phone }}</span>@endif
            @if($company?->email)<span>{{ $company->email }}</span>@endif
            @if($company?->taxNumber)<span>VKN: {{ $company->taxNumber }}@if($company->taxOffice) / {{ $company->taxOffice }}@endif</span>@endif
        </div>
    </div>
    <div class="text-right shrink-0 max-w-[45%]">
        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-neutral-500">{{ $documentTitle ?? 'BELGE' }}</p>
        <p class="print-doc-no text-2xl font-bold text-neutral-900 mt-1 leading-none">{{ $documentNumber ?? '—' }}</p>
        @if(!empty($documentSubtitle))
        <p class="text-[11px] text-neutral-600 mt-1">{{ $documentSubtitle }}</p>
        @endif
        @if(!empty($documentDate))
        <p class="text-[11px] text-neutral-500 mt-2">{{ $documentDate instanceof \DateTimeInterface ? $documentDate->format('d.m.Y') : $documentDate }}</p>
        @endif
    </div>
</div>
