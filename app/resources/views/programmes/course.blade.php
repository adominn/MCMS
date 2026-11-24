@extends('layouts.app')
@section('title', $course->code.' '.$course->name)
@section('content')
<x-breadcrumb :crumbs="[
  __('Degree Programmes') => route('programmes.index'),
  $course->code => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
      <span class="tag info" style="font-family:var(--font-mono)"><strong>{{ $course->code }}</strong></span>
      <span class="tag neutral">{{ mcms_num($course->credit) }} {{ __('credits') }}</span>
      <span class="tag {{ ($course->status ?? 'active') === 'active' ? 'ok' : 'warn' }}">{{ ucfirst($course->status ?? 'active') }}</span>
    </div>
    <h1 style="margin:0">{{ app()->getLocale() === 'ms' && !empty($course->name_ms) ? $course->name_ms : $course->name }}</h1>
  </div>
  <div class="act" style="display:flex;gap:8px;flex-wrap:wrap">
    @if($canPropose)
      <a class="btn primary sm" href="{{ route('equivalences', ['propose' => $course->id]) }}">{{ __('Propose Equivalence') }}</a>
    @endif
    @if($canManageCourse)
      <form method="post" action="{{ route('courses.status', $course->id) }}" style="display:inline-flex;gap:6px;align-items:center">
        @csrf
        <select name="status" aria-label="{{ __('Course status') }}" style="height:34px;padding:0 10px">
          <option value="active" @selected(($course->status ?? 'active') === 'active')>{{ __('Active') }}</option>
          <option value="revised" @selected(($course->status ?? '') === 'revised')>{{ __('Revised (Flags Equivalences)') }}</option>
          <option value="withdrawn" @selected(($course->status ?? '') === 'withdrawn')>{{ __('Withdrawn') }}</option>
        </select>
        <button type="submit" class="btn sec sm">{{ __('Update Status') }}</button>
      </form>
    @endif
  </div>
</div>

<div data-tabs>
  <nav class="tabs" aria-label="{{ __('Course sections') }}" style="margin-bottom:16px">
    <button data-tab="overview" class="on">{{ __('Overview & Prerequisites') }}</button>
    <button data-tab="equivalences">{{ __('Equivalences & Transfer') }} ({{ $detail['outgoing']->count() + $detail['incoming']->count() }})</button>
    <button data-tab="mcs">{{ __('Micro-Credentials') }} ({{ $detail['microCredentials']->count() }})</button>
    <button data-tab="programmes">{{ __('Programmes') }} ({{ $detail['programmes']->count() }})</button>
  </nav>

  <!-- Tab 1: Overview & Prerequisites -->
  <div data-pane="overview">
    <div class="cols" style="gap:20px;align-items:flex-start">
      <div style="flex:1.4;min-width:0">
        <div class="panel" style="margin-bottom:18px">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Course Summary') }}</h2></div>
          <div class="bd" style="padding:16px 20px;font-size:14px;line-height:1.55">
            <p style="margin:0 0 12px">{{ app()->getLocale() === 'ms' && !empty($course->synopsis_ms) ? $course->synopsis_ms : ($course->synopsis_en ?: $course->clos) }}</p>
            <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;color:var(--color-ink-muted, #475569)">
              <span><strong>{{ __('Credits:') }}</strong> {{ mcms_num($course->credit) }}</span>
              <span><strong>{{ __('SLT:') }}</strong> {{ $course->slt_hours }} {{ __('hours') }}</span>
              @if($course->lecture_hours !== null)
                <span><strong>{{ __('Contact Hours:') }}</strong> {{ $course->lecture_hours }}L / {{ $course->lab_hours ?? 0 }}Lab</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div style="flex:1;min-width:260px">
        <div class="panel" style="margin-bottom:18px">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Must Complete First') }}</h2></div>
          <div class="bd" style="padding:14px 18px">
            @forelse($detail['upstream'] as $pr)
              <div style="padding:8px 0;border-bottom:1px solid var(--color-line-hairline, #e2e8f0)">
                @if($pr->req_code)
                  <a href="{{ route('courses.show', urlencode($pr->req_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $pr->req_code }}</a>
                  <span style="margin-left:6px">{{ $pr->req_name }}</span>
                @else
                  <strong>{{ __('All prior programme courses completed') }}</strong>
                @endif
              </div>
            @empty
              <span class="s muted">{{ __('No prerequisites required.') }}</span>
            @endforelse
          </div>
        </div>

        <div class="panel">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Unlocks These Courses') }}</h2></div>
          <div class="bd" style="padding:14px 18px">
            @forelse($detail['downstream'] as $dn)
              <div style="padding:8px 0;border-bottom:1px solid var(--color-line-hairline, #e2e8f0)">
                <a href="{{ route('courses.show', urlencode($dn->tgt_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $dn->tgt_code }}</a>
                <span style="margin-left:6px">{{ $dn->tgt_name }}</span>
              </div>
            @empty
              <span class="s muted">{{ __('No downstream courses depend on this course.') }}</span>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab 2: Equivalences & Transfer -->
  <div data-pane="equivalences" style="display:none">
    <div class="panel">
      <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Course Equivalence & Transfer Map') }}</h2></div>
      <div style="overflow-x:auto">
        <table style="margin:0">
          <thead>
            <tr>
              <th>{{ __('Direction') }}</th>
              <th>{{ __('Equivalent Course') }}</th>
              <th>{{ __('Receiving Programme') }}</th>
              <th>{{ __('Type') }}</th>
              <th class="num">{{ __('Credits Counted') }}</th>
              <th>{{ __('Conditions & Validity') }}</th>
              <th>{{ __('Status') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($detail['outgoing']->concat($detail['incoming']) as $eq)
              @php($typeClass = match($eq->equivalence_type) {
                'exact' => 'ok',
                'partial', 'conditional' => 'warn',
                'no_transfer' => 'err',
                default => 'neutral'
              })
              <tr>
                <td class="s muted">{{ $eq->direction === 'bidirectional' ? __('Both ways') : ($eq->source_course_id === $course->id ? __('Transfers to') : __('Receives from')) }}</td>
                <td>
                  <a href="{{ route('courses.show', urlencode($eq->other_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $eq->other_code }}</a>
                  <div class="s muted">{{ $eq->other_name }}</div>
                </td>
                <td>{{ $eq->target_programme_code ?? __('Any programme') }}</td>
                <td><span class="tag {{ $typeClass }}">{{ __($eqTypes[$eq->equivalence_type] ?? $eq->equivalence_type) }}</span></td>
                <td class="num"><strong>{{ mcms_num($eq->credits_counted) }} cr</strong></td>
                <td class="s">
                  {{ app()->getLocale() === 'ms' && !empty($eq->condition_note_ms) ? $eq->condition_note_ms : ($eq->condition_note_en ?: __('Standard credit transfer rules apply.')) }}
                  <div class="muted" style="margin-top:2px">
                    @if($eq->min_grade) {{ __('Min grade:') }} {{ $eq->min_grade }} &middot; @endif
                    {{ __('Validity:') }} {{ $eq->validity_years }} {{ __('years') }}
                  </div>
                </td>
                <td>
                  <span class="tag {{ $eq->status === 'approved' ? 'ok' : ($eq->status === 'rejected' ? 'err' : 'info') }}">
                    {{ __($eqStatuses[$eq->status] ?? $eq->status) }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="muted" style="padding:24px;text-align:center">{{ __('No course equivalences recorded yet. Unmapped courses must be evaluated upon credit transfer application.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab 3: Micro-Credentials -->
  <div data-pane="mcs" style="display:none">
    <div class="panel">
      <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Approved Micro-Credentials Mapped to This Course') }}</h2></div>
      <table style="margin:0">
        <thead>
          <tr>
            <th>{{ __('Micro-Credential') }}</th>
            <th>{{ __('Category') }}</th>
            <th class="num">{{ __('Credits') }}</th>
            <th style="width:140px"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($detail['microCredentials'] as $mc)
            <tr>
              <td><strong>{{ app()->getLocale() === 'ms' && !empty($mc->name_ms) ? $mc->name_ms : $mc->name_en }}</strong></td>
              <td><span class="tag info">{{ strtoupper($mc->category) }}</span></td>
              <td class="num">{{ mcms_num($mc->proposed_credit ?? 0) }} cr</td>
              <td class="num"><a class="btn sm ter" href="{{ route('catalogue', ['q' => $mc->name_en]) }}">{{ __('Find & enroll') }}</a></td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted" style="padding:24px;text-align:center">{{ __('No micro-credentials are currently linked to this course.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab 4: Programmes -->
  <div data-pane="programmes" style="display:none">
    <div class="panel">
      <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Programmes Including This Course') }}</h2></div>
      <table style="margin:0">
        <thead>
          <tr>
            <th>{{ __('Programme') }}</th>
            <th>{{ __('Session') }}</th>
            <th>{{ __('Placement') }}</th>
            <th>{{ __('Type') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($detail['programmes'] as $p)
            <tr>
              <td>
                <a href="{{ route('programmes.show', $p->code) }}" style="font-weight:600">{{ $p->code }} &ndash; {{ $p->name }}</a>
              </td>
              <td>{{ $p->academic_session }}</td>
              <td>{{ __('Year') }} {{ $p->year_no }}, {{ $p->semester_type === 'short' || $p->semester_no === 3 ? __('Short Semester') : __('Semester').' '.$p->semester_no }}</td>
              <td><span class="tag neutral">{{ $p->component_code }}</span></td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted" style="padding:24px;text-align:center">{{ __('Not yet placed in any active programme version.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
