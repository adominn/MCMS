@props([
  'crumbs' => [],
  'back' => null,
])

@php
  // Determine hierarchical back URL if not explicitly provided or disabled
  $backUrl = $back;
  $backLabel = null;
  if ($backUrl === null && is_array($crumbs) && count($crumbs) > 1) {
    // Look for the closest preceding parent crumb that has a non-empty URL
    $parentCrumbs = array_slice($crumbs, 0, count($crumbs) - 1, true);
    foreach (array_reverse($parentCrumbs, true) as $label => $url) {
      if (!empty($url)) {
        $backUrl = $url;
        if (is_string($label) && !is_numeric($label)) {
          $backLabel = $label;
        }
        break;
      }
    }
  } elseif ($backUrl === false) {
    $backUrl = null;
  }
@endphp

@if(!empty($crumbs))
<nav class="breadcrumb-bar" aria-label="{{ __('Breadcrumb') }}">
  @if($backUrl)
    <a href="{{ $backUrl }}" class="btn-breadcrumb-back" title="{{ $backLabel ? (__('Back to') . ' ' . __($backLabel)) : __('Back') }}" aria-label="{{ $backLabel ? (__('Back to') . ' ' . __($backLabel)) : __('Back') }}">
      <span class="btn-back-arrow" aria-hidden="true">&larr;</span>
      <span class="btn-back-text">{{ __('Back') }}</span>
    </a>
  @endif
  <ol class="breadcrumb">
    @php
      $count = count($crumbs);
      $i = 0;
    @endphp
    @foreach($crumbs as $label => $url)
      @php
        $i++;
        $isLast = ($i === $count);
      @endphp
      @if(!$isLast && $url)
        <li class="breadcrumb-item"><a href="{{ $url }}">{{ __($label) }}</a></li>
      @else
        <li class="breadcrumb-item current" aria-current="page"><span>{{ __($label) }}</span></li>
      @endif
    @endforeach
  </ol>
</nav>
@endif
