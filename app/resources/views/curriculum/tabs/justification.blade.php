<form method="post" action="{{ route('proposals.save', [$v->id, 'justification']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
    <div class="field" style="margin-bottom:16px">
      <label for="need">{{ __('Need or problem addressed') }}</label>
      <textarea id="need" name="need" rows="4">{{ $v->need }}</textarea>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="category_justification">{{ __('Category Selection Justification') }}</label>
      <textarea id="category_justification" name="category_justification" rows="3" placeholder="{{ __('State why UMC, SAMC, or MCE was chosen based on target learners, credit articulation, and curriculum requirements.') }}">{{ $v->category_justification }}</textarea>
    </div>

    <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface);margin-bottom:16px">
      <legend style="font-size:13px;font-weight:600;padding:0 6px">{{ __('Evidence of need') }}</legend>
      @php($sel = json_decode($v->evidence_types ?? '[]', true))
      <div style="display:flex;flex-wrap:wrap;gap:12px 20px">
        @foreach($cfg['evidence_types'] as $k => $l)
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0">
            <input type="checkbox" name="evidence_types[]" value="{{ $k }}" @checked(in_array($k, $sel))> 
            <span>{{ __($l) }}</span>
          </label>
        @endforeach
      </div>
    </fieldset>

    <div class="field" style="margin-bottom:16px">
      <label for="existing_equivalents">{{ __('Analysis of Existing Offerings & Equivalent Courses') }}</label>
      <textarea id="existing_equivalents" name="existing_equivalents" rows="3" placeholder="{{ __('Identify existing internal UTeM or external market offerings and how this micro-credential differentiates or complements them.') }}">{{ $v->existing_equivalents }}</textarea>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="added_value">{{ __('Added value compared with existing courses or training') }}</label>
      <textarea id="added_value" name="added_value" rows="3">{{ $v->added_value }}</textarea>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="recognition_potential">{{ __('Recognition Potential by Industry & Professional Bodies') }}</label>
      <textarea id="recognition_potential" name="recognition_potential" rows="3" placeholder="{{ __('Describe prospective recognition, endorsement, or articulation by industry partners or professional bodies.') }}">{{ $v->recognition_potential }}</textarea>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label for="follow_up_action">{{ __('Intended Follow-up Action / Pathway') }}</label>
      <select id="follow_up_action" name="follow_up_action" style="max-width:440px">
        <option value="">{{ __('-- Select Follow-up Action --') }}</option>
        @foreach(config('proposals.follow_up_actions', []) as $k => $l)
          <option value="{{ $k }}" @selected($v->follow_up_action === $k)>{{ __($l) }}</option>
        @endforeach
      </select>
    </div>

    @unless($locked)
      <p style="margin:16px 0 0">
        <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
      </p>
    @endunless
  </fieldset>
</form>
