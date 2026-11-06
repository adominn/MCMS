@extends('layouts.app')
@section('title', $heading)
@section('content')
<div style="padding:32px 0;display:flex;flex-direction:column;gap:16px;max-width:640px">
  <h1>{{ $heading }}</h1>
  <div class="muted">{{ $text }}</div>
  <div style="display:flex;gap:8px"><a class="btn" href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? 'Go to my work' : 'Go to the home page' }}</a><a class="btn ter" href="javascript:history.back()">Go back</a></div>
</div>
@endsection
