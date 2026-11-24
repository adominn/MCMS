@extends('layouts.app')
@section('title', __($heading))
@section('content')
@php
  $homeUrl = auth()->check() ? auth()->user()->homeUrl() : route('public.home');
  $primaryUrl = !empty($actionUrl) ? $actionUrl : $homeUrl;
  $primaryLabel = !empty($actionLabel) ? $actionLabel : (auth()->check() ? __('Go to my workspace') : __('Go to the home page'));
  $showTaskQueue = auth()->check()
    && !auth()->user()->hasAnyRole('industry_expert', 'reviewer')
    && $primaryUrl !== route('tasks');
@endphp
<div class="panel" style="margin:24px 0;max-width:700px">
  <div class="hd" style="flex-direction:column;align-items:flex-start;gap:4px">
    <span style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--color-primary, #0052ff)">
      {{ __('Access handoff') }}
    </span>
    <h1 style="margin:0;font-size:20px;line-height:1.3">{{ __($heading) }}</h1>
  </div>
  <div class="bd" style="display:flex;flex-direction:column;gap:14px">
    @if(!empty($attempted))
      <div style="font-size:13px;color:var(--color-ink, #090a0f)">
        <span class="muted">{{ __('Requested screen:') }}</span>
        <b>{{ __($attempted) }}</b>
      </div>
    @endif
    <div class="muted" style="line-height:1.6">{{ __($text) }}</div>
    @if(!empty($handledBy))
      <div class="note info" style="margin:0">
        <b>{{ __('Handled by:') }}</b> {{ __($handledBy) }}
      </div>
    @endif
    <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:4px;align-items:center">
      <a class="btn" href="{{ $primaryUrl }}">{{ __($primaryLabel) }}</a>
      @if($showTaskQueue)
        <a class="btn ter" href="{{ route('tasks') }}">{{ __('Go to Task Queue') }}</a>
      @endif
      <a class="btn ghost" href="javascript:history.back()">{{ __('Go back') }}</a>
    </div>
  </div>
</div>
@endsection
