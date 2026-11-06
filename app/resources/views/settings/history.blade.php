@extends('layouts.app')
@section('title', 'Setting history')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <a href="{{ route('settings') }}">{{ __('Settings') }}</a> /
      <span>{{ __('History') }}</span>
    </div>
    <h1>{{ $def['en'] }}</h1>
    <div class="sub">
      <code>{{ $key }}</code> &middot;
      {{ $def['ref'] ?? '' }} &middot;
      {{ __('Decisions use the value in force on the day they were taken; changes never rewrite earlier decisions.') }}
    </div>
  </div>
  <div class="act">
    <a href="{{ route('settings') }}" class="btn ghost sm">&larr; {{ __('Back to settings') }}</a>
  </div>
</div>


<div class="panel">
  <div class="hd">
    <h2>{{ __('Audit history of values') }}</h2>
    <span class="r small muted">{{ count($versions) }} {{ __('versions recorded') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Effective date') }}</th>
          <th>{{ __('Value') }}</th>
          <th>{{ __('Set by') }}</th>
          <th>{{ __('Reason') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($versions as $v)
        <tr>
          <td>
            <b>{{ \Carbon\Carbon::parse($v->effective_from)->format('d/m/Y H:i') }}</b>
          </td>
          <td>
            <code style="font-size:14px;font-weight:600">{{ \App\Modules\Core\Settings::show($v->value) }}</code>
          </td>
          <td>{{ $v->set_by ?? __('System') }}</td>
          <td>{{ $v->reason }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No changes recorded since initial seed.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="note" style="margin-top:16px">
  <div>
    <b>{{ __('Audit integrity') }}:</b>
    {{ __('Each version change is logged in the hash-chained audit trail. Historic decisions that were evaluated under previous values remain permanently bound to those versions.') }}
  </div>
</div>
@endsection
