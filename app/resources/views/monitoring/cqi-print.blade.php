@extends('layouts.print')
@section('title', __('Quality Review & CQI Report') . ' - ' . $r->reference)
@section('doc_title', __('Quality Assurance, Monitoring & CQI Report'))
@section('doc_bm', 'Laporan Jaminan Kualiti, Pemantauan & CQI')
@section('doc_ref', $r->reference)
@section('doc_file', 'cqi-report-' . strtolower($r->reference) . '.pdf')
@section('content')
  <style>
    .sec-title { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; background: #eef0f6; padding: 5px 8px; margin: 14px 0 6px; border-left: 3px solid #0b1a3d; break-after: avoid; page-break-after: avoid; }
    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt; }
    table.data-table th, table.data-table td { border: 1px solid #8d8d8d; padding: 4px 6px; vertical-align: top; }
    table.data-table th { background: #f1f5f9; font-weight: 600; text-align: left; }
    table.grid-form { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt; }
    table.grid-form td { padding: 4px 6px; border: 1px solid #c6cbd6; vertical-align: top; }
    table.grid-form td.label { font-weight: 600; background: #f2f4f8; width: 22%; }
    .nota-box { border: 1px solid #0b1a3d; border-left: 3px solid #0b1a3d; background: #f4f5f8; padding: 8px 12px; margin-top: 14px; font-size: 8.5pt; line-height: 1.45; }
    .nota-box b { color: #0b1a3d; text-transform: uppercase; letter-spacing: 0.4px; }
    .check-box { display: inline-block; width: 11px; height: 11px; border: 1px solid #090a0f; vertical-align: middle; margin-right: 4px; text-align: center; line-height: 10px; font-size: 8pt; }
    .check-box.checked { background: #0b1a3d; color: #fff; font-weight: bold; }
    .page-break { page-break-before: always; break-before: page; }
  </style>

  <div style="margin-bottom:10px">
    <h1 style="font-size:13pt;margin:0 0 2px;text-transform:uppercase">{{ __('Quality Assurance, Monitoring and Continuous Quality Improvement (CQI) Report') }}</h1>
    <div class="muted small">{{ __('Cohort performance evaluation, learning outcome attainment, and continuous improvement record') }}</div>
  </div>

  {{-- Offering Information and Monitoring Period --}}
  <div class="sec-title">1. {{ __('Offering Information and Monitoring Period') }}</div>
  <table class="grid-form">
    <tr>
      <td class="label">{{ __('Micro-Credential Title') }}</td>
      <td colspan="3"><strong>{{ app()->getLocale() === 'ms' ? ($offering->name_ms ?? $offering->name_en) : ($offering->name_en ?? $offering->name_ms) }}</strong> ({{ __('Version') }} {{ $offering->version_no ?? 1 }})</td>
    </tr>
    <tr>
      <td class="label">{{ __('Category') }}</td>
      @php($catLower = strtolower((string) ($offering->category ?? '')))
      <td style="width:28%">
        <span class="check-box {{ $catLower === 'umc' ? 'checked' : '' }}">{{ $catLower === 'umc' ? '✓' : '' }}</span> UMC &nbsp;
        <span class="check-box {{ $catLower === 'samc' ? 'checked' : '' }}">{{ $catLower === 'samc' ? '✓' : '' }}</span> SAMC &nbsp;
        <span class="check-box {{ $catLower === 'mce' ? 'checked' : '' }}">{{ $catLower === 'mce' ? '✓' : '' }}</span> MCE
      </td>
      <td class="label">{{ __('PTj / Faculty') }}</td>
      <td>{{ $offering->ptj_name ?? $offering->ptj_code ?? '–' }}</td>
    </tr>
    <tr>
      <td class="label">{{ __('Coordinator') }}</td>
      <td>{{ $offering->owner_name ?? '–' }}</td>
      <td class="label">{{ __('Cohort / Session') }}</td>
      <td>{{ $offering->cohort_name ?? '–' }}</td>
    </tr>
    <tr>
      <td class="label">{{ __('Delivery Period') }}</td>
      <td>
        @if(!empty($offering->start_date) && !empty($offering->end_date))
          {{ \Carbon\Carbon::parse($offering->start_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($offering->end_date)->format('d/m/Y') }}
        @else
          –
        @endif
      </td>
      <td class="label">{{ __('CQI Review Date') }}</td>
      <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td class="label">{{ __('Registered Learners') }}</td>
      <td><strong>{{ $enrolledCount ?? $ind['participants'] }}</strong></td>
      <td class="label">{{ __('Completed Learners') }}</td>
      <td><strong>{{ $completedCount ?? 0 }}</strong></td>
    </tr>
  </table>

  {{-- Cohort Performance Summary --}}
  <div class="sec-title">2. {{ __('Cohort Performance Summary') }}</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:30%">{{ __('Indicator') }}</th>
        <th style="width:20%">{{ __('Value / Finding') }}</th>
        <th style="width:20%">{{ __('Target / Benchmark') }}</th>
        <th style="width:15%">{{ __('Status') }}</th>
        <th style="width:15%">{{ __('Remarks') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ind['rows'] as $rx)
        <tr>
          <td><strong>{{ __($rx['label']) }}</strong></td>
          <td>
            @if($rx['key'] === 'access_issues')
              {{ $rx['value'] }}
            @elseif($rx['value'] === null)
              <span class="tag info">{{ __('Not recorded') }}</span>
            @else
              {{ $rx['value'] }}%
            @endif
          </td>
          <td>
            @if($rx['key'] === 'access_issues')
              {{ $rx['target'] }}
            @else
              {{ $rx['target'] }}%
            @endif
          </td>
          <td>
            @if($rx['value'] === null)
              <span class="tag info">{{ __('Not recorded') }}</span>
            @elseif($rx['below'])
              <span class="tag err">{{ $rx['key'] === 'access_issues' ? __('Reported') : __('Below target') }}</span>
            @else
              <span class="tag ok">{{ $rx['key'] === 'access_issues' ? __('None') : __('Met') }}</span>
            @endif
          </td>
          <td>{{ $rx['notes'] ?? '–' }}</td>
        </tr>
      @endforeach
      <tr>
        <td><strong>{{ __('Average / median attainment') }}</strong></td>
        <td>{{ __('Average:') }} {{ $ind['average'] !== null ? $ind['average'].'%' : __('n/a') }} &middot; {{ __('Median:') }} {{ $ind['median'] !== null ? $ind['median'].'%' : __('n/a') }}</td>
        <td>&ge; 50%</td>
        <td>
          @if($ind['average'] === null)
            <span class="tag info">{{ __('Not recorded') }}</span>
          @elseif($ind['average'] >= 50)
            <span class="tag ok">{{ __('Met') }}</span>
          @else
            <span class="tag err">{{ __('Below target') }}</span>
          @endif
        </td>
        <td>{{ __('Verified assessment marks') }}</td>
      </tr>
    </tbody>
  </table>

  <div class="sec-title">3. {{ __('Learning Outcome Attainment Analysis') }}</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:6%">{{ __('No.') }}</th>
        <th style="width:10%">{{ __('Code') }}</th>
        <th style="width:40%">{{ __('Learning Outcome') }}</th>
        <th style="width:20%">{{ __('Assessment Method / Evidence') }}</th>
        <th style="width:12%">{{ __('Attainment (%)') }}</th>
        <th style="width:12%">{{ __('Status') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ind['outcomes'] as $idx => $lo)
        <tr>
          <td>{{ $idx + 1 }}</td>
          <td><strong>{{ $lo['code'] }}</strong></td>
          <td>{{ app()->getLocale() === 'ms' ? ($lo['text_ms'] ?? $lo['text']) : ($lo['text'] ?? $lo['text_ms']) }}</td>
          <td>{{ $lo['assessment_method'] ?? __('Course assessments') }}</td>
          <td><strong>{{ $lo['share'] !== null ? $lo['share'].'%' : '–' }}</strong></td>
          <td>
            @if($lo['share'] === null)
              <span class="tag info">n/a</span>
            @elseif($lo['share'] >= 50)
              <span class="tag ok">{{ __('Satisfactory') }}</span>
            @else
              <span class="tag err">{{ __('Needs CQI') }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">{{ __('No learning outcomes mapped.') }}</td></tr>
      @endforelse
    </tbody>
  </table>
  <div style="border:1px solid #8d8d8d;padding:6px 8px;margin-bottom:12px;background:#fbfbfb">
    <div style="font-weight:600;font-size:10px;text-transform:uppercase;margin-bottom:2px">{{ __('Learning Outcomes Attainment Summary:') }}</div>
    <div style="font-size:10.5px">{{ $r->c3_summary ?? __('Learning outcome attainment is evaluated as satisfactory based on verified assessment evidence. Any attainment gaps are addressed in the CQI action plan.') }}</div>
  </div>

  {{-- Stakeholder Feedback --}}
  <div class="sec-title">4. {{ __('Participant and Stakeholder Feedback') }}</div>
  @php($c4Data = json_decode($r->c4_feedback ?? '[]', true) ?: [])
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:20%">{{ __('Stakeholder Source') }}</th>
        <th style="width:22%">{{ __('Aspect Evaluated') }}</th>
        <th style="width:23%">{{ __('Key Findings') }}</th>
        <th style="width:20%">{{ __('Issues / Strengths') }}</th>
        <th style="width:15%">{{ __('Initial Action') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach(config('monitoring.feedback_sources') as $srcKey => $srcDef)
        @php($rowVal = $c4Data[$srcKey] ?? [])
        <tr>
          <td><strong>{{ __($srcDef['label']) }}</strong></td>
          <td>{{ __($srcDef['aspect']) }}</td>
          <td>
            @if($srcKey === 'learners' && $ind['feedback']['count'] > 0)
              {{ __('Avg rating:') }} {{ round(collect($ind['feedback']['areas'])->filter()->avg(), 1) }} / 5.0 ({{ $ind['feedback']['count'] }} {{ __('responses') }})
              @if(!empty($rowVal['findings'])) &middot; {{ $rowVal['findings'] }} @endif
            @else
              {{ !empty($rowVal['findings']) ? $rowVal['findings'] : '–' }}
            @endif
          </td>
          <td>{{ !empty($rowVal['issues_strengths']) ? $rowVal['issues_strengths'] : '–' }}</td>
          <td>{{ !empty($rowVal['initial_action']) ? $rowVal['initial_action'] : '–' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Implementation Quality Monitoring --}}
  <div class="sec-title">5. {{ __('Implementation Quality Domains (8 Domains)') }}</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">{{ __('No.') }}</th>
        <th style="width:40%">{{ __('Domain') }}</th>
        <th style="width:20%">{{ __('Status') }}</th>
        <th style="width:35%">{{ __('Evidence / Reference') }}</th>
      </tr>
    </thead>
    <tbody>
      @php($domIndex = 1)
      @foreach(config('monitoring.domains') as $k => $label)
        @php($dc = $domains[$k] ?? null)
        <tr>
          <td>{{ $domIndex++ }}</td>
          <td><strong>{{ __($label) }}</strong></td>
          <td>
            @if($dc)
              @if($dc->status === 'tb')
                <span class="tag info">{{ __('Not applicable') }}</span>
              @elseif($dc->status === 'memuaskan' || $dc->met)
                <span class="tag ok">{{ __('Satisfactory') }}</span>
              @else
                <span class="tag err">{{ __('Needs action') }}</span>
              @endif
            @else
              <span class="tag info">{{ __('Not checked') }}</span>
            @endif
          </td>
          <td>{{ $dc->finding ?? '–' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="page-break"></div>

  <div class="sec-title">6. {{ __('Issues, Root Cause Analysis, and Priority Level') }}</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">{{ __('No.') }}</th>
        <th style="width:25%">{{ __('Issue / Gap') }}</th>
        <th style="width:25%">{{ __('Evidence') }}</th>
        <th style="width:25%">{{ __('Root Cause') }}</th>
        <th style="width:10%">{{ __('Impact') }}</th>
        <th style="width:10%">{{ __('Priority') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($issues as $idx => $i)
        <tr>
          <td>{{ $idx + 1 }}</td>
          <td><strong>{{ $i->title ?? $i->evidence }}</strong></td>
          <td>{{ $i->evidence }}</td>
          <td>{{ $i->root_cause }}</td>
          <td>{{ $i->impact }}</td>
          <td>
            <span class="tag {{ $i->priority === 'high' ? 'err' : ($i->priority === 'medium' ? 'warn' : 'info') }}">
              {{ __(ucfirst($i->priority)) }}
            </span>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">{{ __('No issue recorded.') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- CQI Action Plan --}}
  <div class="sec-title">7. {{ __('CQI Action Plan') }}</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">{{ __('No.') }}</th>
        <th style="width:35%">{{ __('Improvement Action') }}</th>
        <th style="width:20%">{{ __('Action Owner') }}</th>
        <th style="width:12%">{{ __('Due Date') }}</th>
        <th style="width:18%">{{ __('Success Indicator') }}</th>
        <th style="width:10%">{{ __('Status') }}</th>
      </tr>
    </thead>
    <tbody>
      @php($actionCounter = 1)
      @forelse($issues as $i)
        @foreach($actions[$i->id] ?? [] as $a)
          <tr>
            <td>{{ $actionCounter++ }}</td>
            <td>
              <strong>{{ $a->description }}</strong>
              @if($a->affects)
                <div style="font-size:9.5px;color:#ba1b23;margin-top:2px">{{ __('Component change:') }} {{ __(config('proposals.change_triggers')[$a->affects] ?? $a->affects) }}</div>
              @endif
            </td>
            <td>{{ $a->responsible }}</td>
            <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
            <td>{{ $a->success_indicator }}</td>
            <td>
              <span class="tag {{ in_array($a->status, ['done', 'closed']) ? 'ok' : ($a->status === 'in_progress' ? 'warn' : 'info') }}">
                {{ __(config('monitoring.action_status')[$a->status] ?? $a->status) }}
              </span>
            </td>
          </tr>
        @endforeach
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">{{ __('No CQI action recorded.') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- CQI Effectiveness Evaluation --}}
  <div class="sec-title">8. {{ __('CQI Effectiveness Evaluation') }}</div>
  @php
    $evaluatedActions = [];
    foreach ($issues as $iss) {
      foreach ($actions[$iss->id] ?? [] as $act) {
        if ($act->effectiveness || $act->status === 'closed' || $act->status === 'carried') {
          $evaluatedActions[] = $act;
        }
      }
    }
  @endphp
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">{{ __('No.') }}</th>
        <th style="width:25%">{{ __('CQI Action') }}</th>
        <th style="width:20%">{{ __('Post-Implementation Evidence') }}</th>
        <th style="width:20%">{{ __('Before / After Comparison') }}</th>
        <th style="width:15%">{{ __('Decision') }}</th>
        <th style="width:15%">{{ __('Next Action') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($evaluatedActions as $eIdx => $a)
        <tr>
          <td>{{ $eIdx + 1 }}</td>
          <td><strong>{{ $a->description }}</strong></td>
          <td>{{ $a->after_evidence ?? '–' }}</td>
          <td>{{ __('Before:') }} {{ $a->before_evidence ?? '–' }}<br>{{ __('After:') }} {{ $a->after_evidence ?? '–' }}</td>
          <td>
            <span class="tag {{ $a->effectiveness === 'effective' ? 'ok' : ($a->effectiveness === 'partly' ? 'warn' : 'err') }}">
              {{ __(config('monitoring.effectiveness')[$a->effectiveness] ?? $a->effectiveness ?? 'Evaluated') }}
            </span>
          </td>
          <td>{{ $a->effectiveness === 'not_effective' ? __('Carried to next CQI cycle') : __('Action completed') }}</td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">{{ __('No effectiveness evaluation recorded yet.') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Offering Review Decision --}}
  <div class="sec-title">9. {{ __('Offering Review Decision') }}</div>
  @php($c9Decision = $r->c9_decision ?? 'continue_no_change')
  @php($affectedComps = json_decode($r->c9_affected_components ?? '[]', true) ?: [])
  <table class="grid-form">
    <tr>
      <td class="label">{{ __('Review Decision') }}</td>
      <td colspan="3">
        @foreach(config('monitoring.c9_decisions') as $dKey => $dLabel)
          <div style="margin-bottom:3px">
            <span class="check-box {{ $c9Decision === $dKey ? 'checked' : '' }}">{{ $c9Decision === $dKey ? '✓' : '' }}</span>
            <span style="{{ $c9Decision === $dKey ? 'font-weight:bold' : '' }}">{{ __($dLabel) }}</span>
          </div>
        @endforeach
      </td>
    </tr>
    <tr>
      <td class="label">{{ __('Material Change?') }}</td>
      <td colspan="3">
        <span class="check-box {{ $r->c9_is_material_change ? 'checked' : '' }}">{{ $r->c9_is_material_change ? '✓' : '' }}</span> <strong>{{ __('Yes') }}</strong> &nbsp;&nbsp;&nbsp;&nbsp;
        <span class="check-box {{ ! $r->c9_is_material_change ? 'checked' : '' }}">{{ ! $r->c9_is_material_change ? '✓' : '' }}</span> <strong>{{ __('No') }}</strong>
      </td>
    </tr>
    @if($r->c9_is_material_change)
    <tr>
      <td class="label">{{ __('Affected Components') }}</td>
      <td colspan="3">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px">
          @foreach(config('monitoring.c9_affected_components') as $cKey => $cLabel)
            <div>
              <span class="check-box {{ in_array($cKey, $affectedComps, true) ? 'checked' : '' }}">{{ in_array($cKey, $affectedComps, true) ? '✓' : '' }}</span>
              <span>{{ __($cLabel) }}</span>
            </div>
          @endforeach
        </div>
      </td>
    </tr>
    <tr>
      <td class="label">{{ __('Implications for Existing Learners') }}</td>
      <td colspan="3">{{ $r->c9_learner_implications ?? __('Cohort transition plan will be prepared if core curriculum amendments occur.') }}</td>
    </tr>
    @endif
    <tr>
      <td class="label">{{ __('Follow-up Actions') }}</td>
      <td colspan="3">{{ $r->c9_follow_up_actions ?? __('Execute the CQI action plan on schedule.') }}</td>
    </tr>
  </table>

  {{-- Confirmation and CQI Sign-Off --}}
  <div class="sec-title">10. {{ __('Confirmation and CQI Sign-Off (3 Roles)') }}</div>
  @php($c10Signoffs = json_decode($r->c10_signoffs ?? '[]', true) ?: [])
  <table class="data-table">
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
      {{-- Role 1: Micro-Credential Coordinator --}}
      @php($s1 = $c10Signoffs['coordinator'] ?? null)
      <tr>
        <td><strong>{{ __('Micro-Credential Coordinator') }}</strong><div style="font-size:9.5px;color:#475569">{{ __('Course Coordinator') }}</div></td>
        <td>
          <strong>{{ $s1['name'] ?? $offering->owner_name ?? '–' }}</strong><br>
          <span style="font-size:9.5px;color:#475569">{{ $s1['position'] ?? __('Course Coordinator') }}</span>
        </td>
        <td>
          @if($r->status === 'submitted' || $r->status === 'confirmed' || !empty($s1['signed_at']))
            <span class="tag ok">{{ __('Confirmed') }}</span>
          @else
            <span class="tag info">{{ __('Draft') }}</span>
          @endif
        </td>
        <td>{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : ($r->submitted_at ? \Carbon\Carbon::parse($r->submitted_at)->format('d/m/Y') : '–') }}</td>
        <td>{{ $s1['remarks'] ?? __('Report compiled from cohort evidence') }}</td>
      </tr>

      {{-- Role 2: PTj / Faculty Representative --}}
      @php($s2 = $c10Signoffs['ptj_rep'] ?? null)
      @php($confirmer = $r->confirmed_by ? \App\Models\User::find($r->confirmed_by) : null)
      <tr>
        <td><strong>{{ __('PTj / Faculty Representative') }}</strong><div style="font-size:9.5px;color:#475569">{{ __('Dean / Deputy Dean') }}</div></td>
        <td>
          <strong>{{ $s2['name'] ?? ($confirmer ? $confirmer->name : '–') }}</strong><br>
          <span style="font-size:9.5px;color:#475569">{{ $s2['position'] ?? ($confirmer ? ($confirmer->position ?? __('Deputy Dean (Academic)')) : __('Deputy Dean (Academic)')) }}</span>
        </td>
        <td>
          @if($r->status === 'confirmed' || !empty($s2['signed_at']))
            <span class="tag ok">{{ __('Confirmed') }}</span>
          @else
            <span class="tag info">{{ __('Pending Confirmation') }}</span>
          @endif
        </td>
        <td>{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : ($r->confirmed_at ? \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y') : '–') }}</td>
        <td>{{ $s2['remarks'] ?? __('Faculty-level review and approval') }}</td>
      </tr>

      {{-- Role 3: Quality Assurance / Authority --}}
      @php($s3 = $c10Signoffs['qa_authority'] ?? null)
      @php($isSamcPrint = strtolower((string) ($offering->category ?? '')) === 'samc')
      <tr>
        <td><strong>{{ __('Quality Assurance / University Authority') }}</strong><div style="font-size:9.5px;color:#475569">{{ __('QA Committee / CODL') }}</div></td>
        <td>
          <strong>{{ $s3['name'] ?? '–' }}</strong><br>
          <span style="font-size:9.5px;color:#475569">{{ __($s3['position'] ?? 'Quality Assurance Officer') }}</span>
        </td>
        <td>
          @if(!empty($s3['status']) && $s3['status'] === 'tb')
            <span class="tag info">{{ __('N/A') }}</span>
          @elseif(!empty($s3['signed_at']))
            <span class="tag ok">{{ __('Confirmed') }}</span>
          @else
            <span class="tag info">{{ $isSamcPrint ? __('Pending QA') : __('N/A') }}</span>
          @endif
        </td>
        <td>{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : '–' }}</td>
        <td>{{ !empty($s3['remarks']) ? __($s3['remarks']) : ($isSamcPrint ? __('Awaiting quality assurance sign-off') : __('Not applicable for standard cohort')) }}</td>
      </tr>
    </tbody>
  </table>

  {{-- Important Notice --}}
  <div class="nota-box">
    <b>{{ __('Important Notice:') }}</b><br>
    {{ __('CQI must be evidence-based and not limited to participant satisfaction scores. Analysis must take into account learning outcome achievement, assessment performance, participation/completion data, implementation issues, and stakeholder feedback. Material changes resulting from CQI must undergo relevant review, approval, or re-Quality Verification before implementation.') }}
  </div>
@endsection
