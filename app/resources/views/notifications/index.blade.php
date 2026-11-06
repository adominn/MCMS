@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
@php
  $unread = $all->whereNull('read_at')->count();
  $list = $show === 'unread' ? $all->whereNull('read_at') : ($show === 'read' ? $all->whereNotNull('read_at') : $all);
@endphp
<div class="crumb" style="margin-bottom:8px"><a href="{{ route('tasks') }}">Home</a> <span>/</span> <span>{{ __('Notifications') }}</span></div>
<div class="title"><div><h1>{{ __('Notifications') }}</h1><div class="sub">{{ __(':count unread', ['count' => $unread]) }}</div></div>
  <div class="act"><form method="post" action="{{ route('notifications.readall') }}">@csrf<button type="submit" class="ter" @disabled(!$unread)>{{ __('Mark all read') }}</button></form><a class="btn ter" href="{{ route('profile') }}#notifications">{{ __('Preferences') }}</a></div></div>
<nav class="tabs" aria-label="Notifications">
  <a href="{{ route('notifications') }}" @if($show === 'all') aria-current="page" @endif>{{ __('All') }} <span class="muted">{{ $all->count() }}</span></a>
  <a href="{{ route('notifications', ['show' => 'unread']) }}" @if($show === 'unread') aria-current="page" @endif>{{ __('Unread') }} <span class="muted">{{ $unread }}</span></a>
  <a href="{{ route('notifications', ['show' => 'read']) }}" @if($show === 'read') aria-current="page" @endif>{{ __('Read') }} <span class="muted">{{ $all->count() - $unread }}</span></a>
</nav>
<div class="panel">
  <table id="notices">
    <thead><tr><th>{{ __('State') }}</th><th>{{ __('Notification') }}</th><th>{{ __('Received') }}</th><th>{{ __('Sent by email') }}</th></tr></thead>
    <tbody>
    @forelse($list as $n)
      <tr data-href="{{ route('notifications.open', $n->id) }}">
        <td>@if($n->read_at)<span class="muted">{{ __('Read') }}</span>@else<span class="tag info">{{ __('Unread') }}</span>@endif</td>
        <td><a href="{{ route('notifications.open', $n->id) }}" @if(!$n->read_at) style="font-weight:600" @endif>{{ $n->subject }}</a><span class="s">{{ $n->body }}</span>
          @if(!$n->read_at)<form method="post" action="{{ route('notifications.read', $n->id) }}" style="display:inline">@csrf <button type="submit" class="ghost sm">{{ __('Mark as read') }}</button></form>@endif</td>
        <td data-v="{{ $n->created_at }}">{{ \Carbon\Carbon::parse($n->created_at)->format('d/m/Y H:i') }}</td>
        <td>{{ $emailed->has($n->subject) ? __('Yes') : __('No') }}</td>
      </tr>
    @empty
      <tr><td colspan="4" class="muted">{{ __('No notifications here.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
