@extends('layouts.app')
@section('title', __('Credit ledger and ABC'))
@section('content')
<div class="title"><div><h1>{{ __('Credit ledger and ABC') }}</h1><div class="sub">{{ __('One row for each Academic Credit Record (ACR)') }}</div></div></div>
<div class="note" style="margin-bottom:16px">{{ __('Depositing in the Academic Bank of Credit (ABC) is storage only, not a credit transfer. Accumulated credits are not automatically accepted by any programme; each transfer is decided case by case by the receiving faculty. Micro-Credential Eksekutif (MCE) courses carry no credit and are not listed here.') }}</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Credits earned') }}</div>
    <div class="v">{{ rtrim(rtrim((string) $earned, '0'), '.') ?: '0' }}</div>
    <div class="s">{{ __('All ACRs, deposited or not') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Available') }}</div>
    <div class="v">{{ rtrim(rtrim((string) $total, '0'), '.') ?: '0' }}</div>
    <div class="s">{{ __('Deposited, can be used') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Provisionally redeemed') }}</div>
    <div class="v">{{ rtrim(rtrim((string) $provisional, '0'), '.') ?: '0' }}</div>
    <div class="s">{{ __('In an approved transfer') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Redeemed') }}</div>
    <div class="v">{{ rtrim(rtrim((string) $redeemed, '0'), '.') ?: '0' }}</div>
    <div class="s">{{ __('Used for a qualification') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Expired') }}</div>
    <div class="v">{{ rtrim(rtrim((string) $expired, '0'), '.') ?: '0' }}</div>
    <div class="s">{{ __('Past :years years in ABC', ['years' => $years ?? 10]) }}</div>
  </div>
</div>

<div class="panel"><table id="ledger">
  <thead><tr><th>{{ __('Credential') }}</th><th>{{ __('Provider') }}</th><th>{{ __('Credits') }}</th><th>{{ __('Deposit date') }}</th><th>{{ __('Expiry date') }}</th><th>{{ __('Status') }}</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    @php($p = json_decode($r->payload, true))
    <tr>
      <td><a href="{{ route('credit.acr', $r->id) }}">{{ $r->name_en }}</a><br><span class="muted">{{ $r->reference }}</span></td>
      <td>{{ $p['provider'] }}</td>
      <td>{{ rtrim(rtrim((string) $r->credits, '0'), '.') }}</td>
      <td>{{ $r->deposit_date ? \Carbon\Carbon::parse($r->deposit_date)->format('d/m/Y') : '' }}</td>
      <td>{{ $r->credit_expiry ? \Carbon\Carbon::parse($r->credit_expiry)->format('d/m/Y') : '' }}</td>
      <td><x-pill :s="$r->credit_status ? $creditStatus[$r->credit_status] : $acrStatus[$r->status]" />@if(in_array($r->status, ['verified', 'consent_declined'])) <a class="btn sm" style="margin-left:8px" href="{{ route('credit.acr', $r->id) }}">{{ __('Give consent') }}</a>@endif</td>
    </tr>
  @empty
    <tr><td colspan="6" class="muted">{{ __('You have no Academic Credit Record yet. One is prepared when you complete a UMC, or a SAMC with valid Quality Verification.') }}</td></tr>
  @endforelse
  </tbody>
  <tfoot><tr><th colspan="2">{{ __('Total Available credit') }}</th><th id="total">{{ rtrim(rtrim((string) $total, '0'), '.') ?: '0' }}</th><td colspan="3" class="muted">{{ __('Credit is valid for :years years from the deposit date.', ['years' => $years]) }}</td></tr></tfoot>
</table></div>
@endsection
