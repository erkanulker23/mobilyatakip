@props(['items' => []])
@if(count($items))
<nav class="flex items-center gap-2 text-neutral-500 text-sm mb-1" aria-label="Breadcrumb">
    @foreach($items as $i => $item)
        @if($i > 0)<span aria-hidden="true">/</span>@endif
        @if(!empty($item['url']))
            <a href="{{ $item['url'] }}" class="hover:text-neutral-900 transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="text-neutral-700">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
@endif
