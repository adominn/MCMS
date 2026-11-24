@extends('layouts.app')
@section('title', __('Readiness'))
@section('content')
@php
  $isMs = app()->getLocale() === 'ms';
  $isSupport = !empty($support) && !$dean && $o->owner_id !== auth()->id();
  $isOwnerLecturer = !$dean && !$isSupport && $o->owner_id === auth()->id();
  $sections = $sections ?? config('offerings.readiness_sections', []);
  $autoChecks = $autoChecks ?? ['checks' => [], 'blocking' => [], 'warnings' => [], 'assisted_hints' => [], 'summary' => ['total' => 0, 'passed' => 0, 'warnings' => 0, 'failed' => 0]];
  $lecturerSent = !empty($confirmations) && $confirmations->where('role', 'lecturer')->isNotEmpty();
  $rState = $o->readiness_state ?? null;
  $isConfirmed = in_array($o->status, ['open', 'running', 'ended'], true) || ($rState && in_array($rState, ['ready', 'ready_conditions'], true));
  $acSummary = $autoChecks['summary'] ?? ['total' => 0, 'passed' => 0, 'warnings' => 0, 'failed' => 0];
  $hasBlockingAuto = ($acSummary['failed'] ?? 0) > 0;
  $assistedHints = $autoChecks['assisted_hints'] ?? [];

  $shortTabTitles = [
    'course_info' => $isMs ? 'Maklumat Kursus' : 'Course Info',
    'materials_platform' => $isMs ? 'Bahan & Platform' : 'Materials & LMS',
    'assessment_integrity' => $isMs ? 'Pentaksiran' : 'Assessment',
    'learner_protection' => $isMs ? 'Perlindungan Peserta' : 'Learner Protection',
  ];

  $errTabs = (array) session('readiness_error_tabs', []);
  $errItems = (array) session('readiness_error_items', []);
  if ($errors->has('conditions') && !in_array('review_decision', $errTabs, true)) {
    $errTabs[] = 'review_decision';
  }
  $hasServerErr = $errors->any() || !empty($errTabs);

  // Per-section server-side progress counts
  $sectionStats = [];
  $overallDone = 0;
  $overallTotal = 0;
  foreach ($sections as $secKey => $sec) {
    $secItems = $sec['items'] ?? [];
    $tot = count($secItems);
    $done = 0;
    $yesCount = 0;
    foreach ($secItems as $ik => $idef) {
      $row = $items[$ik] ?? (object) ['status' => 'not_yet', 'note' => '', 'accepted' => false];
      if ($row->status === 'yes') {
        $yesCount++;
      }
      $rowDone = $dean
        ? ($row->status === 'na' || ($row->status === 'yes' && !empty($row->accepted)))
        : ($row->status !== 'not_yet');
      if ($rowDone) {
        $done++;
      }
    }
    $secHasErr = in_array($secKey, $errTabs, true);
    $secInd = $secHasErr ? 'err' : ($done === $tot ? 'complete' : ($done > 0 ? 'in-progress' : 'not-started'));
    $secSr = $secHasErr
      ? __('requires attention, :done of :total', ['done' => $done, 'total' => $tot])
      : ($done === $tot
        ? __('complete, :done of :total', ['done' => $done, 'total' => $tot])
        : ($done > 0
          ? __('in progress, :done of :total', ['done' => $done, 'total' => $tot])
          : __('not started, 0 of :total', ['total' => $tot])));
    $sectionStats[$secKey] = [
      'done' => $done,
      'total' => $tot,
      'yes_count' => $yesCount,
      'has_err' => $secHasErr,
      'state' => $secHasErr ? 'err' : ($done === $tot ? 'ok' : 'warn'),
      'ind' => $secInd,
      'sr' => $secSr,
    ];
    $overallDone += $done;
    $overallTotal += $tot;
  }

  $overallPct = $overallTotal > 0 ? (int) round(($overallDone / $overallTotal) * 100) : 0;
  $allCheckItemsDone = $overallTotal > 0 && $overallDone === $overallTotal;
  $supportConfirmed = !empty($confirmations) && $confirmations->where('role', 'support')->isNotEmpty();

  $acPassed = (int) ($acSummary['passed'] ?? 0);
  $acTotal = (int) ($acSummary['total'] ?? 0);
  $acWarnings = (int) ($acSummary['warnings'] ?? 0);
  $acFailed = (int) ($acSummary['failed'] ?? 0);
  $sysHasErr = $hasBlockingAuto || in_array('system_checks', $errTabs, true);
  $sysInd = $sysHasErr ? 'err' : ($acWarnings > 0 ? 'warn' : 'complete');
  $sysSr = $sysHasErr
    ? __('action required, :passed of :total passed', ['passed' => $acPassed, 'total' => $acTotal])
    : ($acWarnings > 0
      ? __(':count warning(s), :passed of :total passed', ['count' => $acWarnings, 'passed' => $acPassed, 'total' => $acTotal])
      : __('complete, :passed of :total passed', ['passed' => $acPassed, 'total' => $acTotal]));

  // Build ordered 6-tab registry
  $tabList = [
    [
      'key' => 'system_checks',
      'label' => __('System Checks'),
      'badge' => $acPassed.'/'.$acTotal,
      'ind' => $sysInd,
      'show_count' => $sysInd !== 'complete',
      'sr' => $sysSr,
      'has_err' => $sysHasErr,
      'is_final' => false,
    ],
  ];
  foreach ($sections as $secKey => $sec) {
    $st = $sectionStats[$secKey];
    $tabList[] = [
      'key' => $secKey,
      'label' => $shortTabTitles[$secKey] ?? ($isMs ? ($sec['title_ms'] ?? $sec['title_en']) : ($sec['title_en'] ?? $sec['title_ms'])),
      'badge' => $st['done'].'/'.$st['total'],
      'ind' => $st['ind'],
      'show_count' => $st['ind'] !== 'complete',
      'sr' => $st['sr'],
      'has_err' => $st['has_err'],
      'is_final' => false,
    ];
  }

  $decHasErr = in_array('review_decision', $errTabs, true);
  if ($decHasErr) {
    $decisionBadge = __('Needs attention');
    $decisionInd = 'err';
  } elseif ($o->status === 'held') {
    $decisionBadge = __('On hold');
    $decisionInd = 'err';
  } elseif ($o->status === 'cancelled') {
    $decisionBadge = __('Cancelled');
    $decisionInd = 'disabled';
  } elseif ($isConfirmed) {
    $decisionBadge = __('Confirmed');
    $decisionInd = 'complete';
  } elseif ($isSupport) {
    $decisionBadge = $supportConfirmed ? __('Recorded') : __('Awaiting support');
    $decisionInd = $supportConfirmed ? 'complete' : 'in-progress';
  } elseif ($dean) {
    if (!$lecturerSent) {
      $decisionBadge = __('Awaiting lecturer');
      $decisionInd = 'not-started';
    } elseif ($allCheckItemsDone && !$hasBlockingAuto) {
      $decisionBadge = __('Ready to decide');
      $decisionInd = 'ready';
    } else {
      $decisionBadge = __('In review');
      $decisionInd = 'in-progress';
    }
  } else {
    if ($lecturerSent) {
      $decisionBadge = __('Sent');
      $decisionInd = 'in-progress';
    } elseif ($allCheckItemsDone && !$hasBlockingAuto) {
      $decisionBadge = __('Ready to send');
      $decisionInd = 'ready';
    } else {
      $decisionBadge = __('Not ready');
      $decisionInd = 'not-started';
    }
  }

  $tabList[] = [
    'key' => 'review_decision',
    'label' => __('Review & Decision'),
    'badge' => $decisionBadge,
    'ind' => $decisionInd,
    'show_count' => true,
    'sr' => $decisionBadge,
    'has_err' => $decHasErr,
    'is_final' => true,
  ];

  $validTabKeys = array_column($tabList, 'key');

  // Priority default active tab: flashed error/save tab -> blocking auto-check -> first incomplete tab -> Review & Decision
  $flashedTab = session('readiness_active_tab');
  $defaultTab = null;
  if (is_string($flashedTab) && in_array($flashedTab, $validTabKeys, true)) {
    $defaultTab = $flashedTab;
  } elseif ($hasBlockingAuto) {
    $defaultTab = 'system_checks';
  } elseif ($isOwnerLecturer && $editable) {
    foreach (array_keys($sections) as $sk) {
      if (($sectionStats[$sk]['done'] ?? 0) < ($sectionStats[$sk]['total'] ?? 0)) {
        $defaultTab = $sk;
        break;
      }
    }
    $defaultTab ??= 'review_decision';
  } elseif ($dean && $editable && $lecturerSent) {
    foreach (array_keys($sections) as $sk) {
      if (($sectionStats[$sk]['done'] ?? 0) < ($sectionStats[$sk]['total'] ?? 0)) {
        $defaultTab = $sk;
        break;
      }
    }
    $defaultTab ??= 'review_decision';
  } else {
    $defaultTab = 'review_decision';
  }

  $tabIndexMap = array_flip($validTabKeys);
  $totalTabs = count($tabList);
@endphp

<x-breadcrumb :crumbs="[
  ($dean ? 'Offerings and readiness' : ($isSupport ? 'Offerings' : 'Cohorts and results')) => $isSupport ? route('approvals') : route('offerings'),
  $o->name_en . ' ' . $o->cohort_name => null,
]" />

<div class="title">
  <div style="display:flex;align-items:center;gap:16px">
    <x-badge :category="$o->category ?? 'samc'" :size="48" :image="$o->badge_image_path ?? null" />
    <div>
      <h1>{{ $o->name_en }} {{ $o->cohort_name }}</h1>
      <div class="sub">
        @if(!empty($o->lecturer_name)){{ $o->lecturer_name }} &middot; @endif
        {{ __('Starts :date', ['date' => \Carbon\Carbon::parse($o->start_date)->format('d/m/Y')]) }} &middot;
        {{ __('registration :start to :end', ['start' => \Carbon\Carbon::parse($o->reg_opens_on)->format('d/m/Y'), 'end' => \Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y')]) }} &middot;
        {{ __(':count places', ['count' => $o->capacity]) }} &middot; RM {{ number_format($o->fee, 2) }}
      </div>
    </div>
  </div>
  <div class="act-bar">
    <div class="act-left">
      @if(!$isSupport)
        <a class="btn ter sm" href="{{ route('offerings') }}">&larr; {{ __('All offerings') }}</a>
      @else
        <a class="btn ter sm" href="{{ route('approvals') }}">&larr; {{ __('Proposals & Senate') }}</a>
      @endif
    </div>
    <div class="act-right">
      @if($isOwnerLecturer)
        <a class="btn ter sm" href="{{ route('results.cohort', $o->id) }}">{{ __('Results') }}</a>
        <a class="btn ter sm" href="{{ route('completion.signoff.show', $o->id) }}">{{ __('Coordinator sign-off') }}</a>
        <a class="btn ter sm" href="{{ route('cohorts.credits', $o->id) }}">{{ __('Cohort ACR progress') }}</a>
      @elseif($dean)
        <a class="btn ter sm" href="{{ route('results.verify', $o->id) }}">{{ __('Results') }}</a>
        <a class="btn ter sm" href="{{ route('completion.show', $o->id) }}">{{ __('Confirm completion') }}</a>
      @endif
      @if(!$isSupport)
        <a class="btn ter sm" href="{{ route('offerings.participants', $o->id) }}">{{ __('Participants') }}</a>
      @endif
      <a class="btn ter sm" href="{{ route('offerings.readiness.print', $o->id) }}">{{ __('Print readiness report') }}</a>
      <x-pill :s="__($labels[$o->status] ?? ucfirst($o->status))" />
    </div>
  </div>
</div>

@if($o->status === 'held')
  <div class="note err" role="alert" style="margin-bottom:16px">
    <b>{{ __('On hold:') }}</b> {{ $o->held_reason }}
  </div>
@endif

@if($o->status === 'cancelled')
  <div class="note err" role="alert" style="margin-bottom:16px">
    <b>{{ __('Cancelled:') }}</b> {{ $o->cancel_reason }}
  </div>
@endif

<form id="readiness-form" method="post" action="{{ ($dean || $isSupport) ? route('offerings.confirm', $o->id) : route('offerings.readiness', $o->id) }}">
  @csrf
  <input type="hidden" name="active_tab" data-active-tab-input value="{{ $defaultTab }}">

  <div class="panel" style="margin-bottom:24px"
       data-checklist-tabs
       data-default-tab="{{ $defaultTab }}"
       data-has-server-error="{{ $hasServerErr ? '1' : '0' }}">
    <div class="hd">
      <h2>{{ __('Readiness checklist') }}</h2>
      <span class="r">
        @if($isConfirmed)
          <span class="tag ok">{{ __('Confirmed') }}</span>
        @elseif($isOwnerLecturer && !$editable)
          <span class="tag n">{{ __('Locked') }}</span>
        @elseif($dean)
          <span class="tag info">{{ __('Review & Decision') }}</span>
        @elseif($isSupport)
          <span class="tag info">{{ __('Platform Support Review') }}</span>
        @else
          <span class="tag warn">{{ __('In Preparation') }}</span>
        @endif
      </span>
    </div>

    {{-- Compact Overall Readiness Status Strip (separated cleanly from navigation tabs) --}}
    <div class="checklist-summary">
      <div class="checklist-summary-metrics">
        <div class="checklist-metric checklist-progress-wrap">
          <span class="cm-label">{{ $dean ? __('Evidence accepted:') : __('Checklist progress:') }}</span>
          <div class="checklist-progress-bar" aria-hidden="true">
            <span data-overall-bar class="{{ $overallDone === $overallTotal ? 'is-complete' : '' }}" style="width:{{ $overallPct }}%"></span>
          </div>
          <strong data-overall-count data-count-suffix="{{ $dean ? __('accepted') : __('confirmed') }}">{{ $overallDone }} / {{ $overallTotal }} {{ $dean ? __('accepted') : __('confirmed') }}</strong>
        </div>
        <span class="checklist-summary-sep" aria-hidden="true"></span>
        <div class="checklist-metric">
          @if($acFailed > 0)
            <button type="button" class="checklist-summary-link is-err" data-jump-tab="system_checks">
              <span class="ct-ind is-err" aria-hidden="true"></span>
              <span>{{ __(':count blocking issue(s) in System Checks', ['count' => $acFailed]) }}</span>
            </button>
          @elseif($acWarnings > 0)
            <button type="button" class="checklist-summary-link is-warn" data-jump-tab="system_checks">
              <span class="ct-ind is-warn" aria-hidden="true"></span>
              <span>{{ __(':count warning(s) in System Checks', ['count' => $acWarnings]) }}</span>
            </button>
          @else
            <button type="button" class="checklist-summary-link is-ok" data-jump-tab="system_checks">
              <span class="ct-ind is-complete" aria-hidden="true"></span>
              <span>{{ __('All :total system checks passed', ['total' => $acTotal]) }}</span>
            </button>
          @endif
        </div>
      </div>
      <div class="checklist-summary-actions">
        <span class="checklist-dirty-cue" data-dirty-badge hidden>{{ __('Unsaved changes') }}</span>
        @if($isOwnerLecturer && $editable)
          <button type="submit" name="action" value="save" class="btn ter sm">{{ __('Save draft') }}</button>
        @endif
      </div>
    </div>

    @if($isConfirmed)
      <div class="note ok" style="margin:0 20px 14px">
        <b>{{ __('Operational Readiness Confirmed:') }}</b>
        {{ __('All automated system checks and readiness confirmations have been verified. Registration and delivery proceed in accordance with institutional rules.') }}
      </div>
    @endif

    @if($hasBlockingAuto && (($dean && $editable) || ($isOwnerLecturer && $editable)))
      <div class="note err" role="alert" style="margin:0 20px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
          <b>{{ __('Automated Checks Require Attention:') }}</b>
          {{ __('Resolve the Action required automated checks before submitting or confirming this cohort as Ready or Ready with conditions.') }}
        </div>
        <button type="button" class="btn ter sm" data-jump-tab="system_checks">{{ __('View System Checks') }}</button>
      </div>
    @endif

    @if(!empty($errTabs) && !$hasBlockingAuto)
      <div class="note err" role="alert" style="margin:0 20px 14px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div>
          <b>{{ __('Attention required in:') }}</b>
          @foreach($errTabs as $ek)
            @php
              $eTab = collect($tabList)->firstWhere('key', $ek);
            @endphp
            @if($eTab)
              <button type="button" class="btn ter sm" style="margin:2px 4px" data-jump-tab="{{ $ek }}">{{ $eTab['label'] }}</button>
            @endif
          @endforeach
        </div>
      </div>
    @endif

    {{-- Mobile Section Switcher (shown <= 768px; desktop tab strip hidden on mobile) --}}
    <div class="checklist-mobile-nav">
      <div class="checklist-mobile-head">
        <label for="offering-tab-select">{{ __('Checklist section') }}</label>
        <span class="checklist-mobile-step" data-mobile-step data-step-template="{{ __('Step :current of :total', ['current' => ':current', 'total' => $totalTabs]) }}">
          {{ __('Step :current of :total', ['current' => ($tabIndexMap[$defaultTab] ?? 0) + 1, 'total' => $totalTabs]) }}
        </span>
      </div>
      <select id="offering-tab-select" class="checklist-mobile-select" data-tab-select aria-label="{{ __('Checklist section') }}">
        @foreach($tabList as $idx => $t)
          <option value="{{ $t['key'] }}" data-base-label="{{ ($idx + 1) }}. {{ $t['label'] }}" @selected($t['key'] === $defaultTab)>
            {{ ($idx + 1) }}. {{ $t['label'] }} ({{ $t['badge'] }})
          </option>
        @endforeach
      </select>
    </div>

    {{-- Desktop / Tablet Recessed Folder-Tab Navigation Rail --}}
    <div class="checklist-tabs-shell" data-tabs-shell>
      <button type="button" class="checklist-scroll-btn" data-tabs-scroll="left" aria-hidden="true" tabindex="-1" hidden>&lsaquo;</button>
      <div class="checklist-tabs-bar" role="tablist" aria-label="{{ __('Readiness checklist sections') }}" data-tabs-bar>
        @foreach($tabList as $t)
          @if(!empty($t['is_final']))
            <span class="checklist-tab-sep" aria-hidden="true"></span>
          @endif
          <button type="button"
                  class="checklist-tab {{ $t['has_err'] ? 'has-err' : '' }} {{ !empty($t['is_final']) ? 'is-final' : '' }}"
                  role="tab"
                  id="tab-{{ $t['key'] }}"
                  data-tab-btn="{{ $t['key'] }}"
                  data-tab-final="{{ !empty($t['is_final']) ? '1' : '0' }}"
                  aria-selected="{{ $t['key'] === $defaultTab ? 'true' : 'false' }}"
                  aria-controls="panel-{{ $t['key'] }}"
                  tabindex="{{ $t['key'] === $defaultTab ? '0' : '-1' }}">
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

    {{-- TAB 1: System Checks (Automated) --}}
    <section class="checklist-panel"
             role="tabpanel"
             id="panel-system_checks"
             data-tab-panel="system_checks"
             data-countable="0"
             aria-labelledby="tab-system_checks">
      <div class="cp-head">
        <div>
          <h3>{{ __('Automated System Readiness Checks') }}</h3>
          <p class="cp-desc">{{ __('Verified automatically by the system from curriculum, cohort dates, staffing, credential keys, and policy settings.') }}</p>
        </div>
        <span class="tag {{ $hasBlockingAuto ? 'err' : (($acSummary['warnings'] ?? 0) > 0 ? 'warn' : 'ok') }}">
          {{ __(':passed / :total Passed', ['passed' => $acSummary['passed'] ?? 0, 'total' => $acSummary['total'] ?? 0]) }}
        </span>
      </div>

      <table class="checklist-table" data-plain>
        <thead>
          <tr>
            <th style="width:32%">{{ __('System check') }}</th>
            <th style="width:16%">{{ __('Result') }}</th>
            <th style="width:52%">{{ __('Details') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($autoChecks['checks'] ?? [] as $chk)
            @php
              $chkTag = match($chk['status']) {
                'pass' => 'ok',
                'warn' => 'warn',
                'fail' => 'err',
                default => 'n',
              };
              $chkLabel = match($chk['status']) {
                'pass' => __('Passed'),
                'warn' => __('Warning'),
                'fail' => __('Action required'),
                default => __('Not applicable'),
              };
            @endphp
            <tr class="{{ $chk['status'] === 'fail' ? 'row-err' : '' }}">
              <td><b>{{ $chk['label'] }}</b></td>
              <td><span class="tag {{ $chkTag }}">{{ $chkLabel }}</span></td>
              <td style="font-size:13px">
                <div>{{ $chk['reason'] }}</div>
                @if($isOwnerLecturer && !empty($chk['action_url']) && !empty($chk['action_label']))
                  <div style="margin-top:6px">
                    <a class="btn ter sm" href="{{ $chk['action_url'] }}">{{ $chk['action_label'] }} &rarr;</a>
                  </div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="checklist-nav">
        <span class="checklist-nav-pos">{{ __('Section :cur of :total', ['cur' => 1, 'total' => $totalTabs]) }}</span>
        <div class="checklist-nav-group">
          <button type="button" class="btn ter sm" data-tab-next>{{ __('Next') }}: {{ $tabList[1]['label'] ?? '' }} &rarr;</button>
        </div>
      </div>
    </section>

    {{-- TABS 2–5: Four Assisted & Manual Confirmation Sections --}}
    @foreach($sections as $secKey => $sec)
      @php
        $secTitle = $isMs ? ($sec['title_ms'] ?? $sec['title_en']) : ($sec['title_en'] ?? $sec['title_ms']);
        $secDesc = $isMs ? ($sec['desc_ms'] ?? ($sec['desc_en'] ?? '')) : ($sec['desc_en'] ?? '');
        $secItems = $sec['items'] ?? [];
        $st = $sectionStats[$secKey];
        $curPos = ($tabIndexMap[$secKey] ?? 1) + 1;
        $prevTab = $tabList[$curPos - 2] ?? null;
        $nextTab = $tabList[$curPos] ?? null;
      @endphp
      <section class="checklist-panel"
               role="tabpanel"
               id="panel-{{ $secKey }}"
               data-tab-panel="{{ $secKey }}"
               data-countable="1"
               aria-labelledby="tab-{{ $secKey }}">
        <div class="cp-head">
          <div>
            <h3>{{ $secTitle }}</h3>
            @if($secDesc)<p class="cp-desc">{{ $secDesc }}</p>@endif
          </div>
          <div style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap">
            @if($dean && $editable && $st['yes_count'] > 0)
              <button type="button" class="btn ter sm" data-accept-tab="{{ $secKey }}">{{ __('Accept all verified in this tab') }}</button>
            @endif
            <span class="tag {{ $st['state'] === 'err' ? 'err' : ($st['state'] === 'ok' ? 'ok' : 'warn') }}">
              {{ __(':done / :total Confirmed', ['done' => $st['done'], 'total' => $st['total']]) }}
            </span>
          </div>
        </div>

        <table class="checklist-table" data-plain>
          <thead>
            <tr>
              <th style="width:40%">{{ __('Item') }}</th>
              <th style="width:16%">{{ __('Status') }}</th>
              <th style="width:30%">{{ __('Evidence note') }}</th>
              <th style="width:14%">@if($dean && $editable){{ __('Accept evidence') }} @else {{ __('Accepted') }} @endif</th>
            </tr>
          </thead>
          <tbody>
            @foreach($secItems as $k => $itemDef)
              @php
                $it = $items[$k] ?? (object) ['status' => 'not_yet', 'note' => '', 'accepted' => false];
                $title = is_array($itemDef) ? ($isMs ? $itemDef['ms'] : $itemDef['en']) : __($itemDef);
                $itemType = is_array($itemDef) ? ($itemDef['type'] ?? 'manual') : 'manual';
                $hintInfo = $assistedHints[$k] ?? null;
                $statusLabel = [
                  'yes' => ($isMs ? 'Disahkan' : 'Verified'),
                  'not_yet' => ($isMs ? 'Belum Sedia' : 'Pending'),
                  'na' => ($isMs ? 'Tidak Berkenaan' : 'Not applicable'),
                ][$it->status] ?? ucfirst($it->status);
                $rowMode = ($isOwnerLecturer && $editable) ? 'status' : (($dean && $editable) ? 'accept' : 'readonly');
                $rowDone = $dean
                  ? ($it->status === 'na' || ($it->status === 'yes' && !empty($it->accepted)))
                  : ($it->status !== 'not_yet');
                $rowHasErr = in_array($k, $errItems, true);
              @endphp
              <tr data-checklist-row data-row-key="{{ $k }}" data-row-mode="{{ $rowMode }}" data-row-done="{{ $rowDone ? '1' : '0' }}" class="{{ $rowHasErr ? 'row-err' : '' }}">
                <td>
                  <div style="font-weight:500;color:var(--ink);font-size:13px">{{ $title }}</div>
                  <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                    <span class="tag {{ $itemType === 'assisted' ? 'info' : 'n' }}" style="font-size:10.5px">
                      {{ $itemType === 'assisted' ? __('Assisted check') : __('Manual confirmation') }}
                    </span>
                    @if($rowHasErr)
                      <span class="tag err" style="font-size:10.5px">{{ $dean ? __('Acceptance required') : __('Response required') }}</span>
                    @endif
                  </div>
                  @if($hintInfo)
                    <div class="readiness-hint {{ $hintInfo['level'] === 'warn' ? 'is-warn' : '' }}">
                      {{ $hintInfo['hint'] }}
                    </div>
                  @endif
                </td>
                <td>
                  @if($isOwnerLecturer && $editable)
                    <select name="item[{{ $k }}][status]" aria-label="Status of {{ $title }}">
                      @foreach(['yes' => ($isMs ? 'Ya (Disahkan)' : 'Yes (Confirmed)'), 'not_yet' => ($isMs ? 'Belum Sedia' : 'Not yet'), 'na' => ($isMs ? 'Tidak Berkenaan' : 'Not applicable')] as $sv => $sl)
                        <option value="{{ $sv }}" @selected($it->status === $sv)>{{ $sl }}</option>
                      @endforeach
                    </select>
                  @else
                    <span class="tag {{ $it->status === 'yes' ? 'ok' : ($it->status === 'na' ? 'n' : 'warn') }}">
                      {{ $statusLabel }}
                    </span>
                  @endif
                </td>
                <td>
                  @if($isOwnerLecturer && $editable)
                    <input type="text" name="item[{{ $k }}][note]" value="{{ $it->note }}" aria-label="Evidence note for {{ $title }}" placeholder="{{ __('Reference to evidence') }}">
                  @else
                    <span style="font-size:13px">{{ $it->note ?: '—' }}</span>
                  @endif
                </td>
                <td>
                  @if($dean && $editable)
                    @if($it->status === 'yes')
                      <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" name="accept[{{ $k }}]" value="1" @checked($it->accepted) aria-label="Accept evidence for {{ $title }}">
                        <span>{{ __('Accept') }}</span>
                      </label>
                    @else
                      <span class="muted">-</span>
                    @endif
                  @else
                    @if($it->accepted)
                      <span class="tag ok">{{ __('Accepted') }}</span>
                    @else
                      <span class="muted">-</span>
                    @endif
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
          <span class="checklist-nav-pos">{{ __('Section :cur of :total', ['cur' => $curPos, 'total' => $totalTabs]) }}</span>
          <div class="checklist-nav-group">
            @if($isOwnerLecturer && $editable)
              <button type="submit" name="action" value="save" class="btn ter sm">{{ __('Save draft') }}</button>
            @endif
            @if($nextTab)
              <button type="button" class="btn sm" data-tab-next>{{ __('Next') }}: {{ $nextTab['label'] }} &rarr;</button>
            @endif
          </div>
        </div>
      </section>
    @endforeach

    {{-- TAB 6: Review & Decision (Summary + Role Decision Form + Confirmation History + Cohort Cancel) --}}
    <section class="checklist-panel"
             role="tabpanel"
             id="panel-review_decision"
             data-tab-panel="review_decision"
             data-countable="0"
             aria-labelledby="tab-review_decision">
      <div class="cp-head">
        <div>
          <h3>{{ __('Readiness Overview & Decision') }}</h3>
          <p class="cp-desc">{{ __('Review section completion across all tabs, then submit or record the readiness decision.') }}</p>
        </div>
      </div>

      {{-- Compact Section Breakdown Table --}}
      <table class="checklist-table" data-plain style="margin-bottom:20px">
        <thead>
          <tr>
            <th style="width:45%">{{ __('Section') }}</th>
            <th style="width:25%">{{ __('Progress') }}</th>
            <th style="width:30%;text-align:right">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><b>{{ __('Automated System Readiness Checks') }}</b></td>
            <td>
              <span class="tag {{ $hasBlockingAuto ? 'err' : (($acSummary['warnings'] ?? 0) > 0 ? 'warn' : 'ok') }}">
                {{ __(':passed / :total Passed', ['passed' => $acSummary['passed'] ?? 0, 'total' => $acSummary['total'] ?? 0]) }}
              </span>
            </td>
            <td style="text-align:right"><button type="button" class="btn ter sm" data-jump-tab="system_checks">{{ __('Open tab') }}</button></td>
          </tr>
          @foreach($sections as $secKey => $sec)
            @php
              $secTitle = $isMs ? ($sec['title_ms'] ?? $sec['title_en']) : ($sec['title_en'] ?? $sec['title_ms']);
              $st = $sectionStats[$secKey];
            @endphp
            <tr>
              <td><b>{{ $secTitle }}</b></td>
              <td>
                <span class="tag {{ $st['state'] === 'err' ? 'err' : ($st['state'] === 'ok' ? 'ok' : 'warn') }}">
                  {{ $st['done'] }} / {{ $st['total'] }} {{ $dean ? __('Accepted') : __('Confirmed') }}
                </span>
              </td>
              <td style="text-align:right"><button type="button" class="btn ter sm" data-jump-tab="{{ $secKey }}">{{ __('Open tab') }}</button></td>
            </tr>
          @endforeach
        </tbody>
      </table>

      {{-- Role-Specific Decision Form --}}
      @if($dean && $editable)
        <div style="border-top:1px solid var(--border);padding-top:18px;margin-bottom:24px">
          @if(!$lecturerSent)
            <p class="muted" style="margin:0">{{ __('You can confirm once the lecturer has sent the checklist.') }}</p>
          @else
            <h3 style="margin-top:0">{{ __('Confirm readiness') }}</h3>
            <div class="field" style="margin-bottom:16px;max-width:320px">
              <label for="outcome">{{ __('Outcome') }}</label>
              <select id="outcome" name="outcome">
                @foreach(config('offerings.outcomes') as $k => $l)
                  <option value="{{ $k }}" @selected(old('outcome') === $k)>{{ __($l) }}</option>
                @endforeach
              </select>
            </div>
            <div class="field" style="margin-bottom:20px;max-width:540px">
              <label for="conditions">{{ __('Conditions (for "Ready with conditions")') }}</label>
              <input type="text" id="conditions" name="conditions" value="{{ old('conditions') }}">
            </div>
            <button type="submit" class="btn">{{ __('Record decision') }}</button>
          @endif
        </div>
      @elseif($isSupport && $editable)
        <div style="border-top:1px solid var(--border);padding-top:18px;margin-bottom:24px">
          @if(!$lecturerSent)
            <p class="muted" style="margin:0">{{ __('Waiting for the course coordinator to submit the checklist before recording platform support confirmation.') }}</p>
          @else
            <h3 style="margin-top:0">{{ __('Platform support confirmation (CODL)') }}</h3>
            <div class="field" style="margin-bottom:16px;max-width:320px">
              <label for="outcome">{{ __('Support readiness outcome') }}</label>
              <select id="outcome" name="outcome">
                @foreach(config('offerings.outcomes') as $k => $l)
                  <option value="{{ $k }}" @selected(old('outcome') === $k)>{{ __($l) }}</option>
                @endforeach
              </select>
            </div>
            <div class="field" style="margin-bottom:20px;max-width:540px">
              <label for="conditions">{{ __('Conditions or platform remarks') }}</label>
              <input type="text" id="conditions" name="conditions" value="{{ old('conditions') }}">
            </div>
            <button type="submit" class="btn">{{ __('Record support confirmation') }}</button>
          @endif
        </div>
      @elseif($isOwnerLecturer && $editable)
        <div style="border-top:1px solid var(--border);padding-top:18px;margin-bottom:24px">
          <div style="margin-bottom:18px">
            <button type="submit" name="action" value="save" class="btn ter sm">{{ __('Save') }}</button>
          </div>
          <h3 style="margin-top:0">{{ __('Send to DD and/or CODL') }}</h3>
          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="outcome">{{ __('Your view') }}</label>
            <select id="outcome" name="outcome">
              @foreach(config('offerings.outcomes') as $k => $l)
                <option value="{{ $k }}" @selected(old('outcome') === $k)>{{ __($l) }}</option>
              @endforeach
            </select>
          </div>
          <div class="field" style="margin-bottom:20px;max-width:540px">
            <label for="conditions">{{ __('Conditions (for "Ready with conditions")') }}</label>
            <input type="text" id="conditions" name="conditions" value="{{ old('conditions') }}">
          </div>
          <button type="submit" name="action" value="send" class="btn">{{ __('Send the checklist') }}</button>
        </div>
      @endif

      {{-- Confirmations Log --}}
      <div style="border-top:1px solid var(--border);padding-top:18px;margin-bottom:16px">
        <h3 style="margin:0 0 12px">{{ __('Confirmations') }}</h3>
        <table class="checklist-table" data-plain>
          <thead>
            <tr>
              <th>{{ __('Who') }}</th>
              <th>{{ __('Role') }}</th>
              <th>{{ __('Outcome') }}</th>
              <th>{{ __('Date') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($confirmations as $c)
            <tr>
              <td><b>{{ $c->name }}</b>@if(!empty($c->position)) <div class="small muted">{{ $c->position }}</div>@endif</td>
              <td>{{ $c->role === 'ptj' ? __('Faculty Authority (Deputy Dean)') : ($c->role === 'support' ? __('Platform Support (CODL)') : __('Course Coordinator')) }}</td>
              <td>
                <x-pill :s="__(config('offerings.outcomes')[$c->outcome] ?? $c->outcome)" />
                @if($c->conditions) &mdash; <span class="s muted">{{ $c->conditions }}</span>@endif
              </td>
              <td>{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="muted" style="text-align:center;padding:20px">
                {{ __('No confirmation yet. Registration stays closed until confirmed by DD and/or CODL.') }}
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      {{-- Deputy Dean Cohort Cancellation (bound to separate outer form to keep HTML valid) --}}
      @if($dean && !in_array($o->status, ['cancelled', 'ended', 'running']))
        <div style="border-top:1px solid var(--border);padding-top:18px;margin-top:18px">
          <h3 style="margin:0 0 12px;color:var(--err-ink, #a2191f)">{{ __('Cancel this cohort') }}</h3>
          <div class="field" style="margin-bottom:14px;max-width:540px">
            <label for="creason">{{ __('Reason') }}</label>
            <input type="text" id="creason" name="reason" form="cancel-cohort-form" required minlength="5">
          </div>
          <button type="submit" form="cancel-cohort-form" class="btn danger sm">{{ __('Cancel cohort') }}</button>
        </div>
      @endif

      <div class="checklist-nav">
        <div class="checklist-nav-group">
          <button type="button" class="btn ter sm" data-tab-prev>&larr; {{ __('Previous') }}: {{ $tabList[$totalTabs - 2]['label'] ?? '' }}</button>
        </div>
        <span class="checklist-nav-pos">{{ __('Section :cur of :total', ['cur' => $totalTabs, 'total' => $totalTabs]) }}</span>
        <div class="checklist-nav-group"></div>
      </div>
    </section>
  </div>
</form>

@if($dean && !in_array($o->status, ['cancelled', 'ended', 'running']))
  <form id="cancel-cohort-form" method="post" action="{{ route('offerings.cancel', $o->id) }}" data-confirm="{{ __('Cancel this cohort?|It leaves the catalogue and registered participants are told. The reason is kept in the audit trail.') }}" data-danger hidden>
    @csrf
  </form>
@endif

<div class="note info" style="margin-top:20px;margin-bottom:24px">
  <div>
    <b>{{ __('Important Notice:') }}</b> {{ __('This operational readiness check ensures delivery quality and learner protection before an offering opens. It does not replace prior academic approval of the micro-credential.') }}
  </div>
</div>
@endsection
