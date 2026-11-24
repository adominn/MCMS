@props([
  'stage' => 1, // 1..8
  'blocked' => false,
  'blockedLabel' => null,
  'note' => null,
])
@php
  $isMs = app()->getLocale() === 'ms';
  $steps = [
    1 => ['num' => '01', 'role' => 'P8', 'en' => 'Learning & Results', 'ms' => 'Pembelajaran & Keputusan'],
    2 => ['num' => '02', 'role' => 'P1', 'en' => 'Coordinator Sign-off', 'ms' => 'Pengesahan Penyelaras'],
    3 => ['num' => '03', 'role' => 'P2', 'en' => 'Completion Confirmed', 'ms' => 'Pengesahan Tamat'],
    4 => ['num' => '04', 'role' => 'P6', 'en' => 'ACR Confirmed', 'ms' => 'Pengesahan ACR'],
    5 => ['num' => '05', 'role' => 'P8', 'en' => 'Learner Consent', 'ms' => 'Persetujuan Peserta'],
    6 => ['num' => '06', 'role' => 'P7', 'en' => 'ABC Deposit', 'ms' => 'Deposit ABC'],
    7 => ['num' => '07', 'role' => 'P3', 'en' => 'Transfer Evaluation', 'ms' => 'Penilaian Pindah Kredit'],
    8 => ['num' => '08', 'role' => 'P3/P7', 'en' => 'Single Redemption', 'ms' => 'Penebusan Tunggal'],
  ];
  $current = max(1, min(8, (int) $stage));
@endphp
<nav class="lifecycle-tracker" aria-label="{{ __('Credit lifecycle stages') }}">
  <div class="lifecycle-tracker-head">
    <span><b>{{ __('Micro-Credential Credit Lifecycle') }}</b></span>
    <span class="lifecycle-tracker-meta">{{ __('Stage') }} {{ $steps[$current]['num'] }} / 08{{ $note ? ' · ' . $note : '' }}</span>
  </div>
  <div class="lifecycle-stepper-wrap">
    <ol class="lifecycle-steps" style="--stepper-cols: 8;">
      @foreach($steps as $idx => $s)
        @php
          $isDone = $idx < $current;
          $isCurrent = $idx === $current;
          $isBlocked = $isCurrent && $blocked;
          $cls = $isBlocked ? 'is-blocked' : ($isCurrent ? 'active is-current' : ($isDone ? 'completed is-done' : 'pending is-upcoming'));
          $stateText = $isBlocked
            ? ($blockedLabel ?: __('Action paused / Blocked'))
            : ($isCurrent ? __('Current stage') : ($isDone ? __('Completed') : __('Upcoming')));
        @endphp
        <li class="lifecycle-step {{ $cls }}" @if($isCurrent) aria-current="step" @endif>
          <div class="step-node">
            @if($isBlocked)
              !
            @elseif($isDone)
              &#10003;
            @else
              {{ $s['num'] }}
            @endif
          </div>
          <div class="ls-lbl">{{ $isMs ? $s['ms'] : $s['en'] }}</div>
          <div class="ls-state">{{ $stateText }}</div>
        </li>
      @endforeach
    </ol>
  </div>
</nav>
