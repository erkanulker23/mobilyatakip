@php
    $entries = $entries ?? \App\Support\DrawingFiles::existingEntries(
        \App\Support\DrawingFiles::entries($drawingFiles ?? [])
    );
@endphp
@if(count($entries) > 0)
<div class="card overflow-hidden">
    <div class="card-header flex items-center justify-between">
        <span>Çizim Dosyaları</span>
        <span class="text-xs font-normal text-neutral-500">{{ count($entries) }} dosya</span>
    </div>
    <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($entries as $entry)
            @php
                $fileUrl = \App\Support\DrawingFiles::url($entry['path']);
                $kindLabel = \App\Support\DrawingFiles::kindLabel($entry);
                $isImage = \App\Support\DrawingFiles::isImage($entry);
                $isPdf = \App\Support\DrawingFiles::isPdf($entry);
                $isDwg = \App\Support\DrawingFiles::isDwg($entry);
                $previewClass = match (true) {
                    $isImage => 'bg-neutral-50 dark:bg-slate-800',
                    $isPdf => 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100/80',
                    $isDwg => 'bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100/80',
                    default => 'bg-neutral-50 dark:bg-slate-800 hover:bg-neutral-100/80',
                };
                $labelClass = match (true) {
                    $isPdf => 'text-red-700 dark:text-red-300',
                    $isDwg => 'text-sky-700 dark:text-sky-300',
                    default => 'text-neutral-700 dark:text-neutral-300',
                };
            @endphp
            <div class="rounded-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
                @if($isImage && $fileUrl)
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="block aspect-video {{ $previewClass }}">
                    <img src="{{ $fileUrl }}" alt="{{ $entry['name'] }}" class="w-full h-full object-contain p-2">
                </a>
                @elseif($fileUrl)
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" download class="flex aspect-video items-center justify-center {{ $previewClass }} transition-colors">
                    <div class="text-center px-4">
                        @if($isPdf)
                        <svg class="w-10 h-10 mx-auto text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @elseif($isDwg)
                        <svg class="w-10 h-10 mx-auto text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        @else
                        <svg class="w-10 h-10 mx-auto text-neutral-500 dark:text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @endif
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide {{ $labelClass }}">{{ $kindLabel }}</p>
                    </div>
                </a>
                @else
                <div class="flex aspect-video items-center justify-center {{ $previewClass }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $labelClass }}">{{ $kindLabel }}</p>
                </div>
                @endif
                <div class="p-3 border-t border-neutral-100 dark:border-slate-700">
                    @if($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" @unless($isImage) download @endunless class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline break-all">{{ $entry['name'] }}</a>
                    @else
                    <p class="text-sm font-medium break-all">{{ $entry['name'] }}</p>
                    @endif
                    <p class="text-[11px] text-neutral-500 mt-1">{{ $kindLabel }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
