@extends('layouts.app')
@section('title', __('Quality review (CQI)'))
@section('content')
<div class="crumb">
  <a href="{{ route('tasks') }}">{{ __('Home') }}</a>
  <span>/</span>
  <span>{{ __('Quality review (CQI)') }}</span>
</div>

<div class="title" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
  <div>
    <h1>{{ __('Quality review (CQI)') }}</h1>
    <div class="sub">{{ __('One record per cohort: indicators, issues, actions and a check that the action worked.') }}</div>
  </div>
  <div class="act" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    @if(auth()->user()?->hasAnyRole('deputy_dean'))
      <a class="btn sec sm" href="{{ route('suspensions') }}" title="{{ __('Formal micro-credential suspension and termination powers') }}">
        <span class="btn-lbl">{{ __('Suspension / Termination') }}</span>
      </a>
    @endif
  </div>
</div>

{{-- Carbon Horizontal Navigation Tabs --}}
<div data-tabs style="margin-bottom:24px">
  <nav class="tabs" style="margin-bottom:20px;border-bottom:1px solid var(--rule);display:flex;gap:24px" role="tablist">
    <button type="button" class="tab-btn active" data-tab="tab-cohorts" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid var(--accent, #1e2a6e);color:var(--accent, #1e2a6e);cursor:pointer">
      {{ __('Cohort Indicators') }} ({{ count($offerings) }})
    </button>
    <button type="button" class="tab-btn" data-tab="tab-actions" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
      {{ __('Open Improvement Actions') }} ({{ count($actions) }})
    </button>
    <button type="button" class="tab-btn" data-tab="tab-periodic" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
      {{ __('Periodic Reviews & Governance') }} ({{ count($due) + count($qv) }})
    </button>
  </nav>

  {{-- ===================== TAB 1: COHORT INDICATORS ===================== --}}
  <div id="tab-cohorts" class="tab-pane">
    <div class="note info" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px 16px;border-left:4px solid var(--primary);background:var(--info-bg, #e8eaf6)">
      <div style="font-size:20px;color:var(--primary);flex:none">
        <svg viewBox="0 0 16 16" width="20" height="20" fill="currentColor"><path d="M1 6l7-3 7 3-7 3zM4 8v3c2 1.5 6 1.5 8 0V8"/></svg>
      </div>
      <div>
        <div style="font-weight:700;font-size:14px;color:var(--ink)">{{ __('Course & Cohort Level: Performance & Indicators') }}</div>
        <div class="muted s" style="font-size:12px">{{ __('Evaluate active and ended cohort metrics, outcome attainment, and student feedback per course. Click "View details" to inspect full indicator benchmarks.') }}</div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:24px">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h2>{{ __('Cohort indicators') }}</h2>
          <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Select a course/subject or explore cohort indicators, outcome attainment, and improvement actions.') }}</div>
        </div>
        @if($offerings->isNotEmpty())
          <div style="display:inline-flex;align-items:center;gap:8px">
            <label for="course-filter" style="margin:0;font-weight:600;font-size:13px;white-space:nowrap">{{ __('Filter by course/subject:') }}</label>
            <select id="course-filter" onchange="filterCohortCourse(this.value)" style="min-width:240px;margin:0;height:34px;font-size:13px;padding:0 8px">
              <option value="all" @selected(empty($selectedCourse))>{{ __('All courses / subjects (:count)', ['count' => count($offerings)]) }}</option>
              @foreach($courses as $c)
                <option value="{{ $c['id'] }}" @selected(($selectedCourse ?? null) == $c['id'])>{{ $c['name'] }} ({{ $c['count'] }} {{ __('cohort(s)') }})</option>
              @endforeach
            </select>
          </div>
        @endif
      </div>
      <div class="bd" style="padding:0">
        <table id="cohort-indicators-table">
          <thead>
            <tr>
              <th>{{ __('Course / Subject') }}</th>
              <th>{{ __('Cohort') }}</th>
              <th style="text-align:center">{{ __('Participants') }}</th>
              <th>{{ __('Indicator target status') }}</th>
              <th>{{ __('Key metrics') }}</th>
              <th>{{ __('CQI review') }}</th>
              <th style="text-align:right">{{ __('Action') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($offerings as $o)
            @php
              $rowsByKey = collect($o->ind['rows'])->keyBy('key');
              $passVal = $rowsByKey['pass']['value'] ?? null;
              $compVal = $rowsByKey['completion']['value'] ?? null;
            @endphp
            <tr class="cohort-row cohort" data-mc="{{ $o->micro_credential_id }}" data-oid="{{ $o->id }}" data-href="{{ route('monitoring.cohort', $o->id) }}">
              <td>
                <a href="{{ route('monitoring.cohort', $o->id) }}" style="font-weight:700;color:var(--ink)">
                  {{ $o->name_en }}
                </a>
              </td>
              <td>
                <div style="font-weight:600">{{ $o->cohort_name }}</div>
                <x-pill :s="__(config('offerings.status')[$o->status] ?? ucfirst($o->status))" />
              </td>
              <td style="text-align:center">
                <span class="tag info">{{ $o->ind['participants'] }}</span>
              </td>
              <td>
                @if($o->below > 0)
                  <span class="tag overdue">{{ $o->below }} {{ __('below target') }}</span>
                @else
                  <span class="tag ok">{{ __('All targets met') }}</span>
                @endif
              </td>
              <td style="font-size:13px">
                <div><span class="muted s">{{ __('Pass rate:') }}</span> <b>{{ $passVal !== null ? $passVal.'%' : 'n/a' }}</b></div>
                <div><span class="muted s">{{ __('Completion rate:') }}</span> <b>{{ $compVal !== null ? $compVal.'%' : 'n/a' }}</b></div>
              </td>
              <td>
                @if($o->record)
                  <x-pill :s="__(['draft' => 'Draft', 'submitted' => 'Submitted to the deputy dean', 'confirmed' => 'Confirmed'][$o->record->status] ?? ucfirst($o->record->status))" />
                  <span class="muted s" style="display:block;font-size:11px;margin-top:2px">{{ $o->record->reference }}</span>
                @else
                  <span class="muted s">{{ __('Not started') }}</span>
                @endif
              </td>
              <td style="text-align:right">
                <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                  @if($o->record)
                    <a class="btn ter sm" href="{{ route('cqi.show', $o->record->id) }}" title="{{ __('Open Quality Review record') }}">
                      <span class="btn-lbl">{{ __('Quality review :ref', ['ref' => $o->record->reference]) }}</span> &rsaquo;
                    </a>
                  @elseif(auth()->user()?->hasAnyRole('lecturer') && ! auth()->user()?->hasAnyRole('deputy_dean'))
                    <form method="post" action="{{ route('cqi.store') }}" style="display:inline;margin:0">
                      @csrf
                      <input type="hidden" name="offering_id" value="{{ $o->id }}">
                      <button type="submit" class="btn sm" aria-label="Start a quality review of {{ $o->name_en }} {{ $o->cohort_name }}">
                        <span class="btn-lbl">{{ __('Start a quality review') }}</span>
                      </button>
                    </form>
                  @endif
                  <a href="{{ route('monitoring.cohort', $o->id) }}" class="btn sec sm" title="{{ __('View detailed quality indicators') }}">
                    <span class="btn-lbl">{{ __('View details') }} &rarr;</span>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="muted" style="text-align:center;padding:24px">
                {{ __('No cohort with participants yet.') }}
              </td>
            </tr>
          @endforelse
          <tr id="cohort-filter-empty" style="display:none">
            <td colspan="7" class="muted" style="text-align:center;padding:24px">
              {{ __('No cohorts found for the selected course / subject.') }}
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===================== TAB 2: OPEN IMPROVEMENT ACTIONS ===================== --}}
  <div id="tab-actions" class="tab-pane" style="display:none">
    <div class="note info" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px 16px;border-left:4px solid #7c3aed;background:#f5f3ff">
      <div style="font-size:20px;color:#7c3aed;flex:none">
        <svg viewBox="0 0 16 16" width="20" height="20" fill="currentColor"><path d="M3 13V8M7 13V4M11 13V7M15 13H1"/></svg>
      </div>
      <div>
        <div style="font-weight:700;font-size:14px;color:#4c1d95">{{ __('Continuous Quality Improvement (CQI) Action Tracking') }}</div>
        <div class="muted s" style="font-size:12px;color:#6b21a8">{{ __('Consolidated oversight of all open improvement actions generated across micro-credentials, including due dates and implementation status.') }}</div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:24px">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <h2>{{ __('Open improvement actions') }}</h2>
          <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Continuous improvement actions generated across all micro-credentials.') }}</div>
        </div>
        <span class="tag info">{{ count($actions) }} {{ __('actions') }}</span>
      </div>
      <div class="bd" style="padding:0">
        <table id="open-actions">
          <thead>
            <tr>
              <th>{{ __('Record') }}</th>
              <th>{{ __('Micro-credential') }}</th>
              <th>{{ __('Action') }}</th>
              <th>{{ __('Due') }}</th>
              <th>{{ __('Status') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($actions as $a)
            <tr>
              <td><a href="{{ route('cqi.show', $a->cqi_record_id) }}" style="font-weight:600">{{ $a->reference }}</a></td>
              <td>{{ $a->name_en }}</td>
              <td>{{ $a->description }}</td>
              <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
              <td><x-pill :s="__(config('monitoring.action_status')[$a->status] ?? $a->status)" /></td>
            </tr>
          @empty
            <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No open improvement actions.') }}</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===================== TAB 3: PERIODIC REVIEWS & GOVERNANCE ===================== --}}
  <div id="tab-periodic" class="tab-pane" style="display:none">
    <div class="note info" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px 16px;border-left:4px solid #0284c7;background:#f0f9ff">
      <div style="font-size:20px;color:#0284c7;flex:none">
        <svg viewBox="0 0 16 16" width="20" height="20" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm0 2.5a1 1 0 110 2 1 1 0 010-2zM9 12H7V7h2v5z"/></svg>
      </div>
      <div>
        <div style="font-weight:700;font-size:14px;color:#0369a1">{{ __('Institutional Accreditation & Cyclical Reviews') }}</div>
        <div class="muted s" style="font-size:12px;color:#0284c7">{{ __('Monitor Senate-mandated periodic review cycles, Quality Verification (QV) standings for SAMCs, and faculty governance orders.') }}</div>
      </div>
    </div>

    {{-- Periodic reviews --}}
    <div class="panel" style="margin-bottom:24px">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <h2>{{ __('Periodic reviews') }}</h2>
          <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Senate-mandated cyclical review cycles for micro-credentials.') }}</div>
        </div>
        <span class="tag info">{{ count($due) }} {{ __('scheduled') }}</span>
      </div>
      <div class="bd" style="padding:0">
        <table id="periodic">
          <thead>
            <tr>
              <th style="width:35%">{{ __('Micro-credential') }}</th>
              <th style="width:25%">{{ __('Due') }}</th>
              <th style="width:40%">{{ __('Outcome') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($due as $p)
            <tr>
              <td><b>{{ $p->name }}</b></td>
              <td>
                {{ \Carbon\Carbon::parse($p->due_on)->format('d/m/Y') }}
                @if($p->due_on <= today()->toDateString())
                  <span class="tag overdue">{{ __('Due') }}</span>
                @endif
              </td>
              <td>
                <form method="post" action="{{ route('reviews.periodic', $p->id) }}" class="row" style="gap:8px;align-items:center">
                  @csrf
                  <select name="outcome" aria-label="Outcome for {{ $p->name }}" style="max-width:180px">
                    @foreach(config('monitoring.review_outcome') as $v => $t)
                      <option value="{{ $v }}">{{ $t }}</option>
                    @endforeach
                  </select>
                  <input type="text" name="note" aria-label="Note for {{ $p->name }}" placeholder="Note" style="flex:1">
                  <button type="submit" class="btn sm" aria-label="Record review outcome for {{ $p->name }}">{{ __('Record') }}</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="muted" style="text-align:center;padding:20px">{{ __('No review is scheduled.') }}</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Quality Verification standing --}}
    <div class="panel" style="margin-bottom:24px">
      <div class="hd">
        <h2>{{ __('Quality Verification standing') }}</h2>
        <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Validities and standing for Quality-Verified Micro-Credentials (SAMC).') }}</div>
      </div>
      <div class="bd">
        <ul id="qv-expiries" style="margin:0;padding-left:18px">
          @forelse($qv as $x)
            <li style="margin-bottom:6px">
              <b>{{ $x['name'] }}</b>: {{ __('valid until') }} {{ \Carbon\Carbon::parse($x['standing']->valid_until)->format('d/m/Y') }}
            </li>
          @empty
            <li class="muted">{{ __('None in force.') }}</li>
          @endforelse
        </ul>
      </div>
    </div>

    {{-- Governance actions & active suspensions --}}
    @if(auth()->user()?->hasAnyRole('deputy_dean'))
      <div class="panel" style="margin-bottom:24px">
        <div class="hd">
          <h2>{{ __('Governance actions') }}</h2>
          <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Formal suspension or termination powers for faculty micro-credentials.') }}</div>
        </div>
        <div class="bd">
          <a class="btn ter sm" href="{{ route('suspensions') }}">{{ __('Suspension or termination') }}</a>
        </div>
      </div>
    @endif

    @if($suspensions->count())
      <div class="panel">
        <div class="hd"><h2>{{ __('Suspensions and terminations') }}</h2></div>
        <div class="bd">
          <ul style="margin:0;padding-left:18px">
            @foreach($suspensions as $s)
              <li>
                <b>{{ $s->name }}</b>: {{ $s->kind }} {{ __('from') }} {{ \Carbon\Carbon::parse($s->effective_from)->format('d/m/Y') }}
                @if($s->lifted_at) &mdash; <span class="tag ok">{{ __('Lifted') }}</span> @endif
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif
  </div>
</div>

<script>
function filterCohortCourse(val) {
  var rows = document.querySelectorAll('.cohort-row');
  var visibleCount = 0;
  
  rows.forEach(function(row) {
    var mcId = row.getAttribute('data-mc');
    if (val === 'all' || mcId === val) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  var emptyRow = document.getElementById('cohort-filter-empty');
  if (emptyRow) {
    emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Tab Switching Logic
  var tabButtons = document.querySelectorAll('.tab-btn');
  var tabPanes = document.querySelectorAll('.tab-pane');

  function switchTab(targetId) {
    tabButtons.forEach(function(btn) {
      var isTarget = btn.getAttribute('data-tab') === targetId;
      btn.classList.toggle('active', isTarget);
      btn.style.borderBottomColor = isTarget ? 'var(--accent, #1e2a6e)' : 'transparent';
      btn.style.color = isTarget ? 'var(--accent, #1e2a6e)' : 'var(--ink-muted)';
    });

    tabPanes.forEach(function(pane) {
      pane.style.display = (pane.id === targetId) ? 'block' : 'none';
    });
  }

  tabButtons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var targetId = this.getAttribute('data-tab');
      switchTab(targetId);
      if (history.replaceState) {
        history.replaceState(null, null, '#' + targetId);
      }
    });
  });

  // Activate tab from URL hash if present
  var currentHash = window.location.hash ? window.location.hash.substring(1) : '';
  if (currentHash && document.getElementById(currentHash)) {
    switchTab(currentHash);
  }

  // Course Filter initialization
  var params = new URLSearchParams(window.location.search);
  var selected = params.get('course') || params.get('mc');
  if (selected) {
    var select = document.getElementById('course-filter');
    if (select) {
      select.value = selected;
      filterCohortCourse(selected);
    }
  }
});
</script>
@endsection
