@extends('layouts.app')
@section('title', 'Stackable pathways')
@section('content')
<div class="title" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
  <div>
    <h1>{{ __('Stackable pathways') }}</h1>
    <div class="sub">{{ __('Pathway mappings need Senate approval before they are published. A mapping transfers no credit; each credit is still decided as a credit transfer. No number of micro-credentials leads to a degree automatically.') }}</div>
  </div>
  <div class="act" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <button type="button" class="btn ter sm" onclick="var d=document.getElementById('new-pathway-mapping');if(d){d.open=!d.open;if(d.open)d.scrollIntoView({behavior:'smooth'});}">
      + {{ __('New pathway mapping') }}
    </button>
    <a class="btn sm" href="{{ route('plans.new') }}">
      + {{ __('New individual plan') }}
    </a>
  </div>
</div>

<div data-tabs data-auto>
  <nav class="tabs" aria-label="Pathway tabs" style="margin-bottom:20px;border-bottom:1px solid var(--rule);display:flex;gap:24px" role="tablist">
    <button type="button" data-tab="pathways" class="tab-btn active" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid var(--accent, #1e2a6e);color:var(--accent, #1e2a6e);cursor:pointer">
      {{ __('Degree Articulation Roadmaps') }} <span class="muted">({{ count($rows) }})</span>
    </button>
    <button type="button" data-tab="plans" class="tab-btn" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
      {{ __('Individual Student Plans') }} <span class="muted">({{ count($plans) }})</span>
    </button>
  </nav>

  {{-- ==================== TAB 1: FACULTY PATHWAY MAPPINGS ==================== --}}
  <div data-pane="pathways">
    <div class="note info" style="margin-bottom:20px;padding:12px 16px;background:var(--surface-1, #f4f4f4);border-left:4px solid var(--primary, #1e2a6e);font-size:13px;display:flex;gap:12px;align-items:flex-start">
      <div>
        <strong>{{ __('Institutional Curriculum Roadmaps:') }}</strong>
        {{ __('Faculty-level articulation roadmaps define which degree parent courses can be articulated using eligible credit-bearing micro-credentials (UMC and Quality-Verified SAMC). Mappings require formal Senate approval before publication.') }}
      </div>
    </div>

    <div class="panel" style="margin-bottom:24px">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h2>{{ __('Faculty pathway mappings') }}</h2>
        <div style="display:inline-flex;align-items:center;gap:8px">
          <span class="s muted">{{ count($rows) }} {{ __('pathways') }}</span>
          <button type="button" class="btn ter sm" onclick="openDefineMappingModal()">
            + {{ __('New mapping') }}
          </button>
        </div>
      </div>
      <table id="pathways">
        <thead>
          <tr>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Programme') }}</th>
            <th>{{ __('Status') }}</th>
            <th style="text-align:right">{{ __('Notice / Action') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $w)
          <tr>
            <td>
              <a href="{{ route('pathways.show', $w->id) }}" style="font-weight:600">{{ $w->reference }}</a>
            </td>
            <td>
              <b>{{ $w->programme }}</b> {{ $w->programme_name }}
            </td>
            <td><x-pill :s="$status[$w->status] ?? ucfirst(str_replace('_', ' ', $w->status))" /></td>
            <td style="text-align:right">
              @if($w->review_reason)
                <span class="tag overdue">{{ $w->review_reason }}</span>
              @endif
              <a class="btn ter sm" href="{{ route('pathways.show', $w->id) }}">{{ __('Open') }}</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No pathway yet.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    {{-- Carbon Structured Pathway Definition Card --}}
    <div class="card" style="margin-top:24px;padding:16px 20px;border:1px solid var(--hairline);background:var(--surface-1, #f4f4f4);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:12px">
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--ink)">
            {{ __('Define a new degree articulation roadmap') }}
          </div>
          <div class="muted small" style="margin-top:2px">
            {{ __('Curriculum structure, PLOs, and Body of Knowledge requirements') }}
          </div>
        </div>
      </div>
      <div>
        <button type="button" class="btn sm" onclick="openDefineMappingModal()" style="display:inline-flex;align-items:center;gap:6px">
          <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v10M3 8h10"/></svg>
          {{ __('Define mapping') }}
        </button>
      </div>
    </div>

    {{-- Carbon Modal Popup: Define New Degree Articulation Roadmap --}}
    <div class="scrim" id="modal-define-mapping" hidden style="display:none;position:fixed;inset:0;background:rgba(22,22,22,.5);z-index:70;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
      <div class="modal wide" role="dialog" aria-modal="true" aria-labelledby="modal-define-title" style="max-width:760px;width:92%;max-height:88vh;display:flex;flex-direction:column;background:#ffffff;border:1px solid var(--hairline);border-radius:2px;box-shadow:0 12px 40px rgba(0,0,0,.25)">
        
        <!-- Modal Header -->
        <div class="mh" style="padding:18px 24px;border-bottom:1px solid var(--hairline);display:flex;align-items:flex-start;justify-content:space-between;background:#ffffff">
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--primary, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
              {{ __('Degree Articulation Roadmap') }} &bull; {{ __('Step 1 of 3') }}
            </div>
            <h2 id="modal-define-title" style="margin:0;font-size:18px;font-weight:600;color:var(--ink)">
              {{ __('Define a new degree articulation roadmap') }}
            </h2>
            <div class="muted small" style="margin-top:4px">
              {{ __('Curriculum structure, PLOs, and Body of Knowledge requirements') }}
            </div>
          </div>
          <button type="button" class="btn ghost sm" onclick="closeDefineMappingModal()" aria-label="{{ __('Close') }}" style="font-size:20px;line-height:1;padding:4px 8px;cursor:pointer;color:var(--muted);border:none;background:transparent">
            &times;
          </button>
        </div>

        <!-- Modal Form / Body -->
        <form method="post" action="{{ route('pathways.store') }}" id="form-define-mapping" style="display:flex;flex-direction:column;flex:1;overflow:hidden;margin:0">
          @csrf
          <div class="mb" style="padding:24px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:20px">
            <div class="note info" style="margin:0;padding:12px 16px;background:var(--surface-1, #f4f4f4);border-left:4px solid var(--primary, #1e2a6e);font-size:13px;line-height:1.5">
              <b>{{ __('How it works:') }}</b> {{ __('Select a degree programme to build an official articulation roadmap. In the next step, you will pair specific micro-credentials to the degree’s parent courses to fulfill PLOs and Body of Knowledge requirements.') }}
            </div>

            <div class="field">
              <label for="programme_id" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px;color:var(--ink)">
                1. {{ __('Target degree programme') }}
              </label>
              <select id="programme_id" name="programme_id" style="width:100%;height:40px;padding:8px 12px;background:#f4f4f4;border:1px solid var(--hairline);border-radius:2px;font-size:13.5px;color:var(--ink)" onchange="updateProgSummary(this)">
                @foreach($programmes as $p)
                  <option value="{{ $p->id }}" data-credits="{{ $p->total_credits }}" data-code="{{ $p->code }}" data-mqf="{{ $p->mqf_level }}">
                    {{ $p->code }} - {{ $p->name }} (MQF {{ $p->mqf_level }}, {{ $p->total_credits }} credits)
                  </option>
                @endforeach
              </select>
              <div id="prog-summary-pill" style="margin-top:10px;padding:12px 14px;background:#f4f4f4;border-left:3px solid var(--primary, #1e2a6e);font-size:12.5px;color:var(--ink);line-height:1.5">
                <span class="tag sm ok" id="summary-code" style="font-weight:600;margin-right:6px">BITD</span>
                <span id="summary-text">{{ __('Accredited Degree · At least 30% of graduating credits must be completed directly at UTeM (Residency Requirement).') }}</span>
              </div>
            </div>

            <div class="field">
              <label for="plos" style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;color:var(--ink)">
                2. {{ __('Programme learning outcomes to cover') }}
              </label>
              <div style="font-size:12px;color:var(--muted);margin-bottom:8px">
                {{ __('Click quick tags below to add, or type custom outcomes (one per line):') }}
              </div>
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('plos', 'PLO 1: Technical Knowledge & Concepts')">+ PLO 1: Knowledge</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('plos', 'PLO 2: Practical & Technical Skills')">+ PLO 2: Practical Skills</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('plos', 'PLO 3: Critical Thinking & Problem Solving')">+ PLO 3: Problem Solving</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('plos', 'PLO 4: Communication & Collaboration')">+ PLO 4: Communication</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('plos', 'PLO 5: Professional & Ethical Responsibility')">+ PLO 5: Ethics</button>
              </div>
              <textarea id="plos" name="plos" rows="3" placeholder="PLO 1: Technical Knowledge & Concepts&#10;PLO 2: Practical & Technical Skills" style="width:100%;padding:10px 12px;background:#f4f4f4;border:1px solid var(--hairline);border-radius:2px;font-family:inherit;font-size:13px"></textarea>
            </div>

            <div class="field">
              <label for="bok" style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;color:var(--ink)">
                3. {{ __('Body of Knowledge domains to cover') }}
              </label>
              <div style="font-size:12px;color:var(--muted);margin-bottom:8px">
                {{ __('Click quick tags below to add, or type technical areas (one per line):') }}
              </div>
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('bok', 'Database Systems')">+ Database Systems</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('bok', 'Data Warehousing & Analytics')">+ Data Analytics</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('bok', 'Software Engineering')">+ Software Engineering</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('bok', 'Systems Architecture')">+ Systems Architecture</button>
                <button type="button" class="btn ter sm" style="border-radius:2px;font-size:12px;padding:4px 8px" onclick="appendLine('bok', 'Cyber Security Operations')">+ Cyber Security</button>
              </div>
              <textarea id="bok" name="bok" rows="2" placeholder="Software Engineering&#10;Systems Architecture" style="width:100%;padding:10px 12px;background:#f4f4f4;border:1px solid var(--hairline);border-radius:2px;font-family:inherit;font-size:13px"></textarea>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="mf" style="padding:14px 24px;border-top:1px solid var(--hairline);background:var(--surface-1, #f4f4f4);display:flex;align-items:center;justify-content:space-between;gap:12px">
            <button type="button" class="btn ter" onclick="closeDefineMappingModal()" style="border-radius:2px;padding:8px 16px">
              {{ __('Cancel') }}
            </button>
            <div style="display:flex;align-items:center;gap:12px">
              <span class="muted small">{{ __('Next: Pair micro-credentials with degree courses') }}</span>
              <button type="submit" class="btn primary" style="border-radius:2px;padding:8px 18px;background:var(--primary, #1e2a6e);color:#ffffff;display:inline-flex;align-items:center;gap:6px">
                {{ __('Start draft mapping') }} &rarr;
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openDefineMappingModal() {
        var m = document.getElementById('modal-define-mapping');
        if (m) {
          m.hidden = false;
          m.style.display = 'flex';
          document.body.style.overflow = 'hidden';
          var sel = document.getElementById('programme_id');
          if (sel) updateProgSummary(sel);
        }
      }

      function closeDefineMappingModal() {
        var m = document.getElementById('modal-define-mapping');
        if (m) {
          m.hidden = true;
          m.style.display = 'none';
          document.body.style.overflow = '';
        }
      }

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeDefineMappingModal();
        }
      });

      document.addEventListener('click', function(e) {
        var m = document.getElementById('modal-define-mapping');
        if (m && e.target === m) {
          closeDefineMappingModal();
        }
      });

      function appendLine(textareaId, text) {
        var el = document.getElementById(textareaId);
        if (!el) return;
        var cur = el.value.trim();
        if (cur.indexOf(text) === -1) {
          el.value = cur ? cur + '\n' + text : text;
        }
      }

      function updateProgSummary(sel) {
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        var code = opt.getAttribute('data-code') || '';
        var cred = parseFloat(opt.getAttribute('data-credits') || '0');
        var min30 = Math.round(cred * 0.3);
        var max70 = Math.round(cred * 0.7);
        var codeEl = document.getElementById('summary-code');
        var textEl = document.getElementById('summary-text');
        if (codeEl) codeEl.innerText = code;
        if (textEl) {
          textEl.innerText = cred + ' total graduating credits. At least 30% (≥ ' + min30 + ' credits) must be completed directly at UTeM as the home awarding institution. Maximum allowable articulation from micro-credentials: up to 70% (≤ ' + max70 + ' credits).';
        }
      }

      document.addEventListener('DOMContentLoaded', function() {
        var sel = document.getElementById('programme_id');
        if (sel) updateProgSummary(sel);
      });
    </script>
  </div>

  {{-- ==================== TAB 2: INDIVIDUAL STUDENT PLANS ==================== --}}
  <div data-pane="plans">
    <div class="note info" style="margin-bottom:20px;padding:12px 16px;background:var(--surface-1, #f4f4f4);border-left:4px solid var(--primary, #1e2a6e);font-size:13px;display:flex;gap:12px;align-items:flex-start">
      <div>
        <strong>{{ __('Personalized Degree Study Plans:') }}</strong>
        {{ __('Individual student plans provide faculty academic guidance on how a student’s accumulated credentials articulate toward graduation. The plan enforces the mandatory 30% direct UTeM home study rule and tracks remaining curriculum requirements.') }}
      </div>
    </div>

    <div class="panel">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h2>{{ __('Individual stackable plans') }}</h2>
        <div style="display:inline-flex;align-items:center;gap:8px">
          <span class="s muted">{{ count($plans) }} {{ __('plans') }}</span>
          <a class="btn sm" href="{{ route('plans.new') }}">+ {{ __('New individual plan') }}</a>
        </div>
      </div>
      <table id="plans">
        <thead>
          <tr>
            <th>{{ __('Plan Reference') }}</th>
            <th>{{ __('Participant') }}</th>
            <th>{{ __('Programme') }}</th>
            <th style="text-align:right">{{ __('Direct UTeM Study %') }}</th>
            <th>{{ __('Status') }}</th>
            <th style="text-align:right">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($plans as $s)
          <tr>
            <td>
              <a href="{{ route('plans.show', $s->id) }}" style="font-weight:600">{{ $s->reference }}</a>
            </td>
            <td>{{ $s->name }}</td>
            <td><code>{{ $s->code }}</code></td>
            <td style="text-align:right;font-weight:600">{{ rtrim(rtrim((string) $s->utem_percent, '0'), '.') }}%</td>
            <td><x-pill :s="config('pathways.plan_status')[$s->status] ?? ucfirst(str_replace('_', ' ', $s->status))" /></td>
            <td style="text-align:right">
              <a class="btn ter sm" href="{{ route('plans.show', $s->id) }}">{{ __('Open') }}</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="muted" style="text-align:center;padding:24px">{{ __('No plan yet.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
