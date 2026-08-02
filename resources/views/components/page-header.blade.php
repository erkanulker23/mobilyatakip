@props(['title', 'description' => null])
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        @isset($breadcrumb)
            {{ $breadcrumb }}
        @endisset
        <h1 class="page-title">{{ $title }}</h1>
        @if($description)
            <p class="page-desc">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
