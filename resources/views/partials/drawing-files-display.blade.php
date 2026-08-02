@php
    $entries = \App\Support\DrawingFiles::existingEntries(
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
            @php $fileUrl = \App\Support\DrawingFiles::url($entry['path']); @endphp
            <div class="rounded-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
                @if(\App\Support\DrawingFiles::isImage($entry) && $fileUrl)
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="block aspect-video bg-neutral-50 dark:bg-slate-800">
                    <img src="{{ $fileUrl }}" alt="{{ $entry['name'] }}" class="w-full h-full object-contain p-2">
                </a>
                @else
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="flex aspect-video items-center justify-center bg-red-50 dark:bg-red-900/20 hover:bg-red-100/80 transition-colors">
                    <div class="text-center px-4">
                        <svg class="w-10 h-10 mx-auto text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">PDF</p>
                    </div>
                </a>
                @endif
                <div class="p-3 border-t border-neutral-100 dark:border-slate-700">
                    @if($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline break-all">{{ $entry['name'] }}</a>
                    @else
                    <p class="text-sm font-medium break-all">{{ $entry['name'] }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
