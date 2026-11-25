@extends('layouts.app')
@section('title', auth()->user()->hasAnyRole('lecturer') ? __('Invitations & reviews') : __('My review invitations'))
@section('content')
@php
  $isLecturer = auth()->user()->hasAnyRole('lecturer');
  $statusMap = [
    'invited' => 'Invited',
    'in_progress' => 'In progress',
    'submitted' => 'Submitted',
    'declined' => 'Declined',
    'accepted' => 'Accepted',
  ];
  $facInvites = $facilitatorInvites ?? collect();
  $pendingReviews = $rows->whereIn('status', ['invited', 'in_progress'])->count();
  $pendingFac = $facInvites->where('status', 'invited')->count();
@endphp
<div class="title">
  <div>
    <h1>{{ $isLecturer ? __('Invitations & reviews') : __('My review invitations') }}</h1>
    <div class="sub">{{ $isLecturer ? __('Manage peer proposal reviews and cohort facilitator invitations from colleagues.') : __('Proposals you were invited to comment on for industry relevance.') }}</div>
  </div>
</div>

@if($isLecturer)
  <div data-tabs data-default-tab="{{ $activeTab ?? 'reviews' }}">
    <div class="tabs" role="tablist" style="margin-bottom:16px">
      <button type="button" class="tab" data-tab="reviews" role="tab">
        {{ __('Peer reviews') }} ({{ $rows->count() }})@if($pendingReviews > 0) &middot; {{ $pendingReviews }} {{ __('open') }}@endif
      </button>
      <button type="button" class="tab" data-tab="facilitator" role="tab">
        {{ __('Facilitator invitations') }} ({{ $facInvites->count() }})@if($pendingFac > 0) &middot; {{ $pendingFac }} {{ __('pending') }}@endif
      </button>
    </div>

    <div data-pane="reviews" @if(($activeTab ?? 'reviews') !== 'reviews') hidden @endif>
@endif

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

@if($isLecturer)
    </div>

    <div data-pane="facilitator" @if(($activeTab ?? 'reviews') !== 'facilitator') hidden @endif>
      @if($facInvites->isEmpty())
        <div class="panel">
          <div class="bd" style="text-align:center;padding:36px;color:var(--muted)">
            {{ __('You have no cohort facilitator invitations yet.') }}
          </div>
        </div>
      @else
        <div class="panel">
          <table>
            <thead>
              <tr>
                <th>{{ __('Micro-Credential & Cohort') }}</th>
                <th>{{ __('Invited by') }}</th>
                <th>{{ __('Dates') }}</th>
                <th>{{ __('Status') }}</th>
                <th style="text-align:right">{{ __('Action') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($facInvites as $fi)
                <tr>
                  <td>
                    <strong>{{ $fi->name_en }}</strong>
                    <div class="small muted">{{ $fi->cohort_name }} &middot; {{ strtoupper($fi->category) }}@if($fi->ptj_code) &middot; {{ $fi->ptj_code }}@endif</div>
                  </td>
                  <td>{{ $fi->invited_by_name ?: '—' }}</td>
                  <td style="white-space:nowrap">
                    {{ $fi->start_date ? \Carbon\Carbon::parse($fi->start_date)->format('d/m/Y') : '—' }}
                    &ndash;
                    {{ $fi->end_date ? \Carbon\Carbon::parse($fi->end_date)->format('d/m/Y') : '—' }}
                  </td>
                  <td>
                    <x-pill :s="$statusMap[$fi->status] ?? ucfirst($fi->status)" />
                  </td>
                  <td style="text-align:right;white-space:nowrap">
                    @if($fi->status === 'invited')
                      <form method="post" action="{{ route('reviews.facilitator.respond', $fi->id) }}" style="display:inline-flex;gap:6px;align-items:center" data-inplace>
                        @csrf
                        <button type="submit" name="decision" value="accept" class="btn sm">{{ __('Accept') }}</button>
                        <button type="submit" name="decision" value="decline" class="btn ter sm" style="color:#da1e28">{{ __('Decline') }}</button>
                      </form>
                    @elseif($fi->status === 'accepted')
                      <a class="btn ter sm" href="{{ route('offerings.show', $fi->offering_id) }}">{{ __('Open cohort') }}</a>
                    @else
                      <span class="small muted">{{ __('Declined') }}</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endif
@endsection
