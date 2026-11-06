@extends('layouts.app')
@section('title', __('My degree plan'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('tasks') }}">{{ __('My learning') }}</a>
  <span>/</span>
  <span>{{ __('My degree plan') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('My degree plan') }}</h1>
    <div class="sub">{{ __('Your plan is faculty advice on how your micro-credentials could count toward a degree programme.') }}</div>
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>{{ __('A pathway is not admission, a credit transfer or an award. No number of micro-credentials or badges leads to a degree automatically. Credit goes through credit transfer evaluation by the receiving programme, credit is valid for a limited time, and you must meet the graduation conditions of the programme.') }}</div>
</div>

@forelse($plans as $s)
  @php($x = $detail[$s->id])
  @php($chosen = collect($x['items'])->filter(fn ($i) => in_array($i->id, $x['chosen'] ?? [])))
  @php($met = collect($x['checks'])->where('met', true)->count())
  @php($step = $x['award'] ? 5 : ($x['admitted'] ? 4 : ($s->status === 'suitable' ? 3 : 2)))
  @php($totCredits = (float) ($s->total_credits ?: 120))
  @php($earnedCredits = (float) ($chosen->sum('credits') ?: 0))
  @php($minUtemCredits = round($totCredits * (($x['min'] ?: 30) / 100), 1))
  @php($progressPercent = $totCredits > 0 ? min(100, round(($earnedCredits / $totCredits) * 100, 1)) : 0)

  <div class="panel plan" style="margin-bottom:24px">
    <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <h2 style="margin:0 0 4px">{{ $s->reference }}: {{ $s->code }} {{ $s->programme_name }}</h2>
        @php($decisionLabel = $s->decision ? ($decisions[$s->decision] ?? $s->decision) : ($planStatus[$s->status] ?? $s->status))
        @php($tagClass = match($s->decision ?? $s->status) {
          'suitable' => 'ok',
          'conditions', 'needs_review' => 'warn',
          'not_suitable', 'not_yet' => 'err',
          default => 'info'
        })
        <div class="s muted" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span class="tag {{ $tagClass }}"><strong>{{ __($decisionLabel) }}</strong></span>
          @if(!empty($s->faculty_code)) &middot; <span>{{ $s->faculty_code }} &ndash; {{ $s->faculty_name }}</span> @endif
        </div>
      </div>
      <div>
        <a class="btn sm" href="{{ route('public.pathways.print', $s->pathway_id) }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/></svg>
          <span>{{ __('Download Pathway Specification (PDF)') }}</span>
        </a>
      </div>
    </div>

    <!-- Clear Guided Status Banner: What This Plan Means & Who Evaluated It -->
    <div style="background:var(--bg-subtle, #f4f4f4);border-bottom:1px solid var(--rule);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:36px;height:36px;background:var(--accent, #1e2a6e);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700">
          ✓
        </div>
        <div>
          <div style="font-weight:700;font-size:14px;color:var(--ink)">
            {{ __('Faculty Evaluation:') }} {{ __($decisionLabel) }}
          </div>
          <div class="s muted" style="margin-top:2px">
            {{ __('Evaluated by') }} <strong>{{ $s->decider_name ?? __('Faculty Academic Committee') }}</strong> &middot; {{ __('Your micro-credentials have been verified against the degree syllabus and are eligible to articulate into this programme.') }}
          </div>
        </div>
      </div>
      <div>
        <a class="btn primary sm" href="{{ route('transfer') }}">
          <span class="btn-lbl">{{ __('Apply for Credit Transfer') }} &rarr;</span>
        </a>
      </div>
    </div>

    <div class="bd col">
      <!-- 5-Stage Degree Progress Tracker -->
      <div class="track" style="margin-bottom:24px">
        @foreach([[1, 'Plan prepared', 'by the faculty'], [2, 'Faculty review', $decisionLabel], [3, 'Credit transfer', 'decided by the programme'], [4, 'Admission', $x['admitted'] ? 'intake '.$x['admitted']->intake : 'not recorded'], [5, 'Award', $x['award'] ? 'recorded' : 'not yet']] as [$n, $l, $t])
          <div class="stp {{ $n < $step ? 'done' : ($n === $step ? 'now' : '') }}">
            <b>{{ __($l) }}</b>{{ __($t) }}
          </div>
        @endforeach
      </div>

      <!-- Two-Column Layout: Left (Plan Content & Progress) / Right (Advisor & Action Center) -->
      <div class="cols" style="align-items:flex-start;gap:24px">
        <!-- Main Column (Left - 68%) -->
        <div style="flex:2.2;min-width:0">
          <!-- Visual Degree Articulation Credit Bar -->
          <div class="panel" style="margin-bottom:20px;background:#ffffff;border:1px solid var(--rule)">
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
              <h2 style="margin:0;font-size:15px">{{ __('Degree Articulation Progress') }}</h2>
              <span class="tag info">{{ $earnedCredits }} / {{ $totCredits }} {{ __('Graduation Credits') }}</span>
            </div>
            <div class="bd" style="padding:16px 20px">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span><strong>{{ $progressPercent }}%</strong> {{ __('of degree curriculum articulated via micro-credentials') }}</span>
                <span class="muted">{{ $earnedCredits }} {{ __('credits mapped') }}</span>
              </div>

              <!-- Multi-segment progress visual -->
              <div style="height:12px;width:100%;background:#e0e0e0;display:flex;overflow:hidden;margin-bottom:12px">
                <div style="width:{{ max(3, $progressPercent) }}%;background:#24a148" title="{{ $earnedCredits }} credits from micro-credentials"></div>
                <div style="width:{{ max(0, min(100 - $progressPercent, 30)) }}%;background:#0f62fe;opacity:0.3" title="{{ $minUtemCredits }} credits minimum directly at UTeM"></div>
              </div>

              <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--ink-muted)">
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="display:inline-block;width:10px;height:10px;background:#24a148"></span>
                  <span>{{ __('Micro-credentials mapped') }}: <strong>{{ $earnedCredits }} cr</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="display:inline-block;width:10px;height:10px;background:#0f62fe;opacity:0.5"></span>
                  <span>{{ __('Required directly at UTeM (30%)') }}: <strong>&ge; {{ $minUtemCredits }} cr</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="display:inline-block;width:10px;height:10px;background:#e0e0e0"></span>
                  <span>{{ __('Remaining study') }}: <strong>{{ max(0, $totCredits - $earnedCredits) }} cr</strong></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Academic KPI Counters -->
          <div class="kpis" style="margin-bottom:20px">
            <div class="kpi">
              <div class="l">{{ __('Earned directly at UTeM') }}</div>
              <div class="v">{{ rtrim(rtrim((string) $s->utem_percent, '0'), '.') }}%</div>
              <div class="n">{{ __('at least :min% is required', ['min' => $x['min']]) }}</div>
            </div>
            <div class="kpi">
              <div class="l">{{ __('Credentials counted') }}</div>
              <div class="v">{{ $chosen->count() }}</div>
              <div class="n">{{ $chosen->sum('credits') ?? 0 }} {{ __('credits') }}</div>
            </div>
            <div class="kpi">
              <div class="l">{{ __('Checks met') }}</div>
              <div class="v">{{ $met }} / {{ count($x['checks']) }}</div>
              <div class="n">{{ $met === count($x['checks']) ? __('All criteria met') : __('Requirements pending') }}</div>
            </div>
            <div class="kpi">
              <div class="l">{{ __('Requirements left') }}</div>
              <div class="v">{{ count($x['remaining']) }}</div>
              <div class="n">{{ __('Remaining curriculum items') }}</div>
            </div>
          </div>

          @if($s->conditions)
            <div class="note warn" style="margin-bottom:20px">
              <div><strong>{{ __('Academic Conditions & Follow-up:') }}</strong> {{ $s->conditions }}</div>
            </div>
          @endif

          <!-- Tabbed Detail Breakdown (Credentials Counted, Checks, What is Left) -->
          <div data-tabs>
            <nav class="tabs" aria-label="Plan sections" style="margin-bottom:14px">
              <button data-tab="counted" class="on">{{ __('Credentials counted') }} ({{ $chosen->count() }})</button>
              <button data-tab="checks">{{ __('Checks') }} ({{ count($x['checks']) }})</button>
              <button data-tab="left">{{ __('What is left') }} ({{ count($x['remaining']) }})</button>
            </nav>

            <div data-pane="counted">
              <div class="panel">
                <table id="plan-credentials-table" style="margin:0">
                  <thead>
                    <tr>
                      <th style="min-width:220px">{{ __('Micro-credential') }}</th>
                      <th>{{ __('Target Degree Course') }}</th>
                      <th class="num">{{ __('Credit Equivalent') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($chosen as $i)
                      <tr>
                        <td>
                          <strong>{{ $i->name_en }}</strong>
                          <div class="s muted">{{ __('Mapped credential') }}</div>
                        </td>
                        <td>
                          <div style="font-weight:600;color:var(--accent, #1e2a6e)">{{ $i->course_code }}</div>
                        </td>
                        <td class="num">
                          <strong>{{ rtrim(rtrim((string) $i->credits, '0'), '.') }} {{ __('credits') }}</strong>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="3" class="muted" style="padding:24px;text-align:center">{{ __('No credential is counted yet.') }}</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>

            <div data-pane="checks">
              <div class="panel">
                <table id="plan-checks-table" style="margin:0">
                  <thead>
                    <tr>
                      <th>{{ __('Requirement & Policy Criterion') }}</th>
                      <th style="width:140px">{{ __('Result') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($x['checks'] as $c)
                      <tr>
                        <td>{{ $c->detail }}</td>
                        <td>
                          <span class="tag {{ $c->met ? 'ok' : 'warn' }}">{{ $c->met ? __('Met') : __('Not met') }}</span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>

            <div data-pane="left">
              <div class="panel">
                <table id="plan-remaining-table" style="margin:0">
                  <thead>
                    <tr>
                      <th>{{ __('Remaining Degree Subject') }}</th>
                      <th>{{ __('Kind') }}</th>
                      <th>{{ __('Status') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($x['remaining'] as $r)
                      <tr>
                        <td><strong>{{ $r['name'] }}</strong></td>
                        <td><span class="tag neutral">{{ __($x['kinds'][$r['kind']] ?? $r['kind']) }}</span></td>
                        <td><span class="tag {{ ($r['status'] ?? '') === 'done' ? 'ok' : '' }}">{{ __($x['statuses'][$r['status']] ?? $r['status']) }}</span></td>
                      </tr>
                    @empty
                      <tr><td colspan="3" class="muted" style="padding:24px;text-align:center">{{ __('Nothing is listed.') }}</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Collaborative Responsibilities Explanation -->
          <div class="panel" style="margin-top:20px;background:var(--bg-subtle, #f4f4f4)">
            <div class="hd"><h2 style="margin:0;font-size:14px">{{ __('Student & Faculty Collaboration Guide') }}</h2></div>
            <div class="bd" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;font-size:13px;line-height:1.5">
              <div>
                <strong style="color:var(--accent, #1e2a6e);display:block;margin-bottom:4px">
                  {{ __('Role of Academic Advisor / Faculty:') }}
                </strong>
                <ul style="margin:0;padding-left:18px;color:var(--ink-muted)">
                  <li>{{ __('Evaluates micro-credential syllabi and learning outcomes.') }}</li>
                  <li>{{ __('Confirms course-by-course equivalence against degree syllabus.') }}</li>
                  <li>{{ __('Verifies MQA 30% minimum UTeM residency requirement.') }}</li>
                  <li>{{ __('Records official conditions and recommends next steps.') }}</li>
                </ul>
              </div>
              <div>
                <strong style="color:var(--accent, #1e2a6e);display:block;margin-bottom:4px">
                  {{ __('Your Next Steps as Student:') }}
                </strong>
                <ul style="margin:0;padding-left:18px;color:var(--ink-muted)">
                  <li>{{ __('Maintain required academic conditions (e.g. CGPA >= 2.0).') }}</li>
                  <li>{{ __('Complete eligible micro-credentials to earn verified badges.') }}</li>
                  <li>{{ __('Lodge formal Credit Transfer application once admitted.') }}</li>
                  <li>{{ __('Consult with advisor if you wish to adjust your plan.') }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar Column (Right - 32%) -->
        <div style="flex:1;min-width:280px">
          <!-- Academic Advisor Contact Card -->
          <div class="panel" style="margin-bottom:20px;border-top:3px solid var(--accent, #1e2a6e)">
            <div class="hd">
              <h2 style="margin:0;font-size:14px">{{ __('Academic Advisor') }}</h2>
            </div>
            <div class="bd">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div style="width:40px;height:40px;background:#0f62fe;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px">
                  {{ mb_substr($s->decider_name ?? 'Advisor', 0, 2) }}
                </div>
                <div>
                  <div style="font-weight:700;font-size:14px;color:var(--ink)">
                    {{ $s->decider_name ?? __('Faculty Academic Advisor') }}
                  </div>
                  <div class="s muted">
                    {{ $s->faculty_code ?? 'FTMK' }} &middot; {{ __('Degree Articulation Evaluator') }}
                  </div>
                </div>
              </div>
              <p class="small muted" style="margin:0 0 14px;line-height:1.4">
                {{ __('Your academic advisor evaluates your prior credentials, confirms course equivalence, and guides your degree articulation.') }}
              </p>
              @if(!empty($s->decider_email))
                <a class="btn sec sm" href="mailto:{{ $s->decider_email }}?subject=Academic Advising Consultation - Plan {{ $s->reference }}" style="width:100%;text-align:center;display:block">
                  <span class="btn-lbl">{{ __('Email Advisor') }}</span>
                </a>
              @endif
            </div>
          </div>

          <!-- Action Center -->
          <div class="panel" style="margin-bottom:20px">
            <div class="hd"><h2 style="margin:0;font-size:14px">{{ __('Action Center') }}</h2></div>
            <div class="bd">
              <a class="btn primary sm" href="{{ route('transfer') }}" style="width:100%;text-align:center;display:block;margin-bottom:10px">
                <span class="btn-lbl">{{ __('Go to Credit Transfer') }} &rarr;</span>
              </a>
              <a class="btn sec sm" href="{{ route('catalogue') }}" style="width:100%;text-align:center;display:block;margin-bottom:10px">
                <span class="btn-lbl">{{ __('Find Next Micro-Credential') }}</span>
              </a>
              <a class="btn ter sm" href="{{ route('public.pathways.print', $s->pathway_id) }}" target="_blank" style="width:100%;text-align:center;display:block">
                <span class="btn-lbl">{{ __('Download Specification (PDF)') }}</span>
              </a>
            </div>
          </div>

          <!-- Articulation Stages Status Checklist -->
          <div class="panel">
            <div class="hd"><h2 style="margin:0;font-size:14px">{{ __('Articulation Roadmap') }}</h2></div>
            <div class="bd">
              <div class="checks">
                <div class="check" style="margin-bottom:10px">
                  <span class="dot ok"></span>
                  <div class="grow">
                    <strong>{{ __('Study Plan Mapped') }}</strong>
                    <span class="s muted" style="display:block;font-size:11px">{{ __('Prepared by faculty') }}</span>
                  </div>
                </div>
                <div class="check" style="margin-bottom:10px">
                  <span class="dot {{ $s->status === 'suitable' ? 'ok' : 'warn' }}"></span>
                  <div class="grow">
                    <strong>{{ __('Faculty Review') }}</strong>
                    <span class="s muted" style="display:block;font-size:11px">{{ $planStatus[$s->status] }}</span>
                  </div>
                </div>
                <div class="check" style="margin-bottom:10px">
                  <span class="dot {{ $x['admitted'] ? 'ok' : 'warn' }}"></span>
                  <div class="grow">
                    <strong>{{ __('Degree Admission') }}</strong>
                    <span class="s muted" style="display:block;font-size:11px">{{ $x['admitted'] ? 'Intake '.$x['admitted']->intake : __('Pending Pejabat Pendaftar intake') }}</span>
                  </div>
                </div>
                <div class="check" style="margin-bottom:10px">
                  <span class="dot {{ $step >= 3 ? 'ok' : 'n' }}"></span>
                  <div class="grow">
                    <strong>{{ __('Formal Credit Transfer') }}</strong>
                    <span class="s muted" style="display:block;font-size:11px">{{ __('Decided case by case by the programme') }}</span>
                  </div>
                </div>
                <div class="check">
                  <span class="dot {{ $x['award'] ? 'ok' : 'n' }}"></span>
                  <div class="grow">
                    <strong>{{ __('Degree Graduation') }}</strong>
                    <span class="s muted" style="display:block;font-size:11px">{{ $x['award'] ? __('Award recorded') : __('Conferred upon completing all degree credits') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@empty
  <div class="panel" style="margin-bottom:24px">
    <div class="bd col" style="padding:32px 28px">
      <div style="max-width:800px">
        <div style="font-size:12px;font-weight:600;color:var(--primary, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">{{ __('Stackable Degree Articulation') }}</div>
        <h2 style="font-size:20px;font-weight:700;margin:0 0 8px;color:var(--ink)">{{ __('You do not have an active degree plan yet') }}</h2>
        <p class="muted" style="font-size:14px;line-height:1.6;margin:0 0 24px">
          {{ __('A stackable degree study plan maps eligible micro-credentials into a target UTeM bachelor programme. Under Malaysian Qualifications Agency (MQA) regulations and UTeM Senate policy, plans are formulated collaboratively with your Faculty Academic Advisor to verify course equivalence and institutional residency requirements.') }}
        </p>

        <!-- 4-Step Interactive Flow Breakdown -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:28px">
          <div style="background:var(--bg-subtle, #f4f4f4);border:1px solid var(--rule);padding:16px;border-top:3px solid var(--accent, #1e2a6e)">
            <div style="font-size:12px;font-weight:700;color:var(--accent, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
              {{ __('Step 1') }}
            </div>
            <strong style="display:block;font-size:14px;margin-bottom:6px;color:var(--ink)">{{ __('Explore Pathways') }}</strong>
            <p class="small muted" style="margin:0;line-height:1.4">
              {{ __('Browse published degree pathways to discover which micro-credentials articulate into UTeM degree programmes.') }}
            </p>
          </div>

          <div style="background:var(--bg-subtle, #f4f4f4);border:1px solid var(--rule);padding:16px;border-top:3px solid var(--accent, #1e2a6e)">
            <div style="font-size:12px;font-weight:700;color:var(--accent, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
              {{ __('Step 2') }}
            </div>
            <strong style="display:block;font-size:14px;margin-bottom:6px;color:var(--ink)">{{ __('Earn Credentials') }}</strong>
            <p class="small muted" style="margin:0;line-height:1.4">
              {{ __('Complete credit-bearing micro-credentials (UMC or Quality-Verified SAMC) to earn verified digital badges.') }}
            </p>
          </div>

          <div style="background:var(--bg-subtle, #f4f4f4);border:1px solid var(--rule);padding:16px;border-top:3px solid var(--accent, #1e2a6e)">
            <div style="font-size:12px;font-weight:700;color:var(--accent, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
              {{ __('Step 3') }}
            </div>
            <strong style="display:block;font-size:14px;margin-bottom:6px;color:var(--ink)">{{ __('Faculty Advising') }}</strong>
            <p class="small muted" style="margin:0;line-height:1.4">
              {{ __('Meet your faculty academic advisor. They evaluate equivalence, verify the 30% UTeM residency rule, and formulate your plan.') }}
            </p>
          </div>

          <div style="background:var(--bg-subtle, #f4f4f4);border:1px solid var(--rule);padding:16px;border-top:3px solid var(--accent, #1e2a6e)">
            <div style="font-size:12px;font-weight:700;color:var(--accent, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
              {{ __('Step 4') }}
            </div>
            <strong style="display:block;font-size:14px;margin-bottom:6px;color:var(--ink)">{{ __('Credit Transfer') }}</strong>
            <p class="small muted" style="margin:0;line-height:1.4">
              {{ __('Once formally admitted to the degree programme, apply to have your accumulated credits officially recognized.') }}
            </p>
          </div>
        </div>

        <!-- Action CTAs -->
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <a class="btn primary sm" href="{{ route('public.pathways') }}">
            <span class="btn-lbl">{{ __('Explore Degree Pathways') }} &rarr;</span>
          </a>
          <a class="btn sec sm" href="{{ route('catalogue') }}">
            <span class="btn-lbl">{{ __('Browse Micro-Credentials') }}</span>
          </a>
        </div>
      </div>
    </div>
  </div>
@endforelse
@endsection