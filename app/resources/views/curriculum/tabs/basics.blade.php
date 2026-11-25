@if(!empty($canChangeCategory))
  @php
    $catOrigin = old('origin', $v->origin === 'unbundled' ? 'unbundled' : 'own');
    $catSlt = old('slt', $v->planned_slt ?? '');
    $catCredit = old('credit', $v->credit_sought ? 'yes' : 'no');
    $catParent = old('parent_course_id', $v->parent_course_id ?? '');
    $catHasErr = $errors->hasAny(['origin', 'slt', 'credit', 'parent_course_id']);
  @endphp
  <details class="panel" style="margin-bottom:16px" @if($catHasErr) open @endif>
    <summary class="hd" style="cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div>
        <h2 style="margin:0;font-size:15px">{{ __('Category determination') }}</h2>
        <div class="small muted" style="margin-top:2px">
          {{ __('Current category:') }} <strong>{{ strtoupper($v->category) }}</strong> ({{ __($v->category_status === 'confirmed' ? 'Confirmed' : 'Proposed') }}). {{ __('Switch between UMC, SAMC, or MCE here without losing your draft.') }}
        </div>
      </div>
      <span class="r" style="display:flex;align-items:center;gap:8px;flex-shrink:0">
        <span class="tag info">{{ strtoupper($v->category) }}</span>
        <span class="btn ter sm" style="pointer-events:none">{{ __('Change category') }}</span>
      </span>
    </summary>
    <form method="post" action="{{ route('proposals.category', $v->id) }}" class="bd" style="border-top:1px solid var(--line)" id="change-category-form" data-inplace>
      @csrf
      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:14px">
        <div>
          <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">{{ __('1. Where do the modules come from?') }}</label>
          <div style="display:flex;flex-direction:column;gap:8px">
            <label style="display:inline-flex;align-items:flex-start;gap:8px;cursor:pointer;margin:0">
              <input type="radio" name="origin" value="unbundled" @checked($catOrigin === 'unbundled') style="margin-top:3px">
              <span><strong>{{ __('Modules from a UTeM credit course') }}</strong><span class="small muted" style="display:block">{{ __('Unbundling: the category is UMC') }}</span></span>
            </label>
            <label style="display:inline-flex;align-items:flex-start;gap:8px;cursor:pointer;margin:0">
              <input type="radio" name="origin" value="own" @checked($catOrigin === 'own') style="margin-top:3px">
              <span><strong>{{ __('My own offering') }}</strong><span class="small muted" style="display:block">{{ __('Not part of a credit course: SAMC or MCE by learning time') }}</span></span>
            </label>
          </div>
        </div>

        <div>
          <div class="field" style="margin-bottom:12px">
            <label for="cat_slt">{{ __('2. Total planned learning time (SLT hours)') }}</label>
            <input type="number" id="cat_slt" name="slt" min="0" max="5000" value="{{ $catSlt }}" placeholder="e.g. 80" style="max-width:180px">
            <div class="small muted" style="margin-top:4px">{{ __('A SAMC needs at least :min h. Under :min h is an MCE.', ['min' => $minSamcSlt ?? 40]) }}</div>
          </div>

          <div class="field" id="cat-credit-wrap" style="margin:0">
            <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">{{ __('3. Do you want a credit value or MQF level?') }}</label>
            <div style="display:flex;gap:16px">
              <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0">
                <input type="radio" name="credit" value="no" @checked($catCredit !== 'yes')>
                <span>{{ __('No') }}</span>
              </label>
              <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0">
                <input type="radio" name="credit" value="yes" @checked($catCredit === 'yes')>
                <span>{{ __('Yes') }}</span>
              </label>
            </div>
          </div>
          <div id="cat-mce-credit-note" class="small muted" style="display:none;margin-top:4px">
            {{ __('MCE is non-credit short learning (under :min h SLT) and cannot carry a credit value or MQF level.', ['min' => $minSamcSlt ?? 40]) }}
          </div>
        </div>
      </div>

      <div id="cat-parent-wrap" style="display:{{ $catOrigin === 'unbundled' ? 'block' : 'none' }};margin-bottom:14px">
        <label for="cat_parent_course_id">{{ __('Parent UTeM credit course (required for UMC)') }} *</label>
        <select id="cat_parent_course_id" name="parent_course_id" style="max-width:560px">
          <option value="">{{ __('-- Select parent course --') }}</option>
          @foreach($parentCourses ?? [] as $pc)
            <option value="{{ $pc->id }}" @selected((string) $catParent === (string) $pc->id)>
              {{ $pc->code }} {{ $pc->name }} ({{ $pc->credit }} cr · {{ $pc->slt_hours }}h SLT · {{ $pc->programme }})
            </option>
          @endforeach
        </select>
        @error('parent_course_id')
          <div class="small" style="color:var(--danger, #da1e28);margin-top:4px">{{ $message }}</div>
        @enderror
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 12px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px">
        <div class="small">
          {{ __('Resulting category:') }} <strong id="cat-preview-badge">{{ strtoupper($v->category) }}</strong>
          <span id="cat-preview-note" class="muted" style="margin-left:6px"></span>
        </div>
        <button type="submit" class="btn sm">{{ __('Update category') }}</button>
      </div>
    </form>
  </details>

  <script>
  (function () {
    var form = document.getElementById('change-category-form');
    if (!form) return;
    var minSlt = {{ (int) ($minSamcSlt ?? 40) }};
    var parentWrap = document.getElementById('cat-parent-wrap');
    var parentSelect = document.getElementById('cat_parent_course_id');
    var sltInput = document.getElementById('cat_slt');
    var creditWrap = document.getElementById('cat-credit-wrap');
    var mceCreditNote = document.getElementById('cat-mce-credit-note');
    var creditNo = form.querySelector('input[name="credit"][value="no"]');
    var badge = document.getElementById('cat-preview-badge');
    var note = document.getElementById('cat-preview-note');

    var txtUmc = @json(__('Unbundled from a UTeM credit course.'));
    var txtSamcCredit = @json(__('Stand-alone with credit/MQF sought (Quality Verification planned).'));
    var txtSamcPlain = @json(__('Stand-alone micro-credential (40h+ SLT).'));
    var txtMce = @json(__('Non-credit short learning (under 40h SLT). Credit & MQF fields will be cleared.'));

    function syncCategoryPreview() {
      var origin = (form.querySelector('input[name="origin"]:checked') || {}).value || 'own';
      var slt = parseInt((sltInput && sltInput.value) || '0', 10) || 0;
      var isUmc = origin === 'unbundled';
      var cat = isUmc ? 'UMC' : (slt >= minSlt ? 'SAMC' : 'MCE');

      if (parentWrap) parentWrap.style.display = isUmc ? 'block' : 'none';
      if (parentSelect) parentSelect.required = isUmc;

      // Credit / MQF option applies to SAMC only; hide and force "No" for MCE (and hide for UMC where credit comes from parent course)
      var allowCreditChoice = (cat === 'SAMC');
      if (creditWrap) creditWrap.style.display = allowCreditChoice ? '' : 'none';
      if (mceCreditNote) mceCreditNote.style.display = (cat === 'MCE') ? 'block' : 'none';
      if (cat === 'MCE' && creditNo) creditNo.checked = true;

      var credit = allowCreditChoice && ((form.querySelector('input[name="credit"]:checked') || {}).value === 'yes');
      var txt = isUmc ? txtUmc : (cat === 'SAMC' ? (credit ? txtSamcCredit : txtSamcPlain) : txtMce);

      if (badge) badge.textContent = cat;
      if (note) note.textContent = txt;
    }

    form.addEventListener('change', syncCategoryPreview);
    if (sltInput) sltInput.addEventListener('input', syncCategoryPreview);
    syncCategoryPreview();
  })();
  </script>
@endif

<form method="post" action="{{ route('proposals.save', [$v->id, 'basics']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
    <div class="form-grid cols-2">
      <div class="field">
        <label for="name_en">{{ __('Title (English)') }}</label>
        <input type="text" id="name_en" name="name_en" value="{{ $v->name_en }}">
      </div>
      <div class="field">
        <label for="name_ms">{{ __('Title (Bahasa Melayu)') }}</label>
        <input type="text" id="name_ms" name="name_ms" value="{{ $v->name_ms }}">
      </div>
    </div>

    <div class="form-grid cols-2">
      <div class="field">
        <label for="discipline">{{ __('Field or discipline') }}</label>
        <input type="text" id="discipline" name="discipline" value="{{ $v->discipline }}">
      </div>
      <div class="field">
        <label for="target_group">{{ __('Target group') }}</label>
        <input type="text" id="target_group" name="target_group" value="{{ $v->target_group }}">
      </div>
    </div>

    <div class="form-grid cols-3">
      <div class="field">
        <label for="delivery_mode">{{ __('Delivery mode') }}</label>
        <select id="delivery_mode" name="delivery_mode">
          <option value="">-</option>
          @foreach($cfg['delivery_modes'] as $k => $l)
            <option value="{{ $k }}" @selected($v->delivery_mode === $k)>{{ __($l) }}</option>
          @endforeach
        </select>
        <div id="delivery-mode-other-wrap" style="display:{{ $v->delivery_mode === 'other' ? 'block' : 'none' }};margin-top:8px">
          <label for="delivery_mode_other" style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">{{ __('Specify delivery mode') }} *</label>
          <input type="text" id="delivery_mode_other" name="delivery_mode_other" value="{{ $v->delivery_mode_other ?? '' }}" placeholder="{{ __('e.g. Industry site practicum / hybrid lab') }}">
        </div>
      </div>
      <div class="field">
        <label for="delivery_language">{{ __('Delivery language') }}</label>
        <input type="text" id="delivery_language" name="delivery_language" value="{{ $v->delivery_language }}">
      </div>
      <div class="field">
        <label for="offering_period">{{ __('Proposed offering period') }}</label>
        <input type="text" id="offering_period" name="offering_period" value="{{ $v->offering_period }}">
      </div>
    </div>

    @unless($locked)
      <p style="margin-top:16px;margin-bottom:0">
        <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
      </p>
    @endunless
  </fieldset>
</form>

<script>
(function () {
  var sel = document.getElementById('delivery_mode');
  var wrap = document.getElementById('delivery-mode-other-wrap');
  var inp = document.getElementById('delivery_mode_other');
  if (!sel || !wrap) return;
  function syncDeliveryOther() {
    var isOther = sel.value === 'other';
    wrap.style.display = isOther ? 'block' : 'none';
    if (inp) inp.required = isOther;
  }
  sel.addEventListener('change', syncDeliveryOther);
  syncDeliveryOther();
})();
</script>
