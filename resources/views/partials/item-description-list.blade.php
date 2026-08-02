@php
    $lines = \App\Support\ItemDescription::lines($description ?? null);
    $listClass = $listClass ?? 'item-description-list list-disc list-inside text-xs text-neutral-500 dark:text-slate-400 mt-0.5 space-y-0.5';
@endphp
@if(count($lines) > 0)
<ul class="{{ $listClass }}">
    @foreach($lines as $line)
    <li>{{ $line }}</li>
    @endforeach
</ul>
@endif
