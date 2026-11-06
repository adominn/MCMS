@extends('layouts.app')
@section('title', __('My learning'))
@section('content')
<div class="title">
  <div><h1>{{ __('My learning') }}</h1><div class="sub">{{ auth()->user()->name }}@if(auth()->user()->institution_id) &middot; {{ auth()->user()->institution_id }}@endif</div></div>
  <div class="act"><a class="btn" href="{{ route('catalogue') }}">{{ __('Find a micro-credential') }}</a></div>
</div>

<div class="cols">
  <div>
    <section class="panel" id="actions" aria-labelledby="h-actions">
      <div class="hd"><h2 id="h-actions">{{ __('Actions for you') }}</h2><span class="r">{{ $actions->count() }} {{ __('open') }}</span></div>
      @forelse($actions as $t)
        <div class="item">
          <span class="dot {{ $t->dot_class ?? '' }}" aria-hidden="true"></span>
          <div class="grow">
            <a href="{{ $t->link ?? (isset($t->id) && is_numeric($t->id) ? route('tasks.show', $t->id) : '#') }}" style="font-weight:600">{{ $t->title }}</a>
            <span class="s" style="margin-top:2px;display:block">{{ $t->subtitle ?? (__('Waiting since ').\Carbon\Carbon::parse($t->created_at)->format('d/m/Y')) }}</span>
          </div>
          <a class="btn sm go" href="{{ $t->link ?? (isset($t->id) && is_numeric($t->id) ? route('tasks.show', $t->id) : '#') }}" aria-label="{{ $t->action_label ?? __('Open') }}">{{ $t->action_label ?? __('Open') }}</a>
        </div>
      @empty
        <div class="bd muted" style="padding:20px">{{ __('Nothing is waiting for you.') }}</div>
      @endforelse
    </section>

    <section class="panel" id="in-progress" aria-labelledby="h-progress">
      <div class="hd"><h2 id="h-progress">{{ __('In progress') }}</h2></div>
      @forelse($inProgress as $e)
        <div class="item"><div class="grow">
          <b>{{ $e->name_en }}</b> <span class="muted">{{ __('Cohort') }} {{ $e->cohort_name }}</span> <span class="s" style="display:inline;float:right">{{ __('Ends') }} {{ \Carbon\Carbon::parse($e->end_date)->format('d/m/Y') }}</span>
          <div class="bar" role="progressbar" aria-valuenow="{{ $e->percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress in {{ $e->name_en }}"><i style="width:{{ $e->percent }}%"></i></div>
          <span class="s">{{ $e->achieved }} of {{ $e->modules }} {{ __('modules achieved') }} &middot; {{ $e->percent }}% {{ __('done') }}</span>
          <a href="{{ route('enrolments.show', $e->id) }}">{{ __('Registration and payment') }}</a>
        </div></div>
      @empty
        <div class="bd muted" style="padding:20px">{{ __('You are not taking a micro-credential now.') }}</div>
      @endforelse
    </section>

    @if($waiting->count())
    <section class="panel" id="waiting" aria-labelledby="h-waiting">
      <div class="hd"><h2 id="h-waiting">{{ __('Waiting for verification') }}</h2></div>
      @foreach($waiting as $e)
        <div class="item"><span class="dot" aria-hidden="true"></span><div class="grow"><b>{{ $e->name_en }}</b> <span class="muted">{{ __('Cohort') }} {{ $e->cohort_name }}</span>
          <span class="s">{{ __('Your results are verified. The deputy dean now confirms completion. Your certificate is issued after that.') }}</span></div><x-pill s="Waiting for completion to be confirmed" /></div>
      @endforeach
    </section>
    @endif

    <section class="panel" id="achieved" aria-labelledby="h-achieved">
      <div class="hd"><h2 id="h-achieved">{{ __('Achieved') }}</h2><span class="r"><a class="btn ghost sm" href="{{ route('credentials.wallet') }}">{{ __('All credentials') }} &rarr;</a></span></div>
      <table>
        <thead><tr><th>{{ __('Micro-credential') }}</th><th>{{ __('Issued') }}</th><th>{{ __('Verify code') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse($achieved as $c)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:12px">
                <x-badge :category="$c->category" :size="36" />
                <div>
                  <div style="display:flex;align-items:center;gap:6px">
                    <x-cat :c="$c->category" />
                    <a href="{{ route('credentials.show', $c->id) }}" style="font-weight:600">{{ $c->name }}</a>
                  </div>
                  <span class="s" style="margin-top:2px;display:block">{{ $c->number }}</span>
                </div>
              </div>
            </td>
            <td>{{ \Carbon\Carbon::parse($c->issued_at)->format('d/m/Y') }}</td>
            <td><code class="vc">{{ $c->public_code }}</code></td>
            <td><x-pill :s="$c->status" :label="['valid' => 'Issued', 'corrected' => 'Corrected', 'replaced' => 'Replaced', 'revoked' => 'Revoked'][$c->status] ?? $c->status" /></td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted">{{ __('No credential yet. A certificate and Digital Credential are issued after the deputy dean confirms completion.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </section>
  </div>

  <aside>
    <section class="panel" id="upcoming" aria-labelledby="h-upcoming">
      <div class="hd"><h2 id="h-upcoming">{{ __('Upcoming registrations') }}</h2></div>
      @forelse($upcoming as $e)
        <div class="item"><div class="grow">{{ $e->name_en }}<span class="s">{{ __('Cohort') }} {{ $e->cohort_name }} {{ __('starts') }} {{ \Carbon\Carbon::parse($e->start_date)->format('d/m/Y') }}</span></div>
          <x-pill :s="$e->status === 'pending_payment' ? 'Pending payment' : ($e->status === 'registered' ? 'Registered' : 'Enrolled')" /></div>
      @empty
        <div class="bd muted" style="padding:20px">{{ __('None.') }}</div>
      @endforelse
    </section>

    <section class="panel" id="track" aria-labelledby="h-track">
      <div class="hd">
        <h2 id="h-track">{{ __('Your recognition track') }}</h2>
        <div class="small muted" style="font-size:12px;margin-top:2px">{{ __('Cumulative academic journey across all courses') }}</div>
      </div>
      <div class="bd">
        <div class="track">
          <div class="stp {{ $track['modules'] ? 'done' : '' }}"><b>{{ __('Learn') }}</b>{{ $track['modules'] }} {{ __('modules') }}</div>
          <div class="stp {{ $track['credentials'] ? 'done' : '' }}"><b>{{ __('Credential') }}</b>{{ $track['credentials'] }} {{ __('issued') }}</div>
          <div class="stp {{ $track['consent'] ? 'now' : '' }}"><b>{{ __('Consent') }}</b>{{ $track['consent'] }} {{ __('open') }}</div>
          <div class="stp {{ $track['transfer'] ? 'now' : '' }}"><b>{{ __('Transfer') }}</b>{{ $track['transfer'] ? $track['transfer'].' '.__('open') : __('Decision due') }}</div>
        </div>
        <p class="s" style="margin:0 0 10px">{{ __('Each step is a separate decision.') }}</p>
        <a class="btn ter sm" href="{{ route('credit') }}">{{ __('Credit ledger') }} &rarr;</a>
      </div>
    </section>

    <div class="band" style="display:flex;align-items:center;justify-content:space-between"><span>{{ __('Looking for more offerings?') }}</span><a class="btn ter sm" href="{{ route('catalogue') }}">{{ __('Browse open offerings') }} &rarr;</a></div>
  </aside>
</div>
@endsection
