@extends('layouts.app')
@section('title', __('My degree plan'))
@section('content')
<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('My degree plan') }}</h1>
    <div class="sub">{{ __('Track your micro-credential progress against a Senate-approved UTeM degree pathway.') }}</div>
  </div>
  <div class="act">
    <a class="btn primary sm" href="{{ route('plan.choose') }}">{{ __('Browse Degree Pathways') }}</a>
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>{{ __('This plan is academic planning advice only. A pathway is not admission, a credit transfer or an award. No number of micro-credentials or badges leads to a degree automatically. Credit goes through credit transfer evaluation by the receiving programme upon admission, credit validity limits apply, and you must meet all graduation conditions of the programme.') }}</div>
</div>

@forelse($plans as $s)
  @php($x = $detail[$s->id])
  @php($items = collect($x['items']))
  @php($earnedItems = $items->filter(fn ($i) => in_array($i->micro_credential_id, $earnedMcIds ?? [])))
  @php($enrolledItems = $items->filter(fn ($i) => ! in_array($i->micro_credential_id, $earnedMcIds ?? []) && in_array($i->micro_credential_id, $enrolledMcIds ?? [])))
  @php($totCredits = (float) ($s->total_credits ?: 120))
  @php($earnedCredits = (float) $earnedItems->sum('credits'))
  @php($enrolledCredits = (float) $enrolledItems->sum('credits'))
  @php($pathwayCredits = (float) $items->sum('credits'))
  @php($minUtemPct = (float) ($x['min'] ?: 30))
  @php($minUtemCredits = round($totCredits * ($minUtemPct / 100), 1))
  @php($earnedPercent = $totCredits > 0 ? min(100, round(($earnedCredits / $totCredits) * 100, 1)) : 0)
  @php($enrolledPercent = $totCredits > 0 ? min(100 - $earnedPercent, round(($enrolledCredits / $totCredits) * 100, 1)) : 0)
  @php($allEarned = $items->count() > 0 && $earnedItems->count() >= $items->count())
  @php($step = $x['award'] ? 5 : ($x['admitted'] ? 4 : ($allEarned ? 3 : ($earnedItems->count() > 0 ? 2 : 1))))

  <div class="panel plan" style="margin-bottom:24px">
    <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <h2 style="margin:0 0 4px">{{ $s->code }} {{ $s->programme_name }}</h2>
        <div class="s muted" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          @if(!empty($s->faculty_code))
            <span class="tag info"><strong>{{ $s->faculty_code }}</strong></span>
          @endif
          @if(!empty($s->mqf_level))
            <span class="tag neutral">MQF {{ $s->mqf_level }}</span>
          @endif
          <span>{{ mcms_num($totCredits) }} {{ __('graduation credits') }}</span>
          @if(!empty($s->faculty_name))
            &middot; <span>{{ $s->faculty_name }}</span>
          @endif
        </div>
      </div>
      <div style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap">
        @if($x['award'])
          <span class="tag ok"><strong>{{ __('Award Recorded') }}</strong></span>
        @elseif($x['admitted'])
          <span class="tag info"><strong>{{ __('Admitted') }} &middot; {{ $x['admitted']->intake }}</strong></span>
        @else
          <span class="tag neutral">{{ __('Planning') }}</span>
        @endif
      </div>
    </div>

    <div class="bd col">
      <!-- Independent Learner Journey Stepper -->
      <div class="track" style="margin-bottom:24px">
        @foreach([
          [1, 'Pathway selected', 'Self-selected'],
          [2, 'Micro-credentials', $earnedItems->count().' / '.$items->count().' earned'],
          [3, 'Programme admission', $x['admitted'] ? 'Intake '.$x['admitted']->intake : 'Apply when ready'],
          [4, 'Credit transfer', 'Evaluated upon admission'],
          [5, 'Degree award', $x['award'] ? 'Recorded' : 'Upon graduation'],
        ] as [$n, $l, $t])
          <div class="stp {{ $n < $step ? 'done' : ($n === $step ? 'now' : '') }}">
            <b>{{ __($l) }}</b>{{ __($t) }}
          </div>
        @endforeach
      </div>

      <div class="cols" style="align-items:flex-start;gap:24px">
        <!-- Main Column -->
        <div style="flex:2.2;min-width:0">
          <!-- Visual Progress Bar -->
          <div class="panel" style="margin-bottom:20px;background:var(--card-bg, #ffffff);border:1px solid var(--rule)">
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
              <h2 style="margin:0;font-size:15px">{{ __('Pathway Credit Progress') }}</h2>
              <span class="tag info">{{ mcms_num($earnedCredits) }} / {{ mcms_num($totCredits) }} {{ __('Graduation Credits') }}</span>
            </div>
            <div class="bd" style="padding:16px 20px">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px;flex-wrap:wrap;gap:6px">
                <span><strong>{{ $earnedPercent }}%</strong> {{ __('of degree credits earned via micro-credentials') }}</span>
                <span class="muted">{{ mcms_num($pathwayCredits) }} {{ __('credits available in this pathway') }}</span>
              </div>

              <div style="height:12px;width:100%;background:var(--hairline);display:flex;overflow:hidden;margin-bottom:12px">
                @if($earnedPercent > 0)
                  <div style="width:{{ max(2, $earnedPercent) }}%;background:#24a148" title="{{ mcms_num($earnedCredits) }} credits earned"></div>
                @endif
                @if($enrolledPercent > 0)
                  <div style="width:{{ max(2, $enrolledPercent) }}%;background:#0f62fe" title="{{ mcms_num($enrolledCredits) }} credits in progress"></div>
                @endif
              </div>

              <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--ink-muted)">
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="display:inline-block;width:10px;height:10px;background:#24a148"></span>
                  <span>{{ __('Earned') }}: <strong>{{ mcms_num($earnedCredits) }} cr</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="display:inline-block;width:10px;height:10px;background:#0f62fe"></span>
                  <span>{{ __('In progress') }}: <strong>{{ mcms_num($enrolledCredits) }} cr</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="display:inline-block;width:10px;height:10px;background:var(--hairline)"></span>
                  <span>{{ __('Remaining degree study') }}: <strong>{{ mcms_num(max(0, $totCredits - $earnedCredits)) }} cr</strong></span>
                </div>
              </div>
            </div>
          </div>

          <!-- KPI Row -->
          <div class="kpis" style="margin-bottom:20px">
            <div class="kpi">
              <div class="l">{{ __('Earned credentials') }}</div>
              <div class="v">{{ $earnedItems->count() }} / {{ $items->count() }}</div>
              <div class="n">{{ mcms_num($earnedCredits) }} {{ __('credits earned') }}</div>
            </div>
            <div class="kpi">
              <div class="l">{{ __('Currently enrolled') }}</div>
              <div class="v">{{ $enrolledItems->count() }}</div>
              <div class="n">{{ mcms_num($enrolledCredits) }} {{ __('credits in progress') }}</div>
            </div>
            <div class="kpi">
              <div class="l">{{ __('Pathway total') }}</div>
              <div class="v">{{ mcms_num($pathwayCredits) }} cr</div>
              <div class="n">{{ $items->count() }} {{ __('mapped micro-credentials') }}</div>
            </div>
            <div class="kpi">
              <div class="l">{{ __('Direct UTeM minimum') }}</div>
              <div class="v">&ge; {{ mcms_num($minUtemPct) }}%</div>
              <div class="n">&ge; {{ mcms_num($minUtemCredits) }} {{ __('credits at UTeM') }}</div>
            </div>
          </div>

          <!-- Mapped Micro-Credentials Table -->
          <div class="panel">
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
              <h2 style="margin:0;font-size:15px">{{ __('Mapped Micro-Credentials in This Pathway') }}</h2>
              <span class="s muted">{{ $items->count() }} {{ __('items') }}</span>
            </div>
            <table id="plan-credentials-table" style="margin:0">
              <thead>
                <tr>
                  <th style="min-width:220px">{{ __('Micro-credential') }}</th>
                  <th>{{ __('Equivalent Degree Course') }}</th>
                  <th class="num">{{ __('Credits') }}</th>
                  <th>{{ __('Status') }}</th>
                  <th style="width:120px"></th>
                </tr>
              </thead>
              <tbody>
                @forelse($items as $i)
                  @php($isEarned = in_array($i->micro_credential_id, $earnedMcIds ?? []))
                  @php($isEnrolled = ! $isEarned && in_array($i->micro_credential_id, $enrolledMcIds ?? []))
                  <tr>
                    <td>
                      <strong>{{ $i->name_en }}</strong>
                    </td>
                    <td>
                      <div style="font-weight:600;color:var(--accent, #1e2a6e)">{{ $i->course_code }}</div>
                    </td>
                    <td class="num">
                      <strong>{{ mcms_num($i->credits) }}</strong>
                    </td>
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
                        <a class="btn sm ter" href="{{ route('catalogue', ['q' => $i->name_en]) }}">{{ __('Find & enroll') }}</a>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="muted" style="padding:24px;text-align:center">{{ __('No micro-credentials are mapped to this pathway yet.') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right Sidebar -->
        <div style="flex:1;min-width:260px">
          <div class="panel" style="margin-bottom:20px">
            <div class="hd"><h2 style="margin:0;font-size:14px">{{ __('Actions') }}</h2></div>
            <div class="bd" style="display:flex;flex-direction:column;gap:8px">
              <a class="btn primary sm" href="{{ route('programmes.show', $s->code) }}" style="width:100%;text-align:center;display:block">
                {{ __('View Full Degree Plan') }}
              </a>
              <a class="btn sec sm" href="{{ route('catalogue') }}" style="width:100%;text-align:center;display:block">
                {{ __('Find Next Micro-Credential') }}
              </a>
              <a class="btn ter sm" href="{{ route('transfer') }}" style="width:100%;text-align:center;display:block">
                {{ __('Go to Credit Transfer') }}
              </a>
              <a class="btn ter sm" href="{{ route('programmes.compare', ['from' => $s->code]) }}" style="width:100%;text-align:center;display:block">
                {{ __('Compare Programmes') }}
              </a>
              <a class="btn ter sm" href="{{ route('public.pathways.print', $s->pathway_id) }}" target="_blank" style="width:100%;text-align:center;display:block">
                {{ __('Download Specification (PDF)') }}
              </a>

              <div style="border-top:1px solid var(--color-line-hairline, #e2e8f0);padding-top:10px;margin-top:4px;display:flex;flex-direction:column;gap:8px">
                @if($x['award'])
                  <a class="btn ter sm" href="{{ route('plan.choose') }}" style="width:100%;text-align:center;display:block">
                    {{ __('Plan Next Qualification') }}
                  </a>
                @elseif($x['admitted'])
                  <div class="s muted" style="font-size:12px;line-height:1.4">
                    {{ __('Locked to your admitted programme (:intake).', ['intake' => $x['admitted']->intake]) }}
                  </div>
                @else
                  <a class="btn ter sm" href="{{ route('plan.choose') }}" style="width:100%;text-align:center;display:block">
                    {{ __('Switch Pathway') }}
                  </a>
                  <form method="post" action="{{ route('plan.selfDestroy', $s->id) }}" onsubmit="return confirm('{{ __('Remove this degree pathway from your plan? Your earned micro-credentials and badges are not affected.') }}')">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn ter sm" style="width:100%;display:flex;justify-content:center;text-align:center;color:var(--color-error, #da1e28)">{{ __('Remove Plan') }}</button>
                  </form>
                @endif
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="hd"><h2 style="margin:0;font-size:14px">{{ __('Pathway Policy Notes') }}</h2></div>
            <div class="bd" style="font-size:13px;line-height:1.5;color:var(--ink-muted)">
              <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:8px">
                <li>{{ __('At least :min% of graduating credits (:cr cr) must be earned directly at UTeM.', ['min' => mcms_num($minUtemPct), 'cr' => mcms_num($minUtemCredits)]) }}</li>
                <li>{{ __('Earned micro-credentials are automatically matched to this pathway.') }}</li>
                <li>{{ __('Formal credit transfer evaluation is conducted by the receiving faculty upon degree admission.') }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@empty
  <div class="panel" style="margin-bottom:24px">
    <div class="bd col" style="padding:32px 28px">
      <div style="max-width:760px">
        <div style="font-size:12px;font-weight:600;color:var(--primary, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">{{ __('Stackable Degree Pathway') }}</div>
        <h2 style="font-size:20px;font-weight:700;margin:0 0 8px;color:var(--ink)">{{ __('No degree pathway selected yet') }}</h2>
        <p class="muted" style="font-size:14px;line-height:1.6;margin:0 0 24px">
          {{ __('Browse published Senate-approved degree pathways to see which micro-credentials map to UTeM bachelor programmes, and select a pathway to track your progress independently.') }}
        </p>

        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <a class="btn primary sm" href="{{ route('plan.choose') }}">{{ __('Browse Degree Pathways') }}</a>
          <a class="btn sec sm" href="{{ route('catalogue') }}">{{ __('Explore Micro-Credentials') }}</a>
        </div>
      </div>
    </div>
  </div>
@endforelse
@endsection
