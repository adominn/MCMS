@extends('layouts.app')
@section('title', __('Confirm completion'))
@section('content')
<div class="crumb">
  <a href="{{ route('results') }}">{{ __('Results and completion') }}</a>
  <span>/</span>
  <a href="{{ route('results.verify', $o->id) }}">{{ $o->name_en }} {{ $o->cohort_name }}</a>
  <span>/</span>
  <span>{{ __('Confirm completion') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('Confirm completion: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">
      {{ __('Pre-issue completion checks and batch certificate issuance') }}
    </div>
  </div>
  <div class="act" style="gap:8px">
    <a class="btn ter sm" href="{{ route('credentials.admin') }}">{{ __('Credentials') }}</a>
    <a class="btn ter sm" href="{{ route('results.verify', $o->id) }}">{{ __('Results') }}</a>
    <a class="btn ter sm" href="{{ route('results') }}">{{ __('All cohorts') }}</a>
  </div>
</div>

<div class="note info" style="margin-bottom:20px">
  <p style="margin:0">
    {{ __('Verifying results gave module badges only. Completing the micro-credential is a separate decision. A certificate or Digital Credential is issued only after you confirm, and only to a participant with no requirement missing.') }}
  </p>
</div>

<form method="post" action="{{ route('completion.confirm', $o->id) }}">
  @csrf
  <div class="panel">
    <div class="hd"><h2>{{ __('Participants and pre-issue checks') }}</h2></div>
    <div class="bd" style="padding:0">
      <table>
        <thead>
          <tr>
            <th style="width:5%"></th>
            <th style="width:30%">{{ __('Participant') }}</th>
            <th style="width:15%">{{ __('Record') }}</th>
            <th style="width:50%">{{ __('Pre-issue checks') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $e)
          <tr>
            <td style="text-align:center">
              @if($e->status === 'enrolled' && $e->check['met'])
                <input type="checkbox" name="enrolments[]" value="{{ $e->id }}" checked aria-label="Confirm {{ $e->name }}">
              @endif
            </td>
            <td>
              <b>{{ $e->name }}</b>
              <div class="s muted" style="margin-bottom:4px">{{ $e->institution_id }}</div>
              <a href="{{ route('records.show', $e->id) }}" class="btn ter sm">{{ __('View record') }}</a>
            </td>
            <td>
              <x-pill :s="__(['not_started' => 'Not started', 'in_progress' => 'In progress', 'complete' => 'Complete'][$e->state] ?? ucfirst($e->state))" />
            </td>
            <td>
              @if($e->check['met'])
                <span class="tag ok">{{ __('All requirements met') }}</span>
              @else
                <span class="tag overdue">{{ __('Requirement missing, not issued') }}</span>
              @endif

              <details @if(!$e->check['met']) open @endif style="margin-top:8px">
                <summary class="muted small" style="cursor:pointer">{{ count($e->check['items']) }} {{ __('checks') }}</summary>
                <ul style="margin:6px 0 0;padding-left:18px;font-size:13px">
                  @foreach($e->check['items'] as $i)
                    <li style="margin-bottom:4px">
                      <span class="tag {{ $i['ok'] ? 'ok' : 'err' }} sm" style="font-size:10px">{{ $i['ok'] ? __('Met') : __('Missing') }}</span>
                      {{ $i['text'] }}
                    </li>
                  @endforeach
                </ul>
              </details>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No enrolled participants.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="bd" style="border-top:1px solid var(--border)">
      <div class="row" style="gap:12px;align-items:center">
        <button type="submit" class="btn" @disabled($waiting === 0)>
          {{ __('Confirm completion') }}
        </button>
        @if($waiting === 0)
          <span class="muted s">{{ __('Nobody is waiting for confirmation.') }}</span>
        @endif
      </div>
    </div>
  </div>
</form>
@endsection
