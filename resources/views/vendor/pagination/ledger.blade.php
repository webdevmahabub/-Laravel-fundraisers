@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Campaign pages">
        <p class="pager__count">
            {{ __('listing.showing') }}
            <span class="money">{{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}</span>
            {{ __('listing.showing_of') }} <span class="money">{{ $paginator->total() }}</span>
        </p>

        <div class="pager__links">
            @if ($paginator->onFirstPage())
                <span class="pager__btn is-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pager__btn" aria-label="{{ __('pagination.previous') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
                    </svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager__gap" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager__btn is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pager__btn" aria-label="{{ __('pagination.go_to_page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pager__btn" aria-label="{{ __('pagination.next') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
                    </svg>
                </a>
            @else
                <span class="pager__btn is-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
