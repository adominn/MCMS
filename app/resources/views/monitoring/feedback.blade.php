@extends('layouts.app')
@section('title', __('Feedback'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Feedback') }}</h1>
    <div class="sub">{{ __('Tell us how each micro-credential went. Your answers are stored without your name on reports. You can give feedback once for each cohort, after you complete it.') }}</div>
  </div>
</div>

<div class="panel">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <h2 style="margin:0">{{ __('Feedback on completed cohorts') }}</h2>
      <div class="s muted" style="margin-top:2px">{{ __('Provide learner feedback for course evaluation and continuous quality improvement (CQI).') }}</div>
    </div>
    <span class="r">
      <span class="s muted">{{ __(':count cohorts', ['count' => $rows->count()]) }}</span>
    </span>
  </div>
  <table id="feedback-cohorts">
    <thead>
      <tr>
        <th style="min-width:240px">{{ __('Micro-credential / Course') }}</th>
        <th>{{ __('Registration') }}</th>
        <th>{{ __('Cohort Ended') }}</th>
        <th>{{ __('Feedback Status') }}</th>
        <th>{{ __('Given On') }}</th>
        <th class="num" style="min-width:140px">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($rows as $e)
      <tr data-href="{{ route('feedback.show', $e->id) }}">
        <td>
          <a href="{{ route('feedback.show', $e->id) }}" style="font-weight:700;color:var(--ink)">
            {{ $e->name_en }} {{ $e->cohort_name }}
          </a>
        </td>
        <td><code>{{ $e->reference }}</code></td>
        <td data-v="{{ $e->end_date }}">{{ !empty($e->end_date) ? \Carbon\Carbon::parse($e->end_date)->format('d/m/Y') : '-' }}</td>
        <td>
          @if($e->given)
            <span class="tag ok">{{ __('Submitted') }}</span>
          @else
            <span class="tag warn">{{ __('Not given yet') }}</span>
          @endif
        </td>
        <td data-v="{{ $e->given?->submitted_at }}">
          @if($e->given?->submitted_at)
            <div>{{ \Carbon\Carbon::parse($e->given->submitted_at)->format('d/m/Y') }}</div>
            <div class="s muted">{{ \Carbon\Carbon::parse($e->given->submitted_at)->diffForHumans() }}</div>
          @else
            <span class="muted">&ndash;</span>
          @endif
        </td>
        <td class="num">
          <a href="{{ route('feedback.show', $e->id) }}" class="btn ter sm">
            <span class="btn-lbl">{{ $e->given ? __('View feedback') : __('Give feedback') }} &rarr;</span>
          </a>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="6" class="muted" style="text-align:center;padding:36px">
          {{ __('No completed micro-credential is ready for feedback yet. Feedback opens after you complete a micro-credential.') }}
        </td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
