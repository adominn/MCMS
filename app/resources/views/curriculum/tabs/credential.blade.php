<form method="post" action="{{ route('proposals.save', [$v->id, 'credential']) }}" class="card" enctype="multipart/form-data" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  @php
    $sel = json_decode($v->credential_types ?? '[]', true);
    $hasBadge = in_array('badge', $sel);
  @endphp

  <!-- Credential types -->
  <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface);margin-bottom:20px">
    <legend style="font-size:13px;font-weight:600;padding:0 6px">{{ __('Credential types') }}</legend>
    <div style="display:flex;flex-wrap:wrap;gap:12px 20px">
      @foreach($cfg['credential_types'] as $k => $l)
        <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0">
          <input type="checkbox" name="credential_types[]" value="{{ $k }}" @checked(in_array($k, $sel)) @if($k === 'badge') id="cred-type-badge" @endif> 
          <span>{{ __($l) }}</span>
        </label>
      @endforeach
    </div>
    <div class="s muted" style="margin-top:8px">
      {{ __('Tick "Digital Badge" if this micro-credential awards Open Badges 3.0 for modular achievements.') }}
    </div>
  </fieldset>

  <!-- Digital Badge Specification & Custom Artwork (Visible only when Digital Badge is ticked) -->
  <div id="badge-config-section" style="{{ $hasBadge ? '' : 'display:none;' }};margin-bottom:20px">
    <!-- Digital Badge Preview Card for Lecturer -->
    <div class="card" style="border:1px solid var(--hairline);border-left:4px solid var(--accent, #0f62fe);padding:18px 20px;background:var(--card-bg);margin-bottom:16px">
      <div style="font-size:11px;font-weight:700;color:var(--accent, #0f62fe);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px">
        {{ __('Open Badges 3.0 &middot; Digital Badge Specification') }}
      </div>
      <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <x-badge :category="$v->category" :size="72" :image="$v->badge_image_path ?? null" />
        <div style="flex:1;min-width:240px">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
            <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--ink)">{{ $v->name_en ?: __('Micro-Credential Badge') }}</h3>
            @if(!empty($v->badge_image_path))
              <span class="tag ok" style="font-size:10px">{{ __('Custom badge') }}</span>
            @else
              <span class="tag muted" style="font-size:10px">{{ __('Standard UTeM template') }}</span>
            @endif
          </div>
          <div class="small muted" style="line-height:1.6">
            <b>{{ __('Awarded by:') }}</b> Universiti Teknikal Malaysia Melaka (UTeM) &middot; CODL<br>
            <b>{{ __('Format:') }}</b> Open Badges 3.0 &middot; W3C Verifiable Credential (Ed25519 Cryptographic Signature)<br>
            <b>{{ __('Recipient experience:') }}</b> {{ __('Module digital badges are minted automatically upon verified module assessment.') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Badge Artwork Upload Section -->
    <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface);margin-bottom:16px">
      <legend style="font-size:13px;font-weight:600;padding:0 6px">{{ __('Custom Badge Graphic') }}</legend>
      <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
        <div style="text-align:center;flex-shrink:0">
          <div style="width:72px;height:72px;background:var(--card-bg);border:1px dashed var(--hairline);display:flex;align-items:center;justify-content:center;margin-bottom:4px">
            <x-badge :category="$v->category" :size="56" :image="$v->badge_image_path ?? null" />
          </div>
          <div class="s muted" style="font-size:11px">{{ !empty($v->badge_image_path) ? __('Active artwork') : __('Default SVG') }}</div>
        </div>
        <div style="flex:1;min-width:260px">
          <label for="badge_image" style="font-weight:600;font-size:12px;margin-top:0;margin-bottom:4px;display:block">
            {{ __('Upload custom badge image (.png, .svg, .webp)') }}
          </label>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:6px">
            <input type="file" id="badge_image" name="badge_image" accept=".png,.jpg,.jpeg,.svg,.webp" style="font-size:12px;padding:4px;background:var(--card-bg);border:1px solid var(--hairline)">
            @if(!empty($v->badge_image_path))
              <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--error);margin:0;cursor:pointer">
                <input type="checkbox" name="remove_badge_image" value="1">
                <span>{{ __('Remove custom artwork and revert to default UTeM badge') }}</span>
              </label>
            @endif
          </div>
          <div class="s muted" style="font-size:11px;line-height:1.4">
            {{ __('Lecturers can provide custom artwork for course-specific branding or partner logos (e.g. AWS, Cisco). Recommended: 1:1 aspect ratio square or circular graphic with transparent background (minimum 400x400 px, max 2MB). If omitted, the official UTeM category badge is used automatically.') }}
          </div>
        </div>
      </div>
    </fieldset>
  </div>

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

<script>
  (function () {
    var badgeCheckbox = document.getElementById('cred-type-badge');
    var badgeSection = document.getElementById('badge-config-section');
    if (!badgeCheckbox || !badgeSection) return;

    badgeCheckbox.addEventListener('change', function () {
      badgeSection.style.display = this.checked ? '' : 'none';
    });
  })();
</script>
