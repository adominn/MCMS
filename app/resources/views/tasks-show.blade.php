@extends('layouts.app')
@section('title', $t->title)
@section('content')
<h1>{{ $t->title }}</h1>
<div class="card">
  <p>{{ __('Area: :area · Reference: :ref', ['area' => __($area), 'ref' => $t->subject_ref]) }}</p>
  <p class="muted">{{ __('The decision screen for this item arrives with the :area module in a later increment.', ['area' => strtolower($area)]) }}</p>
  <a class="btn ghost sm" href="{{ route('tasks') }}">&larr; {{ __('Back to my tasks') }}</a>
</div>
@endsection
