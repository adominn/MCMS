@extends('layouts.app')
@section('title', __('New transfer application'))
@section('content')
<x-breadcrumb :crumbs="[
  'Credit transfer' => route('transfer'),
  'New transfer application' => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('New Credit Transfer Application') }}</h1>
    <div class="sub">{{ __('Credit Transfer Application · Apply to count deposited Academic Bank of Credit (ABC) credit toward a UTeM programme.') }}</div>
  </div>
  <div class="act">
    <a class="btn ter sm" href="{{ route('transfer') }}">&larr; {{ __('Back to applications') }}</a>
  </div>
</div>

<x-credit-lifecycle :stage="7" :note="__('Preparing credit transfer application')" />

<div class="cols">
  <div>
    <form method="post" action="{{ route('transfer.store') }}">
      @csrf

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('1. Target Programme') }}</h2></div>
        <div class="bd">
          <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
            <div>
              <label for="programme_id">{{ __('Target programme') }} *</label>
              <select id="programme_id" name="programme_id" required>
                @foreach($programmes as $p)
                  <option value="{{ $p->id }}" data-mqf="{{ $p->mqf_level }}" data-code="{{ $p->code }}" {{ (old('programme_id') == $p->id || (!old('programme_id') && $p->code === 'BITS')) ? 'selected' : '' }}>{{ $p->code }} {{ $p->name }} (MQF {{ $p->mqf_level }})</option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="institution">{{ __('Receiving institution') }}</label>
              <input id="institution" name="institution" type="text" value="{{ old('institution', 'UTeM') }}">
            </div>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('2. Credit Selection & Course Mapping') }}</h2></div>
        <div class="bd">
          <p class="small muted" style="margin-top:0;margin-bottom:14px">{{ __('Only Available credit can be selected directly. Expired credit can be selected with an automatic flag for currency-of-knowledge re-evaluation. Credit held by another active application or already redeemed cannot be reused (Single Redemption Principle).') }}</p>

          <div class="table-wrap" style="overflow:visible">
            <table id="credits" style="margin:0 0 12px; width:100%">
              <thead>
                <tr>
                  <th style="width:40px"></th>
                  <th style="min-width:180px; width:35%">{{ __('Credit (ACR & ABC)') }}</th>
                  <th style="min-width:260px; width:45%">{{ __('Course requested') }}</th>
                  <th style="width:130px; text-align:right">{{ __('Credits requested') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($deposits as $d)
                  <tr data-deposit-row data-mqf="{{ $d->acr_mqf_level ?? '' }}" data-base-selectable="{{ $d->selectable ? '1' : '0' }}">
                    <td style="vertical-align:middle; width:40px">
                      <input type="checkbox" class="dep-check" name="deposits[]" value="{{ $d->id }}" aria-label="Use {{ $d->name_en }}" @disabled(! $d->selectable)>
                    </td>
                    <td style="vertical-align:middle">
                      <b>{{ $d->name_en }}</b><br>
                      <span class="muted small"><code>{{ $d->acr_reference }}</code> &middot; {{ __(':count credits', ['count' => mcms_num($d->credits)]) }}@if($d->acr_mqf_level) &middot; MQF {{ $d->acr_mqf_level }}@endif</span>
                      @if($d->why)
                        <br><span class="tag {{ $d->selectable ? 'warn' : 'err' }}">{{ $d->selectable ? __('Expired: needs re-evaluation') : __($d->why) }}</span>
                      @endif
                      <div class="mqf-flag" style="display:none;margin-top:4px"></div>
                    </td>
                    <td style="vertical-align:middle">
                      @php($selectedCourseId = old("course.{$d->id}", $d->suggested_course_id))
                      <div class="course-combo" data-deposit="{{ $d->id }}" data-suggestions='@json($d->suggestions ?? [])' style="position:relative;width:100%;min-width:250px;max-width:380px">
                        <div style="position:relative;display:flex;align-items:center">
                          <input type="text"
                                 class="combo-input"
                                 autocomplete="off"
                                 placeholder="{{ __('Search course code or name...') }}"
                                 aria-label="Search course for {{ $d->name_en }}"
                                 style="width:100%;padding-right:30px">
                          <span style="position:absolute;right:10px;pointer-events:none;color:var(--color-ink-muted, #475569);font-size:12px">&#9662;</span>
                        </div>
                        <select name="course[{{ $d->id }}]" class="combo-select" aria-label="Course for {{ $d->name_en }}" style="display:none">
                          @foreach($courses as $c)
                            <option value="{{ $c->id }}"
                                    data-code="{{ strtoupper($c->code) }}"
                                    data-programme="{{ $c->programme_id }}"
                                    @selected((string) $selectedCourseId === (string) $c->id)>
                              {{ $c->code }} {{ $c->name }} ({{ $c->credit === 1 ? __('1 credit') : __(':count credits', ['count' => $c->credit]) }})
                            </option>
                          @endforeach
                        </select>
                        <div class="combo-menu" style="display:none;position:absolute;left:0;right:0;top:100%;margin-top:4px;max-height:240px;overflow-y:auto;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-radius:var(--radius-sm, 4px);box-shadow:var(--shadow-dropdown, 0 8px 24px rgba(9,10,15,0.12));z-index:60"></div>
                        @if(!empty($d->suggested_course_id) && !old("course.{$d->id}"))
                          <div class="combo-hint s" style="margin-top:4px;font-size:11.5px;color:var(--color-success, #0e6027)">{{ __('Auto-matched closest transferable course') }}</div>
                        @endif
                      </div>
                    </td>
                    <td style="vertical-align:middle; text-align:right">
                      <input type="number" name="credits[{{ $d->id }}]" step="0.5" min="0.5" max="{{ $d->credits }}" value="{{ old("credits.{$d->id}", $d->credits) }}" aria-label="Credits requested for {{ $d->name_en }}" style="width:90px; text-align:right">
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="muted" style="padding:16px">{{ __('You have no deposited credit. Give consent to deposit an Academic Credit Record first.') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd">
          <h2>{{ __('3. Supporting Documents Checklist') }}</h2>
          <span class="r"><span class="tag info">{{ __('Checklist') }}</span></span>
        </div>
        <div class="bd">
          <p class="small muted" style="margin-top:0;margin-bottom:10px">{{ __('Tick each supporting document attached or available in MCMS for the faculty evaluator:') }}</p>
          <div class="checklist-grid" id="f6-checklist">
            @foreach(config('transfer.documents_checklist') as $key => $label)
              <label class="checklist-item">
                <input type="checkbox" name="document_checklist[]" value="{{ $key }}" @checked(in_array($key, old('document_checklist', ['acr', 'credential', 'learning_outcomes']), true))>
                <span>{{ __($label) }}</span>
              </label>
            @endforeach
          </div>

          <div style="margin-top:14px">
            <label for="other_document">{{ __('If "Other supporting documents" is ticked, specify') }}</label>
            <input id="other_document" name="other_document" type="text" value="{{ old('other_document') }}" placeholder="{{ __('e.g. Portfolio link, syllabus PDF reference, or professional endorsement') }}">
          </div>

          <div style="margin-top:12px">
            <label for="documents">{{ __('Additional supporting notes or reference links (optional)') }}</label>
            <textarea id="documents" name="documents" rows="2" placeholder="{{ __('Optional notes or references for the faculty evaluator...') }}">{{ old('documents') }}</textarea>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn">{{ __('Submit application') }}</button>
        <a class="btn ter" href="{{ route('transfer') }}">{{ __('Cancel') }}</a>
      </div>
    </form>
  </div>

  <div>
    <div class="panel">
      <div class="hd"><h2>{{ __('What this means') }}</h2></div>
      <div class="bd">
        <p class="small" style="margin:0 0 12px">{{ __('A credit transfer is evaluated case by case by CODL and decided by Senate based on learning outcomes — never on credit value or Student Learning Time (SLT) alone.') }}</p>
        <p class="small" style="margin:0 0 12px">{{ __('At least 30% of graduating credits must be earned directly at UTeM residency, alongside any faculty transfer cap.') }}</p>
        <p class="small" style="margin:0">{{ __('An approved transfer is not programme admission, and a refused transfer leaves your ACR, credentials, and ABC credit unchanged.') }}</p>
      </div>
    </div>

    <div style="margin-top:12px">
      <a class="btn ghost sm" href="{{ route('transfer') }}">&larr; {{ __('Back to credit transfer') }}</a>
    </div>
  </div>
</div>

<script>
(function () {
  var nonTransferable = @json($nonTransferable ?? []);
  var progCourseIds = @json($progCourseIds ?? []);
  var progSelect = document.getElementById('programme_id');

  function currentProgId() {
    return progSelect ? parseInt(progSelect.value, 10) : 0;
  }

  function initCombo(wrap) {
    var input = wrap.querySelector('.combo-input');
    var select = wrap.querySelector('.combo-select');
    var menu = wrap.querySelector('.combo-menu');
    var hint = wrap.querySelector('.combo-hint');
    var suggestions = {};
    try { suggestions = JSON.parse(wrap.getAttribute('data-suggestions') || '{}'); } catch (e) {}

    function syncInputText() {
      var opt = select.options[select.selectedIndex];
      input.value = opt ? opt.text.trim() : '';
    }

    function renderMenu(filterText) {
      var pid = currentProgId();
      var blocked = nonTransferable[pid] || [];
      var inProg = progCourseIds[pid] || [];
      var suggestedId = suggestions[pid] ? String(suggestions[pid]) : '';
      var q = (filterText || '').trim().toLowerCase();

      var items = [];
      for (var i = 0; i < select.options.length; i++) {
        var o = select.options[i];
        var code = (o.getAttribute('data-code') || '').toUpperCase();
        if (blocked.indexOf(code) !== -1) { continue; }
        var cid = parseInt(o.value, 10);
        var isProg = inProg.indexOf(cid) !== -1;
        // Only show courses belonging to the selected target programme (when programme courses exist)
        if (inProg.length > 0 && !isProg) { continue; }
        var label = o.text.trim();
        if (q && label.toLowerCase().indexOf(q) === -1) { continue; }
        var isSug = String(o.value) === suggestedId;
        items.push({ value: o.value, label: label, isSug: isSug, isProg: isProg });
      }

      // Sort: Suggested first, then Target Programme courses, then remaining transferable courses
      items.sort(function (a, b) {
        if (a.isSug !== b.isSug) return a.isSug ? -1 : 1;
        if (a.isProg !== b.isProg) return a.isProg ? -1 : 1;
        return a.label.localeCompare(b.label);
      });

      menu.innerHTML = '';
      if (!items.length) {
        var empty = document.createElement('div');
        empty.style.cssText = 'padding:10px 12px;font-size:13px;color:#475569';
        empty.textContent = '{{ __("No transferable courses match your search.") }}';
        menu.appendChild(empty);
        return;
      }

      items.forEach(function (item) {
        var btn = document.createElement('button');
        btn.type = 'button';
        var selected = String(select.value) === String(item.value);
        btn.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%;text-align:left;padding:8px 12px;border:0;border-bottom:1px solid #e2e8f0;background:' + (selected ? '#edf4ff' : '#ffffff') + ';color:#090a0f;font-size:13px;cursor:pointer';
        var textSpan = document.createElement('span');
        textSpan.textContent = item.label;
        btn.appendChild(textSpan);
        if (item.isSug) {
          var badge = document.createElement('span');
          badge.className = 'tag ok';
          badge.style.fontSize = '10.5px';
          badge.textContent = '{{ __("Closest match") }}';
          btn.appendChild(badge);
        } else if (item.isProg) {
          var pBadge = document.createElement('span');
          pBadge.className = 'tag info';
          pBadge.style.fontSize = '10.5px';
          pBadge.textContent = '{{ __("In programme") }}';
          btn.appendChild(pBadge);
        }
        btn.addEventListener('mousedown', function (ev) {
          ev.preventDefault();
          select.value = item.value;
          syncInputText();
          menu.style.display = 'none';
          if (hint) { hint.style.display = item.isSug ? 'block' : 'none'; }
        });
        menu.appendChild(btn);
      });
    }

    input.addEventListener('focus', function () {
      input.select();
      renderMenu('');
      menu.style.display = 'block';
    });
    input.addEventListener('input', function () {
      renderMenu(input.value);
      menu.style.display = 'block';
    });
    input.addEventListener('blur', function () {
      setTimeout(function () {
        menu.style.display = 'none';
        syncInputText();
      }, 150);
    });

    wrap._applyProgrammeSuggestion = function () {
      var pid = currentProgId();
      if (suggestions[pid]) {
        select.value = String(suggestions[pid]);
        syncInputText();
        if (hint) { hint.style.display = 'block'; }
      }
    };

    syncInputText();
  }

  var combos = Array.prototype.slice.call(document.querySelectorAll('.course-combo'));
  combos.forEach(initCombo);

  function checkProgrammeEligibility() {
    if (!progSelect) return;
    var opt = progSelect.options[progSelect.selectedIndex];
    var progMqf = parseInt((opt && opt.getAttribute('data-mqf')) || '0', 10);
    var progCode = (opt && opt.getAttribute('data-code')) || '';
    document.querySelectorAll('tr[data-deposit-row]').forEach(function (tr) {
      var mcMqf = parseInt(tr.getAttribute('data-mqf') || '0', 10);
      var baseOk = tr.getAttribute('data-base-selectable') === '1';
      var chk = tr.querySelector('.dep-check');
      var flag = tr.querySelector('.mqf-flag');
      var mqfBlocked = mcMqf > 0 && progMqf > 0 && mcMqf < progMqf;
      if (chk) {
        chk.disabled = !baseOk || mqfBlocked;
        if (mqfBlocked) chk.checked = false;
      }
      if (flag) {
        if (mqfBlocked) {
          flag.innerHTML = '<span class="tag err">MQF ' + mcMqf + ' below ' + progCode + ' requirement (MQF ' + progMqf + ')</span>';
          flag.style.display = 'block';
        } else {
          flag.innerHTML = '';
          flag.style.display = 'none';
        }
      }
    });
  }

  if (progSelect) {
    progSelect.addEventListener('change', function () {
      combos.forEach(function (w) { if (w._applyProgrammeSuggestion) w._applyProgrammeSuggestion(); });
      checkProgrammeEligibility();
    });
    checkProgrammeEligibility();
  }
})();
</script>
@endsection
