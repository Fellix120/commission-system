@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">
                <span class="material-symbols-outlined">chevron_left</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">
                <span class="material-symbols-outlined">chevron_left</span>
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="is-dots">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">
                <span class="material-symbols-outlined">chevron_right</span>
            </a>
        @else
            <span class="is-disabled" aria-disabled="true">
                <span class="material-symbols-outlined">chevron_right</span>
            </span>
        @endif
    </nav>
@endif
