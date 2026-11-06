@if ($paginator->hasPages() || (method_exists($paginator, 'total') && $paginator->total() > 0))
<nav class="pagination-bar" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
  <div class="p-left">
    <span>{{ $paginator->perPage() }} {{ __('items per page') }}</span>
  </div>

  <div class="p-center">
    @if(method_exists($paginator, 'total'))
      <span>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} {{ __('items') }}</span>
      <span style="margin-left:8px;font-size:12px;color:var(--text-muted, #525252)">{{ __('Page') }} {{ $paginator->currentPage() }} {{ __('of') }} {{ $paginator->lastPage() }}</span>
    @else
      <span>{{ __('Page') }} {{ $paginator->currentPage() }}</span>
    @endif
  </div>

  <div class="p-right">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
      <span class="p-btn disabled" aria-disabled="true" role="button" aria-label="{{ __('Previous page') }}">&lsaquo; {{ __('Prev') }}</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" class="p-btn" rel="prev" role="button" aria-label="{{ __('Previous page') }}">&lsaquo; {{ __('Prev') }}</a>
    @endif

    {{-- Numbered Page Links --}}
    @if(method_exists($paginator, 'lastPage'))
      @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
      @endphp

      @if($start > 1)
        <a href="{{ $paginator->url(1) }}" class="p-btn">1</a>
        @if($start > 2)
          <span class="p-btn disabled">&hellip;</span>
        @endif
      @endif

      @for ($page = $start; $page <= $end; $page++)
        @if ($page == $current)
          <span class="p-btn active" aria-current="page">{{ $page }}</span>
        @else
          <a href="{{ $paginator->url($page) }}" class="p-btn">{{ $page }}</a>
        @endif
      @endfor

      @if($end < $last)
        @if($end < $last - 1)
          <span class="p-btn disabled">&hellip;</span>
        @endif
        <a href="{{ $paginator->url($last) }}" class="p-btn">{{ $last }}</a>
      @endif
    @endif

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" class="p-btn" rel="next" role="button" aria-label="{{ __('Next page') }}">{{ __('Next') }} &rsaquo;</a>
    @else
      <span class="p-btn disabled" aria-disabled="true" role="button" aria-label="{{ __('Next page') }}">{{ __('Next') }} &rsaquo;</span>
    @endif
  </div>
</nav>
@endif
