@extends('layouts.app')
@section('title', __('Offerings and readiness'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Offerings and readiness') }}</h1>
    <div class="sub">{{ __('Cohorts of approved versions, and who has confirmed that each is ready.') }}</div>
  </div>
  <div class="act">
    <a class="btn" href="{{ route('offerings.new') }}">{{ __('New offering') }}</a>
  </div>
</div>

@if($rows->isEmpty())
  <div class="panel">
    <div class="bd" style="text-align:center;padding:36px;color:var(--muted)">
      {{ __('No offerings yet.') }}
    </div>
  </div>
@else
<div class="panel">
  <table>
    <thead>
      <tr>
        <th>{{ __('Cohort') }}</th>
        <th>{{ __('Version') }}</th>
        <th>{{ __('Starts') }}</th>
        <th>{{ __('Registration opens') }}</th>
        <th>{{ __('Status') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
      <tr>
        <td>
          <a href="{{ route('offerings.show', $r->id) }}" style="font-weight:600">
            {{ $r->name_en }} {{ $r->cohort_name }}
          </a>
        </td>
        <td>
          v{{ $r->version_no }}
        </td>
        <td>
          {{ \Carbon\Carbon::parse($r->start_date)->format('d/m/Y') }}
        </td>
        <td>
          {{ \Carbon\Carbon::parse($r->reg_opens_on)->format('d/m/Y') }}
        </td>
        <td>
          <x-pill :s="__($labels[$r->status] ?? ucfirst($r->status))" />
          @if($r->status === 'not_ready' && $r->readiness_state !== 'pending')
            <span class="muted s" style="display:block;font-size:12px">({{ __('confirmed: :state', ['state' => str_replace('_', ' ', $r->readiness_state)]) }})</span>
          @endif
        </td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('offerings.show', $r->id) }}">
            {{ __('View') }}
          </a>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
