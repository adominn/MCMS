@extends('layouts.app')
@section('title', __('Quality review record'))
@section('content')

{{-- ===================== BREADCRUMB & HEADER ===================== --}}
<div class="crumb">
  <a href="{{ route('monitoring') }}">{{ __('Quality review') }}</a>
  <span>/</span>
  @if(!empty($offering))
    <span>{{ $offering->name_en }}</span>
    <span>/</span>
    <span>{{ $offering->cohort_name }}</span>
    <span>/</span>
  @endif
  <span>{{ $r->reference }}</span>
</div>

<div class="title" style="margin-bottom:20px">
  <div>
    <h1>{{ __('Quality review :ref', ['ref' => $r->reference]) }}</h1>
    <div class="sub">
      {{ __('Continuous Quality Improvement (CQI) record for cohort performance and evaluation') }}
    </div>
  </div>
  <div class="act" style="display:inline-flex;align-items:center;gap:10px">
    <x-pill :s="__(['draft' => 'Draft', 'submitted' => 'Submitted to the deputy dean', 'confirmed' => 'Confirmed'][$r->status] ?? $r->status)" id="cqi-status" />
    <a class="btn ter sm" href="{{ route('monitoring') }}">{{ __('All quality reviews') }}</a>
  </div>
</div>

{{-- ===================== OFFERING INFORMATION ===================== --}}
<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>{{ __('Offering information and monitoring period') }}</h2>
  </div>
  <div class="bd">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px">
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('Micro-credential') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">{{ $offering->name_en ?? __('Micro-credential') }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">
          Version {{ $offering->version_no ?? 1 }}
          @if(!empty($offering->category))
            &middot; <x-cat :c="$offering->category" />
          @endif
        </div>
      </div>
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('Cohort / Session') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">{{ $offering->cohort_name ?? __('Cohort') }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">
          @if(!empty($offering->start_date))
            {{ \Carbon\Carbon::parse($offering->start_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($offering->end_date)->format('d/m/Y') }}
          @else
            {{ ucfirst($offering->offering_status ?? 'Active') }}
          @endif
        </div>
      </div>
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('PTj / Faculty & Owner') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">{{ $offering->ptj_name ?? $offering->ptj_code ?? __('Faculty') }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">{{ $offering->owner_name ?? __('Academic Coordinator') }}</div>
      </div>
      <div>
        <div class="muted s" style="font-size:12px;text-transform:uppercase;font-weight:600">{{ __('Participants & Review Date') }}</div>
        <div style="font-weight:600;font-size:16px;color:var(--ink);margin-top:2px">
          {{ $enrolledCount ?? $ind['participants'] }} {{ __('registered') }} &middot; {{ $completedCount ?? 0 }} {{ __('completed') }}
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('Reviewed on:') }} {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</div>
      </div>
    </div>
  </div>
</div>

{{-- ===================== COHORT PERFORMANCE & FEEDBACK DATA ===================== --}}
<div style="margin-bottom:24px">
  <div style="margin-bottom:12px">
    <h2 style="margin:0 0 4px">{{ __('Cohort performance and evaluation data') }}</h2>
    <div class="muted s" style="font-size:13px">{{ __('Quantitative performance indicators, student feedback, and learning outcome attainment.') }}</div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:20px;align-items:start">

    {{-- Cohort Performance Indicators --}}
    <div class="panel" style="margin:0">
      <div class="hd">
        <h3>{{ __('Cohort performance indicators') }}</h3>
      </div>
      <div class="bd" style="padding:0">
        <table>
          <thead>
            <tr>
              <th>{{ __('Indicator') }}</th>
              <th style="width:25%">{{ __('Value') }}</th>
              <th style="width:25%">{{ __('Target') }}</th>
              <th style="width:20%"></th>
            </tr>
          </thead>
          <tbody>
          @foreach($ind['rows'] as $x)
            <tr>
              <td><b>{{ __($x['label']) }}</b></td>
              <td>{{ $x['value'] === null ? 'n/a' : $x['value'].'%' }}</td>
              <td>{{ $x['target'] }}%</td>
              <td>
                @if($x['below'])
                  <span class="tag overdue">{{ __('Below target') }}</span>
                @else
                  <span class="tag ok">{{ __('Met') }}</span>
                @endif
              </td>
            </tr>
          @endforeach
          <tr>
            <td><b>{{ __('Average & median score') }}</b></td>
            <td colspan="3">{{ $ind['average'] ?? 'n/a' }} &middot; {{ $ind['median'] ?? 'n/a' }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    {{-- Student Feedback Card --}}
    <div class="panel" style="margin:0">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <h3>{{ __('Student feedback') }}</h3>
        @if(count($ind['feedback']['comments']) > 0)
          <button type="button" class="btn ter sm" data-modal="feedback-comments">
            {{ __('View all comments') }} ({{ count($ind['feedback']['comments']) }})
          </button>
        @endif
      </div>
      <div class="bd">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--border)">
          <div style="font-weight:600">{{ __('Average Rating (Scale 1 – 5)') }}</div>
          <span class="tag info">{{ $ind['feedback']['count'] }} {{ __('responses') }}</span>
        </div>

        @if($ind['feedback']['count'] > 0 && !empty($ind['feedback']['areas']))
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">
            @foreach($ind['feedback']['areas'] as $areaKey => $score)
              <div style="background:var(--s1, #f4f4f4);padding:8px 10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:12px;font-weight:500">{{ __(config('monitoring.feedback_areas')[$areaKey] ?? ucfirst($areaKey)) }}</span>
                <b style="color:var(--primary);font-size:13px">{{ $score !== null ? number_format((float)$score, 1) : '–' }}</b>
              </div>
            @endforeach
          </div>
        @else
          <p class="muted s" style="margin:0 0 12px">{{ __('No numerical feedback responses submitted yet.') }}</p>
        @endif

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <div style="font-weight:600;font-size:12px;text-transform:uppercase;color:var(--muted)">
            {{ __('Student qualitative comments') }}
          </div>
          @if(count($ind['feedback']['comments']) > 2)
            <a href="javascript:void(0)" data-modal="feedback-comments" class="s" style="font-size:12px">
              {{ __('See all :count comments', ['count' => count($ind['feedback']['comments'])]) }} &rarr;
            </a>
          @endif
        </div>

        @php($previewComments = array_slice($ind['feedback']['comments'], 0, 2))
        @forelse($previewComments as $cmt)
          <div style="margin:6px 0;padding:8px 12px;border-left:3px solid var(--primary);background:var(--s1, #f4f4f4);font-size:13px;font-style:italic">
            &ldquo;{{ $cmt }}&rdquo;
          </div>
        @empty
          <p class="muted s" style="margin:0">{{ __('No written comments submitted for this cohort.') }}</p>
        @endforelse

        @if(count($ind['feedback']['comments']) > 2)
          <div style="margin-top:10px;text-align:right">
            <button type="button" class="btn ghost sm" data-modal="feedback-comments" style="font-size:12px">
              + {{ count($ind['feedback']['comments']) - 2 }} {{ __('more comments') }} &rarr;
            </button>
          </div>
        @endif
      </div>
    </div>

  </div>

  {{-- Learning Outcome Attainment (Full Width) --}}
  <div class="panel" style="margin-top:20px">
    <div class="hd">
      <h3>{{ __('Learning outcome attainment') }}</h3>
    </div>
    <div class="bd" style="padding:0">
      <table>
        <thead>
          <tr>
            <th style="width:12%">{{ __('Code') }}</th>
            <th style="width:60%">{{ __('Learning Outcome') }}</th>
            <th style="width:15%">{{ __('Attainment Share') }}</th>
            <th style="width:13%">{{ __('Evaluation') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($ind['outcomes'] as $lo)
          <tr>
            <td><b>{{ $lo['code'] }}</b></td>
            <td>{{ $lo['text'] ?? '-' }}</td>
            <td><b>{{ $lo['share'] === null ? 'n/a' : $lo['share'].'%' }}</b></td>
            <td>
              @if($lo['share'] !== null && $lo['share'] < 50)
                <span class="tag overdue">{{ __('Needs CQI') }}</span>
              @elseif($lo['share'] !== null)
                <span class="tag ok">{{ __('Satisfactory') }}</span>
              @else
                <span class="muted s">n/a</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="muted" style="text-align:center;padding:16px">{{ __('No learning outcomes mapped.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ===================== IMPLEMENTATION QUALITY DOMAINS ===================== --}}
<form method="post" action="{{ route('cqi.domains', $r->id) }}" class="panel" id="domains" style="margin-bottom:24px">
  @csrf
  <div class="hd">
    <h2>{{ __('Implementation quality domains') }}</h2>
    <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Evaluate curriculum alignment, delivery, assessment validity and academic integrity.') }}</div>
  </div>
  <div class="bd" style="padding:0">
    <table>
      <thead>
        <tr>
          <th style="width:30%">{{ __('Domain') }}</th>
          <th style="width:25%">{{ __('Status') }}</th>
          <th style="width:45%">{{ __('Evidence / Finding') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach(config('monitoring.domains') as $k => $label)
        @php($dc = $domains[$k] ?? null)
        <tr>
          <td><b>{{ __($label) }}</b></td>
          <td>
            @if($isOwner && $r->status === 'draft')
              <select name="met[{{ $k }}]" aria-label="Status of {{ $label }}" style="width:100%">
                <option value="">{{ __('Choose') }}</option>
                <option value="1" @selected($dc && $dc->met)>{{ __('Satisfactory') }}</option>
                <option value="0" @selected($dc && ! $dc->met)>{{ __('Needs action') }}</option>
              </select>
            @else
              @if($dc)
                <span class="tag {{ $dc->met ? 'ok' : 'overdue' }}">{{ $dc->met ? __('Satisfactory') : __('Needs action') }}</span>
              @else
                <span class="muted s">{{ __('Not checked') }}</span>
              @endif
            @endif
          </td>
          <td>
            @if($isOwner && $r->status === 'draft')
              <input type="text" name="finding[{{ $k }}]" value="{{ $dc->finding ?? '' }}" aria-label="Evidence for {{ $label }}" placeholder="{{ __('Describe evidence or finding...') }}" style="width:100%">
            @else
              {{ $dc->finding ?? '–' }}
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @if($isOwner && $r->status === 'draft')
    <div class="bd" style="border-top:1px solid var(--border);text-align:right">
      <button type="submit" class="btn sm">{{ __('Save domains') }}</button>
    </div>
  @endif
</form>

{{-- ===================== ISSUES AND ACTIONS ===================== --}}
<div style="margin-bottom:24px">
  <div style="margin-bottom:12px">
    <h2 style="margin:0 0 4px">{{ __('Issues, root cause and CQI action plan') }}</h2>
    <div class="muted s" style="font-size:13px">{{ __('Record issues identified from evidence, formulate targeted actions, and track their implementation.') }}</div>
  </div>

  @forelse($issues as $i)
    <div class="panel issue card" style="margin-bottom:16px;border-left:4px solid var(--primary)">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <span class="tag {{ $i->priority === 'high' ? 'overdue' : 'info' }}" style="font-weight:600">
            {{ __(config('monitoring.priority')[$i->priority] ?? ucfirst($i->priority)) }} {{ __('priority') }}
          </span>
          <b style="margin-left:8px">{{ $i->evidence }}</b>
        </div>
        <span class="muted s">{{ \Carbon\Carbon::parse($i->created_at)->format('d/m/Y') }}</span>
      </div>
      <div class="bd" style="padding-top:8px;padding-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px">
          <div><span class="muted s">{{ __('Root cause:') }}</span> <b>{{ $i->root_cause }}</b></div>
          <div><span class="muted s">{{ __('Impact:') }}</span> <b>{{ $i->impact }}</b></div>
        </div>
      </div>
      <div class="bd" style="padding:0;border-top:1px solid var(--border)">
        <table>
          <thead>
            <tr>
              <th style="width:45%">{{ __('Action') }}</th>
              <th style="width:20%">{{ __('Owner') }}</th>
              <th style="width:15%">{{ __('Due') }}</th>
              <th style="width:20%">{{ __('Status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          @foreach($actions[$i->id] ?? [] as $a)
            <tr class="action" id="action-{{ $a->id }}">
              <td>
                <b>{{ $a->description }}</b>
                <div class="muted s" style="margin-top:2px">
                  {{ __('Success indicator:') }} {{ $a->success_indicator }}
                  @if($a->affects)
                    <br><span style="color:var(--err)">{{ __('Needs a change request: :trigger.', ['trigger' => __($triggers[$a->affects] ?? $a->affects)]) }}</span>
                  @endif
                  @if($a->carried_from_id)
                    <br><span style="color:var(--warn-ink)">{{ __('Carried from an earlier action.') }}</span>
                  @endif
                </div>
              </td>
              <td>{{ $a->responsible }}</td>
              <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
              <td class="st">
                <x-pill :s="__(config('monitoring.action_status')[$a->status] ?? $a->status)" />
                @if($a->effectiveness)
                  <div style="font-size:11px;margin-top:4px;color:var(--muted)">
                    {{ __(config('monitoring.effectiveness')[$a->effectiveness] ?? $a->effectiveness) }}
                  </div>
                @endif
              </td>
              <td style="text-align:right">
                <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                  @if($isOwner && in_array($a->status, ['not_started', 'in_progress']))
                    <form method="post" action="{{ route('cqi.status', $a->id) }}" style="display:inline;margin:0">
                      @csrf
                      <button type="submit" class="btn sm ter" aria-label="{{ $a->status === 'not_started' ? 'Start' : 'Mark done' }}: {{ $a->description }}">
                        {{ $a->status === 'not_started' ? __('Start') : __('Mark done') }}
                      </button>
                    </form>
                  @endif

                  @if($isOwner && $a->status === 'done' && ! $a->evaluated_at)
                    <form method="post" action="{{ route('cqi.evaluate', $a->id) }}" style="display:inline-flex;gap:4px;align-items:center">
                      @csrf
                      <select name="effectiveness" aria-label="Effectiveness of {{ $a->description }}" style="height:32px;font-size:12px">
                        @foreach(config('monitoring.effectiveness') as $v => $t)
                          <option value="{{ $v }}">{{ __($t) }}</option>
                        @endforeach
                      </select>
                      <input type="text" name="before_evidence" placeholder="{{ __('Before') }}" aria-label="Before evidence for {{ $a->description }}" style="height:32px;font-size:12px;width:100px">
                      <input type="text" name="after_evidence" placeholder="{{ __('After') }}" aria-label="After evidence for {{ $a->description }}" style="height:32px;font-size:12px;width:100px">
                      <button type="submit" class="btn sm" aria-label="Evaluate: {{ $a->description }}">{{ __('Evaluate') }}</button>
                    </form>
                  @endif

                  @if($isDean && $a->status === 'done')
                    <form method="post" action="{{ route('cqi.close', $a->id) }}" style="display:inline;margin:0">
                      @csrf
                      <button type="submit" class="btn sm" aria-label="Close: {{ $a->description }}">{{ __('Close action') }}</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="panel" style="margin-bottom:16px">
      <div class="bd" style="text-align:center;padding:24px;color:var(--muted)">
        {{ __('No issue recorded.') }}
      </div>
    </div>
  @endforelse

  {{-- FORM: RECORD AN ISSUE AND ACTION (2 FIELDS PER ROW) --}}
  @if($isOwner && $r->status !== 'confirmed')
    <form method="post" action="{{ route('cqi.issue', $r->id) }}" class="panel card" id="new-issue" style="margin-top:20px">
      @csrf
      <div class="hd">
        <h2>{{ __('Record an issue and an action') }}</h2>
        <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Define the root cause, impact, priority and an actionable improvement with success metrics.') }}</div>
      </div>
      <div class="bd">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

          {{-- Row 1: Evidence & Root Cause --}}
          <div>
            <label for="evidence" style="font-weight:600">{{ __('Evidence') }} <span style="color:var(--err)">*</span></label>
            <input id="evidence" name="evidence" aria-label="Evidence" type="text" required placeholder="{{ __('e.g., Module 2 pass rate was low (67%)') }}" style="width:100%">
          </div>
          <div>
            <label for="root_cause" style="font-weight:600">{{ __('Root cause') }} <span style="color:var(--err)">*</span></label>
            <input id="root_cause" name="root_cause" aria-label="Root cause" type="text" required placeholder="{{ __('e.g., Insufficient hands-on examples in week 3') }}" style="width:100%">
          </div>

          {{-- Row 2: Impact & Priority --}}
          <div>
            <label for="impact" style="font-weight:600">{{ __('Impact') }} <span style="color:var(--err)">*</span></label>
            <input id="impact" name="impact" aria-label="Impact" type="text" required placeholder="{{ __('e.g., 2 participants struggled with practical assessment') }}" style="width:100%">
          </div>
          <div>
            <label for="priority" style="font-weight:600">{{ __('Priority') }} <span style="color:var(--err)">*</span></label>
            <select id="priority" name="priority" style="width:100%">
              @foreach(config('monitoring.priority') as $v => $t)
                <option value="{{ $v }}">{{ __($t) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Row 3: Action & Due Date --}}
          <div>
            <label for="description" style="font-weight:600">{{ __('Improvement action') }} <span style="color:var(--err)">*</span></label>
            <input id="description" name="description" type="text" required placeholder="{{ __('e.g., Add 2 worked tutorial videos and code walk-throughs') }}" style="width:100%">
          </div>
          <div>
            <label for="due_on" style="font-weight:600">{{ __('Due date') }} <span style="color:var(--err)">*</span></label>
            <input id="due_on" name="due_on" type="date" required style="width:100%">
          </div>

          {{-- Row 4 (Full Width): Success indicator --}}
          <div style="grid-column: span 2">
            <label for="success_indicator" style="font-weight:600">{{ __('Success indicator') }} <span style="color:var(--err)">*</span></label>
            <input id="success_indicator" name="success_indicator" aria-label="Success indicator" type="text" required placeholder="{{ __('e.g., Pass rate on module 2 exceeds 75% in next cohort') }}" style="width:100%">
          </div>

          {{-- Row 5 (Full Width): Change Trigger --}}
          <div style="grid-column: span 2">
            <label for="affects" style="font-weight:600">{{ __('Changes a listed component?') }}</label>
            <select id="affects" name="affects" style="width:100%">
              <option value="">{{ __('No (Minor operational adjustment)') }}</option>
              @foreach($triggers as $v => $t)
                <option value="{{ $v }}">{{ __($t) }}</option>
              @endforeach
            </select>
          </div>

        </div>
      </div>
      <div class="bd" style="border-top:1px solid var(--border);text-align:right">
        <button type="submit" class="btn sm">{{ __('Record') }}</button>
      </div>
    </form>
  @endif
</div>

{{-- ===================== SUBMISSION & DEAN CONFIRMATION ===================== --}}
@if($isOwner && $r->status === 'draft')
  <div class="panel" style="margin-bottom:24px;border-left:4px solid var(--primary)">
    <div class="hd">
      <h2>{{ __('Submit Quality Review for Faculty Confirmation') }}</h2>
      <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Once submitted, the record is locked for review by the Deputy Dean (Academic).') }}</div>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('cqi.submit', $r->id) }}">
        @csrf
        <button type="submit" class="btn">{{ __('Submit to the deputy dean') }}</button>
      </form>
    </div>
  </div>
@endif

@if($isDean && $r->status === 'submitted')
  <form method="post" action="{{ route('cqi.confirm', $r->id) }}" class="panel card" id="confirm" style="margin-bottom:24px;border-left:4px solid #24a148">
    @csrf
    <div class="hd">
      <h2>{{ __('Confirm the quality review record') }}</h2>
      <div class="sub" style="font-size:13px;color:var(--muted)">{{ __('Verify that quality domains and improvement actions meet faculty academic governance standards.') }}</div>
    </div>
    <div class="bd">
      @foreach(config('monitoring.confirmations') as $k => $t)
        <label style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;cursor:pointer">
          <input type="checkbox" name="confirm[{{ $k }}]" value="1" style="margin-top:3px">
          <span>{{ __($t) }}</span>
        </label>
      @endforeach
    </div>
    <div class="bd" style="border-top:1px solid var(--border)">
      <button type="submit" class="btn sm">{{ __('Confirm') }}</button>
    </div>
  </form>
@endif

{{-- ===================== STUDENT FEEDBACK COMMENTS MODAL ===================== --}}
<div class="scrim" id="modal-feedback-comments" hidden style="display:none;position:fixed;inset:0;background:rgba(22,22,22,.5);z-index:70;align-items:center;justify-content:center">
  <div class="modal" role="dialog" aria-modal="true" aria-label="{{ __('Student feedback comments') }}" style="max-width:640px;width:90%;max-height:85vh;display:flex;flex-direction:column;background:#fff;border-radius:0;box-shadow:0 4px 20px rgba(0,0,0,.15)">
    <div class="mh" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border)">
      <div>
        <h2 style="margin:0;font-size:18px">{{ __('Student feedback comments') }}</h2>
        <div class="muted s" style="font-size:12px">{{ count($ind['feedback']['comments']) }} {{ __('comments submitted for this cohort') }}</div>
      </div>
      <button type="button" class="btn ghost sm" data-close aria-label="Close" style="font-size:20px;cursor:pointer">&times;</button>
    </div>
    <div class="mb" style="overflow-y:auto;flex:1;padding:20px">
      @forelse($ind['feedback']['comments'] as $cmt)
        <div style="margin-bottom:12px;padding:12px 16px;border-left:3px solid var(--primary);background:var(--s1, #f4f4f4);font-size:13px;line-height:1.5">
          &ldquo;{{ $cmt }}&rdquo;
        </div>
      @empty
        <p class="muted" style="text-align:center;padding:24px">{{ __('No written comments submitted for this cohort.') }}</p>
      @endforelse
    </div>
    <div class="mf" style="display:flex;justify-content:flex-end;padding:12px 20px;border-top:1px solid var(--border);background:var(--s1, #f4f4f4)">
      <button type="button" class="btn ter sm" data-close>{{ __('Close') }}</button>
    </div>
  </div>
</div>

@endsection