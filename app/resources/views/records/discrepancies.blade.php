@extends('layouts.app')
@section('title', __('Discrepancy log'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('tasks') }}">{{ auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard') }}</a>
  <span>/</span>
  <a href="{{ route('trace') }}">{{ __('Traceability and records') }}</a>
  <span>/</span>
  <span>{{ __('Discrepancy log') }}</span>
</div>

<div class="title" style="margin-bottom:16px">
  <div>
    <h1>{{ __('Discrepancy log') }}</h1>
    <div class="sub">{{ __('A mismatch between the achievement record, credential, Academic Credit Record and ABC information is logged here. Resolved only through corrections: nothing is erased.') }}</div>
  </div>
  <div class="act">
    <form method="post" action="{{ route('discrepancies.scan') }}">
      @csrf
      <button type="submit" class="btn primary sm">{{ __('Check the records now') }}</button>
    </form>
  </div>
</div>

<nav class="tabs" aria-label="Records navigation" style="margin-bottom:20px">
  <a href="{{ route('trace') }}">{{ __('Traceability') }}</a>
  <a href="{{ route('records.audit') }}">{{ __('Audit trail') }}</a>
  <a href="{{ route('reports') }}">{{ __('Reports') }}</a>
  <a href="{{ route('discrepancies') }}" aria-current="page">{{ __('Discrepancy log') }}</a>
  <a href="{{ route('retention') }}">{{ __('Retention') }}</a>
</nav>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Recorded record discrepancies') }}</h2>
    <span class="r small muted">{{ count($rows) }} {{ __('discrepancies') }}</span>
  </div>
  <div class="bd" style="padding:0">
    <table id="discrepancies" style="margin:0">
      <thead>
        <tr>
          <th style="width:110px">{{ __('Found') }}</th>
          <th>{{ __('Mismatch') }}</th>
          <th style="width:100px">{{ __('Status') }}</th>
          <th>{{ __('Correction') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $d)
        <tr>
          <td>{{ \Carbon\Carbon::parse($d->found_at)->format('d/m/Y') }}</td>
          <td><b>{{ $d->description }}</b></td>
          <td>
            <span class="tag {{ $d->status === 'open' ? 'warn' : 'ok' }}">{{ ucfirst($d->status) }}</span>
          </td>
          <td>
            @if($d->status === 'open')
              <form method="post" action="{{ route('discrepancies.resolve', $d->id) }}" style="display:flex;flex-direction:column;gap:8px">
                @csrf
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <div style="flex:1;min-width:180px">
                    <label for="reason-{{ $d->id }}" class="small" style="font-weight:600">{{ __('Reason') }}</label>
                    <input id="reason-{{ $d->id }}" name="reason" type="text" required style="width:100%">
                  </div>
                  <div style="flex:1;min-width:180px">
                    <label for="action-{{ $d->id }}" class="small" style="font-weight:600">{{ __('Action taken') }}</label>
                    <input id="action-{{ $d->id }}" name="action" type="text" required style="width:100%">
                  </div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                  <label class="small" style="display:inline-flex;align-items:center;gap:6px">
                    <input type="checkbox" name="source_checked" value="1">
                    {{ __('I checked the valid source record') }}
                  </label>
                  <button type="submit" class="btn sm primary" aria-label="Resolve: {{ $d->description }}">{{ __('Resolve') }}</button>
                </div>
              </form>
            @else
              <div>{{ $d->reason }}. {{ $d->action }} <span class="muted small">({{ \Carbon\Carbon::parse($d->corrected_on)->format('d/m/Y') }})</span></div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No mismatch is logged.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
