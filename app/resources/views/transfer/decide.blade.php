@extends('layouts.app')
@section('title', __('Transfer Decision').' '.$a->reference)
@section('content')
@php
  $fmtNum = fn ($v) => mcms_num($v);
  $decided = in_array($a->status, ['approved', 'partly', 'not_approved'], true);
  $resultLabels = config('transfer.result_labels');
  $okResults = ['equivalent', 'adequate', 'matched', 'aligned', 'valid', 'current', 'met', 'pass', 'suitable', 'sufficient'];
  $warnResults = ['partly', 'needs_review'];
  $lifeStage = $decided ? 8 : 7;
  $authRole = (string) app(\App\Modules\Core\Settings::class)->get('approval.route.transfer');
  $canDecide = in_array($authRole, ['codl_admin', 'academic_authority', ''], true)
    ? auth()->user()->hasAnyRole('codl_admin', 'academic_authority')
    : auth()->user()->hasAnyRole($authRole);
  $isDd = ! $canDecide && auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority');
  $canAdmit = auth()->user()->hasAnyRole('codl_admin', 'academic_authority', 'records_custodian', 'records_depositor');

  $totalRequested = (float) $lines->sum('credits_requested');
  $evaluatedCount = $lines->filter(fn ($l) => ! empty($l->lo))->count();

  // Auto-open the tab matching the application's current stage
  $activeTab = match ($a->status) {
    'submitted' => 'evidence',
    'under_eval' => 'evaluation',
    'approved', 'partly' => 'admission',
    default => 'decision',
  };
@endphp

<x-breadcrumb :crumbs="[
  'Credit transfer' => route('transfers'),
  $a->reference => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('Transfer Decision') }} {{ $a->reference }}</h1>
    <div class="sub">{{ $a->user_name }} (<code>{{ $a->institution_id }}</code>) &middot; {{ $a->programme }} {{ $a->programme_name }}</div>
  </div>
  <div class="act" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <a class="btn ter sm" href="{{ route('transfers') }}">&larr; {{ __('All evaluations') }}</a>
    @if($decided)
      <a class="btn ter sm" href="{{ route('transfers.letter', $a->id) }}?print=1" target="_blank">{{ __('Decision Letter (A4)') }}</a>
    @endif
    <span class="tag {{ in_array($a->status, ['approved', 'partly'], true) ? 'ok' : ($a->status === 'not_approved' ? 'err' : 'warn') }}">
      <strong id="app-status">{{ $status[$a->status] ?? ucfirst($a->status) }}</strong>
    </span>
  </div>
</div>

@if($isDd)
  <div class="note info" style="margin-bottom:16px">
    <p style="margin:0">
      <b>{{ __('Faculty Programme Transfer Monitor (Read-Only):') }}</b>
      {{ __('This application is evaluated by CODL and approved by Senate. You are monitoring credit transfers into your faculty programmes.') }}
    </p>
  </div>
@endif

<x-credit-lifecycle :stage="$lifeStage" :blocked="$a->status === 'more_evidence'" :blockedLabel="__('Waiting for evidence')" :note="$a->reference" />

{{-- Compact Summary KPIs (Always Visible) --}}
<div class="kpis" style="margin-bottom:20px">
  <div class="kpi">
    <div class="l">{{ __('Credits Requested') }}</div>
    <div class="v">{{ $fmtNum($totalRequested) }} cr</div>
    <div class="n">{{ $lines->count() === 1 ? __('1 course line') : __(':count course lines', ['count' => $lines->count()]) }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Record Validity') }}</div>
    <div class="v">{{ $validity ? __('Verified') : __('Pending') }}</div>
    <div class="n">{{ $validity ? __('Step 1 complete') : __('Awaiting validity check') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Courses Evaluated') }}</div>
    <div class="v">{{ $evaluatedCount }} / {{ $lines->count() }}</div>
    <div class="n">{{ $evaluatedCount === $lines->count() ? __('All lines evaluated') : __('Evaluation in progress') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Programme Admission') }}</div>
    <div class="v">{{ $admitted ? $admitted->intake : __('Not recorded') }}</div>
    <div class="n">{{ $admitted ? __('Admission verified') : __('Separate fact from transfer') }}</div>
  </div>
</div>

{{-- Collapsible Courses Summary (Always Accessible from Any Tab without Clutter — Rule D-67) --}}
<details class="panel" style="margin-bottom:18px">
  <summary class="hd" style="cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <strong style="font-size:13.5px">{{ __('Courses in this Application') }}</strong>
      <span class="tag info" style="font-size:11px">{{ $lines->count() }} {{ $lines->count() === 1 ? __('course') : __('courses') }} &middot; {{ $fmtNum($totalRequested) }} cr</span>
      <span class="s muted" style="font-family:var(--mono)">{{ $lines->pluck('course_code')->implode(', ') }}</span>
    </div>
    <span class="s" style="color:var(--color-primary, #0052ff);font-weight:600">{{ __('View subjects') }}</span>
  </summary>
  <div class="table-wrap">
    <table data-no-search data-no-paginate style="margin:0">
      <thead>
        <tr>
          <th>{{ __('Target Course') }}</th>
          <th>{{ __('Source Micro-Credential & ACR') }}</th>
          <th style="text-align:right">{{ __('Credits') }}</th>
          <th>{{ __('Evaluation') }}</th>
          <th>{{ __('ABC Status') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($lines as $l)
          <tr>
            <td><strong style="font-family:var(--mono)">{{ $l->course_code }}</strong> {{ $l->course_name }}</td>
            <td>{{ $l->source_name }} <div class="s muted"><code>{{ $l->acr_reference }}</code>@if($l->acr_mqf_level) &middot; MQF {{ $l->acr_mqf_level }}@endif</div></td>
            <td style="text-align:right;font-weight:600">{{ $fmtNum($l->credits_approved ?: $l->credits_requested) }} / {{ $fmtNum($l->course_credit) }} cr</td>
            <td>
              <span class="tag {{ $l->complete ? 'ok' : 'warn' }}" style="font-size:11px">{{ $l->complete ? __('Evaluated') : __('Pending') }}</span>
            </td>
            <td>
              <span class="tag {{ $l->credit_status === 'redeemed' ? 'ok' : ($l->credit_status === 'provisional' ? 'warn' : 'info') }}" style="font-size:11px">{{ ucfirst((string) $l->credit_status) }}</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</details>

{{-- 4-Tab Workflow (Rule D-67 Compliant — Auto-opens Active Stage Tab) --}}
<div data-tabs data-default-tab="{{ $activeTab }}">
  <nav class="tabs" aria-label="{{ __('Transfer evaluation stages') }}" style="margin-bottom:18px;overflow-x:auto">
    <button data-tab="evidence" class="{{ $activeTab === 'evidence' ? 'on' : '' }}">
      {{ __('1. Evidence & Validity') }}
      <span class="tag {{ $validity ? 'ok' : 'warn' }}" style="margin-left:6px;font-size:10px">{{ $validity ? __('Done') : __('Pending') }}</span>
    </button>
    <button data-tab="evaluation" class="{{ $activeTab === 'evaluation' ? 'on' : '' }}">
      {{ __('2. Course Evaluation') }}
      <span class="tag {{ $evaluatedCount === $lines->count() && $lines->count() > 0 ? 'ok' : 'info' }}" style="margin-left:6px;font-size:10px">{{ $evaluatedCount }}/{{ $lines->count() }}</span>
    </button>
    <button data-tab="decision" class="{{ $activeTab === 'decision' ? 'on' : '' }}">
      {{ __('3. Decision') }}
      <span class="tag {{ $decided ? 'ok' : ($a->status === 'under_eval' && $evaluatedCount === $lines->count() ? 'info' : 'neutral') }}" style="margin-left:6px;font-size:10px">{{ $decided ? __('Recorded') : ($a->status === 'under_eval' && $evaluatedCount === $lines->count() ? __('Ready') : __('Pending')) }}</span>
    </button>
    <button data-tab="admission" class="{{ $activeTab === 'admission' ? 'on' : '' }}">
      {{ __('4. Admission & Redemption') }}
      <span class="tag {{ $admitted ? 'ok' : 'neutral' }}" style="margin-left:6px;font-size:10px">{{ $admitted ? __('Admitted') : __('Not recorded') }}</span>
    </button>
  </nav>

  {{-- TAB 1: Evidence & Validity --}}
  <div data-pane="evidence" @if($activeTab !== 'evidence') hidden @endif>
    <x-transfer-evidence :a="$a" :lines="$lines" routeName="transfers.document" idPrefix="decide" />

    <div class="card panel" id="validity" style="margin-bottom:20px">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0;font-size:15px">{{ __('Record Validity Check') }}</h2>
        @if($validity)
          <span class="tag ok">{{ __('Verified') }}</span>
        @else
          <span class="tag warn">{{ __('Pending check') }}</span>
        @endif
      </div>
      <div class="bd" style="padding:16px 20px">
        @if($validity)
          <table data-no-search data-no-paginate style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Validity Criterion') }}</th>
                <th style="width:120px">{{ __('Result') }}</th>
                <th>{{ __('Note') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach(config('transfer.validity') as $k => $label)
                <tr>
                  <td><strong>{{ __($label) }}</strong></td>
                  <td>
                    <span class="tag {{ ($validity[$k]['result'] ?? '') === 'pass' ? 'ok' : 'err' }}">
                      {{ ($validity[$k]['result'] ?? '') === 'pass' ? __('Passed') : __('Failed') }}
                    </span>
                  </td>
                  <td class="s muted">{{ !empty($validity[$k]['note']) ? $validity[$k]['note'] : '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @elseif($a->status === 'submitted')
          @if($canDecide)
            <form method="post" action="{{ route('transfers.validity', $a->id) }}">
              @csrf
              <table data-no-search data-no-paginate style="margin:0 0 14px">
                <thead>
                  <tr>
                    <th style="width:36%">{{ __('Validity Criterion') }}</th>
                    <th style="width:24%">{{ __('Result') }}</th>
                    <th>{{ __('Optional Note') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach(config('transfer.validity') as $k => $label)
                    <tr>
                      <td><strong>{{ __($label) }}</strong></td>
                      <td>
                        <div style="display:inline-flex;gap:14px;align-items:center">
                          <label style="display:inline-flex;gap:5px;align-items:center;cursor:pointer">
                            <input type="radio" name="check[{{ $k }}]" value="pass" required> {{ __('Passed') }}
                          </label>
                          <label style="display:inline-flex;gap:5px;align-items:center;cursor:pointer">
                            <input type="radio" name="check[{{ $k }}]" value="fail"> {{ __('Failed') }}
                          </label>
                        </div>
                      </td>
                      <td>
                        <input type="text" name="note[{{ $k }}]" placeholder="{{ __('Optional note') }}" aria-label="Note for {{ $label }}" style="width:100%;font-size:12.5px">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
              <p class="muted small" style="margin:0 0 12px">{{ __('A failed check ends the application as Not approved. The credit and records stay unchanged.') }}</p>
              <button type="submit" class="btn primary sm">{{ __('Record validity check') }}</button>
            </form>
          @else
            <p class="muted" style="margin:0"><span class="tag warn">{{ __('Pending validity check by CODL') }}</span></p>
          @endif
        @else
          <span class="not-recorded">{{ __('Not yet recorded') }}</span>
        @endif
      </div>
    </div>
  </div>

  {{-- TAB 2: Course Evaluation --}}
  <div data-pane="evaluation" @if($activeTab !== 'evaluation') hidden @endif>
    @if(count($lines) > 1)
      <div class="row" style="align-items:center;justify-content:space-between;margin:0 0 14px;background:var(--color-canvas-subtle, #f8fafc);padding:10px 16px;border:1px solid var(--color-card-border, #090a0f);border-radius:var(--radius-sm, 4px)">
        <label for="line-switcher" style="font-weight:600;display:inline-flex;align-items:center;gap:8px;margin:0;color:var(--color-ink, #090a0f)">
          <span>{{ __('Transfer line:') }}</span>
          <select id="line-switcher" class="dropdown-switcher" data-switch="transfer-line" style="min-width:320px">
            @foreach($lines as $l)
              <option value="{{ $l->id }}">{{ $l->course_code }} {{ $l->course_name }} ({{ $fmtNum($l->credits_requested) }} cr) &middot; {{ $l->complete ? __('Evaluated') : __('Pending') }}</option>
            @endforeach
          </select>
        </label>
        <span class="s muted">{{ $lines->where('complete', true)->count() }} / {{ count($lines) }} {{ __('evaluated') }}</span>
      </div>
    @endif

    @foreach($lines as $l)
      @php
        $auto = $l->auto ?? null;
        $autoSummary = $auto['summary'] ?? null;
        $autoCrit = $auto['criteria'] ?? [];
        $autoLo = $auto['outcomes'] ?? [];
      @endphp
      <div @if(count($lines) > 1) data-case="transfer-line" data-id="{{ $l->id }}" @endif class="card line section-card panel" id="line-{{ $l->id }}" style="margin-bottom:20px">
        <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <h2 style="margin:0;font-size:15px">{{ $l->course_code }} {{ $l->course_name }}</h2>
            <span class="tag {{ $l->complete ? 'ok' : 'warn' }}">{{ $l->complete ? __('Evaluated') : __('Pending') }}</span>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            @if($l->reevaluate)
              <span class="tag err">{{ __('Needs re-evaluation') }}</span>
            @endif
            <span class="tag info">{{ $fmtNum($l->credits_requested) }} / {{ $fmtNum($l->course_credit) }} {{ __('credits') }}</span>
          </div>
        </div>
        <div class="bd" style="padding:16px 20px">
          <p class="s" style="margin:0 0 12px;color:var(--color-ink-muted, #475569)">
            {{ __('Credit used:') }} <strong style="color:var(--color-ink, #090a0f)">{{ $l->source_name }}</strong>
            (<code>{{ $l->acr_reference }}</code>@if($l->acr_slt_hours), {{ $l->acr_slt_hours }} {{ __('SLT hrs') }}@endif @if($l->acr_mqf_level), MQF {{ $l->acr_mqf_level }}@endif) &middot;
            {{ __('Requested :req of :total credits', ['req' => $fmtNum($l->credits_requested), 'total' => $fmtNum($l->course_credit)]) }}
          </p>

          @if($a->status === 'under_eval' && $canDecide && $autoSummary)
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:10px 14px;margin-bottom:16px;border:1px solid var(--color-card-border, #090a0f);background:var(--color-canvas-subtle, #f8fafc);border-radius:var(--radius-sm, 4px)">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12.5px">
                <span class="tag {{ $autoSummary['flagged'] > 0 ? 'warn' : 'ok' }}" style="font-weight:700">
                  {{ __('System Pre-Check:') }} {{ $autoSummary['passed'] }} / {{ $autoSummary['total'] }} {{ __('passed') }}
                </span>
                @if($autoSummary['is_direct_umc'])
                  <span class="tag info">{{ __('Direct UMC Match') }}</span>
                @elseif($autoSummary['has_approved_equiv'])
                  <span class="tag info">{{ __('Approved Equivalence') }}</span>
                @endif
                @if(!empty($autoSummary['flags']))
                  <span class="s" style="color:#b91c1c;font-weight:600">{{ __('Flagged:') }} {{ implode(' · ', $autoSummary['flags']) }}</span>
                @else
                  <span class="s muted">{{ __('All system-verifiable criteria pre-filled. Review and confirm or override any row below.') }}</span>
                @endif
              </div>
            </div>
          @endif

          @if($a->status === 'under_eval' && $canDecide)
            <form method="post" action="{{ route('transfers.evaluate', [$a->id, $l->id]) }}">
              @csrf
              <h3 style="margin:0 0 8px;font-size:14px">{{ __('Learning Outcomes Comparison') }}</h3>
              <div class="table-wrap" style="margin-bottom:16px">
                <table data-no-search data-no-paginate style="margin:0;min-width:640px">
                  <thead>
                    <tr>
                      <th style="min-width:170px">{{ __('Outcome of the course') }}</th>
                      <th style="min-width:230px">{{ __('Micro-credential outcome') }}</th>
                      <th style="width:160px">{{ __('Match') }}</th>
                      <th style="min-width:160px">{{ __('Note') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($l->targets as $i => $t)
                      @php
                        $as = $l->assessments[$t] ?? null;
                        $sugLo = $autoLo[$t] ?? null;
                        $selSource = $as ? ($as->source_outcome_id ?? null) : ($sugLo['source_outcome_id'] ?? null);
                        $selMatch = $as ? ($as->match ?? null) : ($sugLo['match'] ?? null);
                        $selNote = $as ? ($as->note ?? '') : ($sugLo['note'] ?? '');
                      @endphp
                      <tr>
                        <td><b>{{ $t }}</b></td>
                        <td>
                          <select name="source[{{ $i }}]" aria-label="Outcome meeting: {{ $t }}" style="width:100%">
                            <option value="">{{ __('None') }}</option>
                            @foreach($l->outcomes as $o)
                              <option value="{{ $o->id }}" @selected($selSource == $o->id)>{{ $o->code }} {{ $o->text_en }}</option>
                            @endforeach
                          </select>
                        </td>
                        <td>
                          <select name="match[{{ $i }}]" aria-label="Match for: {{ $t }}" style="width:100%">
                            <option value="">{{ __('Choose') }}</option>
                            @foreach(config('transfer.match') as $v => $tt)
                              <option value="{{ $v }}" @selected($selMatch === $v)>{{ __($tt) }}</option>
                            @endforeach
                          </select>
                        </td>
                        <td>
                          <input type="text" name="note[{{ $i }}]" value="{{ $selNote }}" aria-label="Note for: {{ $t }}" placeholder="{{ __('Evaluation note') }}" style="width:100%">
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              <h3 style="margin:0 0 8px;font-size:14px">{{ __('Course Equivalence Criteria') }}</h3>
              <div class="table-wrap" style="margin-bottom:16px">
                <table data-no-search data-no-paginate style="margin:0;min-width:540px">
                  <thead>
                    <tr>
                      <th style="min-width:210px">{{ __('Criterion') }}</th>
                      <th style="min-width:240px">{{ __('Finding & Evidence') }}</th>
                      <th style="width:190px">{{ __('Result') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach(config('transfer.criteria') as $k => $label)
                      @continue($k === 'outcomes')
                      @php
                        $cr = $l->criteria[$k] ?? null;
                        $sugCr = $autoCrit[$k] ?? null;
                        $opts = config("transfer.criteria_options.$k", config('transfer.results'));
                        $curFinding = $cr ? ($cr->finding ?? '') : ($sugCr['finding'] ?? '');
                        $curResult = $cr ? ($cr->result ?? null) : ($sugCr['result'] ?? null);
                        $sugStatus = $sugCr['status'] ?? null;
                      @endphp
                      <tr @if(!$cr && $sugStatus === 'flagged') style="background:#fef2f2" @endif>
                        <td>
                          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                            <span>{{ __($label) }}</span>
                            @if(!$cr && $sugStatus === 'ok')
                              <span class="tag ok" style="font-size:10px">{{ __('Auto') }}</span>
                            @elseif(!$cr && $sugStatus === 'flagged')
                              <span class="tag err" style="font-size:10px">{{ __('Flagged') }}</span>
                            @elseif(!$cr && $sugStatus === 'review')
                              <span class="tag warn" style="font-size:10px">{{ __('Review') }}</span>
                            @endif
                          </div>
                        </td>
                        <td>
                          <input type="text" name="finding[{{ $k }}]" value="{{ $curFinding }}" aria-label="Finding for {{ $label }}" placeholder="{{ __('Evidence checked') }}" style="width:100%">
                        </td>
                        <td>
                          <select name="result[{{ $k }}]" aria-label="Result for {{ $label }}" style="width:100%">
                            <option value="">{{ __('Choose') }}</option>
                            @foreach($opts as $v => $tt)
                              <option value="{{ $v }}" @selected($curResult === $v)>{{ __($tt) }}</option>
                            @endforeach
                            @if($cr && $cr->result && !array_key_exists($cr->result, $opts))
                              <option value="{{ $cr->result }}" selected>{{ $resultLabels[$cr->result] ?? ucfirst($cr->result) }}</option>
                            @endif
                          </select>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <button type="submit" class="btn primary sm">{{ $l->complete ? __('Update evaluation') : __('Confirm & save evaluation') }}</button>
            </form>
          @else
            <h3 style="margin:0 0 8px;font-size:14px">{{ __('Learning Outcomes Comparison') }}</h3>
            <div class="table-wrap" style="margin-bottom:16px">
              <table data-no-search data-no-paginate style="margin:0;min-width:620px">
                <thead>
                  <tr>
                    <th style="min-width:180px">{{ __('Outcome of the course') }}</th>
                    <th style="min-width:220px">{{ __('Micro-credential outcome') }}</th>
                    <th style="width:140px">{{ __('Match') }}</th>
                    <th style="min-width:160px">{{ __('Note') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($l->targets as $t)
                    @php
                      $as = $l->assessments[$t] ?? null;
                      $matchedOutcome = $as && $as->source_outcome_id ? $l->outcomes->firstWhere('id', $as->source_outcome_id) : null;
                    @endphp
                    <tr>
                      <td><b>{{ $t }}</b></td>
                      <td>
                        @if($matchedOutcome)
                          {{ $matchedOutcome->code }} {{ $matchedOutcome->text_en }}
                        @elseif($as && $as->match === 'none')
                          <span class="muted">{{ __('None matched') }}</span>
                        @else
                          <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                        @endif
                      </td>
                      <td>
                        @if(!empty($as->match))
                          <span class="tag {{ in_array($as->match, ['full', 'direct'], true) ? 'ok' : ($as->match === 'partial' ? 'warn' : 'err') }}">
                            {{ __(config('transfer.match')[$as->match] ?? ucfirst($as->match)) }}
                          </span>
                        @else
                          <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                        @endif
                      </td>
                      <td>@if(!empty($as->note)){{ $as->note }}@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <h3 style="margin:0 0 8px;font-size:14px">{{ __('Course Equivalence Criteria Findings') }}</h3>
            <div class="table-wrap">
              <table data-no-search data-no-paginate style="margin:0;min-width:540px">
                <thead>
                  <tr>
                    <th style="min-width:200px">{{ __('Criterion') }}</th>
                    <th style="min-width:220px">{{ __('Finding & Evidence Checked') }}</th>
                    <th style="width:160px">{{ __('Result') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach(config('transfer.criteria') as $k => $label)
                    @continue($k === 'outcomes')
                    @php
                      $cr = $l->criteria[$k] ?? null;
                    @endphp
                    <tr>
                      <td><b>{{ __($label) }}</b></td>
                      <td>@if($cr && $cr->finding !== ''){{ $cr->finding }}@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
                      <td>
                        @if(!empty($cr->result))
                          <span class="tag {{ in_array($cr->result, $okResults, true) ? 'ok' : (in_array($cr->result, $warnResults, true) ? 'warn' : ($cr->result === 'na' ? '' : 'err')) }}">
                            {{ __($resultLabels[$cr->result] ?? ucfirst($cr->result)) }}
                          </span>
                        @else
                          <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif

          @if($l->lo)
            <div class="note {{ $l->lo === 'equivalent' ? 'ok' : ($l->lo === 'partly' ? 'warn' : 'err') }}" style="margin-top:14px">
              <p class="line-lo" style="margin:0">{{ __('Learning-outcome criterion:') }} <strong>{{ __(config('transfer.results')[$l->lo] ?? $l->lo) }}</strong>@if($l->lo === 'not_equivalent') ({{ __('Approved is blocked for this course') }})@endif</p>
            </div>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  {{-- TAB 3: Decision & More Evidence --}}
  <div data-pane="decision" @if($activeTab !== 'decision') hidden @endif>
    @if($a->status === 'under_eval' && $evaluatedCount < $lines->count())
      <div class="note warn" style="margin-bottom:16px">
        <p style="margin:0">
          <b>{{ __('Course evaluation incomplete:') }}</b>
          {{ __(':done of :total courses have been evaluated in Step 2. Complete all course evaluations before recording a final Approved decision.', ['done' => $evaluatedCount, 'total' => $lines->count()]) }}
        </p>
      </div>
    @endif
    @if($warnings)
      <div class="card panel" id="warnings" style="margin-bottom:20px;border-left:4px solid var(--color-warning, #b28900)">
        <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Governance & Credit-Limit Warnings') }}</h2></div>
        <div class="bd" style="padding:14px 20px">
          <ul style="margin:0;padding-left:18px">
            @foreach($warnings as $w)
              <li style="margin-bottom:4px">{{ $w }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    @if($a->status === 'under_eval')
      @if($canDecide)
        <form method="post" action="{{ route('transfers.decide', $a->id) }}" class="card panel" id="decide" style="margin-bottom:20px">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Record Credit Transfer Decision') }}</h2></div>
          <div class="bd" style="padding:16px 20px">
            @csrf
            <div class="table-wrap" style="margin-bottom:16px">
              <table data-no-search data-no-paginate style="margin:0;min-width:460px">
                <thead>
                  <tr>
                    <th>{{ __('Course') }}</th>
                    <th style="width:220px">{{ __('Credits approved (0 to requested)') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($lines as $l)
                    <tr>
                      <td><b>{{ $l->course_code }}</b> {{ $l->course_name }}</td>
                      <td>
                        <input type="number" step="0.5" min="0" max="{{ $l->credits_requested }}" name="credits[{{ $l->id }}]" value="{{ $l->credits_requested }}" aria-label="Credits approved for {{ $l->course_code }}" style="width:120px">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:14px">
              <div>
                <label for="outcome">{{ __('Decision') }} *</label>
                <select id="outcome" name="outcome" style="width:100%">
                  <option value="approved">{{ __('Approved') }}</option>
                  <option value="partly">{{ __('Partly approved') }}</option>
                  <option value="not_approved">{{ __('Not approved') }}</option>
                </select>
              </div>
              <div>
                <label for="decision_ref">{{ __('Decision reference') }}</label>
                <input id="decision_ref" name="decision_ref" type="text" placeholder="e.g. SENATE/2026/TR-06" value="{{ old('decision_ref') }}" style="width:100%">
              </div>
              <div>
                <label for="decided_on">{{ __('Decision date') }} *</label>
                <input id="decided_on" name="decided_on" type="date" value="{{ old('decided_on', today()->toDateString()) }}" required style="width:100%">
              </div>
            </div>

            <div style="margin-bottom:16px">
              <label for="conditions">{{ __('Conditions or justification') }} <span class="muted">({{ __('Required if Partly approved or Not approved') }})</span></label>
              <textarea id="conditions" name="conditions" rows="2" style="width:100%" placeholder="{{ __('Required if not approved or partly approved') }}">{{ old('conditions') }}</textarea>
            </div>

            <p class="muted small" style="margin:0 0 12px">{{ __('Approving holds the credit used as Provisionally redeemed. It does not admit the participant.') }}</p>
            <button type="submit" class="btn primary">{{ __('Record decision') }}</button>
          </div>
        </form>
      @else
        <div class="card panel" style="margin-bottom:20px">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Record Credit Transfer Decision') }}</h2></div>
          <div class="bd" style="padding:16px 20px">
            <span class="tag warn">{{ __('Pending final Senate decision recorded by CODL Administrator') }}</span>
          </div>
        </div>
      @endif
    @endif

    @if(in_array($a->status, ['submitted', 'under_eval'], true) && $canDecide)
      <details class="panel" style="margin-bottom:20px">
        <summary class="hd" style="cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center">
          <h2 style="margin:0;font-size:14px">{{ __('Request More Evidence from Applicant') }}</h2>
          <span class="s muted">{{ __('Expand') }}</span>
        </summary>
        <form method="post" action="{{ route('transfers.more', $a->id) }}" class="bd" style="padding:16px 20px">
          @csrf
          <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:260px">
              <label for="request">{{ __('Additional evidence required') }}</label>
              <input id="request" name="request" type="text" placeholder="{{ __('Detail what additional evidence is required from applicant') }}" required style="width:100%">
            </div>
            <button type="submit" class="btn ter sm">{{ __('Request more evidence') }}</button>
          </div>
        </form>
      </details>
    @endif

    @if($decision && $decision->outcome !== 'more_evidence')
      <div class="card panel" id="decision" style="margin-bottom:20px">
        <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
          <h2 style="margin:0;font-size:15px">{{ __('Decision Recorded') }}</h2>
          <span class="tag {{ $decision->outcome === 'approved' ? 'ok' : ($decision->outcome === 'partly' ? 'warn' : 'err') }}">{{ __($status[$decision->outcome] ?? $decision->outcome) }}</span>
        </div>
        <div class="bd" style="padding:16px 20px">
          <p style="margin-top:0">
            <b>{{ __($status[$decision->outcome] ?? $decision->outcome) }}</b>
            {{ __('on :date', ['date' => \Carbon\Carbon::parse($decision->decided_on)->format('d/m/Y')]) }}
            {{ __('by') }} <b>{{ $decision->decided_by_name ?: ($decision->actor_name ?: __('Not yet recorded')) }}</b>
            @if($decision->decided_by_position || $decision->actor_position)({{ $decision->decided_by_position ?: $decision->actor_position }})@endif
            @if($decision->decision_ref), {{ __('reference') }} <code>{{ $decision->decision_ref }}</code>@endif.
          </p>
          @if($decision->conditions)<p class="muted" style="margin-bottom:12px"><b>{{ __('Conditions / Justification:') }}</b> {{ $decision->conditions }}</p>@endif
          <a class="btn ter sm" href="{{ route('transfers.letter', $a->id) }}?print=1" target="_blank">{{ __('Print Official Decision Letter (A4)') }}</a>
        </div>
      </div>
    @elseif(! in_array($a->status, ['under_eval'], true))
      <div class="panel">
        <div class="bd" style="padding:20px">
          <p class="muted" style="margin:0">{{ __('Complete Step 1 (Record Validity) first before recording a formal transfer decision.') }}</p>
        </div>
      </div>
    @endif
  </div>

  {{-- TAB 4: Admission & Redemption --}}
  <div data-pane="admission" @if($activeTab !== 'admission') hidden @endif>
    @if($canAdmit)
      <form method="post" action="{{ route('transfers.admit', $a->id) }}" class="card panel" id="admission" style="margin-bottom:20px">
        <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Programme Admission (Separate Fact)') }}</h2></div>
        <div class="bd" style="padding:16px 20px">
          @csrf
          <p class="muted small" style="margin-top:0">{{ __('Admission is a separate fact from the transfer decision. Neither implies the other.') }}</p>
          @if($admitted)
            <div class="note ok"><p style="margin:0">{{ __('Admission recorded: intake :intake (evidence :ref).', ['intake' => $admitted->intake, 'ref' => $admitted->evidence_ref]) }}</p></div>
          @else
            <div style="display:grid;grid-template-columns:220px 1fr;gap:12px;margin-bottom:14px;max-width:600px">
              <div>
                <label for="intake">{{ __('Intake') }}</label>
                <input id="intake" name="intake" type="text" placeholder="e.g. 2026/2" required style="width:100%">
              </div>
              <div>
                <label for="evidence_ref">{{ __('Evidence reference') }}</label>
                <input id="evidence_ref" name="evidence_ref" type="text" placeholder="{{ __('Pejabat Pendaftar admission ref') }}" required style="width:100%">
              </div>
            </div>
            <button type="submit" class="btn primary sm">{{ __('Record admission') }}</button>
          @endif
        </div>
      </form>
    @else
      <div class="card panel" id="admission" style="margin-bottom:20px">
        <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Programme Admission (Separate Fact)') }}</h2></div>
        <div class="bd" style="padding:16px 20px">
          <p class="muted small" style="margin-top:0">{{ __('Admission is a separate fact from the transfer decision. Neither implies the other.') }}</p>
          @if($admitted)
            <div class="note ok"><p style="margin:0">{{ __('Admission recorded: intake :intake (evidence :ref).', ['intake' => $admitted->intake, 'ref' => $admitted->evidence_ref]) }}</p></div>
          @else
            <p class="muted" style="margin:0">{{ __('No admission recorded yet. Admission records are managed by CODL, Senate, and Records Custodian.') }}</p>
          @endif
        </div>
      </div>
    @endif

    {{-- Single Redemption Panel for this Application (All Provisionally Redeemed Courses in 1 Click) --}}
    @php
      $heldLines = $lines->where('credit_status', 'provisional')->values();
      $redeemedLines = $lines->where('credit_status', 'redeemed')->values();
      $canRedeemHere = auth()->user()->hasAnyRole('codl_admin', 'academic_authority', 'abc_depositor', 'records_depositor');
    @endphp
    @if($heldLines->isNotEmpty())
      <div class="card panel" style="margin-bottom:20px">
        <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
          <h2 style="margin:0;font-size:15px">{{ __('Single Redemption (Programme Completion / Withdrawal)') }}</h2>
          <span class="tag warn">{{ $heldLines->count() }} {{ $heldLines->count() === 1 ? __('course held') : __('courses held') }} &middot; {{ $fmtNum($heldLines->sum(fn ($x) => $x->credits_approved ?: $x->credits_requested)) }} cr</span>
        </div>
        <div class="bd" style="padding:16px 20px">
          <p class="muted small" style="margin-top:0;margin-bottom:14px">
            {{ __('Record graduation or withdrawal once to update all :count provisionally redeemed courses for :ref together.', ['count' => $heldLines->count(), 'ref' => $a->reference]) }}
          </p>
          @if($canRedeemHere)
            <form method="post" action="{{ route('transfers.redeem.bulk') }}">
              @csrf
              <input type="hidden" name="return_to" value="show">
              <input type="hidden" name="application_id" value="{{ $a->id }}">
              @foreach($heldLines as $hl)
                <input type="hidden" name="line_ids[]" value="{{ $hl->id }}">
              @endforeach
              <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:14px">
                <div>
                  <label for="app-redeem-ev">{{ __('Evidence reference') }} *</label>
                  <input id="app-redeem-ev" name="evidence_ref" type="text" placeholder="{{ __('e.g. SENATE/GRAD/2026-BITS') }}" required style="width:100%">
                </div>
                <div>
                  <label for="app-redeem-what">{{ __('Outcome') }} *</label>
                  <select id="app-redeem-what" name="what" style="width:100%">
                    <option value="completed">{{ __('Programme completed (Redeem all)') }}</option>
                    <option value="not_completed">{{ __('Withdrawn or not completed (Release all)') }}</option>
                  </select>
                </div>
                <div>
                  <label for="app-redeem-abcon">{{ __('ABC updated on') }}</label>
                  <input id="app-redeem-abcon" name="abc_updated_on" type="date" style="width:100%">
                </div>
                <div>
                  <label for="app-redeem-abcref">{{ __('ABC update ref') }}</label>
                  <input id="app-redeem-abcref" name="abc_update_ref" type="text" placeholder="{{ __('Optional ABC ref') }}" style="width:100%">
                </div>
              </div>
              <button type="submit" class="btn primary sm">{{ __('Record for all :count courses', ['count' => $heldLines->count()]) }}</button>
            </form>
          @else
            <p class="muted" style="margin:0">{{ __('Managed by CODL, Senate, and Records.') }}</p>
          @endif
        </div>
      </div>
    @elseif($redeemedLines->isNotEmpty())
      <div class="card panel" style="margin-bottom:20px">
        <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
          <h2 style="margin:0;font-size:15px">{{ __('Single Redemption Status') }}</h2>
          <span class="tag ok">{{ __('Redeemed') }}</span>
        </div>
        <div class="bd" style="padding:16px 20px">
          <p style="margin:0">{{ __(':count course(s) (:cr cr) permanently redeemed under the Single Redemption Principle.', ['count' => $redeemedLines->count(), 'cr' => $fmtNum($redeemedLines->sum(fn ($x) => $x->credits_approved ?: $x->credits_requested))]) }}</p>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
