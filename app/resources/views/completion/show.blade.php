@extends('layouts.app')
@section('title', __('Confirm completion'))
@section('content')
@php
  $canConfirmCompletion = auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority');
  $canOpenCredentials = auth()->user()->hasAnyRole('records_custodian', 'records_depositor');
@endphp
@if($canConfirmCompletion)
  <x-breadcrumb :crumbs="[
    'Results and completion' => route('results'),
    $o->name_en . ' ' . $o->cohort_name => route('results.verify', $o->id),
    'Confirm completion' => null,
  ]" />
@else
  <x-breadcrumb :crumbs="[
    'Credentials' => route('credentials.admin'),
    $o->name_en . ' ' . $o->cohort_name => null,
  ]" />
@endif

<div class="title">
  <div>
    <h1>{{ $canConfirmCompletion ? __('Confirm completion: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) : __('Completion status: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">
      @if(!empty($o->lecturer_name)){{ $o->lecturer_name }} &middot; @endif
      {{ __('Pre-issue completion checks, Course Coordinator sign-off gate, and completion confirmation status') }}
    </div>
  </div>
  <div class="act" style="gap:8px">
    @if($canOpenCredentials)
      <a class="btn ter sm" href="{{ route('credentials.admin') }}">{{ __('Credentials') }}</a>
    @endif
    @if($canConfirmCompletion)
      <a class="btn ter sm" href="{{ route('results.verify', $o->id) }}">{{ __('Results') }}</a>
      <a class="btn ter sm" href="{{ route('results') }}">{{ __('All cohorts') }}</a>
    @else
      <a class="btn ter sm" href="{{ route('trace') }}">{{ __('Traceability') }}</a>
    @endif
  </div>
</div>

<x-credit-lifecycle :stage="3" :note="__('Deputy Dean academic completion confirmation')" />

@if($canConfirmCompletion)
<form method="post" action="{{ route('completion.confirm', $o->id) }}">
  @csrf
@endif
  <div class="panel">
    <div class="hd">
      <h2>{{ __('Participants and pre-issue checks') }}</h2>
      <span class="r"><span class="tag {{ $waiting > 0 ? 'warn' : 'info' }}">{{ $waiting }} {{ __('ready to confirm') }}</span></span>
    </div>
    <div class="bd" style="padding:0">
      <table>
        <thead>
          <tr>
            @if($canConfirmCompletion)<th style="width:5%"></th>@endif
            <th style="width:24%">{{ __('Participant') }}</th>
            <th style="width:13%">{{ __('Record') }}</th>
            <th style="width:20%">{{ __('Coordinator Sign-off') }}</th>
            <th style="width:38%">{{ __('Pre-issue checks') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $e)
          <tr>
            @if($canConfirmCompletion)
              <td style="text-align:center">
                @if($e->status === 'enrolled' && $e->check['met'] && $e->coordinator_signed)
                  <input type="checkbox" name="enrolments[]" value="{{ $e->id }}" checked aria-label="Confirm {{ $e->name }}">
                @endif
              </td>
            @endif
            <td>
              <b>{{ $e->name }}</b>
              <div class="s muted" style="margin-bottom:4px">{{ $e->institution_id }}</div>
              <a href="{{ route('records.show', $e->id) }}" class="btn ter sm">{{ __('View record') }}</a>
            </td>
            <td>
              <x-pill :s="__(['not_started' => 'Not started', 'in_progress' => 'In progress', 'complete' => 'Complete'][$e->state] ?? ucfirst($e->state))" />
            </td>
            <td>
              @if($e->coordinator_signed)
                <span class="tag ok">{{ __('Signed by Coordinator') }}</span>
                <div class="s muted" style="margin-top:4px">{{ $e->coordinator_name ?? __('Recorded') }}</div>
                <div class="s muted">{{ \Carbon\Carbon::parse($e->coordinator_signed_at)->format('d/m/Y') }}</div>
              @elseif($e->status === 'enrolled' && $e->check['met'])
                <span class="tag warn">{{ __('Waiting for Coordinator sign-off') }}</span>
                <div class="s muted" style="margin-top:4px">{{ __('Course Coordinator must sign off first') }}</div>
              @else
                <span class="not-recorded small">{{ __('Not yet recorded') }}</span>
              @endif
            </td>
            <td>
              @if($e->check['met'])
                <span class="tag ok">{{ __('All requirements met') }}</span>
              @else
                <span class="tag overdue">{{ __('Requirement missing, not issued') }}</span>
                <div class="s muted" style="margin-top:2px;font-size:11px">({{ __('badges only') }})</div>
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
          <tr><td colspan="{{ $canConfirmCompletion ? 5 : 4 }}" class="muted" style="text-align:center;padding:24px">{{ __('No enrolled participants.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="bd" style="border-top:1px solid var(--border)">
      @if($canConfirmCompletion)
        <div class="row" style="gap:12px;align-items:center">
          <button type="submit" class="btn" @disabled($waiting === 0)>
            {{ __('Confirm completion') }}
          </button>
          @if($waiting === 0)
            <span class="muted s">
              @if(($awaitingCoordinator ?? 0) > 0)
                {{ __('Waiting for Course Coordinator sign-off before Deputy Dean confirmation can proceed.') }}
              @else
                {{ __('Nobody is waiting for confirmation.') }}
              @endif
            </span>
          @endif
        </div>
      @else
        <span class="muted s">{{ __('Read-only view. Academic completion confirmation is performed by the Deputy Dean (Faculty Authority).') }}</span>
      @endif
    </div>
  </div>
@if($canConfirmCompletion)
</form>
@endif
@endsection
