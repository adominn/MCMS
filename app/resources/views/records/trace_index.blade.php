@extends('layouts.app')
@section('title', __('Traceability and records'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('tasks') }}">{{ auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard') }}</a>
  <span>/</span>
  <span>{{ __('Traceability and records') }}</span>
</div>

<div class="title" style="margin-bottom:16px">
  <div>
    <h1>{{ __('Traceability and records') }}</h1>
    <div class="sub">{{ __('Inspect the end-to-end credential lifecycle from registration to Academic Bank of Credit deposit for any learner.') }}</div>
  </div>
</div>

<nav class="tabs" aria-label="Records navigation" style="margin-bottom:20px">
  <a href="{{ route('trace') }}" aria-current="page">{{ __('Traceability') }}</a>
  <a href="{{ route('records.audit') }}">{{ __('Audit trail') }}</a>
  <a href="{{ route('reports') }}">{{ __('Reports') }}</a>
  @if(auth()->user()->hasAnyRole('deputy_dean'))
    <a href="{{ route('discrepancies') }}">{{ __('Discrepancy log') }}</a>
  @endif
  @if(auth()->user()->hasAnyRole('deputy_dean', 'codl_admin'))
    <a href="{{ route('retention') }}">{{ __('Retention') }}</a>
  @endif
</nav>

<div class="panel" style="margin-bottom:24px">
  <div class="bd">
    <form method="get" action="{{ route('trace') }}" role="search" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <div class="field" style="flex:1;min-width:280px">
        <label for="q" style="font-weight:600;display:block;margin-bottom:6px">{{ __('Find a participant by name or matric number') }}</label>
        <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('e.g. Amir Hakimi, B032410001') }}" style="width:100%">
      </div>
      <button type="submit" class="btn primary sm" style="height:36px">{{ __('Search') }}</button>
      @if($q !== '')
        <a href="{{ route('trace') }}" class="btn ghost sm" style="height:36px">{{ __('Clear') }}</a>
      @endif
    </form>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Participant search results') }}</h2>
    @if($q !== '')
      <span class="r small muted">{{ count($rows) }} {{ __('matching participant records') }}</span>
    @endif
  </div>
  <div class="bd" style="padding:0">
    <table id="trace-results" style="margin:0">
      <thead>
        <tr>
          <th>{{ __('Participant') }}</th>
          <th>{{ __('Registration') }}</th>
          <th>{{ __('Micro-credential') }}</th>
          <th style="text-align:right">{{ __('Action') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        <tr>
          <td>
            <b>{{ $r->name }}</b>
            @if($r->institution_id)<div class="small muted">{{ $r->institution_id }}</div>@endif
          </td>
          <td>
            <code>{{ $r->reference }}</code>
            <span class="tag sm" style="margin-left:6px">{{ ucfirst($r->status) }}</span>
          </td>
          <td>{{ $r->name_en }} &middot; <span class="muted">{{ $r->cohort_name }}</span></td>
          <td class="num" style="text-align:right">
            <a class="btn ter sm" href="{{ route('trace.show', $r->id) }}">{{ __('Trace') }} &rarr;</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="muted" style="text-align:center;padding:32px 16px">
            {{ $q === '' ? __('Search for a participant.') : __('No participant matches inside your scope.') }}
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
