@extends('layouts.app')
@section('title', $t->description)
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <a href="{{ route('templates') }}">{{ __('Templates') }}</a> /
      <span>{{ $t->description }}</span>
    </div>
    <h1>{{ $t->description }}</h1>
    <div class="sub">
      <code>{{ $t->key }}</code> &middot;
      {{ ucfirst($t->kind) }} &middot;
      {{ $t->kind === 'notification' ? ($t->mandatory ? __('Mandatory notice (FR-267)') : __('Optional notice')) : __('System document template') }}
    </div>
  </div>
  <div class="act">
    <a href="{{ route('templates') }}" class="btn ghost sm">&larr; {{ __('All templates') }}</a>
  </div>
</div>


<div class="row" style="align-items:stretch;margin-bottom:24px;gap:20px">
  @foreach($locales as $l => $name)
    @php($c = $current[$l])
    <div class="panel grow" style="margin-bottom:0">
      <div class="hd">
        <h2>{{ $name }}</h2>
        <span class="r tag {{ $c ? 'ok' : 'warn' }}">
          {{ $c ? __('version :ver', ['ver' => $c->version]) : __('No version yet') }}
        </span>
      </div>
      <div class="bd">
        <form method="post" action="{{ route('templates.update', $t->key) }}" class="col" style="gap:12px">
          @csrf
          <input type="hidden" name="locale" value="{{ $l }}">

          @if($t->kind === 'notification')
            <div>
              <label for="subject-{{ $l }}" style="font-weight:600">{{ __('Subject') }}</label>
              <input type="text" id="subject-{{ $l }}" name="subject" value="{{ $c?->subject }}" style="width:100%" placeholder="{{ __('Notification subject line') }}">
            </div>
          @endif

          <div>
            <label for="body-{{ $l }}" style="font-weight:600">{{ __('Text (use {name}-style placeholders)') }}</label>
            <textarea id="body-{{ $l }}" name="body" rows="6" style="width:100%;font-family:inherit;font-size:13px;line-height:1.5" placeholder="{{ __('Template content with placeholders...') }}">{{ $c?->body }}</textarea>
          </div>

          <div>
            <label for="reason-{{ $l }}" style="font-weight:600">{{ __('Reason for the change') }}</label>
            <input type="text" id="reason-{{ $l }}" name="reason" required minlength="5" placeholder="{{ __('Audit reason for new version') }}" style="width:100%">
          </div>

          <div style="margin-top:6px">
            <button type="submit" class="btn ter sm">{{ __('Save new :lang version', ['lang' => $name]) }}</button>
          </div>
        </form>
      </div>
    </div>
  @endforeach
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Version history') }}</h2>
    <span class="r small muted">{{ count($history) }} {{ __('revisions') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Language') }}</th>
          <th>{{ __('Version') }}</th>
          <th>{{ __('Date') }}</th>
          <th>{{ __('Updated by') }}</th>
          <th>{{ __('Reason') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($history as $h)
        <tr>
          <td><span class="tag info">{{ strtoupper($h->locale) }}</span></td>
          <td><b>v{{ $h->version }}</b></td>
          <td>{{ \Carbon\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}</td>
          <td>{{ $h->set_by_name ?? __('System') }}</td>
          <td>{{ $h->reason }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="muted" style="text-align:center;padding:20px">{{ __('No version history recorded.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
