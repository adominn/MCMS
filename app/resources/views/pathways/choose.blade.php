@extends('layouts.app')
@section('title', __('Choose a degree pathway'))
@section('content')
<x-breadcrumb :crumbs="[
  __('My degree plan') => route('plan.mine'),
  __('Choose a degree pathway') => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('Choose a degree pathway') }}</h1>
    <div class="sub">{{ __('Browse approved UTeM degree pathways by level, compare options, and open any programme to inspect mapped micro-credentials.') }}</div>
  </div>
  <div class="act" style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn ter sm" href="{{ route('programmes.compare') }}">{{ __('Compare Programmes') }}</a>
    <a class="btn ter sm" href="{{ route('plan.mine') }}">&larr; {{ __('Back to my plan') }}</a>
  </div>
</div>

<div class="note" style="margin-bottom:20px">
  <div>{{ __('Selecting a pathway adds it to your personal tracker. It is not programme admission or a credit transfer decision. At least :min% of graduating credits must be earned directly at UTeM upon admission.', ['min' => mcms_num($minHomePercent ?? 30)]) }}</div>
</div>

@if($errors->any())
  <div class="note err" style="margin-bottom:20px">
    <ul style="margin:0;padding-left:18px">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- Pinned Selected Pathway Banner (Visually Distinct at Top) --}}
@if($selectedPathways->isNotEmpty())
  @foreach($selectedPathways as $sel)
    @php($selPct = $sel->total_count > 0 ? round(($sel->earned_count / $sel->total_count) * 100) : 0)
    <div class="panel" style="margin-bottom:20px;border:2px solid var(--color-primary, #0052ff);background:var(--color-primary-tint, #edf4ff)">
      <div class="bd" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
        <div style="flex:1;min-width:260px">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
            <span class="tag ok"><strong>{{ __('Your Selected Pathway') }}</strong></span>
            <span class="tag info" style="font-family:var(--font-mono)"><strong>{{ $sel->programme_code }}</strong></span>
            @if(!empty($sel->faculty_code))
              <span class="tag neutral">{{ $sel->faculty_code }}</span>
            @endif
            <span class="tag neutral">MQF {{ $sel->mqf_level }}</span>
            <span class="s muted">{{ mcms_num($sel->total_credits) }} cr</span>
          </div>
          <h2 style="margin:0 0 4px;font-size:17px;color:var(--color-ink, #090a0f)">{{ $sel->programme_name }}</h2>
          <div class="s" style="color:var(--color-ink-muted, #475569)">
            <strong>{{ $sel->earned_count }} {{ __('of') }} {{ $sel->total_count }}</strong> {{ __('mapped micro-credentials earned') }}
            ({{ mcms_num($sel->earned_credits) }} / {{ mcms_num($sel->mapped_credits) }} cr &middot; {{ $selPct }}%)
          </div>
        </div>
        <div style="display:inline-flex;gap:8px;flex-wrap:wrap;align-items:center">
          <a class="btn primary sm" href="{{ route('plan.mine') }}">{{ __('View in My Plan') }}</a>
          <a class="btn ter sm" href="{{ route('plan.detail', $sel->id) }}">{{ __('View Details') }}</a>
          <a class="btn ter sm" href="{{ route('public.pathways.print', $sel->id) }}" target="_blank">{{ __('Specification (PDF)') }}</a>
        </div>
      </div>
    </div>
  @endforeach
@endif

{{-- Level Tabs (Separates Bachelor, Master's, Diploma cleanly) --}}
<nav class="tabs" aria-label="{{ __('Qualification level') }}" style="margin-bottom:16px;overflow-x:auto">
  @foreach([
    ['all', __('All Levels'), $levelCounts['all']],
    ['bachelor', __('Bachelor (MQF 6)'), $levelCounts['bachelor']],
    ['master', __("Master's (MQF 7)"), $levelCounts['master']],
    ['diploma', __('Diploma (MQF 4)'), $levelCounts['diploma']],
  ] as [$key, $label, $cnt])
    @if($key === 'all' || $cnt > 0)
      <a href="{{ route('plan.choose', array_filter(['level' => $key === 'all' ? null : $key, 'faculty' => $faculty ?: null, 'q' => $q ?: null, 'sort' => $sort !== 'best_match' ? $sort : null, 'selected' => $selectedOnly ? 1 : null])) }}"
         class="{{ $level === $key ? 'on' : '' }}"
         style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:10px 14px;font-size:13px;font-weight:600;border-bottom:2px solid {{ $level === $key ? 'var(--color-primary, #0052ff)' : 'transparent' }};color:{{ $level === $key ? 'var(--color-primary, #0052ff)' : 'var(--color-ink-muted, #475569)' }}">
        <span>{{ $label }}</span>
        <span class="tag neutral" style="font-size:11px">{{ $cnt }}</span>
      </a>
    @endif
  @endforeach
</nav>

{{-- Single Unified Search & Filter Bar (Rule D-67: 1 search bar per screen) --}}
<form method="get" action="{{ route('plan.choose') }}" class="panel" style="margin-bottom:20px" role="search">
  @if($level !== 'all')
    <input type="hidden" name="level" value="{{ $level }}">
  @endif
  <div class="bd" style="padding:14px 18px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <div style="flex:1.6;min-width:220px">
      <label for="q" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Search Programmes or Micro-Credentials') }}</label>
      <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('Search by code, programme name, or course...') }}" style="width:100%">
    </div>
    <div style="min-width:170px">
      <label for="faculty" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Faculty') }}</label>
      <select id="faculty" name="faculty" style="width:100%">
        <option value="">{{ __('All faculties') }}</option>
        @foreach($faculties as $fCode => $fName)
          <option value="{{ $fCode }}" @selected($faculty === $fCode)>{{ $fCode }} &ndash; {{ $fName }}</option>
        @endforeach
      </select>
    </div>
    <div style="min-width:150px">
      <label for="sort" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Sort By') }}</label>
      <select id="sort" name="sort" style="width:100%">
        <option value="best_match" @selected($sort === 'best_match')>{{ __('Best match') }}</option>
        <option value="name" @selected($sort === 'name')>{{ __('Programme name (A–Z)') }}</option>
        <option value="credits" @selected($sort === 'credits')>{{ __('Highest credits') }}</option>
      </select>
    </div>
    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;padding-bottom:8px;cursor:pointer">
      <input type="checkbox" name="selected" value="1" @checked($selectedOnly)>
      <span>{{ __('Selected only') }}</span>
    </label>
    <div style="display:inline-flex;gap:6px">
      <button type="submit" class="btn primary sm">{{ __('Apply') }}</button>
      @if($q !== '' || $faculty !== '' || $selectedOnly || $level !== 'all' || $sort !== 'best_match')
        <a class="btn ter sm" href="{{ route('plan.choose') }}">{{ __('Reset') }}</a>
      @endif
    </div>
  </div>
</form>

{{-- Compact Programme Cards Grid (Rule D-67: 0 tables inside cards, paginated) --}}
@if($pathways->count() > 0)
  <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(360px, 1fr));gap:16px">
    @foreach($pathways as $pw)
      <x-programme-summary-card
        :pathway="$pw"
        :earnedCount="$pw->earned_count"
        :enrolledCount="$pw->enrolled_count"
        :totalCount="$pw->total_count"
        :earnedCredits="$pw->earned_credits"
        :totalMappedCredits="$pw->mapped_credits"
        :isSelected="$pw->is_selected"
        :detailUrl="route('plan.detail', $pw->id)"
      />
    @endforeach
  </div>

  <x-pagination-bar :paginator="$pathways" singular="pathway" plural="pathways" />
@else
  <div class="panel">
    <div class="bd" style="padding:32px;text-align:center">
      <h2 style="margin:0 0 8px;font-size:16px">{{ __('No matching pathways found') }}</h2>
      <p class="muted" style="margin:0 0 16px">{{ __('Try clearing your search or switching to another qualification level.') }}</p>
      <a class="btn ter sm" href="{{ route('plan.choose') }}">{{ __('Show All Pathways') }}</a>
    </div>
  </div>
@endif

{{-- Sticky Bottom Compare Tray (Select 2–3 programmes to compare without opening each one) --}}
<div id="compare-tray" style="display:none;position:sticky;bottom:16px;z-index:40;margin-top:20px;background:var(--color-surface-contrast, #090a0f);color:#fff;padding:12px 18px;border-radius:var(--radius-md, 6px);box-shadow:var(--shadow-modal, 0 16px 40px rgba(9,10,15,0.2));justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px">
    <strong>{{ __('Compare:') }}</strong>
    <span id="compare-tray-list"></span>
  </div>
  <div style="display:inline-flex;gap:8px;align-items:center">
    <a id="compare-tray-go" class="btn primary sm" href="{{ route('programmes.compare') }}">{{ __('Compare Side by Side') }}</a>
    <button type="button" class="btn ter sm" onclick="clearCompareTray()" style="background:#fff;color:#090a0f">{{ __('Clear') }}</button>
  </div>
</div>

<script>
var maxCompare = {{ (int) $maxCompare }};
function updateCompareTray() {
  var checked = Array.prototype.slice.call(document.querySelectorAll('.pw-compare-check:checked'));
  if (checked.length > maxCompare) {
    checked[checked.length - 1].checked = false;
    checked.pop();
  }
  var tray = document.getElementById('compare-tray');
  var list = document.getElementById('compare-tray-list');
  var go = document.getElementById('compare-tray-go');
  if (checked.length < 2) {
    tray.style.display = 'none';
    return;
  }
  tray.style.display = 'flex';
  list.textContent = checked.map(function (c) { return c.value; }).join(' vs ');
  go.href = '{{ route('programmes.compare') }}' + '?from=' + encodeURIComponent(checked[0].value) + '&to=' + encodeURIComponent(checked[1].value);
}
function clearCompareTray() {
  document.querySelectorAll('.pw-compare-check:checked').forEach(function (c) { c.checked = false; });
  updateCompareTray();
}
</script>
@endsection
