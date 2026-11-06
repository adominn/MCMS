@extends('layouts.app')
@section('title', __('My results'))
@section('content')
@php
  $nResults = $data->sum(fn ($d) => count($d['results'] ?? []));
  $nTotalComps = $data->sum(fn ($d) => count($d['components'] ?? []));
  $nMet = $data->sum(fn ($d) => collect($d['results'] ?? [])->filter(fn ($r) => (float) ($r->score ?? 0) >= (float) ($r->pass_mark ?? 0))->count());
  $nBadges = $data->sum(fn ($d) => ($d['badges'] ?? collect())->count());
@endphp
<div class="title">
  <div>
    <h1>{{ __('My results') }}</h1>
    <div class="sub">{{ __('Verified academic results, assessment breakdowns, and module badges.') }}</div>
  </div>
</div>

@if($data->isEmpty())
  <div class="panel">
    <div class="bd muted" style="text-align:center;padding:36px">
      <div style="font-size:28px;margin-bottom:8px;opacity:0.4"><i class="bi bi-journal-x"></i></div>
      <strong>{{ __('No academic results found') }}</strong>
      <div class="s muted" style="margin-top:4px">{{ __('You are not enrolled in any micro-credential cohort yet.') }}</div>
    </div>
  </div>
@else
<!-- KPI Performance Banner -->
<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Total Registrations') }}</div>
    <div class="v">{{ $summary['total_registrations'] ?? $data->count() }}</div>
    <div class="s muted">{{ $data->where('e.enrolment_status', 'completed')->count() }} {{ __('completed') }} &middot; {{ $data->where('e.enrolment_status', 'enrolled')->count() }} {{ __('in study') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Verified Components') }}</div>
    <div class="v">{{ $nResults }} <span class="s muted" style="font-size:14px;font-weight:400">/ {{ $nTotalComps }}</span></div>
    <div class="s muted">{{ $nMet }} {{ __('met pass mark') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Earned Credits') }}</div>
    <div class="v">{{ $summary['total_credits'] ?? 0 }} <span class="s muted" style="font-size:14px;font-weight:400">{{ __('credits') }}</span></div>
    <div class="s muted">{{ __('From completed micro-credentials') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Module Badges') }}</div>
    <div class="v">{{ $nBadges }}</div>
    <div class="s muted">{{ __('Awarded for passed modules') }}</div>
  </div>
</div>

<!-- Academic Results Summary Data Table -->
<div class="panel">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
      <h2 style="margin:0">{{ __('Academic Results Summary') }}</h2>
      <div class="s muted" style="margin-top:2px">{{ __('Select a micro-credential row or click View details to open its full assessment breakdown and badge details.') }}</div>
    </div>
    <span class="r">
      <span class="s muted">{{ __(':count courses', ['count' => $data->count()]) }}</span>
    </span>
  </div>
  <table class="table-results-summary" id="my-results-table">
    <thead>
      <tr>
        <th style="min-width:240px">{{ __('Micro-credential / Subject') }}</th>
        <th>{{ __('Session & Semester') }}</th>
        <th class="num">{{ __('Credits & SLT') }}</th>
        <th class="num">{{ __('Weighted Mark') }}</th>
        <th>{{ __('Letter Grade') }}</th>
        <th>{{ __('Result Status') }}</th>
        <th>{{ __('Module Badges') }}</th>
        <th class="num" style="min-width:130px">{{ __('Actions') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $d)
        @php
          $e = $d['e'];
          $overall = $d['overall'] ?? null;
          $nVerified = count($d['results'] ?? []);
          $nComps = count($d['components'] ?? []);
          $badges = $d['badges'] ?? collect();
          $modules = $d['modules'] ?? collect();
          $badgedModules = $modules->filter(fn($m) => !empty($m->separately_recognisable) || !empty($m->badge_id));
        @endphp
        <tr data-href="{{ route('results.mine.show', $e->id) }}">
          <td>
            <a href="{{ route('results.mine.show', $e->id) }}" style="font-weight:700;font-size:14px;color:var(--ink)">
              {{ $e->name_en }}
            </a>
            <div class="s muted" style="margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
              <span>{{ $e->mc_code ?? ('MC-'.str_pad((string)($e->version_id ?? 1), 4, '0', STR_PAD_LEFT)) }}</span>
              &middot;
              <span class="tag neutral" style="font-size:10px;padding:1px 6px">{{ strtoupper($e->mc_category ?? 'UMC') }}</span>
              &middot;
              <span>{{ $e->reference }}</span>
            </div>
          </td>
          <td class="s">
            <div style="font-weight:600;color:var(--ink)">{{ $e->cohort_name ?? '-' }}</div>
            <div class="muted" style="margin-top:2px">
              @if(!empty($e->start_date) && !empty($e->end_date))
                {{ \Carbon\Carbon::parse($e->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($e->end_date)->format('d/m/Y') }}
              @else
                {{ __('Self-Paced') }}
              @endif
            </div>
          </td>
          <td class="num">
            @if(!empty($e->proposed_credit))
              <strong style="color:var(--ink)">{{ $e->proposed_credit }}</strong> <span class="s muted">{{ __('cr') }}</span>
            @else
              <span class="muted">{{ __('Non-credit') }}</span>
            @endif
            <div class="s muted">{{ !empty($e->planned_slt) ? $e->planned_slt.'h' : '-' }}</div>
          </td>
          <td class="num">
            @if($overall)
              <div style="font-size:15px;font-weight:700;color:var(--ink)">{{ number_format($overall['total_weighted'], 1) }}%</div>
              <div class="s muted">{{ $overall['complete'] ? __('Final') : __('Provisional') }}</div>
            @else
              <span class="muted" style="font-size:13px">{{ __('Pending') }}</span>
            @endif
          </td>
          <td>
            @if($overall)
              <div>
                <strong style="font-size:15px;color:var(--accent, #1e2a6e)">{{ $overall['grade'] }}</strong>
                <span class="s muted" style="font-weight:600">({{ number_format($overall['point'], 2) }})</span>
              </div>
              <div class="s muted" style="font-size:11px;line-height:1.2;margin-top:2px">{{ $overall['grade_desc'] }}</div>
            @else
              <span class="muted">&ndash;</span>
            @endif
          </td>
          <td>
            @if($overall)
              @if($overall['complete'])
                <span class="tag ok" style="font-size:11px">
                  <i class="bi bi-check-circle" style="margin-right:3px"></i>{{ __('Passed (Final)') }}
                </span>
              @else
                <span class="tag info" style="font-size:11px">
                  <i class="bi bi-hourglass-split" style="margin-right:3px"></i>{{ __('Provisional (:v/:t)', ['v' => $nVerified, 't' => $nComps]) }}
                </span>
              @endif
            @else
              <span class="tag n" style="font-size:11px">{{ __('Nothing verified yet') }}</span>
            @endif
          </td>
          <td>
            @if($badges->isNotEmpty())
              <span class="tag ok" style="font-weight:600;font-size:11px">
                <i class="bi bi-award" style="margin-right:3px"></i> {{ $badges->count() }} {{ __('earned') }}
              </span>
              @php($ucreds = $badges->whereNotNull('external_id')->pluck('external_id')->all())
              @if(!empty($ucreds))
                <div class="s muted" style="font-size:11px;margin-top:2px">
                  UCreds {{ implode(', ', $ucreds) }}
                </div>
              @endif
            @elseif($badgedModules->isNotEmpty())
              <span class="tag warn" style="font-size:11px">
                0 / {{ $badgedModules->count() }} {{ __('eligible') }}
              </span>
            @else
              <span class="s muted">{{ __('Integrated') }}</span>
            @endif
          </td>
          <td class="num">
            <a href="{{ route('results.mine.show', $e->id) }}" class="btn ter sm" title="{{ __('View assessment breakdown and badge details') }}">
              <span class="btn-lbl">{{ __('View details') }} &rarr;</span>
            </a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
