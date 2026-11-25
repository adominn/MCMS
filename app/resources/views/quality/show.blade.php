@extends('layouts.app')
@section('title', __('Quality Verification'))
@section('content')
@php
  $isMs = app()->getLocale() === 'ms';
  $dd = $canActQv ?? (auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'qa_officer') && ! $isOwner);
  $stLabels = ['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'Not applicable'];
  $evidenceTabs = $evidenceTabs ?? [];
  $condsTotal = count($conds);
  $condsMet = collect($conds)->where('ok', true)->count();
@endphp

<x-breadcrumb :crumbs="[
  (auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'qa_officer', 'academic_authority') ? 'Quality Verification' : 'QV evidence') => route('quality'),
  $m->name_en => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('Quality Verification: :name', ['name' => $m->name_en]) }}</h1>
    <div class="sub">
      @if(!empty($m->lecturer_name)){{ $m->lecturer_name }}@if($isOwner) &middot; @endif @endif
      @if($isOwner)<a href="{{ route('mcs.show', $m->id) }}">{{ __('Micro-credential') }}</a>@endif
    </div>
  </div>
  <div class="act-bar">
    <div class="act-left">
      <a class="btn ter sm" href="{{ route('quality') }}">&larr; {{ __('All Quality Verification') }}</a>
    </div>
    <div class="act-right">
      @if($rec && in_array($rec->status, ['ready', 'submitted', 'verified'], true))
        <a class="btn ter sm" href="{{ route('quality.package', $m->id) }}">{{ __('Open submission package') }}</a>
      @endif
    </div>
  </div>
</div>

@if($standing)
<div class="panel" id="in-force" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('In force') }}</h2>
    <span class="r"><span class="tag ok">{{ __('Quality-Verified') }}</span></span>
  </div>
  <div class="bd">
    <p style="margin-top:0">
      <strong>{{ $standing->qv_reference }}</strong>: {{ mcms_num($standing->verified_credit) }} {{ __('credit') }}, MQF level {{ $standing->verified_mqf_level }},
      {{ __('valid :from to :to.', ['from' => \Carbon\Carbon::parse($standing->valid_from)->format('d/m/Y'), 'to' => \Carbon\Carbon::parse($standing->valid_until)->format('d/m/Y')]) }}
      @if($standing->reverification) <span class="tag overdue">{{ __('Re-verification needed after a material change') }}</span>@endif
    </p>
    @if($standing->conditions)<p>{{ __('Conditions: :cond', ['cond' => $standing->conditions]) }}</p>@endif
    <p class="muted" style="margin-bottom:{{ $dd ? '14px' : '0' }}">{{ __('Quality Verification does not by itself mean a transfer, admission or stackable status.') }}</p>
    @if($dd)
      <form method="post" action="{{ route('quality.end', $m->id) }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end" data-inplace>
        @csrf
        <div class="field" style="min-width:180px">
          <label for="to">{{ __('If the QVC/MQA suspended or withdrew it') }}</label>
          <select id="to" name="to">
            <option value="suspended">{{ __('Suspended') }}</option>
            <option value="withdrawn">{{ __('Withdrawn') }}</option>
          </select>
        </div>
        <div class="field" style="flex:1;min-width:240px">
          <label for="reason">{{ __('Reason') }}</label>
          <input id="reason" name="reason" type="text" required>
        </div>
        <button type="submit" class="btn danger sm">{{ __('Stop the claims') }}</button>
      </form>
    @endif
  </div>
</div>
@endif

@if(! $rec)
  <div class="panel" style="margin-bottom:24px">
    <div class="hd">
      <h2>{{ __('Basic conditions') }}</h2>
      <span class="r"><span class="tag {{ $met ? 'ok' : 'warn' }}">{{ $condsMet }} / {{ $condsTotal }} {{ __('Met') }}</span></span>
    </div>
    <div class="bd">
      <ul id="conditions" style="margin-top:0">
        @foreach($conds as $c)
          <li><b>{{ $c['ok'] ? __('Met') : __('Not met') }}:</b> {{ $c['text'] }}</li>
        @endforeach
      </ul>
      @if($isOwner)
        <form method="post" action="{{ route('quality.start', $m->id) }}" data-inplace>@csrf<button type="submit" class="btn sm">{{ __('Start a Quality Verification cycle') }}</button></form>
      @else
        <p class="muted" style="margin:0">{{ __('The lecturer who owns this micro-credential starts a cycle.') }}</p>
      @endif
    </div>
  </div>
@else
  @php
    $qvErrTabs = (array) session('qv_error_tabs', []);
    if ($errors->hasAny(['valid_until', 'qv_reference', 'verified_credit', 'verified_mqf_level', 'valid_from', 'decision_document', 'note', 'missing', 'target_date', 'submitted_on', 'reference']) && !in_array('changes_submission', $qvErrTabs, true)) {
      $qvErrTabs[] = 'changes_submission';
    }
    $hasQvServerErr = $errors->any() || !empty($qvErrTabs);

    // Compute per-tab progress stats across all 6 evidence domains
    $evTabStats = [];
    $overallEvDone = 0;
    $overallEvTotal = 0;
    foreach ($evidenceTabs as $tk => $tdef) {
      $tItems = $tdef['items'] ?? [];
      $tot = count($tItems);
      $done = 0;
      foreach ($tItems as $ik => $lbl) {
        $evRow = $evidence[$ik] ?? null;
        if ($evRow && ($evRow->linked || ($evRow->status ?? 'not_yet') !== 'not_yet')) {
          $done++;
        }
      }
      $hasTabErr = in_array($tk, $qvErrTabs, true) || ($tk === 'eligibility' && !$met);
      $tabInd = $hasTabErr ? 'err' : ($done === $tot ? 'complete' : ($done > 0 ? 'in-progress' : 'not-started'));
      $tabSr = $hasTabErr
        ? __('requires attention, :done of :total', ['done' => $done, 'total' => $tot])
        : ($done === $tot
          ? __('complete, :done of :total', ['done' => $done, 'total' => $tot])
          : ($done > 0
            ? __('in progress, :done of :total', ['done' => $done, 'total' => $tot])
            : __('not started, 0 of :total', ['total' => $tot])));
      $evTabStats[$tk] = [
        'done' => $done,
        'total' => $tot,
        'has_err' => $hasTabErr,
        'state' => $hasTabErr ? 'err' : ($done === $tot ? 'ok' : 'warn'),
        'ind' => $tabInd,
        'sr' => $tabSr,
      ];
      $overallEvDone += $done;
      $overallEvTotal += $tot;
    }
    $overallEvPct = $overallEvTotal > 0 ? (int) round(($overallEvDone / $overallEvTotal) * 100) : 0;

    // Build 7-tab registry (6 Evidence domain tabs + 7th Submission tab)
    $qvTabs = [];
    foreach ($evidenceTabs as $tk => $tdef) {
      $st = $evTabStats[$tk];
      $qvTabs[] = [
        'key' => $tk,
        'label' => $isMs ? $tdef['short_ms'] : $tdef['short_en'],
        'badge' => $st['done'].'/'.$st['total'],
        'ind' => $st['ind'],
        'show_count' => $st['ind'] !== 'complete',
        'sr' => $st['sr'],
        'has_err' => $st['has_err'],
        'is_final' => false,
      ];
    }
    $subHasErr = in_array('changes_submission', $qvErrTabs, true);
    if ($subHasErr) {
      $subBadge = __('Needs attention');
      $subInd = 'err';
    } elseif ($rec->status === 'verified') {
      $subBadge = __('Verified');
      $subInd = 'complete';
    } elseif ($rec->status === 'submitted') {
      $subBadge = __('Submitted');
      $subInd = 'in-progress';
    } elseif ($rec->status === 'ready') {
      $subBadge = $dd ? __('Ready to submit') : __('Confirmed ready');
      $subInd = $dd ? 'ready' : 'complete';
    } elseif ($rec->readiness_decision) {
      $subBadge = $dd ? __('Ready to confirm') : __('Awaiting confirmation');
      $subInd = $dd ? 'ready' : 'in-progress';
    } else {
      $subBadge = __('Preparing');
      $subInd = 'not-started';
    }
    $qvTabs[] = [
      'key' => 'changes_submission',
      'label' => $isMs ? 'Penyerahan' : 'Submission',
      'badge' => $subBadge,
      'ind' => $subInd,
      'show_count' => true,
      'sr' => $subBadge,
      'has_err' => $subHasErr,
      'is_final' => true,
    ];

    $validQvKeys = array_column($qvTabs, 'key');
    $qvTabIdx = array_flip($validQvKeys);
    $totalQvTabs = count($qvTabs);

    // Priority default tab selection
    $flashedQvTab = session('qv_active_tab');
    $defaultQvTab = null;
    if (is_string($flashedQvTab) && in_array($flashedQvTab, $validQvKeys, true)) {
      $defaultQvTab = $flashedQvTab;
    } elseif ($subHasErr) {
      $defaultQvTab = 'changes_submission';
    } elseif (!$met) {
      $defaultQvTab = 'eligibility';
    } elseif ($dd && ($rec->readiness_decision || in_array($rec->status, ['ready', 'submitted'], true))) {
      $defaultQvTab = 'changes_submission';
    } elseif ($isOwner && $rec->status !== 'submitted') {
      foreach (array_keys($evidenceTabs) as $ek) {
        if (($evTabStats[$ek]['done'] ?? 0) < ($evTabStats[$ek]['total'] ?? 0)) {
          $defaultQvTab = $ek;
          break;
        }
      }
      $defaultQvTab ??= 'changes_submission';
    } else {
      $defaultQvTab = 'changes_submission';
    }

    $rv = json_decode($rec->change_review ?? 'null', true);
    $canEditEvidence = $isOwner && $rec->status !== 'submitted';
  @endphp

  @if($rec->outcome_note && $rec->status === 'preparing')
    <div class="note err" role="alert" style="margin-bottom:16px">
      <b>{{ __('Last result: not verified.') }}</b> {{ $rec->outcome_note }}
    </div>
  @endif

  <div class="panel" style="margin-bottom:24px"
       data-checklist-tabs
       data-default-tab="{{ $defaultQvTab }}"
       data-has-server-error="{{ $hasQvServerErr ? '1' : '0' }}">
    <div class="hd">
      <h2 id="cycle">{{ __('Cycle :num: :status', ['num' => $rec->cycle_no, 'status' => __(config('qv.status')[$rec->status] ?? $rec->status)]) }}</h2>
      <span class="r">
        <span class="tag {{ $rec->status === 'verified' ? 'ok' : ($rec->status === 'submitted' ? 'info' : ($rec->status === 'ready' ? 'ok' : 'warn')) }}">
          {{ __(config('qv.status')[$rec->status] ?? $rec->status) }}
        </span>
      </span>
    </div>

    {{-- Compact Overall QV Status Strip (separated cleanly from navigation tabs) --}}
    <div class="checklist-summary">
      <div class="checklist-summary-metrics">
        <div class="checklist-metric checklist-progress-wrap">
          <span class="cm-label">{{ __('Evidence checklist:') }}</span>
          <div class="checklist-progress-bar" aria-hidden="true">
            <span data-overall-bar class="{{ $overallEvDone === $overallEvTotal ? 'is-complete' : '' }}" style="width:{{ $overallEvPct }}%"></span>
          </div>
          <strong data-overall-count data-count-suffix="{{ __('ready') }}">{{ $overallEvDone }} / {{ $overallEvTotal }} {{ __('ready') }}</strong>
        </div>
        <span class="checklist-summary-sep" aria-hidden="true"></span>
        <div class="checklist-metric">
          @if(!$met)
            <button type="button" class="checklist-summary-link is-err" data-jump-tab="eligibility">
              <span class="ct-ind is-err" aria-hidden="true"></span>
              <span>{{ __(':met / :total basic conditions met', ['met' => $condsMet, 'total' => $condsTotal]) }}</span>
            </button>
          @else
            <button type="button" class="checklist-summary-link is-ok" data-jump-tab="eligibility">
              <span class="ct-ind is-complete" aria-hidden="true"></span>
              <span>{{ __('All :total basic conditions met', ['total' => $condsTotal]) }}</span>
            </button>
          @endif
        </div>
      </div>
      <div class="checklist-summary-actions">
        <span class="checklist-dirty-cue" data-dirty-badge hidden>{{ __('Unsaved changes') }}</span>
        @if($canEditEvidence)
          <button type="submit" form="evidence" class="btn ter sm">{{ __('Save checklist') }}</button>
        @endif
      </div>
    </div>

    @if(!$met)
      <div class="note warn" style="margin:0 20px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div id="why-not">{{ __('The micro-credential cannot be Ready to submit until every condition is met.') }}</div>
        <button type="button" class="btn ter sm" data-jump-tab="eligibility">{{ __('Review Eligibility') }}</button>
      </div>
    @endif

    {{-- Mobile Section Switcher (shown <= 768px; desktop tab strip hidden on mobile) --}}
    <div class="checklist-mobile-nav">
      <div class="checklist-mobile-head">
        <label for="qv-tab-select">{{ __('Quality Verification section') }}</label>
        <span class="checklist-mobile-step" data-mobile-step data-step-template="{{ __('Step :current of :total', ['current' => ':current', 'total' => $totalQvTabs]) }}">
          {{ __('Step :current of :total', ['current' => ($qvTabIdx[$defaultQvTab] ?? 0) + 1, 'total' => $totalQvTabs]) }}
        </span>
      </div>
      <select id="qv-tab-select" class="checklist-mobile-select" data-tab-select aria-label="{{ __('Quality Verification section') }}">
        @foreach($qvTabs as $idx => $t)
          <option value="{{ $t['key'] }}" data-base-label="{{ ($idx + 1) }}. {{ $t['label'] }}" @selected($t['key'] === $defaultQvTab)>
            {{ ($idx + 1) }}. {{ $t['label'] }} ({{ $t['badge'] }})
          </option>
        @endforeach
      </select>
    </div>

    {{-- Desktop / Tablet Recessed Folder-Tab Navigation Rail --}}
    <div class="checklist-tabs-shell" data-tabs-shell>
      <button type="button" class="checklist-scroll-btn" data-tabs-scroll="left" aria-hidden="true" tabindex="-1" hidden>&lsaquo;</button>
      <div class="checklist-tabs-bar" role="tablist" aria-label="{{ __('Quality Verification sections') }}" data-tabs-bar>
        @foreach($qvTabs as $t)
          @if(!empty($t['is_final']))
            <span class="checklist-tab-sep" aria-hidden="true"></span>
          @endif
          <button type="button"
                  class="checklist-tab {{ $t['has_err'] ? 'has-err' : '' }} {{ !empty($t['is_final']) ? 'is-final' : '' }}"
                  role="tab"
                  id="tab-{{ $t['key'] }}"
                  data-tab-btn="{{ $t['key'] }}"
                  data-tab-final="{{ !empty($t['is_final']) ? '1' : '0' }}"
                  aria-selected="{{ $t['key'] === $defaultQvTab ? 'true' : 'false' }}"
                  aria-controls="panel-{{ $t['key'] }}"
                  tabindex="{{ $t['key'] === $defaultQvTab ? '0' : '-1' }}">
            <span class="ct-label">{{ $t['label'] }}</span>
            <span class="ct-meta">
              <span class="ct-ind is-{{ $t['ind'] }}" data-tab-ind="{{ $t['key'] }}" aria-hidden="true"></span>
              <span class="ct-count {{ !empty($t['is_final']) ? 'is-final-status' : '' }} {{ in_array($t['ind'], ['warn', 'err', 'ready'], true) ? 'is-'.$t['ind'] : '' }}"
                    data-tab-badge="{{ $t['key'] }}"
                    @if(empty($t['show_count'])) hidden @endif>{{ $t['badge'] }}</span>
            </span>
            <span class="sr-only" data-tab-sr="{{ $t['key'] }}">, {{ $t['sr'] }}</span>
          </button>
        @endforeach
      </div>
      <button type="button" class="checklist-scroll-btn" data-tabs-scroll="right" aria-hidden="true" tabindex="-1" hidden>&rsaquo;</button>
    </div>

    <div class="sr-only" aria-live="polite" data-tab-live></div>

    {{-- Single Form Spanning Evidence Tabs 1–6 (zero data loss across tab switches) --}}
    <form method="post" action="{{ route('quality.evidence', $m->id) }}" id="evidence" data-inplace>
      @csrf
      <input type="hidden" name="active_tab" data-active-tab-input value="{{ $defaultQvTab }}">

      @foreach($evidenceTabs as $tk => $tdef)
        @php
          $tTitle = $isMs ? $tdef['title_ms'] : $tdef['title_en'];
          $tDesc = $isMs ? $tdef['desc_ms'] : $tdef['desc_en'];
          $tItems = $tdef['items'] ?? [];
          $st = $evTabStats[$tk];
          $curPos = ($qvTabIdx[$tk] ?? 0) + 1;
          $prevTab = $qvTabs[$curPos - 2] ?? null;
          $nextTab = $qvTabs[$curPos] ?? null;
        @endphp
        <section class="checklist-panel"
                 role="tabpanel"
                 id="panel-{{ $tk }}"
                 data-tab-panel="{{ $tk }}"
                 data-evidence-panel="1"
                 data-countable="1"
                 aria-labelledby="tab-{{ $tk }}">
          <div class="cp-head">
            <div>
              <h3>{{ $tTitle }}</h3>
              <p class="cp-desc">{{ $tDesc }}</p>
            </div>
            <span class="tag {{ $st['state'] === 'err' ? 'err' : ($st['state'] === 'ok' ? 'ok' : 'warn') }}">
              {{ $st['done'] }} / {{ $st['total'] }} {{ __('Available') }}
            </span>
          </div>

          @if($tk === 'eligibility')
            <div style="background:var(--surface);border:1px solid var(--hairline);border-radius:var(--radius-sm,4px);padding:12px 16px;margin-bottom:16px">
              <div style="font-weight:600;font-size:13px;margin-bottom:8px;color:var(--ink)">{{ __('Basic conditions (verified automatically)') }}</div>
              <ul id="conditions" style="margin:0;padding-left:18px;font-size:13px">
                @foreach($conds as $c)
                  <li style="margin-bottom:4px">
                    <span class="tag {{ $c['ok'] ? 'ok' : 'err' }}" style="font-size:11px;margin-right:4px">{{ $c['ok'] ? __('Met') : __('Not met') }}</span>
                    {{ $c['text'] }}
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          <p class="muted small" style="margin:0 0 10px">{{ __('Evidence that MCMS holds is linked for you. Add a file reference for the rest.') }}</p>
          <table class="checklist-table" data-plain>
            <thead>
              <tr>
                <th style="width:40%">{{ __('Item') }}</th>
                <th style="width:20%">{{ __('Status') }}</th>
                <th style="width:22%">{{ __('File reference') }}</th>
                <th style="width:18%">{{ __('Notes') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($tItems as $k => $label)
                @php
                  $e = $evidence[$k] ?? null;
                  $isLinked = (bool) ($e?->linked);
                  $curSt = $e->status ?? 'not_yet';
                  $rowDone = $isLinked || $curSt !== 'not_yet';
                  $isLegacyRow = ($tdef['legacy'] ?? null) === $k;
                @endphp
                <tr data-checklist-row data-row-key="{{ $k }}" data-row-mode="qv" data-row-linked="{{ $isLinked ? '1' : '0' }}" data-row-done="{{ $rowDone ? '1' : '0' }}">
                  <td>
                    <label for="st-{{ $k }}" style="margin:0"><b>{{ __($label) }}</b></label>
                    @if($isLegacyRow)
                      <div style="margin-top:3px"><span class="tag info" style="font-size:10.5px">{{ __('Domain summary record') }}</span></div>
                    @endif
                  </td>
                  <td>
                    @if($isLinked)
                      <span class="tag ok">{{ __('Available (linked from MCMS)') }}</span>
                    @elseif($canEditEvidence)
                      <select id="st-{{ $k }}" name="status[{{ $k }}]" style="min-width:140px">
                        @foreach($stLabels as $v => $t)
                          <option value="{{ $v }}" @selected($curSt === $v)>{{ __($t) }}</option>
                        @endforeach
                      </select>
                    @else
                      <span class="tag {{ $curSt === 'available' ? 'ok' : ($curSt === 'na' ? 'n' : 'warn') }}">{{ __($stLabels[$curSt] ?? ucfirst($curSt)) }}</span>
                    @endif
                  </td>
                  <td>
                    @if($isLinked)
                      <span style="font-size:13px">{{ $e->file_ref }}</span>
                    @elseif($canEditEvidence)
                      <input type="text" name="file[{{ $k }}]" value="{{ $e->file_ref ?? '' }}" aria-label="File reference for {{ $label }}" placeholder="{{ __('File or document ref') }}">
                    @else
                      <span style="font-size:13px">{{ $e->file_ref ?: '—' }}</span>
                    @endif
                  </td>
                  <td>
                    @if($canEditEvidence && ! $isLinked)
                      <input type="text" name="notes[{{ $k }}]" value="{{ $e->notes ?? '' }}" aria-label="Notes for {{ $label }}" placeholder="{{ __('Optional notes') }}">
                    @else
                      <span style="font-size:13px">{{ $e->notes ?: '—' }}</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="checklist-nav">
            <div class="checklist-nav-group">
              @if($prevTab)
                <button type="button" class="btn ter sm" data-tab-prev>&larr; {{ __('Previous') }}: {{ $prevTab['label'] }}</button>
              @endif
            </div>
            <span class="checklist-nav-pos">{{ __('Section :cur of :total', ['cur' => $curPos, 'total' => $totalQvTabs]) }}</span>
            <div class="checklist-nav-group">
              @if($canEditEvidence)
                <button type="submit" class="btn ter sm">{{ __('Save checklist') }}</button>
              @endif
              @if($nextTab)
                <button type="button" class="btn sm" data-tab-next>{{ __('Next') }}: {{ $nextTab['label'] }} &rarr;</button>
              @endif
            </div>
          </div>
        </section>
      @endforeach
    </form>

    {{-- TAB 7: Changes & Submission (Material Change Review + Readiness Decision + Submission/Result + History) --}}
    <section class="checklist-panel"
             role="tabpanel"
             id="panel-changes_submission"
             data-tab-panel="changes_submission"
             data-countable="0"
             aria-labelledby="tab-changes_submission">
      <div class="cp-head">
        <div>
          <h3>{{ __('Material Change Review, Readiness & Submission') }}</h3>
          <p class="cp-desc">{{ __('Review changes since the last verification, confirm readiness, and record QVC/MQA submission or results.') }}</p>
        </div>
      </div>

      @if($canEditEvidence)
        <div class="note warn" data-dirty-notice hidden style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <div>{{ __('You have unsaved changes in the Evidence Checklist tabs. Save your checklist before recording a readiness decision.') }}</div>
          <button type="submit" form="evidence" class="btn ter sm">{{ __('Save checklist') }}</button>
        </div>
      @endif

      {{-- Material Change Review --}}
      <form method="post" action="{{ route('quality.review', $m->id) }}" id="review" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)" data-inplace>
        @csrf
        <h3 style="margin:0 0 4px">{{ __('Material change review') }}</h3>
        <p class="muted small" style="margin:0 0 12px">{{ __('For each component, say whether it changed since the last verification and what the change needs.') }}</p>
        <table class="checklist-table" data-plain>
          <thead>
            <tr>
              <th style="width:36%">{{ __('Component') }}</th>
              <th style="width:24%">{{ __('Change') }}</th>
              <th style="width:40%">{{ __('What changed') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach(config('qv.components') as $k => $label)
              <tr>
                <td><label for="ac-{{ $k }}" style="margin:0"><b>{{ __($label) }}</b></label></td>
                <td>
                  @if($canEditEvidence)
                    <select id="ac-{{ $k }}" name="action[{{ $k }}]" style="min-width:140px">
                      @foreach(config('qv.review_actions') as $v => $t)
                        <option value="{{ $v }}" @selected(($rv[$k]['action'] ?? 'none') === $v)>{{ __($t) }}</option>
                      @endforeach
                    </select>
                  @else
                    {{ __(config('qv.review_actions')[$rv[$k]['action'] ?? 'none'] ?? 'No change') }}
                  @endif
                </td>
                <td>
                  @if($canEditEvidence)
                    <input type="text" name="note[{{ $k }}]" value="{{ $rv[$k]['note'] ?? '' }}" aria-label="What changed in {{ $label }}" placeholder="{{ __('Describe change if applicable') }}">
                  @else
                    {{ $rv[$k]['note'] ?? '—' }}
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($canEditEvidence)
          <p style="margin:12px 0 0"><button type="submit" class="btn ter sm">{{ __('Save review') }}</button></p>
        @endif
      </form>

      {{-- Readiness Decision --}}
      <div id="readiness" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
        <h3 style="margin:0 0 12px">{{ __('Readiness decision') }}</h3>
        @if($rec->readiness_decision)
          <div style="background:var(--surface);border:1px solid var(--hairline);border-radius:var(--radius-sm,4px);padding:12px;margin-bottom:12px">
            <div style="margin-bottom:6px">
              {{ __('Recorded:') }} <strong>{{ __(config('qv.readiness')[$rec->readiness_decision]) }}</strong>
              @if($rec->target_date)
                &middot; <span class="muted">{{ __('target date :date', ['date' => \Carbon\Carbon::parse($rec->target_date)->format('d/m/Y')]) }}</span>
              @endif
              @if($rec->readiness_missing)
                <div class="s err" style="margin-top:4px">{{ __('Missing: :missing', ['missing' => $rec->readiness_missing]) }}</div>
              @endif
            </div>
            <div>
              @if($rec->readiness_confirmed_at)
                <span class="tag ok" style="font-size:11px">{{ __('Confirmed by the deputy dean on :date.', ['date' => \Carbon\Carbon::parse($rec->readiness_confirmed_at)->format('d/m/Y')]) }}</span>
              @else
                <span class="tag info" style="font-size:11px">{{ __('Waiting for the deputy dean to confirm') }}</span>
              @endif
            </div>
          </div>
        @endif

        @if($canEditEvidence)
          @if(! $rec->readiness_confirmed_at)
            <form method="post" action="{{ route('quality.readiness', $m->id) }}" data-inplace>
              @csrf
              <div class="form-grid cols-3">
                <div class="field">
                  <label for="decision">{{ __('Decision') }}</label>
                  <select id="decision" name="decision">
                    @foreach(config('qv.readiness') as $v => $t)
                      <option value="{{ $v }}" @selected($rec->readiness_decision === $v)>{{ __($t) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="field">
                  <label for="target_date">{{ __('Target date') }}</label>
                  <input id="target_date" name="target_date" type="date" value="{{ $rec->target_date }}">
                </div>
                <div class="field">
                  <label for="missing">{{ __('Missing critical evidence') }}</label>
                  <input id="missing" name="missing" type="text" value="{{ $rec->readiness_missing }}">
                </div>
              </div>
              <button type="submit" class="btn sm">{{ __('Record decision') }}</button>
            </form>
          @else
            <details class="fold" style="margin-top:12px">
              <summary><span class="muted small">{{ __('Revise readiness decision (will require deputy dean re-confirmation)') }}</span></summary>
              <form method="post" action="{{ route('quality.readiness', $m->id) }}" style="margin-top:12px" data-inplace>
                @csrf
                <div class="form-grid cols-3">
                  <div class="field">
                    <label for="decision">{{ __('Decision') }}</label>
                    <select id="decision" name="decision">
                      @foreach(config('qv.readiness') as $v => $t)
                        <option value="{{ $v }}" @selected($rec->readiness_decision === $v)>{{ __($t) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="field">
                    <label for="target_date">{{ __('Target date') }}</label>
                    <input id="target_date" name="target_date" type="date" value="{{ $rec->target_date }}">
                  </div>
                  <div class="field">
                    <label for="missing">{{ __('Missing critical evidence') }}</label>
                    <input id="missing" name="missing" type="text" value="{{ $rec->readiness_missing }}">
                  </div>
                </div>
                <button type="submit" class="btn sm">{{ __('Update decision') }}</button>
              </form>
            </details>
          @endif
        @elseif(!$rec->readiness_decision)
          <p class="muted" style="margin:0">{{ __('Waiting for the course coordinator to record a readiness decision.') }}</p>
        @endif

        @if($dd && $rec->readiness_decision && ! $rec->readiness_confirmed_at)
          <form method="post" action="{{ route('quality.confirm', $m->id) }}" style="margin-top:12px" data-inplace>
            @csrf
            <button type="submit" class="btn sm primary">{{ __('Confirm readiness decision') }}</button>
          </form>
        @endif
      </div>

      {{-- Submission to QVC/MQA (hidden from non-acting roles while still preparing) --}}
      @if($dd || in_array($rec->status, ['ready', 'submitted']))
        <div id="submission" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
          <h3 style="margin:0 0 12px">{{ __('Submission to the QVC/MQA') }}</h3>
          @if($rec->status === 'ready' && $dd)
            <p><a href="{{ route('quality.package', $m->id) }}">{{ __('Open the submission package') }}</a></p>
            <form method="post" action="{{ route('quality.submit', $m->id) }}" data-inplace>
              @csrf
              <div class="form-grid cols-2">
                <div class="field">
                  <label for="submitted_on">{{ __('Date submitted') }}</label>
                  <input id="submitted_on" name="submitted_on" type="date" required>
                </div>
                <div class="field">
                  <label for="reference">{{ __('QVC/MQA reference') }}</label>
                  <input id="reference" name="reference" type="text" required>
                </div>
              </div>
              <button type="submit" class="btn sm">{{ __('Record submission') }}</button>
            </form>
          @elseif($rec->status === 'submitted')
            <p>{{ __('Submitted on :date, reference :ref.', ['date' => \Carbon\Carbon::parse($rec->submitted_on)->format('d/m/Y'), 'ref' => $rec->submitted_ref]) }} <a href="{{ route('quality.package', $m->id) }}">{{ __('Package') }}</a></p>
            @if($dd)
              <form method="post" action="{{ route('quality.result', $m->id) }}" id="result" data-inplace>
                @csrf
                <h4>{{ __('Record the result') }}</h4>
                <div class="form-grid cols-2">
                  <div class="field">
                    <label for="outcome">{{ __('Result') }}</label>
                    <select id="outcome" name="outcome">
                      <option value="verified">{{ __('Verified') }}</option>
                      <option value="not_verified">{{ __('Not verified') }}</option>
                    </select>
                  </div>
                  <div class="field">
                    <label for="qv_reference">{{ __('QV reference') }}</label>
                    <input id="qv_reference" name="qv_reference" type="text">
                  </div>
                </div>
                <div class="form-grid cols-4">
                  <div class="field">
                    <label for="verified_credit">{{ __('Verified credit') }}</label>
                    <input id="verified_credit" name="verified_credit" type="number" step="0.5" min="0">
                  </div>
                  <div class="field">
                    <label for="verified_mqf_level">{{ __('MQF level') }}</label>
                    <input id="verified_mqf_level" name="verified_mqf_level" type="number" min="1" max="8">
                  </div>
                  <div class="field">
                    <label for="valid_from">{{ __('Valid from') }}</label>
                    <input id="valid_from" name="valid_from" type="date">
                  </div>
                  <div class="field">
                    <label for="valid_until">{{ __('Valid until (at most :years years)', ['years' => $maxYears]) }}</label>
                    <input id="valid_until" name="valid_until" type="date">
                  </div>
                </div>
                <div class="form-grid cols-2">
                  <div class="field">
                    <label for="conditions-in">{{ __('Conditions') }}</label>
                    <input id="conditions-in" name="conditions" type="text">
                  </div>
                  <div class="field">
                    <label for="decision_document">{{ __('Decision document') }}</label>
                    <input id="decision_document" name="decision_document" type="text">
                  </div>
                </div>
                <div class="field" style="margin-bottom:16px">
                  <label for="note">{{ __('If not verified, why') }}</label>
                  <input id="note" name="note" type="text">
                </div>
                <button type="submit" class="btn sm">{{ __('Record result') }}</button>
              </form>
            @endif
          @elseif($rec->status === 'ready')
            <p class="muted" style="margin:0"><a href="{{ route('quality.package', $m->id) }}">{{ __('Open the submission package') }}</a> &middot; {{ __('Ready for institutional submission to QVC/MQA.') }}</p>
          @else
            <p class="muted" id="submit-off" style="margin:0"><button type="button" disabled class="btn sm">{{ __('Record submission') }}</button> {{ __('Submission needs the status Ready to submit.') }}</p>
          @endif
        </div>
      @endif

      {{-- Cycle History inside Tab 7 --}}
      @if($history->count())
        <div>
          <h3 style="margin:0 0 10px">{{ __('History') }}</h3>
          <table id="history" class="checklist-table" data-plain>
            <thead>
              <tr>
                <th>{{ __('Cycle') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Reference') }}</th>
                <th>{{ __('Valid') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($history as $h)
                <tr>
                  <td>{{ $h->cycle_no }}</td>
                  <td>{{ __(config('qv.status')[$h->status] ?? $h->status) }}</td>
                  <td>{{ $h->qv_reference ?? $h->submitted_ref ?: '—' }}</td>
                  <td>@if($h->valid_until){{ \Carbon\Carbon::parse($h->valid_from)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($h->valid_until)->format('d/m/Y') }}@else—@endif</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      <div class="checklist-nav">
        <div class="checklist-nav-group">
          <button type="button" class="btn ter sm" data-tab-prev>&larr; {{ __('Previous') }}: {{ $qvTabs[$totalQvTabs - 2]['label'] ?? '' }}</button>
        </div>
        <span class="checklist-nav-pos">{{ __('Section :cur of :total', ['cur' => $totalQvTabs, 'total' => $totalQvTabs]) }}</span>
        <div class="checklist-nav-group"></div>
      </div>
    </section>
  </div>
@endif

@if(! $rec && $history->count())
  <div class="panel">
    <div class="hd"><h2>{{ __('History') }}</h2></div>
    <div class="bd" style="padding:0">
      <table id="history" class="checklist-table" data-plain>
        <thead>
          <tr>
            <th>{{ __('Cycle') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Valid') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($history as $h)
            <tr>
              <td>{{ $h->cycle_no }}</td>
              <td>{{ __(config('qv.status')[$h->status] ?? $h->status) }}</td>
              <td>{{ $h->qv_reference ?? $h->submitted_ref ?: '—' }}</td>
              <td>@if($h->valid_until){{ \Carbon\Carbon::parse($h->valid_from)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($h->valid_until)->format('d/m/Y') }}@else—@endif</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
