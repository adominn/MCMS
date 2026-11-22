@extends('layouts.app')
@section('title', __('Coordinator completion sign-off (F11)'))
@section('content')
<x-breadcrumb :crumbs="[
  'Cohorts and results' => route('offerings'),
  $o->name_en . ' ' . $o->cohort_name => route('offerings.show', $o->id),
  'Coordinator sign-off (F11)' => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('Coordinator Completion Sign-off') }}: {{ $o->name_en }} {{ $o->cohort_name }}</h1>
    <div class="sub">{{ __('Course Coordinator / Lecturer Verification') }} &middot; {{ strtoupper($o->category) }}</div>
  </div>
  <div class="act" style="gap:8px">
    <a class="btn ter sm" href="{{ route('results.cohort', $o->id) }}">{{ __('Results') }}</a>
    <a class="btn ter sm" href="{{ route('cohorts.credits', $o->id) }}">{{ __('Cohort ACR progress') }}</a>
    <a class="btn ter sm" href="{{ route('offerings.show', $o->id) }}">{{ __('Cohort overview') }}</a>
  </div>
</div>

<x-credit-lifecycle :stage="2" :note="__('Course Coordinator sign-off before Deputy Dean completion')" />

<form method="post" action="{{ route('completion.signoff.store', $o->id) }}">
  @csrf
  <div class="panel">
    <div class="hd">
      <h2>{{ __('Participants awaiting coordinator sign-off') }}</h2>
      <span class="r"><span class="tag {{ $waitingSignOff > 0 ? 'warn' : 'ok' }}">{{ $waitingSignOff }} {{ __('ready to sign') }}</span></span>
    </div>
    <div class="table-wrap">
      <table id="signoff-table">
        <thead>
          <tr>
            <th style="width:44px;text-align:center"></th>
            <th style="width:24%">{{ __('Participant') }}</th>
            <th style="width:16%">{{ __('P1 Sign-off (F11 Row 1)') }}</th>
            <th style="width:16%">{{ __('P2 Completion') }}</th>
            <th>{{ __('Academic & assessment requirements') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $e)
          <tr>
            <td style="text-align:center">
              @if($e->status === 'enrolled' && $e->check['met'] && !$e->coordinator_signed)
                <input type="checkbox" name="enrolments[]" value="{{ $e->id }}" checked aria-label="Sign off {{ $e->name }}">
              @endif
            </td>
            <td>
              <b>{{ $e->name }}</b>
              <div class="s muted"><code>{{ $e->institution_id }}</code> &middot; {{ $e->reference }}</div>
              <a href="{{ route('records.show', $e->id) }}" class="btn ter sm" style="margin-top:6px">{{ __('View record') }}</a>
            </td>
            <td>
              @if($e->coordinator_signed)
                <span class="tag ok">{{ __('Confirmed') }}</span>
                <div class="s muted" style="margin-top:4px">{{ $e->coordinator_name ?? __('Recorded') }}</div>
                <div class="s muted">{{ \Carbon\Carbon::parse($e->coordinator_signed_at)->format('d/m/Y H:i') }}</div>
                @if($e->coordinator_remarks)<div class="s muted">{{ $e->coordinator_remarks }}</div>@endif
              @elseif($e->check['met'] && $e->status === 'enrolled')
                <span class="tag warn">{{ __('Awaiting your sign-off') }}</span>
              @else
                <span class="tag">{{ __('Not yet eligible') }}</span>
              @endif
            </td>
            <td>
              @if($e->confirmed_at)
                <span class="tag ok">{{ __('Confirmed') }}</span>
                <div class="s muted" style="margin-top:4px">{{ $e->confirmed_by_name ?? __('Recorded') }}</div>
                <div class="s muted">{{ \Carbon\Carbon::parse($e->confirmed_at)->format('d/m/Y') }}</div>
              @elseif($e->coordinator_signed)
                <span class="tag info">{{ __('Queued for Deputy Dean') }}</span>
              @else
                <span class="not-recorded small">{{ __('Not yet recorded') }}</span>
              @endif
            </td>
            <td>
              @if($e->check['met'])
                <span class="tag ok">{{ __('All requirements met') }}</span>
              @else
                <span class="tag err">{{ __('Requirement missing') }}</span>
              @endif
              <details @if(!$e->check['met']) open @endif style="margin-top:6px">
                <summary class="muted small" style="cursor:pointer">{{ count($e->check['items']) }} {{ __('checks') }}</summary>
                <ul style="margin:6px 0 0;padding-left:18px;font-size:12.5px">
                  @foreach($e->check['items'] as $i)
                    <li style="margin-bottom:3px">
                      <span class="tag {{ $i['ok'] ? 'ok' : 'err' }}" style="font-size:10px">{{ $i['ok'] ? __('Met') : __('Missing') }}</span>
                      {{ $i['text'] }}
                    </li>
                  @endforeach
                </ul>
              </details>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No enrolled participants in this cohort.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="bd" style="border-top:1px solid var(--hairline)">
      <div style="margin-bottom:14px;max-width:640px">
        <label for="remarks">{{ __('Verification remarks (optional)') }}</label>
        <input type="text" id="remarks" name="remarks" value="{{ old('remarks') }}" placeholder="{{ __('e.g. All coursework and final assessment marks verified.') }}">
      </div>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <button type="submit" class="btn" @disabled($waitingSignOff === 0)>
          {{ __('Sign off selected participants') }}
        </button>
        @if($waitingSignOff === 0)
          <span class="muted small">{{ __('No eligible participant is waiting for coordinator sign-off.') }}</span>
        @else
          <span class="muted small">{{ __('Signing confirms academic completion and unlocks Deputy Dean confirmation.') }}</span>
        @endif
      </div>
    </div>
  </div>
</form>
@endsection
