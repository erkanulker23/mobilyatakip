@php
    $brand = $brand ?? \App\Support\CompanyBranding::siteName($company ?? null);
    $logoUrl = $logoUrl ?? (($company?->logoUrl && $company->logoDisplayUrl()) ? $company->logoDisplayUrl() : null);
    $homeUrl = $homeUrl ?? url('/');
@endphp
<a class="brand-lockup" href="{{ $homeUrl }}">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $brand }}">
    @else
        <span class="brand-word">{{ $brand }}</span>
    @endif
</a>
