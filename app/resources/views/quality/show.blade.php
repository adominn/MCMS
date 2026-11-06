@extends('layouts.app')
@section('title', __('Quality Verification'))
@section('content')
@php($dd = auth()->user()->hasAnyRole('deputy_dean') && ! $isOwner)
@php($stLabels = ['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'Not applicable'])
<h1>{{ __('Quality Verification: :name', ['name' => $m->name_en]) }}</h1>
<p><a href="{{ route('quality') }}">{{ __('All Quality Verification') }}</a>@if($isOwner) &middot; <a href="{{ route('mcs.show', $m->id) }}">{{ __('Micro-credential') }}</a>@endif</p>

@if($standing)
<div class="card" id="in-force"><h2>{{ __('In force') }}</h2>
  <p><strong>{{ $standing->qv_reference }}</strong>: {{ rtrim(rtrim((string) $standing->verified_credit, '0'), '.') }} {{ __('credit') }}, MQF level {{ $standing->verified_mqf_level }}, {{ __('valid :from to :to.', ['from' => \Carbon\Carbon::parse($standing->valid_from)->format('d/m/Y'), 'to' => \Carbon\Carbon::parse($standing->valid_until)->format('d/m/Y')]) }}@if($standing->reverification) <span class="tag overdue">{{ __('Re-verification needed after a material change') }}</span>@endif</p>
  @if($standing->conditions)<p>{{ __('Conditions: :cond', ['cond' => $standing->conditions]) }}</p>@endif
  <p class="muted">{{ __('Quality Verification does not by itself mean a transfer, admission or stackable status.') }}</p>
  @if($dd)
  <form method="post" action="{{ route('quality.end', $m->id) }}">@csrf
    <label for="to">{{ __('If the QVC/MQA suspended or withdrew it') }}</label>
    <select id="to" name="to"><option value="suspended">{{ __('Suspended') }}</option><option value="withdrawn">{{ __('Withdrawn') }}</option></select>
    <label for="reason">{{ __('Reason') }}</label><input id="reason" name="reason" type="text" required>
    <button type="submit" class="btn danger sm">{{ __('Stop the claims') }}</button>
  </form>
  @endif
</div>
@endif

@if(! $rec)
<div class="card"><h2>{{ __('Basic conditions') }}</h2>
  <ul id="conditions">@foreach($conds as $c)<li><b>{{ $c['ok'] ? __('Met') : __('Not met') }}:</b> {{ $c['text'] }}</li>@endforeach</ul>
  @if($isOwner)
  <form method="post" action="{{ route('quality.start', $m->id) }}">@csrf<button type="submit" class="btn sm">{{ __('Start a Quality Verification cycle') }}</button></form>
  @else<p class="muted">{{ __('The lecturer who owns this micro-credential starts a cycle.') }}</p>@endif
</div>
@else
<h2 id="cycle">{{ __('Cycle :num: :status', ['num' => $rec->cycle_no, 'status' => __(config('qv.status')[$rec->status] ?? $rec->status)]) }}</h2>
@if($rec->outcome_note && $rec->status === 'preparing')<p class="alert">{{ __('Last result: not verified. :note', ['note' => $rec->outcome_note]) }}</p>@endif
<div class="card"><h3>{{ __('Basic conditions') }}</h3>
  <ul id="conditions">@foreach($conds as $c)<li><b>{{ $c['ok'] ? __('Met') : __('Not met') }}:</b> {{ $c['text'] }}</li>@endforeach</ul>
  @if(! $met)<p class="muted" id="why-not">{{ __('The micro-credential cannot be Ready to submit until every condition is met.') }}</p>@endif
</div>

<form method="post" action="{{ route('quality.evidence', $m->id) }}" class="card" id="evidence">@csrf
  <h3>{{ __('Evidence checklist') }}</h3>
  <p class="muted">{{ __('Evidence that MCMS holds is linked for you. Add a file reference for the rest.') }}</p>
  <table>
    <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Status') }}</th><th>{{ __('File reference') }}</th><th>{{ __('Notes') }}</th></tr></thead>
    <tbody>
    @foreach(config('qv.checklist') as $k => $label)
      @php($e = $evidence[$k] ?? null)
      <tr>
        <td><label for="st-{{ $k }}"><b>{{ __($label) }}</b></label></td>
        <td>@if($e?->linked){{ __('Available (linked from MCMS)') }}@elseif($isOwner)<select id="st-{{ $k }}" name="status[{{ $k }}]">@foreach($stLabels as $v => $t)<option value="{{ $v }}" @selected(($e->status ?? 'not_yet') === $v)>{{ __($t) }}</option>@endforeach</select>@else{{ __($stLabels[$e->status ?? 'not_yet']) }}@endif</td>
        <td>@if($e?->linked){{ $e->file_ref }}@elseif($isOwner)<input type="text" name="file[{{ $k }}]" value="{{ $e->file_ref ?? '' }}" aria-label="File reference for {{ $label }}">@else{{ $e->file_ref ?? '' }}@endif</td>
        <td>@if($isOwner && ! $e?->linked)<input type="text" name="notes[{{ $k }}]" value="{{ $e->notes ?? '' }}" aria-label="Notes for {{ $label }}">@else{{ $e->notes ?? '' }}@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  @if($isOwner && $rec->status !== 'submitted')<p><button type="submit" class="btn sm">{{ __('Save checklist') }}</button></p>@endif
</form>

@php($rv = json_decode($rec->change_review ?? 'null', true))
<form method="post" action="{{ route('quality.review', $m->id) }}" class="card" id="review">@csrf
  <h3>{{ __('Material change review') }}</h3>
  <p class="muted">{{ __('For each component, say whether it changed since the last verification and what the change needs.') }}</p>
  <table>
    <thead><tr><th>{{ __('Component') }}</th><th>{{ __('Change') }}</th><th>{{ __('What changed') }}</th></tr></thead>
    <tbody>
    @foreach(config('qv.components') as $k => $label)
      <tr><td><label for="ac-{{ $k }}"><b>{{ __($label) }}</b></label></td>
        <td>@if($isOwner)<select id="ac-{{ $k }}" name="action[{{ $k }}]">@foreach(config('qv.review_actions') as $v => $t)<option value="{{ $v }}" @selected(($rv[$k]['action'] ?? 'none') === $v)>{{ __($t) }}</option>@endforeach</select>@else{{ __(config('qv.review_actions')[$rv[$k]['action'] ?? 'none']) }}@endif</td>
        <td>@if($isOwner)<input type="text" name="note[{{ $k }}]" value="{{ $rv[$k]['note'] ?? '' }}" aria-label="What changed in {{ $label }}">@else{{ $rv[$k]['note'] ?? '' }}@endif</td></tr>
    @endforeach
    </tbody>
  </table>
  @if($isOwner && $rec->status !== 'submitted')<p><button type="submit" class="btn sm">{{ __('Save review') }}</button></p>@endif
</form>

<div class="card" id="readiness"><h3>{{ __('Readiness decision') }}</h3>
  @if($rec->readiness_decision)
    <p>{{ __('Recorded:') }} <strong>{{ __(config('qv.readiness')[$rec->readiness_decision]) }}</strong>@if($rec->target_date), {{ __('target date :date', ['date' => \Carbon\Carbon::parse($rec->target_date)->format('d/m/Y')]) }}@endif.@if($rec->readiness_missing) {{ __('Missing: :missing', ['missing' => $rec->readiness_missing]) }}@endif</p>
    <p>@if($rec->readiness_confirmed_at){{ __('Confirmed by the deputy dean on :date.', ['date' => \Carbon\Carbon::parse($rec->readiness_confirmed_at)->format('d/m/Y')]) }}@else<span class="tag">{{ __('Waiting for the deputy dean to confirm') }}</span>@endif</p>
  @endif
  @if($isOwner && $rec->status !== 'submitted')
  <form method="post" action="{{ route('quality.readiness', $m->id) }}">@csrf
    <div class="form-grid cols-3">
      <div class="field">
        <label for="decision">{{ __('Decision') }}</label>
        <select id="decision" name="decision">@foreach(config('qv.readiness') as $v => $t)<option value="{{ $v }}">{{ __($t) }}</option>@endforeach</select>
      </div>
      <div class="field">
        <label for="target_date">{{ __('Target date') }}</label>
        <input id="target_date" name="target_date" type="date">
      </div>
      <div class="field">
        <label for="missing">{{ __('Missing critical evidence') }}</label>
        <input id="missing" name="missing" type="text">
      </div>
    </div>
    <button type="submit" class="btn sm">{{ __('Record decision') }}</button>
  </form>
  @endif
  @if($dd && $rec->readiness_decision && ! $rec->readiness_confirmed_at)
  <form method="post" action="{{ route('quality.confirm', $m->id) }}">@csrf<button type="submit" class="btn sm">{{ __('Confirm readiness decision') }}</button></form>
  @endif
</div>

@if($dd || in_array($rec->status, ['ready', 'submitted']))
<div class="card" id="submission"><h3>{{ __('Submission to the QVC/MQA') }}</h3>
  @if($rec->status === 'ready' && $dd)
    <p><a href="{{ route('quality.package', $m->id) }}">{{ __('Open the submission package') }}</a></p>
    <form method="post" action="{{ route('quality.submit', $m->id) }}">@csrf
      <div class="form-grid cols-2">
        <div class="field">
          <label for="submitted_on">{{ __('Date submitted') }}</label>
          <input id="submitted_on" name="submitted_on" type="date" required>
        </div>
        <div class="field">
          <label for="reference">{{ __('QVC/MQA reference') }}</label>
          <input id="reference" name="reference" type="text" required>
        </div>
      </div>
      <button type="submit" class="btn sm">{{ __('Record submission') }}</button>
    </form>
  @elseif($rec->status === 'submitted')
    <p>{{ __('Submitted on :date, reference :ref.', ['date' => \Carbon\Carbon::parse($rec->submitted_on)->format('d/m/Y'), 'ref' => $rec->submitted_ref]) }} <a href="{{ route('quality.package', $m->id) }}">{{ __('Package') }}</a></p>
    @if($dd)
    <form method="post" action="{{ route('quality.result', $m->id) }}" id="result">@csrf
      <h4>{{ __('Record the result') }}</h4>
      <div class="form-grid cols-2">
        <div class="field">
          <label for="outcome">{{ __('Result') }}</label>
          <select id="outcome" name="outcome"><option value="verified">{{ __('Verified') }}</option><option value="not_verified">{{ __('Not verified') }}</option></select>
        </div>
        <div class="field">
          <label for="qv_reference">{{ __('QV reference') }}</label>
          <input id="qv_reference" name="qv_reference" type="text">
        </div>
      </div>
      <div class="form-grid cols-4">
        <div class="field">
          <label for="verified_credit">{{ __('Verified credit') }}</label>
          <input id="verified_credit" name="verified_credit" type="number" step="0.5" min="0">
        </div>
        <div class="field">
          <label for="verified_mqf_level">{{ __('MQF level') }}</label>
          <input id="verified_mqf_level" name="verified_mqf_level" type="number" min="1" max="8">
        </div>
        <div class="field">
          <label for="valid_from">{{ __('Valid from') }}</label>
          <input id="valid_from" name="valid_from" type="date">
        </div>
        <div class="field">
          <label for="valid_until">{{ __('Valid until (at most :years years)', ['years' => $maxYears]) }}</label>
          <input id="valid_until" name="valid_until" type="date">
        </div>
      </div>
      <div class="form-grid cols-2">
        <div class="field">
          <label for="conditions-in">{{ __('Conditions') }}</label>
          <input id="conditions-in" name="conditions" type="text">
        </div>
        <div class="field">
          <label for="decision_document">{{ __('Decision document') }}</label>
          <input id="decision_document" name="decision_document" type="text">
        </div>
      </div>
      <div class="field" style="margin-bottom:16px">
        <label for="note">{{ __('If not verified, why') }}</label>
        <input id="note" name="note" type="text">
      </div>
      <button type="submit" class="btn sm">{{ __('Record result') }}</button>
    </form>
    @endif
  @else
    <p class="muted" id="submit-off"><button type="button" disabled class="btn sm">{{ __('Record submission') }}</button> {{ __('Submission needs the status Ready to submit.') }}</p>
  @endif
</div>
@endif
@endif

@if($history->count())
<h2>{{ __('History') }}</h2>
<table id="history"><thead><tr><th>{{ __('Cycle') }}</th><th>{{ __('Status') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Valid') }}</th></tr></thead><tbody>
@foreach($history as $h)
  <tr><td>{{ $h->cycle_no }}</td><td>{{ __(config('qv.status')[$h->status] ?? $h->status) }}</td><td>{{ $h->qv_reference ?? $h->submitted_ref }}</td><td>@if($h->valid_until){{ \Carbon\Carbon::parse($h->valid_from)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($h->valid_until)->format('d/m/Y') }}@endif</td></tr>
@endforeach
</tbody></table>
@endif
@endsection
