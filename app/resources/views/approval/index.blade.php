@extends('layouts.app')
@section('title', 'Proposals and approval')
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Proposals and approval') }}</h1>
    <div class="sub">{{ __('Endorse proposals, check their category and record Senate decisions.') }}</div>
  </div>
</div>

<nav class="tabs" aria-label="Status filter" style="margin-bottom:20px">
  <a href="{{ route('approvals') }}" class="{{ !$status ? 'on' : '' }}">
    {{ __('All') }} ({{ $counts->sum() }})
  </a>
  @foreach($labels as $k => $l)
    @continue($k === 'draft')
    <a href="{{ route('approvals', ['status' => $k]) }}" class="{{ $status === $k ? 'on' : '' }}">
      {{ __($l) }} ({{ $counts[$k] ?? 0 }})
    </a>
  @endforeach
</nav>

@if($rows->isEmpty())
  <div class="panel">
    <div class="bd" style="text-align:center;padding:36px;color:var(--muted)">
      {{ __('No proposals in this group.') }}
    </div>
  </div>
@else
<div class="panel">
  <table>
    <thead>
      <tr>
        <th>{{ __('Micro-credential') }}</th>
        <th>{{ __('Proposer') }}</th>
        <th>{{ __('Category') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Submitted') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
      <tr>
        <td>
          <a href="{{ route('approvals.show', $r->id) }}" style="font-weight:600">
            {{ $r->name_en }}
          </a>
        </td>
        <td>{{ $r->owner }}</td>
        <td>
          <div class="row" style="gap:6px;align-items:center">
            <x-cat :c="$r->category" />
            <x-pill :s="__(['proposed' => 'Proposed', 'verified' => 'Verified', 'confirmed' => 'Confirmed'][$r->category_status] ?? ucfirst($r->category_status))" />
          </div>
        </td>
        <td>
          <x-pill :s="__($labels[$r->status] ?? ucfirst(str_replace('_', ' ', $r->status)))" />
        </td>
        <td>
          {{ $r->submitted_at ? \Carbon\Carbon::parse($r->submitted_at)->format('d/m/Y') : '-' }}
        </td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('approvals.show', $r->id) }}">
            {{ in_array($r->status, ['submitted', 'in_review']) ? __('Review') : __('Open') }}
          </a>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
<div class="s muted" style="margin-top:12px">
  {{ __('Draft proposals are visible to you but only the proposer can edit them. Approved versions are never deleted; a later approved version supersedes them.') }}
</div>
@endif
@endsection
