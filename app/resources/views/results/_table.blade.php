@php
  // Group components by module / scope for clean hierarchical display
  $groupedComps = $comps->groupBy(function($c) {
    if (!empty($c->module_seq)) {
      return 'mod_' . $c->module_seq;
    }
    return 'whole_mc';
  });
@endphp
<style>
  .gradebook-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
  }
  .gradebook-table th, .gradebook-table td {
    border-bottom: 1px solid var(--hairline, #393939);
    padding: 10px 14px;
    vertical-align: middle;
  }
  .gradebook-table thead tr.group-row th {
    background: var(--surface, #262626);
    border-bottom: 2px solid var(--hairline, #393939);
    padding: 8px 12px;
  }
  .gradebook-table thead tr.sub-row th {
    background: var(--canvas, #090a0f);
    border-bottom: 1px solid var(--hairline, #393939);
    font-weight: 500;
  }
  .gradebook-table tbody tr:hover td {
    background: var(--table-hover, #2e2e2e);
  }
  .gradebook-col-mod {
    border-left: 1px solid var(--hairline, #393939);
  }
  .gradebook-score-box {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
  }
  .gradebook-score-val {
    font-size: 14px;
    font-weight: 600;
    min-width: 28px;
    text-align: right;
  }
</style>

<div class="table-wrap" style="overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--hairline, #393939);background:var(--table-bg, #1e1e1e)">
<table class="gradebook-table">
  <thead>
    <tr class="group-row">
      <th rowspan="2" style="position:sticky;left:0;z-index:3;background:var(--surface, #262626);min-width:200px;border-right:1px solid var(--hairline, #393939)">
        {{ __('Participant') }}
      </th>
      <th rowspan="2" style="text-align:center;min-width:75px;border-right:1px solid var(--hairline, #393939)">
        {{ __('Progress') }}
      </th>
      @foreach($groupedComps as $groupKey => $groupItems)
        @php
          $first = $groupItems->first();
          $isMod = !empty($first->module_seq);
        @endphp
        <th colspan="{{ $groupItems->count() }}" class="gradebook-col-mod" style="text-align:left;border-right:1px solid var(--hairline, #393939);min-width:{{ max(180, $groupItems->count() * 160) }}px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;white-space:nowrap">
            <span style="font-weight:600;color:var(--ink, #f1f5f9);overflow:hidden;text-overflow:ellipsis">
              @if($isMod)
                {{ __('Module :seq: :name', ['seq' => $first->module_seq, 'name' => $first->module_name ?: __('Module')]) }}
              @else
                {{ __('Whole Micro-Credential / Capstone') }}
              @endif
            </span>
            @if($isMod && !empty($first->module_slt))
              <span class="tag info" style="font-size:11px;padding:2px 8px;white-space:nowrap;display:inline-block;flex-shrink:0;letter-spacing:0.3px">{{ $first->module_slt }}h SLT</span>
            @endif
          </div>
        </th>
      @endforeach
      <th rowspan="2" class="num" style="text-align:right;min-width:70px;border-left:1px solid var(--hairline, #393939)">{{ __('Final') }}</th>
      <th rowspan="2" style="text-align:center;min-width:110px">{{ __('Result') }}</th>
      @if($flag ?? false)
        <th rowspan="2" style="min-width:140px;border-left:1px solid var(--hairline, #393939)">{{ __('Integrity') }}</th>
      @endif
    </tr>
    <tr class="sub-row">
      @foreach($groupedComps as $groupKey => $groupItems)
        @foreach($groupItems as $c)
          <th class="gradebook-col-mod" style="min-width:160px;vertical-align:top">
            <div style="color:var(--ink, #f1f5f9);font-size:13px;line-height:1.3">{{ $c->name_en }}</div>
            <div class="muted s" style="margin-top:4px;display:flex;align-items:center;gap:6px">
              <span class="tag" style="font-size:10px;padding:0 4px">{{ mcms_num($c->weight_percent) }}%</span>
              <span>{{ __('Pass') }}: {{ mcms_num($c->pass_mark) }}/{{ mcms_num($c->max_mark) }}</span>
            </div>
          </th>
        @endforeach
      @endforeach
    </tr>
  </thead>
  <tbody>
  @forelse($enrol as $e)
    @php
      $weightedSum = 0;
      $hasMissing = false;
      $allPassed = true;
      $isHeld = in_array($e->id, $openCases);
      foreach($comps as $c) {
        $r = $results[$e->id][$c->id] ?? null;
        if ($r === null || $r->score === null || $r->score === '') {
          $hasMissing = true;
        } else {
          $sc = (float) $r->score;
          $max = (float) ($c->max_mark ?: 100);
          $weight = (float) ($c->weight_percent ?: 0);
          $weightedSum += ($max > 0) ? ($sc / $max) * $weight : 0;
          if ($sc < (float) $c->pass_mark) {
            $allPassed = false;
          }
        }
      }
      if ($weightedSum < 50) {
        $allPassed = false;
      }
      $roundedFinal = round($weightedSum);
    @endphp
    <tr>
      <td style="position:sticky;left:0;z-index:2;background:var(--table-bg, #1e1e1e);border-right:1px solid var(--hairline, #393939)">
        <div style="font-weight:600;color:var(--ink, #f1f5f9)">{{ $e->name }}</div>
        <div class="muted s" style="margin-top:2px">{{ $e->institution_id }}</div>
        @if($isHeld)
          <div style="margin-top:4px"><span class="tag overdue">{{ __('Integrity case open') }}</span></div>
        @endif
      </td>
      <td style="text-align:center;border-right:1px solid var(--hairline, #393939);font-weight:500">
        {{ $e->progress_percent !== null ? $e->progress_percent.'%' : '-' }}
      </td>
      @foreach($groupedComps as $groupKey => $groupItems)
        @foreach($groupItems as $c)
          @php($r = $results[$e->id][$c->id] ?? null)
          <td class="gradebook-col-mod">
            @if($editable ?? false)
              @if($r && $r->status === 'verified')
                <div class="gradebook-score-box">
                  <span class="gradebook-score-val">{{ mcms_num($r->score) }}</span>
                  <span class="tag ok">{{ __('Verified') }}</span>
                </div>
              @else
                @php($hasScore = isset($r->score) && $r->score !== null && $r->score !== '')
                <div class="gradebook-score-box">
                  <input type="text"
                         inputmode="decimal"
                         class="input mark-input @if(!$hasScore) bad @endif"
                         name="score[{{ $e->id }}][{{ $c->id }}]"
                         value="{{ $hasScore ? mcms_num($r->score) : '' }}"
                         placeholder="0-{{ mcms_num($c->max_mark) }}"
                         data-eid="{{ $e->id }}"
                         data-cid="{{ $c->id }}"
                         data-weight="{{ $c->weight_percent }}"
                         data-max="{{ $c->max_mark }}"
                         data-pass="{{ $c->pass_mark }}"
                         data-key="{{ $keys[$c->id] }}"
                         style="width:68px;height:32px;text-align:right;@if(!$hasScore) border:1px dashed var(--warn-dark, #b28600);background:var(--warn-bg, #fefdf0); @endif"
                         aria-label="{{ $keys[$c->id] }} for {{ $e->name }}">
                  @if($r)
                    <span class="tag {{ $r->status === 'held' ? 'err' : 'warn' }}">{{ $r->status === 'held' ? __('Held') : __('Provisional') }}</span>
                  @endif
                </div>
              @endif
            @else
              @if($r && $r->score !== null)
                <div class="gradebook-score-box">
                  <span class="gradebook-score-val">{{ mcms_num($r->score) }}</span>
                  <span class="tag {{ $r->status === 'verified' ? 'ok' : ($r->status === 'held' || $r->status === 'on_hold' ? 'err' : 'warn') }}">
                    {{ __(['provisional' => 'Provisional', 'held' => 'Held', 'on_hold' => 'Held', 'verified' => 'Verified'][$r->status] ?? ucfirst(str_replace('_', ' ', $r->status))) }}
                  </span>
                  @if($r->status === 'verified' && ($changeable ?? false))
                    <button type="button" class="btn ter sm" style="padding:1px 6px;font-size:11px;height:22px;line-height:18px" onclick="var el=document.getElementById('change-form-{{ $r->id }}'); el.style.display = (el.style.display==='none'?'block':'none')">
                      {{ __('Change') }}
                    </button>
                  @endif
                </div>
                @if($r->status === 'verified' && ($changeable ?? false))
                  <div id="change-form-{{ $r->id }}" style="display:none;margin-top:8px;padding:8px;background:var(--surface, #262626);border:1px solid var(--hairline, #393939)">
                    <form method="post" action="{{ route('results.change', $r->id) }}" style="display:flex;flex-direction:column;gap:6px">@csrf
                      <div style="display:flex;gap:6px">
                        <input type="text" inputmode="decimal" name="new_score" placeholder="{{ __('New mark') }}" style="max-width:70px;height:28px" required aria-label="New mark for {{ $keys[$c->id] }} of {{ $e->name }}">
                        <input type="text" name="reason" placeholder="{{ __('Reason') }}" required style="flex:1;height:28px" aria-label="Reason to change {{ $keys[$c->id] }} of {{ $e->name }}">
                      </div>
                      <div style="display:flex;gap:6px;justify-content:flex-end">
                        <button type="button" class="btn ter sm" style="padding:1px 6px;font-size:11px;height:22px" onclick="document.getElementById('change-form-{{ $r->id }}').style.display='none'">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn sm" style="padding:1px 6px;font-size:11px;height:22px">{{ __('Submit') }}</button>
                      </div>
                    </form>
                  </div>
                @endif
              @else
                <span class="muted" style="font-size:12px">{{ __('No mark') }}</span>
              @endif
            @endif
          </td>
        @endforeach
      @endforeach
      <td class="num" id="fin-{{ $e->id }}" style="border-left:1px solid var(--hairline, #393939);font-weight:700;font-size:14px">
        {{ $hasMissing ? '-' : $roundedFinal }}
      </td>
      <td id="rs-{{ $e->id }}" style="text-align:center">
        @if($isHeld)
          <span class="tag overdue" style="white-space:nowrap">{{ __('Held (integrity)') }}</span>
        @elseif($hasMissing)
          <span class="tag warn">{{ __('Missing') }}</span>
        @elseif($allPassed)
          <span class="tag ok">{{ __('Pass') }}</span>
        @else
          <span class="tag err">{{ __('Fail') }}</span>
        @endif
      </td>
      @if($flag ?? false)
        <td style="border-left:1px solid var(--hairline, #393939)">
          <form method="post" action="{{ route('results.flag', $o->id) }}" style="display:flex;gap:4px;align-items:center">@csrf
            <input type="hidden" name="enrolment_id" value="{{ $e->id }}">
            <input type="text" name="reason" placeholder="{{ __('Reason') }}" aria-label="Reason to flag {{ $e->name }}" style="max-width:100px;height:28px;font-size:12px">
            <button type="submit" class="btn danger sm" style="height:28px;padding:2px 8px;font-size:11px">{{ __('Flag') }}</button>
          </form>
        </td>
      @endif
    </tr>
  @empty
    <tr><td colspan="{{ 4 + $comps->count() + (($flag ?? false) ? 1 : 0) }}" class="muted" style="text-align:center;padding:24px">{{ __('No enrolled participants.') }}</td></tr>
  @endforelse
  </tbody>
</table>
</div>
