@php
    $company = $company ?? \App\Models\Company::first();
@endphp
<div class="print-brand-header print-section-lg flex flex-row justify-between items-start gap-6 pb-5 mb-5 border-b-2 border-black dark:border-neutral-600">
    <div class="flex-1 min-w-0">
        @if($company?->logoUrl)
        <img src="{{ $company->logoDisplayUrl() }}" alt="Logo" class="print-brand-logo h-14 mb-3 object-contain object-left max-h-14 w-auto max-w-[11rem]">
        @endif
        <p class="print-brand-name text-lg font-semibold tracking-[0.1em] text-black dark:text-neutral-100 uppercase">{{ $company?->name ?? 'Firma Adı' }}</p>
        @if(full_address($company ?? null))<p class="text-sm text-black dark:text-neutral-300 mt-2 leading-relaxed whitespace-pre-wrap">{{ full_address($company) }}</p>@endif
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-black dark:text-neutral-300">
            @if($company?->phone)<span>Tel: {{ $company->phone }}</span>@endif
            @if($company?->email)<span>{{ $company->email }}</span>@endif
            @if($company?->taxNumber)<span>VKN: {{ $company->taxNumber }}@if($company->taxOffice) / {{ $company->taxOffice }}@endif</span>@endif
        </div>
    </div>
    <div class="text-right shrink-0 max-w-[45%]">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-black dark:text-neutral-300">{{ $documentTitle ?? 'BELGE' }}</p>
        <p class="print-doc-no text-3xl font-bold text-black dark:text-neutral-100 mt-2 leading-none">{{ $documentNumber ?? '—' }}</p>
        @if(!empty($documentSubtitle))
        <p class="text-sm text-black dark:text-neutral-300 mt-2">{{ $documentSubtitle }}</p>
        @endif
        @if(!empty($documentDate))
        <p class="text-sm text-black dark:text-neutral-300 mt-2">{{ $documentDate instanceof \DateTimeInterface ? $documentDate->format('d.m.Y') : $documentDate }}</p>
        @endif
    </div>
</div>
