@extends('layouts.app')
@section('title', __('Proposals and versions'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Proposals and versions') }}</h1>
    <div class="sub">{{ __('Every version you own, from draft to approved') }}</div>
  </div>
  <div class="act">
    <a class="btn" href="{{ route('proposals.new') }}">{{ __('New proposal') }}</a>
  </div>
</div>

@php
  $tabsList = [
    'all' => 'All',
    'draft' => 'Draft',
    'returned' => 'Returned',
    'submitted' => 'Submitted',
    'in_review' => 'In review',
    'pending_approval' => 'Pending approval',
    'approved' => 'Approved',
  ];
@endphp

<nav class="tabs" aria-label="Status filter" style="margin-bottom:20px">
  @foreach($tabsList as $key => $label)
    <a href="{{ route('proposals', ['f' => $key]) }}" class="{{ ($filter ?? 'all') === $key ? 'on' : '' }}">
      {{ __($label) }}
      @if(isset($counts[$key]))
        <span class="c">{{ $counts[$key] }}</span>
      @endif
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
        <th>{{ __('Version') }}</th>
        <th>{{ __('Category') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Last change') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
      @php
        $statusLabels = [
          'draft' => 'Draft',
          'returned' => 'Returned',
          'submitted' => 'Submitted',
          'in_review' => 'In review',
          'pending_approval' => 'Pending approval',
          'approved' => 'Approved',
        ];
        $isEditable = in_array($r->status, ['draft', 'returned']);
      @endphp
      <tr>
        <td>
          <a href="{{ route('proposals.show', $r->id) }}" style="font-weight:600">
            {{ $r->name_en ?: __('Untitled') }}
          </a>
        </td>
        <td>
          v{{ $r->version_no ?? 1 }}
          @if(($r->proposal_type ?? 'new') !== 'new')
            <span class="s" style="display:block;color:var(--muted);font-size:12px">{{ ucfirst($r->proposal_type) }}</span>
          @endif
        </td>
        <td>
          <div class="row" style="gap:6px;align-items:center">
            <x-cat :c="$r->category" />
            <x-pill :s="$r->category_status === 'proposed' ? __('Proposed') : __('Verified')" />
          </div>
        </td>
        <td>
          <x-pill :s="__($statusLabels[$r->status] ?? ucfirst(str_replace('_', ' ', $r->status)))" />
        </td>
        <td>
          {{ \Carbon\Carbon::parse($r->updated_at)->format('d/m/Y H:i') }}
        </td>
        <td style="text-align:right">
          <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
            <a class="btn ter sm" href="{{ route('proposals.show', $r->id) }}">
              {{ $isEditable ? __('Edit') : __('View') }}
            </a>
            @if($r->status === 'draft')
              <form method="post" action="{{ route('proposals.destroy', $r->id) }}" data-confirm="{{ __('Delete this draft proposal?') }}|{{ __('This draft proposal will be permanently removed. This action cannot be undone.') }}" data-danger data-label="{{ __('Delete') }}" style="display:inline;margin:0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn ter sm" style="color:var(--err);border-color:var(--err)">{{ __('Delete') }}</button>
              </form>
            @endif
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection

