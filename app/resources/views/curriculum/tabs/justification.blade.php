<form method="post" action="{{ route('proposals.save', [$v->id, 'justification']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
    <div class="field" style="margin-bottom:16px">
      <label for="need">{{ __('Need or problem addressed') }}</label>
      <textarea id="need" name="need" rows="4">{{ $v->need }}</textarea>
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
      <label for="added_value">{{ __('Added value compared with existing courses or training') }}</label>
      <textarea id="added_value" name="added_value" rows="3">{{ $v->added_value }}</textarea>
    </div>

    @unless($locked)
      <p style="margin:16px 0 0">
        <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
      </p>
    @endunless
  </fieldset>
</form>
