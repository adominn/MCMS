@extends('layouts.app')
@section('title', __('Request a change'))
@section('content')
@if(!$current)
  <div class="crumb"><a href="{{ route('mcs') }}">{{ __('My micro-credentials') }}</a> <span>/</span> <span>{{ __('Request a change') }}</span></div>
  <div class="title"><div><h1>{{ __('Request a change') }}</h1></div></div>
  <div class="note warn" role="alert"><p>{{ __('Only an approved version can be changed.') }}</p></div>
@elseif($blocked)
  <div class="crumb"><a href="{{ route('mcs') }}">{{ __('My micro-credentials') }}</a> <span>/</span> <span>{{ __('Request a change') }}</span></div>
  <div class="title"><div><h1>{{ __('Request a change') }}</h1></div></div>
  <div class="note info" role="status"><p>{{ __('Version :num is still in progress. Wait until it is decided.', ['num' => $blocked->version_no]) }}</p></div>
@else
<div class="crumb">
  <a href="{{ route('mcs') }}">{{ __('My micro-credentials') }}</a> <span>/</span>
  <a href="{{ route('mcs.show', $mc) }}">{{ $current->name_en }}</a> <span>/</span>
  <span>{{ __('Request a change') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('Request a change to :name', ['name' => $current->name_en]) }}</h1>
    <div class="sub">{{ __('Current approved version: v:num', ['num' => $current->version_no]) }}</div>
  </div>
</div>

<div class="cols">
  <div>
    <form method="post" action="{{ route('mcs.change', $mc) }}">
      @csrf
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('1. What do you want to change?') }}</h2></div>
        <div class="bd">
          <p class="muted" style="margin-top:0;font-size:13px">
            {{ __('If you tick any of the material change triggers below, the change makes a new version that is reviewed and approved again, taking effect only once approved. If you tick none, it is recorded as a minor change.') }}
          </p>
          <div class="col" style="gap:10px;margin-top:12px">
            @foreach($triggers as $k => $l)
              <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
                <input type="checkbox" name="triggers[]" value="{{ $k }}" style="margin-top:3px">
                <div>
                  <span>{{ __($l) }}</span>
                  <span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('Material change trigger') }}</span>
                </div>
              </label>
            @endforeach
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('2. Impact and description') }}</h2></div>
        <div class="bd">
          <div class="field" style="margin-bottom:16px">
            <label for="reason">{{ __('What is changing, and why?') }}</label>
            <textarea id="reason" name="reason" rows="3" required minlength="5" placeholder="{{ __('Describe the change and reason in detail') }}"></textarea>
            <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('Kept on the permanent version history.') }}</div>
          </div>

          <div class="field" style="margin-bottom:20px">
            <label for="impact_note">{{ __('Impact on participants (required for a minor change)') }}</label>
            <textarea id="impact_note" name="impact_note" rows="2" placeholder="{{ __('What changes for learners, facilitators, or assessment?') }}"></textarea>
            <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('Required if no material triggers are selected.') }}</div>
          </div>

          <div class="row" style="gap:12px">
            <button type="submit" class="btn">{{ __('Record the change') }}</button>
            <a class="btn ter" href="{{ route('mcs.show', $mc) }}">{{ __('Cancel') }}</a>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div>
    <div class="panel" style="position:sticky;top:20px">
      <div class="hd"><h2>{{ __('Change rules') }}</h2></div>
      <div class="bd">
        <div class="col" style="gap:16px">
          <div>
            <b>{{ __('Material change') }}</b>
            <p class="muted s" style="margin:4px 0 0">
              {{ __('Creates version :num in draft. Requires full faculty review and Senate approval before taking effect.', ['num' => $current->version_no + 1]) }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:12px">
            <b>{{ __('Minor change') }}</b>
            <p class="muted s" style="margin:4px 0 0">
              {{ __('Logged directly to the quality record. The deputy dean acknowledges the change; no new version is created.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:12px">
            <b>{{ __('Learners already enrolled') }}</b>
            <p class="muted s" style="margin:4px 0 0">
              {{ __('Learners in active or past cohorts remain on version :num. A new version applies only to subsequent offerings unless a transition rule is set.', ['num' => $current->version_no]) }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection
