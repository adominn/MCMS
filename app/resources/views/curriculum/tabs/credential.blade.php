<form method="post" action="{{ route('proposals.save', [$v->id, 'credential']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  @php($sel = json_decode($v->credential_types ?? '[]', true))

  <!-- Digital Badge Preview Card for Lecturer -->
  <div class="card" style="border:1px solid #e0e0e0;border-left:4px solid var(--primary);padding:18px 20px;background:#ffffff;margin-bottom:20px">
    <div style="font-size:11px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px">
      {{ __('Open Badges 3.0 &middot; Digital Badge Specification') }}
    </div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <x-badge :category="$v->category" :size="72" />
      <div style="flex:1;min-width:240px">
        <h3 style="margin:0 0 4px;font-size:16px;font-weight:700;color:#161616">{{ $v->name_en ?: __('Micro-Credential Badge') }}</h3>
        <div class="small muted" style="line-height:1.6">
          <b>{{ __('Awarded by:') }}</b> Universiti Teknikal Malaysia Melaka (UTeM) &middot; CODL<br>
          <b>{{ __('Format:') }}</b> Open Badges 3.0 &middot; W3C Verifiable Credential (Ed25519 Cryptographic Signature)<br>
          <b>{{ __('Recipient experience:') }}</b> {{ in_array('badge', $sel) ? __('Module badges issued on verified completion; full digital credential issued upon faculty confirmation.') : __('Ensure "Digital Badge" is ticked below to award cryptographic Open Badges.') }}
        </div>
      </div>
    </div>
  </div>

  <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface);margin-bottom:16px">
    <legend style="font-size:13px;font-weight:600;padding:0 6px">{{ __('Credential types') }}</legend>
    <div style="display:flex;flex-wrap:wrap;gap:12px 20px">
      @foreach($cfg['credential_types'] as $k => $l)
        <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0">
          <input type="checkbox" name="credential_types[]" value="{{ $k }}" @checked(in_array($k, $sel))> 
          <span>{{ __($l) }}</span>
        </label>
      @endforeach
    </div>
  </fieldset>

  <div class="form-grid {{ $v->category === 'samc' ? 'cols-3' : 'cols-2' }}">
    <div class="field">
      <label for="achievement_level">{{ __('Achievement level') }}</label>
      <select id="achievement_level" name="achievement_level">
        <option value="">-</option>
        <option value="module" @selected($v->achievement_level === 'module')>{{ __('Module') }}</option>
        <option value="whole" @selected($v->achievement_level === 'whole')>{{ __('Whole micro-credential') }}</option>
      </select>
    </div>

    <div class="field">
      <label for="verification_method">{{ __('Verification method') }}</label>
      <input type="text" id="verification_method" name="verification_method" value="{{ $v->verification_method }}">
    </div>

    @if($v->category === 'samc')
      <div class="field">
        <label for="qv_status">{{ __('Quality Verification status') }}</label>
        <select id="qv_status" name="qv_status">
          <option value="">-</option>
          @foreach($cfg['qv_status'] as $k => $l)
            <option value="{{ $k }}" @selected($v->qv_status === $k)>{{ __($l) }}</option>
          @endforeach
        </select>
      </div>
    @endif
  </div>

  @if($v->category === 'mce')
    <p class="muted">{{ __('An MCE is non-credit. It has no credit value or MQF level.') }}</p>
  @else
    <h2 style="font-size:16px;font-weight:600;margin:20px 0 6px">{{ __('Proposed credit and MQF level') }}</h2>
    <p class="muted small" style="margin-top:0;margin-bottom:14px">{{ __('These are proposals only. They are not shown in the catalogue or on any credential until approval (:basis) confirms them.', ['basis' => $v->category === 'umc' ? 'UMC' : __('or a Quality Verification decision for a SAMC')]) }}</p>
    <div class="form-grid cols-2" style="max-width:560px">
      <div class="field">
        <label for="proposed_credit">{{ __('Proposed credit') }}</label>
        <input type="text" inputmode="decimal" id="proposed_credit" name="proposed_credit" value="{{ $v->proposed_credit !== null ? rtrim(rtrim((string) $v->proposed_credit, '0'), '.') : '' }}" placeholder="e.g. 3.0">
      </div>
      <div class="field">
        <label for="proposed_mqf_level">{{ __('Proposed MQF level (1 to 8)') }}</label>
        <input type="text" inputmode="numeric" id="proposed_mqf_level" name="proposed_mqf_level" value="{{ $v->proposed_mqf_level }}" placeholder="e.g. 6">
      </div>
    </div>
  @endif

  @unless($locked)
    <p style="margin-top:16px;margin-bottom:0">
      <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
    </p>
  @endunless
  </fieldset>
</form>
