@props([
  'paginator',
  'singular' => 'item',
  'plural' => 'items',
])
@php
  $total = $paginator->total();
  $from = $paginator->firstItem() ?? 0;
  $to = $paginator->lastItem() ?? 0;
  $word = $total === 1 ? __($singular) : __($plural);
@endphp

@if($total > 0)
  <div class="pagination-bar" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px;padding:12px 16px;background:var(--color-canvas-subtle, #f8fafc);border:1px solid var(--color-card-border, #090a0f);border-radius:var(--radius-md, 6px)">
    <div class="s muted" style="font-variant-numeric:tabular-nums">
      @if($total === 1)
        {{ __('Showing 1 of 1') }} {{ $word }}
      @else
        {{ __('Showing :from–:to of :total', ['from' => $from, 'to' => $to, 'total' => $total]) }} {{ $word }}
      @endif
    </div>

    @if($paginator->hasPages())
      <div style="display:inline-flex;gap:6px;align-items:center">
        @if($paginator->onFirstPage())
          <span class="btn ter sm" style="opacity:.45;pointer-events:none">&larr; {{ __('Previous') }}</span>
        @else
          <a class="btn ter sm" href="{{ $paginator->previousPageUrl() }}">&larr; {{ __('Previous') }}</a>
        @endif

        <span class="s" style="padding:0 6px;font-variant-numeric:tabular-nums">
          {{ __('Page :current of :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
        </span>

        @if($paginator->hasMorePages())
          <a class="btn ter sm" href="{{ $paginator->nextPageUrl() }}">{{ __('Next') }} &rarr;</a>
        @else
          <span class="btn ter sm" style="opacity:.45;pointer-events:none">{{ __('Next') }} &rarr;</span>
        @endif
      </div>
    @endif
  </div>
@endif
