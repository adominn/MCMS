@extends('layouts.app')
@section('title', __('New proposal'))
@section('content')
<div class="crumb">
  <a href="{{ route('proposals') }}">{{ __('Proposals and versions') }}</a>
  <span>/</span>
  <span>{{ __('New proposal') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('New proposal') }}</h1>
    <div class="sub">{{ __('Answer three questions. The system suggests a category and says why.') }}</div>
  </div>
</div>

<div class="cols">
  <div>
    <form method="get" action="{{ route('proposals.new') }}">
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('1. Where do the modules come from?') }}</h2></div>
        <div class="bd">
          <div class="col" style="gap:12px">
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
              <input type="radio" name="origin" value="unbundled" @checked($origin === 'unbundled') style="margin-top:3px">
              <div>
                <b>{{ __('Modules from a UTeM credit course') }}</b>
                <div class="s" style="color:var(--muted);font-size:12px">{{ __('Unbundling: the category is UMC') }}</div>
              </div>
            </label>
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
              <input type="radio" name="origin" value="own" @checked($origin === 'own') style="margin-top:3px">
              <div>
                <b>{{ __('My own offering') }}</b>
                <div class="s" style="color:var(--muted);font-size:12px">{{ __('Not part of a credit course: SAMC or MCE by learning time') }}</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('2. Size and credit') }}</h2></div>
        <div class="bd">
          <div class="field" style="margin-bottom:16px">
            <label for="slt">{{ __('How many hours of learning time (SLT) in total?') }}</label>
            <input type="text" id="slt" name="slt" inputmode="numeric" value="{{ $slt }}" placeholder="{{ __('For example 40') }}" style="max-width:180px">
            <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('A SAMC needs at least 40 h. Under 40 h is an MCE.') }}</div>
          </div>

          <div class="field">
            <label>{{ __('3. Do you want a credit value or MQF level?') }}</label>
            <div class="row" style="gap:20px;margin-top:6px">
              <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                <input type="radio" name="credit" value="no" @checked(!$credit)>
                <span>{{ __('No') }}</span>
              </label>
              <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                <input type="radio" name="credit" value="yes" @checked($credit)>
                <span>{{ __('Yes') }}</span>
              </label>
            </div>
            <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('Credit and MQF level require Quality Verification (QV) for SAMC.') }}</div>
          </div>

          <div style="margin-top:20px">
            <button type="submit" class="btn">{{ __('Show the category') }}</button>
          </div>
        </div>
      </div>
    </form>

    @if($result)
      <form method="post" action="{{ route('proposals.create') }}">
        @csrf
        <input type="hidden" name="origin" value="{{ $origin }}">
        <input type="hidden" name="slt" value="{{ $slt }}">
        <input type="hidden" name="credit" value="{{ $credit ? 'yes' : 'no' }}">

        <div class="panel" style="margin-bottom:20px">
          <div class="hd"><h2>{{ __('Start the proposal') }}</h2></div>
          <div class="bd">
            <div class="field" style="margin-bottom:16px">
              <label for="proposal_type">{{ __('Type') }}</label>
              <select id="proposal_type" name="proposal_type" style="max-width:320px">
                <option value="new">{{ __('New') }}</option>
                <option value="revision" disabled>{{ __('Revision: use "Request a change" on an approved micro-credential') }}</option>
                <option value="reoffer" disabled>{{ __('Re-offer: use "My micro-credentials"') }}</option>
              </select>
            </div>

            <div class="field" style="margin-bottom:16px">
              <label for="ptj_id">{{ __('Owning PTj') }}</label>
              <select id="ptj_id" name="ptj_id" style="max-width:400px">
                @foreach($ptjs as $p)
                  <option value="{{ $p->id }}">{{ $p->code }} {{ $p->name }}</option>
                @endforeach
              </select>
            </div>

            @if($origin === 'unbundled')
              <div class="field" style="margin-bottom:16px">
                <label for="parent_course_id">{{ __('Parent course') }}</label>
                <select id="parent_course_id" name="parent_course_id" style="max-width:540px">
                  @foreach($courses as $c)
                    <option value="{{ $c->id }}">
                      {{ $c->code }} {{ $c->name }} ({{ $c->credit }} {{ __('credits') }}, {{ $c->slt_hours }} h){{ $c->accreditation_status === 'accredited' ? '' : ' - '.__('programme not accredited') }}
                    </option>
                  @endforeach
                </select>
                <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('From reference data. Parent course and SLT cannot be typed manually.') }}</div>
              </div>
            @endif

            <div class="field" style="margin-bottom:20px">
              <label for="name_en">{{ __('Working title (English)') }}</label>
              <input type="text" id="name_en" name="name_en" required maxlength="200" placeholder="{{ __('For example Software Testing') }}" style="max-width:540px">
              <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('You can add the Bahasa Melayu title in the workspace.') }}</div>
            </div>

            <div class="row" style="gap:12px">
              <button type="submit" class="btn">{{ __('Create proposal') }}</button>
              <a class="btn ter" href="{{ route('proposals') }}">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    @endif
  </div>

  <div>
    <div class="panel" style="position:sticky;top:20px">
      <div class="hd">
        <h2>{{ __('Suggested category') }}</h2>
      </div>
      <div class="bd">
        @if($result)
          <div role="region" aria-label="Proposed category">
            <div style="margin-bottom:12px">
              <x-cat :c="$result['category']" />
            </div>
            <h2 style="margin:0 0 10px;font-size:18px">{{ __('Proposed category: :cat', ['cat' => $categories[$result['category']]]) }}</h2>
            <p style="margin:0 0 14px;line-height:1.5">{{ $result['reason'] }}</p>
            <div class="note info" style="margin:0">
              {{ __('The category stays "proposed" until your deputy dean verifies it, and is fixed by approval.') }}
            </div>
          </div>
        @else
          <div class="muted" style="line-height:1.5">
            {{ __('Answer the three questions on the left. The system suggests a category (UMC, SAMC, or MCE) and explains what it means for your proposal.') }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
