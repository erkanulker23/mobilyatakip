@php
    $company = $company ?? \App\Models\Company::first();
@endphp
<div class="print-brand-header print-section-lg flex flex-row justify-between items-start gap-6 pb-5 mb-5 border-b border-neutral-200 dark:border-neutral-700">
    <div class="flex-1 min-w-0">
        @if($company?->logoUrl)
        <img src="{{ $company->logoDisplayUrl() }}" alt="{{ $company->name ?? 'Logo' }}" class="h-10 max-h-10 w-auto max-w-[9rem] mb-2 object-contain object-left">
        @endif
        <p class="text-base font-semibold tracking-wide text-neutral-900 dark:text-neutral-100 uppercase">{{ $company?->name ?? 'Firma Adı' }}</p>
        @if(full_address($company ?? null))
        <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1 leading-relaxed whitespace-pre-wrap">{{ full_address($company) }}</p>
        @endif
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-neutral-600 dark:text-neutral-400">
            @if($company?->phone)<span>Tel: {{ $company->phone }}</span>@endif
            @if($company?->email)<span>{{ $company->email }}</span>@endif
            @if($company?->website)<span>{{ $company->website }}</span>@endif
            @if($company?->taxNumber)
            <span>VKN: {{ $company->taxNumber }}@if($company->taxOffice) · {{ $company->taxOffice }}@endif</span>
            @endif
        </div>
    </div>
    <div class="text-right shrink-0 max-w-[45%]">
        <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">{{ $documentTitle ?? 'BELGE' }}</p>
        <p class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 mt-1 leading-none">{{ $documentNumber ?? '—' }}</p>
        @if(!empty($documentSubtitle))
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-2">{{ $documentSubtitle }}</p>
        @endif
        @if(!empty($documentDate))
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
            Tarih: {{ $documentDate instanceof \DateTimeInterface ? $documentDate->format('d.m.Y') : $documentDate }}
        </p>
        @endif
    </div>
</div>
