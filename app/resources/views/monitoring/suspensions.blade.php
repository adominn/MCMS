@extends('layouts.app')
@section('title', 'Suspension or termination')
@section('content')
<h1>{{ __('Suspension or termination') }}</h1>
<p><a href="{{ route('monitoring') }}">Quality review</a></p>
<p class="muted">{{ __('A suspension or termination protects current participants. Achievements, credentials and Academic Credit Records already earned stay valid, and every related record is kept.') }}</p>

<form method="get" action="{{ route('suspensions') }}" class="card">
  <label for="mc">{{ __('Micro-credential') }}</label>
  <select id="mc" name="mc"><option value="">Choose</option>@foreach($mcs as $m)<option value="{{ $m->id }}" @selected($selected && $selected->id === $m->id)>{{ $m->name_en }}</option>@endforeach</select>
  <button type="submit">{{ __('Show active participants') }}</button>
</form>

@if($selected)
<div class="card" id="participants">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Active participants: {{ $selected->name_en }}</h2>
    <a href="{{ route('suspensions.print', $selected->id) }}" target="_blank" class="btn sm ter">{{ __('Preview notice (A4)') }}</a>
  </div>
  <ul>@forelse($participants as $p)<li>{{ $p->name }} ({{ $p->cohort_name }})</li>@empty<li class="muted">None.</li>@endforelse</ul>
</div>
<form method="post" action="{{ route('suspensions.decide') }}" class="card" id="decide"><h2>{{ __('Decision') }}</h2>@csrf
  <input type="hidden" name="micro_credential_id" value="{{ $selected->id }}">
  <label for="kind">{{ __('Decision') }}</label><select id="kind" name="kind"><option value="suspension">Suspension</option><option value="termination">Termination</option></select>
  <label for="reason">{{ __('Reason') }}</label><input id="reason" name="reason" type="text" required>
  <label for="effective_from">{{ __('Effective from') }}</label><input id="effective_from" name="effective_from" type="date" value="{{ today()->toDateString() }}" required>
  <label for="transition_type">{{ __('Transition plan') }}</label>
  <select id="transition_type" name="transition_type"><option value="">None</option>@foreach($transition as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select>
  <label for="transition_plan">{{ __('Plan in words (required before termination)') }}</label><textarea id="transition_plan" name="transition_plan" rows="2"></textarea>
  <p class="muted">{{ __('The participants listed above are notified of the status, their options, the period to act and what it means for their credentials.') }}</p>
  <button type="submit">{{ __('Record decision') }}</button>
</form>
@endif

<h2>{{ __('Decisions') }}</h2>
<table id="decisions"><thead><tr><th>{{ __('Micro-credential') }}</th><th>{{ __('Decision') }}</th><th>{{ __('From') }}</th><th>{{ __('Notified') }}</th><th></th></tr></thead><tbody>
@forelse($rows as $s)<tr><td>{{ $s->name_en }}</td><td>{{ ucfirst($s->kind) }}@if($s->lifted_at) (lifted)@endif</td><td>{{ \Carbon\Carbon::parse($s->effective_from)->format('d/m/Y') }}</td><td>{{ $s->participants_notified }}</td>
  <td>
    <a href="{{ route('suspensions.print', $s->id) }}" target="_blank" class="btn sm ter">{{ __('Print notice') }}</a>
    @if($s->kind === 'suspension' && ! $s->lifted_at)<form method="post" action="{{ route('suspensions.lift', $s->id) }}" style="display:inline">@csrf<button type="submit" aria-label="Lift the suspension of {{ $s->name_en }}">{{ __('Lift') }}</button></form>@endif
  </td></tr>
@empty<tr><td colspan="5" class="muted">None.</td></tr>@endforelse
</tbody></table>
@endsection
