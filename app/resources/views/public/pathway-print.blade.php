@extends('layouts.print')
@section('title', $w->code . ' ' . $w->name . ' - ' . __('Pathway Articulation'))
@section('doc_title', __('Degree Articulation Pathway Specification'))
@section('doc_bm', 'Jadual Spesifikasi Laluan Artikulasi Ijazah')
@section('doc_ref', 'PW-' . $w->code . ' · v' . $w->version_no)

@section('content')
<div style="margin-bottom:16px">
  <h1 style="font-size:16pt;margin:0 0 4px">{{ $w->code }} - {{ $w->name }}</h1>
  <div class="muted" style="font-size:10pt">
    MQF Level {{ $w->mqf_level }} &middot; {{ $w->total_credits }} graduating credits &middot; {{ ucfirst($w->mode) }}
    @if(!empty($w->approval_ref))
      &middot; Senate Reference: <b>{{ $w->approval_ref }}</b>
    @endif
    @if(!empty($w->approved_on))
      ({{ \Carbon\Carbon::parse($w->approved_on)->format('d/m/Y') }})
    @endif
  </div>
</div>

<div class="note info" style="margin-bottom:16px;font-size:9.5pt;line-height:1.4">
  <b>{{ __('Official Articulation Roadmap:') }}</b>
  {{ __('This document lists the credit-bearing micro-credentials approved by the Senate for articulation into this undergraduate degree programme. A pathway transfers no credit by itself; each participant\'s credit is decided individually through credit transfer upon official Pejabat Pendaftar admission. At least :min% of graduating credits must be earned directly at UTeM under residency rules.', ['min' => $min]) }}
</div>

<h2 style="font-size:12pt;border-bottom:1px solid var(--primary);padding-bottom:4px;margin-bottom:8px">
  {{ __('Approved Micro-Credential to Degree Course Mappings') }}
</h2>

<table style="width:100%;margin-bottom:16px;font-size:9.5pt">
  <thead>
    <tr>
      <th style="width:35%">{{ __('Micro-credential') }}</th>
      <th style="width:40%">{{ __('Mapped Degree Course') }}</th>
      <th style="width:15%">{{ __('Equivalence') }}</th>
      <th class="num" style="width:10%">{{ __('Credits') }}</th>
    </tr>
  </thead>
  <tbody>
    @php $tot = 0; @endphp
    @forelse($items as $i)
      @php $tot += (float) $i->credits; @endphp
      <tr>
        <td><b>{{ $i->name_en }}</b></td>
        <td>{{ $i->course_code }} {{ $i->course_name }}</td>
        <td>
          <span style="font-size:8.5pt;text-transform:capitalize">{{ $i->preliminary ?? 'Suitable' }}</span>
        </td>
        <td class="num"><b>{{ rtrim(rtrim((string) $i->credits, '0'), '.') }}</b></td>
      </tr>
    @empty
      <tr>
        <td colspan="4" class="muted" style="text-align:center;padding:12px">{{ __('No mapped credentials recorded.') }}</td>
      </tr>
    @endforelse
    <tr style="border-top:2px solid var(--hairline);background:var(--s1)">
      <td colspan="3"><b>{{ __('Total Potential Transferable Credits Under This Roadmap') }}</b></td>
      <td class="num"><b>{{ rtrim(rtrim((string) $tot, '0'), '.') }}</b></td>
    </tr>
  </tbody>
</table>

<div style="margin-top:20px;border-top:1px solid #e0e0e0;padding-top:12px;font-size:8.5pt;color:var(--muted);line-height:1.4">
  <b>{{ __('Academic Governance & Regulatory Notice:') }}</b>
  {{ __('No micro-credential leads to a degree automatically, however many are accumulated. Credit exemption is subject to faculty approval, valid Academic Bank of Credit (ABC) deposits within 10 years of completion, and compliance with the 30% UTeM minimum home-credit requirement.') }}
</div>
@endsection
