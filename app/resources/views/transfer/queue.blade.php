@extends('layouts.app')
@section('title', __('Credit transfer evaluation'))
@section('content')
@php
  $pendingCount = $rows->whereIn('status', ['submitted', 'under_eval'])->count();
  $isDd = false;
@endphp

<div class="title">
  <div>
    @if($isDd)
      <h1>{{ __('Credit Transfer Monitor') }}</h1>
      <div class="sub">{{ __('Faculty Programme Overview · Read-only monitor of credit transfers evaluated by CODL and approved by Senate for your faculty programmes.') }}</div>
    @else
      <h1>{{ __('Credit Transfer Evaluation & Decisions') }}</h1>
      <div class="sub">{{ __('CODL & Senate Academic Authority Workspace · Evaluate learning outcome equivalence, check ACR validity, and record Senate transfer decisions.') }}</div>
    @endif
  </div>
</div>

<div data-tabs data-auto>
  <nav class="tabs" aria-label="Transfer stages" style="margin-bottom:20px">
    <button data-tab="applications">{{ __('Applications') }} <span class="muted">{{ count($rows) }}</span></button>
    <button data-tab="held">{{ __('Credit held (Redemption)') }} <span class="muted">{{ count($inUse) }}</span></button>
    <button data-tab="admissions">{{ __('Admissions recorded') }} <span class="muted">{{ count($admissions) }}</span></button>
  </nav>

  <div data-pane="applications" @if(!count($rows)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Applications queue') }}</h2>
        <span class="r"><span class="tag info">{{ count($rows) }} {{ __('cases') }}</span></span>
      </div>
      <div class="table-wrap">
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
                <a href="{{ route('transfers.show', $r->id) }}" style="font-weight:600"><code>{{ $r->reference }}</code></a>
              </td>
              <td>{{ $r->user_name }}<div class="s muted"><code>{{ $r->institution_id }}</code></div></td>
              <td><code>{{ $r->programme }}</code></td>
              <td><x-pill :s="__($status[$r->status] ?? $r->status)" /></td>
              <td style="text-align:right">
                <a class="btn ter sm" href="{{ route('transfers.show', $r->id) }}">{{ __('Open') }}</a>
                @if(in_array($r->status, ['approved', 'partly', 'not_approved'], true))
                  <a class="btn ter sm" href="{{ route('transfers.letter', $r->id) }}?print=1" target="_blank">{{ __('Letter (A4)') }}</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No application.') }}</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div data-pane="held" @if(!count($inUse)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Single redemption ledger (Provisionally redeemed credits)') }}</h2>
        <span class="r"><span class="tag warn">{{ count($inUse) }} {{ __('held') }}</span></span>
      </div>
      <div class="table-wrap">
        <table id="in-use">
          <thead>
            <tr>
              <th>{{ __('Application') }}</th>
              <th>{{ __('Participant') }}</th>
              <th style="text-align:right">{{ __('Credits') }}</th>
              <th>{{ __('Programme completion / non-completion & ABC update') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($inUse as $x)
            <tr>
              <td>
                <b><code>{{ $x->reference }}</code></b>
                <div class="muted small">{{ $x->programme ?? '' }}</div>
              </td>
              <td>{{ $x->name }}</td>
              <td style="text-align:right;font-weight:600">{{ mcms_num($x->credits_approved ?: ($x->credits_requested ?? 0)) }} cr</td>
              <td>
                <form method="post" action="{{ route('transfers.redeem', $x->id) }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end">
                  @csrf
                  <div style="display:inline-flex;flex-direction:column;gap:2px">
                    <label for="ev-{{ $x->id }}" class="small muted">{{ __('Evidence reference') }} *</label>
                    <input id="ev-{{ $x->id }}" name="evidence_ref" type="text" placeholder="{{ __('Grad / withdrawal ref') }}" required style="width:150px;min-height:32px;padding:4px 8px;font-size:12px">
                  </div>
                  <div style="display:inline-flex;flex-direction:column;gap:2px">
                    <label for="what-{{ $x->id }}" class="small muted">{{ __('Outcome') }} *</label>
                    <select id="what-{{ $x->id }}" name="what" aria-label="Outcome for {{ $x->reference }}" style="min-height:32px;padding:4px 8px;font-size:12px">
                      <option value="completed">{{ __('Programme completed (Redeem)') }}</option>
                      <option value="not_completed">{{ __('Withdrawn or not completed (Release)') }}</option>
                    </select>
                  </div>
                  <div style="display:inline-flex;flex-direction:column;gap:2px">
                    <label for="abcon-{{ $x->id }}" class="small muted">{{ __('ABC updated on') }}</label>
                    <input id="abcon-{{ $x->id }}" name="abc_updated_on" type="date" aria-label="ABC updated date for {{ $x->reference }}" style="width:136px;min-height:32px;padding:4px 8px;font-size:12px">
                  </div>
                  <div style="display:inline-flex;flex-direction:column;gap:2px">
                    <label for="abcref-{{ $x->id }}" class="small muted">{{ __('ABC update ref') }}</label>
                    <input id="abcref-{{ $x->id }}" name="abc_update_ref" type="text" placeholder="{{ __('ABC update ref') }}" aria-label="ABC update reference for {{ $x->reference }}" style="width:135px;min-height:32px;padding:4px 8px;font-size:12px">
                  </div>
                  <div>
                    <button type="submit" class="btn ter sm">{{ __('Record') }}</button>
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
  </div>

  <div data-pane="admissions" @if(!count($admissions)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Programme admissions recorded') }}</h2>
        <span class="r"><span class="tag ok">{{ count($admissions) }} {{ __('admissions') }}</span></span>
      </div>
      <div class="bd" style="padding:0">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
          <p class="muted small" style="margin:0">{{ __('Admission to a degree programme is recorded separately by Pejabat Pendaftar (Academic Registrar). Transfer approval transfers credit only and does not imply university admission.') }}</p>
        </div>
        <div class="table-wrap">
          <table id="admissions" style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Participant') }}</th>
                <th>{{ __('Admitted Programme') }}</th>
                <th>{{ __('Intake') }}</th>
                <th>{{ __('Registrar Evidence Reference') }}</th>
                <th style="text-align:right">{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($admissions as $ad)
              <tr>
                <td><b>{{ $ad->name }}</b></td>
                <td><span class="tag info">{{ $ad->code }}</span></td>
                <td>{{ $ad->intake }}</td>
                <td><code>{{ $ad->evidence_ref }}</code></td>
                <td style="text-align:right"><span class="tag ok">{{ __('Admitted') }}</span></td>
              </tr>
            @empty
              <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No admissions recorded yet.') }}</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
