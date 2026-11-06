@extends('layouts.app')
@section('title', __('Micro-credential'))
@section('content')
@php($head = $current ?? $versions->first())
<div class="crumb" style="margin-bottom:8px"><a href="{{ route('mcs') }}">{{ __('My micro-credentials') }}</a> <span>/</span> <span>{{ $versions->first()->name_en }}</span></div>
<div class="title">
  <div style="display:flex;align-items:center;gap:16px">
    <x-badge :category="$m->category" :size="52" />
    <div>
      <h1><span class="cat">{{ strtoupper($m->category) }}</span> {{ $versions->first()->name_en }}</h1>
      <div class="sub">{{ __('version :num', ['num' => $head->version_no]) }}@if($head->planned_slt) &middot; {{ $head->planned_slt }} h {{ __('learning time') }} @endif &middot; <x-pill :s="__($statusLabels[$head->status] ?? ucfirst($head->status))" /></div>
    </div>
  </div>
  <div class="act">
    @if($current && !$open)<a class="btn ter" href="{{ route('mcs.change', $m->id) }}">{{ __('Request a change') }}</a>@endif
  </div>
</div>
@if($open)<div class="note info">{{ __('Version :num is in progress (:status). A new change waits until it is decided.', ['num' => $open->version_no, 'status' => strtolower($statusLabels[$open->status] ?? $open->status)]) }} <a href="{{ route('proposals.show', $open->id) }}">{{ __('Open it') }}</a></div>@endif

<div class="panel"><div class="bd"><dl class="dl">
  <dt>{{ __('Category') }}</dt><dd><span class="cat">{{ strtoupper($m->category) }}</span> {{ __(['umc' => 'Unbundled Micro-Credential', 'samc' => 'Standalone Micro-Credential', 'mce' => 'Micro-Credential Eksekutif'][$m->category] ?? '') }}</dd>
  <dt>{{ __('Parent course') }}</dt><dd>{{ $m->code ?: __('None, own offering') }}</dd>
  <dt>{{ __('Modules') }}</dt><dd>@forelse($modules as $x){{ $x->seq }}. {{ $x->name_en }} ({{ $x->slt_hours }} h)@if(!$loop->last); @endif @empty<span class="muted">{{ __('No approved version yet.') }}</span>@endforelse</dd>
</dl></div></div>

<div data-tabs style="margin-top:16px">
  <nav class="tabs" aria-label="Micro-credential sections">
    <button data-tab="versions">{{ __('Versions') }} <span class="muted">{{ $versions->count() }}</span></button>
    <button data-tab="cohorts">{{ __('Cohorts') }} <span class="muted">{{ $cohorts->count() }}</span></button>
    <button data-tab="changes">{{ __('Change requests') }} <span class="muted">{{ $changes->count() }}</span></button>
    @if($current && !$open)<button data-tab="reoffer">{{ __('Re-offer') }}</button>@endif
  </nav>
  <div data-pane="versions" class="panel">
    <table id="versions">
      <thead><tr><th>{{ __('Version') }}</th><th>{{ __('Status') }}</th><th>{{ __('Effective') }}</th><th>{{ __('Change') }}</th><th>{{ __('Decision') }}</th><th>{{ __('Current participants') }}</th></tr></thead>
      <tbody>
      @foreach($versions as $v)
        @php($dec = ($decisions[$v->id] ?? collect())->last())
        <tr>
          <td><a href="{{ route('proposals.show', $v->id) }}">Version {{ $v->version_no }}</a></td>
          <td><x-pill :s="__($statusLabels[$v->status] ?? ucfirst($v->status))" /></td>
          <td>{{ $v->effective_date ? \Carbon\Carbon::parse($v->effective_date)->format('d/m/Y') : '' }}</td>
          <td>{{ __(['initial' => 'First version', 'minor' => 'Minor', 'material' => 'Material change', 'reoffer' => 'Re-offer'][$v->change_class] ?? $v->change_class) }}@if($v->change_summary)<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ $v->change_summary }}</span>@endif</td>
          <td>@if($dec){{ __(str_replace('_', ' ', ucfirst($dec->decision))) }}, {{ $dec->meeting_ref }}@endif</td>
          <td>@if($rules->has($v->id)){{ __($rulesText[$rules[$v->id]->rule] ?? $rules[$v->id]->rule) }}@else<span class="muted">{{ $v->supersedes_id ? __('Rule recorded at approval') : __('No change') }}</span>@endif</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div data-pane="cohorts" class="panel">
    <table id="cohorts">
      <thead><tr><th>{{ __('Cohort') }}</th><th>{{ __('Dates') }}</th><th>{{ __('Status') }}</th></tr></thead>
      <tbody>
      @forelse($cohorts as $c)
        <tr><td><a href="{{ route('offerings.show', $c->id) }}">{{ $c->cohort_name }}</a></td><td data-v="{{ $c->start_date }}">{{ \Carbon\Carbon::parse($c->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}</td><td><x-pill :s="__(ucfirst($c->status))" /></td></tr>
      @empty
        <tr><td colspan="3" class="muted">{{ __('No cohorts yet. Create an offering from an approved version.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div data-pane="changes" class="panel">
    <table>
      <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Class') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Status') }}</th></tr></thead>
      <tbody>
      @forelse($changes as $c)
        <tr><td>{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y') }}</td><td>{{ __(ucfirst($c->class)) }}@if($c->requires_qv) <span class="tag">{{ __('Quality Verification repeats') }}</span>@endif</td><td>{{ $c->reason }}</td>
          <td>{{ __(['recorded' => 'Recorded, waiting for acknowledgement', 'acknowledged' => 'Acknowledged', 'new_version' => 'New version in progress', 'approved' => 'Approved'][$c->status] ?? $c->status) }}</td></tr>
      @empty
        <tr><td colspan="4" class="muted">{{ __('No change requests.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($current && !$open)
  <div data-pane="reoffer" class="panel"><div class="bd">
    <form method="post" action="{{ route('mcs.reoffer', $m->id) }}" class="col" style="max-width:520px">@csrf
      <div class="field"><label for="summary">{{ __('Re-offer: why is it offered again?') }}</label><input type="text" id="summary" name="summary" required minlength="5"></div>
      <div><button type="submit" class="btn sm">{{ __('Start a re-offer') }}</button></div>
    </form>
  </div></div>
  @endif
</div>
@endsection
