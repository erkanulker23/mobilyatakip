@php
    $company = $company ?? \App\Models\Company::first();
    $footerNote = $footerNote ?? 'Bu belge bilgi amaçlıdır; imza ve kaşe olmadan resmi geçerliliği yoktur.';
@endphp
<div class="print-document-footer">
    <div class="print-document-footer__brand">
        <span class="print-document-footer__name">{{ $company?->name ?? 'Firma' }}</span>
        @if($company?->phone)<span class="print-document-footer__sep">·</span><span>Tel: {{ $company->phone }}</span>@endif
        @if($company?->email)<span class="print-document-footer__sep">·</span><span>{{ $company->email }}</span>@endif
        @if($company?->taxNumber)<span class="print-document-footer__sep">·</span><span>VKN: {{ $company->taxNumber }}</span>@endif
    </div>
    <div class="print-document-footer__meta">
        {{ now()->format('d.m.Y H:i') }}
        @if(!empty($documentRef))<span class="print-document-footer__sep">·</span><span>{{ $documentRef }}</span>@endif
    </div>
    @if($footerNote)
    <p class="print-document-footer__note">{{ $footerNote }}</p>
    @endif
</div>
