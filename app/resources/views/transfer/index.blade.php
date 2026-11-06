@extends('layouts.app')
@section('title', __('Credit transfer'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Credit transfer') }}</h1>
    <div class="sub">{{ __('Apply to have credit recognised by a degree programme. The programme decides each case.') }}</div>
  </div>
  <div class="act">
    <a class="btn" href="{{ route('transfer.create') }}">{{ __('New transfer application') }}</a>
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>
    {{ __("A transfer application asks a degree programme to accept credit from your Academic Credit Record. The receiving programme decides, not UTeM's MCMS and not ABC. A decision is separate from admission to the programme.") }}
  </div>
</div>

<div class="panel">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <h2 style="margin:0">{{ __('My Transfer Applications') }}</h2>
      <div class="s muted" style="margin-top:2px">{{ __('Review submitted credit transfer applications and their faculty evaluation decisions.') }}</div>
    </div>
    <span class="r">
      <span class="s muted">{{ __(':count applications', ['count' => count($rows)]) }}</span>
    </span>
  </div>
  <table id="applications">
    <thead>
      <tr>
        <th style="min-width:180px">{{ __('Application Reference') }}</th>
        <th style="min-width:240px">{{ __('Target Degree Programme') }}</th>
        <th>{{ __('Applied Date') }}</th>
        <th>{{ __('Application Status') }}</th>
        <th class="num" style="min-width:130px">{{ __('Actions') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($rows as $r)
      <tr data-href="{{ route('transfer.show', $r->id) }}">
        <td>
          <a href="{{ route('transfer.show', $r->id) }}" style="font-weight:700;color:var(--ink)">
            {{ $r->reference }}
          </a>
        </td>
        <td>
          <div style="font-weight:600;color:var(--ink)">{{ $r->programme_name }}</div>
          <div class="s muted"><code>{{ $r->programme }}</code></div>
        </td>
        <td class="s">
          <div>{{ \Carbon\Carbon::parse($r->applied_at)->format('d/m/Y') }}</div>
          <div class="muted">{{ \Carbon\Carbon::parse($r->applied_at)->diffForHumans() }}</div>
        </td>
        <td>
          <x-pill :s="$status[$r->status]" />
        </td>
        <td class="num">
          <a href="{{ route('transfer.show', $r->id) }}" class="btn ter sm">
            <span class="btn-lbl">{{ __('View application') }} &rarr;</span>
          </a>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="5" class="muted" style="text-align:center;padding:36px">
          {{ __('You have not applied for credit transfer yet.') }}
        </td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
