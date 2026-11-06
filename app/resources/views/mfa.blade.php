@extends('layouts.app')
@section('title', __('Verify it is you'))
@section('content')
<h1>{{ __('Verify it is you') }}</h1>
<div class="card">
  <p>{{ __('Roles that decide on records need a second step. Enter the 6-digit code from your authenticator.') }}</p>
  <p class="muted">{{ __('Test sign-in: any 6 digits are accepted.') }}</p>
  <form method="post" action="{{ route('signin.mfa') }}">@csrf
    <label for="code">{{ __('6-digit code') }}</label>
    <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required style="max-width:200px">
    <p><button type="submit">{{ __('Verify') }}</button></p>
  </form>
</div>
@endsection
