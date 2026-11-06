@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', 'Transfer application')
@section('doc_title', 'Credit Transfer Decision')
@section('doc_bm', 'Keputusan Pindahan Kredit')
@section('doc_ref', $a->reference)
@section('doc_file', 'transfer-decision-'.$a->reference.'.pdf')
@section('content')

@php
  $isPrint = request()->boolean('print') || request()->has('print');
  $stage = match ($a->status) {
    'submitted' => 1,
    'under_eval', 'more_evidence' => 2,
    default => 4
  };
  $decided = in_array($a->status, ['approved', 'partly', 'not_approved'], true);
@endphp

@if(!$isPrint)
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ route('transfer') }}">{{ __('Credit transfer') }}</a>
    <span>/</span>
    <span>{{ $a->reference }}</span>
  </div>

  <div class="title">
    <div>
      <h1>Transfer application {{ $a->reference }}</h1>
      <div class="sub">{{ $a->programme }} {{ $a->programme_name }} &middot; applied {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }}</div>
    </div>
    <div class="act" style="align-items:center;display:flex;gap:8px">
      <span class="tag {{ $a->status === 'approved' ? 'ok' : (in_array($a->status, ['submitted', 'under_eval'], true) ? 'info' : ($a->status === 'partly' ? 'warn' : 'err')) }}" id="app-status">
        {{ $status[$a->status] ?? ucfirst($a->status) }}
      </span>
      @if($decided)
        <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter" target="_blank">{{ __('Decision letter (PDF)') }}</a>
      @endif
    </div>
  </div>

  <div class="track" style="margin-bottom:24px">
    <div class="stp {{ $stage > 0 ? 'done' : 'now' }}">
      <b>1. {{ __("Submitted") }}</b>
      {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }}
    </div>
    <div class="stp {{ $stage > 1 ? 'done' : ($stage === 1 ? 'now' : '') }}">
      <b>2. {{ __("Record check") }}</b>
      {{ $stage > 1 ? __('Done') : __('In progress') }}
    </div>
    <div class="stp {{ $stage > 2 ? 'done' : ($stage === 2 ? 'now' : '') }}">
      <b>3. {{ __("Equivalence evaluation") }}</b>
      {{ $a->status === 'more_evidence' ? __('Waiting for your evidence') : ($stage === 2 ? __('In progress') : ($stage > 2 ? __('Done') : __('Pending'))) }}
    </div>
    <div class="stp {{ $decided ? 'done' : '' }}">
      <b>4. {{ __("Decision") }}</b>
      {{ $decided ? ($decision ? $status[$decision->outcome] : ucfirst($a->status)) : __('Pending') }}
    </div>
  </div>

  @if($decided && $decision)
    <div class="note {{ $decision->outcome === 'approved' ? 'ok' : ($decision->outcome === 'not_approved' ? 'err' : 'warn') }}" id="decision" style="margin-bottom:20px">
      <div>
        <b>{{ $status[$decision->outcome] }} on {{ \Carbon\Carbon::parse($decision->decided_on)->format('d/m/Y') }}</b>@if($decision->decision_ref), reference {{ $decision->decision_ref }}@endif.
        @if($decision->conditions)<div style="margin-top:4px">Conditions: {{ $decision->conditions }}</div>@endif
      </div>
    </div>
  @endif

  <div class="cols">
    <div>
      @if($a->status === 'more_evidence')
        <div class="panel" style="margin-bottom:20px">
          <div class="hd"><h2>{{ __("More evidence is needed") }}</h2></div>
          <div class="bd">
            <div class="note warn" style="margin-bottom:16px">
              <div>The evaluator asked for more evidence before deciding: {{ $a->evidence_request }}</div>
            </div>
            <form method="post" action="{{ route('transfer.evidence', $a->id) }}">
              @csrf
              <div class="field" style="margin-bottom:14px">
                <label for="documents">{{ __("What you are adding") }}</label>
                <textarea id="documents" name="documents" rows="3" required placeholder="{{ __("Describe what you are adding or provide reference links...") }}"></textarea>
              </div>
              <button type="submit" class="btn">{{ __("Send evidence") }}</button>
            </form>
          </div>
        </div>
      @endif

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __("Transfer lines") }}</h2></div>
        <div class="bd" style="padding:0">
          <table id="lines" style="margin:0">
            <thead>
              <tr>
                <th>{{ __("Credit used") }}</th>
                <th>{{ __("Course requested") }}</th>
                <th class="num" style="width:100px">{{ __("Requested") }}</th>
                <th class="num" style="width:100px">{{ __("Approved") }}</th>
                <th style="width:160px">{{ __("Credit status") }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lines as $l)
                <tr>
                  <td>
                    <b>{{ $l->source_name }}</b><br>
                    <span class="muted small">{{ $l->acr_reference }}</span>
                  </td>
                  <td>{{ $l->course_code }} {{ $l->course_name }}</td>
                  <td class="num">{{ rtrim(rtrim((string) $l->credits_requested, '0'), '.') }}</td>
                  <td class="num">{{ $l->credits_approved !== null ? (rtrim(rtrim((string) $l->credits_approved, '0'), '.') ?: '0') : '—' }}</td>
                  <td>
                    {{ ['available' => __('Available'), 'provisional' => __('Provisionally redeemed'), 'redeemed' => __('Redeemed'), 'expired' => __('Expired')][$l->credit_status] ?? '' }}
                    @if($l->reevaluate)<br><span class="tag warn">{{ __("Needs re-evaluation") }}</span>@endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      @if($a->documents)
        <details class="fold" style="margin-top:16px">
          <summary><b>{{ __("Supporting documents & notes") }}</b></summary>
          <div class="bd">
            <p style="white-space:pre-line;margin:0">{{ $a->documents }}</p>
          </div>
        </details>
      @endif
    </div>

    <div>
      <div class="panel">
        <div class="hd"><h2>{{ __("What this means") }}</h2></div>
        <div class="bd">
          <p class="small" style="margin:0 0 12px">{{ __("The decision is made by the receiving programme. It does not admit you to the programme, and a refusal does not change your Academic Credit Record, badges, certificates or credentials.") }}</p>
          <p class="small" style="margin:0 0 12px">{{ __("If a line is not approved, your ACR and credit stay as they were. An approved transfer is storage redemption, not degree graduation.") }}</p>
        </div>
      </div>

      <div style="margin-top:12px">
        <a class="btn ghost sm" href="{{ route('transfer') }}">&larr; {{ __('Back to credit transfer') }}</a>
      </div>
    </div>
  </div>

@else
  {{-- Printable Decision Letter Sheet --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <div style="font-size:10.5pt;margin-bottom:4px">{{ now()->format('d F Y') }}</div>
    <div style="font-size:11pt">
      <b>{{ auth()->user()->name }}</b><br>
      <span class="muted small">Learner ID {{ auth()->user()->institution_id ?? auth()->user()->email }}</span>
    </div>
  </div>

  @if(!$decided)
    <div class="note warn" style="margin-bottom:16px">
      <div><b>{{ __("Draft, not issued.") }}</b> {{ __("The deputy dean has not recorded a decision on this application, so no outcome is final below.") }}</div>
    </div>
  @endif

  <h1 style="font-size:18pt;margin:0 0 8px">Decision on credit transfer {{ $a->reference }}</h1>
  <div style="font-size:10.5pt;line-height:1.5;margin-bottom:6px">
    Dear {{ auth()->user()->name }}, this letter gives the decision on your application of {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }} for credit transfer into {{ $a->programme_name }} ({{ $a->programme }}). Each course line was decided separately.
  </div>
  <div class="bm small" style="margin-bottom:20px;font-size:9pt;color:var(--text-secondary)">
    Surat ini memaklumkan keputusan permohonan pindahan kredit anda ke program {{ $a->programme }}. Setiap baris kursus diputuskan secara berasingan.
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __("Outcome for each line") }}</h2></div>
    <div class="bd" style="padding:0">
      <table style="margin:0">
        <thead>
          <tr>
            <th style="width:30px">#</th>
            <th>{{ __("ACR and micro-credential") }}</th>
            <th>{{ __("Course applied for") }}</th>
            <th class="num" style="width:90px">{{ __("Requested") }}</th>
            <th style="width:110px">{{ __("Outcome") }}</th>
            <th class="num" style="width:90px">{{ __("Approved") }}</th>
            <th>{{ __("Conditions") }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($lines as $idx => $l)
            @php($isOk = $l->decision === 'approved' || ($l->credits_approved && (float)$l->credits_approved > 0))
            <tr>
              <td>{{ $idx + 1 }}</td>
              <td>
                <b>{{ $l->source_name }}</b>
                <div class="small muted">{{ $l->acr_reference }}</div>
              </td>
              <td>{{ $l->course_code }} {{ $l->course_name }}</td>
              <td class="num">{{ rtrim(rtrim((string) $l->credits_requested, '0'), '.') }}</td>
              <td>
                <span class="tag {{ $isOk ? 'ok' : 'err' }}">{{ $l->decision ? ucfirst($l->decision) : 'Pending' }}</span>
              </td>
              <td class="num">{{ $l->credits_approved !== null ? rtrim(rtrim((string) $l->credits_approved, '0'), '.') : '0' }}</td>
              <td class="small">{{ $l->conditions ?: ($isOk ? "Subject to admission to {$a->programme}; approval does not imply admission" : 'None') }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="font-weight:600;background:var(--surface)">
            <td colspan="3">{{ __("Total") }}</td>
            <td class="num">{{ rtrim(rtrim((string) $lines->sum('credits_requested'), '0'), '.') }}</td>
            <td></td>
            <td class="num">{{ rtrim(rtrim((string) $lines->sum('credits_approved'), '0'), '.') }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div style="font-size:10.5pt;margin-bottom:16px">
    {{ __("Overall status:") }} <b>{{ $status[$a->status] ?? ucfirst($a->status) }}</b>. {{ __("Credits approved:") }} <b>{{ rtrim(rtrim((string) $lines->sum('credits_approved'), '0'), '.') }} of {{ rtrim(rtrim((string) $lines->sum('credits_requested'), '0'), '.') }}</b>.
  </div>

  <div class="note" style="margin-bottom:16px;font-size:9.5pt">
    <div>
      <b>{{ __("If a line is not approved, nothing else changes.") }}</b> {{ __("Your Academic Credit Record, your credentials and your ABC deposit stay exactly as they are, and the credit remains available for another application. You may apply again with more evidence.") }}
    </div>
  </div>

  <div class="panel" style="margin-bottom:24px">
    <div class="hd"><h2>{{ __("Questions or appeal") }}</h2></div>
    <div class="bd small" style="line-height:1.5">
      {{ __("Write to the Deputy Dean (Academic) or the CODL helpdesk, codl@utem.edu.my, 06-270 2000, stating the line, your reasons and any new evidence. Admission is recorded separately by Pejabat Pendaftar.") }}
    </div>
  </div>

  <div style="display:flex;gap:40px;margin-top:auto;padding-top:20px;border-top:1px solid var(--border)">
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">Deputy Dean (Academic)</div>
      <div class="bm small">Timbalan Dekan (Akademik)</div>
    </div>
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ now()->format('d/m/Y') }}</div>
      <div class="bm small">Tarikh</div>
    </div>
  </div>
@endif

@endsection
