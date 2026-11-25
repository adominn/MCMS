<form method="post" action="{{ route('proposals.save', [$v->id, 'assessment']) }}" class="card" data-autosave id="assessment-form">@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">

  @if($los->isEmpty() || $modules->isEmpty())
    <div class="note warn" role="status" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <span>
        @if($los->isEmpty() && $modules->isEmpty())
          {{ __('Add learning outcomes and modules first so you can align assessment components to outcomes and modules.') }}
        @elseif($los->isEmpty())
          {{ __('Add learning outcomes first so you can align each assessment component to its target outcomes.') }}
        @else
          {{ __('No modules added yet. Add modules first if you plan to link assessments to individual modules.') }}
        @endif
      </span>
      <span style="display:inline-flex;gap:8px;flex-wrap:wrap">
        @if($los->isEmpty())
          <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => 'outcomes']) }}" class="btn ter sm">{{ __('Go to Learning outcomes') }}</a>
        @endif
        @if($modules->isEmpty())
          <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => 'modules']) }}" class="btn ter sm">{{ __('Go to Modules') }}</a>
        @endif
      </span>
    </div>
  @endif

  @if($v->category === 'umc')
    @if($v->credit_model === 'separate_assessment')
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0052ff);padding:12px 14px;border-radius:4px;margin-bottom:18px;font-size:12.5px;color:var(--ink)">
        <b>{{ __('Active Unbundling Model (c) Separate Learning & Assessment:') }}</b>
        {{ __('Please ensure integrated summative assessment components are mapped to the designated Assessment Module.') }}
      </div>
    @elseif($v->credit_model === 'credit_based')
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0052ff);padding:12px 14px;border-radius:4px;margin-bottom:18px;font-size:12.5px;color:var(--ink)">
        <b>{{ __('Active Unbundling Model (a) Credit-Based:') }}</b>
        {{ __('Each module has its own credit value (≥ 40 hours SLT) and encompasses independent assessments.') }}
      </div>
    @elseif($v->credit_model === 'topic_based')
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0052ff);padding:12px 14px;border-radius:4px;margin-bottom:18px;font-size:12.5px;color:var(--ink)">
        <b>{{ __('Active Unbundling Model (b) Topic-Based:') }}</b>
        {{ __('Modules are formed by learning topic clusters with flexible student learning time distribution.') }}
      </div>
    @endif
  @endif

  {{-- Assessment Standards & Governance --}}
  <div style="margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid var(--hairline)">
    <h2 style="font-size:16px;font-weight:700;color:var(--ink);margin:0 0 14px">{{ __('Assessment Standards & Integrity') }}</h2>

    <div class="field" style="margin-bottom:14px">
      <label for="assessment_standard" style="font-weight:600;margin-bottom:4px;display:block">{{ __('Pass or completion standard') }}</label>
      <textarea id="assessment_standard" name="assessment_standard" rows="2" style="width:100%;max-width:100%">{{ $v->assessment_standard }}</textarea>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
      <div class="field" style="margin:0">
        <label for="integrity_mechanism" style="font-weight:600;margin-bottom:4px;display:block">{{ __('Identity and integrity mechanism') }}</label>
        <textarea id="integrity_mechanism" name="integrity_mechanism" rows="3" style="width:100%;max-width:100%">{{ $v->integrity_mechanism }}</textarea>
      </div>
      <div class="field" style="margin:0">
        <label for="feedback_method" style="font-weight:600;margin-bottom:4px;display:block">{{ __('Feedback to participants') }}</label>
        <textarea id="feedback_method" name="feedback_method" rows="3" style="width:100%;max-width:100%">{{ $v->feedback_method }}</textarea>
      </div>
    </div>
  </div>

  {{-- Assessment Components & LO Alignment Cards --}}
  @php
    $totalWeight = (float) $comps->sum('weight_percent');
    $weightOk = abs($totalWeight - 100.0) <= 0.001;
  @endphp
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
      <h2 style="font-size:16px;font-weight:700;color:var(--ink);margin:0">{{ __('Components and alignment') }}</h2>
      <span class="tag {{ $weightOk ? 'ok' : 'err' }}" id="assess-weight-badge" style="font-family:var(--mono);font-weight:700">
        {{ __('Total:') }} {{ mcms_num($totalWeight, 1) }}% / 100%
      </span>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">
      @forelse($comps as $idx => $c)
        @php($linked = ($links[$c->id] ?? collect())->pluck('learning_outcome_id')->all())
        <div class="assess-card" style="border:1px solid var(--color-card-border, #090a0f);border-left:4px solid #0052ff;border-radius:4px;background:var(--card-bg, #ffffff);overflow:hidden">
          {{-- Component Header Bar --}}
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:9px 14px;background:var(--surface, #f8fafc);border-bottom:1px solid var(--hairline)">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1">
              <span style="display:inline-flex;align-items:center;padding:3px 9px;border-radius:3px;background:#0b1a3d;color:#ffffff;font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:0.4px;flex-shrink:0">
                {{ __('COMPONENT :num', ['num' => $idx + 1]) }}
              </span>
              <strong class="assess-title-preview" style="font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $c->name_en ?: __('Untitled Component') }}
              </strong>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
              <span class="tag info assess-weight-pill" style="font-family:var(--mono);font-weight:600">{{ mcms_num($c->weight_percent, 1) }}%</span>
              @unless($locked)
                <button type="submit" name="remove" value="{{ $c->id }}" class="btn ter sm" style="color:#da1e28">{{ __('Remove') }}</button>
              @endunless
            </div>
          </div>

          {{-- Component Body --}}
          <div style="padding:14px 16px;display:flex;flex-direction:column;gap:12px">
            <div style="display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:12px">
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Component name') }}</label>
                <input type="text" name="rows[{{ $c->id }}][name_en]" value="{{ $c->name_en }}" class="assess-name-input" placeholder="{{ __('e.g. Practical Lab or Capstone Project') }}" aria-label="Component name" style="width:100%;max-width:100%">
              </div>
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Assessed module') }}</label>
                <select name="rows[{{ $c->id }}][mc_module_id]" aria-label="Module assessed by {{ $c->name_en ?: 'the component' }}" style="width:100%;max-width:100%">
                  <option value="">{{ __('Whole micro-credential') }}</option>
                  @foreach($modules as $m)
                    <option value="{{ $m->id }}" @selected($c->mc_module_id == $m->id)>
                      Module {{ $m->seq }}{{ $m->name_en ? ': '.$m->name_en : '' }}@if($m->is_assessment) ({{ __('Integrated Assessment Module') }})@endif
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px">
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Weight (%)') }}</label>
                <input type="text" inputmode="decimal" name="rows[{{ $c->id }}][weight_percent]" value="{{ mcms_num($c->weight_percent, 1) }}" class="assess-weight-input" aria-label="Weight in percent" style="width:100%;font-family:var(--mono);font-weight:600">
              </div>
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Mark column') }}</label>
                <input type="text" name="rows[{{ $c->id }}][csv_key]" value="{{ $c->csv_key }}" placeholder="module1_mark" aria-label="Mark column of the component" style="width:100%;font-family:var(--mono)">
              </div>
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Max mark') }}</label>
                <input type="text" inputmode="decimal" name="rows[{{ $c->id }}][max_mark]" value="{{ mcms_num($c->max_mark) }}" aria-label="Maximum mark" style="width:100%;font-family:var(--mono)">
              </div>
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Pass mark') }}</label>
                <input type="text" inputmode="decimal" name="rows[{{ $c->id }}][pass_mark]" value="{{ mcms_num($c->pass_mark) }}" aria-label="Pass mark" style="width:100%;font-family:var(--mono)">
              </div>
            </div>

            {{-- Aligned Learning Outcomes --}}
            <div style="padding:10px 12px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px">
              <div style="font-size:11px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#475569;margin-bottom:6px">
                {{ __('Aligned Learning Outcomes') }}
              </div>
              @if($los->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                  @foreach($los as $lo)
                    <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;padding:4px 10px;background:#ffffff;border:1px solid var(--hairline);border-radius:4px;font-size:12.5px">
                      <input type="checkbox" name="align[{{ $c->id }}][]" value="{{ $lo->id }}" @checked(in_array($lo->id, $linked)) aria-label="{{ $c->name_en ?: 'Component' }} assesses {{ $lo->code }}" style="margin:0">
                      <strong style="font-family:var(--mono)">{{ $lo->code }}</strong>
                    </label>
                  @endforeach
                </div>
              @else
                <span class="small muted">
                  {{ __('No learning outcomes yet.') }}
                  <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => 'outcomes']) }}">{{ __('Add learning outcomes first') }}</a>
                </span>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="muted" style="text-align:center;padding:20px;border:1px dashed var(--hairline);border-radius:4px">
          {{ __('No assessment components yet. Click "Add component" below to begin.') }}
        </div>
      @endforelse
    </div>

    {{-- Total Weighting Bar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;padding:10px 16px;background:var(--surface, #f8fafc);border:1px solid var(--color-card-border, #090a0f);border-radius:4px">
      <strong style="font-size:13.5px">{{ __('Total Assessment Weighting') }}</strong>
      <strong id="assess-weight-total" style="font-family:var(--mono);font-size:15px;color:{{ $weightOk ? '#0e6027' : '#da1e28' }}">{{ mcms_num($totalWeight, 1) }}%</strong>
    </div>
  </div>

  @unless($locked)
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:18px;padding-top:14px;border-top:1px solid var(--hairline);flex-wrap:wrap;gap:12px">
      <button type="submit" name="action" value="add" class="btn ter sm">+ {{ __('Add component') }}</button>
      <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button>
    </div>
  @endunless
  </fieldset>
</form>

<script>
(function initAssessmentTab() {
  var form = document.getElementById('assessment-form');
  if (!form) return;
  var weightInputs = Array.prototype.slice.call(form.querySelectorAll('.assess-weight-input'));
  var totalEl = document.getElementById('assess-weight-total');
  var badgeEl = document.getElementById('assess-weight-badge');

  function recalcWeight() {
    var sum = 0;
    weightInputs.forEach(function (inp) {
      var v = parseFloat(inp.value) || 0;
      sum += v;
      var card = inp.closest('.assess-card');
      var pill = card ? card.querySelector('.assess-weight-pill') : null;
      if (pill) pill.textContent = (Math.round(v * 10) / 10) + '%';
    });
    var rounded = Math.round(sum * 10) / 10;
    var ok = Math.abs(rounded - 100) <= 0.01;
    if (totalEl) {
      totalEl.textContent = rounded + '%';
      totalEl.style.color = ok ? '#0e6027' : '#da1e28';
    }
    if (badgeEl) {
      badgeEl.textContent = '{{ __("Total:") }} ' + rounded + '% / 100%';
      badgeEl.className = 'tag ' + (ok ? 'ok' : 'err');
    }
  }

  weightInputs.forEach(function (inp) { inp.addEventListener('input', recalcWeight); });
  form.querySelectorAll('.assess-name-input').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var card = this.closest('.assess-card');
      var prev = card ? card.querySelector('.assess-title-preview') : null;
      if (prev) prev.textContent = this.value.trim() || @json(__('Untitled Component'));
    });
  });
})();
</script>
