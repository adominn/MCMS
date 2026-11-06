@extends('layouts.app')
@section('title', $title)
@section('content')
@guest @include('public._nav') @endguest
<div class="pub-sec">
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span> <span>{{ $title }}</span>
  </div>
  <div class="title">
    <div>
      <h1>{{ $title }}</h1>
      <div class="sub">{{ $sub }}</div>
    </div>
  </div>
  <div class="col" style="max-width:760px;gap:16px">
    @foreach($sections as [$heading, $text])
      <div class="card">
        <b>{{ $heading }}</b>
        <p style="margin-top:6px;line-height:1.5">{{ $text }}</p>
      </div>
    @endforeach
  </div>
</div>
@include('public._foot')
@endsection
