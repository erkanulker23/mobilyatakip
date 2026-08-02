@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama" class="pagination-nav">
        {{-- Mobil --}}
        <div class="flex items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled flex-1 text-center">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn flex-1 text-center">{{ __('pagination.previous') }}</a>
            @endif

            <span class="text-xs text-neutral-500 dark:text-neutral-400 whitespace-nowrap px-1 tabular-nums">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn flex-1 text-center">{{ __('pagination.next') }}</a>
            @else
                <span class="pagination-btn pagination-btn-disabled flex-1 text-center">{{ __('pagination.next') }}</span>
            @endif
        </div>

        {{-- Masaüstü --}}
        <div class="hidden sm:flex sm:flex-wrap sm:items-center sm:justify-between sm:gap-3">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                @if ($paginator->total() > 0)
                    Toplam {{ number_format($paginator->total(), 0, ',', '.') }} kayıttan
                    <span class="font-medium text-neutral-700 dark:text-neutral-200">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                    arası
                @else
                    Kayıt yok
                @endif
            </p>

            <div class="inline-flex flex-wrap gap-1">
                @if ($paginator->onFirstPage())
                    <span class="pagination-btn pagination-btn-icon pagination-btn-disabled" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn pagination-btn-icon" aria-label="{{ __('pagination.previous') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="pagination-btn pagination-btn-disabled">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-btn pagination-btn-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pagination-btn" aria-label="{{ $page }}. sayfaya git">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn pagination-btn-icon" aria-label="{{ __('pagination.next') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                @else
                    <span class="pagination-btn pagination-btn-icon pagination-btn-disabled" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
