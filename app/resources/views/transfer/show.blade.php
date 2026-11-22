@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Transfer application'))
@section('doc_title', 'Credit Transfer Decision')
@section('doc_bm', 'Keputusan Pemindahan Kredit')
@section('doc_ref', $a->reference)
@section('doc_file', 'transfer-decision-'.$a->reference.'.pdf')
@section('content')

@php
  $isPrint = request()->boolean('print') || request()->has('print');
  $isMs = app()->getLocale() === 'ms';
  $fmtNum = fn ($v) => mcms_num($v);
  $decided = in_array($a->status, ['approved', 'partly', 'not_approved'], true);
  $isStudent = auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('deputy_dean', 'codl_admin');
  $backUrl = $isStudent ? route('transfer') : route('transfers');
  $backLabel = $isStudent ? __('All applications') : __('Credit transfer queue');

  // Applicant identity strictly from application's user record (NEVER auth()->user()!)
  $applicantName = $a->user_name ?? __('Not yet recorded');
  $applicantId = $a->institution_id ?? __('Not yet recorded');

  // Decider identity strictly from real decision actor (NEVER hardcoded!)
  $deciderName = $decision?->decided_by_name ?: ($decision?->actor_name ?: null);
  $deciderPosition = $decision?->decided_by_position ?: ($decision?->actor_position ?: null);
  $decidedDate = $decision?->decided_on ? \Carbon\Carbon::parse($decision->decided_on)->format('d/m/Y') : null;

  $docChecklist = json_decode((string) ($a->document_checklist ?? ''), true) ?: null;
  $checkedDocs = $docChecklist['items'] ?? [];
  $resultLabels = config('transfer.result_labels');
  $verifications = $verifications ?? collect();
  $redemptions = $redemptions ?? collect();
  $lifeStage = $decided ? 8 : 7;

  [$whereTxt, $nextTxt, $whoTxt, $roleCode] = match ($a->status) {
    'submitted' => [
      __('Stage 07: Transfer application submitted on :date', ['date' => \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y')]),
      __('Receiving faculty Deputy Dean checks ACR authenticity and ABC status, then evaluates learning outcomes.'),
      __('Receiving Faculty Deputy Dean'),
      'P3',
    ],
    'under_eval' => [
      __('Stage 07: Record validity verified — under course equivalence evaluation'),
      __('Receiving faculty Deputy Dean completes the 9-criterion evaluation and issues an official decision letter.'),
      __('Receiving Faculty Deputy Dean'),
      'P3',
    ],
    'more_evidence' => [
      __('Stage 07: Additional supporting evidence requested by evaluator'),
      $isStudent ? __('Provide the requested evidence below so the application returns to evaluation.') : __('Waiting for applicant to submit requested supporting evidence.'),
      $isStudent ? __('You (Participant)') : $applicantName,
      'P8',
    ],
    default => [
      __('Stage 08: Official transfer decision issued (:st)', ['st' => __($status[$a->status] ?? ucfirst($a->status))]),
      $a->status === 'not_approved'
        ? __('Your ACR, credentials, and ABC credit remain unchanged and Available for future applications.')
        : __('Approved credits are Provisionally Redeemed until programme completion is recorded. Print your official decision letter for your records.'),
      __('Receiving Faculty Deputy Dean / Registry'),
      'P3/P7',
    ],
  };
@endphp

@if(!$isPrint)
  <x-breadcrumb :crumbs="[
    ($isStudent ? __('Credit transfer') : __('Credit transfer evaluation')) => $backUrl,
    $a->reference => null,
  ]" />

  <div class="title">
    <div>
      <h1>{{ __('Transfer Application') }} {{ $a->reference }}</h1>
      <div class="sub">{{ $applicantName }} (<code>{{ $applicantId }}</code>) &middot; {{ $a->programme }} {{ $a->programme_name }} &middot; {{ __('applied') }} {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }}</div>
    </div>
    <div class="act" style="align-items:center;display:flex;gap:8px">
      <a class="btn ter sm" href="{{ $backUrl }}">&larr; {{ $backLabel }}</a>
      <span class="tag {{ $a->status === 'approved' ? 'ok' : (in_array($a->status, ['submitted', 'under_eval'], true) ? 'info' : ($a->status === 'partly' ? 'warn' : 'err')) }}" id="app-status">
        {{ __($status[$a->status] ?? ucfirst($a->status)) }}
      </span>
      @if($decided)
        <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter" target="_blank">{{ __('Decision letter (PDF)') }}</a>
      @endif
    </div>
  </div>

  <x-credit-lifecycle :stage="$lifeStage" :blocked="$a->status === 'more_evidence'" :blockedLabel="__('Evidence needed')" :note="$a->reference" />

  @if($decided && $decision)
    <div class="note {{ $decision->outcome === 'approved' ? 'ok' : ($decision->outcome === 'not_approved' ? 'err' : 'warn') }}" id="decision" style="margin-bottom:20px">
      <div>
        <b>{{ __($status[$decision->outcome] ?? ucfirst($decision->outcome)) }} {{ __('on') }} {{ $decidedDate ?? __('Not yet recorded') }}</b>
        @if($decision->decision_ref), {{ __('reference') }} <code>{{ $decision->decision_ref }}</code>@endif
        @if($deciderName) &middot; {{ __('Decided by') }} <b>{{ $deciderName }}</b>@if($deciderPosition) ({{ $deciderPosition }})@endif @endif.
        @if($decision->conditions)<div style="margin-top:4px"><b>{{ __('Conditions / Justification:') }}</b> {{ $decision->conditions }}</div>@endif
      </div>
    </div>
  @endif

  <div class="cols">
    <div>
      @if($a->status === 'more_evidence' && $isStudent)
        <div class="panel" style="margin-bottom:20px;border-top:3px solid var(--warn)">
          <div class="hd"><h2>{{ __('More evidence is needed') }}</h2></div>
          <div class="bd">
            <div class="note warn" style="margin-bottom:16px">
              <div>{{ __('The evaluator asked for more evidence before deciding:') }} {{ $a->evidence_request }}</div>
            </div>
            <form method="post" action="{{ route('transfer.evidence', $a->id) }}">
              @csrf
              <div style="margin-bottom:14px">
                <label for="documents">{{ __('What you are adding') }}</label>
                <textarea id="documents" name="documents" rows="3" required placeholder="{{ __('Describe what you are adding or provide reference links...') }}"></textarea>
              </div>
              <button type="submit" class="btn">{{ __('Send evidence') }}</button>
            </form>
          </div>
        </div>
      @endif

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Transfer Lines & Outcomes') }}</h2></div>
        <div class="table-wrap">
          <table id="lines" style="margin:0; width:100%">
            <thead>
              <tr>
                <th>{{ __('Credit used (ACR)') }}</th>
                <th>{{ __('Course requested') }}</th>
                <th class="num" style="width:95px">{{ __('Requested') }}</th>
                <th class="num" style="width:95px">{{ __('Approved') }}</th>
                <th style="width:160px">{{ __('Credit status') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lines as $l)
                <tr>
                  <td>
                    <b>{{ $l->source_name }}</b>@if($l->source_version_no) <span class="tag info small">v{{ $l->source_version_no }}</span>@endif<br>
                    <span class="muted small"><code>{{ $l->acr_reference }}</code></span>
                  </td>
                  <td>
                    <b>{{ $l->course_code }}</b> {{ $l->course_name }}
                    @if($l->target_version)<br><span class="muted small">{{ __('Curriculum') }}: {{ $l->target_version }}</span>@endif
                  </td>
                  <td class="num tabular">{{ $fmtNum($l->credits_requested) }}</td>
                  <td class="num tabular">{{ $l->credits_approved !== null ? $fmtNum($l->credits_approved) : '—' }}</td>
                  <td>
                    {{ ['available' => __('Available'), 'provisional' => __('Provisionally redeemed'), 'redeemed' => __('Redeemed'), 'expired' => __('Expired')][$l->credit_status] ?? __(ucfirst((string) $l->credit_status)) }}
                    @if($l->reevaluate)<br><span class="tag warn">{{ __('Needs re-evaluation') }}</span>@endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      @if($docChecklist || $a->documents)
        <div class="panel" style="margin-bottom:20px">
          <div class="hd"><h2>{{ __('Supporting Documents Checklist') }}</h2></div>
          <div class="bd">
            @if($docChecklist)
              <div class="checklist-grid" style="margin-bottom:8px">
                @foreach(config('transfer.documents_checklist') as $k => $lbl)
                  @php $ticked = in_array($k, $checkedDocs, true); @endphp
                  <div style="padding:6px 10px;border:1px solid var(--hairline);background:{{ $ticked ? 'var(--ok-bg)' : 'var(--surface)' }};font-size:12.5px">
                    <b>[{{ $ticked ? 'X' : ' ' }}]</b> {{ __($lbl) }}
                  </div>
                @endforeach
              </div>
              @if(!empty($docChecklist['other']))<div class="small"><b>{{ __('Other:') }}</b> {{ $docChecklist['other'] }}</div>@endif
              @if(!empty($docChecklist['notes']))<div class="small muted" style="margin-top:4px">{{ $docChecklist['notes'] }}</div>@endif
            @else
              <p style="white-space:pre-line;margin:0">{{ $a->documents }}</p>
            @endif
          </div>
        </div>
      @endif
    </div>

    <div>
      <div class="panel">
        <div class="hd"><h2>{{ __('What this means') }}</h2></div>
        <div class="bd">
          <p class="small" style="margin:0 0 12px">{{ __('The decision is made by the receiving programme. It does not admit you to the programme, and a refusal does not change your Academic Credit Record, badges, certificates or credentials.') }}</p>
          <p class="small" style="margin:0 0 12px">{{ __('If a line is not approved, your ACR and credit stay as they were. An approved transfer holds credit as Provisionally Redeemed until programme graduation.') }}</p>
          @if($decided)
            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter sm" target="_blank" style="width:100%;justify-content:center">{{ __('Print Decision Letter (A4)') }}</a>
          @endif
        </div>
      </div>

      <div style="margin-top:12px">
        <a class="btn ghost sm" href="{{ $backUrl }}">&larr; {{ $isStudent ? __('Back to credit transfer') : __('Back to credit transfer queue') }}</a>
      </div>
    </div>
  </div>

@else
  {{-- =========================================================================
       OFFICIAL A4 PRINTABLE TRANSFER DECISION LETTER — F6, F7, F8, F9, F11 (Q6)
       Addressed strictly to applicant ($a->user_name); real actor & dates only.
       Single-language output driven strictly by system locale toggle (EN / BM).
     ========================================================================= --}}
  <div style="margin-bottom:10px;border-bottom:2px solid #0b1a3d;padding-bottom:8px">
    <h1 style="font-size:14.5pt;font-weight:400;margin:0 0 2px;color:#090a0f">{{ __('Credit Transfer Decision Letter') }}</h1>
    <div style="font-size:8.8pt;line-height:1.45;color:#262626">
      @if($isMs)
        Tuan/Puan <b>{{ $applicantName }}</b>, dokumen rasmi ini merekodkan permohonan pemindahan kredit (<b>{{ $a->reference }}</b>, dihantar pada {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }}) bagi program <b>{{ $a->programme_name }} ({{ $a->programme }})</b> di {{ $a->institution ?: 'UTeM' }}, penilaian kesetaraan kursus, dan keputusan rasmi fakulti penerima.
      @else
        Dear <b>{{ $applicantName }}</b>, this official document records the credit transfer application (<b>{{ $a->reference }}</b>, submitted {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }}) for <b>{{ $a->programme_name }} ({{ $a->programme }})</b> at {{ $a->institution ?: 'UTeM' }}, its course equivalence evaluation, and the receiving faculty's official decision.
      @endif
    </div>
  </div>

  {{-- Top At-a-Glance Summary Strip --}}
  <div class="print-kpi-bar">
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Applicant') }}</div>
      <div class="print-kpi-val">{{ $applicantName }}</div>
      <div class="print-kpi-sub">{{ $applicantId }}</div>
    </div>
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Application Ref') }}</div>
      <div class="print-kpi-val" style="font-family:var(--mono);color:#0b1a3d !important">{{ $a->reference }}</div>
      <div class="print-kpi-sub">{{ __('Target:') }} {{ $a->programme }} ({{ $a->institution ?: 'UTeM' }})</div>
    </div>
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Decision Outcome') }}</div>
      <div class="print-kpi-val">{{ __($status[$a->status] ?? ucfirst($a->status)) }}</div>
      <div class="print-kpi-sub">{{ __(':approved of :requested credits approved', ['approved' => $fmtNum($lines->sum('credits_approved')), 'requested' => $fmtNum($lines->sum('credits_requested'))]) }}</div>
    </div>
    <div class="print-kpi-cell">
      <div class="print-kpi-lbl">{{ __('Decision Date & Ref') }}</div>
      <div class="print-kpi-val">{{ $decidedDate ?? __('Not yet recorded') }}</div>
      <div class="print-kpi-sub">{{ $decision?->decision_ref ?: __('Pending reference') }}</div>
    </div>
  </div>

  @if(!$decided)
    <div class="print-note-box">
      <b>{{ __('Draft — Decision Not Yet Issued:') }}</b> {{ __('The receiving faculty authority has not recorded a final decision on this application yet.') }}
    </div>
  @endif

  {{-- SECTION F6: APPLICATION & SUPPORTING DOCUMENTS --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Application & Supporting Documents') }}</span>
    </div>
    <table class="print-kv-table">
      <tbody>
        <tr>
          <th style="width:22%">{{ __('Applicant') }}</th>
          <td style="width:30%"><span class="val-strong">{{ $applicantName }}</span> <span class="muted">({{ $applicantId }})</span></td>
          <th style="width:22%">{{ __('Application Ref & Date') }}</th>
          <td style="width:26%"><code>{{ $a->reference }}</code> &middot; {{ \Carbon\Carbon::parse($a->applied_at)->format('d/m/Y') }}</td>
        </tr>
        <tr>
          <th>{{ __('Target Programme') }}</th>
          <td><span class="val-strong">{{ $a->programme }}</span> {{ $a->programme_name }}</td>
          <th>{{ __('Receiving Institution') }}</th>
          <td>{{ $a->institution ?: 'UTeM' }}</td>
        </tr>
        <tr>
          <th>{{ __('Supporting Documents') }}</th>
          <td colspan="3">
            @foreach(config('transfer.documents_checklist') as $k => $lbl)
              <span style="display:inline-block;margin-right:10px;margin-bottom:3px;font-size:8pt">
                <span class="print-check {{ in_array($k, $checkedDocs, true) ? 'checked' : '' }}"></span>{{ __($lbl) }}
              </span>
            @endforeach
            @if(!empty($docChecklist['other']))<span class="val-sub">{{ __('Other:') }} {{ $docChecklist['other'] }}</span>@endif
            @if(!empty($docChecklist['notes']))<span class="val-sub">{{ __('Notes:') }} {{ $docChecklist['notes'] }}</span>@elseif(!$docChecklist && $a->documents)<span class="val-sub">{{ __('Listed:') }} {{ $a->documents }}</span>@endif
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- SECTION F7: EQUIVALENCE EVALUATION SUMMARY --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Equivalence Evaluation Summary') }}</span>
    </div>
    @foreach($lines as $l)
      <div style="font-size:8.5pt;font-weight:600;margin:4px 0">{{ __('Course:') }} {{ $l->course_code }} {{ $l->course_name }} &larr; {{ $l->source_name }} (<code>{{ $l->acr_reference }}</code>)</div>
      <table class="print-audit-table">
        <thead>
          <tr>
            <th style="width:34%">{{ __('Criterion') }}</th>
            <th style="width:42%">{{ __('Finding') }}</th>
            <th style="width:24%">{{ __('Result') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach(config('transfer.criteria') as $k => $label)
          @php $cr = $l->criteria[$k] ?? null; @endphp
          <tr>
            <td>{{ __($label) }}</td>
            <td>{{ ($cr && $cr->finding !== '') ? $cr->finding : __('Not yet recorded') }}</td>
            <td>{{ !empty($cr->result) ? __($resultLabels[$cr->result] ?? ucfirst($cr->result)) : __('Not yet recorded') }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endforeach
  </div>

  {{-- SECTION F8: OFFICIAL CREDIT TRANSFER DECISION --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Credit Transfer Decision') }}</span>
    </div>
    <table class="print-audit-table">
      <thead>
        <tr>
          <th style="width:4%">#</th>
          <th style="width:24%">{{ __('ACR & Micro-Credential') }}</th>
          <th style="width:24%">{{ __('Course Applied For') }}</th>
          <th style="width:9%">{{ __('Req.') }}</th>
          <th style="width:13%">{{ __('Outcome') }}</th>
          <th style="width:9%">{{ __('Appr.') }}</th>
          <th style="width:17%">{{ __('Conditions') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($lines as $idx => $l)
          <tr>
            <td>{{ $idx + 1 }}</td>
            <td><b>{{ $l->source_name }}</b>@if($l->source_version_no) (v{{ $l->source_version_no }})@endif<div class="bm">{{ $l->acr_reference }}</div></td>
            <td>{{ $l->course_code }} {{ $l->course_name }}@if($l->target_version)<div class="bm">{{ __('Curriculum') }}: {{ $l->target_version }}</div>@endif</td>
            <td>{{ $fmtNum($l->credits_requested) }}</td>
            <td>{{ $l->decision ? __($status[$l->decision] ?? ucfirst($l->decision)) : __('Pending') }}</td>
            <td><b>{{ $l->credits_approved !== null ? $fmtNum($l->credits_approved) : '0' }}</b></td>
            <td>{{ $decision?->conditions ?: (($l->credits_approved && (float) $l->credits_approved > 0) ? __('Subject to admission to :prog', ['prog' => $a->programme]) : '—') }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">{{ __('Total Credits') }}</th>
          <th>{{ $fmtNum($lines->sum('credits_requested')) }}</th>
          <th>{{ __($status[$a->status] ?? ucfirst($a->status)) }}</th>
          <th>{{ $fmtNum($lines->sum('credits_approved')) }}</th>
          <th>{{ $decision?->decision_ref ? __('Ref:') . ' ' . $decision->decision_ref : '—' }}</th>
        </tr>
      </tfoot>
    </table>
    <div class="print-note-box">
      <b>{{ __('Non-Admission & Unchanged Records Notice:') }}</b> {{ __('Credit transfer approval does not imply admission to :prog. Admission is recorded separately by Pejabat Pendaftar. If any line is not approved, your Academic Credit Record, credentials, and ABC credit remain unchanged and available. Appeals may be directed to the receiving Faculty Deputy Dean (Academic) or CODL (codl@utem.edu.my).', ['prog' => $a->programme]) }}
    </div>
  </div>

  {{-- SECTION F9: SINGLE REDEMPTION STATUS --}}
  <div class="print-sec">
    <div class="print-sec-title">
      <span>{{ __('Single Redemption Status') }}</span>
    </div>
    <table class="print-audit-table">
      <thead>
        <tr>
          <th style="width:24%">{{ __('ACR & ABC Ref') }}</th>
          <th style="width:22%">{{ __('Current Credit Status') }}</th>
          <th style="width:16%">{{ __('ABC Valid Until') }}</th>
          <th style="width:38%">{{ __('Redemption Events Recorded') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($lines as $l)
        @php $lineReds = $redemptions->where('abc_deposit_id', $l->abc_deposit_id); @endphp
        <tr>
          <td><code>{{ $l->acr_reference }}</code>@if($l->abc_reference)<div class="bm">ABC: {{ $l->abc_reference }}</div>@endif</td>
          <td><b>{{ ['available' => __('Available'), 'provisional' => __('Provisionally Redeemed'), 'redeemed' => __('Redeemed'), 'expired' => __('Expired')][$l->credit_status] ?? __(ucfirst((string) $l->credit_status)) }}</b></td>
          <td>{{ $l->credit_expiry ? \Carbon\Carbon::parse($l->credit_expiry)->format('d/m/Y') : __('Not yet recorded') }}</td>
          <td>
            @forelse($lineReds as $re)
              <div>{{ __(ucfirst($re->event)) }} ({{ \Carbon\Carbon::parse($re->occurred_at)->format('d/m/Y') }})@if($re->evidence_ref) — {{ $re->evidence_ref }}@endif</div>
            @empty
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endforelse
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  {{-- APPENDIX F11: THREE-PARTY VERIFICATION & SIGN-OFF --}}
  @php
    $coordVerifs = $verifications->where('role_slot', 'coordinator')->unique('actor_name_snapshot');
    $recVerifs = $verifications->whereIn('role_slot', ['records_abc', 'abc_deposit'])->unique('actor_name_snapshot');
  @endphp
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
        <tr>
          <td><b>{{ __('Course Coordinator / Lecturer') }}</b></td>
          <td>
            @if($coordVerifs->isNotEmpty())
              @foreach($coordVerifs as $vc)
                <div><b>{{ $vc->actor_name_snapshot }}</b>@if($vc->actor_position_snapshot)<div class="bm">{{ $isMs && $vc->user_id ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $vc->user_id)->value('position_ms') ?: $vc->actor_position_snapshot) : $vc->actor_position_snapshot }}</div>@endif</div>
              @endforeach
            @else
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
          <td><span class="print-check {{ $coordVerifs->isNotEmpty() ? 'checked' : '' }}"></span>{{ $coordVerifs->isNotEmpty() ? __('Confirmed') : __('Pending') }}</td>
          <td>
            @foreach($coordVerifs as $vc)
              <div>{{ $vc->verified_at ? \Carbon\Carbon::parse($vc->verified_at)->format('d/m/Y') : __('Not yet recorded') }}</div>
            @endforeach
          </td>
          <td>
            @foreach($coordVerifs as $vc)
              <div>{{ $vc->remarks ?: '—' }}</div>
            @endforeach
          </td>
        </tr>
        <tr>
          <td><b>{{ __('Faculty Academic Authority') }}</b></td>
          <td>@if($deciderName)<b>{{ $deciderName }}</b>@if($deciderPosition)<div class="bm">{{ $isMs && $decision?->decided_by ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $decision->decided_by)->value('position_ms') ?: $deciderPosition) : $deciderPosition }}</div>@endif @else<span class="not-recorded">{{ __('Not yet recorded') }}</span>@endif</td>
          <td><span class="print-check {{ $decided && $deciderName ? 'checked' : '' }}"></span>{{ $decided && $deciderName ? __('Confirmed') : __('Pending') }}</td>
          <td>{{ $decidedDate ?? __('Not yet recorded') }}</td>
          <td>{{ $decision?->decision_ref ? __('Ref:') . ' ' . $decision->decision_ref : ($decision?->conditions ?: '—') }}</td>
        </tr>
        <tr>
          <td><b>{{ __('Records Unit / ABC Depositor') }}</b></td>
          <td>
            @if($recVerifs->isNotEmpty())
              @foreach($recVerifs as $vr)
                <div><b>{{ $vr->actor_name_snapshot }}</b>@if($vr->actor_position_snapshot)<div class="bm">{{ $isMs && $vr->user_id ? (\Illuminate\Support\Facades\DB::table('users')->where('id', $vr->user_id)->value('position_ms') ?: $vr->actor_position_snapshot) : $vr->actor_position_snapshot }}</div>@endif</div>
              @endforeach
            @else
              <span class="not-recorded">{{ __('Not yet recorded') }}</span>
            @endif
          </td>
          <td><span class="print-check {{ $recVerifs->isNotEmpty() ? 'checked' : '' }}"></span>{{ $recVerifs->isNotEmpty() ? __('Confirmed') : __('Pending') }}</td>
          <td>
            @foreach($recVerifs as $vr)
              <div>{{ $vr->verified_at ? \Carbon\Carbon::parse($vr->verified_at)->format('d/m/Y') : __('Not yet recorded') }}</div>
            @endforeach
          </td>
          <td>
            @foreach($recVerifs as $vr)
              <div>{{ $vr->remarks ?: '—' }}</div>
            @endforeach
          </td>
        </tr>
      </tbody>
    </table>
  </div>
@endif
@endsection
