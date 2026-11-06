@extends('layouts.app')
@section('title', __('Cohort results'))
@section('content')
<h1>{{ __('Results: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
<p><a href="{{ route('offerings.show', $o->id) }}">{{ __('The cohort') }}</a> &middot; <a href="{{ route('offerings.participants', $o->id) }}">{{ __('Participants') }}</a>
  @if($locked)&middot; <b>{{ __('Submitted for verification') }}</b> {{ \Carbon\Carbon::parse($locked)->format('d/m/Y H:i') }}@endif</p>
<p class="muted">{{ __('The assessment plan comes from the approved version and cannot be changed here. Attendance or progress never gives a pass: only verified marks do.') }}</p>
<form method="post" action="{{ route('results.save', $o->id) }}" class="card">@csrf
  @include('results._table', ['flag' => false, 'changeable' => $locked === null ? false : true])
  @if($editable)
    <p><button type="submit" name="action" value="save" class="btn sm">{{ __('Save marks') }}</button> <button type="submit" name="action" value="submit" class="btn ter sm">{{ __('Submit for verification') }}</button></p>
  @else
    <p><button type="submit" name="action" value="withdraw" class="btn ter sm">{{ __('Withdraw the submission') }}</button></p>
  @endif
</form>
@if($editable)
<h2>{{ __('Integrity cases') }}</h2>
<p class="muted">{{ __("A flagged participant's results are held and cannot be verified until the deputy dean resolves the case.") }}</p>
<table><tbody>@foreach($enrol as $e)<tr><td><b>{{ $e->name }}</b></td><td><form method="post" action="{{ route('results.flag', $o->id) }}" class="row" style="gap:8px;align-items:center">@csrf<input type="hidden" name="enrolment_id" value="{{ $e->id }}"><input type="text" name="reason" placeholder="{{ __('Reason') }}" required minlength="5" aria-label="Reason to flag {{ $e->name }}" style="max-width:320px"> <button type="submit" class="btn danger sm">{{ __('Flag integrity case') }}</button></form></td></tr>@endforeach</tbody></table>
@endif
@endsection
