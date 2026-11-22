@extends('layouts.app')
@section('title', __('Cohort ACR & ABC Progress'))
@section('content')
<x-breadcrumb :crumbs="[
  'Cohorts and results' => route('offerings'),
  $o->name_en . ' ' . $o->cohort_name => route('offerings.show', $o->id),
  'Cohort ACR progress' => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('Cohort Academic Credit Record (ACR) Progress') }}: {{ $o->name_en }} {{ $o->cohort_name }}</h1>
    <div class="sub">{{ __('Read-only lifecycle view for Course Coordinator / Lecturer · Registry / ABC Depositor handles ACR confirmation and ABC deposits.') }}</div>
  </div>
  <div class="act" style="gap:8px">
    <a class="btn ter sm" href="{{ route('completion.signoff.show', $o->id) }}">{{ __('Coordinator sign-off') }}</a>
    <a class="btn ter sm" href="{{ route('results.cohort', $o->id) }}">{{ __('Results') }}</a>
    <a class="btn ter sm" href="{{ route('offerings.show', $o->id) }}">{{ __('Cohort overview') }}</a>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Participant credit lifecycle status') }}</h2>
    <span class="r"><span class="tag info">{{ $rows->count() }} {{ __('participants') }}</span></span>
  </div>
  <div class="table-wrap">
    <table id="cohort-acr-progress">
      <thead>
        <tr>
          <th>{{ __('Participant') }}</th>
          <th>{{ __('02 P1 Sign-off') }}</th>
          <th>{{ __('03 P2 Completion') }}</th>
          <th>{{ __('04–05 ACR Status') }}</th>
          <th>{{ __('06 ABC Deposit') }}</th>
          <th>{{ __('Credit Status') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        <tr>
          <td>
            <b>{{ $r->name }}</b>
            <div class="s muted"><code>{{ $r->institution_id }}</code></div>
          </td>
          <td>
            @if($r->coordinator_signed_at)
              <span class="tag ok">{{ __('Signed') }}</span>
              <div class="s muted">{{ \Carbon\Carbon::parse($r->coordinator_signed_at)->format('d/m/Y') }}</div>
            @else
              <span class="not-recorded small">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
          <td>
            @if($r->confirmed_at)
              <span class="tag ok">{{ __('Confirmed') }}</span>
              <div class="s muted">{{ \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y') }}</div>
            @else
              <span class="not-recorded small">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
          <td>
            @if($r->acr_reference)
              <code>{{ $r->acr_reference }}</code>
              <div class="s muted">{{ \App\Modules\CreditBank\CreditBankController::ACR_STATUS[$r->acr_status] ?? ucfirst((string) $r->acr_status) }}</div>
            @else
              <span class="not-recorded small">{{ strtoupper($o->category) === 'MCE' ? __('Not applicable (MCE)') : __('Not yet prepared') }}</span>
            @endif
          </td>
          <td>
            @if($r->abc_reference)
              <code>{{ $r->abc_reference }}</code>
              <div class="s muted">{{ $r->deposit_date ? \Carbon\Carbon::parse($r->deposit_date)->format('d/m/Y') : '' }}</div>
            @else
              <span class="not-recorded small">{{ __('Not yet deposited') }}</span>
            @endif
          </td>
          <td>
            @if($r->credit_status)
              <x-pill :s="\App\Modules\CreditBank\CreditBankController::CREDIT_STATUS[$r->credit_status] ?? ucfirst($r->credit_status)" />
              @if($r->credit_expiry)<div class="s muted">{{ __('Valid until') }} {{ \Carbon\Carbon::parse($r->credit_expiry)->format('d/m/Y') }}</div>@endif
            @else
              <span class="not-recorded small">{{ __('Not yet in ABC') }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted" style="text-align:center;padding:24px">{{ __('No participants in this cohort.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
