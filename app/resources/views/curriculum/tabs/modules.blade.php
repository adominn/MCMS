@php
  $activeModel = $v->category === 'umc' ? ($v->credit_model ?: 'credit_based') : ($v->credit_model ?: '');
@endphp
<form method="post" action="{{ route('proposals.save', [$v->id, 'modules']) }}" class="card" data-autosave id="modules-form">@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  @if($los->isEmpty())
    <div class="note warn" role="status" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <span>{{ __('Add learning outcomes first so you can map module learning activities to each outcome.') }}</span>
      <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => 'outcomes']) }}" class="btn ter sm">{{ __('Go to Learning outcomes') }}</a>
    </div>
  @endif
  @if($v->category === 'umc')
    @php
      $parentSlt = (int) ($course->slt_hours ?? 120);
      $parentCredit = $course->credit ?? 3;
    @endphp

    @if($course)
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0052ff);padding:12px 16px;border-radius:2px;margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
          <div>
            <div style="font-size:11px;font-weight:700;color:var(--accent, #0052ff);text-transform:uppercase;letter-spacing:0.5px">
              {{ __('Parent Course Reference') }} &middot; {{ $course->programme }}
            </div>
            <div style="font-size:15px;font-weight:700;color:var(--ink);margin-top:2px">
              {{ $course->code }} {{ $course->name }}
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="tag info" style="font-weight:600">
              {{ $parentCredit }} {{ __('credits') }} &middot; {{ $parentSlt }} h SLT
            </span>
            <span class="tag {{ $course->accreditation_status === 'accredited' ? 'ok' : 'warn' }}">
              {{ $course->accreditation_status === 'accredited' ? __('Accredited Programme') : __('Not Accredited') }}
            </span>
          </div>
        </div>
        <p class="muted small" style="margin:8px 0 0;font-size:12px;line-height:1.4">
          {{ __('The sum of all module hours must strictly equal the parent course Student Learning Time (:slt hours).', ['slt' => $parentSlt]) }}
        </p>
      </div>
    @endif

    {{-- Compact UMC Unbundling Model Selector + Modal Trigger --}}
    <div style="margin-bottom:18px;padding:12px 14px;background:var(--surface, #f8fafc);border:1px solid var(--color-card-border, #090a0f);border-radius:4px">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div style="flex:1;min-width:240px">
          <label for="credit_model" style="font-size:13px;font-weight:700;color:var(--ink);display:block;margin-bottom:6px">
            {{ __('UMC Unbundling Model') }} <span style="color:#da1e28">*</span>
          </label>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <select id="credit_model" name="credit_model" aria-label="{{ __('Unbundling model') }}" style="max-width:340px;padding:6px 10px;font-size:13px;border:1px solid var(--control);background:#ffffff;color:var(--ink)">
              <option value="credit_based" @selected($activeModel === 'credit_based')>{{ __('(a) Credit-Based Model (≥ 40h SLT / module)') }}</option>
              <option value="topic_based" @selected($activeModel === 'topic_based')>{{ __('(b) Topic-Based Model (Variable SLT)') }}</option>
              <option value="separate_assessment" @selected($activeModel === 'separate_assessment')>{{ __('(c) Separate Learning & Assessment') }}</option>
            </select>
            <button type="button" id="btn-open-umc-models" class="btn ter sm">{{ __('Compare models') }}</button>
          </div>
        </div>
      </div>
    </div>

    {{-- UMC Unbundling Model Comparison Modal --}}
    <div class="scrim" id="modal-umc-models" hidden style="display:none;position:fixed;inset:0;background:rgba(9,10,15,0.52);z-index:80;align-items:center;justify-content:center;padding:16px">
      <div class="modal" role="dialog" aria-modal="true" aria-label="{{ __('Choose UMC Unbundling Model') }}" style="width:100%;max-width:720px;background:#ffffff;border:1px solid #090a0f;border-radius:6px;overflow:hidden;box-shadow:0 20px 50px rgba(9,10,15,0.25)">
        <div class="mh" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--hairline);background:var(--surface, #f8fafc)">
          <div>
            <h2 style="margin:0;font-size:16px">{{ __('Choose UMC Unbundling Model') }}</h2>
            <div class="small muted" style="margin-top:2px">{{ __('Click a model below to select it for this UMC.') }}</div>
          </div>
          <button type="button" id="btn-close-umc-models" aria-label="{{ __('Close') }}" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--hairline);background:#ffffff;border-radius:4px;font-size:18px;cursor:pointer;color:var(--ink)">&times;</button>
        </div>

        <div class="mb" style="padding:16px 18px;max-height:70vh;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
          <div class="unbundling-card {{ $activeModel === 'credit_based' ? 'active' : '' }}" data-model-val="credit_based" style="border:2px solid {{ $activeModel === 'credit_based' ? '#0052ff' : 'var(--hairline)' }};background:{{ $activeModel === 'credit_based' ? 'var(--info-bg)' : '#ffffff' }};padding:14px 16px;border-radius:4px;cursor:pointer">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px">
              <span style="display:inline-flex;align-items:center;gap:8px">
                <input type="radio" name="umc_model_radio" id="model_radio_a" value="credit_based" @checked($activeModel === 'credit_based') class="model-radio" style="margin:0">
                <label for="model_radio_a" style="margin:0;cursor:pointer"><strong style="font-size:13.5px;color:var(--ink)">{{ __('(a) Credit-Based Model') }}</strong></label>
              </span>
              <span class="tag info" style="font-size:11px;font-weight:600">{{ __('≥ 40h SLT / Module') }}</span>
            </div>
            <p class="small muted" style="margin:0 0 6px">{{ __('Each module has at least 40 hours of SLT and encompasses its own learning, activities, and assessment.') }}</p>
            <div style="font-size:11.5px;color:var(--muted)"><b>{{ __('Requirement:') }}</b> {{ __('SLT of each module ≥ 40 hours (1 credit). Self-assessment within each module.') }}</div>
          </div>

          <div class="unbundling-card {{ $activeModel === 'topic_based' ? 'active' : '' }}" data-model-val="topic_based" style="border:2px solid {{ $activeModel === 'topic_based' ? '#0052ff' : 'var(--hairline)' }};background:{{ $activeModel === 'topic_based' ? 'var(--info-bg)' : '#ffffff' }};padding:14px 16px;border-radius:4px;cursor:pointer">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px">
              <span style="display:inline-flex;align-items:center;gap:8px">
                <input type="radio" name="umc_model_radio" id="model_radio_b" value="topic_based" @checked($activeModel === 'topic_based') class="model-radio" style="margin:0">
                <label for="model_radio_b" style="margin:0;cursor:pointer"><strong style="font-size:13.5px;color:var(--ink)">{{ __('(b) Topic-Based Model') }}</strong></label>
              </span>
              <span class="tag" style="background:var(--surface);color:var(--ink);border:1px solid var(--hairline);font-size:11px;font-weight:600">{{ __('Variable SLT') }}</span>
            </div>
            <p class="small muted" style="margin:0 0 6px">{{ __('Modules are formed based on topics or learning clusters; SLT per module may vary, with total SLT strictly equal to parent course SLT.') }}</p>
            <div style="font-size:11.5px;color:var(--muted)"><b>{{ __('Requirement:') }}</b> {{ __('Flexible module SLT (< 40h allowed). Example: 120h = 30h + 25h + 25h + 20h + 20h.') }}</div>
          </div>

          <div class="unbundling-card {{ $activeModel === 'separate_assessment' ? 'active' : '' }}" data-model-val="separate_assessment" style="border:2px solid {{ $activeModel === 'separate_assessment' ? '#0052ff' : 'var(--hairline)' }};background:{{ $activeModel === 'separate_assessment' ? 'var(--info-bg)' : '#ffffff' }};padding:14px 16px;border-radius:4px;cursor:pointer">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px">
              <span style="display:inline-flex;align-items:center;gap:8px">
                <input type="radio" name="umc_model_radio" id="model_radio_c" value="separate_assessment" @checked($activeModel === 'separate_assessment') class="model-radio" style="margin:0">
                <label for="model_radio_c" style="margin:0;cursor:pointer"><strong style="font-size:13.5px;color:var(--ink)">{{ __('(c) Separate Learning & Assessment') }}</strong></label>
              </span>
              <span class="tag" style="background:var(--surface);color:var(--ink);border:1px solid var(--hairline);font-size:11px;font-weight:600">{{ __('Integrated Assessment') }}</span>
            </div>
            <p class="small muted" style="margin:0 0 6px">{{ __('Learning components are structured across several modules while integrated assessment is placed in a dedicated assessment module.') }}</p>
            <div style="font-size:11.5px;color:var(--muted)"><b>{{ __('Requirement:') }}</b> {{ __('Learning modules (content & activities) + exactly one Integrated Assessment Module.') }}</div>
          </div>
        </div>

        <div class="mf" style="display:flex;justify-content:flex-end;padding:12px 18px;border-top:1px solid var(--hairline);background:var(--surface, #f8fafc)">
          <button type="button" id="btn-done-umc-models" class="btn sm">{{ __('Done') }}</button>
        </div>
      </div>
    </div>
  @endif

  {{-- Modules Structure Cards --}}
  <div style="margin-top:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <h2 style="font-size:16px;font-weight:700;color:var(--ink);margin:0">
        {{ __('Module Structure & Student Learning Time (SLT)') }}
      </h2>
      <div id="model-status-indicator" class="small muted" style="font-size:12px"></div>
    </div>

    <div id="modules-tbody" style="display:flex;flex-direction:column;gap:16px">
      @forelse($modules as $m)
        @php
          $modActs = ($activities[$m->id] ?? collect());
          $modActSum = (int) $modActs->sum('slt_hours');
          $modSltInt = (int) $m->slt_hours;
          $modDiff = $modSltInt - $modActSum;
          $modActTagClass = $modActSum > $modSltInt ? 'err' : ($modActSum > 0 && $modDiff === 0 ? 'ok' : ($modActSum > 0 ? 'warn' : 'info'));
          $modActTagLabel = $modActSum > $modSltInt
            ? __(':act h / :slt h (exceeds by :diff h)', ['act' => $modActSum, 'slt' => $modSltInt, 'diff' => abs($modDiff)])
            : ($modActSum > 0 && $modDiff === 0
              ? __(':act h / :slt h (matched)', ['act' => $modActSum, 'slt' => $modSltInt])
              : ($modActSum > 0
                ? __(':act h / :slt h (:diff h unallocated)', ['act' => $modActSum, 'slt' => $modSltInt, 'diff' => $modDiff])
                : __(':act h / :slt h', ['act' => $modActSum, 'slt' => $modSltInt])));
        @endphp
        <div class="module-row" data-module-id="{{ $m->id }}" style="border:1px solid var(--color-card-border, #090a0f);border-left:4px solid {{ $m->is_assessment ? '#6929c4' : '#0052ff' }};border-radius:4px;background:var(--card-bg, #ffffff);overflow:hidden">
          {{-- Distinct Module Header Bar --}}
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 14px;background:var(--surface, #f8fafc);border-bottom:1px solid var(--hairline, #e2e8f0)">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1">
              <span style="display:inline-flex;align-items:center;padding:3px 9px;border-radius:3px;background:#0b1a3d;color:#ffffff;font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:0.4px;flex-shrink:0">
                {{ __('MODULE :seq', ['seq' => $m->seq]) }}
              </span>
              <strong class="module-title-preview" style="font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $m->name_en ?: __('Untitled Module') }}
              </strong>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
              <span class="tag info module-slt-badge" style="font-family:var(--mono);font-weight:600">{{ $m->slt_hours }}h SLT</span>

              <span class="assessment-module-col" style="{{ $activeModel === 'separate_assessment' ? '' : 'display:none;' }}">
                <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;margin:0;padding:3px 8px;border:1px solid var(--hairline);border-radius:3px;background:#ffffff">
                  <input type="checkbox" name="rows[{{ $m->id }}][is_assessment]" value="1" @checked($m->is_assessment) class="assessment-checkbox" aria-label="Module {{ $m->seq }} is the assessment module" style="margin:0">
                  <span class="assessment-checkbox-label" style="font-weight:{{ $m->is_assessment ? '700' : 'normal' }};color:{{ $m->is_assessment ? '#6929c4' : '#475569' }}">
                    {{ $m->is_assessment ? __('Assessment') : __('Learning') }}
                  </span>
                </label>
              </span>

              @unless($locked)
                <button type="submit" name="remove" value="{{ $m->id }}" class="btn ter sm" style="color:#da1e28">
                  {{ __('Remove') }}
                </button>
              @endunless
            </div>
          </div>

          {{-- Module Body --}}
          <div style="padding:14px 16px;display:flex;flex-direction:column;gap:12px">
            <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) 120px;gap:12px">
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Name (English)') }}</label>
                <input type="text" name="rows[{{ $m->id }}][name_en]" value="{{ $m->name_en }}" class="module-name-en-input" placeholder="e.g. Module {{ $m->seq }}: Topic Title" aria-label="Module {{ $m->seq }} name in English">
              </div>
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Name (Bahasa Melayu)') }}</label>
                <input type="text" name="rows[{{ $m->id }}][name_ms]" value="{{ $m->name_ms }}" placeholder="cth. Modul {{ $m->seq }}: Tajuk Topik" aria-label="Module {{ $m->seq }} name in Bahasa Melayu">
              </div>
              <div class="field" style="margin:0">
                <label style="font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block">{{ __('Hours (SLT)') }}</label>
                <input type="text" inputmode="numeric" name="rows[{{ $m->id }}][slt_hours]" value="{{ $m->slt_hours }}" class="module-slt-input" style="font-family:var(--mono);font-weight:600" aria-label="Module {{ $m->seq }} hours">
              </div>
            </div>

            <div class="field" style="margin:0">
              <label style="font-size:11px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#475569;display:block;margin-bottom:4px">
                {{ __('Key Content') }} &mdash; {{ __('Module :seq', ['seq' => $m->seq]) }}
              </label>
              <textarea name="rows[{{ $m->id }}][key_content]" rows="2" placeholder="{{ __('List syllabus topics, competencies, and core concepts for Module :seq...', ['seq' => $m->seq]) }}" style="font-size:12.5px;width:100%">{{ $m->key_content }}</textarea>
            </div>

            {{-- Learning Activities & SLT Breakdown (Flex/Grid rows — zero nested tables per D-67) --}}
            <div style="border:1px solid var(--hairline, #e2e8f0);background:var(--surface, #f8fafc);padding:12px 14px;border-radius:4px">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                <span style="font-size:11px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#0052ff;display:inline-flex;align-items:center;gap:8px">
                  <span>{{ __('Learning Activities & SLT Breakdown') }}</span>
                  <span class="tag {{ $modActTagClass }}" style="font-size:10.5px;font-family:var(--mono)">{{ $modActTagLabel }}</span>
                </span>
                @unless($locked)
                  <button type="submit" name="add_activity_module_id" value="{{ $m->id }}" class="btn ter sm" style="font-size:11.5px;padding:3px 10px">
                    + {{ __('Add Activity') }}
                  </button>
                @endunless
              </div>

              @if($modActs->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:6px">
                  <div style="display:grid;grid-template-columns:minmax(0,1fr) 160px 80px 130px 36px;gap:8px;padding:0 2px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--muted)">
                    <span>{{ __('Activity Title') }}</span>
                    <span>{{ __('Type') }}</span>
                    <span>{{ __('SLT (h)') }}</span>
                    <span>{{ __('Mapped LO') }}</span>
                    <span></span>
                  </div>
                  @foreach($modActs as $act)
                    <div style="display:grid;grid-template-columns:minmax(0,1fr) 160px 80px 130px 36px;gap:8px;align-items:center;padding:6px 8px;background:#ffffff;border:1px solid var(--hairline, #e2e8f0);border-radius:3px">
                      <input type="text" name="activities[{{ $act->id }}][name_en]" value="{{ $act->name_en }}" style="font-size:12px;padding:4px 8px" aria-label="{{ __('Activity Title') }}">
                      <select name="activities[{{ $act->id }}][activity_type]" style="font-size:11.5px;padding:4px 6px" aria-label="{{ __('Type') }}">
                        @foreach(config('proposals.activity_types', []) as $ak => $al)
                          <option value="{{ $ak }}" @selected($act->activity_type === $ak)>{{ __($al) }}</option>
                        @endforeach
                      </select>
                      <input type="number" name="activities[{{ $act->id }}][slt_hours]" value="{{ $act->slt_hours }}" min="0" max="1000" style="font-size:12px;padding:4px 6px;font-family:var(--mono)" aria-label="{{ __('SLT (h)') }}">
                      @if($los->isNotEmpty())
                        <select name="activities[{{ $act->id }}][learning_outcome_id]" style="font-size:11.5px;padding:4px 6px" aria-label="{{ __('Mapped LO') }}">
                          <option value="">{{ __('-- None --') }}</option>
                          @foreach($los as $alo)
                            <option value="{{ $alo->id }}" @selected($act->learning_outcome_id === $alo->id)>{{ $alo->code }}</option>
                          @endforeach
                        </select>
                      @else
                        <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => 'outcomes']) }}" class="small" style="font-size:11.5px;white-space:nowrap">{{ __('Add outcomes first') }}</a>
                      @endif
                      <div style="text-align:right">
                        @unless($locked)
                          <button type="submit" name="remove_activity_id" value="{{ $act->id }}" class="btn ter sm" style="color:#da1e28;padding:3px 8px;font-size:12px" title="{{ __('Remove activity') }}" aria-label="{{ __('Remove activity') }}">
                            &times;
                          </button>
                        @endunless
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="small muted" style="font-size:11.5px;font-style:italic">
                  {{ __('No detailed activities added for Module :seq yet. Click "+ Add Activity" to specify lecture, tutorial, practical or self-learning SLT distribution.', ['seq' => $m->seq]) }}
                </div>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="muted" style="text-align:center;padding:20px;border:1px dashed var(--hairline);border-radius:4px">
          {{ __('No modules configured yet. Click "Add module" below to begin.') }}
        </div>
      @endforelse
    </div>

    {{-- Total SLT Summary Bar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;padding:12px 16px;background:var(--surface, #f8fafc);border:1px solid var(--color-card-border, #090a0f);border-radius:4px">
      <strong style="font-size:13.5px">{{ __('Total Student Learning Time (SLT)') }}</strong>
      <div style="display:flex;align-items:center;gap:12px">
        <span class="assessment-module-col" style="{{ $activeModel === 'separate_assessment' ? '' : 'display:none;' }}">
          <span id="slt-split-display" style="font-size:11.5px;color:#475569"></span>
        </span>
        <span id="slt-sum-display" style="font-family:var(--mono);font-size:15px;font-weight:700;color:var(--ink)">
          {{ $modules->sum('slt_hours') }} h
        </span>
      </div>
    </div>
  </div>

  {{-- Live SLT Match & Validation Feedback Bar --}}
  @if($v->category === 'umc' && $course)
    <div id="slt-match-banner" style="margin-top:14px;padding:10px 14px;border-radius:2px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      {{-- Populated dynamically by JavaScript --}}
    </div>
  @endif

  @unless($locked)
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:12px">
      <button type="submit" name="action" value="add" class="btn ter sm" style="display:inline-flex;align-items:center;gap:6px">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
          <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
        </svg>
        <span>{{ __('Add module') }}</span>
      </button>

      <div style="display:flex;align-items:center;gap:8px">
        <button type="submit" name="action" value="save" class="btn sm" style="background:#0b1a3d;color:#ffffff;font-weight:600">
          {{ __('Save Modules') }}
        </button>
      </div>
    </div>
  @endunless
  </fieldset>
</form>

<script>
(function initModulesTab() {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModulesTab, { once: true });
    return;
  }
  const parentSlt = {{ (int) ($course->slt_hours ?? 120) }};
  const isUmc = {{ $v->category === 'umc' ? 'true' : 'false' }};
  const modelCards = document.querySelectorAll('.unbundling-card');
  const radios = document.querySelectorAll('.model-radio');
  const hiddenSelect = document.getElementById('credit_model');
  const assessCols = document.querySelectorAll('.assessment-module-col');
  const sltInputs = document.querySelectorAll('.module-slt-input');
  const sumDisplay = document.getElementById('slt-sum-display');
  const splitDisplay = document.getElementById('slt-split-display');
  const banner = document.getElementById('slt-match-banner');
  const statusIndicator = document.getElementById('model-status-indicator');

  function getSelectedModel() {
    const checked = document.querySelector('.model-radio:checked');
    return checked ? checked.value : 'credit_based';
  }

  function updateModelUI(model) {
    // Update card styling
    modelCards.forEach(card => {
      const radio = card.querySelector('.model-radio');
      if (radio && radio.value === model) {
        card.style.borderColor = 'var(--accent, #0052ff)';
        card.style.background = 'var(--info-bg)';
        card.classList.add('active');
        radio.checked = true;
      } else {
        card.style.borderColor = 'var(--hairline)';
        card.style.background = 'var(--card-bg)';
        card.classList.remove('active');
      }
    });

    if (hiddenSelect) {
      hiddenSelect.value = model;
    }

    // Toggle assessment module column
    const showAssess = (model === 'separate_assessment');
    assessCols.forEach(col => {
      col.style.display = showAssess ? '' : 'none';
    });

    // Update guidance indicator (UMC only — SAMC/MCE badges are updated live in recalcSLT)
    if (statusIndicator && isUmc) {
      if (model === 'credit_based') {
        statusIndicator.innerHTML = '<span class="tag info">{{ __('Model (a): ≥ 40h SLT / module') }}</span>';
      } else if (model === 'topic_based') {
        statusIndicator.innerHTML = '<span class="tag" style="background:var(--surface);color:var(--ink);border:1px solid var(--hairline)">{{ __('Model (b): Topic-based SLT (variable)') }}</span>';
      } else if (model === 'separate_assessment') {
        statusIndicator.innerHTML = '<span class="tag" style="background:var(--surface);color:var(--ink);border:1px solid var(--hairline)">{{ __('Model (c): Learning + 1 Integrated Assessment') }}</span>';
      }
    }

    recalcSLT();
  }

  function recalcSLT() {
    let sum = 0;
    let learnSum = 0;
    let assessSum = 0;
    let assessCheckedCount = 0;
    const model = getSelectedModel();
    const minSamc = {{ (int) ($minSamcSlt ?? 40) }};
    const category = @json($v->category);

    sltInputs.forEach(input => {
      const val = parseInt(input.value, 10) || 0;
      sum += val;
      const row = input.closest('.module-row');
      const badge = row ? row.querySelector('.module-slt-badge') : null;
      if (badge) badge.textContent = val + 'h SLT';
      const chk = row ? row.querySelector('.assessment-checkbox') : null;
      if (chk && chk.checked) {
        assessSum += val;
        assessCheckedCount++;
      } else {
        learnSum += val;
      }
    });

    if (sumDisplay) {
      sumDisplay.textContent = sum + ' h';
    }

    if (statusIndicator && !isUmc) {
      if (category === 'samc') {
        const ok = sum >= minSamc;
        statusIndicator.innerHTML = ok
          ? `<span class="tag ok">{{ __('SAMC minimum:') }} ≥ ${minSamc}h total SLT (${sum}h)</span>`
          : `<span class="tag err">{{ __('SAMC minimum:') }} ≥ ${minSamc}h total SLT (${sum}h — {{ __('short of') }} ${minSamc - sum}h)</span>`;
      } else if (category === 'mce') {
        const ok = sum < minSamc;
        statusIndicator.innerHTML = ok
          ? `<span class="tag ok">{{ __('MCE limit:') }} &lt; ${minSamc}h total SLT (${sum}h)</span>`
          : `<span class="tag err">{{ __('MCE limit:') }} &lt; ${minSamc}h total SLT (${sum}h)</span>`;
      }
    }

    if (splitDisplay && model === 'separate_assessment') {
      splitDisplay.textContent = `{{ __('Learning') }}: ${learnSum}h | {{ __('Assessment') }}: ${assessSum}h`;
    }

    if (!banner || !isUmc) return;

    const diff = parentSlt - sum;
    if (diff === 0) {
      banner.style.background = '#edfcf0';
      banner.style.border = '1px solid #a7f0ba';
      banner.style.color = '#0e6027';
      banner.innerHTML = `
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="#198038"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
          <span>{{ __('Total SLT strictly matches parent course (:slt h).', ['slt' => '${parentSlt}']) }}</span>
        </div>
        <span class="tag ok" style="font-size:11px">{{ __('Exact Match (100%)') }}</span>
      `;
    } else {
      banner.style.background = '#fff1f1';
      banner.style.border = '1px solid #ffd7d9';
      banner.style.color = '#a2191f';
      const diffText = diff > 0 ? `{{ __('Short of') }} ${diff}h` : `{{ __('Exceeds by') }} ${Math.abs(diff)}h`;
      banner.innerHTML = `
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="#da1e28"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
          <span>{{ __('Module hours (:sum h) do not match parent course SLT (:parent h).', ['sum' => '${sum}', 'parent' => '${parentSlt}']) }} ${diffText}.</span>
        </div>
        <span class="tag err" style="font-size:11px">{{ __('Alignment Needed') }} (${sum}/${parentSlt} h)</span>
      `;
    }
  }

  // Event Listeners
  modelCards.forEach(card => {
    card.addEventListener('click', function(e) {
      const radio = card.querySelector('.model-radio');
      if (radio) {
        radio.checked = true;
        updateModelUI(radio.value);
      }
    });
  });

  radios.forEach(radio => {
    radio.addEventListener('change', function() {
      updateModelUI(this.value);
    });
  });

  if (hiddenSelect) {
    hiddenSelect.addEventListener('change', function() {
      updateModelUI(this.value);
    });
  }

  sltInputs.forEach(input => {
    input.addEventListener('input', recalcSLT);
  });

  document.querySelectorAll('.assessment-checkbox').forEach(chk => {
    chk.addEventListener('change', function() {
      const label = this.closest('label')?.querySelector('.assessment-checkbox-label');
      if (label) {
        label.textContent = this.checked ? @json(__('Assessment')) : @json(__('Learning'));
        label.style.fontWeight = this.checked ? '700' : 'normal';
        label.style.color = this.checked ? '#6929c4' : '#475569';
      }
      recalcSLT();
    });
  });

  document.querySelectorAll('.module-name-en-input').forEach(input => {
    input.addEventListener('input', function () {
      const card = this.closest('.module-row');
      const preview = card ? card.querySelector('.module-title-preview') : null;
      if (preview) preview.textContent = this.value.trim() || @json(__('Untitled Module'));
    });
  });

  // UMC Unbundling Model modal controls
  const umcModal = document.getElementById('modal-umc-models');
  const openUmcBtn = document.getElementById('btn-open-umc-models');
  const closeUmcBtn = document.getElementById('btn-close-umc-models');
  const doneUmcBtn = document.getElementById('btn-done-umc-models');

  function closeUmcModal() {
    if (!umcModal) return;
    umcModal.style.display = 'none';
    umcModal.setAttribute('hidden', '');
    if (openUmcBtn) openUmcBtn.focus();
  }
  if (openUmcBtn && umcModal) {
    openUmcBtn.addEventListener('click', function () {
      umcModal.removeAttribute('hidden');
      umcModal.style.display = 'flex';
    });
  }
  if (closeUmcBtn) closeUmcBtn.addEventListener('click', closeUmcModal);
  if (doneUmcBtn) doneUmcBtn.addEventListener('click', closeUmcModal);
  if (umcModal) {
    umcModal.addEventListener('click', function (e) { if (e.target === umcModal) closeUmcModal(); });
    umcModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeUmcModal(); });
  }

  modelCards.forEach(card => {
    card.addEventListener('click', function () {
      const radio = this.querySelector('.model-radio');
      if (radio) {
        radio.checked = true;
        updateModelUI(radio.value);
        if (hiddenSelect) hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
        closeUmcModal();
      }
    });
  });

  // Initialize
  updateModelUI(getSelectedModel());
})();
</script>
