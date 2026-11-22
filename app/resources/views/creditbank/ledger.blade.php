@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Credit ledger and ABC'))
@section('doc_title', 'PENYATA PENGUMPULAN KREDIT AKADEMIK (ABC)')
@section('doc_bm', 'Lampiran F5 — Garis Panduan Micro-Credential UTeM')
@section('doc_ref', 'ABC-LEDGER-' . (auth()->user()->institution_id ?? auth()->id()))
@section('doc_file', 'penyata-kredit-abc.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
  $fmtNum = fn ($v) => mcms_num($v);
  $needsConsent = $rows->whereIn('status', ['verified', 'consent_declined'])->count();
  $awaitingConfirm = $rows->where('status', 'prepared')->count();
  $inDepositQueue = $rows->whereIn('status', ['consent_given', 'correction'])->count();
@endphp

@if($isPrint)
  <div style="margin-bottom:16px;border-bottom:2px solid #161616;padding-bottom:10px">
    <div style="font-size:11pt;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Universiti Teknikal Malaysia Melaka (UTeM)</div>
    <h1 style="font-size:14pt;font-weight:700;text-transform:uppercase;margin:4px 0 2px">
      Lampiran F5: Penyata Pengumpulan Kredit Akademik (ABC)
    </h1>
    <div style="font-size:9.5pt;color:#525252">
      Nama Pelajar: <b>{{ auth()->user()->name }}</b> &middot; No. Matrik: <b>{{ auth()->user()->institution_id ?? '-' }}</b> &middot;
      {{ __('Dijana pada') }} {{ now()->format('d/m/Y') }}
    </div>
  </div>
@else
<div class="title">
  <div>
    <h1>{{ __('Credit Ledger & Academic Bank of Credit (ABC)') }}</h1>
    <div class="sub">{{ __('Credit Accumulation Record · One row per Academic Credit Record (ACR)') }}</div>
  </div>
  <div class="act" style="display:flex;gap:8px">
    <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn sec sm" target="_blank">{{ __('Print or save as PDF') }}</a>
    @if($total > 0)
      <a class="btn primary sm" href="{{ route('transfer.create') }}">{{ __('Apply for credit transfer') }}</a>
    @endif
  </div>
</div>
@endif

<div class="note warn" style="margin-bottom:20px;font-size:10pt;line-height:1.5">
  <b>{{ __('Academic Bank of Credit Notice (Appendix F5):') }}</b>
  {{ __('Credits stored in the Academic Bank of Credit (ABC) are for storage only and are not automatically accepted by any programme for credit transfer or graduation. Credit transfer is subject to faculty evaluation and university approval.') }}
  <div style="margin-top:4px;font-size:9pt;color:var(--muted)">
    {{ __('Kredit yang disimpan di dalam Bank Kredit Akademik (ABC) adalah bagi tujuan penyimpanan rekod sahaja (storage only) dan tidak diterima secara automatik oleh mana-mana program pengajian UTeM.') }}
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Total accumulated credit') }}</div>
    <div class="v">{{ $fmtNum($earned) }}</div>
    <div class="s">{{ __('All ACRs, deposited or not') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Available') }}</div>
    <div class="v">{{ $fmtNum($total) }}</div>
    <div class="s">{{ __('Deposited in ABC, ready to transfer') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Provisionally redeemed') }}</div>
    <div class="v">{{ $fmtNum($provisional) }}</div>
    <div class="s">{{ __('Held by an approved transfer') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Redeemed') }}</div>
    <div class="v">{{ $fmtNum($redeemed) }}</div>
    <div class="s">{{ __('Permanently used for a qualification') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Expired') }}</div>
    <div class="v">{{ $fmtNum($expired) }}</div>
    <div class="s">{{ __('Past :years years in ABC', ['years' => $years ?? 10]) }}</div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Credit Accumulation Record') }}</h2>
    <span class="r"><span class="tag info">{{ $rows->count() }} {{ __('record(s)') }}</span></span>
  </div>
  <div class="table-wrap">
    <table id="ledger">
      <thead>
        <tr>
          <th style="width:54px">{{ __('No.') }}</th>
          <th>{{ __('Micro-Credential & ACR') }}</th>
          <th>{{ __('Provider') }}</th>
          <th class="num">{{ __('Credits') }}</th>
          <th>{{ __('Deposit Date') }}</th>
          <th>{{ __('Expiry Date') }}</th>
          <th>{{ __('Credit Status') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $idx => $r)
        @php $p = json_decode((string) $r->payload, true) ?: []; @endphp
        <tr>
          <td class="tabular">{{ $idx + 1 }}</td>
          <td>
            <a href="{{ route('credit.acr', $r->id) }}" style="font-weight:600">{{ app()->getLocale() === 'ms' && !empty($p['name']['ms']) ? $p['name']['ms'] : $r->name_en }}</a>
            <div class="s muted"><code>{{ $r->reference }}</code> &middot; {{ strtoupper($r->category) }}@if($r->slt_hours) &middot; {{ $r->slt_hours }} {{ __('SLT hrs') }}@endif</div>
          </td>
          <td>{{ $p['provider'] ?? config('mcms.issuer') }}</td>
          <td class="num tabular"><b>{{ $fmtNum($r->credits) }}</b></td>
          <td class="tabular">{{ $r->deposit_date ? \Carbon\Carbon::parse($r->deposit_date)->format('d/m/Y') : __('Not yet recorded') }}</td>
          <td class="tabular">{{ $r->credit_expiry ? \Carbon\Carbon::parse($r->credit_expiry)->format('d/m/Y') : __('Not yet recorded') }}</td>
          <td>
            <x-pill :s="$r->credit_status ? __($creditStatus[$r->credit_status] ?? ucfirst($r->credit_status)) : __($acrStatus[$r->status] ?? ucfirst($r->status))" />
            @if(in_array($r->status, ['verified', 'consent_declined'], true))
              <div style="margin-top:6px"><a class="btn sm" href="{{ route('credit.acr', $r->id) }}">{{ __('Give consent') }}</a></div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">{{ __('You have no Academic Credit Record yet. One is prepared when you complete a UMC, or a SAMC with valid Quality Verification.') }}</td></tr>
      @endforelse
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">{{ __('Total accumulated credit') }}</th>
          <th class="num tabular">{{ $fmtNum($earned) }}</th>
          <td colspan="3" class="muted">
            {{ __('Total Available credit') }}: <b id="total">{{ $fmtNum($total) }}</b> &middot; {{ __('Credit is valid for :years years from the deposit date.', ['years' => $years ?? 10]) }}
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
@endsection
