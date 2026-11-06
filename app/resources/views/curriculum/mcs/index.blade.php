@extends('layouts.app')
@section('title', 'My micro-credentials')
@section('content')
<div class="title">
  <div>
    <h1>{{ __('My micro-credentials') }}</h1>
    <div class="sub">{{ __('Micro-credentials you own. A draft or revision is edited in Proposals and versions.') }}</div>
  </div>
  <div class="act">
    <a class="btn" href="{{ route('proposals.new') }}">{{ __('New proposal') }}</a>
  </div>
</div>

@if($rows->isEmpty())
  <div class="panel">
    <div class="bd" style="text-align:center;padding:36px;color:var(--muted)">
      {{ __('No micro-credentials yet.') }} <a href="{{ route('proposals.new') }}" style="color:var(--primary);font-weight:500">{{ __('Start a proposal.') }}</a>
    </div>
  </div>
@else
<div class="panel">
  <table>
    <thead>
      <tr>
        <th>{{ __('Micro-credential') }}</th>
        <th>{{ __('Category') }}</th>
        <th>{{ __('Latest version') }}</th>
        <th>{{ __('Status') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <x-badge :category="$r->category" :size="32" />
            <a href="{{ route('mcs.show', $r->id) }}" style="font-weight:600">
              {{ $r->name_en ?: __('Untitled') }}
            </a>
          </div>
        </td>
        <td>
          <x-cat :c="$r->category" />
        </td>
        <td>
          Version {{ $r->version_no }}
        </td>
        <td>
          <x-pill :s="\App\Modules\Approval\ApprovalController::STATUS[$r->status] ?? ucfirst($r->status)" />
        </td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('mcs.show', $r->id) }}">
            {{ __('Open') }}
          </a>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
