@extends('layouts.app')
@section('title', __('Participants'))
@section('content')
<h1>{{ __('Participants: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
<p><a href="{{ route('offerings.show', $o->id) }}">{{ __('Back to the cohort') }}</a> &middot; <a href="{{ route('offerings.participants', ['id' => $o->id, 'status' => $status, 'export' => 'csv']) }}">{{ __('Export CSV') }}</a> &middot; {{ __(':taken of :cap places taken', ['taken' => $taken, 'cap' => $o->capacity]) }}</p>
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
