@extends('layouts.app')
@section('title', __('Verify results'))
@section('content')
<div class="crumb">
  <a href="{{ route('results') }}">{{ __('Results and completion') }}</a>
  <span>/</span>
  <span>{{ $o->name_en }} {{ $o->cohort_name }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('Verify results: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">
      {{ $o->results_submitted_at ? __('Submitted :date', ['date' => \Carbon\Carbon::parse($o->results_submitted_at)->format('d/m/Y H:i')]) : __('Not submitted by the lecturer yet') }}
    </div>
  </div>
  <div class="act" style="gap:8px">
    <a class="btn ter sm" href="{{ route('completion.show', $o->id) }}">{{ __('Completion') }}</a>
    <a class="btn ter sm" href="{{ route('results') }}">{{ __('All cohorts') }}</a>
  </div>
</div>

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Summary') }}</h2></div>
  <div class="bd" style="padding:0">
    <table id="summary">
      <thead>
        <tr>
          <th>{{ __('Component') }}</th>
          <th>{{ __('Marks') }}</th>
          <th>{{ __('Provisional') }}</th>
          <th>{{ __('Held') }}</th>
          <th>{{ __('Verified') }}</th>
          <th>{{ __('Pass rate') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($summary as $s)
        <tr>
          <td><b>{{ $s['c']->name_en }}</b> <code class="s">{{ $keys[$s['c']->id] }}</code></td>
          <td>{{ $s['n'] }}</td>
          <td><span class="tag {{ $s['provisional'] ? 'warn' : 'n' }}">{{ $s['provisional'] }}</span></td>
          <td><span class="tag {{ $s['held'] ? 'err' : 'n' }}">{{ $s['held'] }}</span></td>
          <td><span class="tag {{ $s['verified'] ? 'ok' : 'n' }}">{{ $s['verified'] }}</span></td>
          <td>{{ $s['pass_rate'] === null ? '-' : $s['pass_rate'].'%' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

@if($cases->isNotEmpty())
  <div class="panel" style="margin-bottom:24px">
    <div class="hd"><h2>{{ __('Open integrity cases') }}</h2></div>
    <div class="bd">
      @foreach($cases as $c)
        <div class="panel" style="margin-bottom:12px;background:var(--surface)">
          <div class="bd">
            <div style="margin-bottom:8px"><b>{{ $c->name }}</b>: {{ $c->reason }}</div>
            <form method="post" action="{{ route('results.case', [$o->id, $c->id]) }}" class="row" style="gap:8px;align-items:center">
              @csrf
              <input type="text" name="reason" placeholder="{{ __('How it was resolved') }}" required minlength="5" aria-label="Resolution for {{ $c->name }}" style="flex:1;max-width:360px">
              <button type="submit" class="btn sm">{{ __('Resolve case') }}</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif

@if($changes->isNotEmpty())
  <div class="panel" style="margin-bottom:24px">
    <div class="hd"><h2>{{ __('Result changes waiting') }}</h2></div>
    <div class="bd">
      @foreach($changes as $q)
        <div class="panel" style="margin-bottom:12px;background:var(--surface)">
          <div class="bd">
            <div style="margin-bottom:8px">
              <b>{{ $q->name }}</b>, {{ $q->component }}: {{ $q->old_score }} {{ __('to') }} {{ $q->new_score }}.
              <span class="muted s">{{ __('Reason: :reason', ['reason' => $q->reason]) }}</span>
            </div>
            <form method="post" action="{{ route('results.decide', [$o->id, $q->id]) }}" class="row" style="gap:8px;align-items:center">
              @csrf
              <input type="text" name="note" placeholder="{{ __('Your note') }}" required minlength="5" aria-label="Note on the change for {{ $q->name }}" style="flex:1;max-width:320px">
              <button type="submit" name="decision" value="approve" class="btn sm">{{ __('Approve') }}</button>
              <button type="submit" name="decision" value="reject" class="btn ter sm">{{ __('Reject') }}</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Results') }}</h2></div>
  <div class="bd" style="padding:0">
    @include('results._table', ['flag' => false, 'editable' => false, 'changeable' => false])
  </div>
</div>

<form method="post" action="{{ route('results.verify.do', $o->id) }}">
  @csrf
  <div class="panel">
    <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
      <div>
        <h2 style="margin:0">{{ __('Verify & Confirm Results') }}</h2>
        <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Verifying creates module achievements and Open Badges 3.0 digital badges. It does not complete the micro-credential or issue a certificate.') }}</div>
      </div>
      @php($pending = $summary->sum('provisional'))
      <span class="tag {{ $pending > 0 ? 'warn' : 'ok' }}">{{ $pending }} {{ __('provisional waiting') }}</span>
    </div>
    <div class="bd">
      <fieldset style="border:0;padding:0;margin-bottom:20px">
        <legend style="font-weight:600;margin-bottom:10px;font-size:13px">{{ __('Select result scopes to verify (leave unticked to verify all modules):') }}</legend>
        <div class="row" style="gap:16px;flex-wrap:wrap">
          @foreach($modules as $m)
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;background:var(--bg-subtle, #f4f4f4);padding:8px 12px;border:1px solid var(--rule)">
              <input type="checkbox" name="scopes[]" value="m{{ $m->id }}">
              <span><b>Module {{ $m->seq }}:</b> {{ $m->name_en }}</span>
            </label>
          @endforeach
          @if($hasWhole)
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;background:var(--bg-subtle, #f4f4f4);padding:8px 12px;border:1px solid var(--rule)">
              <input type="checkbox" name="scopes[]" value="mc">
              <span><b>{{ __('Whole micro-credential assessment') }}</b></span>
            </label>
          @endif
        </div>
      </fieldset>

      <div class="field" style="margin-bottom:20px;max-width:560px">
        <label for="evidence" style="font-weight:600;font-size:13px;margin-bottom:6px;display:block">
          {{ __('Audit evidence linking identity, assessment and result') }} <span style="color:var(--danger, #da1e28)">*</span>
        </label>
        <input type="text" id="evidence" name="evidence" required minlength="5" placeholder="{{ __('e.g. UCreds proctoring log and photo ID check') }}" style="width:100%;height:38px;padding:0 12px;font-size:13px">
        <div class="muted s" style="font-size:11.5px;margin-top:4px">{{ __('Required for MQA compliance and Academic Bank of Credit (ABC) audit traceability.') }}</div>
      </div>

      <div class="row" style="gap:12px;align-items:center">
        <button type="submit" class="btn" @disabled(! $o->results_submitted_at || $pending === 0)>
          <span class="btn-lbl">{{ __('Verify :count results', ['count' => $pending]) }}</span> &rarr;
        </button>
        @if(! $o->results_submitted_at)
          <span class="muted s" style="font-size:12px">{{ __('Waiting for the lecturer to submit results.') }}</span>
        @elseif($pending === 0)
          <span class="muted s" style="font-size:12px">{{ __('All provisional results for this cohort have been verified.') }}</span>
        @endif
      </div>
    </div>
  </div>
</form>
@endsection
