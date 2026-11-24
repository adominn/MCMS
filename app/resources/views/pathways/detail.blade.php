@extends('layouts.app')
@section('title', $pw->programme_code.' '.__('Pathway Details'))
@section('content')
@php
  $earnedItems = $items->filter(fn ($i) => in_array($i->micro_credential_id, $earnedMcIds, true));
  $enrolledItems = $items->filter(fn ($i) => ! in_array($i->micro_credential_id, $earnedMcIds, true) && in_array($i->micro_credential_id, $enrolledMcIds, true));
  $totCredits = (float) ($pw->total_credits ?: 120);
  $earnedCredits = (float) $earnedItems->sum('credits');
  $enrolledCredits = (float) $enrolledItems->sum('credits');
  $mappedCredits = (float) $items->sum('credits');
  $minUtemCredits = round($totCredits * ($minHomePercent / 100), 1);
  $earnedPct = $totCredits > 0 ? min(100, round(($earnedCredits / $totCredits) * 100, 1)) : 0;
  $enrolledPct = $totCredits > 0 ? min(100 - $earnedPct, round(($enrolledCredits / $totCredits) * 100, 1)) : 0;
  $mappedLabel = $items->count() === 1
    ? __('1 micro-credential mapped')
    : __(':count micro-credentials mapped', ['count' => $items->count()]);
@endphp

<x-breadcrumb :crumbs="[
  __('My degree plan') => route('plan.mine'),
  __('Choose a degree pathway') => route('plan.choose'),
  $pw->programme_code => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
      <span class="tag info" style="font-family:var(--font-mono)"><strong>{{ $pw->programme_code }}</strong></span>
      @if(!empty($pw->faculty_code))
        <span class="tag neutral">{{ $pw->faculty_code }}</span>
      @endif
      <span class="tag neutral">MQF {{ $pw->mqf_level }}</span>
      <span class="s muted">{{ mcms_num($totCredits) }} {{ __('graduation credits') }}</span>
      @if($existingPlan)
        <span class="tag ok"><strong>{{ __('Selected') }}</strong></span>
      @endif
    </div>
    <h1 style="margin:0">{{ $pw->programme_name }}</h1>
    @if(!empty($pw->faculty_name))
      <div class="sub">{{ $pw->faculty_name }}</div>
    @endif
  </div>
  <div class="act" style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn ter sm" href="{{ route('plan.choose') }}">&larr; {{ __('All Pathways') }}</a>
    @if($existingPlan)
      <a class="btn sec sm" href="{{ route('plan.mine') }}">{{ __('View in My Plan') }}</a>
    @else
      <form method="post" action="{{ route('plan.selfStore') }}" style="display:inline">
        @csrf
        <input type="hidden" name="pathway_id" value="{{ $pw->id }}">
        <button type="submit" class="btn primary sm">{{ __('Select This Pathway') }}</button>
      </form>
    @endif
  </div>
</div>

{{-- 4-Tab Detail Layout (Rule D-67: Option A) --}}
<div data-tabs>
  <nav class="tabs" aria-label="{{ __('Pathway detail sections') }}" style="margin-bottom:16px;overflow-x:auto">
    <button data-tab="overview" class="on">{{ __('Overview') }}</button>
    <button data-tab="courses">{{ __('Mapped Courses') }} ({{ $items->count() }})</button>
    <button data-tab="progress">{{ __('My Progress') }}</button>
    <button data-tab="spec">{{ __('Specification') }}</button>
  </nav>

  {{-- Tab 1: Overview --}}
  <div data-pane="overview">
    <div class="kpis" style="margin-bottom:20px">
      <div class="kpi">
        <div class="l">{{ __('Graduation Requirement') }}</div>
        <div class="v">{{ mcms_num($totCredits) }} cr</div>
        <div class="n">MQF {{ $pw->mqf_level }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Your Match') }}</div>
        <div class="v">{{ $earnedItems->count() }} / {{ $items->count() }}</div>
        <div class="n">{{ mcms_num($earnedCredits) }} / {{ mcms_num($mappedCredits) }} cr {{ __('earned') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Currently Enrolled') }}</div>
        <div class="v">{{ $enrolledItems->count() }}</div>
        <div class="n">{{ mcms_num($enrolledCredits) }} cr {{ __('in progress') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Direct UTeM Minimum') }}</div>
        <div class="v">&ge; {{ mcms_num($minHomePercent) }}%</div>
        <div class="n">&ge; {{ mcms_num($minUtemCredits) }} cr {{ __('at UTeM') }}</div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:18px">
      <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Pathway Summary & Key Rules') }}</h2></div>
      <div class="bd" style="padding:16px 20px;font-size:14px;line-height:1.55">
        <p style="margin:0 0 12px">
          {{ __('This pathway maps :label into :programme (:credits graduation credits). Earned micro-credentials are matched automatically to help you track your progress independently.', [
            'label' => $mappedLabel,
            'programme' => $pw->programme_name,
            'credits' => mcms_num($totCredits),
          ]) }}
        </p>
        <ul style="margin:0 0 16px;padding-left:18px;color:var(--color-ink-muted, #475569);display:flex;flex-direction:column;gap:6px">
          <li>{{ __('Selecting this pathway is for academic planning only and does not constitute programme admission or automatic credit transfer.') }}</li>
          <li>{{ __('Formal credit transfer is evaluated by the receiving programme upon admission.') }}</li>
          <li>{{ __('At least :min% of graduating credits (:cr cr) must be earned directly at UTeM.', ['min' => mcms_num($minHomePercent), 'cr' => mcms_num($minUtemCredits)]) }}</li>
        </ul>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          @if($hasFullCurriculum)
            <a class="btn ter sm" href="{{ route('programmes.show', $pw->programme_code) }}">{{ __('View Full Year/Semester Degree Plan') }}</a>
          @endif
          <a class="btn ter sm" href="{{ route('programmes.compare', ['from' => $pw->programme_code]) }}">{{ __('Compare With Another Programme') }}</a>
        </div>
      </div>
    </div>
  </div>

  {{-- Tab 2: Mapped Courses (Bounded table: 10 rows initial, 1 search bar) --}}
  <div data-pane="courses" style="display:none">
    <div class="panel">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h2 style="margin:0;font-size:15px">{{ __('Mapped Micro-Credentials') }}</h2>
        <span class="s muted">{{ $mappedLabel }}</span>
      </div>
      <div style="overflow-x:auto">
        <table data-per-page="{{ $initialRows }}" data-paginated style="margin:0">
          <thead>
            <tr>
              <th style="min-width:220px">{{ __('Micro-Credential') }}</th>
              <th>{{ __('Equivalent Degree Course') }}</th>
              <th class="num" style="width:90px">{{ __('Credits') }}</th>
              <th style="width:120px">{{ __('Your Status') }}</th>
              <th style="width:120px"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $i)
              @php($isEarned = in_array($i->micro_credential_id, $earnedMcIds, true))
              @php($isEnrolled = ! $isEarned && in_array($i->micro_credential_id, $enrolledMcIds, true))
              <tr>
                <td><strong>{{ $i->name_en }}</strong></td>
                <td>
                  <a href="{{ route('courses.show', urlencode($i->course_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $i->course_code }}</a>
                  @if(!empty($i->course_name))
                    <div class="s muted">{{ $i->course_name }}</div>
                  @endif
                </td>
                <td class="num" style="font-variant-numeric:tabular-nums"><strong>{{ mcms_num($i->credits) }}</strong></td>
                <td>
                  @if($isEarned)
                    <span class="tag ok">{{ __('Earned') }}</span>
                  @elseif($isEnrolled)
                    <span class="tag info">{{ __('Enrolled') }}</span>
                  @else
                    <span class="tag neutral">{{ __('Not started') }}</span>
                  @endif
                </td>
                <td class="num">
                  @if(! $isEarned && ! $isEnrolled)
                    <a class="btn ter sm" href="{{ route('catalogue', ['q' => $i->name_en]) }}">{{ __('Find & enroll') }}</a>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">{{ __('No micro-credentials are mapped to this pathway yet.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Tab 3: My Progress --}}
  <div data-pane="progress" style="display:none">
    <div class="panel">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h2 style="margin:0;font-size:15px">{{ __('Your Credit Progress Toward :code', ['code' => $pw->programme_code]) }}</h2>
        <span class="tag info">{{ mcms_num($earnedCredits) }} / {{ mcms_num($totCredits) }} cr</span>
      </div>
      <div class="bd" style="padding:18px 20px">
        <div style="height:12px;width:100%;background:var(--color-surface-2, #e2e8f0);display:flex;overflow:hidden;margin-bottom:14px;border-radius:2px">
          @if($earnedPct > 0)
            <div style="width:{{ max(2, $earnedPct) }}%;background:var(--color-success, #24a148)"></div>
          @endif
          @if($enrolledPct > 0)
            <div style="width:{{ max(2, $enrolledPct) }}%;background:var(--color-primary, #0052ff)"></div>
          @endif
        </div>
        <div style="display:flex;gap:18px;flex-wrap:wrap;font-size:13px;margin-bottom:16px">
          <span><strong>{{ __('Earned:') }}</strong> {{ mcms_num($earnedCredits) }} cr ({{ $earnedItems->count() }})</span>
          <span><strong>{{ __('In progress:') }}</strong> {{ mcms_num($enrolledCredits) }} cr ({{ $enrolledItems->count() }})</span>
          <span><strong>{{ __('Remaining degree study:') }}</strong> {{ mcms_num(max(0, $totCredits - $earnedCredits)) }} cr</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn primary sm" href="{{ route('catalogue') }}">{{ __('Find Next Micro-Credential') }}</a>
          <a class="btn ter sm" href="{{ route('transfer') }}">{{ __('Go to Credit Transfer') }}</a>
        </div>
      </div>
    </div>
  </div>

  {{-- Tab 4: Specification --}}
  <div data-pane="spec" style="display:none">
    <div class="panel">
      <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Pathway Specification Document') }}</h2></div>
      <div class="bd" style="padding:18px 20px">
        <p class="muted" style="margin:0 0 16px;font-size:14px">
          {{ __('Download the official printable pathway specification for :code (:name), showing approved micro-credential mappings and graduation requirements.', ['code' => $pw->programme_code, 'name' => $pw->programme_name]) }}
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <a class="btn primary sm" href="{{ route('public.pathways.print', $pw->id) }}" target="_blank">{{ __('Download Specification (PDF)') }}</a>
          @if($hasFullCurriculum)
            <a class="btn ter sm" href="{{ route('programmes.show', ['code' => $pw->programme_code, 'print' => 1]) }}" target="_blank">{{ __('Print Full Degree Plan (PDF)') }}</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
