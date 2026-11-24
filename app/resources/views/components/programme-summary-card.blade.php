@props([
  'pathway',
  'earnedCount' => 0,
  'enrolledCount' => 0,
  'totalCount' => 0,
  'earnedCredits' => 0,
  'totalMappedCredits' => 0,
  'isSelected' => false,
  'detailUrl' => '#',
  'showCompare' => true,
  'showSelect' => true,
])
@php
  $pct = $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0;
  $mappedLabel = $totalCount === 1
    ? __('1 micro-credential mapped')
    : __(':count micro-credentials mapped', ['count' => $totalCount]);
@endphp

<div class="panel" style="display:flex;flex-direction:column;justify-content:space-between;{{ $isSelected ? 'border:2px solid var(--color-primary, #0052ff);' : '' }}">
  <div class="hd" style="{{ $isSelected ? 'background:var(--color-primary-tint, #edf4ff);' : '' }}">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px">
      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        <span class="tag info" style="font-family:var(--font-mono)"><strong>{{ $pathway->programme_code }}</strong></span>
        @if(!empty($pathway->faculty_code))
          <span class="tag neutral">{{ $pathway->faculty_code }}</span>
        @endif
        <span class="tag neutral">MQF {{ $pathway->mqf_level }}</span>
        <span class="s muted" style="font-variant-numeric:tabular-nums">{{ mcms_num($pathway->total_credits) }} cr</span>
      </div>
      @if($isSelected)
        <span class="tag ok"><strong>{{ __('Selected') }}</strong></span>
      @endif
    </div>
    <h2 style="margin:0;font-size:15px;line-height:1.35">
      <a href="{{ $detailUrl }}" style="color:var(--color-ink, #090a0f);text-decoration:none">{{ $pathway->programme_name }}</a>
    </h2>
    @if(!empty($pathway->faculty_name))
      <div class="s muted" style="margin-top:2px;font-size:12px">{{ $pathway->faculty_name }}</div>
    @endif
  </div>

  <div class="bd" style="padding:14px 18px;display:flex;flex-direction:column;gap:12px;flex:1;justify-content:space-between">
    <!-- Match Summary Strip (No embedded table!) -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:12.5px;margin-bottom:6px">
        <span>
          <strong>{{ $earnedCount }} {{ __('of') }} {{ $totalCount }}</strong> {{ __('earned') }}
          <span class="muted">({{ mcms_num($earnedCredits) }} / {{ mcms_num($totalMappedCredits) }} cr)</span>
        </span>
        <span class="s muted">{{ $mappedLabel }}</span>
      </div>
      <div style="height:6px;width:100%;background:var(--color-surface-2, #e2e8f0);overflow:hidden;border-radius:2px">
        @if($pct > 0)
          <div style="height:100%;width:{{ min(100, $pct) }}%;background:var(--color-success, #24a148)"></div>
        @endif
      </div>
      @if($enrolledCount > 0)
        <div class="s muted" style="margin-top:4px;font-size:12px">
          {{ $enrolledCount === 1 ? __('1 currently enrolled') : __(':count currently enrolled', ['count' => $enrolledCount]) }}
        </div>
      @endif
    </div>

    <!-- Card Footer Actions -->
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;padding-top:10px;border-top:1px solid var(--color-line-hairline, #e2e8f0)">
      <div style="display:inline-flex;gap:6px;flex-wrap:wrap;align-items:center">
        <a class="btn ter sm" href="{{ $detailUrl }}">{{ __('View Details') }}</a>
        @if($showSelect)
          @if($isSelected)
            <a class="btn sec sm" href="{{ route('plan.mine') }}">{{ __('View in My Plan') }}</a>
          @else
            <form method="post" action="{{ route('plan.selfStore') }}" style="display:inline">
              @csrf
              <input type="hidden" name="pathway_id" value="{{ $pathway->id }}">
              <button type="submit" class="btn primary sm">{{ __('Select Pathway') }}</button>
            </form>
          @endif
        @endif
      </div>

      @if($showCompare)
        <label style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--color-ink-muted, #475569);cursor:pointer;user-select:none">
          <input type="checkbox" class="pw-compare-check" value="{{ $pathway->programme_code }}" data-name="{{ $pathway->programme_code }} {{ $pathway->programme_name }}" onchange="updateCompareTray()">
          <span>{{ __('Compare') }}</span>
        </label>
      @endif
    </div>
  </div>
</div>
