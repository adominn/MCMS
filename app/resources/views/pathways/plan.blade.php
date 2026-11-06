@extends('layouts.app')
@section('title', 'Individual stackable plan')
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('pathways') }}">Stackable pathways</a>
  <span>/</span>
  <span>{{ $s->reference }}</span>
</div>

<div class="title">
  <div>
    <h1>Plan {{ $s->reference }}</h1>
    <div class="sub">{{ $s->user_name }} &mdash; {{ $s->code }} {{ $s->programme_name }}</div>
  </div>
  <div class="act" style="align-items:center">
    <span class="tag {{ $s->status === 'suitable' ? 'ok' : ($s->status === 'not_yet' ? 'warn' : 'info') }}">
      <strong id="plan-status">{{ $planStatus[$s->status] }}</strong>
    </span>
    @if($s->decision)
      <span class="tag info">{{ $decisions[$s->decision] }}</span>
    @endif
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>
    <b>{{ __('Advisory note:') }}</b> A plan is advice. It is not admission, a credit transfer or an award. Meeting {{ $min }}% UTeM credit does not mean the rest can be transferred automatically. Each credit must still be evaluated course by course.
  </div>
</div>

<div class="card panel" id="figures" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <h2 style="margin:0;font-size:16px;font-weight:600">{{ __('Degree requirement figures') }}</h2>
    <span class="tag {{ $s->utem_percent >= $min ? 'ok' : 'warn' }}">{{ rtrim(rtrim((string) $s->utem_percent, '0'), '.') }}% {{ __('Direct UTeM Study') }}</span>
  </div>
  <div class="bd" style="padding:16px 20px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));gap:14px">
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Total Degree Credits') }}</div>
        <div style="font-size:15px;font-weight:600;color:var(--ink);margin-top:2px">{{ $s->total_credits }} {{ __('credits') }}</div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--primary, #1e2a6e);letter-spacing:0.5px">{{ __('Direct UTeM Share') }}</div>
        <div style="font-size:15px;font-weight:600;color:var(--primary, #1e2a6e);margin-top:2px">{{ rtrim(rtrim((string) $s->utem_percent, '0'), '.') }}% <span class="muted small">(&ge; {{ $min }}% required)</span></div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Articulated Outside') }}</div>
        <div style="font-size:15px;font-weight:600;color:var(--ink);margin-top:2px">{{ rtrim(rtrim((string) $s->transfer_credits, '0'), '.') }} {{ __('credits') }}</div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Planned Residency') }}</div>
        <div style="font-size:15px;font-weight:600;color:var(--ink);margin-top:2px">{{ $s->residency_semesters }} {{ __('semester(s)') }} <span class="muted small">({{ $s->delivery_method === 'odl' ? 'ODL' : 'Conventional' }})</span></div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Admission Status') }}</div>
        <div style="font-size:15px;font-weight:600;color:var(--ink);margin-top:2px">{{ $admitted ? __('Recorded (intake :intake)', ['intake' => $admitted->intake]) : __('Not recorded') }}</div>
      </div>
    </div>
  </div>
</div>

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Checks') }}</h2></div>
  <div class="bd">
    <ul id="checks" style="margin:0;padding-left:18px">
      @foreach($checks as $c)
        <li style="margin-bottom:6px">
          <b>{{ $c->met ? 'Met' : 'Not met' }}:</b> {{ $c->detail }}
        </li>
      @endforeach
    </ul>
  </div>
</div>

@if($remaining)
<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Remaining requirements') }}</h2></div>
  <div class="bd">
    <ul style="margin:0;padding-left:18px">
      @foreach($remaining as $r)
        <li style="margin-bottom:4px"><b>{{ $kinds[$r['kind']] }}:</b> {{ $r['name'] }} <span class="muted">({{ $statuses[$r['status']] }})</span></li>
      @endforeach
    </ul>
  </div>
</div>
@endif

@if($s->conditions)
  <div class="panel note warn" style="margin-bottom:24px">
    <div><b>{{ __('Conditions / Follow-up:') }}</b> {{ $s->conditions }}</div>
  </div>
@endif

<form method="post" action="{{ route('plans.update', $s->id) }}" class="card panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Update the plan') }}</h2></div>
  <div class="bd">
    @csrf
    <fieldset style="border:1px solid var(--border);padding:12px;border-radius:4px;margin-bottom:14px">
      <legend style="font-weight:600;padding:0 6px">{{ __('Credentials counted') }}</legend>
      @foreach($items as $i)
        <label style="display:inline-flex;gap:8px;align-items:center;margin-bottom:6px;cursor:pointer">
          <input type="checkbox" name="items[]" value="{{ $i->id }}" @checked(in_array($i->id, $chosen))>
          <span>{{ $i->name_en }} for <b>{{ $i->course_code }}</b> ({{ rtrim(rtrim((string) $i->credits, '0'), '.') }} credits)</span>
        </label><br>
      @endforeach
    </fieldset>

    <div class="field" style="margin-bottom:14px">
      <label for="other_credits">{{ __('Other credit from prior qualifications') }}</label>
      <input id="other_credits" name="other_credits" type="number" step="0.5" min="0" value="{{ $s->other_credits }}" style="max-width:180px">
    </div>

    <div class="field" style="margin-bottom:14px">
      <label for="residency_semesters">{{ __('Residency planned (semesters)') }}</label>
      <input id="residency_semesters" name="residency_semesters" type="number" min="0" max="12" value="{{ $s->residency_semesters }}" required style="max-width:180px">
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="delivery_method">{{ __('Delivery method') }}</label>
      <select id="delivery_method" name="delivery_method" style="max-width:260px">
        <option value="conventional" @selected($s->delivery_method === 'conventional')>{{ __('Conventional') }}</option>
        <option value="odl" @selected($s->delivery_method === 'odl')>{{ __('Open and distance learning') }}</option>
      </select>
    </div>

    @foreach($remaining as $n => $r)
      <input type="hidden" name="remaining[{{ $n }}][kind]" value="{{ $r['kind'] }}">
      <input type="hidden" name="remaining[{{ $n }}][name]" value="{{ $r['name'] }}">
      <input type="hidden" name="remaining[{{ $n }}][status]" value="{{ $r['status'] }}">
    @endforeach

    <button type="submit" class="btn">{{ __('Update plan') }}</button>
  </div>
</form>

@if(auth()->user()->hasAnyRole('deputy_dean'))
<form method="post" action="{{ route('plans.decision', $s->id) }}" class="card panel" id="decision" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Plan decision') }}</h2></div>
  <div class="bd">
    @csrf
    <div class="field" style="margin-bottom:14px">
      <label for="decision-sel">{{ __('Decision') }}</label>
      <select id="decision-sel" name="decision" style="max-width:320px">
        @foreach($decisions as $v => $t)
          <option value="{{ $v }}">{{ $t }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="margin-bottom:14px">
      <label for="conditions">{{ __('Conditions and follow-up') }}</label>
      <input id="conditions" name="conditions" type="text" placeholder="Required if conditions exist" style="max-width:540px">
    </div>

    <p class="muted small">{{ __('Suitable to proceed needs the programme admission to be recorded and every requirement met.') }}</p>
    <button type="submit" class="btn">{{ __('Record decision') }}</button>
  </div>
</form>

@if($s->status === 'suitable' && ! $award)
<form method="post" action="{{ route('plans.award', $s->id) }}" class="card panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Record an award') }}</h2></div>
  <div class="bd">
    @csrf
    <p class="muted small" style="margin-top:0">{{ __('MCMS records an award from the programme\'s records. It never gives one.') }}</p>
    <div class="field" style="margin-bottom:14px">
      <label for="evidence_ref">{{ __('Evidence reference') }}</label>
      <input id="evidence_ref" name="evidence_ref" type="text" placeholder="Degree award reference" required style="max-width:320px">
    </div>
    <div class="field" style="margin-bottom:14px">
      <label for="awarded_on">{{ __('Date') }}</label>
      <input id="awarded_on" name="awarded_on" type="date" required style="max-width:200px">
    </div>
    <button type="submit" class="btn ter sm">{{ __('Record award') }}</button>
  </div>
</form>
@endif

@if($award)
  <div class="note ok" style="margin-bottom:24px">
    <p id="award" style="margin:0">Award recorded on {{ \Carbon\Carbon::parse($award->awarded_on)->format('d/m/Y') }} from the programme records ({{ $award->evidence_ref }}).</p>
  </div>
@endif
@endif
@endsection