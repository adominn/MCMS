@extends('layouts.app')
@section('title', __('Readiness'))
@section('content')
<div class="crumb">
  <a href="{{ route('offerings') }}">{{ __('Offerings and readiness') }}</a>
  <span>/</span>
  <span>{{ $o->name_en }} {{ $o->cohort_name }}</span>
</div>

<div class="title">
  <div style="display:flex;align-items:center;gap:16px">
    <x-badge :category="$o->category ?? 'samc'" :size="48" />
    <div>
      <h1>{{ $o->name_en }} {{ $o->cohort_name }}</h1>
      <div class="sub">
        {{ __('Starts :date', ['date' => \Carbon\Carbon::parse($o->start_date)->format('d/m/Y')]) }} &middot;
        {{ __('registration :start to :end', ['start' => \Carbon\Carbon::parse($o->reg_opens_on)->format('d/m/Y'), 'end' => \Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y')]) }} &middot;
        {{ __(':count places', ['count' => $o->capacity]) }} &middot; RM {{ number_format($o->fee, 2) }}
      </div>
    </div>
  </div>
  <div class="act" style="gap:8px;align-items:center">
    <x-pill :s="__($labels[$o->status] ?? ucfirst($o->status))" />
    <a class="btn ter sm" href="{{ route('offerings.participants', $o->id) }}">{{ __('Participants') }}</a>
    @if(!$dean)
      <a class="btn ter sm" href="{{ route('results.cohort', $o->id) }}">{{ __('Results') }}</a>
    @else
      <a class="btn ter sm" href="{{ route('results.verify', $o->id) }}">{{ __('Results') }}</a>
    @endif
    @if($o->status === 'open')
      <a class="btn ter sm" href="{{ route('public.offering', $o->id) }}">{{ __('Public page') }}</a>
    @endif
  </div>
</div>

@if($o->status === 'held')
  <div class="note err" role="alert" style="margin-bottom:16px">
    <b>{{ __('On hold:') }}</b> {{ $o->held_reason }}
  </div>
@endif

@if($o->status === 'cancelled')
  <div class="note err" role="alert" style="margin-bottom:16px">
    <b>{{ __('Cancelled:') }}</b> {{ $o->cancel_reason }}
  </div>
@endif

@php($lecturerSent = $confirmations->where('role', 'lecturer')->isNotEmpty())

<form method="post" action="{{ $dean ? route('offerings.confirm', $o->id) : route('offerings.readiness', $o->id) }}">
  @csrf
  <div class="panel" style="margin-bottom:24px">
    <div class="hd">
      <h2>{{ __('Readiness checklist') }}</h2>
      @if(!$dean && !$editable)
        <span class="r"><span class="tag n">{{ __('Locked') }}</span></span>
      @endif
    </div>
    <div class="bd" style="padding:0">
      <table>
        <thead>
          <tr>
            <th style="width:35%">{{ __('Item') }}</th>
            <th style="width:18%">{{ __('Status') }}</th>
            <th style="width:32%">{{ __('Evidence note') }}</th>
            <th style="width:15%">@if($dean){{ __('Accept evidence') }} @else {{ __('Accepted') }} @endif</th>
          </tr>
        </thead>
        <tbody>
        @foreach($items_cfg as $k => $label)
          @php($it = $items[$k] ?? (object) ["status" => "not_yet", "note" => "", "accepted" => false])
          <tr>
            <td><b>{{ __($label) }}</b></td>
            <td>
              @if($dean)
                <span class="tag {{ $it->status === 'yes' ? 'ok' : ($it->status === 'na' ? 'n' : 'warn') }}">
                  {{ __(['yes' => 'Yes', 'not_yet' => 'Not yet', 'na' => 'Not applicable'][$it->status]) }}
                </span>
              @else
                <select name="item[{{ $k }}][status]" aria-label="Status of {{ $label }}" @disabled(!$editable)>
                  @foreach(['yes' => 'Yes', 'not_yet' => 'Not yet', 'na' => 'Not applicable'] as $sv => $sl)
                    <option value="{{ $sv }}" @selected($it->status === $sv)>{{ __($sl) }}</option>
                  @endforeach
                </select>
              @endif
            </td>
            <td>
              @if($dean)
                {{ $it->note }}
              @else
                <input type="text" name="item[{{ $k }}][note]" value="{{ $it->note }}" aria-label="Evidence note for {{ $label }}" @disabled(!$editable) placeholder="{{ __('Reference to evidence') }}">
              @endif
            </td>
            <td>
              @if($dean && $it->status === 'yes')
                <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer">
                  <input type="checkbox" name="accept[{{ $k }}]" value="1" @checked($it->accepted) aria-label="Accept evidence for {{ $label }}">
                  <span>{{ __('Accept') }}</span>
                </label>
              @elseif($dean)
                <span class="muted">-</span>
              @else
                @if($it->accepted)
                  <span class="tag ok">{{ __('Accepted') }}</span>
                @else
                  <span class="muted">-</span>
                @endif
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    @if($dean && $editable)
      <div class="bd" style="border-top:1px solid var(--border)">
        @if(!$lecturerSent)
          <p class="muted" style="margin:0">{{ __('You can confirm once the lecturer has sent the checklist.') }}</p>
        @else
          <h3>{{ __('Confirm readiness') }}</h3>
          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="outcome">{{ __('Outcome') }}</label>
            <select id="outcome" name="outcome">
              @foreach(config('offerings.outcomes') as $k => $l)
                <option value="{{ $k }}">{{ __($l) }}</option>
              @endforeach
            </select>
          </div>
          <div class="field" style="margin-bottom:20px;max-width:540px">
            <label for="conditions">{{ __('Conditions (for "Ready with conditions")') }}</label>
            <input type="text" id="conditions" name="conditions">
          </div>
          <button type="submit" class="btn">{{ __('Record decision') }}</button>
        @endif
      </div>
    @elseif(!$dean && $editable)
      <div class="bd" style="border-top:1px solid var(--border)">
        <div style="margin-bottom:20px">
          <button type="submit" name="action" value="save" class="btn ter sm">{{ __('Save') }}</button>
        </div>
        <h3>{{ __('Send to the deputy dean') }}</h3>
        <div class="field" style="margin-bottom:16px;max-width:320px">
          <label for="outcome">{{ __('Your view') }}</label>
          <select id="outcome" name="outcome">
            @foreach(config('offerings.outcomes') as $k => $l)
              <option value="{{ $k }}">{{ __($l) }}</option>
            @endforeach
          </select>
        </div>
        <div class="field" style="margin-bottom:20px;max-width:540px">
          <label for="conditions">{{ __('Conditions (for "Ready with conditions")') }}</label>
          <input type="text" id="conditions" name="conditions">
        </div>
        <button type="submit" name="action" value="send" class="btn">{{ __('Send the checklist') }}</button>
      </div>
    @endif
  </div>
</form>

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Confirmations') }}</h2></div>
  <div class="bd" style="padding:0">
    <table>
      <thead>
        <tr>
          <th>{{ __('Who') }}</th>
          <th>{{ __('Role') }}</th>
          <th>{{ __('Outcome') }}</th>
          <th>{{ __('Date') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($confirmations as $c)
        <tr>
          <td><b>{{ $c->name }}</b></td>
          <td>{{ $c->role === 'ptj' ? __('PTj (deputy dean)') : __('Lecturer') }}</td>
          <td>
            <x-pill :s="__(config('offerings.outcomes')[$c->outcome] ?? $c->outcome)" />
            @if($c->conditions) &mdash; <span class="s muted">{{ $c->conditions }}</span>@endif
          </td>
          <td>{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="muted" style="text-align:center;padding:24px">
            {{ __('No confirmation yet. Registration stays closed until the deputy dean confirms.') }}
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($dean && !in_array($o->status, ['cancelled', 'ended', 'running']))
  <form method="post" action="{{ route('offerings.cancel', $o->id) }}" class="panel" data-confirm="{{ __('Cancel this cohort?|It leaves the catalogue and registered participants are told. The reason is kept in the audit trail.') }}" data-danger>
    @csrf
    <div class="hd"><h2>{{ __('Cancel this cohort') }}</h2></div>
    <div class="bd">
      <div class="field" style="margin-bottom:16px;max-width:540px">
        <label for="creason">{{ __('Reason') }}</label>
        <input type="text" id="creason" name="reason" required minlength="5">
      </div>
      <button type="submit" class="btn danger">{{ __('Cancel cohort') }}</button>
    </div>
  </form>
@endif
@endsection
