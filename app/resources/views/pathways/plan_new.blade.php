@extends('layouts.app')
@section('title', 'New individual plan')
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('pathways') }}">Stackable pathways</a>
  <span>/</span>
  <span>{{ __('New individual plan') }}</span>
</div>

<div class="title" style="margin-bottom:16px">
  <div>
    <h1>{{ __('New individual stackable plan') }}</h1>
    <div class="sub">{{ __('A plan is advice. It is not admission, a credit transfer or an award. At least the required share of graduating credit must be earned directly at UTeM.') }}</div>
  </div>
</div>

<form method="post" action="{{ route('plans.store') }}" class="card panel">
  <div class="bd">
    @csrf
    <div class="form-grid cols-2">
      <div class="field">
        <label for="user_id">{{ __('Participant') }}</label>
        <select id="user_id" name="user_id">
          @foreach($students as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
        </select>
      </div>

      <div class="field">
        <label for="pathway_id">{{ __('Published pathway') }}</label>
        <select id="pathway_id" name="pathway_id">
          @foreach($pathways as $w)<option value="{{ $w->id }}">{{ $w->reference }} {{ $w->programme_name }}</option>@endforeach
        </select>
      </div>
    </div>

    <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface);margin-bottom:16px">
      <legend style="font-weight:600;padding:0 6px">{{ __('Credentials the plan counts on (choose from the pathway)') }}</legend>
      <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:8px">
        @foreach($pathways as $w)
          @foreach($w->items as $i)
            <label style="display:flex;align-items:center;gap:8px;background:#ffffff;padding:8px 12px;border:1px solid var(--hairline);cursor:pointer;font-size:13px;margin:0">
              <input type="checkbox" name="items[]" value="{{ $i->id }}">
              <span><b>{{ $w->reference }}:</b> {{ $i->name_en }} ({{ $i->course_code }} &middot; {{ rtrim(rtrim((string) $i->credits, '0'), '.') }} cr)</span>
            </label>
          @endforeach
        @endforeach
      </div>
    </fieldset>

    <div class="form-grid cols-3">
      <div class="field">
        <label for="other_credits">{{ __('Other credit from prior qualifications') }}</label>
        <input id="other_credits" name="other_credits" type="number" step="0.5" min="0" value="0">
      </div>
      <div class="field">
        <label for="residency_semesters">{{ __('Residency planned at UTeM (semesters)') }}</label>
        <input id="residency_semesters" name="residency_semesters" type="number" min="0" max="12" value="1" required>
      </div>
      <div class="field">
        <label for="delivery_method">{{ __('Delivery method') }}</label>
        <select id="delivery_method" name="delivery_method">
          <option value="conventional">{{ __('Conventional') }}</option>
          <option value="odl">{{ __('Open and distance learning') }}</option>
        </select>
      </div>
    </div>

    <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface);margin-bottom:20px">
      <legend style="font-weight:600;padding:0 6px">{{ __('Remaining programme requirements') }}</legend>
      <div style="display:flex;flex-direction:column;gap:10px">
        @for($n = 0; $n < 3; $n++)
          <div class="form-grid cols-3" style="margin-bottom:0">
            <select name="remaining[{{ $n }}][kind]" aria-label="Kind {{ $n + 1 }}">
              @foreach($kinds as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach
            </select>
            <input type="text" name="remaining[{{ $n }}][name]" placeholder="{{ __('Requirement :num name', ['num' => $n + 1]) }}" aria-label="Requirement {{ $n + 1 }}">
            <select name="remaining[{{ $n }}][status]" aria-label="Status {{ $n + 1 }}">
              @foreach($statuses as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach
            </select>
          </div>
        @endfor
      </div>
    </fieldset>

    <button type="submit" class="btn primary">{{ __('Create plan') }}</button>
  </div>
</form>
@endsection
