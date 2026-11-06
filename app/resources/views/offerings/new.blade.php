@extends('layouts.app')
@section('title', 'New offering')
@section('content')
<div class="crumb">
  <a href="{{ route('offerings') }}">{{ __('Offerings and readiness') }}</a>
  <span>/</span>
  <span>{{ __('New offering') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('Create an offering') }}</h1>
    <div class="sub">{{ __('Schedule a cohort of an approved micro-credential version.') }}</div>
  </div>
</div>

@if($versions->isEmpty())
  <div class="note warn" role="alert">
    <b>{{ __('No approved version:') }}</b> {{ __('You have no approved version to offer yet. A version can be offered only after the Senate approves it.') }}
  </div>
@else
<div class="cols">
  <div>
    <form method="post" action="{{ route('offerings.store') }}">
      @csrf
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Cohort parameters') }}</h2></div>
        <div class="bd">
          <div class="field" style="margin-bottom:16px">
            <label for="mc_version_id">{{ __('Approved version') }}</label>
            <select id="mc_version_id" name="mc_version_id">
              @foreach($versions as $v)
                <option value="{{ $v->id }}" @selected(old('mc_version_id') == $v->id)>
                  {{ $v->name_en }} ({{ __('version :num', ['num' => $v->version_no]) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="cohort_name">{{ __('Cohort name') }}</label>
            <input type="text" id="cohort_name" name="cohort_name" value="{{ old('cohort_name') }}" required placeholder="e.g. 2027/1">
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="start_date">{{ __('Start date') }}</label>
              <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
            </div>
            <div class="field" style="flex:1">
              <label for="end_date">{{ __('End date') }}</label>
              <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
            </div>
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="delivery_mode">{{ __('Delivery mode') }}</label>
              <select id="delivery_mode" name="delivery_mode">
                @foreach($modes as $k => $l)
                  <option value="{{ $k }}" @selected(old('delivery_mode') === $k)>{{ __($l) }}</option>
                @endforeach
              </select>
            </div>
            <div class="field" style="max-width:140px">
              <label for="capacity">{{ __('Places') }}</label>
              <input type="text" inputmode="numeric" id="capacity" name="capacity" value="{{ old('capacity') }}" required placeholder="40">
            </div>
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="reg_opens_on">{{ __('Registration opens') }}</label>
              <input type="date" id="reg_opens_on" name="reg_opens_on" value="{{ old('reg_opens_on') }}" required>
            </div>
            <div class="field" style="flex:1">
              <label for="reg_closes_on">{{ __('Registration closes') }}</label>
              <input type="date" id="reg_closes_on" name="reg_closes_on" value="{{ old('reg_closes_on') }}" required>
            </div>
          </div>

          <div class="field" style="margin-bottom:16px;max-width:200px">
            <label for="fee">{{ __('Fee (RM, 0 for free)') }}</label>
            <input type="text" inputmode="decimal" id="fee" name="fee" value="{{ old('fee', '0') }}" required>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="facilitators">{{ __('Facilitators') }}</label>
            <input type="text" id="facilitators" name="facilitators" value="{{ old('facilitators') }}" placeholder="{{ __('Names of lead lecturers / facilitators') }}">
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="eligible_groups">{{ __('Eligible participant groups') }}</label>
            <input type="text" id="eligible_groups" name="eligible_groups" value="{{ old('eligible_groups') }}" placeholder="{{ __('e.g. Open to public, IT working professionals') }}">
          </div>

          <div class="field">
            <label for="platform_ref">{{ __('Learning platform course reference') }}</label>
            <input type="text" id="platform_ref" name="platform_ref" value="{{ old('platform_ref') }}" list="ucreds-courses" autocomplete="off" placeholder="e.g. UCreds course shell ID">
            <datalist id="ucreds-courses">
              @foreach($ucreds as $c)
                <option value="UCreds course {{ $c->course_id }}">{{ $c->title }}{{ $c->subject_code ? ' ('.$c->subject_code.')' : '' }}</option>
              @endforeach
            </datalist>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Public information') }}</h2></div>
        <div class="bd">
          <p class="muted" style="margin-top:0;font-size:13px">
            {{ __('Credit, MQF level and Quality Verification are never typed here. They appear only from verified records. Text that promises credit, transfer or a degree is refused.') }}
          </p>

          <div class="field" style="margin-bottom:16px">
            <label for="description">{{ __('Description') }}</label>
            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="prerequisites">{{ __('Prerequisites') }}</label>
            <input type="text" id="prerequisites" name="prerequisites" value="{{ old('prerequisites') }}" placeholder="e.g. Basic Python programming">
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="access_period">{{ __('Access period') }}</label>
              <input type="text" id="access_period" name="access_period" value="{{ old('access_period') }}">
            </div>
            <div class="field" style="flex:1">
              <label for="learning_support">{{ __('Learning support') }}</label>
              <input type="text" id="learning_support" name="learning_support" value="{{ old('learning_support') }}">
            </div>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="completion_conditions">{{ __('Completion conditions') }}</label>
            <input type="text" id="completion_conditions" name="completion_conditions" value="{{ old('completion_conditions') }}">
          </div>

          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="completion_window_months">{{ __('Months to finish all modules') }}</label>
            <input type="text" inputmode="numeric" id="completion_window_months" name="completion_window_months" value="{{ old('completion_window_months') }}">
            <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('For staged completion; empty uses default setting.') }}</div>
          </div>

          <div style="margin-bottom:20px">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="staged_completion" value="1">
              <span>{{ __('Modules may be completed in stages (a modular UMC)') }}</span>
            </label>
          </div>

          <div class="row" style="gap:12px">
            <button type="submit" class="btn">{{ __('Create offering') }}</button>
            <a class="btn ter" href="{{ route('offerings') }}">{{ __('Cancel') }}</a>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div>
    <div class="panel" style="position:sticky;top:20px">
      <div class="hd"><h2>{{ __('Readiness process') }}</h2></div>
      <div class="bd">
        <div class="col" style="gap:14px;font-size:13px;line-height:1.5">
          <div>
            <b>{{ __('1. Create cohort') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('New cohorts start in Not ready status and do not appear in the public catalogue.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:10px">
            <b>{{ __('2. Complete checklist') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('The lecturer fills evidence notes for all 6 operational readiness areas and sends the checklist to the deputy dean.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:10px">
            <b>{{ __('3. Deputy dean approval') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('The deputy dean reviews the evidence and records the readiness decision. Once confirmed, the cohort opens automatically on the registration opening date.') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection
