@extends('layouts.app')
@section('title', $programme->code.' '.__('Degree Plan'))
@section('content')
<x-breadcrumb :crumbs="[
  __('Degree Programmes') => route('programmes.index'),
  $programme->code => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
      <span class="tag info" style="font-family:var(--font-mono)"><strong>{{ $programme->code }}</strong></span>
      @if(!empty($programme->faculty_code))
        <span class="tag neutral">{{ $programme->faculty_code }}</span>
      @endif
      <span class="tag neutral">MQF {{ $programme->mqf_level }}</span>
      @if($plan['version'])
        <span class="tag ok">{{ __('Session') }} {{ $plan['version']->academic_session }}</span>
      @endif
    </div>
    <h1 style="margin:0">{{ $programme->name }}</h1>
    @if(!empty($programme->faculty_name))
      <div class="sub">{{ $programme->faculty_name }}</div>
    @endif
  </div>
  <div class="act" style="display:flex;gap:8px;flex-wrap:wrap">
    @if($plan['versions']->count() > 1)
      <form method="get" action="{{ route('programmes.show', $programme->code) }}" style="display:inline">
        <select name="version" onchange="this.form.submit()" aria-label="{{ __('Academic session') }}" style="height:34px;padding:0 10px">
          @foreach($plan['versions'] as $ver)
            <option value="{{ $ver->id }}" @selected($plan['version'] && $plan['version']->id === $ver->id)>
              {{ $ver->academic_session }} ({{ ucfirst($ver->status) }})
            </option>
          @endforeach
        </select>
      </form>
    @endif
    <a class="btn ter sm" href="{{ route('programmes.compare', ['from' => $programme->code]) }}">{{ __('Compare Programme') }}</a>
    <a class="btn ter sm" href="{{ route('programmes.show', ['code' => $programme->code, 'version' => $plan['version']->id ?? null, 'print' => 1]) }}" target="_blank">{{ __('Print PDF') }}</a>
    @if($canManage)
      <a class="btn sec sm" href="{{ route('programmes.manage', $programme->code) }}">{{ __('Manage Curriculum') }}</a>
    @endif
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>{{ __('This degree plan is for academic planning. Formal credit transfer and programme admission are subject to evaluation and approval by UTeM. At least :min% of graduating credits must be earned directly at UTeM.', ['min' => mcms_num($minHomePercent)]) }}</div>
</div>

@if(! $plan['version'])
  <div class="panel">
    <div class="bd" style="padding:28px;text-align:center">
      <h2 style="margin:0 0 8px;font-size:16px">{{ __('Curriculum structure not yet published') }}</h2>
      <p class="muted" style="margin:0 0 16px">{{ __('A year-by-semester degree plan has not been created for this programme yet.') }}</p>
      @if($canManage)
        <a class="btn primary sm" href="{{ route('programmes.manage', $programme->code) }}">{{ __('Create Curriculum Version') }}</a>
      @endif
    </div>
  </div>
@else
  @php($totCr = (float) ($plan['version']->total_credits ?: $programme->total_credits))

  <!-- Summary KPIs -->
  <div class="kpis" style="margin-bottom:20px">
    <div class="kpi">
      <div class="l">{{ __('Programme Requirement') }}</div>
      <div class="v">{{ mcms_num($totCr) }} cr</div>
      <div class="n">{{ mcms_num($plan['placedCredits']) }} {{ __('credits placed') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Core & Specialization') }}</div>
      <div class="v">{{ mcms_num(($plan['componentTotals']['P'] ?? 0) + ($plan['componentTotals']['K'] ?? 0)) }} cr</div>
      <div class="n">P: {{ mcms_num($plan['componentTotals']['P'] ?? 0) }} &middot; K: {{ mcms_num($plan['componentTotals']['K'] ?? 0) }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Compulsory & Electives') }}</div>
      <div class="v">{{ mcms_num(($plan['componentTotals']['W'] ?? 0) + ($plan['componentTotals']['E'] ?? 0)) }} cr</div>
      <div class="n">W: {{ mcms_num($plan['componentTotals']['W'] ?? 0) }} &middot; E: {{ mcms_num($plan['componentTotals']['E'] ?? 0) }}</div>
    </div>
    @if($plan['progress'])
      <div class="kpi">
        <div class="l">{{ __('Your Progress') }}</div>
        <div class="v">{{ mcms_num($plan['progress']['completed'] + $plan['progress']['transferred']) }} / {{ mcms_num($totCr) }} cr</div>
        <div class="n">
          {{ __('In progress') }}: {{ mcms_num($plan['progress']['in_progress']) }} cr &middot;
          {{ __('Remaining') }}: {{ mcms_num($plan['progress']['remaining']) }} cr
        </div>
      </div>
    @else
      <div class="kpi">
        <div class="l">{{ __('Direct UTeM Minimum') }}</div>
        <div class="v">&ge; {{ mcms_num($minHomePercent) }}%</div>
        <div class="n">&ge; {{ mcms_num(round($totCr * ($minHomePercent / 100), 1)) }} {{ __('credits at UTeM') }}</div>
      </div>
    @endif
  </div>

  <!-- Compact Year Tabs -->
  <div data-tabs>
    <nav class="tabs" aria-label="{{ __('Study years') }}" style="margin-bottom:16px;overflow-x:auto">
      @foreach($plan['years'] as $idx => $year)
        <button data-tab="year-{{ $year->year_no }}" class="{{ $idx === 0 ? 'on' : '' }}">
          {{ __($year->label) }} ({{ mcms_num($year->credits) }} cr)
        </button>
      @endforeach
      @if($plan['pools']->isNotEmpty())
        <button data-tab="electives">{{ __('Elective Pools') }} ({{ $plan['pools']->count() }})</button>
      @endif
    </nav>

    @foreach($plan['years'] as $idx => $year)
      <div data-pane="year-{{ $year->year_no }}" style="{{ $idx === 0 ? '' : 'display:none' }}">
        @foreach($year->semesters as $sem)
          <div class="panel" style="margin-bottom:18px">
            <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
              <div style="display:flex;align-items:center;gap:8px">
                <h2 style="margin:0;font-size:15px">{{ __($year->label) }} &ndash; {{ __($sem->label) }}</h2>
                @if($sem->semester_type === 'short')
                  <span class="tag info">{{ __('Short Semester') }}</span>
                @endif
              </div>
              <div class="s" style="font-variant-numeric:tabular-nums">
                <strong>{{ mcms_num($sem->credits) }} cr</strong>
                <span class="muted">&middot; {{ __('Running total:') }} {{ mcms_num($sem->running_total) }} / {{ mcms_num($totCr) }} cr</span>
              </div>
            </div>

            <div style="overflow-x:auto">
              <table style="margin:0">
                <thead>
                  <tr>
                    <th style="width:120px">{{ __('Code') }}</th>
                    <th style="min-width:220px">{{ __('Course') }}</th>
                    <th style="width:70px">{{ __('Type') }}</th>
                    <th class="num" style="width:75px">{{ __('Credits') }}</th>
                    <th style="min-width:150px">{{ __('Prerequisites') }}</th>
                    <th style="min-width:140px">{{ __('Transfer & Links') }}</th>
                    @if($plan['progress'])
                      <th style="width:130px">{{ __('Status') }}</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($sem->courses as $c)
                    <tr>
                      <td style="font-family:var(--font-mono);font-size:12px;white-space:nowrap">
                        <a href="{{ route('courses.show', urlencode($c->code)) }}" style="font-weight:600">{{ $c->code }}</a>
                      </td>
                      <td>
                        <a href="{{ route('courses.show', urlencode($c->code)) }}" style="font-weight:600;color:var(--color-ink, #090a0f)">
                          {{ app()->getLocale() === 'ms' && $c->name_ms ? $c->name_ms : $c->name }}
                        </a>
                        @if($c->is_placeholder)
                          <span class="tag neutral" style="margin-left:6px">{{ __('Elective Slot') }}</span>
                        @endif
                        @if($c->course_status === 'revised')
                          <span class="tag warn" style="margin-left:6px">{{ __('Revised') }}</span>
                        @elseif($c->course_status === 'withdrawn')
                          <span class="tag err" style="margin-left:6px">{{ __('Withdrawn') }}</span>
                        @endif
                      </td>
                      <td><span class="tag neutral">{{ $c->component_code }}</span></td>
                      <td class="num" style="font-variant-numeric:tabular-nums"><strong>{{ mcms_num($c->credits) }}</strong></td>
                      <td>
                        @if($c->prerequisites->isEmpty())
                          <span class="s muted">&mdash;</span>
                        @else
                          <div style="display:flex;gap:4px;flex-wrap:wrap">
                            @foreach($c->prerequisites as $pr)
                              @if($pr->req_code)
                                <a class="tag {{ in_array($pr->req_code, $c->missing_prereqs, true) ? 'warn' : 'neutral' }}" href="{{ route('courses.show', urlencode($pr->req_code)) }}" style="font-family:var(--font-mono);text-decoration:none">
                                  {{ $pr->req_code }}
                                </a>
                              @else
                                <span class="tag neutral">{{ __('All prior courses') }}</span>
                              @endif
                            @endforeach
                          </div>
                        @endif
                      </td>
                      <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center">
                          @if($c->is_non_transferable)
                            <span class="tag err">{{ __('Not Transferable') }}</span>
                          @endif
                          @if($c->equivalence_count > 0)
                            <a class="tag info" href="{{ route('courses.show', urlencode($c->code)) }}" style="text-decoration:none">
                              {{ $c->equivalence_count }} {{ __('Equivalence') }}
                            </a>
                          @endif
                          @if($c->mc_count > 0)
                            <a class="tag ok" href="{{ route('courses.show', urlencode($c->code)) }}" style="text-decoration:none">
                              {{ $c->mc_count }} MC
                            </a>
                          @endif
                          @if(! $c->is_non_transferable && $c->equivalence_count === 0 && $c->mc_count === 0)
                            <span class="s muted">&mdash;</span>
                          @endif
                        </div>
                      </td>
                      @if($plan['progress'])
                        <td>
                          @if($c->learner_state === 'transferred')
                            <span class="tag ok">{{ __('Transferred') }}</span>
                          @elseif($c->learner_state === 'completed')
                            <span class="tag ok">{{ __('Completed') }}</span>
                          @elseif($c->learner_state === 'in_progress')
                            <span class="tag info">{{ __('In Progress') }}</span>
                          @elseif($c->is_blocked)
                            <span class="tag warn" title="{{ __('Requires:') }} {{ implode(', ', $c->missing_prereqs) }}">{{ __('Blocked') }}</span>
                          @else
                            <span class="tag neutral">{{ __('Remaining') }}</span>
                          @endif
                        </td>
                      @endif
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endforeach
      </div>
    @endforeach

    @if($plan['pools']->isNotEmpty())
      <div data-pane="electives" style="display:none">
        @foreach($plan['pools'] as $pool)
          <div class="panel" style="margin-bottom:18px">
            <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
              <h2 style="margin:0;font-size:15px">{{ app()->getLocale() === 'ms' && $pool->name_ms ? $pool->name_ms : $pool->name_en }}</h2>
              <span class="s muted">{{ __('Choose :count courses (:cr cr each)', ['count' => $pool->required_count, 'cr' => mcms_num($pool->credits_per_course)]) }}</span>
            </div>
            <table style="margin:0">
              <thead>
                <tr>
                  <th style="width:120px">{{ __('Code') }}</th>
                  <th>{{ __('Course') }}</th>
                  <th class="num" style="width:80px">{{ __('Credits') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($pool->courses as $pc)
                  <tr>
                    <td style="font-family:var(--font-mono);font-size:12px">
                      <a href="{{ route('courses.show', urlencode($pc->code)) }}">{{ $pc->code }}</a>
                    </td>
                    <td><strong>{{ app()->getLocale() === 'ms' && $pc->name_ms ? $pc->name_ms : $pc->name }}</strong></td>
                    <td class="num">{{ mcms_num($pc->credit) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="muted" style="text-align:center;padding:18px">{{ __('No elective options listed yet.') }}</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endif
@endsection
