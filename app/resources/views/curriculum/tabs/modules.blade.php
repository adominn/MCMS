<form method="post" action="{{ route('proposals.save', [$v->id, 'modules']) }}" class="card" data-autosave id="modules-form">@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  @if($v->category === 'umc')
    @php
      $parentSlt = (int) ($course->slt_hours ?? 120);
      $parentCredit = $course->credit ?? 3;
      $activeModel = $v->credit_model ?: 'credit_based';
    @endphp

    @if($course)
      <div style="background:#f4f7fb;border:1px solid #d0e2ff;border-left:4px solid #0f62fe;padding:12px 16px;border-radius:2px;margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
          <div>
            <div style="font-size:11px;font-weight:700;color:#0043ce;text-transform:uppercase;letter-spacing:0.5px">
              {{ __('Parent Course Reference') }} &middot; {{ $course->programme }}
            </div>
            <div style="font-size:15px;font-weight:700;color:#161616;margin-top:2px">
              {{ $course->code }} {{ $course->name }}
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="tag" style="background:#ffffff;border:1px solid #d0e2ff;color:#0043ce;font-weight:600">
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

    {{-- 3 Official Unbundling Models (Garis Panduan Micro-Credential UTeM Section 6.3.1.1) --}}
    <div style="margin-bottom:22px">
      <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px;flex-wrap:wrap;gap:8px">
        <label style="font-size:14px;font-weight:700;color:#161616;margin:0">
          {{ __('Model Penyahhimpunan UMC (Unbundling Model)') }} <span style="color:#da1e28">*</span>
        </label>
        <span class="muted small" style="font-size:11.5px">
          {{ __('Garis Panduan Micro-Credential UTeM Seksyen 6.3.1.1 & Rajah 2') }}
        </span>
      </div>

      <div class="unbundling-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:14px;margin-bottom:12px">
        
        {{-- (a) Model Penyahhimpunan Berasaskan Kredit --}}
        <label class="unbundling-card {{ $activeModel === 'credit_based' ? 'active' : '' }}" style="border:2px solid {{ $activeModel === 'credit_based' ? '#0f62fe' : '#e0e0e0' }};background:{{ $activeModel === 'credit_based' ? '#fbfdff' : '#ffffff' }};padding:14px 16px;border-radius:3px;cursor:pointer;display:flex;flex-direction:column;justify-content:space-between;gap:10px;transition:all 0.15s ease;position:relative">
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px">
              <span style="display:inline-flex;align-items:center;gap:6px">
                <input type="radio" name="credit_model" value="credit_based" @checked($activeModel === 'credit_based') class="model-radio" style="margin:0">
                <b style="font-size:13px;color:#161616">{{ __('(a) Model Penyahhimpunan Berasaskan Kredit') }}</b>
              </span>
              <span class="tag" style="background:#edf5ff;color:#0043ce;border:1px solid #d0e2ff;font-size:11px;font-weight:600">
                &ge; 40h SLT / Modul
              </span>
            </div>
            <div style="font-size:12px;font-weight:600;color:#0f62fe;margin-bottom:4px">
              {{ __('Credit-Based Unbundling Model') }}
            </div>
            <p style="font-size:12px;color:#525252;line-height:1.45;margin:0 0 6px">
              {{ __('Setiap modul mempunyai sekurang-kurangnya 40 jam SLT dan merangkumi pembelajaran, aktiviti dan pentaksiran.') }}
            </p>
          </div>
          <div style="font-size:11px;color:#6f6f6f;border-top:1px dashed #e0e0e0;padding-top:8px">
            <b>{{ __('Syarat:') }}</b> {{ __('SLT setiap modul &ge; 40 jam (1 kredit). Pentaksiran kendiri di dalam setiap modul.') }}
          </div>
        </label>

        {{-- (b) Model Penyahhimpunan Berasaskan Topik --}}
        <label class="unbundling-card {{ $activeModel === 'topic_based' ? 'active' : '' }}" style="border:2px solid {{ $activeModel === 'topic_based' ? '#0f62fe' : '#e0e0e0' }};background:{{ $activeModel === 'topic_based' ? '#fbfdff' : '#ffffff' }};padding:14px 16px;border-radius:3px;cursor:pointer;display:flex;flex-direction:column;justify-content:space-between;gap:10px;transition:all 0.15s ease;position:relative">
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px">
              <span style="display:inline-flex;align-items:center;gap:6px">
                <input type="radio" name="credit_model" value="topic_based" @checked($activeModel === 'topic_based') class="model-radio" style="margin:0">
                <b style="font-size:13px;color:#161616">{{ __('(b) Model Penyahhimpunan Berasaskan Topik') }}</b>
              </span>
              <span class="tag" style="background:#e5f6f6;color:#005d5d;border:1px solid #9ef0f0;font-size:11px;font-weight:600">
                {{ __('SLT Berbeza') }}
              </span>
            </div>
            <div style="font-size:12px;font-weight:600;color:#005d5d;margin-bottom:4px">
              {{ __('Topic-Based Unbundling Model') }}
            </div>
            <p style="font-size:12px;color:#525252;line-height:1.45;margin:0 0 6px">
              {{ __('Modul dibentuk berdasarkan topik atau kelompok pembelajaran dan SLT setiap modul boleh berbeza, dengan jumlah keseluruhan SLT bersamaan SLT kursus induk.') }}
            </p>
          </div>
          <div style="font-size:11px;color:#6f6f6f;border-top:1px dashed #e0e0e0;padding-top:8px">
            <b>{{ __('Syarat:') }}</b> {{ __('SLT modul fleksibel (< 40j dibenarkan). Cth. Rajah 6: 120j = 30j + 25j + 25j + 20j + 20j.') }}
          </div>
        </label>

        {{-- (c) Model Penyahhimpunan Pembelajaran dan Pentaksiran Berasingan --}}
        <label class="unbundling-card {{ $activeModel === 'separate_assessment' ? 'active' : '' }}" style="border:2px solid {{ $activeModel === 'separate_assessment' ? '#0f62fe' : '#e0e0e0' }};background:{{ $activeModel === 'separate_assessment' ? '#fbfdff' : '#ffffff' }};padding:14px 16px;border-radius:3px;cursor:pointer;display:flex;flex-direction:column;justify-content:space-between;gap:10px;transition:all 0.15s ease;position:relative">
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px">
              <span style="display:inline-flex;align-items:center;gap:6px">
                <input type="radio" name="credit_model" value="separate_assessment" @checked($activeModel === 'separate_assessment') class="model-radio" style="margin:0">
                <b style="font-size:13px;color:#161616">{{ __('(c) Pembelajaran & Pentaksiran Berasingan') }}</b>
              </span>
              <span class="tag" style="background:#f6f2ff;color:#6929c4;border:1px solid #e8daff;font-size:11px;font-weight:600">
                {{ __('Pentaksiran Bersepadu') }}
              </span>
            </div>
            <div style="font-size:12px;font-weight:600;color:#6929c4;margin-bottom:4px">
              {{ __('Separate Learning and Assessment Model') }}
            </div>
            <p style="font-size:12px;color:#525252;line-height:1.45;margin:0 0 6px">
              {{ __('Komponen pembelajaran distrukturkan dalam beberapa modul manakala pentaksiran bersepadu ditempatkan dalam modul pentaksiran yang berasingan.') }}
            </p>
          </div>
          <div style="font-size:11px;color:#6f6f6f;border-top:1px dashed #e0e0e0;padding-top:8px">
            <b>{{ __('Syarat:') }}</b> {{ __('Modul pembelajaran (kandungan & aktiviti) + tepat satu Modul Pentaksiran Bersepadu.') }}
          </div>
        </label>
      </div>

      {{-- Hidden select kept in sync for form submission compatibility --}}
      <select id="credit_model" name="credit_model_hidden" style="display:none">
        <option value="credit_based" @selected($activeModel === 'credit_based')>Credit-based</option>
        <option value="topic_based" @selected($activeModel === 'topic_based')>Topic-based</option>
        <option value="separate_assessment" @selected($activeModel === 'separate_assessment')>Separate assessment</option>
      </select>
    </div>
  @endif

  {{-- Modules Structure Table --}}
  <div style="margin-top:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-wrap:wrap;gap:8px">
      <h2 style="font-size:16px;font-weight:700;color:#161616;margin:0">
        {{ __('Module Structure & Student Learning Time (SLT)') }}
      </h2>
      <div id="model-status-indicator" class="small muted" style="font-size:12px"></div>
    </div>

    <table style="width:100%;margin-top:4px">
      <thead>
        <tr>
          <th style="width:40px">{{ __('No.') }}</th>
          <th>{{ __('Name (English)') }}</th>
          <th>{{ __('Name (Bahasa Melayu)') }}</th>
          <th style="width:110px">{{ __('Hours (SLT)') }}</th>
          <th class="assessment-module-col" style="width:170px;{{ $activeModel === 'separate_assessment' ? '' : 'display:none;' }}">
            {{ __('Assessment Module') }}
          </th>
          <th style="width:80px"></th>
        </tr>
      </thead>
      <tbody id="modules-tbody">
      @forelse($modules as $m)
        <tr class="module-row" data-module-id="{{ $m->id }}">
          <td><b style="font-family:var(--mono)">{{ $m->seq }}</b></td>
          <td>
            <input type="text" name="rows[{{ $m->id }}][name_en]" value="{{ $m->name_en }}" placeholder="e.g. Module {{ $m->seq }}: Topic Title" aria-label="Module {{ $m->seq }} name in English">
          </td>
          <td>
            <input type="text" name="rows[{{ $m->id }}][name_ms]" value="{{ $m->name_ms }}" placeholder="cth. Modul {{ $m->seq }}: Tajuk Topik" aria-label="Module {{ $m->seq }} name in Bahasa Melayu">
          </td>
          <td>
            <input type="text" inputmode="numeric" name="rows[{{ $m->id }}][slt_hours]" value="{{ $m->slt_hours }}" class="module-slt-input" style="max-width:90px;font-family:var(--mono);font-weight:600" aria-label="Module {{ $m->seq }} hours">
          </td>
          <td class="assessment-module-col" style="{{ $activeModel === 'separate_assessment' ? '' : 'display:none;' }}">
            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;margin:0">
              <input type="checkbox" name="rows[{{ $m->id }}][is_assessment]" value="1" @checked($m->is_assessment) class="assessment-checkbox" aria-label="Module {{ $m->seq }} is the assessment module">
              <span class="assessment-checkbox-label" style="font-weight:{{ $m->is_assessment ? '700' : 'normal' }};color:{{ $m->is_assessment ? '#6929c4' : '#525252' }}">
                {{ $m->is_assessment ? __('Assessment') : __('Learning') }}
              </span>
            </label>
          </td>
          <td style="text-align:right">
            @unless($locked)
              <button type="submit" name="remove" value="{{ $m->id }}" class="btn ter sm" style="color:#da1e28">
                {{ __('Remove') }}
              </button>
            @endunless
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="{{ $activeModel === 'separate_assessment' ? 6 : 5 }}" class="muted" style="text-align:center;padding:16px">
            {{ __('No modules configured yet. Click "Add module" below to begin unbundling.') }}
          </td>
        </tr>
      @endforelse
      </tbody>
      <tfoot>
        <tr style="background:#f8f9fa;font-weight:700;border-top:2px solid #e0e0e0">
          <td></td>
          <td colspan="2">
            <span style="font-size:13px">{{ __('Total Student Learning Time (SLT)') }}</span>
          </td>
          <td>
            <span id="slt-sum-display" style="font-family:var(--mono);font-size:14px;color:#161616">
              {{ $modules->sum('slt_hours') }} h
            </span>
          </td>
          <td class="assessment-module-col" style="{{ $activeModel === 'separate_assessment' ? '' : 'display:none;' }}">
            <span id="slt-split-display" style="font-size:11px;color:#6f6f6f"></span>
          </td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  {{-- Live SLT Match & Validation Feedback Bar --}}
  @if($v->category === 'umc' && $course)
    <div id="slt-match-banner" style="margin-top:14px;padding:10px 14px;border-radius:2px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      {{-- Populated dynamically by JavaScript --}}
    </div>
  @endif

  @unless($locked)
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;padding-top:16px;border-top:1px solid #e0e0e0;flex-wrap:wrap;gap:12px">
      <button type="submit" name="action" value="add" class="btn ter sm" style="display:inline-flex;align-items:center;gap:6px">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
          <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
        </svg>
        <span>{{ __('Add module') }}</span>
      </button>

      <div style="display:flex;align-items:center;gap:8px">
        <button type="submit" name="action" value="save" class="btn sm" style="background:#1e2a6e;color:#ffffff;font-weight:600">
          {{ __('Save Modules') }}
        </button>
      </div>
    </div>
  @endunless
  </fieldset>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
        card.style.borderColor = '#0f62fe';
        card.style.background = '#fbfdff';
        card.classList.add('active');
        radio.checked = true;
      } else {
        card.style.borderColor = '#e0e0e0';
        card.style.background = '#ffffff';
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

    // Update guidance indicator
    if (statusIndicator) {
      if (model === 'credit_based') {
        statusIndicator.innerHTML = '<span class="tag" style="background:#edf5ff;color:#0043ce;border:1px solid #d0e2ff">Model (a): &ge; 40h SLT / modul</span>';
      } else if (model === 'topic_based') {
        statusIndicator.innerHTML = '<span class="tag" style="background:#e5f6f6;color:#005d5d;border:1px solid #9ef0f0">Model (b): SLT mengikut topik (boleh berbeza)</span>';
      } else if (model === 'separate_assessment') {
        statusIndicator.innerHTML = '<span class="tag" style="background:#f6f2ff;color:#6929c4;border:1px solid #e8daff">Model (c): Pembelajaran + 1 Pentaksiran Bersepadu</span>';
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

    sltInputs.forEach(input => {
      const val = parseInt(input.value, 10) || 0;
      sum += val;
      const row = input.closest('tr');
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

    if (splitDisplay && model === 'separate_assessment') {
      splitDisplay.textContent = `Pembelajaran: ${learnSum}h | Pentaksiran: ${assessSum}h`;
    }

    if (!banner || !isUmc) return;

    const diff = parentSlt - sum;
    if (diff === 0) {
      banner.style.background = '#defbe6';
      banner.style.border = '1px solid #a7f0ba';
      banner.style.color = '#0e6027';
      banner.innerHTML = `
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="#198038"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
          <span>Jumlah SLT tepat bersamaan kursus induk (${parentSlt} h).</span>
        </div>
        <span class="tag ok" style="font-size:11px">Padanan Tepat (100%)</span>
      `;
    } else {
      banner.style.background = '#fff1f1';
      banner.style.border = '1px solid #ffd7d9';
      banner.style.color = '#a2191f';
      const diffText = diff > 0 ? `Kurang ${diff} jam lagi diperlukan` : `Terlebih ${Math.abs(diff)} jam daripada kursus induk`;
      banner.innerHTML = `
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="#da1e28"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
          <span>Jumlah modul (${sum} h) belum sama dengan SLT kursus induk (${parentSlt} h). ${diffText}.</span>
        </div>
        <span class="tag err" style="font-size:11px">Perlu Selaras (${sum}/${parentSlt} h)</span>
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

  sltInputs.forEach(input => {
    input.addEventListener('input', recalcSLT);
  });

  document.querySelectorAll('.assessment-checkbox').forEach(chk => {
    chk.addEventListener('change', function() {
      const label = this.closest('label')?.querySelector('.assessment-checkbox-label');
      if (label) {
        label.textContent = this.checked ? 'Assessment' : 'Learning';
        label.style.fontWeight = this.checked ? '700' : 'normal';
        label.style.color = this.checked ? '#6929c4' : '#525252';
      }
      recalcSLT();
    });
  });

  // Initialize
  updateModelUI(getSelectedModel());
});
</script>
