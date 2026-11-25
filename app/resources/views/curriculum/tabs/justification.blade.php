<form method="post" action="{{ route('proposals.save', [$v->id, 'justification']) }}" enctype="multipart/form-data" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
    <h2 style="font-size:16px;font-weight:700;color:var(--ink);margin:0 0 14px">{{ __('Development Basis & Justification') }}</h2>

    <div class="field" style="margin-bottom:16px">
      <label for="need" style="font-weight:600;margin-bottom:4px;display:block">{{ __('Need or problem addressed') }}</label>
      <textarea id="need" name="need" rows="3" style="width:100%;max-width:100%">{{ $v->need }}</textarea>
    </div>

    <div class="field" style="margin-bottom:18px">
      <label for="category_justification" style="font-weight:600;margin-bottom:4px;display:block">{{ __('Category Selection Justification') }}</label>
      <textarea id="category_justification" name="category_justification" rows="3" style="width:100%;max-width:100%" placeholder="{{ __('State why UMC, SAMC, or MCE was chosen based on target learners, credit articulation, and curriculum requirements.') }}">{{ $v->category_justification }}</textarea>
    </div>

    @php
      $sel = json_decode($v->evidence_types ?? '[]', true) ?: [];
      $evData = \App\Modules\Curriculum\ProposalChecker::parseEvidenceFiles($v->evidence_files ?? null);
      $evByType = $evData['by_type'];
      $evOtherLabel = $evData['other_label'] ?? '';
      $evLegacy = $evData['legacy'];
      $evHints = [
        'industry' => __('e.g. Employer demand survey, industry advisory panel minutes, or endorsement letter'),
        'feedback' => __('e.g. Learner survey summary, course evaluation report, or alumni feedback'),
        'programme' => __('e.g. Faculty curriculum committee minutes, programme gap analysis, or senate paper'),
        'standard' => __('e.g. MQA, MBOT, BEM, IEEE, or professional body competency framework'),
        'technology' => __('e.g. Industry 4.0 technology adoption report, vendor roadmap, or market brief'),
        'other' => __('e.g. Community needs study, ministry directive, or stakeholder consultation report'),
      ];
    @endphp
    <div style="border:1px solid var(--color-card-border, #090a0f);border-radius:4px;background:var(--surface, #f8fafc);margin-bottom:18px;overflow:hidden">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:var(--card-bg, #ffffff);border-bottom:1px solid var(--hairline)">
        <strong style="font-size:13.5px;color:var(--ink)">{{ __('Evidence of need') }}</strong>
        <span class="tag info" id="ev-selected-count" style="font-family:var(--mono)">{{ count($sel) }} {{ __('selected') }}</span>
      </div>

      <div style="padding:14px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;margin-bottom:14px">
          @foreach($cfg['evidence_types'] as $k => $l)
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;padding:8px 10px;background:#ffffff;border:1px solid var(--hairline);border-radius:4px;font-size:13px">
              <input type="checkbox" class="ev-type-checkbox" data-ev-key="{{ $k }}" name="evidence_types[]" value="{{ $k }}" @checked(in_array($k, $sel, true)) style="margin:0">
              <span>{{ __($l) }}</span>
            </label>
          @endforeach
        </div>

        <div id="ev-uploads-container" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px">
          <p id="ev-empty-hint" class="small muted" style="margin:0;grid-column:1 / -1;display:{{ empty($sel) ? 'block' : 'none' }}">
            {{ __('Tick one or more evidence types above to attach supporting documents (at least one file per ticked type is required before submission).') }}
          </p>

          @foreach($cfg['evidence_types'] as $k => $l)
            @php
              $isTicked = in_array($k, $sel, true);
              $typeFiles = $evByType[$k] ?? [];
              $hasFiles = count($typeFiles) > 0;
            @endphp
            <div data-ev-slot="{{ $k }}" style="display:{{ $isTicked ? 'flex' : 'none' }};flex-direction:column;justify-content:space-between;gap:10px;padding:12px 14px;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-left:4px solid {{ $hasFiles ? '#0e6027' : '#b28600' }};border-radius:4px">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                <strong style="font-size:13px;color:var(--ink)">{{ __($l) }}</strong>
                @if($hasFiles)
                  <span class="tag ok">{{ count($typeFiles) === 1 ? __('1 file attached') : __(':count files attached', ['count' => count($typeFiles)]) }}</span>
                @else
                  <span class="tag warn">{{ __('Document required before submit') }}</span>
                @endif
              </div>

              @if($k === 'other')
                <div style="margin-bottom:10px">
                  <label for="evidence_other" style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">{{ __('Specify other evidence') }} *</label>
                  <input id="evidence_other" name="evidence_other" type="text" value="{{ old('evidence_other', $evOtherLabel) }}" placeholder="{{ __('e.g. Industry workshop consultation report') }}" style="width:100%;max-width:100%">
                </div>
              @endif

              @if($hasFiles)
                <ul style="list-style:none;padding:0;margin:0 0 10px;display:flex;flex-direction:column;gap:6px">
                  @foreach($typeFiles as $ef)
                    <li style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 10px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px;font-size:13px">
                      <a href="{{ route('proposals.evidence', [$v->id, $ef]) }}" target="_blank" rel="noopener" style="font-weight:500;word-break:break-all">{{ $ef }}</a>
                      @unless($locked)
                        <button type="submit" name="remove_evidence_file" value="{{ $k }}|{{ $ef }}" class="btn ghost sm" style="color:var(--danger, #da1e28);padding:2px 8px;flex-shrink:0">{{ __('Remove') }}</button>
                      @endunless
                    </li>
                  @endforeach
                </ul>
              @endif

              @unless($locked)
                <input id="evidence_files_{{ $k }}" name="evidence_files[{{ $k }}][]" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">
                <div class="small muted" style="margin-top:4px">{{ $evHints[$k] ?? '' }} ({{ __('PDF, PNG, JPG, DOC, DOCX — up to 3 files, max 10 MB each. Click Save to upload.') }})</div>
                @error("evidence_files.$k")
                  <div class="small" style="color:var(--danger, #da1e28);margin-top:4px">{{ $message }}</div>
                @enderror
                @error("evidence_files.$k.*")
                  <div class="small" style="color:var(--danger, #da1e28);margin-top:4px">{{ $message }}</div>
                @enderror
              @endunless
            </div>
          @endforeach

          @if(!empty($evLegacy))
            <div style="padding:10px 14px;background:#ffffff;border:1px solid var(--hairline);border-radius:4px">
              <strong style="font-size:12.5px;display:block;margin-bottom:6px">{{ __('Previously uploaded general evidence') }}</strong>
              <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px">
                @foreach($evLegacy as $ef)
                  <li style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:13px">
                    <a href="{{ route('proposals.evidence', [$v->id, $ef]) }}" target="_blank" rel="noopener">{{ $ef }}</a>
                    @unless($locked)
                      <button type="submit" name="remove_evidence_file" value="_legacy|{{ $ef }}" class="btn ghost sm" style="color:var(--danger, #da1e28);padding:2px 8px">{{ __('Remove') }}</button>
                    @endunless
                  </li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="added_value" style="font-weight:600;margin-bottom:4px;display:block">{{ __('Added value compared with existing courses or training') }}</label>
      <textarea id="added_value" name="added_value" rows="3" style="width:100%;max-width:100%">{{ $v->added_value }}</textarea>
    </div>

    @unless($locked)
      <div style="display:flex;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid var(--hairline)">
        <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
      </div>
    @endunless
  </fieldset>
</form>

<script>
(function () {
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.ev-type-checkbox'));
  var emptyHint = document.getElementById('ev-empty-hint');
  var countBadge = document.getElementById('ev-selected-count');
  function syncEvSlots() {
    var count = 0;
    boxes.forEach(function (cb) {
      var key = cb.getAttribute('data-ev-key');
      var slot = document.querySelector('[data-ev-slot="' + key + '"]');
      if (cb.checked) { count++; }
      if (slot) { slot.style.display = cb.checked ? 'flex' : 'none'; }
    });
    if (emptyHint) { emptyHint.style.display = count > 0 ? 'none' : 'block'; }
    if (countBadge) { countBadge.textContent = count + ' {{ __("selected") }}'; }
  }
  boxes.forEach(function (cb) { cb.addEventListener('change', syncEvSlots); });
  syncEvSlots();
})();
</script>
