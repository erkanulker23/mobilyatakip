@php
    use App\Support\CompanyBranding;

    $company = $company ?? \App\Models\Company::first();
    $pageTitle = trim($pageTitle ?? '');
    $siteName = CompanyBranding::siteName($company);
    $documentTitle = $documentTitle ?? CompanyBranding::documentTitle($pageTitle, $company);
    $metaDescriptionPlain = CompanyBranding::metaDescription($company, $metaDescription ?? null);
    $canonicalUrl = $canonicalUrl ?? url()->current();
    $robotsContent = $robots ?? 'noindex, nofollow';
    $ogImage = $company?->logoDisplayUrl();
@endphp
<title>{{ $documentTitle }}</title>
<meta name="description" content="{{ $metaDescriptionPlain }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="robots" content="{{ $robotsContent }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $documentTitle }}">
<meta property="og:description" content="{{ $metaDescriptionPlain }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
@if($ogImage)
<meta property="og:image" content="{{ $ogImage }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@endif
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $documentTitle }}">
<meta name="twitter:description" content="{{ $metaDescriptionPlain }}">
<meta name="application-name" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
