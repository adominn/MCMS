@extends('layouts.app')
@section('title', __('Participants'))
@section('content')
<x-breadcrumb :crumbs="[
  (auth()->user()->hasAnyRole('deputy_dean') ? 'Offerings and readiness' : 'Cohorts and results') => route('offerings'),
  $o->name_en . ' ' . $o->cohort_name => route('offerings.show', $o->id),
  'Participants' => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('Participants: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">
      {{ __(':taken of :cap places taken', ['taken' => $taken, 'cap' => $o->capacity]) }}
    </div>
  </div>
  <div class="act-bar">
    <div class="act-left">
      <a class="btn ter sm" href="{{ route('offerings.show', $o->id) }}">&larr; {{ __('Back to the cohort') }}</a>
    </div>
    <div class="act-right">
      <a class="btn ter sm" href="{{ route('offerings.participants', ['id' => $o->id, 'status' => $status, 'export' => 'csv']) }}">{{ __('Export CSV') }}</a>
    </div>
  </div>
</div>
<nav class="tabs" aria-label="Status">
  <a href="{{ route('offerings.participants', $o->id) }}" @if(!$status) aria-current="page" class="on" @endif>{{ __('All') }}</a>
  @foreach($labels as $k => $l)<a href="{{ route('offerings.participants', ['id' => $o->id, 'status' => $k]) }}" @if($status === $k) aria-current="page" class="on" @endif>{{ __($l) }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
</nav>
<table style="margin-top:16px">
  <thead><tr><th>{{ __('Registration') }}</th><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Status') }}</th><th>{{ __('Registered') }}</th></tr></thead>
  <tbody>
  @forelse($rows as $r)<tr><td><b>{{ $r->reference }}</b> <a href="{{ route('records.show', $r->id) }}">{{ __('Record') }}</a></td><td>{{ $r->name }}</td><td>{{ $r->email }}</td><td><x-pill :s="__($labels[$r->status] ?? $r->status)" /></td><td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</td></tr>
  @empty<tr><td colspan="5" class="muted">{{ __('No participants.') }}</td></tr>@endforelse
  </tbody>
</table>
@endsection
