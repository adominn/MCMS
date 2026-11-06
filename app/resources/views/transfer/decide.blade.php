@extends('layouts.app')
@section('title', 'Transfer decision')
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('transfers') }}">Credit transfer evaluation</a>
  <span>/</span>
  <span>{{ $a->reference }}</span>
</div>

<div class="title">
  <div>
    <h1>Transfer decision {{ $a->reference }}</h1>
    <div class="sub">{{ $a->user_name }} ({{ $a->institution_id }}) &middot; {{ $a->programme }} {{ $a->programme_name }}</div>
  </div>
  <div class="act" style="align-items:center">
    <span class="tag {{ in_array($a->status, ['approved', 'partly']) ? 'ok' : ($a->status === 'not_approved' ? 'err' : 'warn') }}">
      <strong id="app-status">{{ $status[$a->status] }}</strong>
    </span>
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>
    <b>{{ __('Outcome comparison rule:') }}</b> {{ __('Acceptance of credit is decided by the receiving programme. Matching credit value or learning time is never enough: every learning outcome of the course is compared. Approving holds credit as Provisionally redeemed and never admits the participant.') }}
  </div>
</div>

@if($a->documents)
  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <p style="margin:0"><strong>{{ __('Documents listed:') }}</strong> {{ $a->documents }}</p>
    </div>
  </div>
@endif

<div class="card panel" id="validity" style="margin-bottom:24px">
  <div class="hd">
    <h2>{{ __('1. Validity of the record') }}</h2>
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
            <b>{{ __($label) }}:</b> <span class="tag {{ $validity[$k]['result'] === 'pass' ? 'ok' : 'err' }}">{{ $validity[$k]['result'] === 'pass' ? __('Passed') : __('Failed') }}</span>
            @if($validity[$k]['note']) <span class="muted">({{ $validity[$k]['note'] }})</span>@endif
          </li>
        @endforeach
      </ul>
    @elseif($a->status === 'submitted')
      <form method="post" action="{{ route('transfers.validity', $a->id) }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:12px">
          @foreach(config('transfer.validity') as $k => $label)
            <fieldset style="border:1px solid var(--border);padding:10px 14px;border-radius:4px">
              <legend style="font-weight:600;padding:0 6px">{{ __($label) }}</legend>
              <div style="display:flex;gap:16px;align-items:center;margin-bottom:8px">
                <label style="display:inline-flex;gap:6px;align-items:center;cursor:pointer">
                  <input type="radio" name="check[{{ $k }}]" value="pass" required> {{ __('Passed') }}
                </label>
                <label style="display:inline-flex;gap:6px;align-items:center;cursor:pointer">
                  <input type="radio" name="check[{{ $k }}]" value="fail"> {{ __('Failed') }}
                </label>
              </div>
              <input type="text" name="note[{{ $k }}]" placeholder="{{ __('Optional note for :label', ['label' => __($label)]) }}" aria-label="Note for {{ $label }}" style="width:100%;max-width:400px;font-size:12px">
            </fieldset>
          @endforeach
        </div>
        <p class="muted small" style="margin:12px 0">{{ __('A failed check ends the application as Not approved. The credit and the records stay as they are.') }}</p>
        <button type="submit" class="btn">{{ __('Record validity check') }}</button>
      </form>
    @endif
  </div>
</div>

@if(count($lines) > 1)
<div class="row" style="align-items:center;justify-content:space-between;margin:24px 0 16px;background:var(--s1);padding:10px 16px;border:1px solid var(--hairline)">
  <label for="line-switcher" style="font-weight:600;display:inline-flex;align-items:center;gap:8px">
    <span>Transfer line:</span>
    <select id="line-switcher" class="dropdown-switcher" data-switch="transfer-line" style="min-width:320px">
      @foreach($lines as $idx => $l)
        <option value="{{ $l->id }}">{{ $l->course_code }} {{ $l->course_name }} ({{ rtrim(rtrim((string) $l->credits_requested, '0'), '.') }} cr)</option>
      @endforeach
    </select>
  </label>
  <span class="small muted">{{ count($lines) }} lines requested</span>
</div>
@endif

@foreach($lines as $l)
<div @if(count($lines) > 1) data-case="transfer-line" data-id="{{ $l->id }}" @endif class="card line section-card panel" id="line-{{ $l->id }}" style="margin-bottom:24px">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
    <h2>2. {{ $l->course_code }} {{ $l->course_name }}</h2>
    <div style="display:flex;gap:8px;align-items:center">
      @if($l->reevaluate)
        <span class="tag overdue">Needs re-evaluation</span>
      @endif
      <span class="tag info">{{ rtrim(rtrim((string) $l->credits_requested, '0'), '.') }} / {{ $l->course_credit }} credits</span>
    </div>
  </div>
  <div class="bd">
    <p style="margin-top:0">{{ __('Credit used: :name (:ref), requested :req of the course\'s :total credit. Curriculum version :v.', ['name' => $l->source_name, 'ref' => $l->acr_reference, 'req' => rtrim(rtrim((string) $l->credits_requested, '0'), '.'), 'total' => $l->course_credit, 'v' => $l->target_version]) }}</p>

    @if($a->status === 'under_eval')
    <form method="post" action="{{ route('transfers.evaluate', [$a->id, $l->id]) }}">
      @csrf
      <h3 style="margin:16px 0 8px;font-size:14px">{{ __('Learning outcomes, one by one') }}</h3>
      <table style="margin-bottom:16px">
        <thead>
          <tr>
            <th>{{ __('Outcome of the course') }}</th>
            <th>{{ __('Micro-credential outcome that meets it') }}</th>
            <th>{{ __('Match') }}</th>
            <th>{{ __('Note') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach($l->targets as $i => $t)
          @php($as = $l->assessments[$t] ?? null)
          <tr>
            <td><b>{{ $t }}</b></td>
            <td>
              <select name="source[{{ $i }}]" aria-label="Outcome meeting: {{ $t }}" style="width:100%;max-width:260px">
                <option value="">{{ __('None') }}</option>
                @foreach($l->outcomes as $o)
                  <option value="{{ $o->id }}" @selected(($as->source_outcome_id ?? null) == $o->id)>{{ $o->code }} {{ $o->text_en }}</option>
                @endforeach
              </select>
            </td>
            <td>
              <select name="match[{{ $i }}]" aria-label="Match for: {{ $t }}">
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

      <h3 style="margin:16px 0 8px;font-size:14px">{{ __('Other criteria') }}</h3>
      <table style="margin-bottom:16px">
        <thead>
          <tr>
            <th>{{ __('Criterion') }}</th>
            <th>{{ __('Finding') }}</th>
            <th>{{ __('Result') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach(config('transfer.criteria') as $k => $label)
          @continue($k === 'outcomes')
          @php($cr = $l->criteria[$k] ?? null)
          <tr>
            <td>{{ __($label) }}</td>
            <td>
              <input type="text" name="finding[{{ $k }}]" value="{{ $cr->finding ?? '' }}" aria-label="Finding for {{ $label }}" placeholder="{{ __('Evidence checked') }}" style="width:100%;max-width:320px">
            </td>
            <td>
              <select name="result[{{ $k }}]" aria-label="Result for {{ $label }}">
                <option value="">{{ __('Choose') }}</option>
                @foreach(config('transfer.results') as $v => $tt)
                  <option value="{{ $v }}" @selected(($cr->result ?? null) === $v)>{{ __($tt) }}</option>
                @endforeach
              </select>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <button type="submit" class="btn ter sm">{{ __('Save evaluation') }}</button>
    </form>
    @else
      <p>{{ __('Learning outcomes: :status.', ['status' => $l->lo ? __(config('transfer.results')[$l->lo]) : __('not evaluated')]) }}</p>
    @endif

    @if($l->lo)
      <div style="margin-top:14px;padding:8px 12px;background:var(--bg);border-left:3px solid {{ $l->lo === 'equivalent' ? 'var(--green)' : 'var(--red)' }}">
        <p class="line-lo" style="margin:0">{{ __('Learning-outcome criterion:') }} <strong>{{ __(config('transfer.results')[$l->lo]) }}</strong>@if($l->lo === 'not_equivalent') ({{ __('Approved is blocked for this course') }})@endif</p>
      </div>
    @endif
  </div>
</div>
@endforeach

@if($warnings)
<div class="card panel" id="warnings" style="margin-bottom:24px;border-left:4px solid var(--gold)">
  <div class="hd"><h2>{{ __('Warnings') }}</h2></div>
  <div class="bd">
    <ul style="margin:0;padding-left:18px">
      @foreach($warnings as $w)
        <li style="margin-bottom:4px;color:var(--text)">{{ $w }}</li>
      @endforeach
    </ul>
  </div>
</div>
@endif

@if($a->status === 'under_eval')
<form method="post" action="{{ route('transfers.decide', $a->id) }}" class="card panel" id="decide" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('3. Decision') }}</h2></div>
  <div class="bd">
    @csrf
    <table style="margin-bottom:16px">
      <thead>
        <tr>
          <th>{{ __('Course') }}</th>
          <th>{{ __('Credits approved (0 to requested)') }}</th>
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

    <div class="form-grid cols-3">
      <div class="field">
        <label for="outcome">{{ __('Decision') }}</label>
        <select id="outcome" name="outcome">
          <option value="approved">{{ __('Approved') }}</option>
          <option value="partly">{{ __('Partly approved') }}</option>
          <option value="not_approved">{{ __('Not approved') }}</option>
        </select>
      </div>

      <div class="field">
        <label for="decision_ref">{{ __('Decision reference') }}</label>
        <input id="decision_ref" name="decision_ref" type="text" placeholder="e.g. FTMK 4/2026">
      </div>

      <div class="field">
        <label for="decided_on">{{ __('Decision date') }}</label>
        <input id="decided_on" name="decided_on" type="date" value="{{ today()->toDateString() }}" required>
      </div>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="conditions">{{ __('Conditions or justification') }}</label>
      <textarea id="conditions" name="conditions" rows="2" placeholder="{{ __('Required if not approved or partly approved') }}"></textarea>
    </div>

    <p class="muted small">{{ __('Approving holds the credit used as Provisionally redeemed. It does not admit the participant.') }}</p>
    <button type="submit" class="btn">{{ __('Record decision') }}</button>
  </div>
</form>

<form method="post" action="{{ route('transfers.more', $a->id) }}" class="card panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Ask for more evidence') }}</h2></div>
  <div class="bd">
    @csrf
    <div class="form-grid cols-3-1" style="align-items:end;margin-bottom:0">
      <div class="field">
        <label for="request">{{ __('Or ask for more evidence') }}</label>
        <input id="request" name="request" type="text" placeholder="{{ __('Detail what additional evidence is required from applicant') }}" required>
      </div>
      <div>
        <button type="submit" class="btn ter sm" style="width:100%;justify-content:center">{{ __('Request more evidence') }}</button>
      </div>
    </div>
  </div>
</form>
@elseif($a->status === 'submitted')
<form method="post" action="{{ route('transfers.more', $a->id) }}" class="card panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Ask for more evidence') }}</h2></div>
  <div class="bd">
    @csrf
    <div class="field" style="margin-bottom:14px">
      <label for="request">{{ __('Ask for more evidence') }}</label>
      <input id="request" name="request" type="text" placeholder="{{ __('Detail what additional evidence is required from applicant') }}" required style="max-width:480px">
    </div>
    <button type="submit" class="btn ter sm">{{ __('Request more evidence') }}</button>
  </div>
</form>
@endif

@if($decision && $decision->outcome !== 'more_evidence')
<div class="card panel" id="decision" style="margin-bottom:24px">
  <div class="hd">
    <h2>{{ __('Decision recorded') }}</h2>
    <span class="r"><span class="tag {{ $decision->outcome === 'approved' ? 'ok' : 'info' }}">{{ __($status[$decision->outcome] ?? $decision->outcome) }}</span></span>
  </div>
  <div class="bd">
    <p style="margin-top:0">{{ __($status[$decision->outcome] ?? $decision->outcome) }} {{ __('on :date by the :auth', ['date' => \Carbon\Carbon::parse($decision->decided_on)->format('d/m/Y'), 'auth' => str_replace('_', ' ', $decision->authority)]) }}@if($decision->decision_ref), {{ __('reference :ref', ['ref' => $decision->decision_ref]) }}@endif.</p>
    @if($decision->conditions)<p class="muted" style="margin-bottom:0"><b>{{ __('Conditions / Notes:') }}</b> {{ $decision->conditions }}</p>@endif
  </div>
</div>
@endif

<form method="post" action="{{ route('transfers.admit', $a->id) }}" class="card panel" id="admission">
  <div class="hd"><h2>{{ __('Programme admission') }}</h2></div>
  <div class="bd">
    @csrf
    <p class="muted small" style="margin-top:0">{{ __('Admission is a separate fact from the transfer decision. Neither implies the other.') }}</p>
    @if($admitted)
      <div class="note ok"><p style="margin:0">{{ __('Admission recorded: intake :intake (evidence :ref).', ['intake' => $admitted->intake, 'ref' => $admitted->evidence_ref]) }}</p></div>
    @else
      <div class="field" style="margin-bottom:14px">
        <label for="intake">{{ __('Intake') }}</label>
        <input id="intake" name="intake" type="text" placeholder="e.g. 2026/1" required style="max-width:240px">
      </div>
      <div class="field" style="margin-bottom:16px">
        <label for="evidence_ref">{{ __('Evidence reference') }}</label>
        <input id="evidence_ref" name="evidence_ref" type="text" placeholder="{{ __('Pejabat Pendaftar admission ref') }}" required style="max-width:320px">
      </div>
      <button type="submit" class="btn ter sm">{{ __('Record admission') }}</button>
    @endif
  </div>
</form>
@endsection