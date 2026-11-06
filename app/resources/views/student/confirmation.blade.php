@extends('layouts.print')
@section('title', 'Enrolment confirmation')
@section('doc_title', 'Enrolment confirmation')
@section('doc_bm', 'Pengesahan pendaftaran')
@section('doc_ref', $e->reference)
@section('doc_file', 'confirmation-'.$e->reference.'.pdf')
@section('content')

@php
  $paid = $p && $p->status === 'succeeded';
  $startDate = \Carbon\Carbon::parse($o->start_date)->format('d/m/Y');
  $endDate = \Carbon\Carbon::parse($o->end_date)->format('d/m/Y');
  $wdDate = $deadline ? \Carbon\Carbon::parse($deadline)->format('d/m/Y') : $endDate;
  $mode = ucfirst($o->delivery_mode ?? 'Online');
  $cat = strtoupper($o->category ?? 'UMC');
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">{{ __('Your registration is confirmed') }}</h1>
  <div class="small muted">
    {{ __('Registration') }} {{ $e->reference }} &middot; {{ now()->format('d/m/Y') }} &middot; Status: <b>{{ ucfirst($e->status) }}</b>
  </div>
</div>

@if(!$paid)
  <div class="note warn" style="margin-bottom:16px;font-size:10pt">
    Registration {{ $e->reference }} is {{ $e->status }}. This letter is a preview: it is final once payment is received.
  </div>
@endif

<div style="font-size:10.5pt;margin-bottom:12px">
  <div>Dear <b>{{ $u->name }}</b>,</div>
  <div style="margin-top:4px">Thank you for registering. This letter confirms your place and tells you how to start.</div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Registration') }}</dt>
      <dd><code>{{ $e->reference }}</code></dd>

      <dt style="font-size:9pt">{{ __('Learner ID') }}</dt>
      <dd>{{ $u->institution_id ?? ('ID-'.$u->id) }}</dd>

      <dt style="font-size:9pt">{{ __('Micro-credential') }}</dt>
      <dd><b>{{ $o->name_en }}</b> ({{ $cat }})</dd>

      <dt style="font-size:9pt">{{ __('Cohort') }}</dt>
      <dd>{{ $o->cohort_name }}</dd>

      <dt style="font-size:9pt">{{ __('Dates') }}</dt>
      <dd>{{ $startDate }} to {{ $endDate }}</dd>

      <dt style="font-size:9pt">{{ __('Mode') }}</dt>
      <dd>{{ $mode }}</dd>

      <dt style="font-size:9pt">{{ __('Fee paid') }}</dt>
      <dd>{{ $paid ? 'RM '.number_format($p->amount, 2).' (Paid in full)' : 'Not paid yet' }}</dd>

      <dt style="font-size:9pt">{{ __('Withdrawal deadline') }}</dt>
      <dd>{{ $wdDate }}. Withdrawn modules stop; modules already achieved stay on your record.</dd>
    </dl>
  </div>
</div>

<div style="margin-bottom:16px">
  <h2 style="font-size:12pt;margin:0 0 6px">{{ __('How to open your course on UCreds') }}</h2>
  <ol style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:4px;font-size:10pt">
    <li>The course space opens on UCreds before {{ $startDate }}. You get a message when it is ready.</li>
    <li>Sign in to UCreds with your UTeM account. There is no separate password.</li>
    <li>Open the course named <b>{{ $o->name_en }}, {{ $o->cohort_name }}</b>. Modules open in order.</li>
    <li>Return to MCMS to follow progress. Results and credentials appear there after the lecturer submits and the deputy dean verifies them.</li>
  </ol>
  <div class="bm" style="font-size:9pt;margin-top:4px;color:var(--muted)">
    Log masuk ke UCreds dengan akaun UTeM anda. Ruang kursus dibuka sebelum {{ $startDate }}; modul dibuka mengikut urutan.
  </div>
</div>

<div style="margin-bottom:16px">
  <h2 style="font-size:12pt;margin:0 0 6px">{{ __('What you receive') }}</h2>
  <div style="font-size:10pt">
    @if($cat === 'MCE')
      {{ $o->name_en }} is a Micro-Credential Eksekutif (MCE). It earns a certificate and module badges. It carries no academic credit, so no Academic Credit Record is made and it cannot be used for credit transfer.
    @elseif($cat === 'UMC')
      {{ $o->name_en }} is an Unbundled Micro-Credential (UMC). Completing all modules earns academic credit, a certificate, a Digital Credential, and an Academic Credit Record (ACR) you may choose to deposit in ABC.
    @else
      {{ $o->name_en }} is a Standalone Micro-Credential (SAMC). Completing all modules earns a certificate and badges. Credit and MQF level are recorded in an ACR only while Quality Verification is valid.
    @endif
  </div>
  <div class="bm" style="font-size:9pt;margin-top:4px;color:var(--muted)">
    @if($cat === 'MCE')
      Tawaran ini memberi sijil dan lencana modul sahaja, tanpa kredit akademik.
    @else
      Penamatan memberi sijil dan Kredensial Digital; kredit direkodkan dalam ACR hanya apabila layak mengikut pengesahan kualiti.
    @endif
  </div>
</div>

<div class="note" style="margin-bottom:16px;font-size:9.5pt">
  <div>
    <b>{{ __("Separate stages notice:") }}</b>
    Completion, credential, ACR, ABC deposit and credit transfer are separate decisions, each made by a different person at a different time. Registering does not promise any of them. Deposit in ABC is storage, not a transfer.
  </div>
  <div class="bm" style="font-size:8.5pt;margin-top:4px">
    Penamatan, kredensial, ACR, deposit ABC dan pindahan kredit ialah keputusan berasingan. Pendaftaran tidak menjanjikan mana-mana daripadanya.
  </div>
</div>

<div style="font-size:9pt;color:var(--muted)">
  Questions: codl@utem.edu.my &middot; 06-270 2000 (08:00 to 17:00, Monday to Friday).
</div>
@endsection
