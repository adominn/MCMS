@props([
  'steps' => [], // array of array{label: string, meta?: string, status?: string, num?: string}
  'current' => 1,
])
@php
  $count = count($steps);
@endphp
<div class="linear-stepper" style="--stepper-cols: {{ max(1, $count) }};">
  @foreach($steps as $idx => $s)
    @php
      $stepNum = $idx + 1;
      $status = $s['status'] ?? ($stepNum < $current ? 'completed' : ($stepNum === (int)$current ? 'active' : 'pending'));
      $num = $s['num'] ?? str_pad((string)$stepNum, 2, '0', STR_PAD_LEFT);
    @endphp
    <div class="stepper-step {{ $status }}">
      <div class="step-node">
        @if($status === 'completed')
          &#10003;
        @else
          {{ $num }}
        @endif
      </div>
      <div class="step-label">{{ $s['label'] }}</div>
      @if(!empty($s['meta']))
        <div class="step-meta">{{ $s['meta'] }}</div>
      @endif
    </div>
  @endforeach
</div>
