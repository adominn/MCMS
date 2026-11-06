@extends('layouts.app')
@section('title', 'Reviews')
@section('content')
@php
  $isLecturer = auth()->user()->hasAnyRole('lecturer');
  $statusMap = [
    'invited' => 'Invited',
    'in_progress' => 'In progress',
    'submitted' => 'Submitted',
    'declined' => 'Declined',
  ];
@endphp
<div class="title">
  <div>
    <h1>{{ $isLecturer ? __('Reviews for colleagues') : __('My review invitations') }}</h1>
    <div class="sub">{{ $isLecturer ? __('You review as a peer when the deputy dean invites you. Declare any conflict first.') : __('Proposals you were invited to comment on for industry relevance.') }}</div>
  </div>
</div>

@if($rows->isEmpty())
  <div class="panel">
    <div class="bd" style="text-align:center;padding:36px;color:var(--muted)">
      {{ __('You have no review invitations.') }}
    </div>
  </div>
@else
<div class="panel">
  <table>
    <thead>
      <tr>
        <th>{{ __('Proposal') }}</th>
        <th>{{ __('Your part') }}</th>
        <th>{{ __('Due date') }}</th>
        <th>{{ __('Status') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
      <tr>
        <td>
          <a href="{{ route('reviews.show', $r->id) }}" style="font-weight:600">
            {{ $r->name_en }}
          </a>
        </td>
        <td>
          {{ $r->kind === 'expert' ? __('Comment on relevance') : __('Academic review') }}
        </td>
        <td>
          {{ \Carbon\Carbon::parse($r->due_on)->format('d/m/Y') }}
        </td>
        <td>
          <x-pill :s="$statusMap[$r->status] ?? ucfirst($r->status)" />
        </td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('reviews.show', $r->id) }}">
            {{ $r->status === 'submitted' ? __('View') : __('Open') }}
          </a>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
