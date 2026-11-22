@extends('layouts.app')
@section('title', __('Verify results'))
@section('content')
<x-breadcrumb :crumbs="[
  'Results and completion' => route('results'),
  $o->name_en . ' ' . $o->cohort_name => null,
]" />

<div class="ph" style="margin-bottom:20px">
  <div>
    <h1>{{ __('Verify results: :name (:cohort)', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">
      @if(!empty($o->lecturer_name))<b>Lecturer:</b> {{ $o->lecturer_name }} &middot; @endif
      {{ $o->results_submitted_at ? __('Submitted on :date', ['date' => \Carbon\Carbon::parse($o->results_submitted_at)->format('d/m/Y H:i')]) : __('Not submitted by the lecturer yet') }}
    </div>
  </div>
  <div class="act" style="gap:8px;display:flex;align-items:center">
    <a class="btn ter sm" href="{{ route('completion.show', $o->id) }}">{{ __('Completion overview') }}</a>
    <a class="btn ghost sm" href="{{ route('results') }}">&larr; {{ __('All cohorts') }}</a>
  </div>
</div>

<div data-tabs style="margin-top:16px">
  <nav class="tabs" aria-label="Verification subsections">
    <button data-tab="summary">
      {{ __('Summary') }}
    </button>
    @if($cases->isNotEmpty())
      <button data-tab="cases">
        {{ __('Open integrity cases') }} <span class="tag err" style="font-size:10.5px;padding:1px 6px;margin-left:4px">{{ $cases->count() }}</span>
      </button>
    @endif
    @if($changes->isNotEmpty())
      <button data-tab="changes">
        {{ __('Result changes waiting') }} <span class="tag warn" style="font-size:10.5px;padding:1px 6px;margin-left:4px">{{ $changes->count() }}</span>
      </button>
    @endif
    <button data-tab="gradebook">
      {{ __('Participant Gradebook') }}
    </button>
    <button data-tab="verify">
      {{ __('Verify & Confirm') }}
      @if($summary->sum('provisional') > 0)
        <span class="tag warn" style="font-size:10.5px;padding:1px 6px;margin-left:4px">{{ $summary->sum('provisional') }}</span>
      @endif
    </button>
  </nav>

  {{-- SUBSECTION 1: SUMMARY --}}
  <div data-pane="summary" class="panel">
    <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
      <div>
        <h2 style="margin:0">{{ __('Summary') }} &mdash; {{ __('Verification Progress & Assessment Overview') }}</h2>
        <div class="sub" style="font-size:12.5px;color:var(--text-secondary);margin-top:2px">
          {{ __('Component benchmarks, weighting, provisional marks, and pass rates grouped by module.') }}
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        @if($summary->sum('provisional') > 0)
          <span class="tag warn"><b>{{ $summary->sum('provisional') }}</b> {{ __('Awaiting verification') }}</span>
        @endif
        @if($summary->sum('held') > 0)
          <span class="tag err"><b>{{ $summary->sum('held') }}</b> {{ __('Held') }}</span>
        @endif
        @if($summary->sum('verified') > 0)
          <span class="tag ok"><b>{{ $summary->sum('verified') }}</b> {{ __('Verified') }}</span>
        @endif
      </div>
    </div>
    <div class="bd" style="padding:0">
      <table id="summary" style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:var(--table-header-bg, #f4f4f4);border-bottom:2px solid var(--rule, #e0e0e0)">
            <th style="padding:10px 14px;text-align:left;font-weight:600">{{ __('Assessment Component') }}</th>
            <th style="padding:10px 14px;text-align:center;width:90px;font-weight:600">{{ __('Weight') }}</th>
            <th style="padding:10px 14px;text-align:center;width:110px;font-weight:600">{{ __('Pass mark') }}</th>
            <th style="padding:10px 14px;text-align:center;width:90px;font-weight:600">{{ __('Marks') }}</th>
            <th style="padding:10px 14px;text-align:center;width:120px;font-weight:600">{{ __('Provisional') }}</th>
            <th style="padding:10px 14px;text-align:center;width:90px;font-weight:600">{{ __('Held') }}</th>
            <th style="padding:10px 14px;text-align:center;width:100px;font-weight:600">{{ __('Verified') }}</th>
            <th style="padding:10px 14px;text-align:center;width:110px;font-weight:600">{{ __('Pass rate') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach($summary->groupBy(fn($item) => $item['c']->mc_module_id ?? 'whole') as $modKey => $items)
          @php($mod = $modKey === 'whole' ? null : $modules->firstWhere('id', $modKey))
          <tr style="background:var(--bg-subtle, #f4f4f4);border-top:1px solid var(--rule, #e0e0e0);border-bottom:1px solid var(--rule, #e0e0e0)">
            <td colspan="8" style="padding:8px 14px;font-size:12.5px;font-weight:700">
              @if($modKey === 'whole')
                <span class="tag muted" style="font-size:10.5px;text-transform:uppercase;padding:2px 6px">{{ __('Whole MC') }}</span>
                <span>{{ __('Whole Micro-Credential / Integrated Capstone Project') }}</span>
              @else
                <span class="tag info" style="font-size:10.5px;text-transform:uppercase;padding:2px 6px">{{ __('Module :seq', ['seq' => $mod->seq ?? '']) }}</span>
                <span>{{ $mod->name_en ?? __('Modular Assessment') }}</span>
                @if(!empty($mod->slt_hours))
                  <span class="muted" style="font-weight:400;font-size:12px">({{ $mod->slt_hours }}h SLT)</span>
                @endif
              @endif
            </td>
          </tr>
          @foreach($items as $s)
            <tr style="border-bottom:1px solid var(--rule-light, #f0f0f0)">
              <td style="padding:10px 14px 10px 24px">
                <div style="font-weight:600">{{ $s['c']->name_en }}</div>
                <span style="display:none" class="csv-key-hidden">{{ $keys[$s['c']->id] ?? $s['c']->csv_key ?? '' }}</span>
              </td>
              <td style="padding:10px 14px;text-align:center">
                <span class="tag muted">{{ mcms_num($s['c']->weight_percent) }}%</span>
              </td>
              <td style="padding:10px 14px;text-align:center;font-size:12px">
                {{ mcms_num($s['c']->pass_mark) }} / {{ mcms_num($s['c']->max_mark) }}
              </td>
              <td style="padding:10px 14px;text-align:center;font-weight:600">{{ $s['n'] }}</td>
              <td style="padding:10px 14px;text-align:center">
                @if($s['provisional'] > 0)
                  <span class="tag warn">{{ $s['provisional'] }}</span>
                @else
                  <span class="muted">0</span>
                @endif
              </td>
              <td style="padding:10px 14px;text-align:center">
                @if($s['held'] > 0)
                  <span class="tag err">{{ $s['held'] }}</span>
                @else
                  <span class="muted">0</span>
                @endif
              </td>
              <td style="padding:10px 14px;text-align:center">
                @if($s['verified'] > 0)
                  <span class="tag ok">{{ $s['verified'] }}</span>
                @else
                  <span class="muted">0</span>
                @endif
              </td>
              <td style="padding:10px 14px;text-align:center">
                @if($s['pass_rate'] === null)
                  <span class="muted">-</span>
                @else
                  <span class="tag {{ $s['pass_rate'] >= 75 ? 'ok' : ($s['pass_rate'] >= 50 ? 'warn' : 'err') }}">{{ $s['pass_rate'] }}%</span>
                @endif
              </td>
            </tr>
          @endforeach
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- SUBSECTION 2: INTEGRITY CASES --}}
  @if($cases->isNotEmpty())
    <div data-pane="cases" class="panel" style="border-left:4px solid var(--danger, #da1e28)">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <h2 style="margin:0">{{ __('Open integrity cases') }}</h2>
          <div class="sub" style="font-size:12.5px;color:var(--text-secondary);margin-top:2px">
            {{ __('Results for flagged students are frozen (Held) and cannot be verified until resolved by the Deputy Dean.') }}
          </div>
        </div>
        <span class="tag err" style="font-weight:600">{{ $cases->count() }} {{ __('case(s) open') }}</span>
      </div>
      <div class="bd">
        @foreach($cases as $c)
          <div class="panel" style="margin-bottom:12px;background:var(--surface, #ffffff);border:1px solid var(--rule, #e0e0e0)">
            <div class="bd" style="padding:14px 16px">
              <div style="margin-bottom:10px;font-size:13.5px">
                <b>{{ $c->name }}</b>: <span style="color:var(--text-secondary)">{{ $c->reason }}</span>
              </div>
              <form method="post" action="{{ route('results.case', [$o->id, $c->id]) }}" class="row" style="gap:10px;align-items:center;flex-wrap:wrap">
                @csrf
                <input type="text" name="reason" placeholder="{{ __('State resolution outcome (e.g. Investigation complete, penalty cleared / applied)') }}" required minlength="5" aria-label="Resolution for {{ $c->name }}" style="flex:1;min-width:300px;height:38px;padding:0 12px;font-size:13px">
                <button type="submit" class="btn sm">{{ __('Resolve case & release hold') }}</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- SUBSECTION 3: CHANGE REQUESTS --}}
  @if($changes->isNotEmpty())
    <div data-pane="changes" class="panel" style="border-left:4px solid var(--warning, #f1c21b)">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <h2 style="margin:0">{{ __('Result changes waiting') }}</h2>
          <div class="sub" style="font-size:12.5px;color:var(--text-secondary);margin-top:2px">
            {{ __('The lecturer has requested an amendment to an already-verified mark.') }}
          </div>
        </div>
        <span class="tag warn" style="font-weight:600">{{ $changes->count() }} {{ __('request(s)') }}</span>
      </div>
      <div class="bd">
        @foreach($changes as $q)
          <div class="panel" style="margin-bottom:12px;background:var(--surface, #ffffff);border:1px solid var(--rule, #e0e0e0)">
            <div class="bd" style="padding:14px 16px">
              <div style="margin-bottom:10px;font-size:13.5px">
                <b>{{ $q->name }}</b> &mdash; <span>{{ $q->component }}</span>: 
                <span class="tag muted">{{ $q->old_score }}</span> &rarr; <span class="tag ok" style="font-weight:700">{{ $q->new_score }}</span>
                <div class="muted s" style="margin-top:4px;font-size:12px">{{ __('Lecturer reason: :reason', ['reason' => $q->reason]) }}</div>
              </div>
              <form method="post" action="{{ route('results.decide', [$o->id, $q->id]) }}" class="row" style="gap:10px;align-items:center;flex-wrap:wrap">
                @csrf
                <input type="text" name="note" placeholder="{{ __('Deputy dean decision note') }}" required minlength="5" aria-label="Note on the change for {{ $q->name }}" style="flex:1;min-width:260px;height:38px;padding:0 12px;font-size:13px">
                <button type="submit" name="decision" value="approve" class="btn sm">{{ __('Approve change') }}</button>
                <button type="submit" name="decision" value="reject" class="btn ter sm">{{ __('Reject') }}</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- SUBSECTION 4: GRADEBOOK --}}
  <div data-pane="gradebook" class="panel">
    <div class="hd">
      <h2 style="margin:0">{{ __('Participant Gradebook Matrix') }}</h2>
      <div class="sub" style="font-size:12.5px;color:var(--text-secondary);margin-top:2px">
        {{ __('Detailed mark breakdown per participant with status flags.') }}
      </div>
    </div>
    <div class="bd" style="padding:0">
      @include('results._table', ['flag' => false, 'editable' => false, 'changeable' => false])
    </div>
  </div>

  {{-- SUBSECTION 5: VERIFY & CONFIRM --}}
  <div data-pane="verify">
    <form method="post" action="{{ route('results.verify.do', $o->id) }}">
      @csrf
      <div class="panel" style="border-top:3px solid var(--primary, #0f62fe)">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
          <div>
            <h2 style="margin:0">{{ __('Statutory Verification & Badge Confirmation') }}</h2>
            <div class="sub" style="font-size:12.5px;color:var(--text-secondary);margin-top:2px">
              {{ __('Verifying results confirms module achievements and auto-mints Open Badges 3.0 digital badges. It does not complete the overall micro-credential or issue final certificates.') }}
            </div>
          </div>
          <span class="tag {{ $summary->sum('provisional') > 0 ? 'warn' : 'ok' }}">
            {{ $summary->sum('provisional') }} {{ __('provisional waiting') }}
          </span>
        </div>
        <div class="bd">
          <fieldset style="border:0;padding:0;margin-bottom:20px">
            <legend style="font-weight:600;margin-bottom:12px;font-size:13px;color:var(--ink)">
              {{ __('Select modules to verify (leave unselected to verify all pending):') }}
            </legend>
            <div style="display:flex;flex-direction:column;gap:10px">
              @foreach($modules as $m)
                @php($mPending = $summary->filter(fn($s) => ($s['c']->mc_module_id == $m->id))->sum('provisional'))
                <label style="display:flex;align-items:center;gap:16px;padding:12px 18px;background:var(--card-bg, #ffffff);border:1px solid var(--rule, #e0e0e0);border-radius:2px;cursor:{{ $mPending > 0 ? 'pointer' : 'default' }};transition:border-color 0.15s, box-shadow 0.15s">
                  <input type="checkbox" name="scopes[]" value="m{{ $m->id }}" @disabled($mPending === 0) style="margin:0;width:18px;height:18px;cursor:{{ $mPending > 0 ? 'pointer' : 'default' }}">
                  
                  <div style="flex-shrink:0" title="Module {{ $m->seq }} {{ __('Digital Badge') }}">
                    <x-badge :category="$o->category ?? 'umc'" :size="40" :seq="$m->seq" :image="$o->badge_image_path ?? null" />
                  </div>
                  
                  <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                      <span style="font-weight:700;font-size:13.5px;color:var(--ink)">
                        Module {{ $m->seq }}: {{ $m->name_en }}
                      </span>
                      @if(!empty($m->slt_hours))
                        <span class="tag neutral" style="font-size:10.5px;padding:1px 6px">{{ $m->slt_hours }}h SLT</span>
                      @endif
                    </div>
                    <div class="s muted" style="margin-top:2px;display:flex;align-items:center;gap:6px;font-size:12px">
                      <i class="bi bi-award" style="color:var(--accent, #0f62fe)"></i>
                      <span>{{ __('Mints Open Badges 3.0 on verification') }}</span>
                    </div>
                  </div>

                  <div style="flex-shrink:0;text-align:right">
                    @if($mPending > 0)
                      <span class="tag warn" style="font-size:11px;font-weight:600;padding:3px 10px">
                        {{ $mPending }} {{ __('provisional waiting') }}
                      </span>
                    @else
                      <span class="tag ok" style="font-size:11px;font-weight:600;padding:3px 10px">
                        <i class="bi bi-check2" style="margin-right:3px"></i>{{ __('Fully verified') }}
                      </span>
                    @endif
                  </div>
                </label>
              @endforeach

              @if($hasWhole)
                @php($mcPending = $summary->filter(fn($s) => empty($s['c']->mc_module_id))->sum('provisional'))
                <label style="display:flex;align-items:center;gap:16px;padding:12px 18px;background:var(--card-bg, #ffffff);border:1px solid var(--rule, #e0e0e0);border-radius:2px;cursor:{{ $mcPending > 0 ? 'pointer' : 'default' }};transition:border-color 0.15s, box-shadow 0.15s">
                  <input type="checkbox" name="scopes[]" value="mc" @disabled($mcPending === 0) style="margin:0;width:18px;height:18px;cursor:{{ $mcPending > 0 ? 'pointer' : 'default' }}">
                  
                  <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                      <span style="font-weight:700;font-size:13.5px;color:var(--ink)">
                        {{ __('Whole micro-credential assessment') }}
                      </span>
                      <span class="tag neutral" style="font-size:10.5px;padding:1px 6px">{{ __('Integrated Capstone') }}</span>
                      <span class="tag muted" style="font-size:10.5px;padding:1px 6px">{{ __('No modular badge') }}</span>
                    </div>
                    <div class="s muted" style="margin-top:2px;display:flex;align-items:center;gap:6px;font-size:12px">
                      <i class="bi bi-mortarboard" style="color:var(--accent, #0f62fe)"></i>
                      <span>{{ __('Integrated Capstone project · Required for certificate and academic credits') }}</span>
                    </div>
                  </div>

                  <div style="flex-shrink:0;text-align:right">
                    @if($mcPending > 0)
                      <span class="tag warn" style="font-size:11px;font-weight:600;padding:3px 10px">
                        {{ $mcPending }} {{ __('provisional waiting') }}
                      </span>
                    @else
                      <span class="tag ok" style="font-size:11px;font-weight:600;padding:3px 10px">
                        <i class="bi bi-check2" style="margin-right:3px"></i>{{ __('Fully verified') }}
                      </span>
                    @endif
                  </div>
                </label>
              @endif
            </div>
          </fieldset>

          {{-- Collapsible Participant-Level Filter --}}
          @php($participantsList = $enrol ?? collect())
          <details style="margin-bottom:24px;border:1px solid var(--rule, #e0e0e0);border-radius:4px;background:var(--surface, #ffffff)">
            <summary style="padding:10px 14px;font-weight:600;font-size:13px;cursor:pointer;user-select:none;display:flex;align-items:center;justify-content:space-between">
              <span>
                {{ __('Filter specific participants (optional)') }}
                <span class="muted s" style="font-weight:400;margin-left:6px">{{ __('All :count participants auto-selected by default', ['count' => $participantsList->count()]) }}</span>
              </span>
              <span class="tag info" style="font-size:11px">{{ __('Click to collapse / expand') }}</span>
            </summary>
            <div style="padding:14px;border-top:1px solid var(--rule, #e0e0e0);background:var(--bg-subtle, #fbfbfb)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <span style="font-size:12px;color:var(--text-secondary)">
                  {{ __('Untick any participant you wish to exclude from this verification run.') }}
                </span>
                <div style="display:flex;gap:8px">
                  <button type="button" class="btn ghost sm" id="btn-select-all-students" style="font-size:11px;padding:3px 8px">{{ __('Select all') }}</button>
                  <button type="button" class="btn ghost sm" id="btn-deselect-all-students" style="font-size:11px;padding:3px 8px">{{ __('Deselect all') }}</button>
                </div>
              </div>
              <div class="row" style="gap:10px;flex-wrap:wrap">
                @foreach($participantsList as $p)
                  <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;background:var(--card-bg, #ffffff);padding:6px 12px;border:1px solid var(--rule, #e0e0e0);border-radius:2px;cursor:pointer">
                    <input type="checkbox" name="enrolments[]" value="{{ $p->id }}" class="student-checkbox" checked>
                    <span>
                      <b>{{ $p->name }}</b>
                      @if(!empty($p->institution_id))
                        <span class="muted s" style="font-size:11px">({{ $p->institution_id }})</span>
                      @endif
                    </span>
                  </label>
                @endforeach
              </div>
            </div>
          </details>

          <div class="field" style="margin-bottom:20px;max-width:560px">
            <label for="evidence" style="font-weight:600;font-size:13px;margin-bottom:6px;display:block">
              {{ __('Audit evidence linking identity, assessment and result') }} <span style="color:var(--danger, #da1e28)">*</span>
            </label>
            <input type="text" id="evidence" name="evidence" required minlength="5" placeholder="{{ __('e.g. UCreds proctoring log and photo ID check') }}" style="width:100%;height:38px;padding:0 12px;font-size:13px">
            <div class="muted s" style="font-size:11.5px;margin-top:4px">{{ __('Required for MQA compliance and Academic Bank of Credit (ABC) audit traceability.') }}</div>
          </div>

          <div class="row" style="gap:12px;align-items:center">
            <button type="submit" id="btn-verify-submit" class="btn" @disabled(! $o->results_submitted_at || $summary->sum('provisional') === 0)>
              <span class="btn-lbl" id="btn-verify-label">{{ __('Verify :count results', ['count' => $summary->sum('provisional')]) }}</span> &rarr;
            </button>
            @if(! $o->results_submitted_at)
              <span class="muted s" style="font-size:12px">{{ __('Waiting for the lecturer to submit results.') }}</span>
            @elseif($summary->sum('provisional') === 0)
              <span class="muted s" style="font-size:12px">{{ __('All provisional results for this cohort have been verified.') }}</span>
            @endif
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  (function() {
    var form = document.querySelector('form[action*="verify"]');
    if (!form) return;
    var moduleCheckboxes = form.querySelectorAll('input[type="checkbox"][name="scopes[]"]');
    var studentCheckboxes = form.querySelectorAll('input[type="checkbox"][name="enrolments[]"]');
    var btnLabel = document.getElementById('btn-verify-label');
    var btnAllStudents = document.getElementById('btn-select-all-students');
    var btnNoneStudents = document.getElementById('btn-deselect-all-students');

    if (btnAllStudents) {
      btnAllStudents.addEventListener('click', function() {
        studentCheckboxes.forEach(function(cb) { cb.checked = true; });
        updateCount();
      });
    }
    if (btnNoneStudents) {
      btnNoneStudents.addEventListener('click', function() {
        studentCheckboxes.forEach(function(cb) { cb.checked = false; });
        updateCount();
      });
    }

    var totalProvisional = {{ $summary->sum('provisional') }};
    var scopeCounts = @json($modules->mapWithKeys(fn($m) => ['m' . $m->id => $summary->filter(fn($s) => ($s['c']->mc_module_id == $m->id))->sum('provisional')])->merge(['mc' => $summary->filter(fn($s) => empty($s['c']->mc_module_id))->sum('provisional')]));
    var totalStudents = {{ (isset($enrol) ? $enrol->count() : 1) ?: 1 }};

    function updateCount() {
      var selectedModules = Array.from(moduleCheckboxes).filter(function(cb) { return cb.checked && !cb.disabled; });
      var selectedStudents = Array.from(studentCheckboxes).filter(function(cb) { return cb.checked; });
      
      var baseCount = 0;
      if (selectedModules.length === 0) {
        baseCount = totalProvisional;
      } else {
        selectedModules.forEach(function(cb) {
          baseCount += (scopeCounts[cb.value] || 0);
        });
      }

      var finalCount = baseCount;
      if (studentCheckboxes.length > 0) {
        var studentRatio = selectedStudents.length / totalStudents;
        finalCount = Math.round(baseCount * studentRatio);
      }

      if (btnLabel) {
        btnLabel.textContent = 'Verify ' + finalCount + ' ' + (finalCount === 1 ? 'result' : 'results');
      }
    }

    moduleCheckboxes.forEach(function(cb) {
      cb.addEventListener('change', updateCount);
    });
    studentCheckboxes.forEach(function(cb) {
      cb.addEventListener('change', updateCount);
    });
  })();
</script>
@endsection
