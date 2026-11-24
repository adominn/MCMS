@extends('layouts.app')
@section('title', __('Compare Degree Programmes'))
@section('content')
<x-breadcrumb :crumbs="[
  __('Degree Programmes') => route('programmes.index'),
  __('Compare Programmes') => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('Compare Degree Programmes') }}</h1>
    <div class="sub">{{ __('Compare shared courses, approved equivalences, and credits carried over or remaining between two programmes.') }}</div>
  </div>
  @if($progA && $progB)
    <div class="act">
      <a class="btn ter sm" href="{{ route('programmes.compare', ['from' => $progA->code, 'to' => $progB->code, 'mine' => $applyRecord ? 1 : 0, 'print' => 1]) }}" target="_blank">{{ __('Print Comparison (PDF)') }}</a>
    </div>
  @endif
</div>

<form method="get" action="{{ route('programmes.compare') }}" class="panel" style="margin-bottom:20px">
  <div class="bd" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;padding:16px 20px">
    <div style="flex:1;min-width:220px">
      <label for="from" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('From Programme (Current / Source)') }}</label>
      <select id="from" name="from" style="width:100%">
        @foreach($programmes as $p)
          <option value="{{ $p->code }}" @selected($progA && $progA->code === $p->code)>{{ $p->code }} &ndash; {{ $p->name }} ({{ $p->total_credits }} cr)</option>
        @endforeach
      </select>
    </div>
    <div style="flex:1;min-width:220px">
      <label for="to" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('To Programme (Target)') }}</label>
      <select id="to" name="to" style="width:100%">
        @foreach($programmes as $p)
          <option value="{{ $p->code }}" @selected($progB && $progB->code === $p->code)>{{ $p->code }} &ndash; {{ $p->name }} ({{ $p->total_credits }} cr)</option>
        @endforeach
      </select>
    </div>
    @if(auth()->user()?->hasAnyRole('student'))
      <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;padding-bottom:8px;cursor:pointer">
        <input type="checkbox" name="mine" value="1" @checked($applyRecord)>
        <span>{{ __('Apply my completed record') }}</span>
      </label>
    @endif
    <button type="submit" class="btn primary sm">{{ __('Compare') }}</button>
  </div>
</form>

@if($comparison)
  <div class="kpis" style="margin-bottom:20px">
    <div class="kpi">
      <div class="l">{{ __('Shared Courses') }}</div>
      <div class="v">{{ mcms_num($comparison['summary']['shared_credits']) }} cr</div>
      <div class="n">{{ $comparison['shared']->count() }} {{ __('identical courses') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Approved Equivalences') }}</div>
      <div class="v">{{ mcms_num($comparison['summary']['approved_equiv_credits']) }} cr</div>
      <div class="n">{{ $comparison['equivalences']->where('status', 'approved')->count() }} {{ __('approved links') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Unique to :code (Not Carried)', ['code' => $progA->code]) }}</div>
      <div class="v">{{ mcms_num($comparison['summary']['not_carried_credits']) }} cr</div>
      <div class="n">{{ $comparison['uniqueA']->count() }} {{ __('courses') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Remaining in :code', ['code' => $progB->code]) }}</div>
      <div class="v">{{ mcms_num($comparison['summary']['remaining_credits_b']) }} cr</div>
      <div class="n">{{ __('of :tot cr required', ['tot' => $progB->total_credits]) }}</div>
    </div>
  </div>

  @if($comparison['learnerSummary'])
    <div class="note info" style="margin-bottom:20px">
      <div>
        <strong>{{ __('Based on your completed record:') }}</strong>
        {{ mcms_num($comparison['learnerSummary']['carried_credits']) }} {{ __('credits can potentially carry into :code, leaving approximately :rem credits remaining (subject to formal credit transfer evaluation and the :min% direct UTeM minimum).', [
          'code' => $progB->code,
          'rem' => mcms_num($comparison['learnerSummary']['remaining_credits']),
          'min' => mcms_num($minHomePercent),
        ]) }}
      </div>
    </div>
  @endif

  <div data-tabs>
    <nav class="tabs" aria-label="{{ __('Comparison sections') }}" style="margin-bottom:16px">
      <button data-tab="shared" class="on">{{ __('Shared Courses') }} ({{ $comparison['shared']->count() }})</button>
      <button data-tab="equiv">{{ __('Equivalences & Transfers') }} ({{ $comparison['equivalences']->count() }})</button>
      <button data-tab="unique">{{ __('Unique Courses (Credit Impact)') }} ({{ $comparison['uniqueA']->count() + $comparison['uniqueB']->count() }})</button>
    </nav>

    <!-- Tab 1: Shared Courses -->
    <div data-pane="shared">
      <div class="panel">
        <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Courses Shared Between :a and :b', ['a' => $progA->code, 'b' => $progB->code]) }}</h2></div>
        <div style="overflow-x:auto">
          <table style="margin:0">
            <thead>
              <tr>
                <th style="width:120px">{{ __('Code') }}</th>
                <th>{{ __('Course') }}</th>
                <th>{{ $progA->code }} {{ __('Placement') }}</th>
                <th>{{ $progB->code }} {{ __('Placement') }}</th>
                <th class="num">{{ __('Credits') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($comparison['shared'] as $row)
                <tr>
                  <td style="font-family:var(--font-mono);font-size:12px">
                    <a href="{{ route('courses.show', urlencode($row->course->code)) }}" style="font-weight:600">{{ $row->course->code }}</a>
                  </td>
                  <td><strong>{{ $row->course->name }}</strong></td>
                  <td class="s muted">{{ __('Year') }} {{ $row->year_a }}, {{ __($row->sem_a) }}</td>
                  <td class="s muted">{{ __('Year') }} {{ $row->year_b }}, {{ __($row->sem_b) }}</td>
                  <td class="num"><strong>{{ mcms_num($row->credits) }}</strong></td>
                </tr>
              @empty
                <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">{{ __('No identical shared courses found between these two programmes.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tab 2: Equivalences & Transfers -->
    <div data-pane="equiv" style="display:none">
      <div class="panel">
        <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Course Equivalences Between :a and :b', ['a' => $progA->code, 'b' => $progB->code]) }}</h2></div>
        <div style="overflow-x:auto">
          <table style="margin:0">
            <thead>
              <tr>
                <th>{{ $progA->code }} {{ __('Course') }}</th>
                <th>{{ $progB->code }} {{ __('Course') }}</th>
                <th>{{ __('Type') }}</th>
                <th class="num">{{ __('Credits Counted') }}</th>
                <th>{{ __('Conditions') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($comparison['equivalences'] as $eq)
                <tr>
                  <td>
                    <a href="{{ route('courses.show', urlencode($eq->from_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $eq->from_code }}</a>
                    <div class="s muted">{{ $eq->from_name }}</div>
                  </td>
                  <td>
                    <a href="{{ route('courses.show', urlencode($eq->to_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $eq->to_code }}</a>
                    <div class="s muted">{{ $eq->to_name }}</div>
                  </td>
                  <td><span class="tag {{ $eq->equivalence_type === 'exact' ? 'ok' : ($eq->equivalence_type === 'no_transfer' ? 'err' : 'warn') }}">{{ __($eqTypes[$eq->equivalence_type] ?? $eq->equivalence_type) }}</span></td>
                  <td class="num"><strong>{{ mcms_num($eq->credits_counted) }} cr</strong></td>
                  <td class="s muted">{{ $eq->condition_note_en ?: '&mdash;' }}</td>
                  <td><span class="tag {{ $eq->status === 'approved' ? 'ok' : 'info' }}">{{ __($eqStatuses[$eq->status] ?? $eq->status) }}</span></td>
                </tr>
              @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">{{ __('No recorded course equivalences between unique courses in these programmes yet.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tab 3: Unique Courses (Gain / Loss) -->
    <div data-pane="unique" style="display:none">
      <div class="cols" style="gap:20px;align-items:flex-start">
        <div class="panel" style="flex:1;min-width:0">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Only in :code (Not Carried Over)', ['code' => $progA->code]) }}</h2></div>
          <table style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Course') }}</th>
                <th class="num">{{ __('Credits') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($comparison['uniqueA'] as $c)
                <tr>
                  <td style="font-family:var(--font-mono);font-size:12px"><a href="{{ route('courses.show', urlencode($c->code)) }}">{{ $c->code }}</a></td>
                  <td>{{ $c->name }}</td>
                  <td class="num">{{ mcms_num($c->credits) }}</td>
                </tr>
              @empty
                <tr><td colspan="3" class="muted" style="padding:18px;text-align:center">{{ __('None.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="panel" style="flex:1;min-width:0">
          <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Only in :code (Required to Complete)', ['code' => $progB->code]) }}</h2></div>
          <table style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Course') }}</th>
                <th class="num">{{ __('Credits') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($comparison['uniqueB'] as $c)
                <tr>
                  <td style="font-family:var(--font-mono);font-size:12px"><a href="{{ route('courses.show', urlencode($c->code)) }}">{{ $c->code }}</a></td>
                  <td>{{ $c->name }}</td>
                  <td class="num">{{ mcms_num($c->credits) }}</td>
                </tr>
              @empty
                <tr><td colspan="3" class="muted" style="padding:18px;text-align:center">{{ __('None.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@else
  <div class="panel">
    <div class="bd" style="padding:28px;text-align:center">
      <p class="muted" style="margin:0">{{ __('At least one structured programme curriculum is required to compare programmes.') }}</p>
    </div>
  </div>
@endif
@endsection
