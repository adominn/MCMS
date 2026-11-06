@extends('layouts.print')
@section('title', 'Notice of ' . ($s ? ucfirst($s->kind) : 'suspension or termination'))
@section('doc_title', 'Notice of ' . ($s ? ucfirst($s->kind) : 'suspension or termination'))
@section('doc_bm', 'Notis penggantungan')
@section('doc_ref', 'NOT-' . $m->id . '-' . now()->year)
@section('doc_file', 'notice-' . $m->id . '.pdf')
@section('content')

@php
  $kind = $s ? $s->kind : 'suspension';
  $kindLabel = ucfirst($kind);
  $eff = $s ? \Carbon\Carbon::parse($s->effective_from)->format('d M Y') : now()->format('d M Y');
  $title = $v ? $v->name_en : 'Micro-Credential';
  $ptjName = $m->ptj_name ?? 'Faculty';
  $totParticipants = $cohorts->sum('participants');
  $plan = ($s && $s->transition_plan)
    ? $s->transition_plan
    : 'Participants already registered in an open or running cohort finish it as planned. No new registrations are accepted. Participants who prefer to leave may withdraw, and CODL contacts them about fees. The lecturer stays responsible for teaching and marking until the last cohort ends.';
@endphp

@if(!$s)
  <div class="note warn" style="margin-bottom:16px;font-size:10pt">
    <b>{{ __("Draft for review.") }}</b> No suspension or termination has been recorded for this micro-credential. This is the notice that participants would receive.
  </div>
@endif

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">{{ $title }}: {{ $kindLabel }} notice</h1>
  <div class="small muted">
    Reference: NOT-{{ $m->id }}-{{ now()->year }} &middot; Effective: {{ $eff }} &middot; {{ $ptjName }}
  </div>
</div>

<div style="font-size:10.5pt;margin-bottom:4px">
  To all participants registered in <b>{{ $title }}</b>.
</div>
<div class="bm" style="font-size:9.5pt;margin-bottom:12px">
  Kepada semua peserta yang berdaftar dalam {{ $title }}.
</div>

<div style="font-size:10.5pt;line-height:1.5;margin-bottom:6px">
  The {{ $ptjName }} has decided on the {{ $kind }} of {{ $title }}, effective <b>{{ $eff }}</b>.@if($s && $s->reason) Reason: {{ $s->reason }}.@endif This notice explains what it means for you.
</div>
<div class="bm" style="font-size:9.5pt;line-height:1.4;margin-bottom:20px">
  Fakulti telah memutuskan {{ $kind === 'termination' ? 'penamatan' : 'penggantungan' }} {{ $title }} berkuat kuasa {{ $eff }}.
</div>

{{-- Who is affected --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __("Who is affected") }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __("Cohort") }}</th>
          <th>{{ __("Dates") }}</th>
          <th>{{ __("Status") }}</th>
          <th class="num">{{ __("Participants") }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($cohorts as $c)
          <tr>
            <td><b>{{ $c->name }}</b></td>
            <td>{{ $c->start }} to {{ $c->end }}</td>
            <td><span class="tag sm info">{{ $c->status }}</span></td>
            <td class="num">{{ $c->participants }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted" style="text-align:center">{{ __("No active cohorts affected.") }}</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td colspan="3">Total participants</td>
          <td class="num">{{ $totParticipants }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

{{-- Transition plan --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __("Transition plan") }}</h2></div>
  <div class="bd" style="padding:12px 16px;font-size:10.5pt;line-height:1.5">
    {{ $plan }}
  </div>
</div>

{{-- Participant safeguards --}}
<div class="note ok" style="margin-bottom:8px;font-size:10pt">
  <b>{{ __("Credentials and credit you have already earned stay valid.") }}</b> Certificates, Digital Credentials, Academic Credit Records and any ABC deposit are not changed by this decision.
</div>
<div class="bm" style="font-size:9.5pt;margin-bottom:20px">
  Kredensial dan kredit yang telah anda peroleh kekal sah. Sijil, Kredensial Digital, ACR dan deposit ABC tidak berubah.
</div>

<div class="small muted" style="margin-bottom:24px">
  Questions: codl@utem.edu.my, 06-270 2000 (08:00 to 17:00, Monday to Friday).
</div>

{{-- Signatory --}}
<div style="margin-top:32px;display:flex;gap:24px;border-top:1px dashed var(--border);padding-top:16px">
  <div style="width:50%">
    <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
    <div style="font-size:10pt"><b>{{ $dean }}</b></div>
    <div class="small muted">Deputy Dean (Academic), {{ $ptjName }}</div>
    <div class="bm" style="font-size:8.5pt">Timbalan Dekan (Akademik)</div>
  </div>
</div>

@endsection
