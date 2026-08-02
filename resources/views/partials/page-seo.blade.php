@php
    $title = $title ?? '';
    $description = $description ?? '';
    $canonical = $canonical ?? url()->current();
    $breadcrumbs = $breadcrumbs ?? [];
    $robots = $robots ?? 'noindex, nofollow';
@endphp
@section('title', $title)
@section('meta_description', $description)
@section('canonical', $canonical)
@section('robots', $robots)
@if(count($breadcrumbs) > 0)
@push('structured_data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($breadcrumbs)->values()->map(function ($item, $index) use ($canonical) {
        return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'] ?? '',
            'item' => $item['url'] ?? $canonical,
        ];
    })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
@endpush
@endif
