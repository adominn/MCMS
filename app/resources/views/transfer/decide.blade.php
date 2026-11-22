@extends('layouts.app')
@section('title', 'Transfer decision')
@section('content')
@php
  $fmtNum = fn ($v) => mcms_num($v);
  $decided = in_array($a->status, ['approved', 'partly', 'not_approved'], true);
  $docChecklist = json_decode((string) ($a->document_checklist ?? ''), true) ?: null;
  $checkedDocs = $docChecklist['items'] ?? [];
  $resultLabels = config('transfer.result_labels');
  $okResults = ['equivalent', 'adequate', 'matched', 'aligned', 'valid', 'current', 'met', 'pass', 'suitable', 'sufficient'];
  $warnResults = ['partly', 'needs_review'];
  $lifeStage = $decided ? 8 : 7;
  $authRole = (string) app(\App\Modules\Core\Settings::class)->get('approval.route.transfer');
  $canDecide = auth()->user()->hasAnyRole($authRole ?: 'deputy_dean', 'codl_admin', 'academic_authority', 'deputy_dean', 'ptj_authority');
  $isDd = false;

  [$whereTxt, $nextTxt, $whoTxt, $roleCode] = match ($a->status) {
    'submitted' => [
      __('Stage 07: Transfer application submitted — awaiting Step 1 record authenticity & validity check'),
      __('Verify ACR authenticity, ABC deposit status, and QV/accreditation validity below to advance to F7 equivalence evaluation.'),
      auth()->user()->name,
      'CODL',
    ],
    'under_eval' => [
      __('Stage 07: Under CODL equivalence evaluation and Senate decision review'),
      __('Complete outcome-by-outcome mapping and all 9 equivalence criteria for each course line, then record the official Senate decision.'),
      auth()->user()->name,
      'CODL',
    ],
    'more_evidence' => [
      __('Stage 07: Paused — additional supporting evidence requested from applicant'),
      __('Waiting for :name to submit the requested evidence; application returns to Submitted automatically once sent.', ['name' => $a->user_name]),
      $a->user_name,
      'P8',
    ],
    default => [
      __('Stage 08: Transfer decision recorded (:st)', ['st' => $status[$a->status] ?? ucfirst($a->status)]),
      $a->status === 'not_approved'
        ? __('Applicant\'s ACR and ABC credits remain unchanged and Available. Print the official decision letter if needed.')
        : __('Approved credits are now Provisionally Redeemed. Record programme admission and eventual graduation/release under Credit Held (F9).'),
      auth()->user()->name,
      'CODL',
    ],
  };
@endphp

<x-breadcrumb :crumbs="[
  'Credit transfer' => route('transfers'),
  $a->reference => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('Transfer Decision') }} {{ $a->reference }}</h1>
    <div class="sub">{{ $a->user_name }} (<code>{{ $a->institution_id }}</code>) &middot; {{ $a->programme }} {{ $a->programme_name }}</div>
  </div>
  <div class="act" style="align-items:center;gap:8px">
    <a class="btn ter sm" href="{{ route('transfers') }}">&larr; {{ __('All evaluations') }}</a>
    @if($decided)
      <a class="btn ter sm" href="{{ route('transfers.letter', $a->id) }}?print=1" target="_blank">{{ __('Decision Letter (A4)') }}</a>
    @endif
    <span class="tag {{ in_array($a->status, ['approved', 'partly'], true) ? 'ok' : ($a->status === 'not_approved' ? 'err' : 'warn') }}">
      <strong id="app-status">{{ $status[$a->status] ?? ucfirst($a->status) }}</strong>
    </span>
  </div>
</div>

@if($isDd)
  <div class="note info" style="margin-bottom:20px">
    <p style="margin:0">
      <b>{{ __('Faculty Programme Transfer Monitor (Read-Only):') }}</b>
      {{ __('This application is evaluated by CODL and approved by Senate. You are monitoring credit transfers into your faculty programmes.') }}
    </p>
  </div>
@endif

<x-credit-lifecycle :stage="$lifeStage" :blocked="$a->status === 'more_evidence'" :blockedLabel="__('Waiting for evidence')" :note="$a->reference" />

{{-- Supporting Documents Checklist --}}
@if($docChecklist || $a->documents)
  <div class="panel" style="margin-bottom:20px">
    <div class="hd">
      <h2>{{ __('Supporting Documents Submitted') }}</h2>
      <span class="r"><span class="tag info">{{ $docChecklist ? count($checkedDocs).' / '.count(config('transfer.documents_checklist')).' '.__('ticked') : __('Notes listed') }}</span></span>
    </div>
    <div class="bd">
      @if($docChecklist)
        <div class="checklist-grid" style="margin-bottom:10px">
          @foreach(config('transfer.documents_checklist') as $k => $lbl)
            @php $ticked = in_array($k, $checkedDocs, true); @endphp
            <div style="padding:6px 10px;border:1px solid var(--hairline);background:{{ $ticked ? 'var(--ok-bg)' : 'var(--surface)' }};font-size:12.5px">
              <b>[{{ $ticked ? 'X' : ' ' }}]</b> {{ __($lbl) }}
            </div>
          @endforeach
        </div>
        @if(!empty($docChecklist['other']))<div class="small"><b>{{ __('Other specified:') }}</b> {{ $docChecklist['other'] }}</div>@endif
        @if(!empty($docChecklist['notes']))<div class="small muted" style="margin-top:4px"><b>{{ __('Applicant notes:') }}</b> {{ $docChecklist['notes'] }}</div>@endif
      @else
        <p style="margin:0"><strong>{{ __('Documents listed:') }}</strong> {{ $a->documents }}</p>
      @endif
    </div>
  </div>
@endif

{{-- Step 1: Record Validity Check --}}
<div class="card panel" id="validity" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('1. Validity of the Record') }}</h2>
    <span class="r">
      @if($validity)
        <span class="tag ok">{{ __('Verified') }}</span>
      @else
        <span class="tag warn">{{ __('Pending check') }}</span>
      @endif
    </span>
  </div>
  <div class="bd">
    @if($validity)
      <ul style="margin:0;padding-left:18px">
        @foreach(config('transfer.validity') as $k => $label)
          <li style="margin-bottom:6px">
            <b>{{ __($label) }}:</b>
            <span class="tag {{ ($validity[$k]['result'] ?? '') === 'pass' ? 'ok' : 'err' }}">{{ ($validity[$k]['result'] ?? '') === 'pass' ? __('Passed') : __('Failed') }}</span>
            @if(!empty($validity[$k]['note'])) <span class="muted">({{ $validity[$k]['note'] }})</span>@endif
          </li>
        @endforeach
      </ul>
    @elseif($a->status === 'submitted')
      @if($canDecide)
      <form method="post" action="{{ route('transfers.validity', $a->id) }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:12px">
          @foreach(config('transfer.validity') as $k => $label)
            <fieldset style="border:1px solid var(--hairline);padding:10px 14px">
              <legend style="font-weight:600;padding:0 6px">{{ __($label) }}</legend>
              <div style="display:flex;gap:16px;align-items:center;margin-bottom:8px">
                <label style="display:inline-flex;gap:6px;align-items:center;cursor:pointer;color:var(--ink)">
                  <input type="radio" name="check[{{ $k }}]" value="pass" required> {{ __('Passed') }}
                </label>
                <label style="display:inline-flex;gap:6px;align-items:center;cursor:pointer;color:var(--ink)">
                  <input type="radio" name="check[{{ $k }}]" value="fail"> {{ __('Failed') }}
                </label>
              </div>
              <input type="text" name="note[{{ $k }}]" placeholder="{{ __('Optional note for :label', ['label' => __($label)]) }}" aria-label="Note for {{ $label }}" style="width:100%;max-width:440px;font-size:12px">
            </fieldset>
          @endforeach
        </div>
        <p class="muted small" style="margin:12px 0">{{ __('A failed check ends the application as Not approved. The credit and the records stay as they are.') }}</p>
        <button type="submit" class="btn">{{ __('Record validity check') }}</button>
      </form>
      @else
        <p class="muted" style="margin:0"><span class="tag warn">{{ __('Pending Step 1 validity check by CODL') }}</span></p>
      @endif
    @else
      <span class="not-recorded">{{ __('Not yet recorded') }}</span>
    @endif
  </div>
</div>

@if(count($lines) > 1)
<div class="row" style="align-items:center;justify-content:space-between;margin:20px 0 14px;background:var(--s1);padding:10px 16px;border:1px solid var(--hairline)">
  <label for="line-switcher" style="font-weight:600;display:inline-flex;align-items:center;gap:8px;margin:0;color:var(--ink)">
    <span>{{ __('Transfer line:') }}</span>
    <select id="line-switcher" class="dropdown-switcher" data-switch="transfer-line" style="min-width:320px">
      @foreach($lines as $l)
        <option value="{{ $l->id }}">{{ $l->course_code }} {{ $l->course_name }} ({{ $fmtNum($l->credits_requested) }} cr)</option>
      @endforeach
    </select>
  </label>
  <span class="small muted">{{ count($lines) }} {{ __('lines requested') }}</span>
</div>
@endif

{{-- Step 2: Course Equivalence Evaluation --}}
@foreach($lines as $l)
<div @if(count($lines) > 1) data-case="transfer-line" data-id="{{ $l->id }}" @endif class="card line section-card panel" id="line-{{ $l->id }}" style="margin-bottom:20px">
  <div class="hd">
    <h2>2. {{ $l->course_code }} {{ $l->course_name }}</h2>
    <div class="r" style="display:flex;gap:8px;align-items:center">
      @if($l->reevaluate)
        <span class="tag overdue">{{ __('Needs re-evaluation') }}</span>
      @endif
      <span class="tag info">{{ $fmtNum($l->credits_requested) }} / {{ $fmtNum($l->course_credit) }} {{ __('credits') }}</span>
    </div>
  </div>
  <div class="bd">
    <p style="margin-top:0">
      {{ __('Credit used:') }} <b>{{ $l->source_name }}</b> (<code>{{ $l->acr_reference }}</code>@if($l->acr_slt_hours), {{ $l->acr_slt_hours }} {{ __('SLT hrs') }}@endif @if($l->acr_mqf_level), MQF {{ $l->acr_mqf_level }}@endif),
      {{ __('requested :req of the course\'s :total credit. Curriculum version :v.', ['req' => $fmtNum($l->credits_requested), 'total' => $fmtNum($l->course_credit), 'v' => $l->target_version]) }}
    </p>

    @if($a->status === 'under_eval' && $canDecide)
    <form method="post" action="{{ route('transfers.evaluate', [$a->id, $l->id]) }}">
      @csrf
      <h3 style="margin:14px 0 8px">{{ __('Learning outcomes, one by one') }}</h3>
      <div class="table-wrap">
        <table style="margin-bottom:16px; min-width:650px">
          <thead>
            <tr>
              <th style="min-width:170px">{{ __('Outcome of the course') }}</th>
              <th style="min-width:240px">{{ __('Micro-credential outcome that meets it') }}</th>
              <th style="width:170px">{{ __('Match') }}</th>
              <th style="min-width:170px">{{ __('Note') }}</th>
            </tr>
          </thead>
          <tbody>
          @foreach($l->targets as $i => $t)
            @php $as = $l->assessments[$t] ?? null; @endphp
            <tr>
              <td><b>{{ $t }}</b></td>
              <td>
                <select name="source[{{ $i }}]" aria-label="Outcome meeting: {{ $t }}" style="width:100%;min-width:220px;max-width:320px">
                  <option value="">{{ __('None') }}</option>
                  @foreach($l->outcomes as $o)
                    <option value="{{ $o->id }}" @selected(($as->source_outcome_id ?? null) == $o->id)>{{ $o->code }} {{ $o->text_en }}</option>
                  @endforeach
                </select>
              </td>
              <td>
                <select name="match[{{ $i }}]" aria-label="Match for: {{ $t }}" style="width:100%;min-width:150px">
                  <option value="">{{ __('Choose') }}</option>
                  @foreach(config('transfer.match') as $v => $tt)
                    <option value="{{ $v }}" @selected(($as->match ?? null) === $v)>{{ __($tt) }}</option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text" name="note[{{ $i }}]" value="{{ $as->note ?? '' }}" aria-label="Note for: {{ $t }}" placeholder="{{ __('Evaluation note') }}" style="width:100%">
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <h3 style="margin:14px 0 8px">{{ __('Course Equivalence Criteria') }}</h3>
      <div class="table-wrap">
        <table style="margin-bottom:16px; min-width:550px">
          <thead>
            <tr>
              <th style="min-width:200px">{{ __('Criterion') }}</th>
              <th style="min-width:230px">{{ __('Finding & Evidence') }}</th>
              <th style="width:200px">{{ __('Result') }}</th>
            </tr>
          </thead>
          <tbody>
          @foreach(config('transfer.criteria') as $k => $label)
            @continue($k === 'outcomes')
            @php $cr = $l->criteria[$k] ?? null; $opts = config("transfer.criteria_options.$k", config('transfer.results')); @endphp
            <tr>
              <td>{{ __($label) }}</td>
              <td>
                <input type="text" name="finding[{{ $k }}]" value="{{ $cr->finding ?? '' }}" aria-label="Finding for {{ $label }}" placeholder="{{ __('Evidence checked') }}" style="width:100%;max-width:320px">
              </td>
              <td>
                <select name="result[{{ $k }}]" aria-label="Result for {{ $label }}" style="width:100%;min-width:180px">
                  <option value="">{{ __('Choose') }}</option>
                  @foreach($opts as $v => $tt)
                    <option value="{{ $v }}" @selected(($cr->result ?? null) === $v)>{{ __($tt) }}</option>
                  @endforeach
                  @if($cr && $cr->result && !array_key_exists($cr->result, $opts))
                    <option value="{{ $cr->result }}" selected>{{ $resultLabels[$cr->result] ?? ucfirst($cr->result) }}</option>
                  @endif
                </select>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <button type="submit" class="btn ter sm">{{ __('Save evaluation') }}</button>
    </form>
    @else
      <div style="margin-top:12px">
        <h3 style="margin:14px 0 8px">{{ __('Learning Outcomes Comparison') }}</h3>
        <div class="table-wrap">
          <table style="margin-bottom:16px; min-width:650px">
            <thead>
              <tr>
                <th style="min-width:180px">{{ __('Outcome of the course') }}</th>
                <th style="min-width:240px">{{ __('Micro-credential outcome') }}</th>
                <th style="width:150px">{{ __('Match') }}</th>
                <th style="min-width:180px">{{ __('Note') }}</th>
              </tr>
            </thead>
            <tbody>
            @foreach($l->targets as $t)
              @php $as = $l->assessments[$t] ?? null; $matchedOutcome = $as && $as->source_outcome_id ? $l->outcomes->firstWhere('id', $as->source_outcome_id) : null; @endphp
              <tr>
                <td><b>{{ $t }}</b></td>
                <td>
                  @if($matchedOutcome)
                    {{ $matchedOutcome->code }} {{ $matchedOutcome->text_en }}
                  @elseif($as && $as->match === 'none')
                    <span class="muted">{{ __('None matched') }}</span>
                  @else
                    <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                  @endif
                </td>
                <td>
                  @if(!empty($as->match))
                    <span class="tag {{ in_array($as->match, ['full', 'direct'], true) ? 'ok' : ($as->match === 'partial' ? 'warn' : 'err') }}">
                      {{ __(config('transfer.match')[$as->match] ?? ucfirst($as->match)) }}
                    </span>
                  @else
                    <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                  @endif
                </td>
                <td>@if(!empty($as->note)){{ $as->note }}@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>

        <h3 style="margin:14px 0 8px">{{ __('Course Equivalence Criteria Findings') }}</h3>
        <div class="table-wrap">
          <table style="margin-bottom:16px; min-width:550px">
            <thead>
              <tr>
                <th style="min-width:200px">{{ __('Criterion') }}</th>
                <th style="min-width:230px">{{ __('Finding & Evidence Checked') }}</th>
                <th style="width:170px">{{ __('Result') }}</th>
              </tr>
            </thead>
            <tbody>
            @foreach(config('transfer.criteria') as $k => $label)
              @continue($k === 'outcomes')
              @php $cr = $l->criteria[$k] ?? null; @endphp
              <tr>
                <td><b>{{ __($label) }}</b></td>
                <td>@if($cr && $cr->finding !== ''){{ $cr->finding }}@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
                <td>
                  @if(!empty($cr->result))
                    <span class="tag {{ in_array($cr->result, $okResults, true) ? 'ok' : (in_array($cr->result, $warnResults, true) ? 'warn' : ($cr->result === 'na' ? '' : 'err')) }}">
                      {{ __($resultLabels[$cr->result] ?? ucfirst($cr->result)) }}
                    </span>
                  @else
                    <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                  @endif
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    @if($l->lo)
      <div style="margin-top:12px;padding:10px 14px;background:var(--surface);border-left:3px solid {{ $l->lo === 'equivalent' ? 'var(--success)' : ($l->lo === 'partly' ? 'var(--warn)' : 'var(--error)') }}">
        <p class="line-lo" style="margin:0">{{ __('Learning-outcome criterion:') }} <strong>{{ __(config('transfer.results')[$l->lo] ?? $l->lo) }}</strong>@if($l->lo === 'not_equivalent') ({{ __('Approved is blocked for this course') }})@endif</p>
      </div>
    @endif
  </div>
</div>
@endforeach

@if($warnings)
<div class="card panel" id="warnings" style="margin-bottom:20px;border-left:4px solid var(--warn)">
  <div class="hd"><h2>{{ __('Governance & Credit-Limit Warnings') }}</h2></div>
  <div class="bd">
    <ul style="margin:0;padding-left:18px">
      @foreach($warnings as $w)
        <li style="margin-bottom:4px;color:var(--ink)">{{ $w }}</li>
      @endforeach
    </ul>
  </div>
</div>
@endif

@if($a->status === 'under_eval')
  @if($canDecide)
  <form method="post" action="{{ route('transfers.decide', $a->id) }}" class="card panel" id="decide" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __('3. Record Credit Transfer Decision') }}</h2></div>
    <div class="bd">
      @csrf
      <div class="table-wrap">
        <table style="margin-bottom:16px; min-width:480px">
          <thead>
            <tr>
              <th>{{ __('Course') }}</th>
              <th style="width:240px">{{ __('Credits approved (0 to requested)') }}</th>
            </tr>
          </thead>
          <tbody>
          @foreach($lines as $l)
            <tr>
              <td><b>{{ $l->course_code }}</b> {{ $l->course_name }}</td>
              <td>
                <input type="number" step="0.5" min="0" max="{{ $l->credits_requested }}" name="credits[{{ $l->id }}]" value="{{ $l->credits_requested }}" aria-label="Credits approved for {{ $l->course_code }}" style="width:120px">
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:14px">
        <div>
          <label for="outcome">{{ __('Decision') }} *</label>
          <select id="outcome" name="outcome">
            <option value="approved">{{ __('Approved') }}</option>
            <option value="partly">{{ __('Partly approved') }}</option>
            <option value="not_approved">{{ __('Not approved') }}</option>
          </select>
        </div>
        <div>
          <label for="decision_ref">{{ __('Decision reference') }}</label>
          <input id="decision_ref" name="decision_ref" type="text" placeholder="e.g. FTMK 4/2026" value="{{ old('decision_ref') }}">
        </div>
        <div>
          <label for="decided_on">{{ __('Decision date') }} *</label>
          <input id="decided_on" name="decided_on" type="date" value="{{ old('decided_on', today()->toDateString()) }}" required>
        </div>
      </div>

      <div style="margin-bottom:16px">
        <label for="conditions">{{ __('Conditions or justification') }} <span class="muted">({{ __('Required if Partly approved or Not approved') }})</span></label>
        <textarea id="conditions" name="conditions" rows="2" placeholder="{{ __('Required if not approved or partly approved') }}">{{ old('conditions') }}</textarea>
      </div>

      <p class="muted small">{{ __('Approving holds the credit used as Provisionally redeemed. It does not admit the participant.') }}</p>
      <button type="submit" class="btn">{{ __('Record decision') }}</button>
    </div>
  </form>

  <form method="post" action="{{ route('transfers.more', $a->id) }}" class="card panel" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __('Ask for more evidence') }}</h2></div>
    <div class="bd">
      @csrf
      <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:260px">
          <label for="request">{{ __('Or ask for more evidence') }}</label>
          <input id="request" name="request" type="text" placeholder="{{ __('Detail what additional evidence is required from applicant') }}" required>
        </div>
        <button type="submit" class="btn ter sm">{{ __('Request more evidence') }}</button>
      </div>
    </div>
  </form>
  @else
  <div class="card panel" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __('3. Record Credit Transfer Decision') }}</h2></div>
    <div class="bd">
      <p class="muted" style="margin:0"><span class="tag warn">{{ __('Pending final Senate decision recorded by CODL Administrator') }}</span></p>
    </div>
  </div>
  @endif
@elseif($a->status === 'submitted' && $canDecide)
<form method="post" action="{{ route('transfers.more', $a->id) }}" class="card panel" style="margin-bottom:20px">
  <div class="hd"><h2>{{ __('Ask for more evidence') }}</h2></div>
  <div class="bd">
    @csrf
    <div style="margin-bottom:14px">
      <label for="request">{{ __('Ask for more evidence') }}</label>
      <input id="request" name="request" type="text" placeholder="{{ __('Detail what additional evidence is required from applicant') }}" required style="max-width:480px">
    </div>
    <button type="submit" class="btn ter sm">{{ __('Request more evidence') }}</button>
  </div>
</form>
@endif

@if($decision && $decision->outcome !== 'more_evidence')
<div class="card panel" id="decision" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('Decision Recorded') }}</h2>
    <span class="r"><span class="tag {{ $decision->outcome === 'approved' ? 'ok' : ($decision->outcome === 'partly' ? 'warn' : 'err') }}">{{ __($status[$decision->outcome] ?? $decision->outcome) }}</span></span>
  </div>
  <div class="bd">
    <p style="margin-top:0">
      <b>{{ __($status[$decision->outcome] ?? $decision->outcome) }}</b>
      {{ __('on :date', ['date' => \Carbon\Carbon::parse($decision->decided_on)->format('d/m/Y')]) }}
      {{ __('by') }} <b>{{ $decision->decided_by_name ?: ($decision->actor_name ?: __('Not yet recorded')) }}</b>
      @if($decision->decided_by_position || $decision->actor_position)({{ $decision->decided_by_position ?: $decision->actor_position }})@endif
      @if($decision->decision_ref), {{ __('reference') }} <code>{{ $decision->decision_ref }}</code>@endif.
    </p>
    @if($decision->conditions)<p class="muted" style="margin-bottom:8px"><b>{{ __('Conditions / Justification:') }}</b> {{ $decision->conditions }}</p>@endif
    <a class="btn ter sm" href="{{ route('transfers.letter', $a->id) }}?print=1" target="_blank">{{ __('Print Official Decision Letter (A4)') }}</a>
  </div>
</div>
@endif

<form method="post" action="{{ route('transfers.admit', $a->id) }}" class="card panel" id="admission">
  <div class="hd"><h2>{{ __('Programme Admission (Separate Fact)') }}</h2></div>
  <div class="bd">
    @csrf
    <p class="muted small" style="margin-top:0">{{ __('Admission is a separate fact from the transfer decision. Neither implies the other.') }}</p>
    @if($admitted)
      <div class="note ok"><p style="margin:0">{{ __('Admission recorded: intake :intake (evidence :ref).', ['intake' => $admitted->intake, 'ref' => $admitted->evidence_ref]) }}</p></div>
    @else
      <div style="display:grid;grid-template-columns:220px 1fr;gap:12px;margin-bottom:14px;max-width:600px">
        <div>
          <label for="intake">{{ __('Intake') }}</label>
          <input id="intake" name="intake" type="text" placeholder="e.g. 2026/1" required>
        </div>
        <div>
          <label for="evidence_ref">{{ __('Evidence reference') }}</label>
          <input id="evidence_ref" name="evidence_ref" type="text" placeholder="{{ __('Pejabat Pendaftar admission ref') }}" required>
        </div>
      </div>
      <button type="submit" class="btn ter sm">{{ __('Record admission') }}</button>
    @endif
  </div>
</form>
@endsection
