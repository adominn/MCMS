@extends('layouts.app')
@section('title', 'Quality Verification')
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Quality Verification') }}</h1>
    <div class="sub">{{ __('Quality verification applies only to standalone micro-credentials (SAMC). Credit and MQF level appear only after QV is recorded.') }}</div>
  </div>
</div>

<div class="note info" style="margin-bottom:20px">
  <p style="margin:0 0 6px">
    <b>{{ __("Rules:") }}</b> Only a SAMC with a confirmed category appears here. Credit, MQF level and the designation "Quality-Verified" are shown only while a verified record is in force. When it expires, the credit claim stops; credentials already earned remain valid.
  </p>
  <p class="s muted" style="margin:0">
    Alerts are dispatched {{ implode(', ', $alertDays) }} days before the expiration date. Maximum validity is {{ $maxYears }} years.
  </p>
</div>

<div class="panel">
  <table id="qv-list">
    <thead>
      <tr>
        <th>{{ __('Micro-credential') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Valid until') }}</th>
        <th>{{ __('Alert') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($rows as $m)
      <tr>
        <td>
          <a href="{{ route('quality.show', $m->id) }}" style="font-weight:600">
            {{ $m->name_en }}
          </a>
        </td>
        <td>
          @if($m->standing)
            <span class="tag ok">{{ __('Quality-Verified') }}</span>
          @elseif($m->open)
            <span class="tag info">{{ __(config('qv.status')[$m->open->status] ?? $m->open->status) }}</span>
          @elseif($m->last)
            <span class="tag warn">{{ __(config('qv.status')[$m->last->status] ?? $m->last->status) }}</span>
          @else
            <span class="tag n">{{ __('Not applied') }}</span>
          @endif
        </td>
        <td>
          {{ $m->standing ? \Carbon\Carbon::parse($m->standing->valid_until)->format('d/m/Y').' ('.$m->days.' '.__('days').')' : '' }}
        </td>
        <td>
          @if($m->alert !== null)
            <span class="tag overdue">{{ __(':days days left', ['days' => $m->days]) }}</span>
          @endif
        </td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('quality.show', $m->id) }}">
            {{ __('Open') }}
          </a>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="5" class="muted" style="text-align:center;padding:36px">
          {{ __('No confirmed SAMC yet.') }}
        </td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
