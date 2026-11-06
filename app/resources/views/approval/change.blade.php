@extends('layouts.app')
@section('title', __('Change'))
@section('content')
<h1>{{ __('Minor change: :name', ['name' => $v->name_en]) }}</h1>
<div class="card">
  <p>{{ __('Version :num · requested by :owner on :date', ['num' => $v->version_no, 'owner' => $v->owner, 'date' => \Carbon\Carbon::parse($cr->created_at)->format('d/m/Y')]) }}</p>
  <p><b>{{ __('Change:') }}</b> {{ $cr->reason }}</p>
  <p><b>{{ __('Impact on participants:') }}</b> {{ $cr->impact_note }}</p>
  @if($cr->status === 'recorded')
    <form method="post" action="{{ route('changes.acknowledge', $cr->id) }}">@csrf <button type="submit" class="btn sm">{{ __('Acknowledge') }}</button></form>
    <p class="muted s">{{ __('Acknowledging logs the change in the quality record of the offering.') }}</p>
  @else
    <p><b>{{ __('Acknowledged') }}</b> {{ \Carbon\Carbon::parse($cr->acknowledged_at)->format('d/m/Y H:i') }}; {{ __('logged for the quality record.') }}</p>
  @endif
  <a class="btn ghost sm" href="{{ route('approvals') }}">&larr; {{ __('Back to proposals and approval') }}</a>
</div>
@endsection
