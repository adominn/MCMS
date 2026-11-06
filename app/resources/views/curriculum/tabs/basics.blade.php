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
