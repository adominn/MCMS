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
  .gradebook-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--hairline, #393939);
    background: var(--table-bg, #1e1e1e);
  }
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
  .results-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 16px;
    background: var(--surface, #1e2433);
    border: 1px solid var(--hairline, #393939);
    border-bottom: none;
  }
  .results-sort-btn {
    all: unset;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    width: 100%;
    min-height: 38px;
    font: inherit;
    color: inherit;
    padding: 2px 4px;
    border-radius: 4px;
    transition: background 0.15s ease, color 0.15s ease;
  }
  .results-sort-btn:hover {
    color: var(--primary, #0052ff) !important;
  }
  .results-sort-btn:focus-visible {
    outline: 2px solid var(--primary, #0052ff);
    outline-offset: -2px;
  }
  .results-sort-btn .sort-icon {
    display: inline-flex;
    align-items: center;
    color: var(--muted, #8d8d8d);
    flex-shrink: 0;
  }
  th.sorted .results-sort-btn .sort-icon {
    color: var(--primary, #0052ff);
  }
  .results-filter-btn {
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
  }
  .results-filter-btn.on {
    background: var(--primary, #0052ff) !important;
    color: #ffffff !important;
    border-color: var(--primary, #0052ff) !important;
  }
</style>

{{-- Search & Filter Toolbar --}}
<div class="results-toolbar">
  <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:240px;max-width:440px">
    <div style="position:relative;flex:1;display:flex;align-items:center">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="position:absolute;left:10px;color:var(--muted);pointer-events:none" aria-hidden="true">
        <circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5L14 14"/>
      </svg>
      <input type="search"
             id="results-search-input"
             placeholder="{{ __('Search participant name, matrix ID...') }}"
             aria-label="{{ __('Search participants in results') }}"
             autocomplete="off"
             style="width:100%;height:34px;padding:0 30px 0 32px;font-size:13px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg, #ffffff);color:var(--ink)">
      <button type="button"
              id="results-search-clear"
              title="{{ __('Clear search') }}"
              style="position:absolute;right:8px;background:none;border:none;cursor:pointer;color:var(--muted);display:none;padding:2px"
              aria-label="{{ __('Clear search') }}">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
      </button>
    </div>

    {{-- Shared Inline Sort Control (D-73) --}}
    <div class="tbar-sort" id="results-sort-wrap">
      <button type="button" class="tbar-sort-btn" id="results-sort-btn" aria-haspopup="true" aria-expanded="false">
        <span class="tbar-sort-lbl">{{ __('Sort: Default') }}</span>
        <span class="tbar-sort-caret" aria-hidden="true">&#9662;</span>
      </button>
      <div class="tbar-sort-menu" id="results-sort-menu" hidden role="dialog" aria-label="{{ __('Sort options') }}">
        <div class="tbar-sort-sec">{{ __('Sort by') }}</div>
        <div class="tbar-sort-list">
          <label class="tbar-sort-opt"><input type="radio" name="results_sort_field" value="name" data-type="text" data-label="{{ __('Participant') }}"><span>{{ __('Participant') }}</span></label>
          <label class="tbar-sort-opt"><input type="radio" name="results_sort_field" value="progress" data-type="num" data-label="{{ __('Progress') }}"><span>{{ __('Progress') }}</span></label>
          <label class="tbar-sort-opt"><input type="radio" name="results_sort_field" value="final" data-type="num" data-label="{{ __('Final') }}"><span>{{ __('Final') }}</span></label>
          <label class="tbar-sort-opt"><input type="radio" name="results_sort_field" value="result" data-type="status" data-label="{{ __('Result') }}"><span>{{ __('Result') }}</span></label>
          @foreach($comps as $c)
            <label class="tbar-sort-opt"><input type="radio" name="results_sort_field" value="comp:{{ $c->id }}" data-type="num" data-label="{{ $c->name_en }}"><span>{{ $c->name_en }}</span></label>
          @endforeach
        </div>
        <div class="tbar-sort-sec" style="margin-top:10px">{{ __('Direction') }}</div>
        <div class="tbar-sort-dir" id="results-sort-dir" role="group" aria-label="{{ __('Sort direction') }}">
          <button type="button" class="tbar-dir-btn on" data-dir="asc">{{ __('A to Z') }}</button>
          <button type="button" class="tbar-dir-btn" data-dir="desc">{{ __('Z to A') }}</button>
        </div>
        <div class="tbar-sort-foot" id="results-sort-foot" hidden>
          <button type="button" class="tbar-sort-reset" id="results-sort-reset">{{ __('Reset to default') }}</button>
        </div>
      </div>
      <span class="sr" aria-live="polite" id="results-sort-live"></span>
    </div>
  </div>

  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div class="filter-group" style="display:inline-flex;align-items:center;gap:6px">
      <span class="small muted" style="font-size:12px;font-weight:500">{{ __('Filter:') }}</span>
      <div class="btn-group" role="group" aria-label="{{ __('Filter by result status') }}" style="display:inline-flex;border:1px solid var(--border);border-radius:6px;overflow:hidden">
        <button type="button" class="btn sm results-filter-btn on" data-filter="all" style="padding:4px 10px;font-size:12px;height:28px;border-radius:0;border:none">{{ __('All') }}</button>
        <button type="button" class="btn sm ter results-filter-btn" data-filter="missing" style="padding:4px 10px;font-size:12px;height:28px;border-radius:0;border:none;border-left:1px solid var(--border)">{{ __('Missing') }}</button>
        <button type="button" class="btn sm ter results-filter-btn" data-filter="pass" style="padding:4px 10px;font-size:12px;height:28px;border-radius:0;border:none;border-left:1px solid var(--border)">{{ __('Pass') }}</button>
        <button type="button" class="btn sm ter results-filter-btn" data-filter="fail" style="padding:4px 10px;font-size:12px;height:28px;border-radius:0;border:none;border-left:1px solid var(--border)">{{ __('Fail') }}</button>
        <button type="button" class="btn sm ter results-filter-btn" data-filter="held" style="padding:4px 10px;font-size:12px;height:28px;border-radius:0;border:none;border-left:1px solid var(--border)">{{ __('Held') }}</button>
      </div>
    </div>
    <span id="results-count-badge" class="tag" style="font-size:12px;padding:3px 8px;font-weight:500">
      {{ __(':count participants', ['count' => $enrol->count()]) }}
    </span>
  </div>
</div>

<div class="gradebook-table-wrap table-wrap">
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
      <th rowspan="2" class="num" style="text-align:right;min-width:75px;border-left:1px solid var(--hairline, #393939)">
        {{ __('Final') }}
      </th>
      <th rowspan="2" style="text-align:center;min-width:110px">
        {{ __('Result') }}
      </th>
      @if($flag ?? false)
        <th rowspan="2" style="min-width:140px;border-left:1px solid var(--hairline, #393939)">{{ __('Integrity') }}</th>
      @endif
    </tr>
    <tr class="sub-row">
      @foreach($groupedComps as $groupKey => $groupItems)
        @foreach($groupItems as $c)
          <th class="gradebook-col-mod" style="min-width:160px;vertical-align:top;padding:6px 10px">
            <div style="color:var(--ink, #f1f5f9);font-size:13px;line-height:1.3;font-weight:600">{{ $c->name_en }}</div>
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
      $resultStatus = $isHeld ? 'held' : ($hasMissing ? 'missing' : ($allPassed ? 'pass' : 'fail'));
    @endphp
    <tr class="gradebook-row"
        data-eid="{{ $e->id }}"
        data-name="{{ strtolower($e->name) }}"
        data-id="{{ strtolower($e->institution_id) }}"
        data-progress="{{ (float) ($e->progress_percent ?? -1) }}"
        data-final="{{ $hasMissing ? -1 : $roundedFinal }}"
        data-result="{{ $resultStatus }}">
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
          <td class="gradebook-col-mod" data-cid="{{ $c->id }}">
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
    <tr id="results-empty-row" style="display:none">
      <td colspan="{{ 4 + $comps->count() + (($flag ?? false) ? 1 : 0) }}" class="muted" style="text-align:center;padding:32px 16px">
        <div style="font-size:14px;font-weight:600;color:var(--ink, #f1f5f9)">{{ __('No matching participants found') }}</div>
        <div class="s muted" style="margin-top:4px">
          {{ __('Try checking your search query or clear the filter.') }}
          <button type="button" class="btn ghost sm" id="results-clear-btn" style="margin-left:6px;padding:2px 8px;font-size:12px">{{ __('Clear search') }}</button>
        </div>
      </td>
    </tr>
  </tbody>
</table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const tableWrap = document.querySelector('.gradebook-table-wrap');
  if (!tableWrap) return;
  const table = tableWrap.querySelector('.gradebook-table');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const searchInput = document.getElementById('results-search-input');
  const searchClear = document.getElementById('results-search-clear');
  const countBadge = document.getElementById('results-count-badge');
  const filterBtns = document.querySelectorAll('.results-filter-btn');
  const sortBtns = table.querySelectorAll('.results-sort-btn');
  const emptyRow = document.getElementById('results-empty-row');
  const clearBtn = document.getElementById('results-clear-btn');

  let currentSearch = '';
  let currentFilter = 'all';
  let currentSort = null; // { col: 'name'|'progress'|'final'|'result'|'comp', cid: id, dir: 'asc'|'desc' }

  // 1. FILTERING
  function applyFilters() {
    const q = currentSearch.trim().toLowerCase();
    const rows = Array.from(tbody.querySelectorAll('tr.gradebook-row'));
    let visibleCount = 0;

    rows.forEach(row => {
      const name = (row.dataset.name || '').toLowerCase();
      const id = (row.dataset.id || '').toLowerCase();
      const result = (row.dataset.result || '').toLowerCase();

      const matchesSearch = !q || name.includes(q) || id.includes(q);
      const matchesFilter = (currentFilter === 'all') || (result === currentFilter);

      if (matchesSearch && matchesFilter) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (emptyRow) {
      emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }

    if (countBadge) {
      if (visibleCount === rows.length) {
        countBadge.textContent = `${rows.length} {{ __('participants') }}`;
      } else {
        countBadge.textContent = `${visibleCount} / ${rows.length} {{ __('participants') }}`;
      }
    }
  }

  // 2. SORTING
  function getRowValue(row, col, cid) {
    if (col === 'name') {
      return (row.dataset.name || '').trim();
    }
    if (col === 'progress') {
      const p = parseFloat(row.dataset.progress);
      return isNaN(p) ? -1 : p;
    }
    if (col === 'final') {
      const fin = parseFloat(row.dataset.final);
      return isNaN(fin) ? -1 : fin;
    }
    if (col === 'result') {
      const order = { 'pass': 1, 'missing': 2, 'fail': 3, 'held': 4 };
      return order[row.dataset.result] || 99;
    }
    if (col === 'comp') {
      const input = row.querySelector(`input.mark-input[data-cid="${cid}"]`);
      if (input) {
        const val = input.value.trim();
        return val === '' || isNaN(val) ? -1 : parseFloat(val);
      }
      const valSpan = row.querySelector(`td[data-cid="${cid}"] .gradebook-score-val`);
      if (valSpan) {
        const v = parseFloat(valSpan.textContent.trim());
        return isNaN(v) ? -1 : v;
      }
      return -1;
    }
    return '';
  }

  function applySort() {
    if (!currentSort) return;
    const rows = Array.from(tbody.querySelectorAll('tr.gradebook-row'));
    if (!rows.length) return;

    const { col, cid, dir } = currentSort;
    const factor = dir === 'asc' ? 1 : -1;

    rows.sort((a, b) => {
      const valA = getRowValue(a, col, cid);
      const valB = getRowValue(b, col, cid);

      if (typeof valA === 'number' && typeof valB === 'number') {
        if (valA !== valB) {
          return (valA - valB) * factor;
        }
      } else {
        const strA = String(valA);
        const strB = String(valB);
        const cmp = strA.localeCompare(strB);
        if (cmp !== 0) {
          return cmp * factor;
        }
      }
      return (a.dataset.name || '').localeCompare(b.dataset.name || '');
    });

    rows.forEach(r => tbody.appendChild(r));
    if (emptyRow) tbody.appendChild(emptyRow);
  }

  const sortWrap = document.getElementById('results-sort-wrap');
  const sortBtn = document.getElementById('results-sort-btn');
  const sortMenu = document.getElementById('results-sort-menu');
  const sortDirBox = document.getElementById('results-sort-dir');
  const sortFoot = document.getElementById('results-sort-foot');
  const sortReset = document.getElementById('results-sort-reset');
  const sortLive = document.getElementById('results-sort-live');
  const origRows = Array.from(tbody.querySelectorAll('tr.gradebook-row'));
  const isMs = (document.documentElement.lang || '').startsWith('ms');

  function getDirLabels(type) {
    if (type === 'num') return { asc: isMs ? 'Terendah dahulu' : 'Lowest first', desc: isMs ? 'Tertinggi dahulu' : 'Highest first', def: 'desc' };
    if (type === 'status') return { asc: isMs ? 'Urutan aliran kerja' : 'Workflow order', desc: isMs ? 'Urutan terbalik' : 'Reverse order', def: 'asc' };
    return { asc: isMs ? 'A ke Z' : 'A to Z', desc: isMs ? 'Z ke A' : 'Z to A', def: 'asc' };
  }

  function updateSortUi(announce) {
    if (!sortBtn || !sortMenu) return;
    const lblSpan = sortBtn.querySelector('.tbar-sort-lbl');
    if (!currentSort) {
      if (lblSpan) lblSpan.textContent = isMs ? 'Susun: Lalai' : 'Sort: Default';
      sortBtn.classList.remove('active');
      if (sortFoot) sortFoot.hidden = true;
      sortMenu.querySelectorAll('input[type=radio]').forEach(r => { r.checked = false; r.closest('.tbar-sort-opt')?.classList.remove('on'); });
      if (announce && sortLive) sortLive.textContent = isMs ? 'Susun: Lalai' : 'Sort: Default';
      return;
    }
    const val = currentSort.col === 'comp' ? ('comp:' + currentSort.cid) : currentSort.col;
    const radio = sortMenu.querySelector(`input[value="${val}"]`);
    const type = radio ? (radio.dataset.type || 'text') : 'text';
    const fieldLabel = radio ? (radio.dataset.label || val) : val;
    const dl = getDirLabels(type);
    const dirTxt = currentSort.dir === 'asc' ? dl.asc : dl.desc;
    const summary = (isMs ? 'Susun: ' : 'Sort: ') + fieldLabel + ', ' + dirTxt.toLowerCase();
    if (lblSpan) lblSpan.textContent = summary;
    sortBtn.classList.add('active');
    if (sortFoot) sortFoot.hidden = false;
    sortMenu.querySelectorAll('input[type=radio]').forEach(r => {
      const on = r.value === val;
      r.checked = on;
      r.closest('.tbar-sort-opt')?.classList.toggle('on', on);
    });
    if (sortDirBox) {
      const btns = sortDirBox.querySelectorAll('.tbar-dir-btn');
      if (btns[0]) { btns[0].dataset.dir = 'asc'; btns[0].textContent = dl.asc; btns[0].classList.toggle('on', currentSort.dir === 'asc'); }
      if (btns[1]) { btns[1].dataset.dir = 'desc'; btns[1].textContent = dl.desc; btns[1].classList.toggle('on', currentSort.dir === 'desc'); }
    }
    if (announce && sortLive) sortLive.textContent = summary;
  }

  if (sortBtn && sortMenu) {
    sortBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const open = sortMenu.hidden;
      sortMenu.hidden = !open;
      sortBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    sortMenu.addEventListener('click', function (e) { e.stopPropagation(); });
    sortMenu.addEventListener('change', function (e) {
      if (e.target && e.target.type === 'radio') {
        const v = e.target.value;
        const dl = getDirLabels(e.target.dataset.type || 'text');
        if (v.startsWith('comp:')) {
          currentSort = { col: 'comp', cid: v.slice(5), dir: dl.def };
        } else {
          currentSort = { col: v, cid: null, dir: dl.def };
        }
        applySort();
        updateSortUi(true);
      }
    });
    if (sortDirBox) {
      sortDirBox.addEventListener('click', function (e) {
        const b = e.target.closest('.tbar-dir-btn');
        if (!b) return;
        if (!currentSort) currentSort = { col: 'name', cid: null, dir: b.dataset.dir };
        else currentSort.dir = b.dataset.dir;
        applySort();
        updateSortUi(true);
      });
    }
    if (sortReset) {
      sortReset.addEventListener('click', function () {
        currentSort = null;
        origRows.forEach(r => tbody.appendChild(r));
        updateSortUi(true);
        sortMenu.hidden = true;
        sortBtn.setAttribute('aria-expanded', 'false');
        sortBtn.focus();
      });
    }
    sortMenu.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { sortMenu.hidden = true; sortBtn.setAttribute('aria-expanded', 'false'); sortBtn.focus(); }
    });
    document.addEventListener('click', function (e) {
      if (sortWrap && !sortWrap.contains(e.target)) { sortMenu.hidden = true; sortBtn.setAttribute('aria-expanded', 'false'); }
    });
  }

  function updateSortIcons() { updateSortUi(false); }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentSearch = this.value;
      if (searchClear) searchClear.style.display = currentSearch ? 'block' : 'none';
      applyFilters();
    });
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') e.preventDefault();
    });
  }

  if (searchClear) {
    searchClear.addEventListener('click', function () {
      if (searchInput) searchInput.value = '';
      currentSearch = '';
      searchClear.style.display = 'none';
      applyFilters();
      if (searchInput) searchInput.focus();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (searchInput) searchInput.value = '';
      currentSearch = '';
      currentFilter = 'all';
      if (searchClear) searchClear.style.display = 'none';
      filterBtns.forEach(b => b.classList.toggle('on', b.dataset.filter === 'all'));
      applyFilters();
    });
  }

  filterBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      filterBtns.forEach(b => b.classList.remove('on'));
      this.classList.add('on');
      currentFilter = this.dataset.filter || 'all';
      applyFilters();
    });
  });

  sortBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const col = this.dataset.sort;
      const cid = this.dataset.cid || null;

      if (currentSort && currentSort.col === col && currentSort.cid === cid) {
        currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
      } else {
        currentSort = { col, cid, dir: 'asc' };
      }

      applySort();
      updateSortIcons();
    });
  });

  // Keep row dataset in sync with live input changes
  table.addEventListener('input', function (e) {
    if (e.target.matches('input.mark-input')) {
      const eid = e.target.dataset.eid;
      const row = tbody.querySelector(`tr.gradebook-row[data-eid="${eid}"]`);
      if (row) {
        setTimeout(() => {
          const finEl = document.getElementById('fin-' + eid);
          const rsEl = document.getElementById('rs-' + eid);
          if (finEl) {
            const txt = finEl.textContent.trim();
            row.dataset.final = (txt === '-' || isNaN(txt)) ? -1 : parseFloat(txt);
          }
          if (rsEl) {
            const txt = rsEl.textContent.trim().toLowerCase();
            if (txt.includes('pass')) row.dataset.result = 'pass';
            else if (txt.includes('missing')) row.dataset.result = 'missing';
            else if (txt.includes('fail')) row.dataset.result = 'fail';
            else if (txt.includes('held')) row.dataset.result = 'held';
          }
        }, 10);
      }
    }
  });

  updateSortIcons();
});
</script>
