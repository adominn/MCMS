@extends('layouts.app')
@section('title', __('My tasks'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('My tasks') }}</h1>
    <div class="sub">
      {{ $total }} {{ $total === 1 ? __('item is') : __('items are') }} {{ __('waiting for you') }} &middot;
      {{ __('reminders go out after :days days', ['days' => $days]) }}
      @if($roles->isNotEmpty())
        &middot;
        @foreach($roles as $r)
          <span class="tag">{{ $r->label() }}</span>
        @endforeach
      @endif
    </div>
  </div>
  @if($kpis)
    <div class="act" style="gap:8px">
      <a class="btn ter sm" href="{{ route('offerings.new') }}">{{ __('Create offering') }}</a>
      <a class="btn sm" href="{{ route('proposals.new') }}">{{ __('New proposal') }}</a>
    </div>
  @endif
</div>

@if($kpis)
  <div class="kpis" style="margin-bottom:20px">
    @foreach($kpis as $l => $n)
      <div class="kpi">
        <div class="l">{{ __($l) }}</div>
        <div class="v">{{ $n }}</div>
      </div>
    @endforeach
  </div>
@endif

<nav class="tabs" aria-label="Areas" style="margin-bottom:20px">
  <a href="{{ route('tasks', array_filter(['q' => $q])) }}" @if(!$area) aria-current="page" class="on" @endif>
    {{ __('All') }} ({{ $total }})
  </a>
  @foreach($areas as $key => $label)
    <a href="{{ route('tasks', array_filter(['area' => $key, 'q' => $q])) }}" @if($area === $key) aria-current="page" class="on" @endif>
      {{ __($label) }} ({{ $counts[$key] ?? 0 }})
    </a>
  @endforeach
</nav>

<div class="cols">
  <div>
    <section class="panel" aria-label="Task queue">
      <div class="hd" style="padding:12px 16px">
        <form method="get" action="{{ route('tasks') }}" role="search" class="row" style="gap:8px;align-items:center;width:100%">
          @if($area)<input type="hidden" name="area" value="{{ $area }}">@endif
          <input type="search" id="q" name="q" value="{{ $q }}" aria-label="{{ __('Search tasks') }}" placeholder="{{ __('Search the queue') }}" style="flex:1;max-width:360px">
          <button type="submit" class="btn ter sm">{{ __('Search') }}</button>
        </form>
      </div>
      @if($tasks->isEmpty())
        <div style="padding:36px;text-align:center" class="muted">
          {{ __('Nothing is waiting for you in this group.') }}
        </div>
      @else
      <table>
        <thead>
          <tr>
            <th style="width:40%">{{ __('Decision') }}</th>
            <th style="width:20%">{{ __('Area') }}</th>
            <th class="num" style="width:18%">{{ __('Waiting') }}</th>
            <th style="width:12%">{{ __('Status') }}</th>
            <th style="width:10%;text-align:right"></th>
          </tr>
        </thead>
        <tbody>
        @foreach($tasks as $t)
          @php
            $waited = (int) \Carbon\Carbon::parse($t->created_at)->diffInDays(now());
            $isOverdue = \Carbon\Carbon::parse($t->created_at)->lt($overdueBefore);
          @endphp
          <tr>
            <td>
              <a href="{{ route('tasks.show', $t->id) }}" style="font-weight:600">{{ $t->title }}</a>
              @if($t->subject_ref)
                <span class="s" style="display:block;color:var(--muted);font-size:12px">{{ $t->subject_ref }}</span>
              @endif
            </td>
            <td>{{ __($areas[$t->area] ?? ucfirst($t->area)) }}</td>
            <td class="num">
              <b>{{ $waited === 0 ? __('Today') : $waited.' '.($waited === 1 ? __('day') : __('days')) }}</b>
              <span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('since') }} {{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y') }}</span>
            </td>
            <td>
              @if($isOverdue)
                <span class="tag overdue">{{ __('Overdue') }}</span>
              @else
                <span class="tag">{{ __('On time') }}</span>
              @endif
            </td>
            <td style="text-align:right">
              <a class="btn ter sm" href="{{ route('tasks.show', $t->id) }}" aria-label="{{ __('Open this task') }}">{{ __('Open') }}</a>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="panel-foot" style="background:transparent;margin:0;padding:0">
        {{ $tasks->links('pagination.simple') }}
      </div>
      @endif
    </section>
  </div>

  <div>
    <aside style="position:sticky;top:20px">
      <section class="panel" aria-labelledby="h-recent">
        <div class="hd"><h2 id="h-recent">{{ __('Decided in the last 7 days') }}</h2></div>
        <div class="bd">
          @forelse($recent as $t)
            <div class="check" style="margin-bottom:12px">
              <span class="dot ok"></span>
              <div class="grow">
                <b>{{ $t->title }}</b>
                <span class="s" style="display:block;color:var(--muted);font-size:12px">{{ \Carbon\Carbon::parse($t->completed_at)->format('d/m/Y H:i') }}</span>
              </div>
            </div>
          @empty
            <div class="muted s" style="line-height:1.5">
              {{ __('Decisions you take appear here with a link to their permanent record.') }}
            </div>
          @endforelse
        </div>
      </section>
    </aside>
  </div>
</div>
@endsection
