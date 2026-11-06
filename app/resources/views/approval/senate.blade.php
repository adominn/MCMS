@extends('layouts.app')
@section('title', __('Record Senate decision'))
@section('content')
<div class="crumb">
  <a href="{{ route('approvals') }}">{{ __('Proposals and approval') }}</a>
  <span>/</span>
  <a href="{{ route('approvals.show', $v->id) }}">{{ $v->name_en }}</a>
  <span>/</span>
  <span>{{ __('Record Senate decision') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('Record Senate decision') }}</h1>
    <div class="sub"><b>{{ $v->name_en }}</b> &middot; <x-pill :s="__($labels[$v->status] ?? ucfirst($v->status))" /></div>
  </div>
</div>

@if($v->status !== 'endorsed')
  <div class="note warn" role="alert">
    <b>{{ __('Not endorsed:') }}</b> {{ __('The Senate decision is recorded after endorsement. This proposal is currently :status.', ['status' => strtolower($labels[$v->status] ?? $v->status)]) }}
  </div>
  <div style="margin-top:16px">
    <a class="btn ter" href="{{ route('approvals.show', $v->id) }}">{{ __('Back to the proposal') }}</a>
  </div>
@else
<div class="cols">
  <div>
    <form method="post" action="{{ route('approvals.senate', $v->id) }}">
      @csrf
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Senate meeting details') }}</h2></div>
        <div class="bd">
          <p class="muted s" style="margin-top:0">
            {{ __('The Senate does not use MCMS directly. As deputy dean, you record its formal decision from meeting minutes. Endorsed by faculty on :date.', ['date' => \Carbon\Carbon::parse($endorsedOn)->format('d/m/Y')]) }}
          </p>

          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="meeting_ref">{{ __('Senate meeting reference') }}</label>
            <input type="text" id="meeting_ref" name="meeting_ref" required placeholder="{{ __('e.g. Senate 8/2026') }}">
          </div>

          <div class="field" style="margin-bottom:16px;max-width:240px">
            <label for="meeting_date">{{ __('Meeting date') }}</label>
            <input type="date" id="meeting_date" name="meeting_date" min="{{ \Carbon\Carbon::parse($endorsedOn)->toDateString() }}" max="{{ today()->toDateString() }}" required>
          </div>

          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="decision">{{ __('Decision') }}</label>
            <select id="decision" name="decision">
              <option value="approved">{{ __('Approved') }}</option>
              <option value="approved_conditions">{{ __('Approved with conditions') }}</option>
              <option value="not_approved">{{ __('Not approved') }}</option>
              <option value="returned">{{ __('Returned for amendment') }}</option>
              <option value="deferred">{{ __('Deferred') }}</option>
            </select>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="conditions">{{ __('Conditions (for approval with conditions)') }}</label>
            <input type="text" id="conditions" name="conditions" placeholder="{{ __('Specify any conditions imposed by Senate') }}">
          </div>

          @if($v->supersedes_id)
            <div class="panel" style="margin:20px 0;background:var(--surface)">
              <div class="hd"><h3>{{ __('Transition rule') }}</h3></div>
              <div class="bd">
                <p class="muted s" style="margin-top:0">
                  {{ __('This version replaces version :num. What happens to participants enrolled on the earlier version?', ['num' => $v->version_no - 1]) }}
                </p>
                <div class="field" style="margin-bottom:12px">
                  <select id="transition_rule" name="transition_rule" aria-label="Transition rule">
                    <option value="">-</option>
                    @foreach(config('proposals.transition_rules') as $k => $l)
                      <option value="{{ $k }}">{{ __($l) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="field">
                  <label for="transition_note">{{ __('Note (required for an equivalence)') }}</label>
                  <input type="text" id="transition_note" name="transition_note" placeholder="{{ __('Explain equivalence basis') }}">
                </div>
              </div>
            </div>
          @endif

          <div class="field" style="margin-bottom:20px">
            <label for="comments">{{ __('Wording from the minutes') }}</label>
            <textarea id="comments" name="comments" rows="3" required minlength="5" placeholder="{{ __('Extract of official Senate decision wording') }}"></textarea>
          </div>

          <div class="note info" style="margin-bottom:20px">
            <p style="margin:0;font-size:13px">
              {{ __('Approval of this version does not set any credit, Quality Verification or transfer status. Those are separate decisions.') }}
            </p>
          </div>

          <div class="row" style="gap:12px">
            <button type="submit" class="btn">{{ __('Record decision') }}</button>
            <a class="btn ter" href="{{ route('approvals.show', $v->id) }}">{{ __('Cancel') }}</a>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div>
    <div class="panel" style="position:sticky;top:20px">
      <div class="hd"><h2>{{ __('Governance guidance') }}</h2></div>
      <div class="bd">
        <div class="col" style="gap:14px;font-size:13px;line-height:1.5">
          <div>
            <b>{{ __('Senate decision only') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('Only approved Senate decisions permit cohorts to be offered. If returned or not approved, the proposal cycle halts or reopens for amendment.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:10px">
            <b>{{ __('Meeting date constraint') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('Meeting date cannot precede the faculty endorsement date or lie in the future.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:10px">
            <b>{{ __('Separation of credit claims') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('Under MQA guidelines, SAMC credit and MQF levels require an independent Quality Verification dossier review.') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection
