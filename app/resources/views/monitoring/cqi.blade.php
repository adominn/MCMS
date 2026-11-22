@extends('layouts.app')
@section('title', __('Quality review record'))
@section('content')

{{-- ===================== BREADCRUMB & HEADER ===================== --}}
<x-breadcrumb :crumbs="array_filter([
  (auth()->user()->hasAnyRole('deputy_dean') ? 'Quality review & suspensions' : 'Quality review (CQI)') => route('monitoring'),
  !empty($offering) ? ($offering->name_en . ' ' . $offering->cohort_name) : null => null,
  __('Quality review :ref', ['ref' => $r->reference]) => null,
])" />

<div class="title" style="margin-bottom:20px">
  <div>
    <h1>{{ __('Quality review :ref', ['ref' => $r->reference]) }}</h1>
    <div class="sub">
      {{ __('Continuous Quality Improvement (CQI) record for cohort performance and evaluation') }} &middot;
      <span class="muted">{{ __('Lampiran C — Garis Panduan Micro-Credential UTeM') }}</span>
    </div>
  </div>
  <div class="act" style="display:inline-flex;align-items:center;gap:10px">
    <a class="btn sec sm" href="{{ route('cqi.print', $r->id) }}" target="_blank">
      {{ __('Print Appendix C') }}
    </a>
    <x-pill :s="__(['draft' => 'Draft', 'submitted' => 'Submitted to the deputy dean', 'confirmed' => 'Confirmed'][$r->status] ?? $r->status)" id="cqi-status" />
    <a class="btn ter sm" href="{{ route('monitoring') }}">&larr; {{ __('All quality reviews') }}</a>
  </div>
</div>

{{-- ===================== C1. OFFERING INFORMATION ===================== --}}
<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>C1. {{ __('Offering information and monitoring period') }}</h2>
  </div>
  <div class="bd">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px">
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('Micro-credential') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">{{ $offering->name_en ?? __('Micro-credential') }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">
          Version {{ $offering->version_no ?? 1 }}
          @if(!empty($offering->category))
            &middot; <x-cat :c="$offering->category" />
          @endif
        </div>
      </div>
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('Cohort / Session') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">{{ $offering->cohort_name ?? __('Cohort') }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">
          @if(!empty($offering->start_date))
            {{ \Carbon\Carbon::parse($offering->start_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($offering->end_date)->format('d/m/Y') }}
          @else
            {{ ucfirst($offering->offering_status ?? 'Active') }}
          @endif
        </div>
      </div>
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('PTj / Faculty & Owner') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">{{ $offering->ptj_name ?? $offering->ptj_code ?? __('Faculty') }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">{{ $offering->owner_name ?? __('Academic Coordinator') }}</div>
      </div>
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('Participants & Review Date') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">
          {{ $enrolledCount ?? $ind['participants'] }} {{ __('registered') }} &middot; {{ $completedCount ?? 0 }} {{ __('completed') }}
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('Reviewed on:') }} {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</div>
      </div>
    </div>
  </div>
</div>

{{-- ===================== C2. COHORT PERFORMANCE & C4. STAKEHOLDER FEEDBACK ===================== --}}
<div style="margin-bottom:24px">
  <div style="margin-bottom:12px">
    <h2 style="margin:0 0 4px">C2. {{ __('Cohort performance indicators') }}</h2>
    <div class="muted s" style="font-size:13px">{{ __('Quantitative performance indicators across 6 dimensions per Guide Appendix C2.') }}</div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:20px;align-items:start">

    {{-- C2 Cohort Performance Indicators (6 indicators) --}}
    <div class="panel" style="margin:0">
      <div class="hd">
        <h3>{{ __('Cohort performance indicators (6 indicators)') }}</h3>
      </div>
      <div class="bd" style="padding:0">
        <table>
          <thead>
            <tr>
              <th>{{ __('Indicator') }}</th>
              <th style="width:25%">{{ __('Value') }}</th>
              <th style="width:25%">{{ __('Target') }}</th>
              <th style="width:20%">{{ __('Status') }}</th>
            </tr>
          </thead>
          <tbody>
          @foreach($ind['rows'] as $x)
            <tr>
              <td><b>{{ __($x['label']) }}</b></td>
              <td>
                @if($x['key'] === 'access_issues')
                  {{ $x['value'] }}
                @elseif($x['value'] === null)
                  <span class="muted s">n/a</span>
                @else
                  {{ $x['value'] }}%
                @endif
              </td>
              <td>
                @if($x['key'] === 'access_issues')
                  {{ $x['target'] }}
                @else
                  {{ $x['target'] }}%
                @endif
              </td>
              <td>
                @if($x['value'] === null)
                  <span class="tag info">{{ __('Not recorded') }}</span>
                @elseif($x['below'])
                  <span class="tag overdue">{{ $x['key'] === 'access_issues' ? __('Issues found') : __('Below target') }}</span>
                @else
                  <span class="tag ok">{{ $x['key'] === 'access_issues' ? __('None') : __('Met') }}</span>
                @endif
              </td>
            </tr>
          @endforeach
          <tr>
            <td><b>{{ __('Average & median score') }}</b></td>
            <td colspan="2">
              @if($ind['average'] !== null)
                {{ $ind['average'] }}% &middot; {{ $ind['median'] }}%
              @else
                <span class="muted s">n/a</span>
              @endif
            </td>
            <td>
              @if($ind['average'] === null)
                <span class="tag info">{{ __('Not recorded') }}</span>
              @elseif($ind['average'] >= 50)
                <span class="tag ok">{{ __('Met') }}</span>
              @else
                <span class="tag overdue">{{ __('Below target') }}</span>
              @endif
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      {{-- Edit C2 Access Issues Form --}}
      @if($isOwner && $r->status !== 'confirmed')
        <div class="bd" style="border-top:1px solid var(--border);background:var(--s1, #f4f4f4);padding:10px 14px">
          <form method="post" action="{{ route('cqi.access_issues', $r->id) }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            @csrf
            <span style="font-size:12px;font-weight:600">{{ __('Update Indicator #6 (Access issues):') }}</span>
            <select name="c2_access_issues_status" style="height:28px;font-size:12px">
              <option value="tiada" @selected(($r->c2_access_issues_status ?? 'tiada') === 'tiada')>{{ __('Tiada (None)') }}</option>
              <option value="ada" @selected(($r->c2_access_issues_status ?? '') === 'ada')>{{ __('Ada (Yes)') }}</option>
            </select>
            <input type="number" name="c2_access_issues_count" value="{{ $r->c2_access_issues_count ?? 0 }}" min="0" placeholder="{{ __('Count') }}" style="height:28px;width:70px;font-size:12px">
            <input type="text" name="c2_access_issues_notes" value="{{ $r->c2_access_issues_notes ?? '' }}" placeholder="{{ __('Remarks / technical issues notes...') }}" style="height:28px;flex:1;min-width:140px;font-size:12px">
            <button type="submit" class="btn sm">{{ __('Save') }}</button>
          </form>
        </div>
      @endif
    </div>

    {{-- C4 Student Feedback Card --}}
    <div class="panel" style="margin:0">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <h3>{{ __('Student feedback (Source 1/4)') }}</h3>
        @if(count($ind['feedback']['comments']) > 0)
          <button type="button" class="btn ter sm" data-modal="feedback-comments">
            {{ __('View all comments') }} ({{ count($ind['feedback']['comments']) }})
          </button>
        @endif
      </div>
      <div class="bd">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--border)">
          <div style="font-weight:600">{{ __('Average Rating (Scale 1 – 5)') }}</div>
          <span class="tag info">{{ $ind['feedback']['count'] }} {{ __('responses') }}</span>
        </div>

        @if($ind['feedback']['count'] > 0 && !empty($ind['feedback']['areas']))
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">
            @foreach($ind['feedback']['areas'] as $areaKey => $score)
              <div style="background:var(--s1, #f4f4f4);padding:8px 10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:12px;font-weight:500">{{ __(config('monitoring.feedback_areas')[$areaKey] ?? ucfirst($areaKey)) }}</span>
                <b style="color:var(--primary);font-size:13px">{{ $score !== null ? number_format((float)$score, 1) : '–' }}</b>
              </div>
            @endforeach
          </div>
        @else
          <p class="muted s" style="margin:0 0 12px">{{ __('No numerical feedback responses submitted yet.') }}</p>
        @endif

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <div style="font-weight:600;font-size:12px;text-transform:uppercase;color:var(--muted)">
            {{ __('Student qualitative comments') }}
          </div>
        </div>

        @php($previewComments = array_slice($ind['feedback']['comments'], 0, 2))
        @forelse($previewComments as $cmt)
          <div style="margin:6px 0;padding:8px 12px;border-left:3px solid var(--primary);background:var(--s1, #f4f4f4);font-size:13px;font-style:italic">
            &ldquo;{{ $cmt }}&rdquo;
          </div>
        @empty
          <p class="muted s" style="margin:0">{{ __('No written comments submitted for this cohort.') }}</p>
        @endforelse
      </div>
    </div>

  </div>

  {{-- C3. Learning Outcome Attainment (Full Width) --}}
  <div class="panel" style="margin-top:20px">
    <div class="hd">
      <h3>C3. {{ __('Learning outcome attainment') }}</h3>
    </div>
    <div class="bd" style="padding:0">
      <table>
        <thead>
          <tr>
            <th style="width:10%">{{ __('Code') }}</th>
            <th style="width:40%">{{ __('Learning Outcome') }}</th>
            <th style="width:25%">{{ __('Assessment Method / Evidence') }}</th>
            <th style="width:12%">{{ __('Attainment (%)') }}</th>
            <th style="width:13%">{{ __('Evaluation') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($ind['outcomes'] as $lo)
          <tr>
            <td><b>{{ $lo['code'] }}</b></td>
            <td>{{ $lo['text_ms'] ?? $lo['text'] ?? '-' }}</td>
            <td>{{ $lo['assessment_method'] ?? __('Course assessments') }}</td>
            <td><b>{{ $lo['share'] === null ? 'n/a' : $lo['share'].'%' }}</b></td>
            <td>
              @if($lo['share'] !== null && $lo['share'] < 50)
                <span class="tag overdue">{{ __('Needs CQI') }}</span>
              @elseif($lo['share'] !== null)
                <span class="tag ok">{{ __('Satisfactory') }}</span>
              @else
                <span class="muted s">n/a</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted" style="text-align:center;padding:16px">{{ __('No learning outcomes mapped.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    {{-- C3 Rumusan --}}
    <div class="bd" style="border-top:1px solid var(--border);padding:14px 16px">
      @if($isOwner && $r->status !== 'confirmed')
        <form method="post" action="{{ route('cqi.c3_summary', $r->id) }}">
          @csrf
          <label for="c3_summary" style="font-weight:600;font-size:12px;text-transform:uppercase;color:var(--ink)">
            {{ __('Rumusan Pencapaian Hasil Pembelajaran (C3 Summary):') }}
          </label>
          <div class="muted s" style="margin-bottom:6px">{{ __('State whether learning outcomes are satisfactorily achieved and identify those needing improvement actions.') }}</div>
          <textarea id="c3_summary" name="c3_summary" rows="2" style="width:100%" placeholder="{{ __('Enter LO attainment interpretation and summary...') }}">{{ $r->c3_summary ?? '' }}</textarea>
          <div style="text-align:right;margin-top:6px">
            <button type="submit" class="btn sm">{{ __('Save C3 Summary') }}</button>
          </div>
        </form>
      @else
        <div style="font-weight:600;font-size:12px;text-transform:uppercase;color:var(--muted);margin-bottom:4px">{{ __('Rumusan Pencapaian Hasil Pembelajaran (C3):') }}</div>
        <div>{{ $r->c3_summary ?? __('No overall summary recorded.') }}</div>
      @endif
    </div>
  </div>

  {{-- C4. Maklum Balas Peserta dan Pihak Berkepentingan (4 Sources) --}}
  <div class="panel" style="margin-top:20px">
    <div class="hd">
      <h3>C4. {{ __('Stakeholder feedback (4 sources: Learners, Instructors, Industry, System data)') }}</h3>
      <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Analyze qualitative and quantitative input from all 4 stakeholder groups per Guide Appendix C4.') }}</div>
    </div>
    @php($c4Feedback = json_decode($r->c4_feedback ?? '[]', true) ?: [])
    <div class="bd" style="padding:0">
      <table>
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
          @php($rowVal = $c4Feedback[$srcKey] ?? [])
          <tr>
            <td><b>{{ $srcDef['label'] }}</b></td>
            <td>{{ $srcDef['aspect'] }}</td>
            <td>
              @if($srcKey === 'learners' && $ind['feedback']['count'] > 0)
                {{ __('Avg rating:') }} {{ round(collect($ind['feedback']['areas'])->filter()->avg(), 1) }}/5.0 ({{ $ind['feedback']['count'] }} {{ __('responses') }})
                @if(!empty($rowVal['findings'])) &middot; {{ $rowVal['findings'] }} @endif
              @else
                {{ $rowVal['findings'] ?? '–' }}
              @endif
            </td>
            <td>{{ $rowVal['issues_strengths'] ?? '–' }}</td>
            <td>{{ $rowVal['initial_action'] ?? '–' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    @if($isOwner && $r->status !== 'confirmed')
      <div class="bd" style="border-top:1px solid var(--border);padding:14px 16px;background:var(--s1, #f4f4f4)">
        <h4 style="margin:0 0 10px">{{ __('Update Stakeholder Feedback (Appendix C4)') }}</h4>
        <form method="post" action="{{ route('cqi.feedback', $r->id) }}">
          @csrf
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:14px">
            @foreach(config('monitoring.feedback_sources') as $srcKey => $srcDef)
              @php($rowVal = $c4Feedback[$srcKey] ?? [])
              <div style="background:#fff;border:1px solid var(--border);padding:10px">
                <div style="font-weight:600;font-size:12px;margin-bottom:6px">{{ $srcDef['label'] }}</div>
                <div style="font-size:11px;color:var(--muted);margin-bottom:6px">{{ $srcDef['aspect'] }}</div>
                <input type="text" name="feedback[{{ $srcKey }}][findings]" value="{{ $rowVal['findings'] ?? '' }}" placeholder="{{ __('Key findings...') }}" style="width:100%;margin-bottom:6px;font-size:12px">
                <input type="text" name="feedback[{{ $srcKey }}][issues_strengths]" value="{{ $rowVal['issues_strengths'] ?? '' }}" placeholder="{{ __('Issues / strengths...') }}" style="width:100%;margin-bottom:6px;font-size:12px">
                <input type="text" name="feedback[{{ $srcKey }}][initial_action]" value="{{ $rowVal['initial_action'] ?? '' }}" placeholder="{{ __('Initial action...') }}" style="width:100%;font-size:12px">
              </div>
            @endforeach
          </div>
          <div style="text-align:right;margin-top:12px">
            <button type="submit" class="btn sm">{{ __('Save Stakeholder Feedback') }}</button>
          </div>
        </form>
      </div>
    @endif
  </div>

</div>

{{-- ===================== C5. IMPLEMENTATION QUALITY DOMAINS ===================== --}}
<form method="post" action="{{ route('cqi.domains', $r->id) }}" class="panel" id="domains" style="margin-bottom:24px">
  @csrf
  <div class="hd">
    <h2>C5. {{ __('Implementation quality domains (8 domains per Guide Appendix C5)') }}</h2>
    <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Evaluate curriculum alignment, SLT adequacy, materials, assessment validity, integrity, platform, records, and QV/credit status.') }}</div>
  </div>
  <div class="bd" style="padding:0">
    <table>
      <thead>
        <tr>
          <th style="width:5%">#</th>
          <th style="width:35%">{{ __('Domain') }}</th>
          <th style="width:25%">{{ __('Status') }}</th>
          <th style="width:35%">{{ __('Evidence / Finding') }}</th>
        </tr>
      </thead>
      <tbody>
      @php($dIndex = 1)
      @foreach(config('monitoring.domains') as $k => $label)
        @php($dc = $domains[$k] ?? null)
        <tr>
          <td>{{ $dIndex++ }}</td>
          <td><b>{{ __($label) }}</b></td>
          <td>
            @if($isOwner && $r->status === 'draft')
              <select name="status[{{ $k }}]" aria-label="Status of {{ $label }}" style="width:100%">
                <option value="">{{ __('Choose') }}</option>
                <option value="memuaskan" @selected($dc && ($dc->status === 'memuaskan' || ($dc->met && $dc->status !== 'tb')))>{{ __('Memuaskan (Satisfactory)') }}</option>
                <option value="perlu_tindakan" @selected($dc && ($dc->status === 'perlu_tindakan' || (! $dc->met && $dc->status !== 'tb')))>{{ __('Perlu Tindakan (Needs action)') }}</option>
                <option value="tb" @selected($dc && $dc->status === 'tb')>{{ __('Tidak Berkaitan (TB)') }}</option>
              </select>
            @else
              @if($dc)
                @if($dc->status === 'tb')
                  <span class="tag info">{{ __('Tidak Berkaitan (TB)') }}</span>
                @elseif($dc->status === 'memuaskan' || $dc->met)
                  <span class="tag ok">{{ __('Memuaskan (Satisfactory)') }}</span>
                @else
                  <span class="tag overdue">{{ __('Perlu Tindakan (Needs action)') }}</span>
                @endif
              @else
                <span class="muted s">{{ __('Not checked') }}</span>
              @endif
            @endif
          </td>
          <td>
            @if($isOwner && $r->status === 'draft')
              <input type="text" name="finding[{{ $k }}]" value="{{ $dc->finding ?? '' }}" aria-label="Evidence for {{ $label }}" placeholder="{{ __('Describe evidence or finding...') }}" style="width:100%">
            @else
              {{ $dc->finding ?? '–' }}
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @if($isOwner && $r->status === 'draft')
    <div class="bd" style="border-top:1px solid var(--border);text-align:right">
      <button type="submit" class="btn sm">{{ __('Save domains') }}</button>
    </div>
  @endif
</form>

{{-- ===================== C6 & C7. ISSUES, ROOT CAUSE & CQI ACTION PLAN ===================== --}}
<div style="margin-bottom:24px">
  <div style="margin-bottom:12px">
    <h2 style="margin:0 0 4px">C6 & C7. {{ __('Issues, root cause and CQI action plan') }}</h2>
    <div class="muted s" style="font-size:13px">{{ __('Traceability chain: Issue / Gap -> Evidence -> Root Cause -> Action -> Implementation -> Effectiveness Evaluation.') }}</div>
  </div>

  @forelse($issues as $i)
    <div class="panel issue card" style="margin-bottom:16px;border-left:4px solid var(--primary)">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <span class="tag {{ $i->priority === 'high' ? 'overdue' : 'info' }}" style="font-weight:600">
            {{ __(config('monitoring.priority')[$i->priority] ?? ucfirst($i->priority)) }}
          </span>
          <b style="margin-left:8px">{{ $i->title ?? $i->evidence }}</b>
        </div>
        <span class="muted s">{{ \Carbon\Carbon::parse($i->created_at)->format('d/m/Y') }}</span>
      </div>
      <div class="bd" style="padding-top:8px;padding-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;font-size:13px">
          <div><span class="muted s">{{ __('Evidens:') }}</span> <b>{{ $i->evidence }}</b></div>
          <div><span class="muted s">{{ __('Root cause:') }}</span> <b>{{ $i->root_cause }}</b></div>
          <div><span class="muted s">{{ __('Impact:') }}</span> <b>{{ $i->impact }}</b></div>
        </div>
      </div>
      <div class="bd" style="padding:0;border-top:1px solid var(--border)">
        <table>
          <thead>
            <tr>
              <th style="width:40%">{{ __('Action') }}</th>
              <th style="width:20%">{{ __('Owner') }}</th>
              <th style="width:15%">{{ __('Due') }}</th>
              <th style="width:25%">{{ __('Status & Effectiveness (C8)') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          @foreach($actions[$i->id] ?? [] as $a)
            <tr class="action" id="action-{{ $a->id }}">
              <td>
                <b>{{ $a->description }}</b>
                <div class="muted s" style="margin-top:2px">
                  {{ __('Success indicator:') }} {{ $a->success_indicator }}
                  @if($a->affects)
                    <br><span style="color:var(--err)">{{ __('Needs a change request: :trigger.', ['trigger' => __($triggers[$a->affects] ?? $a->affects)]) }}</span>
                  @endif
                  @if($a->carried_from_id)
                    <br><span style="color:var(--warn-ink)">{{ __('Carried from an earlier action.') }}</span>
                  @endif
                </div>
              </td>
              <td>{{ $a->responsible }}</td>
              <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
              <td class="st">
                <x-pill :s="__(config('monitoring.action_status')[$a->status] ?? $a->status)" />
                @if($a->effectiveness)
                  <div style="font-size:11px;margin-top:4px">
                    <b>{{ __('Effectiveness:') }}</b> {{ __(config('monitoring.effectiveness')[$a->effectiveness] ?? $a->effectiveness) }}
                  </div>
                @endif
                @if($a->before_evidence || $a->after_evidence)
                  <div style="font-size:11px;margin-top:4px;color:var(--muted)">
                    <b>{{ __('Before:') }}</b> {{ $a->before_evidence ?? '–' }} &middot; <b>{{ __('After:') }}</b> {{ $a->after_evidence ?? '–' }}
                  </div>
                @endif
              </td>
              <td style="text-align:right">
                <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                  @if($isOwner && in_array($a->status, ['not_started', 'in_progress']))
                    <form method="post" action="{{ route('cqi.status', $a->id) }}" style="display:inline;margin:0">
                      @csrf
                      <button type="submit" class="btn sm ter" aria-label="{{ $a->status === 'not_started' ? 'Start' : 'Mark done' }}: {{ $a->description }}">
                        {{ $a->status === 'not_started' ? __('Start') : __('Mark done') }}
                      </button>
                    </form>
                  @endif

                  @if($isOwner && $a->status === 'done' && ! $a->evaluated_at)
                    <form method="post" action="{{ route('cqi.evaluate', $a->id) }}" style="display:inline-flex;gap:4px;align-items:center">
                      @csrf
                      <select name="effectiveness" aria-label="Effectiveness of {{ $a->description }}" style="height:32px;font-size:12px">
                        @foreach(config('monitoring.effectiveness') as $v => $t)
                          <option value="{{ $v }}">{{ __($t) }}</option>
                        @endforeach
                      </select>
                      <input type="text" name="before_evidence" placeholder="{{ __('Before') }}" aria-label="Before evidence for {{ $a->description }}" style="height:32px;font-size:12px;width:100px" required>
                      <input type="text" name="after_evidence" placeholder="{{ __('After') }}" aria-label="After evidence for {{ $a->description }}" style="height:32px;font-size:12px;width:100px" required>
                      <button type="submit" class="btn sm" aria-label="Evaluate: {{ $a->description }}">{{ __('Evaluate') }}</button>
                    </form>
                  @endif

                  @if($isDean && $a->status === 'done')
                    <form method="post" action="{{ route('cqi.close', $a->id) }}" style="display:inline;margin:0">
                      @csrf
                      <button type="submit" class="btn sm" aria-label="Close: {{ $a->description }}">{{ __('Close action') }}</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="panel" style="margin-bottom:16px">
      <div class="bd" style="text-align:center;padding:24px;color:var(--muted)">
        {{ __('No issue recorded.') }}
      </div>
    </div>
  @endforelse

  {{-- FORM: RECORD AN ISSUE AND ACTION (WITH TITLE & SELECTABLE OWNER) --}}
  @if($isOwner && $r->status !== 'confirmed')
    <form method="post" action="{{ route('cqi.issue', $r->id) }}" class="panel card" id="new-issue" style="margin-top:20px">
      @csrf
      <div class="hd">
        <h2>{{ __('Record an issue and an action (Appendix C6 & C7)') }}</h2>
        <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Define the issue title, root cause, impact, priority, designated action owner, and success metrics.') }}</div>
      </div>
      <div class="bd">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

          {{-- Row 1: Issue Title & Evidence --}}
          <div>
            <label for="title" style="font-weight:600">{{ __('Issue Title / Summary (Isu / Jurang)') }}</label>
            <input id="title" name="title" aria-label="Issue Title" type="text" placeholder="{{ __('e.g., Module 2 Practical Mastery Gap') }}" style="width:100%">
          </div>
          <div>
            <label for="evidence" style="font-weight:600">{{ __('Evidence') }} <span style="color:var(--err)">*</span></label>
            <input id="evidence" name="evidence" aria-label="Evidence" type="text" required placeholder="{{ __('e.g., Module 2 pass rate was low (67%)') }}" style="width:100%">
          </div>

          {{-- Row 2: Root Cause & Impact --}}
          <div>
            <label for="root_cause" style="font-weight:600">{{ __('Root cause') }} <span style="color:var(--err)">*</span></label>
            <input id="root_cause" name="root_cause" aria-label="Root cause" type="text" required placeholder="{{ __('e.g., Insufficient hands-on examples in week 3') }}" style="width:100%">
          </div>
          <div>
            <label for="impact" style="font-weight:600">{{ __('Impact') }} <span style="color:var(--err)">*</span></label>
            <input id="impact" name="impact" aria-label="Impact" type="text" required placeholder="{{ __('e.g., 2 participants struggled with practical assessment') }}" style="width:100%">
          </div>

          {{-- Row 3: Priority & Action Owner --}}
          <div>
            <label for="priority" style="font-weight:600">{{ __('Priority') }} <span style="color:var(--err)">*</span></label>
            <select id="priority" name="priority" style="width:100%">
              @foreach(config('monitoring.priority') as $v => $t)
                <option value="{{ $v }}">{{ __($t) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="responsible_id" style="font-weight:600">{{ __('Action Owner (Pemilik Tindakan)') }}</label>
            <select id="responsible_id" name="responsible_id" style="width:100%">
              @foreach($ptjUsers as $u)
                <option value="{{ $u->id }}" @selected($u->id === $me)>{{ $u->name }} ({{ $u->position ?? __('Staff') }})</option>
              @endforeach
            </select>
          </div>

          {{-- Row 4: Action & Due Date --}}
          <div>
            <label for="description" style="font-weight:600">{{ __('Improvement action') }} <span style="color:var(--err)">*</span></label>
            <input id="description" name="description" type="text" required placeholder="{{ __('e.g., Add 2 worked tutorial videos and code walk-throughs') }}" style="width:100%">
          </div>
          <div>
            <label for="due_on" style="font-weight:600">{{ __('Due date') }} <span style="color:var(--err)">*</span></label>
            <input id="due_on" name="due_on" type="date" required style="width:100%">
          </div>

          {{-- Row 5 (Full Width): Success indicator --}}
          <div style="grid-column: span 2">
            <label for="success_indicator" style="font-weight:600">{{ __('Success indicator') }} <span style="color:var(--err)">*</span></label>
            <input id="success_indicator" name="success_indicator" aria-label="Success indicator" type="text" required placeholder="{{ __('e.g., Pass rate on module 2 exceeds 75% in next cohort') }}" style="width:100%">
          </div>

          {{-- Row 6 (Full Width): Change Trigger --}}
          <div style="grid-column: span 2">
            <label for="affects" style="font-weight:600">{{ __('Changes a listed component?') }}</label>
            <select id="affects" name="affects" style="width:100%">
              <option value="">{{ __('No (Minor operational adjustment)') }}</option>
              @foreach($triggers as $v => $t)
                <option value="{{ $v }}">{{ __($t) }}</option>
              @endforeach
            </select>
          </div>

        </div>
      </div>
      <div class="bd" style="border-top:1px solid var(--border);text-align:right">
        <button type="submit" class="btn sm">{{ __('Record') }}</button>
      </div>
    </form>
  @endif
</div>

{{-- ===================== C9. OFFERING REVIEW DECISION ===================== --}}
<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>C9. {{ __('Offering review decision (Keputusan Semakan Penawaran)') }}</h2>
    <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Determine whether offering continues, requires minor CQI, curriculum review, re-approval, or suspension.') }}</div>
  </div>
  @php($c9Decision = $r->c9_decision ?? 'continue_no_change')
  @php($affectedComps = json_decode($r->c9_affected_components ?? '[]', true) ?: [])
  <div class="bd">
    @if($isDean && $r->status !== 'confirmed')
      <form method="post" action="{{ route('cqi.c9_decision', $r->id) }}">
        @csrf
        <div style="margin-bottom:14px">
          <label style="font-weight:600;display:block;margin-bottom:6px">{{ __('Review Decision (Keputusan Semakan):') }}</label>
          @foreach(config('monitoring.c9_decisions') as $dKey => $dLabel)
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer">
              <input type="radio" name="c9_decision" value="{{ $dKey }}" @checked($c9Decision === $dKey) required>
              <span>{{ $dLabel }}</span>
            </label>
          @endforeach
        </div>

        <div style="margin-bottom:14px">
          <label style="font-weight:600;display:block;margin-bottom:6px">{{ __('Is there a Material Change? (Adakah terdapat Perubahan Signifikan?):') }}</label>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:20px;cursor:pointer">
            <input type="radio" name="c9_is_material_change" value="1" @checked($r->c9_is_material_change)>
            <span>{{ __('Ya (Yes)') }}</span>
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="c9_is_material_change" value="0" @checked(! $r->c9_is_material_change)>
            <span>{{ __('Tidak (No)') }}</span>
          </label>
        </div>

        <div style="margin-bottom:14px">
          <label style="font-weight:600;display:block;margin-bottom:6px">{{ __('If Yes, Affected Components (Komponen Terjejas):') }}</label>
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:6px">
            @foreach(config('monitoring.c9_affected_components') as $cKey => $cLabel)
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="checkbox" name="c9_affected_components[]" value="{{ $cKey }}" @checked(in_array($cKey, $affectedComps, true))>
                <span style="font-size:12px">{{ $cLabel }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div style="margin-bottom:14px">
          <label for="c9_learner_implications" style="font-weight:600;display:block;margin-bottom:4px">{{ __('Implications for Existing Learners (Implikasi kepada Peserta Sedia Ada):') }}</label>
          <textarea id="c9_learner_implications" name="c9_learner_implications" rows="2" style="width:100%" placeholder="{{ __('Describe learner transition arrangements if applicable...') }}">{{ $r->c9_learner_implications ?? '' }}</textarea>
        </div>

        <div style="margin-bottom:14px">
          <label for="c9_follow_up_actions" style="font-weight:600;display:block;margin-bottom:4px">{{ __('Follow-up Actions (Tindakan Susulan):') }}</label>
          <textarea id="c9_follow_up_actions" name="c9_follow_up_actions" rows="2" style="width:100%" placeholder="{{ __('Outline follow-up monitoring or approval steps...') }}">{{ $r->c9_follow_up_actions ?? '' }}</textarea>
        </div>

        <div style="text-align:right">
          <button type="submit" class="btn sm">{{ __('Save Decision (C9)') }}</button>
        </div>
      </form>
    @else
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <div class="muted s" style="font-size:12px;font-weight:600;text-transform:uppercase">{{ __('Review Decision') }}</div>
          <div style="font-weight:600;font-size:14px;margin-top:2px">{{ config('monitoring.c9_decisions')[$c9Decision] ?? $c9Decision }}</div>
          <div style="margin-top:8px">
            <span class="tag {{ $r->c9_is_material_change ? 'overdue' : 'ok' }}">
              {{ $r->c9_is_material_change ? __('Material Change: Yes') : __('Material Change: No') }}
            </span>
          </div>
        </div>
        <div>
          <div class="muted s" style="font-size:12px;font-weight:600;text-transform:uppercase">{{ __('Follow-up Actions & Implications') }}</div>
          <div style="font-size:13px;margin-top:4px">{{ $r->c9_follow_up_actions ?? __('Standard CQI implementation.') }}</div>
          @if($r->c9_is_material_change)
            <div style="font-size:12px;color:var(--muted);margin-top:4px"><b>{{ __('Implications:') }}</b> {{ $r->c9_learner_implications ?? '–' }}</div>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>

{{-- ===================== C10. CONFIRMATION AND 3-ROLE SIGN-OFF ===================== --}}
<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>C10. {{ __('Confirmation and CQI sign-off (3 roles)') }}</h2>
    <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Sign-off by MC Coordinator (P1), PTj/Faculty Representative (P2), and QA/University Authority (P4).') }}</div>
  </div>
  @php($c10Signoffs = json_decode($r->c10_signoffs ?? '[]', true) ?: [])
  <div class="bd" style="padding:0">
    <table>
      <thead>
        <tr>
          <th style="width:25%">{{ __('Role') }}</th>
          <th style="width:30%">{{ __('Name / Position') }}</th>
          <th style="width:15%">{{ __('Status') }}</th>
          <th style="width:15%">{{ __('Date') }}</th>
          <th style="width:15%">{{ __('Remarks') }}</th>
        </tr>
      </thead>
      <tbody>
        {{-- P1 Coordinator --}}
        @php($s1 = $c10Signoffs['coordinator'] ?? null)
        <tr>
          <td><b>P1. {{ __('Penyelaras Micro-Credential') }}</b></td>
          <td>
            <b>{{ $s1['name'] ?? $offering->owner_name ?? '–' }}</b><br>
            <span class="muted s">{{ $s1['position'] ?? __('Course Coordinator') }}</span>
          </td>
          <td>
            @if($r->status === 'submitted' || $r->status === 'confirmed' || !empty($s1['signed_at']))
              <span class="tag ok">{{ __('Disahkan') }}</span>
            @else
              <span class="tag info">{{ __('Draft') }}</span>
            @endif
          </td>
          <td>{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : ($r->submitted_at ? \Carbon\Carbon::parse($r->submitted_at)->format('d/m/Y') : '–') }}</td>
          <td>{{ $s1['remarks'] ?? __('Prepared from cohort evidence') }}</td>
        </tr>

        {{-- P2 Faculty Rep --}}
        @php($s2 = $c10Signoffs['ptj_rep'] ?? null)
        @php($confirmer = $r->confirmed_by ? \App\Models\User::find($r->confirmed_by) : null)
        <tr>
          <td><b>P2. {{ __('Wakil PTj / Fakulti') }}</b></td>
          <td>
            <b>{{ $s2['name'] ?? ($confirmer ? $confirmer->name : '–') }}</b><br>
            <span class="muted s">{{ $s2['position'] ?? ($confirmer ? ($confirmer->position ?? 'Timbalan Dekan') : 'Timbalan Dekan (Akademik)') }}</span>
          </td>
          <td>
            @if($r->status === 'confirmed' || !empty($s2['signed_at']))
              <span class="tag ok">{{ __('Disahkan') }}</span>
            @else
              <span class="tag info">{{ __('Awaiting Confirmation') }}</span>
            @endif
          </td>
          <td>{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : ($r->confirmed_at ? \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y') : '–') }}</td>
          <td>{{ $s2['remarks'] ?? __('Faculty academic review') }}</td>
        </tr>

        {{-- P4 QA Authority --}}
        @php($s3 = $c10Signoffs['qa_authority'] ?? null)
        <tr>
          <td><b>P4. {{ __('Pihak Jaminan Kualiti / Berkuasa') }}</b></td>
          <td>
            <b>{{ $s3['name'] ?? ($r->status === 'confirmed' ? 'Jawatankuasa Jaminan Kualiti' : '–') }}</b><br>
            <span class="muted s">{{ $s3['position'] ?? __('Pegawai Jaminan Kualiti') }}</span>
          </td>
          <td>
            @if(!empty($s3['status']) && $s3['status'] === 'tb')
              <span class="tag info">TB</span>
            @elseif($r->status === 'confirmed' || !empty($s3['signed_at']))
              <span class="tag ok">{{ __('Disahkan') }}</span>
            @else
              <span class="tag info">{{ $offering->category === 'SAMC' ? __('Awaiting QA') : 'TB' }}</span>
            @endif
          </td>
          <td>{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : ($r->confirmed_at ? \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y') : '–') }}</td>
          <td>{{ $s3['remarks'] ?? ($offering->category === 'SAMC' ? __('SAMC quality verification compliance') : __('Not applicable for standard cohort')) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

{{-- ===================== SUBMISSION & DEAN CONFIRMATION ===================== --}}
@if($isOwner && $r->status === 'draft')
  <div class="panel" style="margin-bottom:24px;border-left:4px solid var(--primary)">
    <div class="hd">
      <h2>{{ __('Submit Quality Review for Faculty Confirmation') }}</h2>
      <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Once submitted, the record is locked for review by the Deputy Dean (Academic).') }}</div>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('cqi.submit', $r->id) }}">
        @csrf
        <button type="submit" class="btn">{{ __('Submit to the deputy dean') }}</button>
      </form>
    </div>
  </div>
@endif

@if($isDean && $r->status === 'submitted')
  <form method="post" action="{{ route('cqi.confirm', $r->id) }}" class="panel card" id="confirm" style="margin-bottom:24px;border-left:4px solid #24a148">
    @csrf
    <div class="hd">
      <h2>{{ __('Confirm the quality review record (Appendix C10)') }}</h2>
      <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Verify that quality domains and improvement actions meet faculty academic governance standards.') }}</div>
    </div>
    <div class="bd">
      @foreach(config('monitoring.confirmations') as $k => $t)
        <label style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;cursor:pointer">
          <input type="checkbox" name="confirm[{{ $k }}]" value="1" style="margin-top:3px">
          <span>{{ __($t) }}</span>
        </label>
      @endforeach
      <div style="margin-top:12px">
        <label for="remarks" style="font-weight:600;font-size:12px">{{ __('Dean Confirmation Remarks:') }}</label>
        <input type="text" id="remarks" name="remarks" placeholder="{{ __('Optional remarks on faculty endorsement...') }}" style="width:100%;margin-top:4px">
      </div>
    </div>
    <div class="bd" style="border-top:1px solid var(--border)">
      <button type="submit" class="btn sm">{{ __('Confirm') }}</button>
    </div>
  </form>
@endif

{{-- ===================== NOTA PENTING (LAMPIRAN C) ===================== --}}
<div class="panel" style="margin-top:24px;background:#edf5ff;border:1.5px solid #0f62fe">
  <div class="bd" style="padding:16px 20px">
    <div style="font-weight:700;font-size:12px;color:#0043ce;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">
      {{ __('NOTA PENTING (Lampiran C)') }}
    </div>
    <p style="margin:0;font-size:13px;line-height:1.5;color:#161616">
      {{ __('CQI hendaklah berasaskan evidens dan tidak terhad kepada skor kepuasan peserta. Analisis hendaklah mengambil kira pencapaian hasil pembelajaran, prestasi pentaksiran, data penyertaan/penyempurnaan, isu pelaksanaan dan maklum balas pihak berkepentingan. Perubahan signifikan yang terhasil daripada CQI hendaklah melalui mekanisme semakan, kelulusan atau Quality Verification semula yang berkaitan sebelum dilaksanakan.') }}
    </p>
  </div>
</div>

{{-- ===================== STUDENT FEEDBACK COMMENTS MODAL ===================== --}}
<div class="scrim" id="modal-feedback-comments" hidden style="display:none;position:fixed;inset:0;background:rgba(22,22,22,.5);z-index:70;align-items:center;justify-content:center">
  <div class="modal" role="dialog" aria-modal="true" aria-label="{{ __('Student feedback comments') }}" style="max-width:640px;width:90%;max-height:85vh;display:flex;flex-direction:column;background:var(--card-bg);border:1px solid var(--hairline);border-radius:0;box-shadow:0 4px 20px rgba(0,0,0,.15)">
    <div class="mh" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border)">
      <div>
        <h2 style="margin:0;font-size:18px">{{ __('Student feedback comments') }}</h2>
        <div class="muted s" style="font-size:12px">{{ count($ind['feedback']['comments']) }} {{ __('comments submitted for this cohort') }}</div>
      </div>
      <button type="button" class="btn ghost sm" data-close aria-label="Close" style="font-size:20px;cursor:pointer">&times;</button>
    </div>
    <div class="mb" style="overflow-y:auto;flex:1;padding:20px">
      @forelse($ind['feedback']['comments'] as $cmt)
        <div style="margin-bottom:12px;padding:12px 16px;border-left:3px solid var(--primary);background:var(--s1, #f4f4f4);font-size:13px;line-height:1.5">
          &ldquo;{{ $cmt }}&rdquo;
        </div>
      @empty
        <p class="muted" style="text-align:center;padding:24px">{{ __('No written comments submitted for this cohort.') }}</p>
      @endforelse
    </div>
    <div class="mf" style="display:flex;justify-content:flex-end;padding:12px 20px;border-top:1px solid var(--border);background:var(--s1, #f4f4f4)">
      <button type="button" class="btn ter sm" data-close>{{ __('Close') }}</button>
    </div>
  </div>
</div>

@endsection
