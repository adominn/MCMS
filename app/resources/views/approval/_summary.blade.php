@php
  $isMs = app()->getLocale() === 'ms';
  $cat = strtoupper($v->category ?? 'UMC');
  $catFull = [
    'umc' => __('Unbundled Micro-Credential'),
    'samc' => __('Stand-Alone Micro-Credential'),
    'mce' => __('Micro-Credential Eksekutif'),
  ][$v->category ?? ''] ?? $cat;
  $propTypeLabel = [
    'new' => __('New'),
    'revision' => __('Revision'),
    'reoffer' => __('Reoffer'),
  ][$v->proposal_type ?? 'new'] ?? ucfirst((string) ($v->proposal_type ?? 'new'));

  $sltSum = (int) $modules->sum('slt_hours');
  $weightsSum = (float) $comps->sum('weight_percent');

  $summaryEvData = \App\Modules\Curriculum\ProposalChecker::parseEvidenceFiles($v->evidence_files ?? null);
  $summaryHasEv = !empty($summaryEvData['by_type']) || !empty($summaryEvData['legacy']);
  $evTypes = is_string($v->evidence_types ?? null) ? json_decode($v->evidence_types, true) : ($v->evidence_types ?? []);
  if (!is_array($evTypes)) { $evTypes = []; }

  $credTypes = is_string($v->credential_types ?? null) ? json_decode($v->credential_types, true) : ($v->credential_types ?? []);
  if (!is_array($credTypes)) { $credTypes = []; }
  $credDisplay = [];
  if (in_array('badge', $credTypes, true)) { $credDisplay[] = __('Digital Badge (Open Badges 3.0)'); }
  if (in_array('certificate', $credTypes, true)) { $credDisplay[] = __('Official UTeM Certificate'); }
  if (in_array('vc', $credTypes, true) || in_array('digital_credential', $credTypes, true)) { $credDisplay[] = __('Digital Credential (W3C)'); }
  if ($credDisplay === []) { $credDisplay[] = __('Digital Badge (Open Badges 3.0)'); }

  $loCodeById = [];
  foreach ($los as $idx => $loItem) {
    $loCodeById[$loItem->id] = $loItem->code ?: ('LO' . ($idx + 1));
  }
  $hasParentClo = $los->contains(fn ($l) => !empty($l->parent_clo_id));

  $totalReady = count($cfg['readiness']);
  $readyDone = 0;
  foreach ($cfg['readiness'] as $rk => $rl) {
    $rst = $ready[$rk]->status ?? 'not_yet';
    if ($rst === 'yes' || $rst === 'na') { $readyDone++; }
  }

  $deliveryLabel = __($cfg['delivery_modes'][$v->delivery_mode] ?? ($v->delivery_mode ?: '-'));
  if (($v->delivery_mode ?? '') === 'other' && !empty($v->delivery_mode_other)) {
    $deliveryLabel .= ' (' . $v->delivery_mode_other . ')';
  }
  $primaryTitle = ($isMs && !empty($v->name_ms)) ? $v->name_ms : $v->name_en;
  $secondaryTitle = ($isMs && !empty($v->name_ms) && !empty($v->name_en) && $v->name_ms !== $v->name_en)
    ? $v->name_en
    : ((!$isMs && !empty($v->name_ms) && $v->name_ms !== $v->name_en) ? $v->name_ms : null);
@endphp

<style>
  .ps-panel { border: 1px solid var(--color-card-border, #090a0f); border-radius: 6px; background: var(--card-bg, #ffffff); overflow: hidden; }
  .ps-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 16px 20px; border-bottom: 1px solid var(--hairline); background: var(--surface, #f8fafc); }
  .ps-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 4px; }
  .ps-title { font-size: 17px; font-weight: 700; color: var(--ink); margin: 0; line-height: 1.3; }
  .ps-subtitle { font-size: 13px; color: var(--muted); margin-top: 3px; }
  .ps-tags { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

  .ps-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-bottom: 1px solid var(--hairline); background: var(--card-bg, #ffffff); }
  .ps-fact { padding: 12px 16px; border-right: 1px solid var(--hairline); }
  .ps-fact:last-child { border-right: 0; }
  .ps-fact-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 4px; }
  .ps-fact-val { font-size: 13px; font-weight: 600; color: var(--ink); line-height: 1.35; }
  .ps-fact-sub { font-size: 12px; color: var(--muted); margin-top: 2px; line-height: 1.35; }

  .ps-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 20px; }
  .ps-sec { display: flex; flex-direction: column; gap: 10px; }
  .ps-sec-hd { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-bottom: 6px; border-bottom: 1px solid var(--hairline); }
  .ps-sec-hd h3 { margin: 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--ink); }
  .ps-sec-meta { font-size: 12px; color: var(--muted); font-family: var(--mono); }

  .ps-kv { display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 8px 16px; font-size: 13px; line-height: 1.5; }
  .ps-kv-k { color: var(--muted); font-weight: 600; }
  .ps-kv-v { color: var(--ink); }

  .ps-ev-list { display: flex; flex-direction: column; gap: 6px; margin: 0; padding: 0; list-style: none; }
  .ps-ev-item { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; padding: 7px 10px; background: var(--surface, #f8fafc); border: 1px solid var(--hairline); border-radius: 4px; font-size: 12.5px; }

  .ps-umc-box { background: var(--surface, #f8fafc); border: 1px solid var(--hairline); border-left: 3px solid var(--accent, #0052ff); border-radius: 4px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }

  .ps-table-wrap { border: 1px solid var(--hairline); border-radius: 4px; overflow-x: auto; }
  .ps-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .ps-table th { text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); background: var(--surface, #f8fafc); padding: 8px 12px; border-bottom: 1px solid var(--hairline); }
  .ps-table td { padding: 9px 12px; border-bottom: 1px solid var(--hairline); vertical-align: top; color: var(--ink); line-height: 1.45; }
  .ps-table tbody tr:last-child td { border-bottom: 0; }
  .ps-table tfoot td { padding: 8px 12px; background: var(--surface, #f8fafc); border-top: 1px solid var(--hairline); font-weight: 700; color: var(--ink); }
  .ps-table .num { text-align: right; white-space: nowrap; font-family: var(--mono); }

  .ps-std-strip { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
  .ps-std-box { padding: 9px 12px; background: var(--surface, #f8fafc); border: 1px solid var(--hairline); border-radius: 4px; font-size: 12.5px; }
  .ps-std-box .k { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 3px; }

  .ps-cred-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 18px; font-size: 13px; }
  .ps-cred-item { padding: 9px 12px; background: var(--surface, #f8fafc); border: 1px solid var(--hairline); border-radius: 4px; }
  .ps-cred-item .k { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 3px; }

  .ps-ready-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
  .ps-ready-item { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 8px 12px; border: 1px solid var(--hairline); border-radius: 4px; background: var(--card-bg, #ffffff); font-size: 12.5px; }
  .ps-ready-note { font-size: 11.5px; color: var(--muted); margin-top: 3px; line-height: 1.35; }

  @media (max-width: 860px) {
    .ps-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ps-fact { border-bottom: 1px solid var(--hairline); }
    .ps-kv { grid-template-columns: 1fr; gap: 2px; }
    .ps-std-strip, .ps-cred-grid, .ps-ready-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="ps-panel">
  {{-- Header --}}
  <div class="ps-head">
    <div style="min-width:0;flex:1">
      <div class="ps-eyebrow">{{ __('Proposal summary') }}</div>
      <h2 class="ps-title">{{ $primaryTitle }}</h2>
      @if($secondaryTitle)
        <div class="ps-subtitle">{{ $secondaryTitle }}</div>
      @endif
    </div>
    <div class="ps-tags">
      <span class="tag info" style="font-weight:700">{{ $cat }}</span>
      <span class="tag">{{ $v->ptj_code }}</span>
      <span class="tag">{{ $propTypeLabel }}</span>
      <span class="tag" style="font-family:var(--mono)">v{{ $v->version_no ?? 1 }}</span>
    </div>
  </div>

  {{-- Key Facts Strip --}}
  <div class="ps-facts">
    <div class="ps-fact">
      <div class="ps-fact-label">{{ __('Proposer & Faculty') }}</div>
      <div class="ps-fact-val">{{ $v->owner_name }}</div>
      <div class="ps-fact-sub">{{ $v->ptj_code }} &middot; {{ $catFull }}</div>
    </div>
    <div class="ps-fact">
      <div class="ps-fact-label">{{ __('Discipline & Target') }}</div>
      <div class="ps-fact-val">{{ $v->discipline ?: '–' }}</div>
      <div class="ps-fact-sub">{{ $v->target_group ?: '–' }}</div>
    </div>
    <div class="ps-fact">
      <div class="ps-fact-label">{{ __('Delivery & Duration') }}</div>
      <div class="ps-fact-val">{{ $deliveryLabel }}</div>
      <div class="ps-fact-sub">{{ $v->delivery_language ?: '–' }}@if(!empty($v->offering_period)) &middot; {{ $v->offering_period }}@endif</div>
    </div>
    <div class="ps-fact">
      <div class="ps-fact-label">{{ __('SLT · Credit · MQF') }}</div>
      <div class="ps-fact-val">
        {{ $sltSum }} h SLT
        @if($v->proposed_credit !== null)
          &middot; {{ mcms_num($v->proposed_credit) }} {{ __('cr') }}
        @elseif($v->category === 'mce')
          &middot; {{ __('Non-credit') }}
        @endif
        @if($v->proposed_mqf_level)
          &middot; MQF {{ $v->proposed_mqf_level }}
        @endif
      </div>
      <div class="ps-fact-sub">{{ $modules->count() }} {{ __('modules') }} &middot; {{ $los->count() }} {{ __('LOs') }} &middot; {{ $comps->count() }} {{ __('assessments') }}</div>
    </div>
  </div>

  <div class="ps-body">
    {{-- 1. Rationale & Supporting Evidence --}}
    <div class="ps-sec">
      <div class="ps-sec-hd">
        <h3>{{ __('Rationale and justification') }}</h3>
        @if(!empty($evTypes))
          <span class="ps-sec-meta">{{ count($evTypes) }} {{ __('evidence type(s)') }}</span>
        @endif
      </div>
      <div class="ps-kv">
        <div class="ps-kv-k">{{ __('Need') }}</div>
        <div class="ps-kv-v">{{ $v->need ?: '–' }}</div>

        @if(!empty($v->added_value))
          <div class="ps-kv-k">{{ __('What is new / Added value') }}</div>
          <div class="ps-kv-v">{{ $v->added_value }}</div>
        @endif

        @if(!empty($v->category_justification))
          <div class="ps-kv-k">{{ __('Category justification') }}</div>
          <div class="ps-kv-v">{{ $v->category_justification }}</div>
        @endif

        @if(!empty($v->existing_equivalents))
          <div class="ps-kv-k">{{ __('Relation to existing courses') }}</div>
          <div class="ps-kv-v">{{ $v->existing_equivalents }}</div>
        @endif

        @if(!empty($evTypes))
          <div class="ps-kv-k">{{ __('Evidence basis') }}</div>
          <div class="ps-kv-v" style="display:flex;gap:6px;flex-wrap:wrap">
            @foreach($evTypes as $etKey)
              <span class="tag">{{ __($cfg['evidence_types'][$etKey] ?? $etKey) }}@if($etKey === 'other' && !empty($summaryEvData['other_label'])) ({{ $summaryEvData['other_label'] }})@endif</span>
            @endforeach
          </div>
        @endif
      </div>

      @if($summaryHasEv)
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px">{{ __('Evidence of need documents') }}</div>
          <ul class="ps-ev-list">
            @foreach($summaryEvData['by_type'] as $evKey => $evList)
              <li class="ps-ev-item">
                <strong>{{ __($cfg['evidence_types'][$evKey] ?? $evKey) }}@if($evKey === 'other' && !empty($summaryEvData['other_label'])) ({{ $summaryEvData['other_label'] }})@endif:</strong>
                @foreach($evList as $ef)
                  <a href="{{ route('proposals.evidence', [$v->id, $ef]) }}" target="_blank" rel="noopener">{{ $ef }}</a>
                @endforeach
              </li>
            @endforeach
            @if(!empty($summaryEvData['legacy']))
              <li class="ps-ev-item">
                <strong>{{ __('General') }}:</strong>
                @foreach($summaryEvData['legacy'] as $ef)
                  <a href="{{ route('proposals.evidence', [$v->id, $ef]) }}" target="_blank" rel="noopener">{{ $ef }}</a>
                @endforeach
              </li>
            @endif
          </ul>
        </div>
      @endif
    </div>

    {{-- 2. Parent Course & Unbundling Model (UMC only) --}}
    @if($course)
      @php
        $modelKey = $v->credit_model;
        $modelMeta = config("proposals.unbundling_models.$modelKey");
        $sltMatchesParent = ($sltSum === (int) $course->slt_hours);
      @endphp
      <div class="ps-sec">
        <div class="ps-sec-hd">
          <h3>{{ __('Parent course and unbundling model') }}</h3>
          <span class="tag {{ $sltMatchesParent ? 'ok' : 'warn' }}">
            {{ $sltMatchesParent ? __('SLT matches parent') : __('SLT differs from parent') }}
          </span>
        </div>
        <div class="ps-umc-box">
          <div class="ps-kv">
            <div class="ps-kv-k">{{ __('Parent programme') }}</div>
            <div class="ps-kv-v">{{ $course->programme ?: '–' }}</div>

            <div class="ps-kv-k">{{ __('Parent course') }}</div>
            <div class="ps-kv-v"><strong>{{ $course->code }}</strong> {{ $course->name }} &middot; {{ $course->credit }} {{ __('credits') }} &middot; {{ $course->slt_hours }} h SLT</div>

            <div class="ps-kv-k">{{ __('Unbundling model') }}</div>
            <div class="ps-kv-v">
              @if($modelMeta)
                <span class="tag" style="background:var(--card-bg);border:1px solid {{ $modelMeta['badge_color'] }};color:{{ $modelMeta['badge_color'] }};font-weight:700">
                  ({{ $modelMeta['letter'] }}) {{ $isMs ? $modelMeta['name_ms'] : $modelMeta['name_en'] }}
                </span>
                <div class="muted small" style="margin-top:4px">{{ $isMs ? $modelMeta['desc_ms'] : $modelMeta['desc_en'] }}</div>
              @else
                <span>{{ $v->credit_model ? __($cfg['credit_models'][$v->credit_model] ?? $v->credit_model) : '–' }}</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif

    {{-- 3. Learning Outcomes --}}
    <div class="ps-sec">
      <div class="ps-sec-hd">
        <h3>{{ __('Learning outcomes') }}</h3>
        <span class="ps-sec-meta">{{ $los->count() }} {{ __('total') }}</span>
      </div>
      @if($los->isNotEmpty())
        <div class="ps-table-wrap">
          <table class="ps-table" data-plain>
            <thead>
              <tr>
                <th style="width:76px">{{ __('Code') }}</th>
                <th>{{ __('Outcome statement') }}</th>
                @if($hasParentClo)<th style="width:120px">{{ __('Parent CLO') }}</th>@endif
              </tr>
            </thead>
            <tbody>
              @foreach($los as $idx => $lo)
                <tr>
                  <td><span class="tag info" style="font-family:var(--mono);font-weight:700">{{ $lo->code ?: ('LO' . ($idx + 1)) }}</span></td>
                  <td>
                    {{ ($isMs && !empty($lo->text_ms)) ? $lo->text_ms : $lo->text_en }}
                    @if(!$isMs && !empty($lo->text_ms) && $lo->text_ms !== $lo->text_en)
                      <div class="muted small" style="margin-top:2px">{{ $lo->text_ms }}</div>
                    @endif
                  </td>
                  @if($hasParentClo)
                    <td style="font-family:var(--mono);font-size:12px">{{ $lo->parent_clo_id ? ('CLO' . $lo->parent_clo_id) : '–' }}</td>
                  @endif
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="muted small">{{ __('No learning outcomes entered.') }}</div>
      @endif
    </div>

    {{-- 4. Modules & SLT --}}
    <div class="ps-sec">
      <div class="ps-sec-hd">
        <h3>{{ __('Modules and learning time (SLT)') }}</h3>
        <span class="ps-sec-meta">{{ $modules->count() }} {{ __('modules') }} &middot; {{ $sltSum }} h SLT</span>
      </div>
      @if($modules->isNotEmpty())
        <div class="ps-table-wrap">
          <table class="ps-table" data-plain>
            <thead>
              <tr>
                <th style="width:44px">#</th>
                <th style="width:28%">{{ __('Module') }}</th>
                <th>{{ __('Key content and learning activities') }}</th>
                @if($v->credit_model === 'separate_assessment')<th style="width:110px">{{ __('Type') }}</th>@endif
                <th class="num" style="width:80px">{{ __('SLT') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($modules as $m)
                @php $modActs = $activities[$m->id] ?? collect(); @endphp
                <tr>
                  <td style="font-family:var(--mono);color:var(--muted)">{{ $m->seq }}</td>
                  <td><strong>{{ ($isMs && !empty($m->name_ms)) ? $m->name_ms : $m->name_en }}</strong></td>
                  <td>
                    @if(!empty($m->key_content))
                      <div>{{ $m->key_content }}</div>
                    @endif
                    @if($modActs->isNotEmpty())
                      <div style="margin-top:{{ !empty($m->key_content) ? '5px' : '0' }};display:flex;flex-direction:column;gap:2px">
                        @foreach($modActs as $act)
                          <div class="small muted">&bull; {{ $act->name_en }} ({{ __($cfg['activity_types'][$act->activity_type] ?? ucfirst((string) $act->activity_type)) }}, {{ $act->slt_hours }} h)</div>
                        @endforeach
                      </div>
                    @elseif(empty($m->key_content))
                      <span class="muted">–</span>
                    @endif
                  </td>
                  @if($v->credit_model === 'separate_assessment')
                    <td>
                      <span class="tag {{ $m->is_assessment ? 'warn' : 'info' }}">
                        {{ $m->is_assessment ? __('Assessment') : __('Learning') }}
                      </span>
                    </td>
                  @endif
                  <td class="num">{{ $m->slt_hours }} h</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="{{ $v->credit_model === 'separate_assessment' ? 4 : 3 }}">{{ __('Total Student Learning Time (SLT)') }}</td>
                <td class="num">{{ $sltSum }} h</td>
              </tr>
            </tfoot>
          </table>
        </div>
      @else
        <div class="muted small">{{ __('No modules configured.') }}</div>
      @endif
    </div>

    {{-- 5. Assessment & Outcome Alignment --}}
    <div class="ps-sec">
      <div class="ps-sec-hd">
        <h3>{{ __('Assessment and completion standards') }}</h3>
        <span class="ps-sec-meta">{{ $comps->count() }} {{ __('components') }} &middot; {{ mcms_num($weightsSum, 1) }}%</span>
      </div>
      @if($comps->isNotEmpty())
        <div class="ps-table-wrap">
          <table class="ps-table" data-plain>
            <thead>
              <tr>
                <th>{{ __('Assessment component') }}</th>
                <th style="width:30%">{{ __('Aligned outcomes') }}</th>
                <th class="num" style="width:90px">{{ __('Weight') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($comps as $c)
                @php $compLinks = $links[$c->id] ?? collect(); @endphp
                <tr>
                  <td><strong>{{ $c->name_en }}</strong></td>
                  <td>
                    @if($compLinks->isNotEmpty())
                      <div style="display:flex;gap:4px;flex-wrap:wrap">
                        @foreach($compLinks as $lk)
                          <span class="tag" style="font-family:var(--mono);font-size:11px">{{ $loCodeById[$lk->learning_outcome_id] ?? ('LO' . $lk->learning_outcome_id) }}</span>
                        @endforeach
                      </div>
                    @else
                      <span class="muted">–</span>
                    @endif
                  </td>
                  <td class="num">{{ mcms_num($c->weight_percent, 1) }}%</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2">{{ __('Total assessment weight') }}</td>
                <td class="num">{{ mcms_num($weightsSum, 1) }}%</td>
              </tr>
            </tfoot>
          </table>
        </div>
      @else
        <div class="muted small">{{ __('No assessment components configured.') }}</div>
      @endif

      @if(!empty($v->assessment_standard) || !empty($v->integrity_mechanism) || !empty($v->feedback_method))
        <div class="ps-std-strip">
          <div class="ps-std-box">
            <div class="k">{{ __('Pass mark / standard') }}</div>
            <div>{{ $v->assessment_standard ?: '–' }}</div>
          </div>
          <div class="ps-std-box">
            <div class="k">{{ __('Assessment integrity') }}</div>
            <div>{{ $v->integrity_mechanism ?: '–' }}</div>
          </div>
          <div class="ps-std-box">
            <div class="k">{{ __('Feedback method') }}</div>
            <div>{{ $v->feedback_method ?: '–' }}</div>
          </div>
        </div>
      @endif
    </div>

    {{-- 6. Credential & Recognition --}}
    <div class="ps-sec">
      <div class="ps-sec-hd">
        <h3>{{ __('Credential and recognition') }}</h3>
        <span class="ps-sec-meta">{{ __('Proposals only until confirmed') }}</span>
      </div>
      <div class="ps-cred-grid">
        <div class="ps-cred-item">
          <div class="k">{{ __('Credential awarded') }}</div>
          <div>{{ implode(' · ', $credDisplay) }}</div>
        </div>
        <div class="ps-cred-item">
          <div class="k">{{ __('Achievement level') }}</div>
          <div>{{ ($v->achievement_level ?? 'overall') === 'module' ? __('Module / Learning Unit Level') : __('Overall Micro-Credential') }}</div>
        </div>
        <div class="ps-cred-item">
          <div class="k">{{ __('Proposed credit / MQF level') }}</div>
          <div>
            <strong>{{ $v->proposed_credit !== null ? mcms_num($v->proposed_credit) : '–' }} / {{ $v->proposed_mqf_level ?? '–' }}</strong>
            <div class="muted small" style="margin-top:2px">{{ __('proposals only until approval or Quality Verification confirms them') }}</div>
          </div>
        </div>
        <div class="ps-cred-item">
          <div class="k">{{ __('Quality Verification') }}</div>
          <div>
            @if($v->category === 'samc')
              <span class="tag {{ !empty($v->qv_planned) ? 'info' : '' }}">
                {{ !empty($v->qv_planned) ? __('Will apply for Quality Verification') : __('Not planned') }}
              </span>
            @else
              <span class="muted">{{ __('Not applicable') }}</span>
            @endif
          </div>
        </div>
        @if(!empty($v->recognition_potential))
          <div class="ps-cred-item" style="grid-column:1 / -1">
            <div class="k">{{ __('Recognition potential') }}</div>
            <div>{{ $v->recognition_potential }}</div>
          </div>
        @endif
      </div>
    </div>

    {{-- 7. Delivery Readiness --}}
    <div class="ps-sec">
      <div class="ps-sec-hd">
        <h3>{{ __('Delivery readiness') }}</h3>
        <span class="tag {{ $readyDone === $totalReady ? 'ok' : 'warn' }}" style="font-family:var(--mono)">
          {{ $readyDone }} / {{ $totalReady }} {{ __('ready') }}
        </span>
      </div>
      <div class="ps-ready-grid">
        @foreach($cfg['readiness'] as $k => $l)
          @php
            $r = $ready[$k] ?? null;
            $st = $r->status ?? 'not_yet';
            $stLabel = __(['yes' => 'Yes', 'not_yet' => 'Not yet', 'na' => 'Not applicable'][$st] ?? 'Not yet');
            $stClass = $st === 'yes' ? 'ok' : ($st === 'not_yet' ? 'warn' : '');
          @endphp
          <div class="ps-ready-item">
            <div style="min-width:0;flex:1">
              <div style="font-weight:600;color:var(--ink)">{{ __($l) }}</div>
              @if(!empty($r?->note))
                <div class="ps-ready-note">{{ $r->note }}</div>
              @endif
            </div>
            <span class="tag {{ $stClass }}" style="flex-shrink:0">{{ $stLabel }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
