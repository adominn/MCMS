@php
  $totalItems = count($cfg['readiness']);
  $readyCount = 0;
  foreach ($cfg['readiness'] as $k => $l) {
    $st = $ready[$k]->status ?? 'not_yet';
    if ($st === 'yes' || $st === 'na') { $readyCount++; }
  }
@endphp

<form method="post" action="{{ route('proposals.save', [$v->id, 'readiness']) }}" class="card" data-autosave id="readiness-form">@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--hairline)">
      <div style="display:flex;align-items:center;gap:10px">
        <h2 style="font-size:16px;font-weight:700;color:var(--ink);margin:0">{{ __('Implementation Readiness') }}</h2>
        <span class="tag {{ $readyCount === $totalItems ? 'ok' : 'warn' }}" id="readiness-progress-badge" style="font-family:var(--mono);font-weight:700">
          {{ $readyCount }} / {{ $totalItems }} {{ __('ready') }}
        </span>
      </div>
      @unless($locked)
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <button type="button" id="btn-autofill-readiness" class="btn ter sm">{{ __('Auto-fill verified items') }}</button>
          <button type="button" id="btn-mark-all-ready" class="btn ter sm">{{ __('Mark all Yes') }}</button>
        </div>
      @endunless
    </div>

    <div style="display:flex;flex-direction:column;gap:10px">
      @foreach($cfg['readiness'] as $k => $l)
        @php
          $r = $ready[$k] ?? null;
          $curStatus = $r->status ?? 'not_yet';
          $hint = $autoHints[$k] ?? null;
          $borderColor = $curStatus === 'yes' ? '#0e6027' : ($curStatus === 'na' ? '#64748b' : '#b28600');
        @endphp
        <div class="readiness-row" data-rkey="{{ $k }}" style="padding:12px 14px;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-left:4px solid {{ $borderColor }};border-radius:4px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <strong style="font-size:13.5px;color:var(--ink)">{{ __($l) }}</strong>
              @if($hint && !empty($hint['ready']))
                <span class="tag info" style="font-size:10.5px">{{ __('Verified from proposal') }}</span>
              @endif
            </div>

            {{-- 1-Click Segmented Radio Group --}}
            <div class="seg-group" role="radiogroup" aria-label="{{ __($l) }}" style="display:inline-flex;border:1px solid var(--color-card-border, #090a0f);border-radius:4px;overflow:hidden">
              @foreach(['yes' => __('Yes'), 'not_yet' => __('Not yet'), 'na' => __('N/A')] as $sv => $sl)
                @php($active = $curStatus === $sv)
                <label class="seg-opt" data-val="{{ $sv }}" style="display:inline-flex;align-items:center;justify-content:center;padding:4px 12px;font-size:12px;font-weight:600;cursor:pointer;margin:0;border-right:{{ $sv !== 'na' ? '1px solid var(--hairline)' : '0' }};background:{{ $active ? ($sv === 'yes' ? '#0e6027' : ($sv === 'not_yet' ? '#b28600' : '#334155')) : '#ffffff' }};color:{{ $active ? '#ffffff' : 'var(--ink)' }}">
                  <input type="radio" name="ready[{{ $k }}][status]" value="{{ $sv }}" @checked($active) class="readiness-radio" style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none">
                  <span>{{ $sl }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <div>
            @if($k === 'facilitators' && !$locked)
              @php($mcPtjCode = \Illuminate\Support\Facades\DB::table('ptjs')->where('id', $v->ptj_id)->value('code') ?? 'Faculty')
              <div style="display:flex;gap:8px;align-items:center">
                <input type="text" name="ready[{{ $k }}][note]" value="{{ $r->note ?? '' }}" class="readiness-note" placeholder="{{ __('Planned lead lecturer(s), co-facilitators, or industry trainers') }}" aria-label="Evidence note for {{ $l }}" style="flex:1;min-width:0;max-width:100%">
                <button type="button" id="btn-open-fac-modal" class="btn ter sm" style="white-space:nowrap;flex-shrink:0">+ {{ __('Add lecturer') }}</button>
              </div>
              <div id="fac-selected-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px"></div>
              <div class="small muted" style="margin-top:4px">
                {{ __('Lists planned teaching staff for proposal review. Formal facilitator invitations are sent when creating a cohort.') }}
              </div>
            @else
              <input type="text" name="ready[{{ $k }}][note]" value="{{ $r->note ?? '' }}" class="readiness-note" placeholder="{{ __('Optional evidence or readiness note...') }}" aria-label="Evidence note for {{ $l }}" style="width:100%;max-width:100%">
            @endif
          </div>
        </div>
      @endforeach
    </div>

    @unless($locked)
      <div style="display:flex;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid var(--hairline)">
        <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
      </div>
    @endunless
  </fieldset>
</form>

@unless($locked)
  @php($mcPtjCode = \Illuminate\Support\Facades\DB::table('ptjs')->where('id', $v->ptj_id)->value('code') ?? 'Faculty')
  <div class="scrim" id="modal-fac-picker" hidden style="display:none;position:fixed;inset:0;background:rgba(9,10,15,0.52);z-index:80;align-items:center;justify-content:center;padding:16px">
    <div class="modal" role="dialog" aria-modal="true" aria-label="{{ __('Select UTeM Facilitators') }}" style="width:100%;max-width:560px;background:#ffffff;border:1px solid #090a0f;border-radius:6px;overflow:hidden;box-shadow:0 20px 50px rgba(9,10,15,0.25)" data-ptj-id="{{ $v->ptj_id }}" data-min-mqf="{{ $v->proposed_mqf_level ?? '' }}" data-url="{{ route('lecturers.search') }}">
      <div class="mh" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--hairline);background:var(--surface, #f8fafc)">
        <div>
          <h2 style="margin:0;font-size:16px">{{ __('Select UTeM Facilitators') }}</h2>
          <div class="small muted" style="margin-top:2px">
            {{ __('Default:') }} {{ $mcPtjCode }}@if(!empty($v->proposed_mqf_level)) &middot; {{ __('MQF :lvl+ eligible', ['lvl' => $v->proposed_mqf_level]) }}@endif
          </div>
        </div>
        <button type="button" id="btn-close-fac-modal" aria-label="{{ __('Close') }}" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--hairline);background:#ffffff;border-radius:4px;font-size:18px;cursor:pointer;color:var(--ink)">&times;</button>
      </div>

      <div class="mb" style="padding:14px 18px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
          <input type="search" id="fac-search-input" autocomplete="off" placeholder="{{ __('Search by name, staff ID, or email...') }}" style="flex:1;min-width:220px">
          <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;cursor:pointer;margin:0;white-space:nowrap">
            <input type="checkbox" id="fac-all-ptj" style="margin:0">
            <span>{{ __('All faculties') }}</span>
          </label>
        </div>

        <div id="fac-search-results" style="border:1px solid var(--hairline);border-radius:4px;max-height:280px;overflow-y:auto;background:#ffffff"></div>
      </div>

      <div class="mf" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--hairline);background:var(--surface, #f8fafc)">
        <span class="small muted" id="fac-modal-count">0 {{ __('selected') }}</span>
        <button type="button" id="btn-done-fac-modal" class="btn sm">{{ __('Done') }}</button>
      </div>
    </div>
  </div>
@endunless

<script>
(function initReadinessTab() {
  var form = document.getElementById('readiness-form');
  if (!form) return;
  var autoHints = @json($autoHints ?? []);
  var totalItems = {{ $totalItems }};
  var badge = document.getElementById('readiness-progress-badge');

  function styleRow(row) {
    var checked = row.querySelector('.readiness-radio:checked');
    var val = checked ? checked.value : 'not_yet';
    row.style.borderLeftColor = val === 'yes' ? '#0e6027' : (val === 'na' ? '#64748b' : '#b28600');
    row.querySelectorAll('.seg-opt').forEach(function (lbl) {
      var lv = lbl.getAttribute('data-val');
      var on = lv === val;
      lbl.style.background = on ? (lv === 'yes' ? '#0e6027' : (lv === 'not_yet' ? '#b28600' : '#334155')) : '#ffffff';
      lbl.style.color = on ? '#ffffff' : 'var(--ink)';
    });
  }

  function updateProgress() {
    var ready = 0;
    form.querySelectorAll('.readiness-row').forEach(function (row) {
      styleRow(row);
      var checked = row.querySelector('.readiness-radio:checked');
      if (checked && (checked.value === 'yes' || checked.value === 'na')) ready++;
    });
    if (badge) {
      badge.textContent = ready + ' / ' + totalItems + ' {{ __("ready") }}';
      badge.className = 'tag ' + (ready === totalItems ? 'ok' : 'warn');
    }
  }

  form.querySelectorAll('.readiness-radio').forEach(function (r) {
    r.addEventListener('change', updateProgress);
  });

  var btnAuto = document.getElementById('btn-autofill-readiness');
  if (btnAuto) {
    btnAuto.addEventListener('click', function () {
      Object.keys(autoHints).forEach(function (k) {
        var h = autoHints[k];
        if (!h || !h.ready) return;
        var row = form.querySelector('.readiness-row[data-rkey="' + k + '"]');
        if (!row) return;
        var yesRadio = row.querySelector('.readiness-radio[value="yes"]');
        if (yesRadio) yesRadio.checked = true;
        var note = row.querySelector('.readiness-note');
        if (note && !note.value.trim() && h.note) note.value = h.note;
      });
      updateProgress();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  var btnAll = document.getElementById('btn-mark-all-ready');
  if (btnAll) {
    btnAll.addEventListener('click', function () {
      form.querySelectorAll('.readiness-row').forEach(function (row) {
        var k = row.getAttribute('data-rkey');
        var yesRadio = row.querySelector('.readiness-radio[value="yes"]');
        if (yesRadio) yesRadio.checked = true;
        var note = row.querySelector('.readiness-note');
        if (note && !note.value.trim() && autoHints[k] && autoHints[k].note) {
          note.value = autoHints[k].note;
        }
      });
      updateProgress();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  var facModal = document.getElementById('modal-fac-picker');
  var facDialog = facModal ? facModal.querySelector('.modal') : null;
  var facOpenBtn = document.getElementById('btn-open-fac-modal');
  var facCloseBtn = document.getElementById('btn-close-fac-modal');
  var facDoneBtn = document.getElementById('btn-done-fac-modal');
  var facInput = document.getElementById('fac-search-input');
  var facAllPtj = document.getElementById('fac-all-ptj');
  var facResults = document.getElementById('fac-search-results');
  var facModalCount = document.getElementById('fac-modal-count');
  var facChips = document.getElementById('fac-selected-chips');
  var facRow = form.querySelector('.readiness-row[data-rkey="facilitators"]');
  var facNote = facRow ? facRow.querySelector('.readiness-note') : null;

  function getSelectedFacNames() {
    if (!facNote || !facNote.value.trim()) return [];
    return facNote.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
  }

  function setSelectedFacNames(list) {
    if (!facNote) return;
    facNote.value = list.join(', ');
    renderFacChips();
    var yesRadio = facRow ? facRow.querySelector('.readiness-radio[value="yes"]') : null;
    if (list.length > 0 && yesRadio) yesRadio.checked = true;
    updateProgress();
    form.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function renderFacChips() {
    var names = getSelectedFacNames();
    if (facModalCount) facModalCount.textContent = names.length + ' {{ __("selected") }}';
    if (!facChips) return;
    facChips.innerHTML = '';
    names.forEach(function (name) {
      var chip = document.createElement('span');
      chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:3px 9px;background:#edf4ff;border:1px solid #0052ff;border-radius:4px;font-size:12px;font-weight:500;color:#0b1a3d';
      chip.innerHTML = '<span></span><button type="button" aria-label="Remove" style="border:0;background:none;color:#da1e28;font-weight:700;cursor:pointer;padding:0 2px;font-size:14px;line-height:1">&times;</button>';
      chip.querySelector('span').textContent = name;
      chip.querySelector('button').addEventListener('click', function () {
        setSelectedFacNames(getSelectedFacNames().filter(function (n) { return n !== name; }));
      });
      facChips.appendChild(chip);
    });
  }

  if (facNote) {
    facNote.addEventListener('input', renderFacChips);
    renderFacChips();
  }

  if (facModal && facDialog && facOpenBtn && facInput && facResults) {
    var facTimer = null;

    function fetchLecturers() {
      var url = new URL(facDialog.getAttribute('data-url'), location.origin);
      url.searchParams.set('q', facInput.value.trim());
      if (facDialog.getAttribute('data-ptj-id')) url.searchParams.set('ptj_id', facDialog.getAttribute('data-ptj-id'));
      if (facAllPtj && facAllPtj.checked) url.searchParams.set('all_ptj', '1');
      if (facDialog.getAttribute('data-min-mqf')) url.searchParams.set('min_mqf', facDialog.getAttribute('data-min-mqf'));
      facResults.innerHTML = '<div class="small muted" style="padding:14px;text-align:center">{{ __("Searching...") }}</div>';
      fetch(url.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          var rows = (json && json.data) ? json.data : [];
          var picked = getSelectedFacNames();
          facResults.innerHTML = '';
          if (!rows.length) {
            facResults.innerHTML = '<div class="small muted" style="padding:16px;text-align:center">{{ __("No matching lecturers found.") }}</div>';
            return;
          }
          rows.forEach(function (u) {
            var isPicked = picked.indexOf(u.name) !== -1;
            var b = document.createElement('button');
            b.type = 'button';
            b.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:10px;width:100%;text-align:left;padding:10px 14px;border:0;border-bottom:1px solid #e2e8f0;background:' + (isPicked ? '#edfcf0' : '#ffffff') + ';color:#090a0f;font-size:13px;cursor:pointer';
            var meta = (u.ptj_code || 'UTeM') + (u.max_mqf_level ? ' · ≤ MQF ' + u.max_mqf_level : '');
            var rightBadge = isPicked
              ? '<span class="tag ok" style="font-size:11px">{{ __("Selected") }}</span>'
              : '<span class="tag info" style="font-size:11px">' + meta + '</span>';
            b.innerHTML = '<span><strong>' + u.name + '</strong><span class="muted small" style="display:block">' + (u.institution_id || u.email) + '</span></span>' + rightBadge;
            b.addEventListener('click', function () {
              var cur = getSelectedFacNames();
              if (cur.indexOf(u.name) !== -1) {
                setSelectedFacNames(cur.filter(function (n) { return n !== u.name; }));
              } else {
                cur.push(u.name);
                setSelectedFacNames(cur);
              }
              fetchLecturers();
            });
            facResults.appendChild(b);
          });
        });
    }

    function openFacModal() {
      facModal.removeAttribute('hidden');
      facModal.style.display = 'flex';
      facInput.value = '';
      renderFacChips();
      fetchLecturers();
      setTimeout(function () { facInput.focus(); }, 40);
    }

    function closeFacModal() {
      facModal.style.display = 'none';
      facModal.setAttribute('hidden', '');
      facOpenBtn.focus();
    }

    facOpenBtn.addEventListener('click', openFacModal);
    if (facCloseBtn) facCloseBtn.addEventListener('click', closeFacModal);
    if (facDoneBtn) facDoneBtn.addEventListener('click', closeFacModal);
    facModal.addEventListener('click', function (e) { if (e.target === facModal) closeFacModal(); });
    facModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeFacModal(); });
    facInput.addEventListener('input', function () { clearTimeout(facTimer); facTimer = setTimeout(fetchLecturers, 180); });
    if (facAllPtj) facAllPtj.addEventListener('change', fetchLecturers);
  }

  updateProgress();
})();
</script>
