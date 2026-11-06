@extends('layouts.app')
@section('title', __('Demo clock'))
@section('content')
<h1>{{ __('Demo clock') }}</h1>
<p class="muted">{{ __('Test environments only. The demo clock moves the date forward so that registration, cohort dates, Quality Verification and credit expiry can be shown. It cannot be switched on in production.') }}</p>
<p id="clock">{{ __('Date in MCMS:') }} <strong>{{ $today->format('d/m/Y') }}</strong> ({{ __(':days day(s) ahead of the real date.', ['days' => $offset]) }}).</p>
<form method="post" action="{{ route('demo.clock.move') }}" class="card">@csrf
  <label for="days">{{ __('Move forward by (days)') }}</label><input id="days" name="days" type="number" min="0" max="4000" value="30" required>
  <button type="submit">{{ __('Move the clock') }}</button>
</form>
<form method="post" action="{{ route('demo.clock.move') }}">@csrf<input type="hidden" name="days" value="0"><input type="hidden" name="reset" value="1"><button type="submit">{{ __('Back to the real date') }}</button></form>
<form method="post" action="{{ route('demo.clock.jobs') }}" class="card">@csrf
  <p>{{ __('Run the daily jobs now, so that cohorts open and close, Quality Verification and credit expire, and reminders go out for the date shown.') }}</p>
  <button type="submit">{{ __('Run the daily jobs') }}</button>
</form>
@endsection
