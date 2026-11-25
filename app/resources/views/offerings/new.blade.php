@extends('layouts.app')
@section('title', 'New offering')
@section('content')
<div class="ph">
  <div>
    <x-breadcrumb :crumbs="[
      'Offerings & cohorts' => route('offerings'),
      'New offering' => null,
    ]" />
    <h1>{{ __('Create an offering') }}</h1>
    <div class="sub">{{ __('Schedule a cohort of an approved micro-credential version.') }}</div>
  </div>
</div>

@if($versions->isEmpty())
  <div class="note warn" role="alert">
    <b>{{ __('No approved version:') }}</b> {{ __('You have no approved version to offer yet. A version can be offered only after the Senate approves it.') }}
  </div>
@else
@php
  $tab2ErrorKeys = ['description', 'prerequisites', 'access_period', 'learning_support', 'completion_conditions', 'completion_window_months', 'staged_completion'];
  $startOnPublicTab = $errors->hasAny($tab2ErrorKeys) && !$errors->hasAny(['mc_version_id', 'cohort_name', 'start_date', 'end_date', 'delivery_mode', 'capacity', 'reg_opens_on', 'reg_closes_on', 'fee', 'platform_ref']);
@endphp
<div class="cols">
  <div>
    <form method="post" action="{{ route('offerings.store') }}" id="offering-create-form">
      @csrf
      <div data-tabs data-default-tab="{{ $startOnPublicTab ? 'off-public' : 'off-params' }}">
        <div class="tabs" role="tablist" style="margin-bottom:16px">
          <button type="button" class="tab" role="tab" data-tab="off-params" aria-selected="{{ $startOnPublicTab ? 'false' : 'true' }}">
            {{ __('1. Cohort parameters') }}
          </button>
          <button type="button" class="tab" role="tab" data-tab="off-public" aria-selected="{{ $startOnPublicTab ? 'true' : 'false' }}">
            {{ __('2. Public information') }}
          </button>
        </div>

        <div data-pane="off-params" @if($startOnPublicTab) hidden @endif>
          <div class="panel" style="margin-bottom:20px">
            <div class="hd"><h2>{{ __('Cohort parameters') }}</h2></div>
            <div class="bd">
          <div class="field" style="margin-bottom:14px">
            <label for="mc_version_id">{{ __('Approved version') }}</label>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <select id="mc_version_id" name="mc_version_id" style="flex:1;min-width:240px">
                @foreach($versions as $v)
                  @php
                    $vModeKey = $v->delivery_mode ?: 'online';
                    $vModeLabel = __($modes[$vModeKey] ?? ucfirst($vModeKey));
                    if ($vModeKey === 'other' && !empty($v->delivery_mode_other)) {
                      $vModeLabel .= ' (' . $v->delivery_mode_other . ')';
                    }
                  @endphp
                  <option value="{{ $v->id }}"
                    data-url="{{ route('proposals.show', $v->id) }}"
                    data-print-url="{{ route('proposals.print', $v->id) }}"
                    data-cat="{{ strtoupper($v->category ?? '') }}"
                    data-mode-key="{{ $vModeKey }}"
                    data-mode-label="{{ $vModeLabel }}"
                    data-lang="{{ $v->delivery_language ?? '' }}"
                    data-period="{{ $v->offering_period ?? '' }}"
                    data-slt="{{ $v->slt_hours ?? 0 }}"
                    data-target="{{ $v->target_group ?? '' }}"
                    @selected(old('mc_version_id') == $v->id)>
                    {{ $v->name_en }} ({{ __('version :num', ['num' => $v->version_no]) }})
                  </option>
                @endforeach
              </select>
              <a id="btn-view-approved-mc" href="{{ route('proposals.show', $versions->first()->id) }}" target="_blank" rel="noopener" class="btn ter sm" style="white-space:nowrap">
                {{ __('View approved proposal') }} &nearr;
              </a>
            </div>
            <div id="approved-mc-snapshot" style="margin-top:8px;padding:9px 12px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px;font-size:12.5px;display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center"></div>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="cohort_name">{{ __('Cohort name') }}</label>
            <input type="text" id="cohort_name" name="cohort_name" value="{{ old('cohort_name') }}" required placeholder="e.g. 2027/1">
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="start_date">{{ __('Start date') }}</label>
              <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
            </div>
            <div class="field" style="flex:1">
              <label for="end_date">{{ __('End date') }}</label>
              <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
            </div>
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="delivery_mode_display" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span>{{ __('Delivery mode') }}</span>
                <span class="tag" style="font-size:11px;font-weight:600">{{ __('From approved proposal') }}</span>
              </label>
              <input type="hidden" id="delivery_mode" name="delivery_mode" value="{{ old('delivery_mode', $versions->first()->delivery_mode ?: 'online') }}">
              <input type="text" id="delivery_mode_display" value="" readonly aria-readonly="true" style="background:var(--surface, #f8fafc);color:var(--ink);cursor:not-allowed">
            </div>
            <div class="field" style="max-width:140px">
              <label for="capacity">{{ __('Places') }}</label>
              <input type="text" inputmode="numeric" id="capacity" name="capacity" value="{{ old('capacity') }}" required placeholder="40">
            </div>
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="reg_opens_on">{{ __('Registration opens') }}</label>
              <input type="date" id="reg_opens_on" name="reg_opens_on" value="{{ old('reg_opens_on') }}" required>
            </div>
            <div class="field" style="flex:1">
              <label for="reg_closes_on">{{ __('Registration closes') }}</label>
              <input type="date" id="reg_closes_on" name="reg_closes_on" value="{{ old('reg_closes_on') }}" required>
            </div>
          </div>

          <div class="field" style="margin-bottom:16px;max-width:200px">
            <label for="fee">{{ __('Fee (RM, 0 for free)') }}</label>
            <input type="text" inputmode="decimal" id="fee" name="fee" value="{{ old('fee', '0') }}" required>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="off-fac-search" style="display:block;font-weight:600;margin-bottom:6px">{{ __('Invite UTeM Facilitators (optional — invited colleagues accept under Invitations & reviews)') }}</label>
            <div id="off-fac-picker" data-url="{{ route('lecturers.search') }}" data-ptj-id="{{ $defaultPtjId ?? '' }}" style="position:relative">
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                <input type="search" id="off-fac-search" autocomplete="off" placeholder="{{ __('Search lecturer by name, staff ID, or email...') }}" style="flex:1;min-width:240px">
                <label style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;cursor:pointer;margin:0;white-space:nowrap">
                  <input type="checkbox" id="off-fac-all-ptj" style="margin:0">
                  <span>{{ __('All faculties') }}</span>
                </label>
              </div>
              <div id="off-fac-results" style="display:none;position:absolute;left:0;right:0;top:40px;z-index:50;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-radius:4px;box-shadow:0 8px 24px rgba(9,10,15,0.12);max-height:220px;overflow-y:auto"></div>
              <div id="off-fac-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px"></div>
            </div>
            <div class="small muted" style="margin-top:4px">{{ __('Default filtered to :ptj. Invited lecturers receive a notification and gain marks entry & sign-off access once they accept.', ['ptj' => $defaultPtjCode ?? __('your faculty')]) }}</div>
          </div>

          <script>
          (function () {
            var wrap = document.getElementById('off-fac-picker');
            var inp = document.getElementById('off-fac-search');
            var allPtj = document.getElementById('off-fac-all-ptj');
            var resBox = document.getElementById('off-fac-results');
            var chips = document.getElementById('off-fac-chips');
            if (!wrap || !inp || !resBox || !chips) return;
            var selected = {};
            var timer = null;

            function renderChips() {
              chips.innerHTML = '';
              Object.keys(selected).forEach(function (id) {
                var u = selected[id];
                var chip = document.createElement('span');
                chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:#edf4ff;border:1px solid #0052ff;border-radius:4px;font-size:12.5px;font-weight:500';
                chip.innerHTML = '<span>' + u.name + '</span><input type="hidden" name="assigned_facilitators[]" value="' + u.id + '"><button type="button" aria-label="Remove" style="border:0;background:none;color:#da1e28;font-weight:700;cursor:pointer;padding:0 2px">&times;</button>';
                chip.querySelector('button').addEventListener('click', function () { delete selected[id]; renderChips(); });
                chips.appendChild(chip);
              });
            }

            function load() {
              var url = new URL(wrap.getAttribute('data-url'), location.origin);
              url.searchParams.set('q', inp.value.trim());
              if (wrap.getAttribute('data-ptj-id')) url.searchParams.set('ptj_id', wrap.getAttribute('data-ptj-id'));
              if (allPtj && allPtj.checked) url.searchParams.set('all_ptj', '1');
              fetch(url.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                  var rows = (json && json.data) ? json.data : [];
                  resBox.innerHTML = '';
                  if (!rows.length) {
                    resBox.innerHTML = '<div class="small muted" style="padding:10px 12px">{{ __("No matching lecturers found.") }}</div>';
                  } else {
                    rows.forEach(function (u) {
                      var isPicked = !!selected[u.id];
                      var b = document.createElement('button');
                      b.type = 'button';
                      b.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%;text-align:left;padding:8px 12px;border:0;border-bottom:1px solid #e2e8f0;background:' + (isPicked ? '#edfcf0' : '#ffffff') + ';color:#090a0f;font-size:12.5px;cursor:pointer';
                      var meta = (u.ptj_code || 'UTeM') + (u.max_mqf_level ? ' · ≤ MQF ' + u.max_mqf_level : '');
                      var rightBadge = isPicked
                        ? '<span class="tag ok" style="font-size:10.5px">{{ __("Selected") }}</span>'
                        : '<span class="tag info" style="font-size:10.5px">' + meta + '</span>';
                      b.innerHTML = '<span><strong>' + u.name + '</strong> <span class="muted small">' + (u.institution_id || u.email) + '</span></span>' + rightBadge;
                      b.addEventListener('mousedown', function (ev) {
                        ev.preventDefault();
                        if (selected[u.id]) { delete selected[u.id]; } else { selected[u.id] = u; }
                        renderChips();
                        inp.value = '';
                        load();
                      });
                      resBox.appendChild(b);
                    });
                  }
                  resBox.style.display = 'block';
                });
            }
            inp.addEventListener('focus', load);
            inp.addEventListener('click', load);
            inp.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(load, 180); });
            if (allPtj) allPtj.addEventListener('change', load);
            inp.addEventListener('blur', function () { setTimeout(function () { resBox.style.display = 'none'; }, 160); });
          })();
          </script>

          <div class="field" style="margin-bottom:16px">
            <label for="facilitators">{{ __('Additional / External Facilitators (displayed publicly)') }}</label>
            <input type="text" id="facilitators" name="facilitators" value="{{ old('facilitators') }}" placeholder="{{ __('Names of external industry trainers or additional facilitators') }}">
          </div>

          <div class="field" style="margin-bottom:20px">
            <label for="platform_ref">{{ __('Learning platform course reference') }}</label>
            <input type="text" id="platform_ref" name="platform_ref" value="{{ old('platform_ref') }}" list="ucreds-courses" autocomplete="off" placeholder="{{ __('e.g. UCreds course shell ID or course URL') }}">
            <datalist id="ucreds-courses">
              @foreach($ucreds as $c)
                <option value="UCreds course {{ $c->course_id }}">{{ $c->title }}{{ $c->subject_code ? ' ('.$c->subject_code.')' : '' }}</option>
              @endforeach
            </datalist>
          </div>

          <div class="row" style="justify-content:space-between;align-items:center;gap:12px;padding-top:12px;border-top:1px solid var(--hairline)">
            <a class="btn ter" href="{{ route('offerings') }}">{{ __('Cancel') }}</a>
            <button type="button" class="btn" onclick="var b=document.querySelector('[data-tab=&quot;off-public&quot;]');if(b)b.click();window.scrollTo({top:0,behavior:'smooth'});">
              {{ __('Next: Public information') }} &rarr;
            </button>
          </div>
        </div>
      </div>
        </div>

        <div data-pane="off-public" @if(!$startOnPublicTab) hidden @endif>
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Public information') }}</h2></div>
        <div class="bd">
          <p class="muted" style="margin-top:0;font-size:13px">
            {{ __('Credit, MQF level and Quality Verification are never typed here. They appear only from verified records. Text that promises credit, transfer or a degree is refused.') }}
          </p>

          <div class="field" style="margin-bottom:16px">
            <label for="description">{{ __('Description') }}</label>
            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="prerequisites">{{ __('Prerequisites') }}</label>
            <input type="text" id="prerequisites" name="prerequisites" value="{{ old('prerequisites') }}" placeholder="e.g. Basic Python programming">
          </div>

          <div class="row" style="gap:16px;margin-bottom:16px">
            <div class="field" style="flex:1">
              <label for="access_period">{{ __('Access period') }}</label>
              <input type="text" id="access_period" name="access_period" value="{{ old('access_period') }}">
            </div>
            <div class="field" style="flex:1">
              <label for="learning_support">{{ __('Learning support') }}</label>
              <input type="text" id="learning_support" name="learning_support" value="{{ old('learning_support') }}">
            </div>
          </div>

          <div class="field" style="margin-bottom:16px">
            <label for="completion_conditions">{{ __('Completion conditions') }}</label>
            <input type="text" id="completion_conditions" name="completion_conditions" value="{{ old('completion_conditions') }}">
          </div>

          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="completion_window_months">{{ __('Months to finish all modules') }}</label>
            <input type="text" inputmode="numeric" id="completion_window_months" name="completion_window_months" value="{{ old('completion_window_months') }}">
            <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('For staged completion; empty uses default setting.') }}</div>
          </div>

          <div id="staged-completion-wrap" style="margin-bottom:20px">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" id="staged_completion" name="staged_completion" value="1" @checked(old('staged_completion'))>
              <span>{{ __('Modules may be completed in stages (a modular UMC)') }}</span>
            </label>
          </div>

          <div class="row" style="justify-content:space-between;align-items:center;gap:12px;padding-top:12px;border-top:1px solid var(--hairline)">
            <button type="button" class="btn ter" onclick="var b=document.querySelector('[data-tab=&quot;off-params&quot;]');if(b)b.click();window.scrollTo({top:0,behavior:'smooth'});">
              &larr; {{ __('Back to cohort parameters') }}
            </button>
            <div class="row" style="gap:10px">
              <a class="btn ter" href="{{ route('offerings') }}">{{ __('Cancel') }}</a>
              <button type="submit" class="btn">{{ __('Create offering') }}</button>
            </div>
          </div>
        </div>
      </div>
        </div>
      </div>
    </form>
    <script>
    (function () {
      var form = document.getElementById('offering-create-form');
      if (!form) return;

      // Sync locked fields (Delivery mode, Approved proposal link, snapshot & UMC-only staged toggle) when Approved version changes
      var verSel = document.getElementById('mc_version_id');
      var viewBtn = document.getElementById('btn-view-approved-mc');
      var snap = document.getElementById('approved-mc-snapshot');
      var modeHidden = document.getElementById('delivery_mode');
      var modeDisplay = document.getElementById('delivery_mode_display');
      var stagedWrap = document.getElementById('staged-completion-wrap');
      var stagedCb = document.getElementById('staged_completion');

      function escHtml(s) {
        return String(s || '').replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; });
      }
      function syncVersion() {
        if (!verSel) return;
        var opt = verSel.options[verSel.selectedIndex];
        if (!opt) return;
        var cat = opt.getAttribute('data-cat') || '';
        var modeKey = opt.getAttribute('data-mode-key') || 'online';
        var modeLbl = opt.getAttribute('data-mode-label') || modeKey;
        var lang = opt.getAttribute('data-lang') || '';
        var period = opt.getAttribute('data-period') || '';
        var slt = opt.getAttribute('data-slt') || '0';
        var url = opt.getAttribute('data-url') || '';

        if (viewBtn && url) viewBtn.href = url;
        if (modeHidden) modeHidden.value = modeKey;
        if (modeDisplay) modeDisplay.value = modeLbl;

        if (snap) {
          var parts = [];
          if (cat) parts.push('<span class="tag info" style="font-weight:700">' + escHtml(cat) + '</span>');
          if (slt && parseInt(slt, 10) > 0) parts.push('<span><b>SLT:</b> ' + escHtml(slt) + ' h</span>');
          if (period) parts.push('<span><b>' + escHtml(@json(__('Duration:'))) + '</b> ' + escHtml(period) + '</span>');
          if (lang) parts.push('<span><b>' + escHtml(@json(__('Language:'))) + '</b> ' + escHtml(lang) + '</span>');
          snap.innerHTML = parts.join('');
          snap.hidden = parts.length === 0;
        }
        if (stagedWrap) {
          var isUmc = (cat === 'UMC');
          stagedWrap.hidden = !isUmc;
          if (!isUmc && stagedCb) stagedCb.checked = false;
        }
      }
      if (verSel) {
        verSel.addEventListener('change', syncVersion);
        syncVersion();
      }

      // If a required field on a hidden tab fails browser validation, switch to that tab and focus it
      form.addEventListener('invalid', function (e) {
        var pane = e.target.closest('[data-pane]');
        if (pane && pane.hidden) {
          var key = pane.getAttribute('data-pane');
          var btn = form.querySelector('[data-tab="' + key + '"]');
          if (btn) btn.click();
          setTimeout(function () { e.target.focus(); }, 20);
        }
      }, true);
    })();
    </script>
  </div>

  <div>
    <div class="panel" style="position:sticky;top:20px">
      <div class="hd"><h2>{{ __('Readiness process') }}</h2></div>
      <div class="bd">
        <div class="col" style="gap:14px;font-size:13px;line-height:1.5">
          <div>
            <b>{{ __('1. Create cohort') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('New cohorts start in Not ready status and do not appear in the public catalogue.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:10px">
            <b>{{ __('2. Review automated checks & confirmations') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('Resolve any automated readiness issues, confirm the 4 operational sections, and send the checklist to DD and/or CODL.') }}
            </p>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:10px">
            <b>{{ __('3. DD and/or CODL confirmation') }}</b>
            <p class="muted s" style="margin:2px 0 0">
              {{ __('The Deputy Dean and/or CODL reviews the evidence and records the readiness decision. Once confirmed, the cohort opens automatically on the registration opening date.') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection
