@extends('layouts.print')
@section('title', 'Offering Readiness: ' . $o->name_en . ' (' . $o->cohort_name . ')')
@section('doc_title', 'Micro-Credential Offering Readiness Checklist (Appendix B)')
@section('doc_bm', 'Senarai Semak Kesediaan Penawaran Micro-Credential (Lampiran B)')
@section('doc_ref', 'COHORT-' . $o->id . ' · ' . $o->cohort_name)
@section('doc_file', 'readiness-cohort-' . $o->id . '.pdf')
@section('content')

@php
  $cat = strtoupper($o->category ?? 'UMC');
  $ptjName = $o->ptj_name ?: ($o->ptj_code ?? 'FTMK');
  $lecConf = $confirmations->where('role', 'lecturer')->last();
  $ptjConf = $confirmations->where('role', 'ptj')->last();
  $supConf = $confirmations->where('role', 'support')->last();
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:0.5px;font-weight:600;color:var(--accent, #0f62fe);margin-bottom:2px">
    Appendix B &middot; Lampiran B (Garis Panduan Micro-Credential UTeM)
  </div>
  <h1 style="font-size:18pt;margin:0 0 4px">{{ $o->name_en }} &middot; {{ $o->cohort_name }}</h1>
  <div class="small muted">
    {{ $cat }} &middot; Version {{ $o->version_no }} &middot; {{ $ptjName }} &middot; Coordinator: {{ $o->lecturer_name }} &middot; Status: <span class="tag {{ $o->readiness_state === 'ready' ? 'ok' : ($o->readiness_state === 'ready_conditions' ? 'warn' : 'n') }}">{{ config('offerings.outcomes.' . $o->readiness_state) ?: ucfirst($o->readiness_state) }}</span>
  </div>
</div>

{{-- B1. Maklumat Penawaran dan Kohort --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('B1. Maklumat Penawaran dan Kohort') }} &middot; {{ __('Offering & Cohort Information') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('MC Title') }}</dt>
      <dd><b>{{ $o->name_en }}</b></dd>

      <dt style="font-size:9pt">{{ __('Category') }}</dt>
      <dd><b>{{ $cat }}</b> ({{ ['umc' => 'Unbundled Micro-Credential', 'samc' => 'Stand-Alone Micro-Credential', 'mce' => 'Micro-Credential Eksekutif'][$o->category] ?? $cat }})</dd>

      <dt style="font-size:9pt">{{ __('PTj / Faculty') }}</dt>
      <dd>{{ $ptjName }}</dd>

      <dt style="font-size:9pt">{{ __('Course Coordinator') }}</dt>
      <dd>{{ $o->lecturer_name }}</dd>

      <dt style="font-size:9pt">{{ __('Cohort / Session') }}</dt>
      <dd><b>{{ $o->cohort_name }}</b> (Capacity: {{ $o->capacity }} places)</dd>

      <dt style="font-size:9pt">{{ __('Offering Dates') }}</dt>
      <dd>{{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }} (Registration: {{ \Carbon\Carbon::parse($o->reg_opens_on)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y') }})</dd>

      <dt style="font-size:9pt">{{ __('Delivery Mode') }}</dt>
      <dd>{{ ucfirst($o->delivery_mode) }}@if(!empty($o->platform_ref)) &middot; Platform: {{ $o->platform_ref }}@endif</dd>

      <dt style="font-size:9pt">{{ __('SAMC QV Status') }}</dt>
      <dd>
        @if($o->category === 'samc')
          @if($qv)
            <span class="tag ok">Sah sehingga {{ \Carbon\Carbon::parse($qv->valid_until)->format('d/m/Y') }} (Quality-Verified)</span>
          @else
            <span class="tag warn">Dalam proses / Belum disahkan</span>
          @endif
        @else
          <span class="tag info">Tidak Berkenaan (TB &ndash; {{ $cat }})</span>
        @endif
      </dd>
    </dl>
  </div>
</div>

{{-- B2 to B7 Sections --}}
@foreach($sections as $secKey => $sec)
  <div class="panel" style="margin-bottom:16px">
    <div class="hd">
      <h2 style="font-size:11pt">{{ $sec['code'] }}. {{ __($sec['title_ms']) }} &middot; <span class="muted" style="font-weight:normal">{{ __($sec['title_en']) }}</span></h2>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:5%">#</th>
            <th style="width:45%">{{ __('Perkara Semakan (Checklist Item)') }}</th>
            <th style="width:12%">{{ __('Status') }}</th>
            <th style="width:26%">{{ __('Catatan / Evidens (Notes / Evidence)') }}</th>
            <th style="width:12%">{{ __('Pengesahan PTj') }}</th>
          </tr>
        </thead>
        <tbody>
          @php $i = 1; @endphp
          @foreach($sec['items'] as $itemKey => $itemData)
            @php
              $it = $items[$itemKey] ?? (object)['status' => 'not_yet', 'note' => '', 'accepted' => false];
            @endphp
            <tr>
              <td>{{ $i++ }}</td>
              <td>
                <b>{{ $itemData['ms'] }}</b>
                <div class="small muted">{{ $itemData['en'] }}</div>
              </td>
              <td>
                <span class="tag {{ $it->status === 'yes' ? 'ok' : ($it->status === 'na' ? 'info' : 'warn') }}">
                  {{ ['yes' => 'Ya (Yes)', 'not_yet' => 'Belum (Not yet)', 'na' => 'TB (N/A)'][$it->status] ?? ucfirst($it->status) }}
                </span>
              </td>
              <td>
                {{ $it->note ?: '—' }}
              </td>
              <td>
                @if($it->accepted)
                  <span class="tag ok">{{ __('Diterima (Accepted)') }}</span>
                @else
                  <span class="muted">{{ __('Belum disahkan') }}</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endforeach

{{-- B8. Pengesahan Kesediaan Sebelum Penawaran --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('B8. Pengesahan Kesediaan Sebelum Penawaran') }} &middot; {{ __('Pre-Offering Readiness Sign-Off') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:24%">{{ __('Peranan (Role)') }}</th>
          <th style="width:26%">{{ __('Pegawai / Jawatan (Officer & Position)') }}</th>
          <th style="width:16%">{{ __('Status Kesediaan (Outcome)') }}</th>
          <th style="width:14%">{{ __('Tarikh (Date)') }}</th>
          <th style="width:20%">{{ __('Catatan / Syarat (Remarks)') }}</th>
        </tr>
      </thead>
      <tbody>
        {{-- P1: Coordinator --}}
        <tr>
          <td>
            <b>{{ __('1. Penyelaras Micro-Credential') }}</b>
            <div class="small muted">{{ __('Course Coordinator (P1)') }}</div>
          </td>
          <td>
            <b>{{ $lecConf?->name ?: $o->lecturer_name }}</b>
            <div class="small muted">{{ __('Penyelaras Kursus') }}, {{ $ptjName }}</div>
          </td>
          <td>
            @if($lecConf)
              <span class="tag {{ $lecConf->outcome === 'ready' ? 'ok' : ($lecConf->outcome === 'ready_conditions' ? 'warn' : 'n') }}">
                {{ config('offerings.outcomes.' . $lecConf->outcome) ?: ucfirst($lecConf->outcome) }}
              </span>
            @else
              <span class="muted">{{ __('Belum dihantar') }}</span>
            @endif
          </td>
          <td>{{ $lecConf ? \Carbon\Carbon::parse($lecConf->created_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $lecConf?->conditions ?: '—' }}</td>
        </tr>

        {{-- P2: PTj Management --}}
        <tr>
          <td>
            <b>{{ __('2. Wakil Pengurusan PTj / Fakulti') }}</b>
            <div class="small muted">{{ __('PTj / Faculty Management (P2)') }}</div>
          </td>
          <td>
            @if($ptjConf)
              <b>{{ $ptjConf->name }}</b>
              <div class="small muted">{{ $ptjConf->position ?: __('Timbalan Dekan (Akademik)') }}, {{ $ptjName }}</div>
            @else
              <span class="muted">{{ __('Belum disahkan') }}</span>
            @endif
          </td>
          <td>
            @if($ptjConf)
              <span class="tag {{ $ptjConf->outcome === 'ready' ? 'ok' : ($ptjConf->outcome === 'ready_conditions' ? 'warn' : 'n') }}">
                {{ config('offerings.outcomes.' . $ptjConf->outcome) ?: ucfirst($ptjConf->outcome) }}
              </span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td>{{ $ptjConf ? \Carbon\Carbon::parse($ptjConf->created_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $ptjConf?->conditions ?: '—' }}</td>
        </tr>

        {{-- P5: Support / System Party --}}
        <tr>
          <td>
            <b>{{ __('3. Pihak Sokongan / Sistem') }}</b>
            <div class="small muted">{{ __('Support / System Party (P5 &ndash; CODL / PPP)') }}</div>
          </td>
          <td>
            @if($supConf)
              <b>{{ $supConf->name }}</b>
              <div class="small muted">{{ $supConf->position ?: __('Pusat Pembelajaran Digital dan Fleksibel (CODL)') }}</div>
            @else
              <span class="muted">{{ __('Disahkan automatik melalui integrasi platform') }}</span>
            @endif
          </td>
          <td>
            @if($supConf)
              <span class="tag {{ $supConf->outcome === 'ready' ? 'ok' : 'n' }}">
                {{ config('offerings.outcomes.' . $supConf->outcome) ?: ucfirst($supConf->outcome) }}
              </span>
            @else
              <span class="tag ok">{{ __('Sedia (Ready)') }}</span>
            @endif
          </td>
          <td>{{ $supConf ? \Carbon\Carbon::parse($supConf->created_at)->format('d/m/Y') : ($ptjConf ? \Carbon\Carbon::parse($ptjConf->created_at)->format('d/m/Y') : '—') }}</td>
          <td>{{ $supConf?->conditions ?: __('Platform uLearn/UCreds sedia beroperasi.') }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

{{-- NOTA PENTING LAMPIRAN B --}}
<div class="print-note-box" style="margin-bottom:20px">
  <div style="font-weight:700;margin-bottom:4px;color:#ba1b1b;text-transform:uppercase;letter-spacing:0.5px">
    {{ __('NOTA PENTING (Lampiran B) &middot; IMPORTANT NOTICE (Appendix B)') }}
  </div>
  <div style="font-size:9pt;line-height:1.5">
    <b>{{ __('Bahasa Melayu:') }}</b> {{ __('Senarai semak ini bertujuan memastikan kesediaan operasi, integriti pelaksanaan, dan perlindungan peserta sebelum sesuatu penawaran dimulakan. Semakan kesediaan operasi ini TIDAK menggantikan kelulusan akademik terdahulu bagi sesuatu Micro-Credential.') }}
  </div>
  <div style="font-size:9pt;line-height:1.5;margin-top:4px;color:var(--text-secondary)">
    <b>{{ __('English:') }}</b> {{ __('This checklist ensures operational readiness, delivery integrity, and learner protection before an offering commences. This operational readiness check DOES NOT replace the prior academic approval of the Micro-Credential.') }}
  </div>
</div>

@endsection
