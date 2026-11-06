@extends('layouts.app')
@section('title', $c->number)
@section('content')
@php
  $modules = $modules ?? collect();
@endphp
<h1>{{ $c->number }}</h1>
<p style="display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
  <span class="tag ok">{{ __(['valid' => 'Valid', 'corrected' => 'Corrected', 'replaced' => 'Replaced', 'revoked' => 'Revoked'][$c->status] ?? $c->status) }}</span>
  <span class="small muted">{{ __('revision :rev · :name · code', ['rev' => $c->revision, 'name' => $x['name']['en']]) }} <code>{{ $c->public_code }}</code></span>
  <a class="btn ter sm" href="{{ route('public.verify.show', $c->public_code) }}" target="_blank">{{ __('Public page') }}</a>
  <a class="btn ter sm" href="{{ route('credentials.admin.sheet', $c->id) }}" target="_blank">{{ __('Printable sheet') }}</a>
  <a class="btn ghost sm" href="{{ route('credentials.admin') }}">&larr; {{ __('All credentials') }}</a>
</p>
@if($newer)<div class="status" role="status">{{ __('Replaced by') }} <a href="{{ route('credentials.admin.show', $newer->id) }}">{{ $newer->number }}</a>.</div>@endif
@if($c->status === 'revoked')<div class="status error" role="alert">{{ __('Revoked :date: :reason', ['date' => \Carbon\Carbon::parse($c->status_changed_at)->format('d/m/Y'), 'reason' => $c->status_reason]) }}</div>@endif
<div class="card"><p>{{ __('Recipient on the credential:') }} <b>{{ $x['holder'] }}</b></p>
  <p>{{ $x['statement']['en'] }}</p><p class="muted s">{{ __('Credit, MQF level and Quality Verification wording come from verified records and cannot be typed.') }}</p></div>

@if($modules->isNotEmpty())
<div class="card" style="margin-top:16px">
  <div style="margin-bottom:12px">
    <h2 style="margin:0">{{ __('Completed Modules & Earned Badges') }}</h2>
  </div>
  <p class="small muted" style="margin:0 0 12px">{{ __('Modules completed under this micro-credential and digital badges earned by the learner.') }}</p>
  <table>
    <thead>
      <tr>
        <th>{{ __('Module') }}</th>
        <th class="num">{{ __('Hours') }}</th>
        <th>{{ __('Earned Badge') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Achieved Date') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($modules as $m)
        <tr>
          <td>
            <strong>{{ $m->seq }}. {{ $m->name_en }}</strong>
            @if(!empty($m->name_ms))
              <div class="s muted">{{ $m->name_ms }}</div>
            @endif
          </td>
          <td class="num">{{ $m->slt_hours }}</td>
          <td>
            @if(!empty($m->badge_id))
              <div style="display:flex;align-items:center;gap:6px">
                <x-badge :category="$enrolment->mc_category ?? 'umc'" :size="24" />
                <span><a href="{{ route('verify.show', $m->badge_code) }}" target="_blank" title="{{ __('Verify badge') }}"><strong>{{ $m->badge_code }}</strong> <i class="bi bi-box-arrow-up-right" style="font-size:10px"></i></a> @if(!empty($m->badge_external_id)) &middot; UCreds {{ $m->badge_external_id }} @endif</span>
              </div>
            @elseif(!empty($m->separately_recognisable))
              <span class="tag neutral" style="font-size:11px">{{ __('Badge eligible') }}</span>
            @else
              <span class="muted" style="font-size:11px">{{ __('Course unit') }}</span>
            @endif
          </td>
          <td>
            @if($m->is_achieved)
              <span class="tag ok">{{ __('Achieved') }}</span>
            @else
              <span class="tag neutral">{{ __('Incomplete') }}</span>
            @endif
          </td>
          <td class="s">
            {{ $m->achieved_at ? \Carbon\Carbon::parse($m->achieved_at)->format('d/m/Y') : '-' }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

@if(in_array($c->status, ['valid', 'corrected']))
<form method="post" action="{{ route('credentials.correct', $c->id) }}" class="card" style="margin-top:16px">@csrf
  <h2>{{ __('Start correction') }}</h2><p class="muted s">{{ __('Only for a confirmed error. The original stays in the history.') }}</p>
  <label for="holder">{{ __('Name as it should read') }}</label><input type="text" id="holder" name="holder" value="{{ $x['holder'] }}" required>
  <label for="creason">{{ __('Reason') }}</label><input type="text" id="creason" name="reason" required minlength="5">
  <p><button type="submit" class="btn sm">{{ __('Correct the credential') }}</button></p>
</form>
<form method="post" action="{{ route('credentials.replace', $c->id) }}" class="card" style="margin-top:16px">@csrf
  <h2>{{ __('Replace') }}</h2><p class="muted s">{{ __('Issues a new credential with a new code. The old code then shows Replaced and points to the new one.') }}</p>
  <label for="rreason">{{ __('Reason') }}</label><input type="text" id="rreason" name="reason" required minlength="5">
  <p><button type="submit" class="btn sm">{{ __('Replace the credential') }}</button></p>
</form>
<form method="post" action="{{ route('credentials.revoke', $c->id) }}" class="card" style="margin-top:16px" data-confirm="{{ __('Revoke this credential?|Public verification will then say Revoked and show no result. The reason is kept in the audit trail.') }}" data-danger>@csrf
  <h2>{{ __('Revoke') }}</h2><p class="muted s">{{ __('A recorded decision. The public page shows Revoked and the date, and no result.') }}</p>
  <label for="vreason">{{ __('Reason') }}</label><input type="text" id="vreason" name="reason" required minlength="5">
  <p><button type="submit" class="btn danger sm">{{ __('Revoke the credential') }}</button></p>
</form>
@endif

<h2 style="margin-top:24px">{{ __('History') }}</h2>
<table>
  <thead><tr><th>{{ __('Revision') }}</th><th>{{ __('Event') }}</th><th>{{ __('When') }}</th><th>{{ __('By') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Name on it') }}</th></tr></thead>
  <tbody>@foreach($revisions as $r)@php($rc = json_decode($r->content, true))<tr><td>{{ $r->revision }}</td><td>{{ __(ucfirst($r->event)) }}</td><td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td><td>{{ $r->by ?? __('System') }}</td><td>{{ $r->reason }}</td><td>{{ $rc['holder'] }}</td></tr>@endforeach</tbody>
</table>
@endsection
