{{--
    RoomLink page links, used by every ->links() call (registered in AppServiceProvider).
    Laravel's default view needs Tailwind, which this app does not use.
--}}
@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Pagination">

        <p class="pager-summary">
            Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong>
        </p>

        <div class="pager-links">

            @if ($paginator->onFirstPage())
                <span class="pager-link pager-step is-disabled" aria-disabled="true">‹ Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pager-link pager-step" rel="prev">‹ Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-link pager-gap" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-link pager-page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pager-link pager-page" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pager-link pager-step" rel="next">Next ›</a>
            @else
                <span class="pager-link pager-step is-disabled" aria-disabled="true">Next ›</span>
            @endif

        </div>

    </nav>
@endif
