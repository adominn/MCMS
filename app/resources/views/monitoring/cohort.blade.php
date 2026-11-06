@extends('layouts.app')
@section('title', __('Quality indicators: :name', ['name' => $o->name_en]))
@section('content')
@php
  $rowsByKey = collect($ind['rows'])->keyBy('key');
  $passVal = $rowsByKey['pass']['value'] ?? null;
  $compVal = $rowsByKey['completion']['value'] ?? null;
  $partVal = $rowsByKey['participation']['value'] ?? null;
  $issueVal = $rowsByKey['issuance']['value'] ?? null;
@endphp

<!-- Breadcrumbs -->
<div class="crumb" style="margin-bottom:12px">
  <a href="{{ route('tasks') }}">{{ __('Home') }}</a>
  <span>/</span>
  <a href="{{ route('monitoring') }}">{{ __('Quality review (CQI)') }}</a>
  <span>/</span>
  <span>{{ $o->name_en }} ({{ $o->cohort_name }})</span>
</div>

<!-- Header & Top Actions -->
<div class="title" style="margin-bottom:20px">
  <div style="display:flex;align-items:flex-start;gap:16px">
    <x-badge :category="$o->category ?? 'samc'" :size="48" />
    <div>
      <h1 style="margin:0 0 4px">{{ $o->name_en }} &middot; {{ $o->cohort_name }}</h1>
      <div class="sub" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span>{{ $o->mc_code ?? ('MC-'.str_pad((string)($o->version_id ?? 1), 4, '0', STR_PAD_LEFT)) }}</span>
        &middot;
        <span class="tag neutral" style="font-size:10px">{{ strtoupper($o->category ?? 'SAMC') }}</span>
        &middot;
        <span>{{ $o->ptj_code ?? 'FTMK' }} &middot; {{ $o->ptj_name ?? '' }}</span>
        &middot;
        <span>{{ __('Owner:') }} <strong>{{ $o->owner_name ?? '-' }}</strong></span>
        &middot;
        <x-pill :s="__(config('offerings.status')[$o->status] ?? ucfirst($o->status))" />
      </div>
    </div>
  </div>
  <div class="act" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('monitoring') }}" class="btn sec sm">
      <span class="btn-lbl">&larr; {{ __('Back to Quality review') }}</span>
    </a>
    @if($record)
      <a href="{{ route('cqi.show', $record->id) }}" class="btn primary sm" title="{{ __('Open formal CQI Record') }}">
        <span class="btn-lbl">{{ __('Open CQI Record :ref', ['ref' => $record->reference]) }}</span> &rarr;
      </a>
    @elseif($isLecturer && $isOwner)
      <form method="post" action="{{ route('cqi.store') }}" style="display:inline;margin:0">
        @csrf
        <input type="hidden" name="offering_id" value="{{ $o->id }}">
        <button type="submit" class="btn primary sm" aria-label="Start a quality review of {{ $o->name_en }} {{ $o->cohort_name }}">
          <span class="btn-lbl">{{ __('Start Quality Review') }}</span> &rsaquo;
        </button>
      </form>
    @endif
    <a href="{{ route('offerings.participants', $o->id) }}" class="btn ter sm" title="{{ __('View enrolled participants') }}">
      <span class="btn-lbl">{{ __('Participants (:count)', ['count' => $ind['participants']]) }}</span>
    </a>
    <a href="{{ route('offerings.show', $o->id) }}" class="btn ter sm" title="{{ __('View cohort offering details') }}">
      <span class="btn-lbl">{{ __('Offering settings') }}</span>
    </a>
  </div>
</div>

<!-- KPI Overview Banner -->
<div class="kpis" style="margin-bottom:24px">
  <div class="kpi">
    <div class="l">{{ __('Participants') }}</div>
    <div class="v">{{ $ind['participants'] }}</div>
    <div class="s muted">{{ __('Active in cohort') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Indicator Standing') }}</div>
    <div class="v">
      @if($o->below > 0)
        <span class="tag overdue" style="font-size:14px;padding:3px 8px">{{ $o->below }} {{ __('below target') }}</span>
      @else
        <span class="tag ok" style="font-size:14px;padding:3px 8px">{{ __('All targets met') }}</span>
      @endif
    </div>
    <div class="s muted">{{ count($ind['rows']) }} {{ __('indicators evaluated') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Pass & Completion') }}</div>
    <div class="v" style="font-size:20px;font-weight:700">
      {{ $passVal !== null ? $passVal.'%' : 'n/a' }} <span class="s muted" style="font-size:13px;font-weight:400">/ {{ $compVal !== null ? $compVal.'%' : 'n/a' }}</span>
    </div>
    <div class="s muted">{{ __('Pass rate / Completion rate') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Average Mark') }}</div>
    <div class="v" style="font-size:20px;font-weight:700">
      {{ $ind['average'] !== null ? $ind['average'].'%' : 'n/a' }}
    </div>
    <div class="s muted">{{ __('Median:') }} {{ $ind['median'] !== null ? $ind['median'].'%' : 'n/a' }}</div>
  </div>
</div>

<!-- Carbon Horizontal Navigation Tabs -->
<div data-tabs style="margin-bottom:24px">
  <nav class="tabs" style="margin-bottom:16px;border-bottom:1px solid var(--rule);display:flex;gap:24px" role="tablist">
    <button type="button" class="tab-btn active" data-tab="tab-indicators" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid var(--accent, #1e2a6e);color:var(--accent, #1e2a6e);cursor:pointer">
      {{ __('Quality Indicators') }} ({{ count($ind['rows']) }})
    </button>
    <button type="button" class="tab-btn" data-tab="tab-outcomes" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
      {{ __('Learning Outcomes & Modules') }} ({{ count($ind['outcomes']) + count($ind['modules']) }})
    </button>
    <button type="button" class="tab-btn" data-tab="tab-feedback" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
      {{ __('Learner Feedback') }} ({{ $ind['feedback']['count'] ?? 0 }})
    </button>
    <button type="button" class="tab-btn" data-tab="tab-cqi" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
      {{ __('CQI Improvement Record') }} @if($record)<span class="tag info" style="font-size:10px;margin-left:4px">{{ $record->status }}</span>@endif
    </button>
  </nav>

  <!-- Tab 1: Quality Indicators Table -->
  <div id="tab-indicators" class="tab-pane" data-pane="tab-indicators">
    <div class="panel">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h2 style="margin:0">{{ __('Cohort Performance Indicators') }}</h2>
          <div class="s muted" style="margin-top:2px">{{ __('Evaluated against official UTeM institutional quality benchmarks.') }}</div>
        </div>
        <span class="r">
          <span class="s muted">{{ count($ind['rows']) }} {{ __('benchmarks') }}</span>
        </span>
      </div>
      <table id="indicators-detail-table">
        <thead>
          <tr>
            <th style="min-width:280px">{{ __('Quality Indicator') }}</th>
            <th class="num">{{ __('Actual Value') }}</th>
            <th class="num">{{ __('Target Benchmark') }}</th>
            <th>{{ __('Compliance Standing') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach($ind['rows'] as $r)
          <tr>
            <td>
              <strong style="color:var(--ink)">{{ __($r['label']) }}</strong>
              <div class="s muted" style="font-size:11px"><code>{{ $r['key'] }}</code></div>
            </td>
            <td class="num" style="font-size:15px;font-weight:700">
              {{ $r['value'] === null ? 'n/a' : $r['value'].'%' }}
            </td>
            <td class="num" style="font-size:14px">
              {{ $r['target'] }}%
            </td>
            <td>
              @if($r['below'])
                <span class="tag overdue">
                  <i class="bi bi-exclamation-triangle" style="margin-right:4px"></i>{{ __('Below target') }}
                </span>
              @elseif($r['value'] !== null)
                <span class="tag ok">
                  <i class="bi bi-check-circle" style="margin-right:4px"></i>{{ __('Target Met') }}
                </span>
              @else
                <span class="tag neutral">{{ __('Pending data') }}</span>
              @endif
            </td>
          </tr>
        @endforeach
        <tr style="background:var(--bg-subtle, #fbfbfb)">
          <td>
            <strong>{{ __('Average achievement mark') }}</strong>
            <div class="s muted">{{ __('Overall weighted grade across participants') }}</div>
          </td>
          <td class="num" style="font-size:15px;font-weight:700">
            {{ $ind['average'] !== null ? $ind['average'].'%' : 'n/a' }}
          </td>
          <td class="num" style="font-size:14px">
            {{ __('Median:') }} {{ $ind['median'] !== null ? $ind['median'].'%' : 'n/a' }}
          </td>
          <td>
            <span class="tag neutral">{{ __('Grade distribution') }}</span>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab 2: Learning Outcomes & Module Attainment -->
  <div id="tab-outcomes" class="tab-pane" style="display:none">
    <div class="panel" style="margin-bottom:20px">
      <div class="hd">
        <h2 style="margin:0">{{ __('Course Learning Outcome (CLO) Attainment') }}</h2>
        <div class="s muted" style="margin-top:2px">{{ __('Percentage of cohort participants achieving pass mark in mapped assessment components.') }}</div>
      </div>
      <table id="outcomes-table">
        <thead>
          <tr>
            <th style="width:100px">{{ __('CLO Code') }}</th>
            <th>{{ __('Outcome Statement') }}</th>
            <th class="num" style="width:160px">{{ __('Attainment Share') }}</th>
            <th style="width:140px">{{ __('Standing') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($ind['outcomes'] as $lo)
          <tr>
            <td><strong style="color:var(--accent, #1e2a6e)">{{ $lo['code'] }}</strong></td>
            <td>{{ $lo['text'] }}</td>
            <td class="num" style="font-size:15px;font-weight:700">
              {{ $lo['share'] !== null ? $lo['share'].'%' : 'n/a' }}
            </td>
            <td>
              @if($lo['share'] !== null && $lo['share'] >= 50)
                <span class="tag ok">{{ __('Attained') }}</span>
              @elseif($lo['share'] !== null)
                <span class="tag warn">{{ __('Needs CQI') }}</span>
              @else
                <span class="muted s">{{ __('No mapped data') }}</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No learning outcomes recorded.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="panel">
      <div class="hd">
        <h2 style="margin:0">{{ __('Modular Completion Progress') }}</h2>
        <div class="s muted" style="margin-top:2px">{{ __('Progress across micro-credential modules.') }}</div>
      </div>
      <table id="modules-table">
        <thead>
          <tr>
            <th>{{ __('Module Name') }}</th>
            <th class="num" style="width:180px">{{ __('Completion Rate') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($ind['modules'] as $m)
          <tr>
            <td><strong>{{ $m['name'] }}</strong></td>
            <td class="num" style="font-size:15px;font-weight:700">
              {{ $m['share'] !== null ? $m['share'].'%' : 'n/a' }}
            </td>
          </tr>
        @empty
          <tr><td colspan="2" class="muted" style="text-align:center;padding:24px">{{ __('No modular progress recorded.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab 3: Learner Feedback Evaluation -->
  <div id="tab-feedback" class="tab-pane" style="display:none">
    <div class="panel" style="margin-bottom:20px">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <h2 style="margin:0">{{ __('Learner Feedback Summary') }}</h2>
          <div class="s muted" style="margin-top:2px">{{ __('Anonymous course evaluations submitted by completed cohort participants.') }}</div>
        </div>
        <span class="tag info">{{ $ind['feedback']['count'] ?? 0 }} {{ __('submissions') }}</span>
      </div>
      <div class="bd">
        @if(($ind['feedback']['count'] ?? 0) > 0)
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:20px">
            @foreach($ind['feedback']['areas'] as $areaKey => $score)
              <div style="background:var(--bg-subtle, #f4f4f4);padding:14px 18px;border:1px solid var(--rule)">
                <div class="s muted">{{ __(config('monitoring.feedback_areas')[$areaKey] ?? ucfirst($areaKey)) }}</div>
                <div style="font-size:24px;font-weight:700;color:var(--ink);margin:4px 0">
                  {{ $score !== null ? number_format($score, 1) : 'n/a' }} <span class="s muted" style="font-size:13px;font-weight:400">/ 5.0</span>
                </div>
                <div class="s">
                  @if($score >= 4.0)
                    <span class="tag ok" style="font-size:10px">{{ __('Satisfactory') }}</span>
                  @elseif($score !== null)
                    <span class="tag warn" style="font-size:10px">{{ __('Attention Needed') }}</span>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          @if(!empty($ind['feedback']['comments']))
            <h3 style="font-size:14px;font-weight:700;margin:20px 0 10px">{{ __('Learner Comments & Suggestions') }}</h3>
            <div style="display:flex;flex-direction:column;gap:10px">
              @foreach($ind['feedback']['comments'] as $cmt)
                <div style="padding:12px 16px;background:var(--bg-subtle, #fbfbfb);border-left:3px solid var(--accent, #1e2a6e);font-size:13px;line-height:1.5">
                  &ldquo;{{ $cmt }}&rdquo;
                </div>
              @endforeach
            </div>
          @endif
        @else
          <div style="text-align:center;padding:36px;color:var(--ink-muted)">
            {{ __('No feedback responses received yet for this cohort.') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Tab 4: CQI Improvement Record -->
  <div id="tab-cqi" class="tab-pane" style="display:none">
    <div class="panel">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h2 style="margin:0">{{ __('Continuous Quality Improvement (CQI) Record') }}</h2>
          <div class="s muted" style="margin-top:2px">{{ __('Formal cycle: issues identified, action plans formulated, and deputy dean confirmation.') }}</div>
        </div>
        @if($record)
          <span class="r">
            <x-pill :s="__(['draft' => 'Draft', 'submitted' => 'Submitted to Deputy Dean', 'confirmed' => 'Confirmed'][$record->status] ?? ucfirst($record->status))" />
            <span class="tag neutral" style="margin-left:6px">{{ $record->reference }}</span>
          </span>
        @endif
      </div>
      <div class="bd">
        @if($record)
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding:12px 16px;background:var(--bg-subtle, #f4f4f4);border:1px solid var(--rule)">
            <div>
              <strong>{{ __('CQI Record Reference:') }} {{ $record->reference }}</strong>
              <div class="s muted">{{ __('Status:') }} {{ ucfirst($record->status) }} &middot; {{ __('Opened on:') }} {{ \Carbon\Carbon::parse($record->created_at)->format('d/m/Y') }}</div>
            </div>
            <a href="{{ route('cqi.show', $record->id) }}" class="btn primary sm">
              <span class="btn-lbl">{{ __('Open Full CQI Workspace') }}</span> &rarr;
            </a>
          </div>

          <h3 style="font-size:14px;font-weight:700;margin:16px 0 8px">{{ __('Improvement Actions (:count)', ['count' => $actions->count()]) }}</h3>
          <table>
            <thead>
              <tr>
                <th>{{ __('Action Description') }}</th>
                <th>{{ __('Responsible') }}</th>
                <th>{{ __('Due Date') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($actions as $a)
              <tr>
                <td><strong>{{ $a->description }}</strong></td>
                <td>{{ $a->responsible }}</td>
                <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
                <td><x-pill :s="__(config('monitoring.action_status')[$a->status] ?? $a->status)" /></td>
              </tr>
            @empty
              <tr><td colspan="4" class="muted" style="text-align:center;padding:20px">{{ __('No specific improvement actions recorded yet.') }}</td></tr>
            @endforelse
            </tbody>
          </table>
        @else
          <div style="text-align:center;padding:36px;color:var(--ink-muted)">
            <p style="margin:0 0 16px;font-size:14px">{{ __('No formal CQI record opened yet for this cohort.') }}</p>
            @if($isLecturer && $isOwner)
              <form method="post" action="{{ route('cqi.store') }}" style="display:inline;margin:0">
                @csrf
                <input type="hidden" name="offering_id" value="{{ $o->id }}">
                <button type="submit" class="btn primary sm" aria-label="Start a quality review of {{ $o->name_en }} {{ $o->cohort_name }}">
                  <span class="btn-lbl">{{ __('Start Quality Review (CQI)') }}</span> &rsaquo;
                </button>
              </form>
            @else
              <span class="muted s">{{ __('The course owner lecturer will initiate the CQI review upon cohort completion.') }}</span>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Bottom Navigation Bar -->
<div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--rule);display:flex;justify-content:space-between;align-items:center">
  <a href="{{ route('monitoring') }}" class="btn sec sm">
    <span class="btn-lbl">&larr; {{ __('Back to Quality review') }}</span>
  </a>
  <span class="s muted">{{ __('Cohort ID: :id · Reference: :ref', ['id' => $o->id, 'ref' => $o->cohort_name]) }}</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

  var currentHash = window.location.hash ? window.location.hash.substring(1) : '';
  if (currentHash && document.getElementById(currentHash)) {
    switchTab(currentHash);
  }
});
</script>
@endsection
