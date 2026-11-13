<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>{{ __('Participant') }}</th>
      <th>{{ __('Progress') }}</th>
      @foreach($comps as $c)
        <th>
          {{ $c->name_en }} ({{ rtrim(rtrim((string) $c->weight_percent, '0'), '.') }}%)<br>
          <span class="muted s">{{ __('Pass') }}: {{ rtrim(rtrim((string) $c->pass_mark, '0'), '.') }}/{{ rtrim(rtrim((string) $c->max_mark, '0'), '.') }}</span>
        </th>
      @endforeach
      <th class="num">{{ __('Final') }}</th>
      <th>{{ __('Result') }}</th>
      @if($flag ?? false)
        <th>{{ __('Integrity') }}</th>
      @endif
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
      <td>
        <b>{{ $e->name }}</b><br>
        <span class="muted s">{{ $e->institution_id }}</span>
        @if($isHeld)
          <br><span class="tag overdue">{{ __('Integrity case open') }}</span>
        @endif
      </td>
      <td>{{ $e->progress_percent !== null ? $e->progress_percent.'%' : '-' }}</td>
      @foreach($comps as $c)
        @php($r = $results[$e->id][$c->id] ?? null)
        <td>
          @if($editable ?? false)
            @if($r && $r->status === 'verified')
              <div style="display:inline-flex;align-items:center;gap:6px">
                <b>{{ rtrim(rtrim((string) $r->score, '0'), '.') }}</b>
                <span class="tag ok">{{ __('Verified') }}</span>
              </div>
            @else
              @php($hasScore = isset($r->score) && $r->score !== null && $r->score !== '')
              <div style="display:inline-flex;align-items:center;gap:6px">
                <input type="text"
                       inputmode="decimal"
                       class="input mark-input @if(!$hasScore) bad @endif"
                       name="score[{{ $e->id }}][{{ $c->id }}]"
                       value="{{ $hasScore ? rtrim(rtrim((string) $r->score, '0'), '.') : '' }}"
                       placeholder="0-{{ rtrim(rtrim((string) $c->max_mark, '0'), '.') }}"
                       data-eid="{{ $e->id }}"
                       data-cid="{{ $c->id }}"
                       data-weight="{{ $c->weight_percent }}"
                       data-max="{{ $c->max_mark }}"
                       data-pass="{{ $c->pass_mark }}"
                       data-key="{{ $keys[$c->id] }}"
                       style="max-width:80px;height:32px;text-align:right;@if(!$hasScore) border:1px dashed var(--warn-dark, #b28600);background:var(--warn-bg, #fcf4d6); @endif"
                       aria-label="{{ $keys[$c->id] }} for {{ $e->name }}">
                @if($r)
                  <span class="tag {{ $r->status === 'held' ? 'err' : 'warn' }}">{{ $r->status === 'held' ? __('Held') : __('Provisional') }}</span>
                @endif
              </div>
            @endif
          @else
            @if($r && $r->score !== null)
              <div style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap">
                <b>{{ rtrim(rtrim((string) $r->score, '0'), '.') }}</b>
                <span class="tag {{ $r->status === 'verified' ? 'ok' : ($r->status === 'held' || $r->status === 'on_hold' ? 'err' : 'warn') }}">
                  {{ __(['provisional' => 'Provisional', 'held' => 'Held', 'on_hold' => 'Held', 'verified' => 'Verified'][$r->status] ?? ucfirst(str_replace('_', ' ', $r->status))) }}
                </span>
                @if($r->status === 'verified' && ($changeable ?? false))
                  <button type="button" class="btn ter sm" style="padding:2px 8px;font-size:11px;height:24px;line-height:20px" onclick="var el=document.getElementById('change-form-{{ $r->id }}'); el.style.display = (el.style.display==='none'?'block':'none')">
                    {{ __('Request change') }}
                  </button>
                @endif
              </div>
              @if($r->status === 'verified' && ($changeable ?? false))
                <div id="change-form-{{ $r->id }}" style="display:none;margin-top:8px;padding:10px;background:var(--subtle, #f4f4f4);border:1px solid var(--hairline, #e0e0e0)">
                  <form method="post" action="{{ route('results.change', $r->id) }}" style="display:flex;flex-direction:column;gap:6px">@csrf
                    <div style="display:flex;gap:6px">
                      <input type="text" inputmode="decimal" name="new_score" placeholder="{{ __('New mark') }}" style="max-width:80px;height:32px" required aria-label="New mark for {{ $keys[$c->id] }} of {{ $e->name }}">
                      <input type="text" name="reason" placeholder="{{ __('Reason (e.g. re-marking appeal)') }}" required style="flex:1;height:32px" aria-label="Reason to change {{ $keys[$c->id] }} of {{ $e->name }}">
                    </div>
                    <div style="display:flex;gap:6px;justify-content:flex-end">
                      <button type="button" class="btn ter sm" style="padding:2px 8px;font-size:11px;height:24px" onclick="document.getElementById('change-form-{{ $r->id }}').style.display='none'">{{ __('Cancel') }}</button>
                      <button type="submit" class="btn sm" style="padding:2px 8px;font-size:11px;height:24px">{{ __('Submit change request') }}</button>
                    </div>
                  </form>
                </div>
              @endif
            @else
              <span class="muted">{{ __('No mark') }}</span>
            @endif
          @endif
        </td>
      @endforeach
      <td class="num" id="fin-{{ $e->id }}">
        <b>{{ $hasMissing ? '-' : $roundedFinal }}</b>
      </td>
      <td id="rs-{{ $e->id }}">
        @if($isHeld)
          <span class="tag overdue">{{ __('Held (integrity)') }}</span>
        @elseif($hasMissing)
          <span class="tag warn">{{ __('Missing') }}</span>
        @elseif($allPassed)
          <span class="tag ok">{{ __('Pass') }}</span>
        @else
          <span class="tag err">{{ __('Fail') }}</span>
        @endif
      </td>
      @if($flag ?? false)
        <td>
          <form method="post" action="{{ route('results.flag', $o->id) }}" style="display:flex;gap:6px;align-items:center">@csrf
            <input type="hidden" name="enrolment_id" value="{{ $e->id }}">
            <input type="text" name="reason" placeholder="{{ __('Reason') }}" aria-label="Reason to flag {{ $e->name }}" style="max-width:140px;height:32px">
            <button type="submit" class="btn danger sm" style="height:32px">{{ __('Flag') }}</button>
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
