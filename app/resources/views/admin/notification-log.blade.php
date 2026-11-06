@extends('layouts.app')
@section('title', 'Notification log')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Notification log') }}</span>
    </div>
    <h1>{{ __('Notification log') }}</h1>
    <div class="sub">{{ __('Every email and in-system notice MCMS sent or tried to send, recorded in the recipient language.') }}</div>
  </div>
</div>


<div class="panel">
  <div class="hd">
    <h2>{{ __('Recent transmissions') }}</h2>
    <span class="r small muted">{{ __('Latest 100 entries') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:70px">{{ __('No.') }}</th>
          <th style="width:160px">{{ __('Time') }}</th>
          <th style="width:200px">{{ __('Recipient') }}</th>
          <th>{{ __('Template') }}</th>
          <th>{{ __('Channel') }}</th>
          <th>{{ __('Language') }}</th>
          <th>{{ __('Status') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        <tr>
          <td><code>{{ $r->id }}</code></td>
          <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
          <td><b>{{ $r->name }}</b></td>
          <td><code>{{ $r->template_key }}</code></td>
          <td>
            <span class="tag {{ $r->channel === 'email' ? 'info' : 'muted' }}">{{ $r->channel }}</span>
          </td>
          <td>{{ strtoupper($r->locale) }}</td>
          <td>
            <span class="tag {{ $r->status === 'sent' ? 'ok' : ($r->status === 'failed' ? 'warn' : 'info') }}">
              {{ ucfirst($r->status) }}
            </span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="muted" style="text-align:center;padding:24px">{{ __('No notifications logged.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
