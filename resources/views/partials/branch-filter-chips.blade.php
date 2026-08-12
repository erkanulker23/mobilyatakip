@php
    $selectedBranchId = $selectedBranchId ?? request('branchId');
    $chipClass = fn (bool $active) => $active
        ? 'bg-teal-700 text-white dark:bg-teal-500 dark:text-neutral-900'
        : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700';
@endphp
@if(($branches ?? collect())->isNotEmpty())
<div class="flex flex-wrap items-center gap-2 {{ $wrapperClass ?? 'mb-4' }}">
    <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mr-1">Şube:</span>
    <a href="{{ $filterChip(['branchId' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $chipClass(! $selectedBranchId) }}">Tümü</a>
    @foreach($branches as $branch)
    <a href="{{ $filterChip(['branchId' => $branch->id]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $chipClass((string) $selectedBranchId === (string) $branch->id) }}">{{ $branch->name }}</a>
    @endforeach
    <a href="{{ $filterChip(['branchId' => 'none']) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $chipClass($selectedBranchId === 'none') }}">Şube belirtilmemiş</a>
</div>
@endif
