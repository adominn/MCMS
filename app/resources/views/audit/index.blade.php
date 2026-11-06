@extends('layouts.app')
@section('title', 'Audit trail')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Audit trail') }}</span>
    </div>
    <h1>{{ __('Audit trail') }}</h1>
    <div class="sub">{{ __('Append-only: nobody can edit or delete an entry, including CODL administrators. Each entry is cryptographically chained to the previous one with SHA-256.') }}</div>
  </div>
  <div class="act" style="display:flex;gap:8px;align-items:center">
    <form method="post" action="{{ route('audit.verify') }}" style="margin:0">
      @csrf
      <button type="submit" class="btn ter">{{ __('Check integrity') }}</button>
    </form>
    <a href="{{ route('documents') }}" class="btn ghost sm">{{ __('Export CSV') }}</a>
  </div>
</div>



<div class="panel">
  <div class="hd">
    <h2>{{ __('Audit log entries') }}</h2>
    <span class="r small muted">{{ __('Showing the latest 100 entries') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:70px">{{ __('No.') }}</th>
          <th style="width:160px">{{ __('When (UTC)') }}</th>
          <th style="width:180px">{{ __('Who') }}</th>
          <th style="width:200px">{{ __('Action') }}</th>
          <th style="width:180px">{{ __('Record') }}</th>
          <th>{{ __('Reason') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($events as $e)
        <tr>
          <td><code>{{ $e->seq }}</code></td>
          <td>{{ \Carbon\Carbon::parse($e->occurred_at)->format('d/m/Y H:i:s') }}</td>
          <td>
            <b>{{ $e->actor ?? __('System') }}</b>
            @if($e->actor_role)
              <br><span class="tag muted" style="font-size:10px;margin-top:2px">{{ $e->actor_role }}</span>
            @endif
          </td>
          <td><code>{{ $e->action }}</code></td>
          <td>{{ $e->subject_type }} {{ $e->subject_id }}</td>
          <td class="small">{{ $e->reason }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="muted" style="text-align:center;padding:24px">{{ __('No audit events recorded.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="foot">
    <span class="small muted">{{ __('Showing the latest 100 entries.') }}</span>
  </div>
</div>

<div class="note" style="margin-top:16px">
  <div>
    <b>{{ __('Cryptographic verification') }}:</b>
    {{ __('Click "Check integrity" at any time to traverse the hash chain and verify that no row has been modified, deleted, or inserted out of order.') }}
  </div>
</div>
@endsection
