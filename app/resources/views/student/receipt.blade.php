@extends('layouts.print')
@section('title', 'Payment receipt')
@section('doc_title', 'Official payment receipt')
@section('doc_bm', 'Resit rasmi bayaran')
@section('doc_ref', $p ? 'R-'.\Carbon\Carbon::parse($p->created_at)->year.'-'.str_pad((string) $p->id, 4, '0', STR_PAD_LEFT) : $e->reference)
@section('doc_file', 'receipt-'.$e->reference.'.pdf')
@section('content')

@php
  $rno = $p ? 'R-'.\Carbon\Carbon::parse($p->created_at)->year.'-'.str_pad((string) $p->id, 4, '0', STR_PAD_LEFT) : null;
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">{{ $p ? __('Payment receipt') : __('Receipt not available') }}</h1>
  <div class="small muted">
    @if($p)
      <b>Receipt {{ $rno }}</b> &middot; {{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }} &middot; Status: <b>{{ __("Paid in full") }}</b>
    @else
      Registration {{ $e->reference }} &middot; {{ __('Generated on') }} {{ now()->format('d/m/Y') }}
    @endif
  </div>
</div>

@if($p)
  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <table style="margin:0">
        <tbody>
          <tr><th style="width:180px">{{ __('Receipt number') }}</th><td><b>{{ $rno }}</b></td></tr>
          <tr><th>{{ __('Date of payment') }}</th><td>{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td></tr>
          <tr><th>{{ __('Received from') }}</th><td><b>{{ $u->name }}</b></td></tr>
          <tr><th>{{ __('Learner ID') }}</th><td>{{ $u->institution_id ?? $u->email }}</td></tr>
          <tr><th>{{ __('Registration reference') }}</th><td><code>{{ $e->reference }}</code></td></tr>
          <tr><th>{{ __('Micro-credential') }}</th><td>{{ $o->name_en }}</td></tr>
          <tr><th>{{ __('Cohort') }}</th><td>{{ $o->cohort_name }} ({{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }})</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __('Fee breakdown') }}</h2></div>
    <div class="bd" style="padding:0">
      <table style="margin:0">
        <thead>
          <tr>
            <th>{{ __('Item') }}</th>
            <th class="num" style="width:140px">{{ __('Amount') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Offering fee: {{ $o->name_en }}, {{ $o->cohort_name }}</td>
            <td class="num">RM {{ number_format($p->amount, 2) }}</td>
          </tr>
          <tr>
            <td>Service charge</td>
            <td class="num">RM 0.00</td>
          </tr>
          <tr>
            <td>Tax</td>
            <td class="num">RM 0.00 (not applicable)</td>
          </tr>
        </tbody>
        <tfoot>
          <tr style="font-weight:600;background:var(--surface)">
            <td>{{ __('Total paid') }}</td>
            <td class="num"><b>RM {{ number_format($p->amount, 2) }}</b></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <table style="margin:0">
        <tbody>
          <tr><th style="width:180px">{{ __('Payment method') }}</th><td>Dummy card ending {{ $p->card_last4 }}</td></tr>
          <tr><th>{{ __('Payment reference') }}</th><td><code>{{ $p->provider_ref }}</code></td></tr>
          <tr><th>{{ __('Status') }}</th><td><span class="tag ok">{{ __('Paid in full') }}</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="small muted" style="margin-bottom:16px;line-height:1.5">
    {{ __('This receipt confirms a payment for registration') }} {{ $e->reference }}.
    {{ __('Refund rules are decided case by case by CODL, outside MCMS.') }}
  </div>

  <div class="note warn" style="font-size:9.5pt">
    <div><b>{{ __("Prototype notice:") }}</b> Dummy payment service. No money was taken. This receipt is sample data and has no accounting value.</div>
  </div>
@else
  <div class="note err" style="margin-bottom:20px">
    <div>
      <b>{{ __('No payment received.') }}</b>
      Registration {{ $e->reference }} is {{ $e->status }}.
      {{ __('The payment service has not confirmed any payment, so no receipt is issued.') }}
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <table style="margin:0">
        <tbody>
          <tr><th style="width:180px">{{ __('Registration') }}</th><td><code>{{ $e->reference }}</code></td></tr>
          <tr><th>{{ __('Payer') }}</th><td>{{ $u->name }}</td></tr>
          <tr><th>{{ __('Micro-credential') }}</th><td>{{ $o->name_en }}</td></tr>
          <tr><th>{{ __('Cohort') }}</th><td>{{ $o->cohort_name }} ({{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }})</td></tr>
          <tr><th>{{ __('Amount due') }}</th><td>RM {{ number_format($o->fee ?? 0, 2) }}</td></tr>
          <tr><th>{{ __('Amount received') }}</th><td>RM 0.00</td></tr>
          <tr><th>{{ __('Status') }}</th><td><span class="tag info">{{ ucfirst($e->status) }}</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div style="font-size:10pt;margin-bottom:16px;line-height:1.5">
    {{ __('Your place is held until the registration window ends. Pay from Registrations and payments in MCMS; a receipt is issued as soon as the payment is received.') }}
  </div>

  <div class="note warn" style="font-size:9.5pt">
    <div><b>{{ __("Prototype notice:") }}</b> Dummy payment service. Unpaid registrations do not generate an official receipt.</div>
  </div>
@endif

@endsection
