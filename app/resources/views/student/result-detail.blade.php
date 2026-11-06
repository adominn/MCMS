@extends('layouts.app')
@section('title', __('Academic Results: :name', ['name' => $e->name_en]))
@section('content')
@php
  $n = count($results);
  $eStatus = $e->enrolment_status ?? ($e->status ?? 'enrolled');
  $mk = fn ($v) => rtrim(rtrim((string) $v, '0'), '.');
@endphp

<!-- Breadcrumbs -->
<div class="crumb" style="margin-bottom:12px">
  <a href="{{ route('tasks') }}">{{ __('My learning') }}</a>
  <span>/</span>
  <a href="{{ route('results.mine') }}">{{ __('My results') }}</a>
  <span>/</span>
  <span>{{ $e->name_en }} ({{ $e->cohort_name ?? $e->reference }})</span>
</div>

<!-- Page Title & Top Actions -->
<div class="title" style="margin-bottom:20px">
  <div>
    <h1>{{ $e->name_en }} {{ $e->cohort_name ?? '' }}</h1>
    <div class="sub" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <span>{{ $e->mc_code ?? ('MC-'.str_pad((string)($e->version_id ?? 1), 4, '0', STR_PAD_LEFT)) }}</span>
      &middot;
      <span class="tag neutral" style="font-size:11px">{{ strtoupper($e->mc_category ?? 'UMC') }}</span>
      &middot;
      <span>{{ __('Registration:') }} <strong>{{ $e->reference }}</strong></span>
      &middot;
      @if($eStatus === 'completed')
        <span class="tag ok">{{ __('Completed') }}</span>
      @elseif($eStatus === 'enrolled')
        <span class="tag info">{{ __('In Study') }}</span>
      @else
        <span class="tag neutral">{{ ucfirst($eStatus) }}</span>
      @endif
    </div>
  </div>
  <div class="act" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('results.mine') }}" class="btn sec sm">
      <i class="bi bi-arrow-left" style="margin-right:4px"></i>{{ __('Back to all results') }}
    </a>
    @if($eStatus === 'completed')
      <a href="{{ route('feedback.show', $e->id) }}" class="btn ter sm">{{ __('Give feedback') }}</a>
    @endif
  </div>
</div>

<!-- Academic Course Context Bar -->
<div class="panel" style="margin-bottom:20px;background:linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%)">
  <div class="bd" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;align-items:center">
    <div>
      <div class="small muted">{{ __('Course & Code') }}</div>
      <div style="font-weight:700;font-size:15px;color:var(--ink)">{{ $e->name_en }}</div>
      <div class="s">{{ $e->mc_code ?? ('MC-'.str_pad((string)($e->version_id ?? 1), 4, '0', STR_PAD_LEFT)) }} &middot; {{ strtoupper($e->mc_category ?? 'UMC') }}</div>
    </div>
    <div>
      <div class="small muted">{{ __('Cohort & Study Period') }}</div>
      <div style="font-weight:600;color:var(--ink)">{{ $e->cohort_name ?? '-' }}</div>
      <div class="s">
        @if(!empty($e->start_date) && !empty($e->end_date))
          {{ \Carbon\Carbon::parse($e->start_date)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($e->end_date)->format('d M Y') }}
        @else
          {{ __('Flexible / Self-Paced') }}
        @endif
      </div>
    </div>
    <div>
      <div class="small muted">{{ __('Academic PTj & Examiner') }}</div>
      <div style="font-weight:600;color:var(--ink)">{{ $e->ptj_code ?? 'FTMK' }} &middot; {{ $e->ptj_name ?? 'Faculty' }}</div>
      <div class="s">{{ $e->facilitators ?? __('Faculty Examiner') }}</div>
    </div>
    <div>
      <div class="small muted">{{ __('Credits & Total SLT') }}</div>
      <div style="font-weight:600;color:var(--ink)">
        {{ !empty($e->proposed_credit) ? $e->proposed_credit.' '.__('credits') : __('Non-credit') }}
      </div>
      <div class="s">{{ !empty($e->planned_slt) ? $e->planned_slt.' '.__('SLT hours') : '-' }} &middot; MQF Level {{ $e->proposed_mqf_level ?? '6' }}</div>
    </div>
    <div>
      <div class="small muted">{{ __('Registration Status') }}</div>
      <div>
        @if($eStatus === 'completed')
          <span class="tag ok">{{ __('Completed') }}</span>
        @elseif($eStatus === 'enrolled')
          <span class="tag info">{{ __('Enrolled / In Study') }}</span>
        @else
          <span class="tag neutral">{{ ucfirst($eStatus) }}</span>
        @endif
      </div>
      <div class="s">{{ __('Ref:') }} {{ $e->reference }}</div>
    </div>
  </div>
</div>

<div class="cols">
  <!-- Left Column: Results & Breakdown -->
  <div>
    <!-- Overall Academic Performance Card (when results exist) -->
    @if($overall)
      <div class="panel" style="margin-bottom:20px;border-left:4px solid var(--accent, #1e2a6e)">
        <div class="hd">
          <h2>{{ __('Academic Performance Summary') }}</h2>
          <span class="r">
            <span class="tag {{ $overall['complete'] ? 'ok' : 'info' }}">
              {{ $overall['complete'] ? __('Final Result') : __('Provisional (:count/:total)', ['count' => $overall['verified_count'], 'total' => $overall['total_count']]) }}
            </span>
          </span>
        </div>
        <div class="bd" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:16px;text-align:center;padding:16px 20px">
          <div style="padding:12px;background:var(--bg-subtle, #f4f4f4);border-radius:0px">
            <div class="small muted">{{ __('Weighted Score') }}</div>
            <div style="font-size:24px;font-weight:700;color:var(--ink)">{{ number_format($overall['total_weighted'], 1) }}%</div>
            <div class="s muted">{{ __('Overall out of 100%') }}</div>
          </div>
          <div style="padding:12px;background:var(--bg-subtle, #f4f4f4);border-radius:0px">
            <div class="small muted">{{ __('Letter Grade') }}</div>
            <div style="font-size:24px;font-weight:700;color:var(--accent, #1e2a6e)">{{ $overall['grade'] }}</div>
            <div class="s muted">{{ $overall['grade_desc'] }}</div>
          </div>
          <div style="padding:12px;background:var(--bg-subtle, #f4f4f4);border-radius:0px">
            <div class="small muted">{{ __('Grade Points') }}</div>
            <div style="font-size:24px;font-weight:700;color:var(--ink)">{{ number_format($overall['point'], 2) }}</div>
            <div class="s muted">{{ __('4.00 Scale') }}</div>
          </div>
          <div style="padding:12px;background:var(--bg-subtle, #f4f4f4);border-radius:0px">
            <div class="small muted">{{ __('Status') }}</div>
            <div style="font-size:18px;font-weight:700;margin-top:4px" class="{{ $overall['point'] >= 2.0 ? 'ok-text' : 'warn-text' }}">
              {{ $overall['point'] >= 2.0 ? __('Passed') : __('Below Target') }}
            </div>
            <div class="s muted">{{ $overall['complete'] ? __('Requirements met') : __('Pending components') }}</div>
          </div>
        </div>
        @if(!empty($overall['latest_verification']))
          <div class="ft" style="font-size:12px;color:var(--ink-muted);background:transparent;border-top:1px solid var(--rule);padding:10px 20px">
            <span>{{ __('Verified by:') }} <strong>{{ $overall['latest_verification']->verified_by_name ?? __('Deputy Dean / Examiner') }}</strong></span>
            @if(!empty($overall['latest_verification']->verified_at))
              &middot; <span>{{ __('Date:') }} {{ \Carbon\Carbon::parse($overall['latest_verification']->verified_at)->format('d M Y, H:i') }}</span>
            @endif
            @if(!empty($overall['latest_verification']->verification_evidence))
              <div style="margin-top:4px"><em>{{ __('Remarks:') }} {{ $overall['latest_verification']->verification_evidence }}</em></div>
            @endif
          </div>
        @endif
      </div>
    @endif

    <!-- Assessment Component Breakdown Table -->
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Assessment Breakdown') }}</h2>
        <span class="r">
          @if($n)
            <span class="tag ok">{{ __(':count verified', ['count' => $n]) }}</span>
          @else
            <span class="tag n">{{ __('Nothing verified yet') }}</span>
          @endif
        </span>
      </div>
      @if($n)
        <table data-plain>
          <thead>
            <tr>
              <th>{{ __('Assessment') }}</th>
              @if($modules->isNotEmpty()) <th>{{ __('Module Mapping') }}</th> @endif
              @if(collect($results)->some(fn($r) => !empty($r->weight_percent))) <th class="num">{{ __('Weight') }}</th> @endif
              <th class="num">{{ __('Mark') }}</th>
              @if(collect($results)->some(fn($r) => isset($r->weighted_val))) <th class="num">{{ __('Weighted') }}</th> @endif
              <th class="num">{{ __('Pass mark') }}</th>
              <th>{{ __('Result') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($results as $r)
              <tr>
                <td>
                  <strong>{{ $r->name_en }}</strong>
                </td>
                @if($modules->isNotEmpty())
                  <td class="s">
                    @if(!empty($r->mc_module_id) && !empty($r->module_name))
                      <span class="tag neutral" style="font-size:11px">M{{ $r->module_seq }}: {{ \Illuminate\Support\Str::limit($r->module_name, 24) }}</span>
                    @else
                      <span class="muted" style="font-size:11px">{{ __('Comprehensive') }}</span>
                    @endif
                  </td>
                @endif
                @if(collect($results)->some(fn($item) => !empty($item->weight_percent)))
                  <td class="num">{{ !empty($r->weight_percent) ? $mk($r->weight_percent).'%' : '-' }}</td>
                @endif
                <td class="num">{{ $mk($r->score) }} / {{ $mk($r->max_mark) }}</td>
                @if(collect($results)->some(fn($item) => isset($item->weighted_val)))
                  <td class="num">
                    @if(isset($r->weighted_val))
                      {{ number_format($r->weighted_val, 1) }}%
                    @else
                      -
                    @endif
                  </td>
                @endif
                <td class="num">{{ $mk($r->pass_mark) }}</td>
                <td>
                  <span class="tag {{ (float) $r->score >= (float) $r->pass_mark ? 'ok' : 'warn' }}">
                    {{ (float) $r->score >= (float) $r->pass_mark ? __('Met') : __('Below target') }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <div class="bd muted">{{ __('Nothing is verified for this registration yet. Results appear here after the deputy dean verifies them.') }}</div>
      @endif
    </div>
    <p class="small muted" style="font-style:italic;margin-top:8px"><em>{{ __('Progress alone never gives a pass; the deputy dean confirms completion.') }}</em></p>
  </div>

  <!-- Right Column: Registration Progress & Module Badges -->
  <div>
    <!-- Registration & Learning Platform Progress -->
    <div class="panel">
      <div class="hd"><h2>{{ __('Registration') }}</h2></div>
      <div class="bd">
        <div class="small muted">{{ __('Registration Reference') }}</div>
        <div style="margin-bottom:12px;font-weight:600">{{ $e->reference }}</div>

        @if(!empty($e->registered_at))
          <div style="margin-bottom:12px">
            <div class="small muted">{{ __('Registration Date') }}</div>
            <div>{{ \Carbon\Carbon::parse($e->registered_at)->format('d M Y') }}</div>
          </div>
        @endif

        @if($e->progress_percent !== null)
          <div class="small muted">{{ __('Progress on the learning platform: :percent%', ['percent' => $e->progress_percent]) }}</div>
          <div class="bar" role="progressbar" aria-valuenow="{{ $e->progress_percent }}" aria-valuemin="0" aria-valuemax="100" style="margin-top:6px">
            <i style="width:{{ $e->progress_percent }}%"></i>
          </div>
          <div class="s muted" style="margin-top:4px">{{ __('LMS attendance and online coursework completion') }}</div>
        @endif
      </div>
    </div>

    <!-- Module Badges Card -->
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Module badges') }}</h2>
        <span class="r">{{ $badges->count() }}</span>
      </div>
      <div class="bd" style="padding:0">
        <div style="padding:12px 16px;background:var(--bg-subtle, #f4f4f4);border-bottom:1px solid var(--rule);font-size:12px;color:var(--ink-muted);line-height:1.4">
          <i class="bi bi-info-circle" style="margin-right:4px"></i>
          {{ __('In MCMS, digital badges are awarded per module upon meeting assessment standards. The overall course awards the Micro-Credential Certificate.') }}
        </div>
        <div style="padding:8px 16px">
          @forelse($modules as $m)
            <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--rule)">
              <!-- Badge Visual -->
              @if(!empty($m->badge_id))
                <x-badge :category="$e->mc_category ?? 'umc'" :size="44" />
              @elseif(!empty($m->separately_recognisable))
                <div style="width:44px;height:44px;border-radius:0px;border:2px dashed var(--rule, #d1d5db);display:flex;align-items:center;justify-content:center;color:var(--ink-muted);flex-shrink:0" title="{{ __('Eligible for badge upon completion') }}">
                  <i class="bi bi-award" style="font-size:18px;opacity:0.4"></i>
                </div>
              @else
                <div style="width:44px;height:44px;border-radius:0px;background:var(--bg-subtle, #f4f4f4);display:flex;align-items:center;justify-content:center;color:var(--ink-muted);flex-shrink:0" title="{{ __('Integrated course unit') }}">
                  <i class="bi bi-journal-text" style="font-size:16px;opacity:0.4"></i>
                </div>
              @endif

              <!-- Badge / Module Details -->
              <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                  <span style="font-weight:600;font-size:13px;color:var(--ink)">
                    {{ __('Module :seq: :name', ['seq' => $m->seq, 'name' => $m->name_en]) }}
                  </span>
                  @if(!empty($m->badge_id))
                    <span class="tag ok" style="font-size:10px">{{ __('Earned') }}</span>
                  @elseif(!empty($m->separately_recognisable))
                    <span class="tag warn" style="font-size:10px">{{ __('In Progress') }}</span>
                  @else
                    <span class="tag neutral" style="font-size:10px">{{ __('Course unit') }}</span>
                  @endif
                </div>

                @if(!empty($m->badge_id))
                  <div style="margin-top:2px;font-size:12px;color:var(--ink-muted)">
                    <span class="s">{{ $m->code }}@if(!empty($m->external_id)) &middot; UCreds {{ $m->external_id }}@endif</span>
                  </div>
                  <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
                    @if(!empty($m->achieved_at) || !empty($m->badge_issued_at))
                      <span class="s muted">
                        {{ __('Issued:') }} {{ \Carbon\Carbon::parse($m->badge_issued_at ?? $m->achieved_at)->format('d M Y') }}
                      </span>
                    @endif
                    @if(!empty($m->code))
                      <a href="{{ route('verify.show', $m->code) }}" target="_blank" class="s" style="font-weight:600;color:var(--accent, #1e2a6e)" title="{{ __('Verify badge authenticity') }}">
                        {{ __('Verify badge') }} &rarr;
                      </a>
                    @endif
                  </div>
                @elseif(!empty($m->separately_recognisable))
                  <div class="s muted" style="margin-top:2px">
                    {{ __('Badged module &middot; Awards digital badge upon passing mapped assessments.') }}
                  </div>
                @else
                  <div class="s muted" style="margin-top:2px">
                    {{ __('Component of full credential &middot; :slt SLT hours', ['slt' => $m->slt_hours ?? 40]) }}
                  </div>
                @endif
              </div>
            </div>
          @empty
            @forelse($badges as $b)
              <div style="padding:8px 0;border-bottom:1px solid var(--rule)">
                {{ __('Module :seq: :name', ['seq' => $b->seq, 'name' => $b->name_en]) }}
                <span class="s">{{ $b->code }}@if(!empty($b->external_id)) &middot; UCreds {{ $b->external_id }}@endif</span>
              </div>
            @empty
              <div class="muted" style="padding:16px">{{ __('No module badge yet. A badge is issued when a module is achieved.') }}</div>
            @endforelse
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<div style="margin-top:24px">
  <a href="{{ route('results.mine') }}" class="btn sec">
    <i class="bi bi-arrow-left" style="margin-right:6px"></i>{{ __('Back to all results') }}
  </a>
</div>
@endsection
