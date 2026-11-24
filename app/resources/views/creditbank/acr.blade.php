@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Academic Credit Record'))
@section('doc_title', 'Academic Credit Record')
@section('doc_bm', 'Rekod Kredit Akademik')
@section('doc_ref', $a->reference)
@section('doc_file', 'acr-'.$a->reference.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
  $isMs = app()->getLocale() === 'ms';
  $fmtNum = fn ($v) => mcms_num($v);
  $mcName = $isMs
    ? ($p['name']['ms'] ?? $p['name']['en'] ?? $a->name_en)
    : ($p['name']['en'] ?? $a->name_en);
  $catCode = strtoupper((string) ($a->category ?? $p['category'] ?? ''));
  $catLabel = match ($catCode) {
    'UMC' => __('Unbundled Micro-Credential (UMC)'),
    'SAMC' => __('Quality-Verified Standalone Micro-Credential (SAMC)'),
    default => $catCode ?: __('Not yet recorded'),
  };
  $sltHours = $a->slt_hours ?? ($p['slt_hours'] ?? null);
  $qvRef = $a->qv_reference ?? ($p['verification']['qv_reference'] ?? null);
  $qvUntil = $a->qv_valid_until ?? ($p['verification']['qv_valid_until'] ?? null);
  $parentCourse = $p['verification']['parent_course'] ?? null;
  $provider = $p['provider'] ?? config('mcms.issuer');
  $isDeposited = $a->status === 'deposited';
  $consentGiven = $latestGivenConsent !== null || in_array($a->status, ['consent_given', 'deposited'], true);
  $consentDeclined = $a->status === 'consent_declined';
  $consentText = $consent[$isMs ? 'ms' : 'en'] ?? $consent['en'] ?? '';
  $depositStatusLabels = [
    'successful' => __('Successful'),
    'confirmed' => __('Successful'),
    'in_process' => __('In process'),
    'correction' => __('Correction needed'),
  ];

  // Unified 8-stage credit lifecycle calculation
  $lifeStage = match (true) {
    $a->status === 'prepared' => 4,
    in_array($a->status, ['verified', 'consent_declined'], true) => 5,
    in_array($a->status, ['consent_given', 'correction'], true) => 6,
    $isDeposited && in_array($a->credit_status, ['provisional', 'redeemed', 'expired'], true) => 8,
    $isDeposited => 7,
    default => 4,
  };
  $lifeBlocked = in_array($a->status, ['consent_declined', 'correction'], true);
  $lifeBlockedLbl = $a->status === 'consent_declined' ? __('Consent declined or postponed') : ($a->status === 'correction' ? __('Correction required') : null);

  // Orientation strip answers
  [$whereTxt, $nextTxt, $whoTxt, $roleCode] = match (true) {
    $a->status === 'prepared' => [
      __('Stage 04: ACR prepared from verified academic records, awaiting confirmation'),
      __('Registry / ABC Depositor verifies eligibility checks and confirms the ACR.'),
      __('Registry / ABC Depositor'),
      'P6',
    ],
    $a->status === 'verified' => [
      __('Stage 05: ACR confirmed; waiting for participant ABC deposit consent'),
      $mine ? __('Choose whether to consent, postpone, or decline depositing this ACR into ABC below.') : __('Participant decides whether to give consent to deposit this ACR into ABC.'),
      $mine ? __('You (Participant)') : $a->user_name,
      'P8',
    ],
    $a->status === 'consent_declined' => [
      __('Stage 05: Deposit consent declined or postponed (ACR retained permanently in MCMS)'),
      $mine ? __('You may give consent at any time if you decide to deposit this credit into ABC.') : __('Waiting for participant to revisit deposit consent if desired.'),
      $mine ? __('You (Participant)') : $a->user_name,
      'P8',
    ],
    in_array($a->status, ['consent_given', 'correction'], true) => [
      __('Stage 06: Learner consent recorded; queued for Academic Bank of Credit (ABC) deposit'),
      $mine ? __('Registry / ABC Depositor exports the batch and records the official ABC deposit reference.') : __('Export batch CSV and record the official ABC deposit reference, date, and status below.'),
      __('Registry / ABC Depositor'),
      'P7',
    ],
    $isDeposited && $a->credit_status === 'available' => [
      __('Stage 07: Credit deposited in ABC and Available for transfer until :date', ['date' => $a->credit_expiry ? \Carbon\Carbon::parse($a->credit_expiry)->format('d/m/Y') : __('Not yet recorded')]),
      $mine ? __('Apply for credit transfer into a UTeM programme when ready. Deposit is storage only, not transfer.') : __('Participant may apply for credit transfer; CODL evaluates equivalence and Senate decides approval.'),
      $mine ? __('You (Participant) / CODL & Senate') : __('CODL & Senate'),
      'CODL/Senate',
    ],
    $isDeposited && $a->credit_status === 'provisional' => [
      __('Stage 08: Credit is Provisionally Redeemed toward an approved transfer application'),
      __('Credit is locked under the Single Redemption Principle until programme completion or release is recorded.'),
      __('CODL & Senate / Registry'),
      'CODL/P6',
    ],
    $isDeposited && $a->credit_status === 'redeemed' => [
      __('Stage 08: Credit is permanently Redeemed toward a qualification (Single Redemption complete)'),
      __('No further transfer or reuse is permitted. This ACR remains archived permanently.'),
      __('Records Custody (Perpetual Retention)'),
      'P6',
    ],
    default => [
      __('Stage 08: ABC credit status — :st', ['st' => $a->credit_status ? __($creditStatus[$a->credit_status] ?? ucfirst($a->credit_status)) : __($acrStatus[$a->status] ?? ucfirst($a->status))]),
      __('Review credit validity or re-evaluation requirements with CODL and Registry.'),
      __('CODL & Senate / Registry'),
      'CODL/P6',
    ],
  };

  // F11 Three-Party Verification rows definition (Guide Appendix F11)
  $f11Slots = [
    'coordinator' => ['en' => 'Course Coordinator / Lecturer', 'ms' => 'Penyelaras Kursus / Pensyarah', 'code' => 'P1'],
    'academic' => ['en' => 'Faculty Academic Authority (Deputy Dean)', 'ms' => 'Pihak Berkuasa Akademik Fakulti (Timbalan Dekan)', 'code' => 'P2/P3'],
    'records_abc' => ['en' => 'Records Unit / ABC Depositor', 'ms' => 'Unit Rekod / Pendeposit ABC', 'code' => 'P6/P7'],
  ];

  // F9 Single Redemption stage lookup
  $redempByEvent = $redemptions->groupBy('event');
  $provEv = $redempByEvent->get('provisional')?->last();
  $finalEv = ($redempByEvent->get('final') ?? $redempByEvent->get('redemption'))?->last();
  $relEv = $redempByEvent->get('released')?->last();
@endphp

@if(!$isPrint)
  <x-breadcrumb :crumbs="[
    ($mine ? __('Credit ledger and ABC') : __('ACR and ABC deposits')) => ($mine ? route('credit') : route('deposits')),
    $a->reference => null,
  ]" />

  <div class="title">
    <div>
      <h1>{{ $mcName }}: {{ __('Academic Credit Record') }}</h1>
      <div class="sub">
        <code>{{ $a->reference }}</code> &middot; {{ $catLabel }} &middot; {{ $fmtNum($a->credits) }} {{ __('credits') }}
        @if($sltHours) &middot; {{ $sltHours }} {{ __('SLT hours') }}@endif
      </div>
    </div>
    <div class="act" style="align-items:center;display:flex;gap:8px">
      <span class="tag {{ $a->credit_status === 'available' ? 'ok' : ($a->credit_status === 'provisional' ? 'warn' : ($isDeposited ? 'ok' : 'info')) }}">
        {{ $a->credit_status ? __($creditStatus[$a->credit_status] ?? ucfirst($a->credit_status)) : __($acrStatus[$a->status] ?? ucfirst($a->status)) }}
      </span>
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter" target="_blank">{{ __('Print ACR (A4)') }}</a>
    </div>
  </div>

  <x-credit-lifecycle :stage="$lifeStage" :blocked="$lifeBlocked" :blockedLabel="$lifeBlockedLbl" :note="$a->reference" />

  <div class="cols">
    <div>
      {{-- Action Panel 1: Participant Consent (Stage 05) --}}
      @if($mine && in_array($a->status, ['verified', 'consent_declined'], true))
        <form method="post" action="{{ route('credit.consent', $a->id) }}" class="panel" id="consent" style="border-top:3px solid var(--primary)">
          @csrf
          <div class="hd">
            <h2>{{ __('Learner Consent for ABC Deposit') }}</h2>
            <span class="r"><span class="tag warn">{{ __('Action required') }}</span></span>
          </div>
          <div class="bd">
            <div class="note" style="margin-bottom:14px">
              <div>
                <p style="margin:0">{{ $consentText }}</p>
              </div>
            </div>
            <p class="small muted" style="margin-bottom:14px">{{ __('Depositing into the Academic Bank of Credit (ABC) stores your credit for up to 10 years. It is storage only and not a credit transfer.') }}</p>
            <fieldset style="border:0;padding:0;margin:0 0 16px">
              <div style="display:flex;flex-direction:column;gap:10px">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;color:var(--ink)">
                  <input type="radio" name="decision" value="given" required>
                  <span><b>{{ __('I consent to the deposit') }}</b> &middot; <span class="muted small">{{ __('The ACR enters the Registry / ABC Depositor queue') }}</span></span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;color:var(--ink)">
                  <input type="radio" name="decision" value="postponed">
                  <span><b>{{ __('Postpone') }}</b> &middot; <span class="muted small">{{ __('Nothing changes; decide at any time') }}</span></span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;color:var(--ink)">
                  <input type="radio" name="decision" value="declined">
                  <span><b>{{ __('Decline') }}</b> &middot; <span class="muted small">{{ __('The ACR is kept permanently in MCMS and not deposited') }}</span></span>
                </label>
              </div>
            </fieldset>
            <button type="submit" class="btn">{{ __('Save my choice') }}</button>
          </div>
        </form>
      @elseif($mine && $isDeposited)
        <div class="note ok" style="margin-bottom:16px">
          <div><b>{{ __('Consent recorded and credit deposited.') }}</b> {{ __('This Academic Credit Record is deposited in the Academic Bank of Credit. Consent can no longer be changed here. Contact CODL / Registry for corrections.') }}</div>
        </div>
      @endif

      {{-- Action Panel 2: P6/P7 ACR Confirmation (Stage 04) --}}
      @if(! $mine && $a->status === 'prepared')
        <form method="post" action="{{ route('deposits.confirm', $a->id) }}" class="panel" style="border-top:3px solid var(--primary)">
          @csrf
          <div class="hd">
            <h2>{{ __('Confirm Academic Credit Record') }}</h2>
            <span class="r"><span class="tag warn">{{ __('Action required') }}</span></span>
          </div>
          <div class="bd">
            <p class="small" style="margin-top:0">{{ __('Review only. Verify the eligibility checks below and confirm that the generated record is accurate; generated academic values cannot be edited here.') }}</p>
            <div style="margin-bottom:14px">
              <label for="acr_notes">{{ __('ACR Status Notes (optional)') }}</label>
              <input id="acr_notes" name="notes" type="text" value="{{ old('notes') }}" placeholder="{{ __('e.g. All eligibility checks verified against source records.') }}">
            </div>
            <button type="submit" class="btn">{{ __('Confirm Academic Credit Record') }}</button>
          </div>
        </form>
      @endif

      {{-- Action Panel 3: P7 ABC Deposit Recording (Stage 06) --}}
      @if(! $mine && in_array($a->status, ['consent_given', 'correction'], true))
        <form method="post" action="{{ route('deposits.record', $a->id) }}" class="panel" id="deposit" style="border-top:3px solid var(--primary);margin-bottom:20px">
          @csrf
          <div class="hd">
            <h2>{{ __('Record the ABC Deposit') }}</h2>
            <span class="r"><span class="tag warn">{{ __('Action required') }}</span></span>
          </div>
          <div class="bd">
            <div class="note info" style="margin-top:0;margin-bottom:16px">
              <div>
                <strong>{{ __('Deposit Storage Notice:') }}</strong> {{ __('A deposit is storage in the Academic Bank of Credit only and is not a credit transfer. On success, this credit will be Available for :years years from the deposit date.', ['years' => $years ?? 10]) }}
              </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:16px">
              <div>
                <label for="abc_reference">{{ __('ABC Reference') }} *</label>
                <input id="abc_reference" name="abc_reference" type="text" required value="{{ old('abc_reference', $a->abc_reference ?: 'ABC-'.now()->year.'-'.str_pad((string)$a->id, 4, '0', STR_PAD_LEFT)) }}">
              </div>
              <div>
                <label for="deposit_date">{{ __('Deposit Date') }} *</label>
                <input id="deposit_date" name="deposit_date" type="date" required value="{{ old('deposit_date', $a->deposit_date ?: today()->toDateString()) }}">
              </div>
              <div>
                <label for="credits">{{ __('Credits Deposited') }} *</label>
                <input id="credits" name="credits" type="number" step="0.5" min="0.5" max="{{ $a->credits }}" value="{{ old('credits', $a->deposited_credits ?? $a->credits) }}" required>
              </div>
              <div>
                <label for="deposit_status">{{ __('Deposit Status') }} *</label>
                <select id="deposit_status" name="deposit_status">
                  <option value="successful">{{ __('Successful') }}</option>
                  <option value="in_process">{{ __('In process') }}</option>
                  <option value="correction">{{ __('Correction needed') }}</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn primary">{{ __('Record ABC Deposit') }}</button>
          </div>
        </form>
      @endif

      {{-- Carbon Tabbed Inspection Workspace --}}
      <div data-tabs>
        <nav class="tabs" aria-label="{{ __('Record sections') }}">
          <button type="button" data-tab="f1-f3" class="on">{{ __('Identity & ACR') }}</button>
          <button type="button" data-tab="f2">{{ __('Checks') }} ({{ $checks->count() }})</button>
          <button type="button" data-tab="f4-f9">{{ __('Deposit & Redemption') }}</button>
          <button type="button" data-tab="f10-f11">{{ __('Corrections & Sign-off') }}</button>
        </nav>

        {{-- TAB 1: General Information & ACR Summary --}}
        <div data-pane="f1-f3">
          <div class="panel" id="acr">
            <div class="hd">
              <h2>{{ __('General Information') }}</h2>
              <span class="r"><span class="tag info">{{ $catCode }}</span></span>
            </div>
            <div class="table-wrap">
              <table>
                <tbody>
                  <tr><th scope="row" style="width:240px">{{ __('Participant Name') }}</th><td><b>{{ $p['participant']['name'] ?? $a->user_name }}</b></td></tr>
                  <tr><th scope="row">{{ __('Matric / Participant ID') }}</th><td><code>{{ $p['participant']['matric'] ?? $a->institution_id }}</code></td></tr>
                  <tr><th scope="row">{{ __('Provider') }}</th><td>{{ $provider }}</td></tr>
                  <tr><th scope="row">{{ __('Micro-Credential') }}</th><td><b>{{ $mcName }}</b></td></tr>
                  <tr><th scope="row">{{ __('Category') }}</th><td><b>{{ $catLabel }}</b></td></tr>
                  <tr><th scope="row">{{ __('Learning Type') }}</th><td><b>{{ ($a->learning_type ?? ($a->category === 'umc' ? 'formal' : 'non_formal')) === 'formal' ? __('Formal Learning (UMC)') : __('Non-Formal Learning (SAMC)') }}</b></td></tr>
                  <tr><th scope="row">{{ __('Credit Value') }}</th><td><b>{{ $fmtNum($a->credits) }} {{ __('credits') }}</b></td></tr>
                  <tr><th scope="row">{{ __('Student Learning Time (SLT)') }}</th><td>@if($sltHours !== null)<b>{{ $sltHours }} {{ __('hours') }}</b>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td></tr>
                  <tr><th scope="row">{{ __('MQF Level') }}</th><td>{{ $a->mqf_level ? __('Level :lvl', ['lvl' => $a->mqf_level]) : __('Not yet recorded') }}</td></tr>
                  <tr><th scope="row">{{ __('Completion Date') }}</th><td>{{ $a->completion_date ? \Carbon\Carbon::parse($a->completion_date)->format('d/m/Y') : __('Not yet recorded') }}</td></tr>
                  <tr><th scope="row">{{ __('Assessment Basis') }}</th><td>{{ $a->assessment_basis ?: __('Not yet recorded') }}</td></tr>
                  <tr>
                    <th scope="row">{{ __('Source Verification') }}</th>
                    <td>
                      {{ __('Credential') }}: <code>{{ $p['verification']['credential'] ?? __('Not yet recorded') }}</code>
                      @if($parentCourse) &middot; {{ __('Parent course') }}: <b>{{ $parentCourse }}</b>@endif
                      @if($qvRef) &middot; {{ __('Quality Verification (QV)') }}: <code>{{ $qvRef }}</code> ({{ __('Valid until') }}: {{ $qvUntil ? \Carbon\Carbon::parse($qvUntil)->format('d/m/Y') : __('Not yet recorded') }})@endif
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel">
            <div class="hd">
              <h2>{{ __('Academic Credit Record (ACR) Summary') }}</h2>
              <span class="r" id="acr-status"><span class="tag {{ $isDeposited ? 'ok' : 'info' }}">{{ __($acrStatus[$a->status] ?? ucfirst($a->status)) }}</span></span>
            </div>
            <div class="table-wrap">
              <table>
                <tbody>
                  <tr><th scope="row" style="width:240px">{{ __('ACR Reference') }}</th><td><code>{{ $a->reference }}</code></td></tr>
                  <tr><th scope="row">{{ __('ACR Created Date') }}</th><td>{{ $a->created_at ? \Carbon\Carbon::parse($a->created_at)->format('d/m/Y H:i') : __('Not yet recorded') }}</td></tr>
                  <tr><th scope="row">{{ __('Confirmed By') }}</th><td>@if($a->confirmed_by_name)<b>{{ $a->confirmed_by_name }}</b>@if($a->confirmed_by_position)<div class="s muted">{{ $a->confirmed_by_position }}</div>@endif @else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td></tr>
                  <tr><th scope="row">{{ __('Confirmation Date') }}</th><td>{{ $a->confirmed_at ? \Carbon\Carbon::parse($a->confirmed_at)->format('d/m/Y H:i') : __('Not yet recorded') }}</td></tr>
                  <tr><th scope="row">{{ __('ACR Status & Remarks') }}</th><td>{{ __($acrStatus[$a->status] ?? ucfirst($a->status)) }}@if($a->notes)<div class="s muted">{{ $a->notes }}</div>@endif</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel">
            @php
              $outcomesList = ($isMs && !empty($p['outcomes_ms']))
                ? $p['outcomes_ms']
                : ($isMs
                    ? (\Illuminate\Support\Facades\DB::table('learning_outcomes')->where('mc_version_id', $a->mc_version_id)->orderBy('sort_order')->pluck('text_ms')->filter()->values()->all() ?: ($p['outcomes'] ?? []))
                    : ($p['outcomes'] ?? []));
            @endphp
            <div class="hd"><h2>{{ __('Learning Outcomes') }}</h2><span class="r">{{ count($outcomesList) }} {{ __('outcomes') }}</span></div>
            <div class="bd">
              @if(!empty($outcomesList))
                <ol style="margin:0;padding-left:20px">
                  @foreach($outcomesList as $lo)<li style="margin-bottom:6px">{{ $lo }}</li>@endforeach
                </ol>
              @else
                <span class="not-recorded">{{ __('Not yet recorded') }}</span>
              @endif
            </div>
          </div>
        </div>

        {{-- TAB 2: Eligibility Verification --}}
        <div data-pane="f2">
          <div class="panel">
            <div class="hd">
              <h2>{{ __('Eligibility Verification Before ACR Preparation') }}</h2>
              <span class="r"><span class="tag ok">{{ $checks->count() }} {{ __('checks') }}</span></span>
            </div>
            <div class="table-wrap">
              <table id="checks">
                <thead>
                  <tr>
                    <th style="width:44px">{{ __('No.') }}</th>
                    <th>{{ __('Eligibility Criterion') }}</th>
                    <th style="width:160px">{{ __('Result') }}</th>
                    <th>{{ __('Evidence Reference') }}</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($checks as $idx => $c)
                  @php
                    $res = $c->result ?? (! $c->passed ? 'no' : (str_starts_with((string) $c->detail, 'Not applicable') ? 'na' : 'yes'));
                    $resTag = $res === 'yes' ? 'ok' : ($res === 'na' ? '' : 'err');
                    $resLbl = match ($res) {
                      'yes' => __('Yes (Passed)'),
                      'na' => __('Not Applicable'),
                      default => __('No (Failed)'),
                    };
                  @endphp
                  <tr>
                    <td class="tabular">{{ $idx + 1 }}</td>
                    <td>{{ __(\App\Modules\CreditBank\Acr::CHECKS[$c->check_key] ?? $c->check_key) }}</td>
                    <td><span class="tag {{ $resTag }}">{{ $resLbl }}</span></td>
                    <td>{{ $isMs ? __($c->evidence_ref ?: $c->detail) : ($c->evidence_ref ?: $c->detail) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="not-recorded" style="text-align:center;padding:20px">{{ __('Not yet recorded') }}</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- TAB 3: Consent & ABC Deposit + Single Redemption --}}
        <div data-pane="f4-f9">
          <div class="panel">
            <div class="hd">
              <h2>{{ __('Learner Consent & ABC Deposit Record') }}</h2>
              <span class="r"><span class="tag {{ $isDeposited ? 'ok' : 'info' }}">{{ $a->deposit_status ? ($depositStatusLabels[$a->deposit_status] ?? __(ucfirst($a->deposit_status))) : __('Not yet deposited') }}</span></span>
            </div>
            <div class="table-wrap">
              <table>
                <tbody>
                  <tr>
                    <th scope="row" style="width:240px">{{ __('Learner Consent Obtained') }}</th>
                    <td>
                      @if($latestGivenConsent)
                        <span class="tag ok">{{ __('Consent Given') }}</span>
                        <span class="s muted" style="display:inline;margin-left:8px">{{ \Carbon\Carbon::parse($latestGivenConsent->decided_at)->format('d/m/Y H:i') }} ({{ $latestGivenConsent->channel }})</span>
                      @elseif($consentDeclined)
                        <span class="tag warn">{{ __('Declined or Postponed') }}</span>
                      @else
                        <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                      @endif
                    </td>
                  </tr>
                  <tr><th scope="row">{{ __('ABC Reference') }}</th><td>@if($a->abc_reference)<code>{{ $a->abc_reference }}</code>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td></tr>
                  <tr><th scope="row">{{ __('Deposit Date') }}</th><td>{{ $a->deposit_date ? \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') : __('Not yet recorded') }}</td></tr>
                  <tr><th scope="row">{{ __('Credits Deposited') }}</th><td>@if($a->deposited_credits !== null)<b>{{ $fmtNum($a->deposited_credits) }} {{ __('credits') }}</b>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td></tr>
                  <tr><th scope="row">{{ __('Credit Validity Expiry') }}</th><td>@if($a->credit_expiry)<b>{{ \Carbon\Carbon::parse($a->credit_expiry)->format('d/m/Y') }}</b> <span class="small muted">({{ __('10 years from deposit date') }})</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td></tr>
                  <tr><th scope="row">{{ __('Deposit Status') }}</th><td>{{ $a->deposit_status ? ($depositStatusLabels[$a->deposit_status] ?? __(ucfirst($a->deposit_status))) : __('Not yet recorded') }}</td></tr>
                  <tr><th scope="row">{{ __('Recorded By (ABC Depositor)') }}</th><td>@if($a->deposited_by_name)<b>{{ $a->deposited_by_name }}</b>@if($a->deposited_by_position)<div class="s muted">{{ $a->deposited_by_position }}</div>@endif @else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td></tr>
                  <tr><th scope="row">{{ __('Credit Ledger Status') }}</th><td>{{ $a->credit_status ? __($creditStatus[$a->credit_status] ?? ucfirst($a->credit_status)) : __('Not yet in ABC') }}</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel">
            <div class="hd">
              <h2>{{ __('Single Redemption Principle Tracking') }}</h2>
              <span class="r"><span class="tag {{ $a->credit_status === 'redeemed' ? 'ok' : ($a->credit_status === 'provisional' ? 'warn' : 'info') }}">{{ $a->credit_status ? __($creditStatus[$a->credit_status] ?? ucfirst($a->credit_status)) : __('Pending deposit') }}</span></span>
            </div>
            <div class="table-wrap">
              <table id="redemption-table">
                <thead>
                  <tr>
                    <th>{{ __('Stage') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Date & ABC Update') }}</th>
                    <th>{{ __('Evidence & Actor') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><b>01. {{ __('Available') }}</b></td>
                    <td>@if($a->deposit_date)<span class="tag {{ $a->credit_status === 'available' ? 'ok' : '' }}">{{ $a->credit_status === 'available' ? __('Current') : __('Completed') }}</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
                    <td>{{ $a->deposit_date ? \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $a->abc_reference ? __('ABC Ref: :ref', ['ref' => $a->abc_reference]) : __('Awaiting ABC deposit') }}</td>
                  </tr>
                  <tr>
                    <td><b>02. {{ __('Provisionally Redeemed') }}</b></td>
                    <td>@if($provEv)<span class="tag {{ $a->credit_status === 'provisional' ? 'warn' : 'ok' }}">{{ $a->credit_status === 'provisional' ? __('Current') : __('Recorded') }}</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
                    <td>{{ ($provEv ? \Carbon\Carbon::parse($provEv->occurred_at) : ($provLedger ? \Carbon\Carbon::parse($provLedger->occurred_at) : null))?->format('d/m/Y') ?: '—' }}</td>
                    <td>@if($provEv){{ $provEv->transfer_ref ? $provEv->transfer_ref.' ('.$provEv->programme_code.')' : ($provEv->evidence_ref ?: __('Transfer approved')) }}@if($provEv->recorded_by_name) &middot; {{ $provEv->recorded_by_name }}@endif @elseif($provLedger) {{ __('Transfer approved') }} @else — @endif</td>
                  </tr>
                  <tr>
                    <td><b>03. {{ __('Redeemed') }}</b></td>
                    <td>@if($finalEv)<span class="tag ok">{{ __('Redeemed (Final)') }}</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
                    <td>@if($finalEv){{ \Carbon\Carbon::parse($finalEv->occurred_at)->format('d/m/Y') }}@if($finalEv->abc_updated_on)<div class="s muted">ABC: {{ \Carbon\Carbon::parse($finalEv->abc_updated_on)->format('d/m/Y') }}</div>@endif @else — @endif</td>
                    <td>@if($finalEv){{ $finalEv->evidence_ref }}@if($finalEv->abc_update_ref) (<code>{{ $finalEv->abc_update_ref }}</code>)@endif @if($finalEv->recorded_by_name) &middot; {{ $finalEv->recorded_by_name }}@endif @else — @endif</td>
                  </tr>
                  <tr>
                    <td><b>04. {{ __('Programme Not Completed / Released') }}</b></td>
                    <td>@if($relEv)<span class="tag warn">{{ __('Released') }}</span>@else<span class="not-recorded">{{ __('Not applicable') }}</span>@endif</td>
                    <td>{{ $relEv ? \Carbon\Carbon::parse($relEv->occurred_at)->format('d/m/Y') : '—' }}</td>
                    <td>@if($relEv){{ $relEv->evidence_ref }}@if($relEv->recorded_by_name) &middot; {{ $relEv->recorded_by_name }}@endif @else — @endif</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          @if($consents->count())
            <div class="panel">
              <div class="hd"><h2>{{ __('Consent History') }}</h2><span class="r">{{ $consents->count() }} {{ __('record(s)') }}</span></div>
              <div class="table-wrap">
                <table id="consents">
                  <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Decision') }}</th><th>{{ __('Channel') }}</th><th>{{ __('Wording version') }}</th></tr></thead>
                  <tbody>
                    @foreach($consents as $c)
                      <tr>
                        <td>{{ \Carbon\Carbon::parse($c->decided_at)->format('d/m/Y H:i') }}</td>
                        <td><span class="tag {{ $c->decision === 'given' ? 'ok' : 'warn' }}">{{ ['given' => __('Consent given'), 'declined' => __('Declined'), 'postponed' => __('Postponed')][$c->decision] }}</span></td>
                        <td>{{ $c->channel }}</td>
                        <td>{{ collect(json_decode($c->template_versions, true))->map(fn ($v, $l) => "$l " . (str_starts_with((string) $v, 'v') ? $v : "v$v"))->implode(', ') }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endif

          @if($ledger->count())
            <div class="panel">
              <div class="hd"><h2>{{ __('Credit Ledger History') }}</h2><span class="r">{{ $ledger->count() }} {{ __('entries') }}</span></div>
              <div class="table-wrap">
                <table id="credit-history">
                  <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Event') }}</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th></tr></thead>
                  <tbody>
                    @foreach($ledger as $l)
                      <tr>
                        <td>{{ \Carbon\Carbon::parse($l->occurred_at)->format('d/m/Y') }}</td>
                        <td>{{ __(ucfirst($l->event)) }}</td>
                        <td>{{ $l->from_status ? __($creditStatus[$l->from_status] ?? ucfirst($l->from_status)) : '—' }}</td>
                        <td><b>{{ __($creditStatus[$l->to_status] ?? ucfirst($l->to_status)) }}</b></td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endif
        </div>

        {{-- TAB 4: Corrections & Three-Party Verification --}}
        <div data-pane="f10-f11">
          <div class="panel">
            <div class="hd">
              <h2>{{ __('Three-Party Verification') }}</h2>
              <span class="r"><span class="tag info">{{ $verifications->count() }} / 3 {{ __('recorded') }}</span></span>
            </div>
            <div class="table-wrap">
              <table id="f11-verifications">
                <thead>
                  <tr>
                    <th style="width:24%">{{ __('Role') }}</th>
                    <th style="width:26%">{{ __('Name & Position') }}</th>
                    <th style="width:14%">{{ __('Verification') }}</th>
                    <th style="width:14%">{{ __('Date') }}</th>
                    <th>{{ __('Remarks') }}</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($f11Slots as $slot => $meta)
                  @php $v = $verifications->get($slot); @endphp
                  <tr>
                    <td>
                      <div style="font-weight:600">{{ $isMs ? $meta['ms'] : $meta['en'] }}</div>
                    </td>
                    <td>
                      @if($v)
                        <b>{{ $v->actor_name_snapshot }}</b>
                        @if($v->actor_position_snapshot)<div class="s muted">{{ $isMs && $v->user_id ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $v->user_id)->value('position_ms') ?: $v->actor_position_snapshot) : $v->actor_position_snapshot }}</div>@endif
                      @endif
                      @if($slot === 'records_abc' && ($vDep = $verifications->get('abc_deposit')))
                        <div style="margin-top:6px"><b>{{ $vDep->actor_name_snapshot }}</b>@if($vDep->actor_position_snapshot)<div class="s muted">{{ $isMs && $vDep->user_id ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $vDep->user_id)->value('position_ms') ?: $vDep->actor_position_snapshot) : $vDep->actor_position_snapshot }}</div>@endif</div>
                      @endif
                      @if(!$v && !($slot === 'records_abc' && $verifications->get('abc_deposit')))
                        <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                      @endif
                    </td>
                    <td>
                      @if(($v && $v->status === 'confirmed') || ($slot === 'records_abc' && $verifications->get('abc_deposit')))
                        <span class="tag ok">{{ __('Confirmed') }}</span>
                      @else
                        <span class="tag">{{ __('Pending') }}</span>
                      @endif
                    </td>
                    <td class="tabular">
                      @if($v && $v->verified_at)
                        {{ \Carbon\Carbon::parse($v->verified_at)->format('d/m/Y') }}
                      @elseif($slot === 'records_abc' && ($vDep = $verifications->get('abc_deposit')) && $vDep->verified_at)
                        {{ \Carbon\Carbon::parse($vDep->verified_at)->format('d/m/Y') }}
                      @else
                        —
                      @endif
                    </td>
                    <td>
                      {{ $v?->remarks ?: '—' }}
                      @if($slot === 'records_abc' && ($vDep = $verifications->get('abc_deposit')))
                        <div class="s muted" style="margin-top:4px">{{ $vDep->remarks }}</div>
                      @endif
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel">
            <div class="hd">
              <h2>{{ __('Record Correction & Update Log') }}</h2>
              <span class="r"><span class="tag info">{{ $corrections->count() }} {{ __('entry/entries') }}</span></span>
            </div>
            <div class="table-wrap">
              <table id="f10-corrections">
                <thead>
                  <tr>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th>{{ __('Record Type') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th>{{ __('Action Taken') }}</th>
                    <th>{{ __('Approved By') }}</th>
                    <th>{{ __('Notified') }}</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($corrections as $cor)
                  <tr>
                    <td class="tabular">{{ $cor->corrected_on ? \Carbon\Carbon::parse($cor->corrected_on)->format('d/m/Y') : '—' }}</td>
                    <td><b>{{ $cor->record_type }}</b><div class="s muted"><code>{{ $cor->record_ref }}</code></div></td>
                    <td>{{ $cor->reason }}</td>
                    <td>{{ $cor->action }}</td>
                    <td>
                      @if($cor->approver_name)
                        <b>{{ $cor->approver_name }}</b>
                        @if($cor->approver_position)<div class="s muted">{{ $cor->approver_position }}</div>@endif
                      @else
                        <span class="not-recorded">{{ __('Not yet recorded') }}</span>
                      @endif
                    </td>
                    <td><span class="tag {{ $cor->participant_notified ? 'ok' : '' }}">{{ $cor->participant_notified ? __('Yes') : __('No') }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="not-recorded" style="text-align:center;padding:20px">{{ __('No corrections recorded. Original ACR records are preserved permanently.') }}</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>

            @if(! $mine)
              <div class="bd" style="border-top:1px solid var(--hairline)">
                <h3 style="margin-top:0">{{ __('Log a Controlled Correction') }}</h3>
                <p class="small muted">{{ __('Corrections never overwrite the original ACR or ABC deposit. The original snapshot is preserved and the participant is notified automatically.') }}</p>
                <form method="post" action="{{ route('deposits.correct', $a->id) }}" id="f10-form">
                  @csrf
                  <div style="display:grid;grid-template-columns:220px 1fr;gap:12px;margin-bottom:12px">
                    <div>
                      <label for="cor_type">{{ __('Record Type') }} *</label>
                      <select id="cor_type" name="record_type" required>
                        <option value="Acr">{{ __('Academic Credit Record (ACR)') }}</option>
                        <option value="AbcDeposit">{{ __('ABC Deposit Record') }}</option>
                        <option value="Credential">{{ __('Linked Credential') }}</option>
                      </select>
                    </div>
                    <div>
                      <label for="cor_reason">{{ __('Reason for Correction') }} *</label>
                      <input id="cor_reason" name="reason" type="text" required value="{{ old('reason') }}" placeholder="{{ __('State discrepancy or factual correction needed') }}">
                    </div>
                  </div>
                  <div style="margin-bottom:12px">
                    <label for="cor_action">{{ __('Corrective Action Taken') }} *</label>
                    <textarea id="cor_action" name="action" required rows="2" placeholder="{{ __('Describe the verified update made while retaining the original record') }}">{{ old('action') }}</textarea>
                  </div>
                  <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;color:var(--ink);margin-bottom:14px">
                    <input type="checkbox" name="source_checked" value="1" required>
                    <span>{{ __('I confirm the valid source record (assessment / QV / Senate / ABC receipt) has been checked.') }}</span>
                  </label>
                  <div>
                    <button type="submit" class="btn ter sm">{{ __('Record Correction') }}</button>
                  </div>
                </form>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Right Sidebar: Policy & Linked Transfers --}}
    <div>
      <div class="panel" style="margin-bottom:16px">
        <div class="hd">
          <h2>{{ __('Academic Credit Policy') }}</h2>
          <span class="r"><span class="tag info">{{ __('Policy') }}</span></span>
        </div>
        <div class="bd">
          <p class="small muted" style="margin-top:0;margin-bottom:12px">
            {{ __('An Academic Credit Record (ACR) is a separate record from your certificate and Digital Credential. It is retained permanently and cannot be edited directly; any correction is logged in the official correction audit log.') }}
          </p>
          <div style="margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--hairline)">
            <strong style="display:block;font-size:12px;margin-bottom:2px">{{ __('10-Year ABC Validity') }}</strong>
            <div class="small muted">{{ __('Credits deposited in ABC remain valid for 10 years from the deposit date.') }}</div>
          </div>
          <div style="margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--hairline)">
            <strong style="display:block;font-size:12px;margin-bottom:2px">{{ __('Single Redemption Principle') }}</strong>
            <div class="small muted">{{ __('Once redeemed toward a qualification, credit cannot be reused for another qualification.') }}</div>
          </div>
          <div>
            <strong style="display:block;font-size:12px;margin-bottom:2px">{{ __('Storage Only') }}</strong>
            <div class="small muted">{{ __('ABC deposit is storage only and never grants automatic credit transfer or programme admission.') }}</div>
          </div>
          @if($mine && $a->credit_status === 'available')
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--hairline)">
              <a class="btn sm primary" href="{{ route('transfer.create') }}" style="width:100%;justify-content:center">{{ __('Apply for credit transfer') }}</a>
            </div>
          @endif
        </div>
      </div>

      @if($transfers->count())
        <div class="panel" style="margin-bottom:16px">
          <div class="hd"><h2>{{ __('Linked Transfers') }}</h2><span class="r">{{ $transfers->count() }}</span></div>
          <div class="bd" style="padding:12px 16px">
            @foreach($transfers as $tr)
              <div style="padding:8px 0;border-bottom:1px solid var(--rule)">
                <div><b><code>{{ $tr->reference }}</code></b> &middot; {{ $tr->programme_code }}</div>
                <div class="s muted">{{ __(config('transfer.status')[$tr->status] ?? ucfirst($tr->status)) }} &middot; {{ $fmtNum($tr->credits_requested) }} {{ __('credits requested') }}</div>
                @if($mine)
                  <a href="{{ route('transfer.show', $tr->id) }}" class="btn ter sm" style="margin-top:6px">{{ __('View transfer') }}</a>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div class="panel">
        <div class="hd"><h2>{{ __('Actions') }}</h2></div>
        <div class="bd" style="display:flex;flex-direction:column;gap:8px">
          <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter sm" target="_blank" style="justify-content:center">{{ __('Print Official ACR (A4)') }}</a>
          <a href="{{ $mine ? route('credit') : route('deposits') }}" class="btn ter sm" style="justify-content:center">&larr; {{ $mine ? __('Back to credit ledger') : __('Back to deposit queue') }}</a>
        </div>
      </div>
    </div>
  </div>

@else
  {{-- =========================================================================
       OFFICIAL A4 PRINTABLE ACR — APPENDIX F1, F2, F3, F4, F9, F10, F11 (Q6)
       Strict zero-fabrication: every actor, position, date from real DB rows.
       Single-language output driven strictly by system locale toggle (EN / BM).
     ========================================================================= --}}
  <div style="margin-bottom:10px;border-bottom:2px solid #0b1a3d;padding-bottom:8px">
    <h1 style="font-size:15pt;font-weight:400;margin:0 0 2px;color:#090a0f">{{ __('Academic Credit Record (ACR)') }}</h1>
    <div style="font-size:8.6pt;color:#393939">{{ __('Official Academic Credit Record issued by Universiti Teknikal Malaysia Melaka.') }}</div>
  </div>

  {{-- Top At-a-Glance Summary Strip --}}
  <div class="print-kpi-bar">
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Holder') }}</div>
      <div class="print-kpi-val">{{ $p['participant']['name'] ?? $a->user_name }}</div>
      <div class="print-kpi-sub">{{ $p['participant']['matric'] ?? $a->institution_id }}</div>
    </div>
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('ACR Reference') }}</div>
      <div class="print-kpi-val" style="font-family:var(--mono);color:#0b1a3d !important">{{ $a->reference }}</div>
      <div class="print-kpi-sub">{{ $catCode }} &middot; MQF {{ $a->mqf_level ?? '—' }}</div>
    </div>
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Credit & SLT') }}</div>
      <div class="print-kpi-val">{{ $fmtNum($a->credits) }} {{ __('credits') }}</div>
      <div class="print-kpi-sub">{{ $sltHours !== null ? $sltHours.' '.__('SLT hours') : __('SLT not yet recorded') }}</div>
    </div>
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Current Status') }}</div>
      <div class="print-kpi-val">{{ $a->credit_status ? __($creditStatus[$a->credit_status] ?? ucfirst($a->credit_status)) : __($acrStatus[$a->status] ?? ucfirst($a->status)) }}</div>
      <div class="print-kpi-sub">{{ $a->credit_expiry ? __('Valid until :date', ['date' => \Carbon\Carbon::parse($a->credit_expiry)->format('d/m/Y')]) : __('Not yet in ABC') }}</div>
    </div>
  </div>

  {{-- SECTION F1: GENERAL INFORMATION --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('General Information') }}</span>
    </div>
    <table class="print-kv-table">
      <tbody>
        <tr>
          <th style="width:23%">{{ __('Participant Name') }}</th>
          <td style="width:35%"><span class="val-strong">{{ $p['participant']['name'] ?? $a->user_name }}</span></td>
          <th style="width:18%">{{ __('Matric / ID') }}</th>
          <td style="width:24%"><code>{{ $p['participant']['matric'] ?? $a->institution_id }}</code></td>
        </tr>
        <tr>
          <th>{{ __('Provider') }}</th>
          <td colspan="3">{{ $provider }}</td>
        </tr>
        <tr>
          <th>{{ __('Micro-Credential') }}</th>
          <td colspan="3"><span class="val-strong">{{ $mcName }}</span></td>
        </tr>
        <tr>
          <th>{{ __('Category') }}</th>
          <td><span class="val-strong">{{ $catLabel }}</span></td>
          <th>{{ __('Learning Type') }}</th>
          <td><span class="val-strong">{{ ($a->learning_type ?? ($a->category === 'umc' ? 'formal' : 'non_formal')) === 'formal' ? __('Formal Learning (UMC)') : __('Non-Formal Learning (SAMC)') }}</span></td>
        </tr>
        <tr>
          <th>{{ __('Completion Date') }}</th>
          <td colspan="3">{{ $a->completion_date ? \Carbon\Carbon::parse($a->completion_date)->format('d/m/Y') : __('Not yet recorded') }}</td>
        </tr>
        <tr>
          <th>{{ __('Credit Value') }}</th>
          <td><span class="val-strong">{{ $fmtNum($a->credits) }} {{ __('credits') }}</span></td>
          <th>{{ __('SLT (Hours)') }}</th>
          <td>@if($sltHours !== null)<span class="val-strong">{{ $sltHours }} {{ __('hours') }}</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
        </tr>
        <tr>
          <th>{{ __('MQF Level') }}</th>
          <td>{{ $a->mqf_level ? __('Level :lvl', ['lvl' => $a->mqf_level]) : __('Not yet recorded') }}</td>
          <th>{{ __('Assessment Basis') }}</th>
          <td>{{ $a->assessment_basis ?: __('Not yet recorded') }}</td>
        </tr>
        <tr>
          <th>{{ __('Source Verification') }}</th>
          <td colspan="3">
            {{ __('Credential') }}: <code>{{ $p['verification']['credential'] ?? __('Not yet recorded') }}</code>
            @if($parentCourse) &nbsp;&middot;&nbsp; {{ __('Parent Course') }}: <span class="val-strong">{{ $parentCourse }}</span>@endif
            @if($qvRef) &nbsp;&middot;&nbsp; {{ __('QV Ref') }}: <code>{{ $qvRef }}</code> <span class="muted">({{ __('Valid until') }}: {{ $qvUntil ? \Carbon\Carbon::parse($qvUntil)->format('d/m/Y') : __('Not yet recorded') }})</span>@endif
          </td>
        </tr>
        <tr>
          <th>{{ __('Learning Outcomes') }}</th>
          <td colspan="3">
            @php
              $outcomesList = ($isMs && !empty($p['outcomes_ms']))
                ? $p['outcomes_ms']
                : ($isMs
                    ? (\Illuminate\Support\Facades\DB::table('learning_outcomes')->where('mc_version_id', $a->mc_version_id)->orderBy('sort_order')->pluck('text_ms')->filter()->values()->all() ?: ($p['outcomes'] ?? []))
                    : ($p['outcomes'] ?? []));
            @endphp
            @if(!empty($outcomesList))
              @foreach($outcomesList as $idx => $lo)<div style="margin-bottom:2px">{{ $idx + 1 }}. {{ $lo }}</div>@endforeach
            @else
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- SECTION F2: ELIGIBILITY VERIFICATION BEFORE ACR PREPARATION --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Eligibility Verification Before ACR') }}</span>
    </div>
    <table class="print-audit-table">
      <thead>
        <tr>
          <th style="width:5%">{{ __('No.') }}</th>
          <th style="width:43%">{{ __('Eligibility Criterion') }}</th>
          <th style="width:23%">{{ __('Result') }}</th>
          <th style="width:29%">{{ __('Evidence Reference') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($checks as $idx => $c)
        @php $res = $c->result ?? (! $c->passed ? 'no' : (str_starts_with((string) $c->detail, 'Not applicable') ? 'na' : 'yes')); @endphp
        <tr>
          <td style="font-family:var(--mono);color:#475569">{{ $idx + 1 }}</td>
          <td>{{ __(\App\Modules\CreditBank\Acr::CHECKS[$c->check_key] ?? $c->check_key) }}</td>
          <td style="white-space:nowrap">
            <span class="print-check {{ $res === 'yes' ? 'checked' : '' }}"></span>{{ __('Yes') }}
            <span class="print-check {{ $res === 'no' ? 'checked' : '' }}" style="margin-left:6px"></span>{{ __('No') }}
            <span class="print-check {{ $res === 'na' ? 'checked' : '' }}" style="margin-left:6px"></span>{{ __('N/A') }}
          </td>
          <td>{{ $isMs ? __($c->evidence_ref ?: $c->detail) : ($c->evidence_ref ?: $c->detail) }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="not-recorded">{{ __('Not yet recorded') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  {{-- SECTION F3: ACADEMIC CREDIT RECORD SUMMARY --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Academic Credit Record Summary') }}</span>
    </div>
    <table class="print-kv-table">
      <tbody>
        <tr>
          <th style="width:23%">{{ __('ACR Reference') }}</th>
          <td style="width:31%"><code>{{ $a->reference }}</code></td>
          <th style="width:22%">{{ __('ACR Created Date') }}</th>
          <td style="width:24%">{{ $a->created_at ? \Carbon\Carbon::parse($a->created_at)->format('d/m/Y') : __('Not yet recorded') }}</td>
        </tr>
        <tr>
          <th>{{ __('Confirmed By') }}</th>
          <td>
            @if($a->confirmed_by_name)
              <span class="val-strong">{{ $a->confirmed_by_name }}</span>
              @if($a->confirmed_by_position)<span class="val-sub">{{ $a->confirmed_by_position }}</span>@endif
            @else
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
          <th>{{ __('Confirmation Date') }}</th>
          <td>@if($a->confirmed_at){{ \Carbon\Carbon::parse($a->confirmed_at)->format('d/m/Y') }}@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
        </tr>
        <tr>
          <th>{{ __('ACR Status & Remarks') }}</th>
          <td colspan="3"><span class="print-badge info">{{ __($acrStatus[$a->status] ?? ucfirst($a->status)) }}</span>{{ $a->notes ? ' — '.$a->notes : '' }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- SECTION F4: LEARNER CONSENT & ABC DEPOSIT RECORD --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Learner Consent & ABC Deposit Record') }}</span>
    </div>
    <table class="print-kv-table">
      <tbody>
        <tr>
          <th style="width:23%">{{ __('Learner Consent') }}</th>
          <td colspan="3">
            <span class="print-check {{ $latestGivenConsent ? 'checked' : '' }}"></span><span class="val-strong">{{ __('Consent Obtained') }}</span>
            @if($latestGivenConsent)
              &nbsp;&middot;&nbsp; {{ \Carbon\Carbon::parse($latestGivenConsent->decided_at)->format('d/m/Y') }} ({{ $latestGivenConsent->channel }})
            @elseif($consentDeclined)
              &nbsp;&middot;&nbsp; <span class="not-recorded">{{ __('Declined or Postponed') }}</span>
            @else
              &nbsp;&middot;&nbsp; <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
        </tr>
        <tr>
          <th>{{ __('ABC Reference') }}</th>
          <td style="width:31%">@if($a->abc_reference)<code>{{ $a->abc_reference }}</code>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
          <th style="width:22%">{{ __('Deposit Date') }}</th>
          <td style="width:24%">@if($a->deposit_date){{ \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') }}@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
        </tr>
        <tr>
          <th>{{ __('Credits Deposited') }}</th>
          <td>@if($a->deposited_credits !== null)<span class="val-strong">{{ $fmtNum($a->deposited_credits) }} {{ __('credits') }}</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
          <th>{{ __('Valid Until (10 yrs)') }}</th>
          <td>@if($a->credit_expiry)<span class="val-strong">{{ \Carbon\Carbon::parse($a->credit_expiry)->format('d/m/Y') }}</span>@else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
        </tr>
        <tr>
          <th>{{ __('Deposit Status') }}</th>
          <td colspan="3">
            <span class="print-check {{ in_array($a->deposit_status, ['successful', 'confirmed'], true) ? 'checked' : '' }}"></span>{{ __('Successful') }}
            <span class="print-check {{ $a->deposit_status === 'in_process' ? 'checked' : '' }}" style="margin-left:12px"></span>{{ __('In process') }}
            <span class="print-check {{ $a->deposit_status === 'correction' ? 'checked' : '' }}" style="margin-left:12px"></span>{{ __('Correction needed') }}
            @if(!$a->deposit_status) &nbsp;<span class="not-recorded">({{ __('Not yet deposited') }})</span>@endif
          </td>
        </tr>
        <tr>
          <th>{{ __('Depositor Officer') }}</th>
          <td colspan="3">
            @if($a->deposited_by_name)
              <span class="val-strong">{{ $a->deposited_by_name }}</span>@if($a->deposited_by_position) &mdash; <span class="muted">{{ $a->deposited_by_position }}</span>@endif
            @else
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
        </tr>
      </tbody>
    </table>
    <div class="print-note-box">
      <b>{{ __('Storage Only Notice:') }}</b> {{ __('Depositing an ACR into the Academic Bank of Credit (ABC) stores credit for 10 years and does not constitute credit transfer or programme admission.') }}
    </div>
  </div>

  {{-- SECTION F9: SINGLE REDEMPTION TRACKING --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Single Redemption Principle Record') }}</span>
    </div>
    <table class="print-audit-table">
      <thead>
        <tr>
          <th style="width:26%">{{ __('Redemption Stage') }}</th>
          <th style="width:16%">{{ __('Status') }}</th>
          <th style="width:16%">{{ __('Date') }}</th>
          <th style="width:42%">{{ __('Evidence & Programme') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1. {{ __('Available') }}</td>
          <td><span class="print-check {{ $a->credit_status === 'available' ? 'checked' : '' }}"></span>{{ $a->credit_status === 'available' ? __('Active') : ($a->deposit_date ? __('Completed') : __('Pending')) }}</td>
          <td>{{ $a->deposit_date ? \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') : '—' }}</td>
          <td>{{ $a->abc_reference ? __('ABC Ref: :ref', ['ref' => $a->abc_reference]) : __('Not yet deposited') }}</td>
        </tr>
        <tr>
          <td>2. {{ __('Provisionally Redeemed') }}</td>
          <td><span class="print-check {{ $a->credit_status === 'provisional' ? 'checked' : '' }}"></span>{{ ($provEv || $provLedger || $a->credit_status === 'provisional') ? __('Recorded') : __('Not recorded') }}</td>
          <td>{{ ($provEv ? \Carbon\Carbon::parse($provEv->occurred_at) : ($provLedger ? \Carbon\Carbon::parse($provLedger->occurred_at) : null))?->format('d/m/Y') ?: '—' }}</td>
          <td>{{ $provEv ? ($provEv->transfer_ref ? $provEv->transfer_ref.' ('.$provEv->programme_code.')' : ($provEv->evidence_ref ?: __('Transfer approved'))) : ($provLedger ? __('Transfer approved') : '—') }}</td>
        </tr>
        <tr>
          <td>3. {{ __('Redeemed') }}</td>
          <td><span class="print-check {{ $a->credit_status === 'redeemed' ? 'checked' : '' }}"></span>{{ ($finalEv || $a->credit_status === 'redeemed') ? __('Redeemed (Final)') : __('Not recorded') }}</td>
          <td>{{ $finalEv ? \Carbon\Carbon::parse($finalEv->occurred_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $finalEv ? $finalEv->evidence_ref.($finalEv->abc_updated_on ? ' · '.__('ABC updated :date', ['date' => \Carbon\Carbon::parse($finalEv->abc_updated_on)->format('d/m/Y')]) : '') : ($a->credit_status === 'redeemed' ? __('Status recorded in ABC ledger') : '—') }}</td>
        </tr>
        <tr>
          <td>4. {{ __('Programme Not Completed / Released') }}</td>
          <td><span class="print-check {{ $relEv ? 'checked' : '' }}"></span>{{ $relEv ? __('Released') : __('None') }}</td>
          <td>{{ $relEv ? \Carbon\Carbon::parse($relEv->occurred_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $relEv ? $relEv->evidence_ref : '—' }}</td>
        </tr>
      </tbody>
    </table>
    @if($transfers->count())
      <div class="small muted" style="margin-top:4px">
        {{ __('Linked Transfer Applications:') }}
        @foreach($transfers as $tr)
          <code>{{ $tr->reference }}</code> ({{ $tr->programme_code }}, {{ __(config('transfer.status')[$tr->status] ?? $tr->status) }}){{ !$loop->last ? '; ' : '' }}
        @endforeach
      </div>
    @endif
  </div>

  {{-- SECTION F10: RECORD CORRECTION & UPDATE LOG --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Record Correction & Update Log') }}</span>
    </div>
    <table class="print-audit-table">
      <thead>
        <tr>
          <th style="width:12%">{{ __('Date') }}</th>
          <th style="width:14%">{{ __('Record Type') }}</th>
          <th style="width:24%">{{ __('Reason') }}</th>
          <th style="width:24%">{{ __('Action Taken') }}</th>
          <th style="width:18%">{{ __('Approved By') }}</th>
          <th style="width:8%">{{ __('Notified') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($corrections as $cor)
        <tr>
          <td>{{ $cor->corrected_on ? \Carbon\Carbon::parse($cor->corrected_on)->format('d/m/Y') : '—' }}</td>
          <td>{{ $cor->record_type }}</td>
          <td>{{ $cor->reason }}</td>
          <td>{{ $cor->action }}</td>
          <td>{{ $cor->approver_name ?: __('Not yet recorded') }}@if($cor->approver_position)<div class="bm">{{ $isMs && !empty($cor->approver_id) ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $cor->approver_id)->value('position_ms') ?: $cor->approver_position) : $cor->approver_position }}</div>@endif</td>
          <td>{{ $cor->participant_notified ? __('Yes') : __('No') }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="not-recorded">{{ __('No corrections recorded.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  {{-- SECTION F11: THREE-PARTY VERIFICATION & SIGN-OFF --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Three-Party Verification & Sign-Off') }}</span>
    </div>
    <table class="print-audit-table">
      <thead>
        <tr>
          <th style="width:24%">{{ __('Role') }}</th>
          <th style="width:26%">{{ __('Name & Position') }}</th>
          <th style="width:14%">{{ __('Verification') }}</th>
          <th style="width:12%">{{ __('Date') }}</th>
          <th style="width:24%">{{ __('Remarks') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($f11Slots as $slot => $meta)
        @php $v = $verifications->get($slot); @endphp
        <tr>
          <td><b>{{ $isMs ? $meta['ms'] : $meta['en'] }}</b></td>
          <td>
            @if($v)
              <b>{{ $v->actor_name_snapshot }}</b>
              @if($v->actor_position_snapshot)<div class="bm">{{ $isMs && $v->user_id ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $v->user_id)->value('position_ms') ?: $v->actor_position_snapshot) : $v->actor_position_snapshot }}</div>@endif
            @else
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
          <td>
            <span class="print-check {{ $v && $v->status === 'confirmed' ? 'checked' : '' }}"></span>{{ $v && $v->status === 'confirmed' ? __('Confirmed') : __('Pending') }}
          </td>
          <td>{{ $v && $v->verified_at ? \Carbon\Carbon::parse($v->verified_at)->format('d/m/Y') : __('Not yet recorded') }}</td>
          <td>{{ $v?->remarks ?: '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection
