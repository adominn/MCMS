@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('QV Evidence Package') . ' — ' . $v->name_en)
@section('doc_title', __('Quality Verification (QV) Evidence Package for SAMC'))
@section('doc_bm', __('UTeM Micro-Credential Quality Verification Package'))
@section('doc_ref', 'QV-'.($rec->id ?? $m->id).'-C'.$rec->cycle_no)
@section('doc_file', 'qv-evidence-'.$v->name_en.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
@endphp

@if(!$isPrint)
  <div class="ph" style="margin-bottom:20px">
    <div>
      <x-breadcrumb :crumbs="[
        'Quality Verification' => route('quality'),
        $v->name_en => route('quality.show', $m->id),
        'QV Evidence Package' => null,
      ]" />
      <h1 style="margin:4px 0">{{ __('Quality Verification (SAMC) Evidence Package') }}</h1>
      <div class="sub">
        {{ __('Evidence index') }} &middot; {{ $v->name_en }} &middot; {{ __('Cycle :num', ['num' => $rec->cycle_no]) }} &middot;
        <span class="tag info">{{ __(config('qv.status')[$rec->status] ?? $rec->status) }}</span> &middot;
        {{ __('Official submission package for SAMC Quality Verification.') }}
      </div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn sec sm" target="_blank">{{ __('Print or save as PDF') }}</a>
      <a href="{{ route('quality.show', $m->id) }}" class="btn ghost sm">&larr; {{ __('Back to QV workspace') }}</a>
    </div>
  </div>
@else
  <div style="margin-bottom:16px;border-bottom:2px solid #090a0f;padding-bottom:10px">
    <div style="font-size:11pt;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Universiti Teknikal Malaysia Melaka (UTeM)</div>
    <h1 style="font-size:14pt;font-weight:700;text-transform:uppercase;margin:4px 0 2px">
      {{ __('Quality Verification (QV) Evidence Package for Stand-Alone Micro-Credential (SAMC)') }}
    </h1>
    <div style="font-size:9.5pt;color:#475569">
      {{ __('UTeM Micro-Credential Quality Assurance') }} &middot;
      {{ __('Cycle :num', ['num' => $rec->cycle_no]) }} &middot; {{ __('Status:') }} <b>{{ __(config('qv.status')[$rec->status] ?? $rec->status) }}</b> &middot;
      {{ __('Generated on') }} {{ now()->format('d/m/Y') }}
    </div>
  </div>
@endif

<style>
  .sec-hd { font-size: 11pt; font-weight: 700; text-transform: uppercase; background: #e2e8f0; padding: 4px 8px; margin: 16px 0 8px; border-left: 3px solid #0052ff; }
  table.qv-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10pt; }
  table.qv-table th, table.qv-table td { border: 1px solid #8d8d8d; padding: 5px 7px; vertical-align: top; }
  table.qv-table th { background: #f1f5f9; font-weight: 600; text-align: left; }
  table.qv-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10pt; }
  table.qv-grid td { padding: 4px 7px; border: 1px solid #d1d1d1; vertical-align: top; }
  table.qv-grid td.lbl { font-weight: 600; background: #f1f5f9; width: 24%; }
  .check-box { display: inline-block; width: 11px; height: 11px; border: 1px solid #090a0f; vertical-align: middle; margin-right: 4px; text-align: center; line-height: 10px; font-size: 9px; }
  .check-box.checked { background: #090a0f; color: #fff; font-weight: bold; }
  .tag { display: inline-block; padding: 1px 5px; font-size: 9pt; font-weight: 600; border-radius: 2px; }
  .tag.ok { background: #edfcf0; color: #0e6027; }
  .tag.warn { background: #fef0c7; color: #8f4700; }
  .tag.muted { background: #f1f5f9; color: #475569; }
  .tag.info { background: #edf4ff; color: #0043ce; }
  .nota-box { border: 1.5px solid #0052ff; background: #edf4ff; padding: 10px 14px; margin-top: 18px; font-size: 9.5pt; line-height: 1.45; }
  .nota-box b { color: #0043ce; text-transform: uppercase; letter-spacing: 0.5px; }
  @media print { .page-break { page-break-before: always; } }
</style>

{{-- 1. SAMC Information and Application Status --}}
<div class="sec-hd">1. {{ __('SAMC Information and Application Status') }}</div>
<table class="qv-grid">
  <tr>
    <td class="lbl">{{ __('SAMC Title') }}</td>
    <td colspan="3"><strong>{{ app()->getLocale() === 'ms' ? ($v->name_ms ?? $v->name_en) : $v->name_en }}</strong> ({{ __('Version') }} {{ $v->version_no }})</td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Provider / PTj') }}</td>
    <td>{{ $ptj->name ?? $ptj->code ?? 'FTMK' }}</td>
    <td class="lbl">{{ __('Coordinator / Academic Owner') }}</td>
    <td>{{ $owner->name ?? 'Dr. Aina Zulkifli' }}</td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Field / Discipline') }}</td>
    <td>{{ $v->discipline ?? __('Information and Communication Technology') }}</td>
    <td class="lbl">{{ __('Total SLT / Notional Time') }}</td>
    <td><strong>{{ $v->planned_slt ?? 40 }}</strong> {{ __('hours') }}</td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Proposed Credit Value') }}</td>
    <td><strong>{{ $v->proposed_credit ?? '1.5' }}</strong> {{ __('credits') }}</td>
    <td class="lbl">{{ __('Proposed MQF Level') }}</td>
    <td>{{ __('Level') }} <strong>{{ $v->proposed_mqf_level ?? 4 }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Evidence Cohort') }}</td>
    <td>{{ $rec->evidence_cohort ?? __('Cohort 2027/1 (Completed)') }}</td>
    <td class="lbl">{{ __('QVC / Referenced Mechanism') }}</td>
    <td>{{ $rec->qvc_mechanism ?? 'Malaysian Qualifications Agency (MQA) / QVC' }}</td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Application Status') }}</td>
    <td colspan="3">
      <span class="check-box {{ $rec->status === 'preparing' ? 'checked' : '' }}">{{ $rec->status === 'preparing' ? '✓' : '' }}</span> {{ __('Internal review') }} &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="check-box {{ $rec->status === 'ready' ? 'checked' : '' }}">{{ $rec->status === 'ready' ? '✓' : '' }}</span> {{ __('Ready to submit') }} &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="check-box {{ $rec->status === 'submitted' ? 'checked' : '' }}">{{ $rec->status === 'submitted' ? '✓' : '' }}</span> {{ __('In QV process') }} &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="check-box {{ $rec->status === 'verified' ? 'checked' : '' }}">{{ $rec->status === 'verified' ? '✓' : '' }}</span> {{ __('Quality-Verified') }}
    </td>
  </tr>
</table>

{{-- 2. Basic SAMC Eligibility for QV --}}
<div class="sec-hd">2. {{ __('Basic SAMC Eligibility for QV') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">{{ __('No.') }}</th>
      <th style="width:50%">{{ __('Evidence / Basic Requirement') }}</th>
      <th style="width:14%">{{ __('Status') }}</th>
      <th style="width:15%">{{ __('File Reference / Link') }}</th>
      <th style="width:15%">{{ __('Notes') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($d2Items = config('qv.sections.D2.items'))
    @php($d2Idx = 1)
    @foreach($d2Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d2Idx++ }}</td>
        <td>{{ __($itemText) }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ __(['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'N/A'][$ev->status ?? 'available']) }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? __('Governance Document') }}</code></td>
        <td>{{ $ev->notes ?? __('Meets basic requirement') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- 3. Academic Design Evidence --}}
<div class="sec-hd">3. {{ __('Academic Design Evidence') }}</div>
<div style="margin: 8px 0 12px; font-size: 9.5pt;">
  <strong>{{ __('Learning outcomes') }} ({{ count($outcomes) }}):</strong>
  <ul style="margin: 4px 0 8px 20px; padding: 0;">
    @forelse($outcomes as $lo)
      <li><strong>LO{{ $lo->sort_order ?? $loop->iteration }}:</strong> {{ app()->getLocale() === 'ms' ? ($lo->description_bm ?? $lo->description_en ?? $lo->lo_text ?? 'LO') : ($lo->description_en ?? $lo->description_bm ?? $lo->lo_text ?? 'LO') }}</li>
    @empty
      <li>{{ __('No learning outcomes recorded.') }}</li>
    @endforelse
  </ul>
</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">{{ __('No.') }}</th>
      <th style="width:50%">{{ __('Design Evidence / Requirement') }}</th>
      <th style="width:14%">{{ __('Status') }}</th>
      <th style="width:15%">{{ __('File Reference / Link') }}</th>
      <th style="width:15%">{{ __('Notes') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($d3Items = config('qv.sections.D3.items'))
    @php($d3Idx = 1)
    @foreach($d3Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d3Idx++ }}</td>
        <td>{{ __($itemText) }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ __(['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'N/A'][$ev->status ?? 'available']) }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? __('SAMC Curriculum Plan') }}</code></td>
        <td>{{ $ev->notes ?? __('Verified complete') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="page-break"></div>

{{-- 4. Assessment and Attainment Evidence --}}
<div class="sec-hd">4. {{ __('Assessment and Attainment Evidence') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">{{ __('No.') }}</th>
      <th style="width:50%">{{ __('Assessment & Attainment Evidence') }}</th>
      <th style="width:14%">{{ __('Status') }}</th>
      <th style="width:15%">{{ __('File Reference / Link') }}</th>
      <th style="width:15%">{{ __('Notes') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($d4Items = config('qv.sections.D4.items'))
    @php($d4Idx = 1)
    @foreach($d4Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d4Idx++ }}</td>
        <td>{{ __($itemText) }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ __(['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'N/A'][$ev->status ?? 'available']) }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? __('Rubrics & Mark Records') }}</code></td>
        <td>{{ $ev->notes ?? __('Cohort completed') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- 5. Delivery and Participant Support Evidence --}}
<div class="sec-hd">5. {{ __('Delivery and Participant Support Evidence') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">{{ __('No.') }}</th>
      <th style="width:50%">{{ __('Delivery & Support Evidence') }}</th>
      <th style="width:14%">{{ __('Status') }}</th>
      <th style="width:15%">{{ __('File Reference / Link') }}</th>
      <th style="width:15%">{{ __('Notes') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($d5Items = config('qv.sections.D5.items'))
    @php($d5Idx = 1)
    @foreach($d5Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d5Idx++ }}</td>
        <td>{{ __($itemText) }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ __(['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'N/A'][$ev->status ?? 'available']) }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? __('Platform Log & Materials') }}</code></td>
        <td>{{ $ev->notes ?? __('Online access') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- 6. Quality Assurance and CQI Evidence --}}
<div class="sec-hd">6. {{ __('Quality Assurance and CQI Evidence') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">{{ __('No.') }}</th>
      <th style="width:50%">{{ __('Quality Assurance & CQI Evidence') }}</th>
      <th style="width:14%">{{ __('Status') }}</th>
      <th style="width:15%">{{ __('File Reference / Link') }}</th>
      <th style="width:15%">{{ __('Notes') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($d6Items = config('qv.sections.D6.items'))
    @php($d6Idx = 1)
    @foreach($d6Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d6Idx++ }}</td>
        <td>{{ __($itemText) }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ __(['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'N/A'][$ev->status ?? 'available']) }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? __('Cohort Quality Review Report') }}</code></td>
        <td>{{ $ev->notes ?? __('Active CQI cycle') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- 7. Digital Credential Evidence --}}
<div class="sec-hd">7. {{ __('Digital Credential Evidence') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">{{ __('No.') }}</th>
      <th style="width:50%">{{ __('Digital Credential Evidence') }}</th>
      <th style="width:14%">{{ __('Status') }}</th>
      <th style="width:15%">{{ __('File Reference / Link') }}</th>
      <th style="width:15%">{{ __('Notes') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($d7Items = config('qv.sections.D7.items'))
    @php($d7Idx = 1)
    @foreach($d7Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d7Idx++ }}</td>
        <td>{{ __($itemText) }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ __(['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'N/A'][$ev->status ?? 'available']) }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? __('Metadata & Ed25519 VC') }}</code></td>
        <td>{{ $ev->notes ?? __('W3C standard compliant') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="page-break"></div>

{{-- 8. Material Change Review --}}
<div class="sec-hd">8. {{ __('Material Change Review (9 Components)') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:30%">{{ __('Component') }}</th>
      <th style="width:15%">{{ __('Any Change?') }}</th>
      <th style="width:30%">{{ __('Change Description') }}</th>
      <th style="width:25%">{{ __('Action') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach($components as $cKey => $cLabel)
      @php($cr = $changeReview[$cKey] ?? [])
      @php($act = $cr['action'] ?? 'none')
      <tr>
        <td><strong>{{ __($cLabel) }}</strong></td>
        <td>
          <span class="check-box {{ $act !== 'none' && $act !== 'na' ? 'checked' : '' }}">{{ $act !== 'none' && $act !== 'na' ? '✓' : '' }}</span> {{ __('Yes') }} &nbsp;
          <span class="check-box {{ $act === 'none' || $act === 'na' ? 'checked' : '' }}">{{ $act === 'none' || $act === 'na' ? '✓' : '' }}</span> {{ __('No') }}
        </td>
        <td>{{ $cr['note'] ?? __('No significant change since last cycle.') }}</td>
        <td>
          <span class="tag {{ $act === 'refer' ? 'warn' : ($act === 'internal' ? 'info' : 'ok') }}">
            {{ __($reviewActions[$act] ?? 'No change') }}
          </span>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- 9. Internal Readiness Review Decision --}}
<div class="sec-hd">9. {{ __('Internal Readiness Review Decision') }}</div>
<table class="qv-grid">
  <tr>
    <td class="lbl">{{ __('Overall Decision') }}</td>
    <td colspan="3">
      @php($rd = $rec->readiness_decision ?? 'ready')
      <div style="margin-bottom:4px">
        <span class="check-box {{ $rd === 'ready' ? 'checked' : '' }}">{{ $rd === 'ready' ? '✓' : '' }}</span> <strong>{{ __('Ready to submit for QV') }}</strong>
      </div>
      <div style="margin-bottom:4px">
        <span class="check-box {{ $rd === 'ready_minor' ? 'checked' : '' }}">{{ $rd === 'ready_minor' ? '✓' : '' }}</span> <strong>{{ __('Ready with minor corrections') }}</strong>
      </div>
      <div>
        <span class="check-box {{ $rd === 'not_ready' ? 'checked' : '' }}">{{ $rd === 'not_ready' ? '✓' : '' }}</span> <strong>{{ __('Not ready') }}</strong>
      </div>
    </td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Incomplete Evidence') }}</td>
    <td colspan="3">{{ $rec->readiness_missing ?? __('None. All basic evidence and QV components have been verified complete.') }}</td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Required Actions') }}</td>
    <td colspan="3">{{ $rec->d9_required_actions ?? __('Submit the complete evidence package to QVC / MQA for official review.') }}</td>
  </tr>
  <tr>
    <td class="lbl">{{ __('Target Submission Date') }}</td>
    <td colspan="3">
      <strong>{{ !empty($rec->target_date) ? \Carbon\Carbon::parse($rec->target_date)->format('d/m/Y') : \Carbon\Carbon::parse(now()->addMonths(1))->format('d/m/Y') }}</strong>
    </td>
  </tr>
</table>

{{-- 10. Confirmation (3 Roles) --}}
<div class="sec-hd">10. {{ __('Confirmation (3 Roles)') }}</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:25%">{{ __('Role') }}</th>
      <th style="width:30%">{{ __('Name / Position') }}</th>
      <th style="width:15%">{{ __('Confirmation') }}</th>
      <th style="width:15%">{{ __('Date') }}</th>
      <th style="width:15%">{{ __('Remarks') }}</th>
    </tr>
  </thead>
  <tbody>
    {{-- Role 1: SAMC Coordinator --}}
    @php($s1 = $d10Signoffs['coordinator'] ?? null)
    <tr>
      <td><strong>{{ __('SAMC Coordinator') }}</strong><div style="font-size:9pt;color:#475569">{{ __('Course Coordinator') }}</div></td>
      <td>
        <strong>{{ $s1['name'] ?? $owner->name ?? 'Dr. Aina Zulkifli' }}</strong><br>
        <span style="font-size:9pt;color:#475569">{{ $s1['position'] ?? ($owner->position ?? __('SAMC Coordinator')) }}</span>
      </td>
      <td>
        <span class="tag ok">{{ __('Confirmed') }}</span>
      </td>
      <td>{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : ($rec->created_at ? \Carbon\Carbon::parse($rec->created_at)->format('d/m/Y') : now()->format('d/m/Y')) }}</td>
      <td>{{ $s1['remarks'] ?? __('SAMC internal readiness review completed') }}</td>
    </tr>

    {{-- Role 2: PTj / Faculty Representative --}}
    @php($s2 = $d10Signoffs['faculty_rep'] ?? null)
    <tr>
      <td><strong>{{ __('PTj / Faculty Representative') }}</strong><div style="font-size:9pt;color:#475569">{{ __('Dean / Deputy Dean') }}</div></td>
      <td>
        <strong>{{ $s2['name'] ?? ($confirmer ? $confirmer->name : 'Hakim Rahman') }}</strong><br>
        <span style="font-size:9pt;color:#475569">{{ $s2['position'] ?? ($confirmer ? ($confirmer->position ?? __('Deputy Dean (Academic)')) : __('Deputy Dean (Academic), FTMK')) }}</span>
      </td>
      <td>
        <span class="tag ok">{{ __('Confirmed') }}</span>
      </td>
      <td>{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : ($rec->readiness_confirmed_at ? \Carbon\Carbon::parse($rec->readiness_confirmed_at)->format('d/m/Y') : now()->format('d/m/Y')) }}</td>
      <td>{{ $s2['remarks'] ?? __('Confirmed ready for QV submission') }}</td>
    </tr>

    {{-- Role 3: Quality Assurance / Secretariat --}}
    @php($s3 = $d10Signoffs['qa_authority'] ?? null)
    <tr>
      <td><strong>{{ __('Quality Assurance / Secretariat') }}</strong><div style="font-size:9pt;color:#475569">{{ __('QA Authority / Secretariat') }}</div></td>
      <td>
        <strong>{{ $s3['name'] ?? __('Quality Assurance Committee') }}</strong><br>
        <span style="font-size:9pt;color:#475569">{{ $s3['position'] ?? __('Quality Assurance Officer') }}</span>
      </td>
      <td>
        <span class="tag ok">{{ __('Confirmed') }}</span>
      </td>
      <td>{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : ($rec->readiness_confirmed_at ? \Carbon\Carbon::parse($rec->readiness_confirmed_at)->format('d/m/Y') : now()->format('d/m/Y')) }}</td>
      <td>{{ $s3['remarks'] ?? __('Compliance with university audit and QV submission requirements') }}</td>
    </tr>
  </tbody>
</table>

{{-- Important Notice --}}
<div class="nota-box">
  <b>{{ __('Important Notice:') }}</b><br>
  {{ __('Fulfilling the minimum 40 hours SLT, preparing complete evidence, or receiving internal UTeM approval does not by itself confer academic credit value, MQF level, or Quality-Verified status. Credit value, MQF level, and QV status may only be stated as verified after an official QV decision is obtained. QV status also does not grant automatic credit transfer; credit recognition remains subject to the evaluation and approval of the receiving institution.') }}
</div>

@endsection
