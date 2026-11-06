@extends('layouts.app')
@section('title', __('Credit transfer evaluation'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Credit transfer evaluation') }}</h1>
    <div class="sub">{{ __('Cases for programmes owned by your faculty. Equivalence is decided by comparing learning outcomes course by course. A transfer approval never admits anyone.') }}</div>
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>
    <b>{{ __('Outcome comparison rule:') }}</b> {{ __('Credit or learning time alone is not evidence of equivalence. Every course learning outcome must be compared. Approval transfers credit only; it does not admit anyone.') }}
  </div>
</div>

<div data-tabs data-auto>
  <nav class="tabs" aria-label="Transfer stages" style="margin-bottom:20px">
    <button data-tab="applications">{{ __('Applications') }} <span class="muted">{{ count($rows) }}</span></button>
    <button data-tab="held">{{ __('Credit held') }} <span class="muted">{{ count($inUse) }}</span></button>
    <button data-tab="admissions">{{ __('Admissions recorded') }} <span class="muted">{{ count($admissions) }}</span></button>
  </nav>

  <div data-pane="applications" @if(!count($rows)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Applications queue') }}</h2>
        <span class="r"><span class="tag info">{{ count($rows) }} {{ __('cases') }}</span></span>
      </div>
      <table id="queue">
        <thead>
          <tr>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Participant') }}</th>
            <th>{{ __('Programme') }}</th>
            <th>{{ __('Status') }}</th>
            <th style="text-align:right">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          <tr>
            <td>
              <a href="{{ route('transfers.show', $r->id) }}" style="font-weight:600">{{ $r->reference }}</a>
            </td>
            <td>{{ $r->user_name }}</td>
            <td><code>{{ $r->programme }}</code></td>
            <td><x-pill :s="__($status[$r->status] ?? $r->status)" /></td>
            <td style="text-align:right">
              <a class="btn ter sm" href="{{ route('transfers.show', $r->id) }}">{{ __('Open') }}</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No application.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div data-pane="held" @if(!count($inUse)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Single redemption ledger (held credits)') }}</h2>
        <span class="r"><span class="tag warn">{{ count($inUse) }} {{ __('held') }}</span></span>
      </div>
      <table id="in-use">
        <thead>
          <tr>
            <th>{{ __('Application') }}</th>
            <th>{{ __('Participant') }}</th>
            <th style="text-align:right">{{ __('Credits') }}</th>
            <th>{{ __('Programme record') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($inUse as $x)
          <tr>
            <td>
              <b>{{ $x->reference }}</b>
              <div class="muted small">{{ $x->programme ?? '' }}</div>
            </td>
            <td>{{ $x->name }}</td>
            <td style="text-align:right;font-weight:600">{{ rtrim(rtrim((string) $x->credits_approved, '0'), '.') }}</td>
            <td>
              <form method="post" action="{{ route('transfers.redeem', $x->id) }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                @csrf
                <div style="display:inline-flex;flex-direction:column;gap:2px">
                  <label for="ev-{{ $x->id }}" class="small muted">Evidence reference</label>
                  <input id="ev-{{ $x->id }}" name="evidence_ref" type="text" placeholder="Grad / withdrawal ref" required style="width:160px;padding:4px 8px;font-size:12px">
                </div>
                <div style="display:inline-flex;flex-direction:column;gap:2px">
                  <label class="small muted">{{ __('Outcome') }}</label>
                  <select name="what" aria-label="Outcome for {{ $x->reference }}" style="padding:4px 8px;font-size:12px">
                    <option value="completed">Programme completed</option>
                    <option value="not_completed">Withdrawn or not completed</option>
                  </select>
                </div>
                <div style="display:inline-flex;flex-direction:column;gap:2px">
                  <label class="small muted">{{ __('ABC update (optional)') }}</label>
                  <input name="abc_update_ref" type="text" placeholder="ABC update reference" aria-label="ABC update reference for {{ $x->reference }}" style="width:150px;padding:4px 8px;font-size:12px">
                </div>
                <div style="align-self:flex-end">
                  <button type="submit" class="btn ter sm">{{ __("Record") }}</button>
                </div>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No credit is held.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div data-pane="admissions" @if(!count($admissions)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Programme admissions recorded') }}</h2>
        <span class="r"><span class="tag ok">{{ count($admissions) }} {{ __('admissions') }}</span></span>
      </div>
      <div class="bd">
        <p class="muted small" style="margin-top:0">{{ __('Admission to a degree programme is recorded separately by Pejabat Pendaftar. Transfer approval transfers credit only and does not imply admission.') }}</p>
        <ul id="admissions" style="margin:0;padding-left:18px">
        @forelse($admissions as $a)
          <li style="margin-bottom:6px"><b>{{ $a->name }}</b>, <code>{{ $a->code }}</code>, intake {{ $a->intake }}</li>
        @empty
          <li class="muted">None.</li>
        @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection