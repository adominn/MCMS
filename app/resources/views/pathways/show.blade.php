@extends('layouts.app')
@section('title', 'Pathway mapping')
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('pathways') }}">Stackable pathways</a>
  <span>/</span>
  <span>{{ $w->reference }}</span>
</div>

<div class="title" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
  <div>
    <h1>Pathway {{ $w->reference }}</h1>
    <div class="sub">{{ $w->programme }} &mdash; {{ $w->programme_name }}</div>
  </div>
  <div class="act" style="display:flex;align-items:center;gap:10px">
    <span class="tag {{ $w->status === 'published' ? 'ok' : ($w->status === 'approved' ? 'info' : 'warn') }}">
      <strong id="pw-status">{{ $status[$w->status] ?? ucfirst(str_replace('_', ' ', $w->status)) }}</strong>
    </span>
  </div>
</div>

<div class="panel" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="bd" style="padding:16px 20px">
    {{-- Structured Programme Specification Strip --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:14px;padding-bottom:14px;border-bottom:1px solid var(--hairline)">
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Programme Code') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px">{{ $w->programme }}</div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('MQF Level') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px">Level {{ $w->mqf_level }}</div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Total Credits') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px">{{ $w->total_credits }} {{ __('credits') }}</div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Delivery Mode') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px;text-transform:capitalize">{{ $w->mode }}</div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Accreditation') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px;text-transform:capitalize">{{ $w->accreditation_status }}</div>
      </div>
      @if($w->intake)
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Intake Cohort') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px">{{ $w->intake }}</div>
      </div>
      @endif
      @if($w->approval_ref)
      <div>
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Senate Reference') }}</div>
        <div style="font-size:14px;font-weight:600;color:var(--ink);margin-top:2px">{{ $w->approval_ref }}</div>
      </div>
      @endif
    </div>

    <div style="margin-top:12px;font-size:13px;color:var(--ink-muted)">
      {{ __("An approved mapping transfers no credit. Each participant's credit still goes through credit transfer evaluation, and admission is decided separately.") }}
    </div>

    @if($w->review_reason)
      <div class="note warn" style="margin-top:12px" id="review-reason">
        <b>{{ __('Flagged for review:') }}</b> {{ $w->review_reason }}
      </div>
    @endif
    @if($w->approval_ref && !$w->review_reason)
      <div class="note ok" style="margin-top:12px">
        Approved on {{ \Carbon\Carbon::parse($w->approved_on)->format('d/m/Y') }}, Senate reference {{ $w->approval_ref }}.
      </div>
    @endif
  </div>
</div>

@php
  $totalCredits = (float) $w->total_credits;
  $mappedCredits = (float) $items->sum('credits');
  $pct = $totalCredits > 0 ? min(round(($mappedCredits / $totalCredits) * 100, 1), 100) : 0;
  $maxTransferCredits = round($totalCredits * 0.7, 1);
  $minHomeCredits = round($totalCredits * 0.3, 1);
  $articulationPct = min($pct, 70.0);
  $headroomPct = max(0, 70.0 - $pct);
  $homePct = 30.0;
@endphp

{{-- Institutional Degree Articulation Capacity --}}
<div class="panel" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <div>
      <h2 style="margin:0;font-size:16px;font-weight:600">{{ __('Institutional Degree Articulation Capacity') }}</h2>
      <div class="muted small" style="margin-top:2px">{{ __('Curriculum mapping metrics and credit articulation capacity') }}</div>
    </div>
    <div style="display:inline-flex;align-items:center;gap:8px">
      <span class="tag {{ $pct <= 70 ? 'ok' : 'warn' }}">{{ $mappedCredits }} / {{ $totalCredits }} {{ __('credits mapped') }} ({{ $pct }}%)</span>
    </div>
  </div>
  <div class="bd">
    {{-- 4-Metric Carbon Summary Grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:12px;margin-bottom:16px">
      <div style="background:var(--surface-1, #f4f4f4);padding:12px 14px;border:1px solid var(--hairline)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Total Degree Requirement') }}</div>
        <div style="font-size:20px;font-weight:600;color:var(--ink);margin-top:4px">{{ $totalCredits }} <span style="font-size:13px;font-weight:400">{{ __('credits') }}</span></div>
        <div class="muted small" style="margin-top:2px">{{ __('100% Graduating curriculum') }}</div>
      </div>
      <div style="background:var(--surface-1, #f4f4f4);padding:12px 14px;border:1px solid var(--hairline)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--primary, #1e2a6e);letter-spacing:0.5px">{{ __('Articulated via MCs') }}</div>
        <div style="font-size:20px;font-weight:600;color:var(--primary, #1e2a6e);margin-top:4px">{{ $mappedCredits }} <span style="font-size:13px;font-weight:400">{{ __('credits') }}</span></div>
        <div class="muted small" style="margin-top:2px">{{ count($items) }} {{ __('degree courses mapped') }} ({{ $pct }}%)</div>
      </div>
      <div style="background:var(--surface-1, #f4f4f4);padding:12px 14px;border:1px solid var(--hairline)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Max Articulation Ceiling') }}</div>
        <div style="font-size:20px;font-weight:600;color:var(--ink);margin-top:4px">&le; {{ $maxTransferCredits }} <span style="font-size:13px;font-weight:400">{{ __('credits') }}</span></div>
        <div class="muted small" style="margin-top:2px">{{ __('Up to 70% allowable from micro-credentials') }}</div>
      </div>
      <div style="background:var(--surface-1, #f4f4f4);padding:12px 14px;border:1px solid var(--hairline)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--ink-muted);letter-spacing:0.5px">{{ __('Mandatory UTeM Direct Study') }}</div>
        <div style="font-size:20px;font-weight:600;color:var(--ink);margin-top:4px">&ge; {{ $minHomeCredits }} <span style="font-size:13px;font-weight:400">{{ __('credits') }}</span></div>
        <div class="muted small" style="margin-top:2px">{{ __('Minimum 30% direct home institution study') }}</div>
      </div>
    </div>

    {{-- Flat Multi-Segment Carbon Articulation Bar --}}
    <div style="margin-bottom:8px">
      <div style="display:flex;height:12px;background:var(--surface-2, #e0e0e0);border-radius:2px;overflow:hidden">
        {{-- Mapped credits in uLearn navy --}}
        <div style="background:var(--primary, #1e2a6e);width:{{ $articulationPct }}%;height:100%" title="{{ __('Mapped Micro-Credentials') }}: {{ $mappedCredits }} cr ({{ $pct }}%)"></div>
        {{-- Available articulation headroom up to 70% in light blue --}}
        @if($headroomPct > 0)
          <div style="background:#8ab4f8;width:{{ $headroomPct }}%;height:100%" title="{{ __('Allowable Articulation Headroom') }}: {{ max(0, round($maxTransferCredits - $mappedCredits, 1)) }} cr ({{ round($headroomPct, 1) }}%)"></div>
        @endif
        {{-- Mandatory direct UTeM home study in neutral dark grey --}}
        <div style="background:#525252;width:{{ $homePct }}%;height:100%" title="{{ __('Mandatory Direct UTeM Study') }}: {{ $minHomeCredits }} cr (30%)"></div>
      </div>
    </div>

    {{-- Legend for Capacity Bar --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;font-size:12px;color:var(--ink-muted);margin-bottom:14px">
      <div style="display:inline-flex;align-items:center;gap:6px">
        <span style="display:inline-block;width:10px;height:10px;background:var(--primary, #1e2a6e);border-radius:1px"></span>
        <span><b>{{ __('Currently mapped') }}:</b> {{ $mappedCredits }} cr ({{ $pct }}%)</span>
      </div>
      <div style="display:inline-flex;align-items:center;gap:6px">
        <span style="display:inline-block;width:10px;height:10px;background:#8ab4f8;border-radius:1px"></span>
        <span><b>{{ __('Allowable articulation headroom') }}:</b> {{ max(0, round($maxTransferCredits - $mappedCredits, 1)) }} cr (up to 70%)</span>
      </div>
      <div style="display:inline-flex;align-items:center;gap:6px">
        <span style="display:inline-block;width:10px;height:10px;background:#525252;border-radius:1px"></span>
        <span><b>{{ __('Mandatory direct UTeM study') }}:</b> &ge; {{ $minHomeCredits }} cr (30% floor)</span>
      </div>
    </div>

    {{-- Institutional Articulation Compliance Notes --}}
    <div style="background:var(--surface-1, #f4f4f4);border-left:3px solid var(--primary, #1e2a6e);padding:10px 14px;font-size:12px;color:var(--ink-muted);display:flex;flex-direction:column;gap:6px">
      <div>
        @if($mappedCredits <= $maxTransferCredits)
          <span style="color:var(--semantic-success, #24a148);font-weight:600">&check; {{ __('Home institution quota compliant') }}:</span>
          {{ __('Mapped credits (:mapped cr) do not exceed the 70% institutional articulation limit (:max cr), preserving at least 30% (:min cr) for direct study at UTeM as the home awarding institution.', ['mapped' => $mappedCredits, 'max' => $maxTransferCredits, 'min' => $minHomeCredits]) }}
        @else
          <span style="color:var(--semantic-error, #da1e28);font-weight:600">&excl; {{ __('Home institution quota exceeded') }}:</span>
          {{ __('Mapped micro-credential credits (:mapped cr) exceed the 70% maximum allowable articulation limit (:max cr). Individual student plans cannot articulate more than 70% of graduating credits.', ['mapped' => $mappedCredits, 'max' => $maxTransferCredits]) }}
        @endif
      </div>
      <div>
        &bull; <b>{{ __('Residency requirement') }}:</b> {{ __('Conventional degree students must complete at least 1 semester on-campus residency at UTeM regardless of credit transfers.') }}
      </div>
      <div>
        &bull; <b>{{ __('Mapping is not transfer') }}:</b> {{ __('Curriculum roadmap approval establishes academic equivalence only; credit transfer is formally decided upon learner application.') }}
      </div>
    </div>
  </div>
</div>

<div class="panel" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <div>
      <h2 style="margin:0;font-size:16px;font-weight:600">{{ __('Mapped degree courses') }}</h2>
      <div class="muted small" style="margin-top:2px">{{ __('Equivalence pairings between eligible micro-credentials and parent degree courses') }}</div>
    </div>
    <span class="r"><span class="s muted">{{ count($items) }} {{ __('courses') }}</span></span>
  </div>
  <table id="items">
    <thead>
      <tr>
        <th>{{ __('Micro-credential') }}</th>
        <th>{{ __('Target course') }}</th>
        <th style="text-align:right">{{ __('Credits') }}</th>
        <th>{{ __('Outcomes') }}</th>
        <th>{{ __('Content') }}</th>
        <th>{{ __('Preliminary') }}</th>
        <th>{{ __('PLO') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($items as $i)
      <tr>
        <td>
          <b>{{ $i->name_en }}</b>
          <span class="tag sm">{{ strtoupper($i->category) }}</span>
        </td>
        <td>{{ $i->course_code }} {{ $i->course_name }}</td>
        <td style="text-align:right;font-weight:600">{{ rtrim(rtrim((string) $i->credits, '0'), '.') }}</td>
        <td>{{ config('pathways.equivalence')[$i->lo_equivalence] ?? ucfirst($i->lo_equivalence) }}</td>
        <td>{{ config('pathways.equivalence')[$i->content_equivalence] ?? ucfirst($i->content_equivalence) }}</td>
        <td><span class="tag {{ $i->preliminary === 'likely' ? 'ok' : 'info' }}">{{ config('pathways.preliminary')[$i->preliminary] ?? ucfirst($i->preliminary) }}</span></td>
        <td class="small">{{ collect(json_decode($i->plo_map, true))->map(fn ($p) => $p['plo'].' ('.$p['contribution'].')')->implode(', ') }}</td>
        <td style="text-align:right">
          @if($editable)
            <form method="post" action="{{ route('pathways.remove', [$w->id, $i->id]) }}" style="display:inline">
              @csrf
              <button type="submit" class="btn danger sm" aria-label="Remove {{ $i->name_en }}">Remove</button>
            </form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="8" class="muted" style="text-align:center;padding:24px">Nothing mapped yet.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

@if($gaps)
  <div class="panel note warn" id="gaps" style="margin-bottom:24px">
    <div><strong>{{ __('No coverage yet for:') }}</strong> {{ implode(', ', $gaps) }}.</div>
  </div>
@endif

@if($editable)
<form method="post" action="{{ route('pathways.item', $w->id) }}" class="card panel" id="add-item" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="hd">
    <h2 style="margin:0;font-size:16px;font-weight:600">{{ __('Map a credential to a course') }}</h2>
    <div class="muted small" style="margin-top:2px">{{ __('Pair an eligible micro-credential to a parent degree course') }}</div>
  </div>
  <div class="bd">
    @csrf
    <p class="muted small" style="margin-top:0;margin-bottom:16px">{{ __('Only credit-bearing credentials can be mapped: a UMC, or a SAMC with valid Quality Verification. An MCE is refused.') }}</p>
    
    <div class="form-grid cols-2" style="margin-bottom:16px">
      <div class="field">
        <label for="micro_credential_id" style="font-weight:600;font-size:13px">{{ __('Micro-credential') }}</label>
        <select id="micro_credential_id" name="micro_credential_id">
          @foreach($eligible as $m)
            <option value="{{ $m->id }}">{{ $m->name_en }} ({{ strtoupper($m->category) }})</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="parent_course_id" style="font-weight:600;font-size:13px">{{ __('Target course') }}</label>
        <select id="parent_course_id" name="parent_course_id">
          @foreach($courses as $c)
            <option value="{{ $c->id }}">{{ $c->code }} {{ $c->name }} ({{ $c->credit }} credit)</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="form-grid cols-4" style="margin-bottom:16px">
      <div class="field">
        <label for="credits" style="font-weight:600;font-size:13px">{{ __('Credits') }}</label>
        <input id="credits" name="credits" type="number" step="0.5" min="0.5" required placeholder="3.0">
      </div>

      <div class="field">
        <label for="lo_equivalence" style="font-weight:600;font-size:13px">{{ __('Learning-outcome equivalence') }}</label>
        <select id="lo_equivalence" name="lo_equivalence">
          @foreach(config('pathways.equivalence') as $v => $t)
            <option value="{{ $v }}">{{ $t }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="content_equivalence" style="font-weight:600;font-size:13px">{{ __('Content equivalence') }}</label>
        <select id="content_equivalence" name="content_equivalence">
          @foreach(config('pathways.equivalence') as $v => $t)
            <option value="{{ $v }}">{{ $t }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="preliminary" style="font-weight:600;font-size:13px">{{ __('Preliminary result') }}</label>
        <select id="preliminary" name="preliminary">
          @foreach(config('pathways.preliminary') as $v => $t)
            <option value="{{ $v }}">{{ $t }}</option>
          @endforeach
        </select>
      </div>
    </div>

    @if($plos)
      <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface-1, #f4f4f4);margin-bottom:16px">
        <legend style="font-weight:600;font-size:13px;padding:0 6px;color:var(--ink)">{{ __('Programme learning outcomes this credential supports') }}</legend>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:10px">
          @foreach($plos as $p)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;background:#ffffff;padding:8px 12px;border:1px solid var(--hairline)">
              <label style="display:inline-flex;gap:6px;align-items:center;cursor:pointer;font-weight:600;margin:0;font-size:13px">
                <input type="checkbox" name="plo[]" value="{{ $p }}"> {{ $p }}
              </label>
              <select name="contribution[{{ $p }}]" aria-label="Contribution to {{ $p }}" style="font-size:12px;padding:2px 6px;width:auto;min-height:30px">
                @foreach(config('pathways.contribution') as $v => $t)
                  <option value="{{ $v }}">{{ $t }}</option>
                @endforeach
              </select>
            </div>
          @endforeach
        </div>
      </fieldset>
    @endif

    @if($bok)
      <fieldset style="border:1px solid var(--hairline);padding:14px 16px;background:var(--surface-1, #f4f4f4);margin-bottom:16px">
        <legend style="font-weight:600;font-size:13px;padding:0 6px;color:var(--ink)">{{ __('Body of Knowledge domains') }}</legend>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:10px">
          @foreach($bok as $b)
            <label style="display:inline-flex;gap:8px;align-items:center;margin:0;cursor:pointer;background:#ffffff;padding:8px 12px;border:1px solid var(--hairline);font-size:13px">
              <input type="checkbox" name="bok[]" value="{{ $b }}"> <span>{{ $b }}</span>
            </label>
          @endforeach
        </div>
      </fieldset>
    @endif

    <div class="form-grid cols-3-1" style="align-items:end;margin-bottom:0">
      <div class="field">
        <label for="note" style="font-weight:600;font-size:13px">{{ __('Note') }}</label>
        <input id="note" name="note" type="text" placeholder="Optional notes on mapping">
      </div>
      <div>
        <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--primary, #1e2a6e);color:#ffffff">{{ __('Save mapping') }}</button>
      </div>
    </div>
  </div>
</form>
@endif

@if(auth()->user()->hasAnyRole('deputy_dean') && in_array($w->status, ['draft', 'review', 'in_review']))
<form method="post" action="{{ route('pathways.approve', $w->id) }}" class="card panel" id="approve" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="hd">
    <h2 style="margin:0;font-size:16px;font-weight:600">{{ __('Record the Senate approval') }}</h2>
    <div class="muted small" style="margin-top:2px">{{ __('Institutional signoff and meeting reference') }}</div>
  </div>
  <div class="bd">
    @csrf
    <p class="muted small" style="margin-top:0;margin-bottom:14px">{{ __('A mapping must be approved before it is published to participants.') }}</p>
    <div class="form-grid cols-2">
      <div class="field">
        <label for="approval_ref" style="font-weight:600;font-size:13px">{{ __('Senate meeting reference') }}</label>
        <input id="approval_ref" name="approval_ref" type="text" placeholder="e.g. Senate 8/2026" required>
      </div>
      <div class="field">
        <label for="approved_on" style="font-weight:600;font-size:13px">{{ __('Date') }}</label>
        <input id="approved_on" name="approved_on" type="date" value="{{ today()->toDateString() }}" required>
      </div>
    </div>
    <button type="submit" class="btn" style="background:var(--primary, #1e2a6e);color:#ffffff">{{ __('Record approval') }}</button>
  </div>
</form>
@endif

@if(auth()->user()->hasAnyRole('deputy_dean') && $w->status === 'approved')
<form method="post" action="{{ route('pathways.publish', $w->id) }}" class="card panel" style="margin-bottom:24px;border:1px solid var(--hairline);background:#ffffff">
  <div class="hd">
    <h2 style="margin:0;font-size:16px;font-weight:600">{{ __('Publish to participants') }}</h2>
    <div class="muted small" style="margin-top:2px">{{ __('Make roadmap publicly accessible on the MCMS catalogue') }}</div>
  </div>
  <div class="bd">
    @csrf
    <p class="muted small" style="margin-top:0">{{ __('Approved pathway mappings become visible on the public catalogue once published.') }}</p>
    <button type="submit" class="btn ok">{{ __('Publish to participants') }}</button>
  </div>
</form>
@endif
@endsection
