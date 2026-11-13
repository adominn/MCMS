@php
  $isPrint = request()->boolean('print') || request()->has('print');
  $nAch = collect($d['modules'])->filter(fn ($m) => $m->achieved)->count();
  $nTot = count($d['modules']);
  $stateLabel = match($d['state']) {
    'complete' => 'Complete',
    'in_progress' => 'In progress',
    default => 'Not started',
  };
@endphp

@if(!$isPrint)
  {{-- Guide Section 8.7.5 Compliance Banner --}}
  <div class="note info" style="margin-bottom:16px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
      <strong>{{ __('Internal record, not an Academic Credit Record') }}</strong>
      <div class="s" style="margin-top:2px">
        {{ __('This record verifies completed modules, digital badges earned, and confirmed completion status. It is an internal UTeM record and distinct from an Academic Credit Record (ACR).') }}
      </div>
    </div>
    @if(!empty($d['acr']))
      @php
        $acrLink = auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin')
          ? route('credit.acr', $d['acr']->id)
          : (auth()->user()->hasAnyRole('deputy_dean') ? route('deposits.show', $d['acr']->id) : null);
      @endphp
      @if($acrLink)
        <a href="{{ $acrLink }}" class="btn ghost sm" style="white-space:nowrap">
          {{ __('View ACR (:credits cr) &rarr;', ['credits' => $d['acr']->credits]) }}
        </a>
      @else
        <span class="tag info" style="white-space:nowrap">
          {{ __('ACR: :credits cr', ['credits' => $d['acr']->credits]) }}
        </span>
      @endif
    @endif
  </div>

  {{-- High-Density Metric KPIs --}}
  <div class="kpis" style="margin-bottom:20px">
    <div class="kpi">
      <div class="l">{{ __('Modules achieved') }}</div>
      <div class="v">{{ $nAch }} / {{ $nTot }}</div>
      <div class="s muted">{{ $nTot > 0 ? round(($nAch / $nTot) * 100) : 0 }}% {{ __('completed') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Verified results') }}</div>
      <div class="v">{{ count($d['results']) }}</div>
      <div class="s muted">{{ __('Formal assessments') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Final result') }}</div>
      <div class="v">{{ $d['final'] ? $d['final']['weighted_percent'].'%' : '—' }}</div>
      <div class="s muted">{{ $d['final']['grade'] ?? __('Weighted grade') }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Completion status') }}</div>
      <div class="v">
        @if($d['state'] === 'complete')
          <span class="tag ok" style="font-size:13px">{{ __('Complete') }}</span>
        @elseif($d['state'] === 'in_progress')
          <span class="tag info" style="font-size:13px">{{ __('In progress') }}</span>
        @else
          <span class="tag muted" style="font-size:13px">{{ __('Not started') }}</span>
        @endif
      </div>
      <div class="s muted">
        @if($d['rec']->completion_status)
          {{ __('Confirmed on') }} {{ \Carbon\Carbon::parse($d['rec']->completed_on)->format('d/m/Y') }}
        @else
          {{ __('Pending confirmation') }}
        @endif
      </div>
    </div>
  </div>

  {{-- Two-Column Structured Carbon Layout --}}
  <div class="cols" style="grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start">
    {{-- Left Column: Main Academic Records --}}
    <div style="display:flex;flex-direction:column;gap:20px">

      {{-- Section 1: Modules and Digital Badges --}}
      <div class="panel">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <div>
            <h2 style="margin:0">{{ __('Completed Modules & Digital Badges') }}</h2>
            <div class="s muted" style="margin-top:2px">
              {{ __('Each module is separately recognisable and awards an Open Badges 3.0 digital badge upon assessment mastery.') }}
            </div>
          </div>
          <span class="tag ok">{{ $nAch }} / {{ $nTot }} {{ __('Achieved') }}</span>
        </div>
        <div class="bd" style="padding:0">
          <table>
            <thead>
              <tr>
                <th style="min-width:200px">{{ __('Module') }}</th>
                <th class="num" style="width:60px">{{ __('Hours') }}</th>
                <th style="min-width:180px">{{ __('Earned Digital Badge') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Achieved Date') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($d['modules'] as $m)
                <tr>
                  <td>
                    @if($m->achieved)
                      <a href="{{ route('verify.show', $m->achieved->code) }}" target="_blank" style="font-weight:700;color:var(--ink);text-decoration:none">
                        <span style="color:var(--primary);text-decoration:underline">{{ $m->seq }}. {{ $m->name }}</span>
                        <span style="font-size:11px;color:var(--muted)">&nearr;</span>
                      </a>
                    @else
                      <strong style="color:var(--ink)">{{ $m->seq }}. {{ $m->name }}</strong>
                    @endif
                    @if(!empty($m->name_ms))
                      <div class="s muted" style="margin-top:2px">{{ $m->name_ms }}</div>
                    @endif
                  </td>
                  <td class="num">{{ $m->slt_hours ?? '—' }}</td>
                  <td>
                    @if($m->achieved)
                      <div style="display:flex;align-items:center;gap:10px">
                        <a href="{{ route('verify.show', $m->achieved->code) }}" target="_blank" title="{{ __('View digital badge verification') }}" style="display:inline-flex;align-items:center;text-decoration:none">
                          <x-badge :category="$d['e']->category" :size="36" :image="$d['e']->badge_image_path ?? null" />
                        </a>
                        <div>
                          <a href="{{ route('verify.show', $m->achieved->code) }}" target="_blank" style="font-weight:700;font-size:12px;color:var(--primary)">
                            {{ $m->achieved->code }} &nearr;
                          </a>
                          @if(!empty($m->achieved->external_id))
                            <div class="s muted" style="font-size:11px">UCreds {{ $m->achieved->external_id }}</div>
                          @endif
                        </div>
                      </div>
                    @else
                      <div style="display:flex;align-items:center;gap:10px;opacity:0.4">
                        <div style="filter:grayscale(100%)">
                          <x-badge :category="$d['e']->category" :size="36" :image="$d['e']->badge_image_path ?? null" />
                        </div>
                        <div>
                          <span class="muted" style="font-size:12px">{{ __('Not yet achieved') }}</span>
                        </div>
                      </div>
                    @endif
                  </td>
                  <td>
                    @if($m->achieved)
                      <span class="tag ok">{{ __('Achieved') }}</span>
                    @else
                      <span class="tag muted">{{ __('Not yet achieved') }}</span>
                    @endif
                  </td>
                  <td>
                    @if($m->achieved)
                      {{ \Carbon\Carbon::parse($m->achieved->achieved_at)->format('d/m/Y') }}
                    @else
                      <span class="muted">—</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- Section 2: Verified Assessment Results --}}
      <div class="panel">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <div>
            <h2 style="margin:0">{{ __('Verified Assessment Results') }}</h2>
            <div class="s muted" style="margin-top:2px">
              {{ __('Formal component results formally verified by the course lecturer and academic committee.') }}
            </div>
          </div>
          @if($d['final'])
            <span class="tag {{ $d['final']['weighted_percent'] >= 50 ? 'ok' : 'err' }}" style="font-weight:700">
              {{ __('Final:') }} {{ $d['final']['weighted_percent'] }}% ({{ $d['final']['grade'] ?? 'Pass' }})
            </span>
          @endif
        </div>
        <div class="bd" style="padding:0">
          <table>
            <thead>
              <tr>
                <th style="min-width:220px">{{ __('Assessment Component') }}</th>
                <th class="num" style="width:100px">{{ __('Pass Mark') }}</th>
                <th class="num" style="width:100px">{{ __('Mark Obtained') }}</th>
                <th style="width:110px">{{ __('Result') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($d['results'] as $r)
                @php
                  $isMet = (float) $r->score >= (float) $r->pass_mark;
                @endphp
                <tr>
                  <td><strong>{{ $r->name_en }}</strong></td>
                  <td class="num">{{ rtrim(rtrim((string) $r->pass_mark, '0'), '.') }}</td>
                  <td class="num" style="font-weight:600">
                    {{ rtrim(rtrim((string) $r->score, '0'), '.') }} / {{ rtrim(rtrim((string) $r->max_mark, '0'), '.') }}
                  </td>
                  <td>
                    <span class="tag {{ $isMet ? 'ok' : 'err' }}">
                      {{ $isMet ? __('Met') : __('Not met') }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="muted" style="text-align:center;padding:24px">
                    {{ __('Nothing verified yet. Results will appear once submitted and verified by the lecturer.') }}
                  </td>
                </tr>
              @endforelse
            </tbody>
            @if($d['final'])
              <tfoot>
                <tr>
                  <th>{{ __('Weighted Final Result') }}</th>
                  <th colspan="2" class="num" style="font-size:14px;font-weight:700;color:var(--ink)">
                    {{ $d['final']['weighted_percent'] }}%
                  </th>
                  <th>
                    <span class="tag {{ $d['final']['weighted_percent'] >= 50 ? 'ok' : 'err' }}" style="font-weight:700">
                      {{ $d['final']['grade'] ?? 'PASS' }}
                    </span>
                  </th>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>

      {{-- Section 3: Completion & Governance Verification --}}
      <div class="panel">
        <div class="hd">
          <h2 style="margin:0">{{ __('Governance & Deputy Dean Confirmation') }}</h2>
        </div>
        <div class="bd col" style="gap:12px">
          @if($d['state'] === 'complete')
            <div class="note ok" style="margin:0">
              <strong>{{ __('Completion Confirmed') }}</strong>
              <div class="s" style="margin-top:2px">
                {{ __('Completion was formally confirmed by the Deputy Dean on :date. All academic requirements, verified assessments, and quality audits were satisfied.', ['date' => \Carbon\Carbon::parse($d['rec']->completed_on)->format('d/m/Y')]) }}
              </div>
            </div>
          @else
            <div class="note warn" style="margin:0">
              <strong>{{ __('Completion In Progress') }}</strong>
              <div class="s" style="margin-top:2px">
                {{ __('Module badges and verified results do not complete the micro-credential: the deputy dean confirms completion separately before credential issuance.') }}
              </div>
            </div>
            @if(!empty($d['check']['reasons']))
              <div style="background:var(--surface);border:1px solid var(--hairline);padding:12px;margin-top:4px">
                <div style="font-weight:600;font-size:12px;margin-bottom:6px">{{ __('Pending Completion Requirements:') }}</div>
                <ul style="margin:0;padding-left:18px;font-size:12px;color:var(--muted)">
                  @foreach($d['check']['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
          @endif
        </div>
      </div>

    </div>

    {{-- Right Column (Sidebar - 340px) --}}
    <div style="display:flex;flex-direction:column;gap:16px">

      {{-- Sidebar Card 1: Credentials Awarded --}}
      <div class="panel">
        <div class="hd">
          <h3 style="margin:0">{{ __('Credentials Awarded') }}</h3>
        </div>
        <div class="bd col" style="gap:12px">
          @forelse($d['credentials'] as $cr)
            <div style="background:var(--surface);border:1px solid var(--hairline);padding:12px">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                <code class="vc" style="font-weight:700;font-size:13px">{{ $cr->number }}</code>
                <x-pill :s="$cr->status" />
              </div>
              <div class="s muted" style="margin-bottom:10px">
                {{ __('Issued on') }} {{ \Carbon\Carbon::parse($cr->issued_at ?? now())->format('d/m/Y') }}
              </div>
              @if(isset($cr->id))
                @php
                  $isStudent = auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin');
                  $credLink = $isStudent
                    ? route('credentials.show', $cr->id)
                    : (auth()->user()->hasAnyRole('deputy_dean')
                      ? route('credentials.admin.show', $cr->id)
                      : (!empty($cr->public_code) ? route('public.verify.show', $cr->public_code) : null));
                @endphp
                @if($credLink)
                  <a href="{{ $credLink }}" class="btn primary sm" style="width:100%;justify-content:center">
                    {{ __('View Credential') }} &rarr;
                  </a>
                @endif
              @endif
            </div>
          @empty
            <p class="s muted" style="margin:0">
              {{ __('None yet. Credentials are awarded upon confirmation of completion.') }}
            </p>
          @endforelse
        </div>
      </div>

      {{-- Sidebar Card 2: Academic Bank of Credit (Bahagian D) --}}
      <div class="panel">
        <div class="hd">
          <h3 style="margin:0">{{ __('Academic Bank of Credit (ABC)') }}</h3>
        </div>
        <div class="bd col" style="gap:12px">
          <p class="s muted" style="margin:0;line-height:1.4">
            {{ __('Under UTeM Micro-Credential Guide Bahagian D, academic credits are maintained separately in an Academic Credit Record (ACR) and deposited into the ABC upon participant consent.') }}
          </p>

          @if(!empty($d['acr']))
            <div style="background:var(--surface);border:1px solid var(--hairline);padding:12px">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                <strong style="font-size:12px">{{ __('ACR Reference') }}</strong>
                <x-pill :s="$d['acr']->status" />
              </div>
              <code class="vc" style="font-size:12px">{{ $d['acr']->reference }}</code>
              <div style="margin-top:6px;font-size:12px">
                <b>{{ $d['acr']->credits }}</b> {{ __('Academic Credits') }}
              </div>
              @if(!empty($d['acr']->confirmed_at))
                <div class="s muted" style="margin-top:2px">
                  {{ __('Confirmed on') }} {{ \Carbon\Carbon::parse($d['acr']->confirmed_at)->format('d/m/Y') }}
                </div>
              @endif
              @php
                $acrLinkSide = auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin')
                  ? route('credit.acr', $d['acr']->id)
                  : (auth()->user()->hasAnyRole('deputy_dean') ? route('deposits.show', $d['acr']->id) : null);
              @endphp
              @if($acrLinkSide)
                <a href="{{ $acrLinkSide }}" class="btn ghost sm" style="width:100%;justify-content:center;margin-top:10px">
                  {{ auth()->user()->hasAnyRole('deputy_dean') ? __('View in Deposit Queue & ABC') : __('View in Credit Ledger & ABC') }} &rarr;
                </a>
              @endif
            </div>
          @else
            <div class="s muted" style="border-top:1px solid var(--hairline);padding-top:8px">
              {{ __('An ACR will be generated upon completion and student deposit consent.') }}
            </div>
          @endif
        </div>
      </div>

      {{-- Sidebar Card 3: Enrolment & Course Metadata --}}
      <div class="panel">
        <div class="hd">
          <h3 style="margin:0">{{ __('Enrolment Information') }}</h3>
        </div>
        <div class="bd" style="padding:12px 16px;font-size:12px;line-height:1.6">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Learner:') }}</span>
            <span style="font-weight:600;text-align:right">{{ $d['e']->person }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Learner ID:') }}</span>
            <span style="font-family:var(--mono);text-align:right">{{ $d['e']->institution_id ?? '—' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Faculty / PTj:') }}</span>
            <span style="text-align:right;max-width:180px">{{ $d['e']->ptj_name ?? 'CODL' }}</span>
          </div>
          @if(!empty($d['e']->lecturer_name))
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
              <span class="muted">{{ __('Lecturer:') }}</span>
              <span style="text-align:right">{{ $d['e']->lecturer_name }}</span>
            </div>
          @endif
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Cohort:') }}</span>
            <span style="font-weight:600;text-align:right">{{ $d['e']->cohort_name }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Category:') }}</span>
            <span class="tag" style="text-transform:uppercase">{{ $d['e']->category ?? 'MC' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Proposed Credit:') }}</span>
            <span>{{ $d['e']->proposed_credit ?? 0 }} Credits</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Delivery Mode:') }}</span>
            <span>{{ ucfirst($d['e']->delivery_mode ?? 'online') }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="muted">{{ __('Version:') }}</span>
            <span>v{{ $d['e']->version_no }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding-top:6px;border-top:1px solid var(--hairline)">
            <span class="muted">{{ __('Registration Ref:') }}</span>
            <code style="font-size:11px">{{ $d['e']->reference }}</code>
          </div>
        </div>
      </div>

    </div>
  </div>

@else
  {{-- Printable Layout (Official A4 Sheet without tabs) --}}
  <div class="note warn" style="margin-bottom:16px;font-size:9.5pt">
    <div>
      <b>{{ __('Internal record, not an Academic Credit Record') }}</b> &ndash;
      <span>{{ __('This is MCMS internal record of modules, digital badges, and confirmed completions. It is governed separately from Academic Credit Records (ACR) under Bahagian D.') }}</span>
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="hd">
      <h2 style="font-size:12pt;margin:0">{{ __('Learner & Micro-Credential Summary') }}</h2>
    </div>
    <div class="bd" style="padding:12px;font-size:10pt;line-height:1.6">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 24px">
        <div><b>{{ __('Learner Name:') }}</b> {{ $d['e']->person }}</div>
        <div><b>{{ __('Learner ID / Email:') }}</b> {{ $d['e']->institution_id ?? '-' }}</div>
        <div><b>{{ __('Micro-Credential:') }}</b> {{ $d['e']->name_en }} (v{{ $d['e']->version_no }})</div>
        <div><b>{{ __('Cohort:') }}</b> {{ $d['e']->cohort_name }}</div>
        <div><b>{{ __('Faculty / PTj:') }}</b> {{ $d['e']->ptj_name ?? 'CODL' }}</div>
        <div><b>{{ __('Lecturer:') }}</b> {{ $d['e']->lecturer_name ?? '-' }}</div>
        <div><b>{{ __('Registration Reference:') }}</b> <code>{{ $d['e']->reference }}</code></div>
        <div>
          <b>{{ __('Completion Status:') }}</b>
          <span class="tag {{ $d['state'] === 'complete' ? 'ok' : 'info' }}">
            {{ __($stateLabel) }}
          </span>
          @if($d['rec']->completion_status)
            ({{ \Carbon\Carbon::parse($d['rec']->completed_on)->format('d/m/Y') }})
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="kpis" style="margin-bottom:16px">
    <div class="kpi">
      <div class="l">{{ __('Modules achieved') }}</div>
      <div class="v">{{ $nAch }} / {{ $nTot }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Verified results') }}</div>
      <div class="v">{{ count($d['results']) }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Final result') }}</div>
      <div class="v">{{ $d['final'] ? $d['final']['weighted_percent'].'%' : '—' }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Credentials awarded') }}</div>
      <div class="v">{{ count($d['credentials']) }}</div>
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="hd">
      <h2 style="font-size:12pt;margin:0">{{ __('Completed Modules & Earned Digital Badges') }}</h2>
    </div>
    <table>
      <thead>
        <tr>
          <th>{{ __('Module') }}</th>
          <th class="num">{{ __('Hours') }}</th>
          <th>{{ __('Earned Digital Badge') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Date') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($d['modules'] as $m)
          <tr>
            <td>
              <b>{{ $m->seq }}. {{ $m->name }}</b>
              @if(!empty($m->name_ms))<div class="small muted">{{ $m->name_ms }}</div>@endif
            </td>
            <td class="num">{{ $m->slt_hours ?? '-' }}</td>
            <td>
              @if($m->achieved)
                <code>{{ $m->achieved->code }}</code>
                @if($m->achieved->external_id) (UCreds {{ $m->achieved->external_id }}) @endif
              @else
                <span class="muted">{{ __('Not yet achieved') }}</span>
              @endif
            </td>
            <td>
              <span class="tag {{ $m->achieved ? 'ok' : 'muted' }}">
                {{ $m->achieved ? __('Achieved') : __('Not yet achieved') }}
              </span>
            </td>
            <td>{{ $m->achieved ? \Carbon\Carbon::parse($m->achieved->achieved_at)->format('d/m/Y') : '-' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if(count($d['results']))
    <div class="panel" style="margin-bottom:16px">
      <div class="hd">
        <h2 style="font-size:12pt;margin:0">{{ __('Verified Assessment Results') }}</h2>
      </div>
      <table>
        <thead>
          <tr>
            <th>{{ __('Assessment') }}</th>
            <th class="num">{{ __('Pass Mark') }}</th>
            <th class="num">{{ __('Mark Obtained') }}</th>
            <th>{{ __('Result') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($d['results'] as $r)
            <tr>
              <td>{{ $r->name_en }}</td>
              <td class="num">{{ rtrim(rtrim((string) $r->pass_mark, '0'), '.') }}</td>
              <td class="num">{{ rtrim(rtrim((string) $r->score, '0'), '.') }} / {{ rtrim(rtrim((string) $r->max_mark, '0'), '.') }}</td>
              <td>
                <span class="tag {{ (float) $r->score >= (float) $r->pass_mark ? 'ok' : 'err' }}">
                  {{ (float) $r->score >= (float) $r->pass_mark ? __('Met') : __('Not met') }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
        @if($d['final'])
          <tfoot>
            <tr>
              <th>{{ __('Final Result') }}</th>
              <th colspan="2" class="num">{{ $d['final']['weighted_percent'] }}%</th>
              <th><span class="tag ok">{{ $d['final']['grade'] ?? 'Pass' }}</span></th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  @endif

  @if(count($d['credentials']) || !empty($d['acr']))
    <div class="panel" style="margin-bottom:16px">
      <div class="hd">
        <h2 style="font-size:12pt;margin:0">{{ __('Credentials & Academic Credit Integration') }}</h2>
      </div>
      <div class="bd" style="padding:12px;font-size:10pt">
        @if(count($d['credentials']))
          <div style="margin-bottom:10px">
            <b>{{ __('Credentials Awarded:') }}</b>
            @foreach($d['credentials'] as $cr)
              <code style="margin-left:8px">{{ $cr->number }}</code> ({{ $cr->status }})
            @endforeach
          </div>
        @endif
        @if(!empty($d['acr']))
          <div>
            <b>{{ __('Academic Credit Record (Bahagian D):') }}</b>
            <code>{{ $d['acr']->reference }}</code> &ndash; {{ $d['acr']->credits }} {{ __('credits') }} ({{ $d['acr']->status }})
          </div>
        @endif
      </div>
    </div>
  @endif

@endif
