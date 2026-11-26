@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp
<nav class="account-pagination" aria-label="Order history pages">
    <p class="account-pagination-summary">Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> orders</p>
    <div class="account-pagination-controls">
        @if ($paginator->onFirstPage())
            <span class="account-page-control is-disabled" aria-disabled="true">Previous</span>
        @else
            <a class="account-page-control" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        @if ($startPage > 1)
            <a class="account-page-number" href="{{ $paginator->url(1) }}" aria-label="Go to page 1">1</a>
            @if ($startPage > 2)<span class="account-pagination-ellipsis" aria-hidden="true">…</span>@endif
        @endif

        @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $currentPage)
                <span class="account-page-number is-current" aria-current="page">{{ $page }}</span>
            @else
                <a class="account-page-number" href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($endPage < $lastPage)
            @if ($endPage < $lastPage - 1)<span class="account-pagination-ellipsis" aria-hidden="true">…</span>@endif
            <a class="account-page-number" href="{{ $paginator->url($lastPage) }}" aria-label="Go to page {{ $lastPage }}">{{ $lastPage }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="account-page-control" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="account-page-control is-disabled" aria-disabled="true">Next</span>
        @endif
    </div>
</nav>
