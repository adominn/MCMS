@extends('layouts.app')
@section('title', 'Trace a participant')
@section('content')
<h1>Trace: {{ $user->name }}, {{ $version->name_en }}</h1>
<p><a href="{{ route('trace') }}">Traceability</a> &middot; {{ $enrolment->reference }} &middot; cohort {{ $offering->cohort_name }}</p>
<p class="muted">{{ __('From development to monitoring, with what is on record at each stage. A stage that is empty has not happened yet, or its record is missing.') }}</p>
<ol id="chain" class="chain">
@foreach($stages as $s)
  <li data-stage="{{ $s['key'] }}" class="{{ $s['present'] ? 'present' : 'absent' }}"><strong>{{ $s['label'] }}</strong>: {{ $s['present'] ? 'On record' : 'Not on record' }}. {{ $s['text'] }}@if($s['link'] && $s['present']) <a href="{{ $s['link'] }}">Open</a>@endif</li>
@endforeach
</ol>
<h2>{{ __('Audit trail of these records') }}</h2>
<table id="trace-audit">
  <thead><tr><th>{{ __('Seq') }}</th><th>{{ __('Time') }}</th><th>{{ __('Action') }}</th><th>{{ __('Record') }}</th><th>{{ __('By') }}</th></tr></thead>
  <tbody>
  @forelse($audit as $a)<tr><td>{{ $a->seq }}</td><td>{{ \Carbon\Carbon::parse($a->occurred_at)->format('d/m/Y H:i') }}</td><td>{{ $a->action }}@if($a->reason)<br><span class="muted">{{ $a->reason }}</span>@endif</td><td>{{ $a->subject_type }} {{ $a->subject_id }}</td><td>{{ $a->actor ?? 'System' }}{{ $a->actor_role ? ' ('.$a->actor_role.')' : '' }}</td></tr>
  @empty<tr><td colspan="5" class="muted">{{ __('No audit entry for these records.') }}</td></tr>@endforelse
  </tbody>
</table>
<p><a href="{{ route('cohort.records', $offering->id) }}">{{ __('Minimum records of this cohort') }}</a></p>
@endsection
