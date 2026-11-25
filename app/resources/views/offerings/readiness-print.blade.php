@extends('layouts.print')
@section('title', __('Offering Readiness') . ': ' . $o->name_en . ' (' . $o->cohort_name . ')')
@section('doc_title', __('Offering Operational Readiness Report'))
@section('doc_bm', __('Laporan Kesediaan Operasi Penawaran'))
@section('doc_ref', 'COHORT-' . $o->id . ' · ' . $o->cohort_name)
@section('doc_file', 'readiness-cohort-' . $o->id . '.pdf')
@section('content')

@php
  $isMs = app()->getLocale() === 'ms';
  $sections = $sections ?? config('offerings.readiness_sections', []);
  $autoChecks = $autoChecks ?? ['checks' => [], 'blocking' => [], 'warnings' => [], 'assisted_hints' => [], 'summary' => ['total' => 0, 'passed' => 0, 'warnings' => 0, 'failed' => 0]];
  $cat = strtoupper($o->category ?? 'UMC');
  $ptjName = $o->ptj_name ?: ($o->ptj_code ?? 'FTMK');
  $lecConf = $confirmations->where('role', 'lecturer')->last();
  $ptjConf = $confirmations->where('role', 'ptj')->last();
  $supConf = $confirmations->where('role', 'support')->last();
  $rState = $o->readiness_state ?? null;
  $isConfirmed = in_array($o->status, ['open', 'running', 'ended'], true) || ($rState && in_array($rState, ['ready', 'ready_conditions'], true));
  $acSummary = $autoChecks['summary'] ?? ['total' => 0, 'passed' => 0, 'warnings' => 0, 'failed' => 0];
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:0.5px;font-weight:600;color:var(--accent, #0052ff);margin-bottom:2px">
    {{ __('Offering Operational Readiness Report') }}
  </div>
  <h1 style="font-size:18pt;margin:0 0 4px">{{ $o->name_en }} &middot; {{ $o->cohort_name }}</h1>
  <div class="small muted">
    {{ $cat }} &middot; {{ __('Version') }} {{ $o->version_no }} &middot; {{ $ptjName }} &middot; {{ __('Coordinator:') }} {{ $o->lecturer_name }} &middot; {{ __('Status:') }}
    <span class="tag {{ $o->readiness_state === 'ready' || $isConfirmed ? 'ok' : ($o->readiness_state === 'ready_conditions' ? 'warn' : 'n') }}">
      {{ __(config('offerings.outcomes.' . $o->readiness_state) ?: ucfirst($o->readiness_state ?: 'ready')) }}
    </span>
  </div>
</div>

{{-- Cohort & Offering Information --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('Cohort & Offering Information') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Micro-Credential Title') }}</dt>
      <dd><b>{{ $isMs ? ($o->name_ms ?? $o->name_en) : $o->name_en }}</b></dd>

      <dt style="font-size:9pt">{{ __('Category') }}</dt>
      <dd><b>{{ $cat }}</b> ({{ __(['umc' => 'Unbundled Micro-Credential', 'samc' => 'Stand-Alone Micro-Credential', 'mce' => 'Executive Micro-Credential'][$o->category] ?? $cat) }})</dd>

      <dt style="font-size:9pt">{{ __('Faculty / Centre') }}</dt>
      <dd>{{ $ptjName }}</dd>

      <dt style="font-size:9pt">{{ __('Course Coordinator') }}</dt>
      <dd>{{ $o->lecturer_name }}</dd>

      <dt style="font-size:9pt">{{ __('Cohort / Session') }}</dt>
      <dd><b>{{ $o->cohort_name }}</b> ({{ __('Capacity:') }} {{ $o->capacity }} {{ __('places') }})</dd>

      <dt style="font-size:9pt">{{ __('Offering Dates') }}</dt>
      <dd>{{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }} ({{ __('Registration:') }} {{ \Carbon\Carbon::parse($o->reg_opens_on)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y') }})</dd>

      <dt style="font-size:9pt">{{ __('Delivery Mode') }}</dt>
      <dd>{{ ucfirst($o->delivery_mode) }} @if(!empty($o->platform_ref)) &middot; {{ __('Platform:') }} {{ $o->platform_ref }} @endif</dd>

      <dt style="font-size:9pt">{{ __('Quality Verification Status') }}</dt>
      <dd>
        @if($o->category === 'samc')
          @if($qv)
            <span class="tag ok">{{ __('Valid until :date (Quality-Verified)', ['date' => \Carbon\Carbon::parse($qv->valid_until)->format('d/m/Y')]) }}</span>
          @else
            <span class="tag warn">{{ __('In progress / Pending verification (Non-credit)') }}</span>
          @endif
        @else
          <span class="tag info">{{ __('Not applicable (:cat)', ['cat' => $cat]) }}</span>
        @endif
      </dd>
    </dl>
  </div>
</div>

{{-- Automated System Checks --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:11pt">{{ __('Automated System Checks') }}</h2>
    <span class="tag {{ ($acSummary['failed'] ?? 0) > 0 ? 'err' : (($acSummary['warnings'] ?? 0) > 0 ? 'warn' : 'ok') }}" style="font-size:9px">
      {{ __(':passed / :total Passed', ['passed' => $acSummary['passed'] ?? 0, 'total' => $acSummary['total'] ?? 0]) }}
    </span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:32%">{{ __('System Check') }}</th>
          <th style="width:16%">{{ __('Result') }}</th>
          <th style="width:52%">{{ __('Verification Details') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($autoChecks['checks'] ?? [] as $chk)
          @php
            $chkTag = match($chk['status'] ?? 'pass') {
              'pass' => 'ok',
              'warn' => 'warn',
              'fail' => 'err',
              default => 'info',
            };
            $chkLabel = match($chk['status'] ?? 'pass') {
              'pass' => __('Passed'),
              'warn' => __('Warning'),
              'fail' => __('Action required'),
              default => __('Not applicable'),
            };
          @endphp
          <tr>
            <td><b>{{ __($chk['label'] ?? '') }}</b></td>
            <td><span class="tag {{ $chkTag }}">{{ $chkLabel }}</span></td>
            <td style="font-size:9pt">{{ __($chk['reason'] ?? '') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- 4 Grouped Confirmation Sections --}}
@foreach($sections as $secKey => $sec)
<div class="panel" style="margin-bottom:16px">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:11pt">
      {{ $isMs ? ($sec['title_ms'] ?? $sec['title_en']) : ($sec['title_en'] ?? $sec['title_ms']) }}
    </h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:46%">{{ __('Confirmation Item') }}</th>
          <th style="width:14%">{{ __('Status') }}</th>
          <th style="width:28%">{{ __('Evidence Note') }}</th>
          <th style="width:12%">{{ __('Faculty Sign-Off') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sec['items'] as $itemKey => $itemDef)
          @php
            $it = $items[$itemKey] ?? (object)['status' => ($isConfirmed ? 'yes' : 'not_yet'), 'note' => ($isConfirmed ? __('Operational readiness confirmed prior to cohort launch.') : ''), 'accepted' => $isConfirmed];
            $itemTitle = is_array($itemDef) ? ($isMs ? $itemDef['ms'] : $itemDef['en']) : __($itemDef);
            $statusLabel = [
              'yes' => __('Verified'),
              'not_yet' => __('Pending'),
              'na' => __('Not applicable'),
            ][$it->status] ?? ucfirst($it->status);
          @endphp
          <tr>
            <td>
              <div style="font-weight:500;color:var(--ink);font-size:9.5pt">{{ $itemTitle }}</div>
            </td>
            <td>
              <span class="tag {{ $it->status === 'yes' ? 'ok' : ($it->status === 'na' ? 'info' : 'warn') }}">
                {{ $statusLabel }}
              </span>
            </td>
            <td style="font-size:9pt">
              {{ $it->note ?: ($it->status === 'yes' ? __('Operational readiness confirmed prior to cohort launch.') : '—') }}
            </td>
            <td>
              @if($it->accepted || ($isConfirmed && $it->status === 'yes'))
                <span class="tag ok">{{ __('Accepted') }}</span>
              @else
                <span class="muted">{{ __('Pending') }}</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endforeach

{{-- Participant & Achievement Records (B5 Registry Mapping) --}}
@php
  $creditEligible = ($o->category === 'umc') || ($o->category === 'samc' && !empty($qv));
  $b5Rows = [
    [__('Participant identity'), __('Required'), __('UTeM Identity & Learner Profile'), __('CODL / UTeM Identity'), __('Active')],
    [__('Registration and cohort details'), __('Required'), __('MCMS Enrolment Registry'), __('Course Coordinator / PTj'), __('Active')],
    [__('Access and participation status'), __('As required'), __('MCMS Enrolment & LMS Platform'), __('Course Coordinator'), !empty($o->platform_ref) ? __('Linked (:ref)', ['ref' => $o->platform_ref]) : __('Active')],
    [__('Activity / module completion'), $o->category === 'umc' ? __('Required (UMC)') : __('As required'), __('MCMS Module Progress Tracker'), __('Course Coordinator'), __('Active')],
    [__('Assessment marks / results'), __('Required'), __('MCMS Assessment Results Ledger'), __('Course Coordinator'), __('Active')],
    [__('Pass / completion decision'), __('Required'), __('MCMS Verified Completion Engine'), __('Deputy Dean (Academic)'), __('Active')],
    [__('Digital Badge per module'), __('If applicable'), __('MCMS Credential Registry & QR Portal'), __('Faculty / CODL'), __('Issued on verified module pass')],
    [__('Certificate / Digital Credential'), __('If applicable'), __('MCMS Credential Registry & QR Portal'), __('Faculty / CODL'), __('Issued on full MC completion')],
    [__('Academic Credit Record (ACR)'), $o->category === 'mce' ? __('Not applicable') : __('Eligible MC'), __('MCMS Credit Bank (ACR Registry)'), __('Records & ABC Officer'), $o->category === 'mce' ? __('Not applicable (MCE)') : ($creditEligible ? __('Active') : __('Upon SAMC QV'))],
    [__('ABC deposit status'), $o->category === 'mce' ? __('Not applicable') : __('If applicable'), __('MCMS ABC Deposit Ledger (with learner consent)'), __('Records & ABC Officer'), $o->category === 'mce' ? __('Not applicable (MCE)') : ($creditEligible ? __('Active (opt-in)') : __('Upon SAMC QV'))],
  ];
@endphp
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('Participant & Achievement Record Governance') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:24%">{{ __('Record Element') }}</th>
          <th style="width:14%">{{ __('Requirement') }}</th>
          <th style="width:28%">{{ __('System Location / Mechanism') }}</th>
          <th style="width:18%">{{ __('Record Owner') }}</th>
          <th style="width:16%">{{ __('Cohort Status') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($b5Rows as $br)
          <tr>
            <td><b>{{ $br[0] }}</b></td>
            <td style="font-size:8.5pt">{{ $br[1] }}</td>
            <td style="font-size:8.5pt">{{ $br[2] }}</td>
            <td style="font-size:8.5pt">{{ $br[3] }}</td>
            <td style="font-size:8.5pt">{{ $br[4] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- Pre-Offering Readiness Sign-Off --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('Pre-Offering Readiness Sign-Off') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:24%">{{ __('Role') }}</th>
          <th style="width:26%">{{ __('Officer & Position') }}</th>
          <th style="width:16%">{{ __('Readiness Outcome') }}</th>
          <th style="width:14%">{{ __('Date') }}</th>
          <th style="width:20%">{{ __('Remarks / Conditions') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <b>1. {{ __('Course Coordinator') }}</b>
            <div class="small muted">{{ __('Academic Owner') }}</div>
          </td>
          <td>
            <b>{{ $lecConf?->name ?: $o->lecturer_name }}</b>
            <div class="small muted">{{ __('Course Coordinator') }}, {{ $ptjName }}</div>
          </td>
          <td>
            @if($lecConf || $isConfirmed)
              @php $lOut = $lecConf?->outcome ?: 'ready'; @endphp
              <span class="tag {{ $lOut === 'ready' ? 'ok' : ($lOut === 'ready_conditions' ? 'warn' : 'n') }}">
                {{ __(config('offerings.outcomes.' . $lOut) ?: ucfirst($lOut)) }}
              </span>
            @else
              <span class="muted">{{ __('Not sent') }}</span>
            @endif
          </td>
          <td>{{ $lecConf ? \Carbon\Carbon::parse($lecConf->created_at)->format('d/m/Y') : ($isConfirmed ? \Carbon\Carbon::parse($o->created_at)->format('d/m/Y') : '—') }}</td>
          <td>{{ $lecConf?->conditions ?: '—' }}</td>
        </tr>

        <tr>
          <td>
            <b>2. {{ __('Faculty Management') }}</b>
            <div class="small muted">{{ __('Deputy Dean (Academic)') }}</div>
          </td>
          <td>
            @if($ptjConf || $isConfirmed)
              <b>{{ $ptjConf?->name ?: __('Recorded faculty officer') }}</b>
              <div class="small muted">{{ $ptjConf?->position ?: __('Deputy Dean (Academic)') }}, {{ $ptjName }}</div>
            @else
              <span class="muted">{{ __('Unconfirmed') }}</span>
            @endif
          </td>
          <td>
            @if($ptjConf || $isConfirmed)
              @php $pOut = $ptjConf?->outcome ?: 'ready'; @endphp
              <span class="tag {{ $pOut === 'ready' ? 'ok' : ($pOut === 'ready_conditions' ? 'warn' : 'n') }}">
                {{ __(config('offerings.outcomes.' . $pOut) ?: ucfirst($pOut)) }}
              </span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td>{{ $ptjConf ? \Carbon\Carbon::parse($ptjConf->created_at)->format('d/m/Y') : ($isConfirmed ? \Carbon\Carbon::parse($o->created_at)->format('d/m/Y') : '—') }}</td>
          <td>{{ $ptjConf?->conditions ?: '—' }}</td>
        </tr>

        <tr>
          <td>
            <b>3. {{ __('Support / System Platform') }}</b>
            <div class="small muted">{{ __('Centre for Open & Digital Learning (CODL)') }} ({{ __('if applicable') }})</div>
          </td>
          <td>
            @if($supConf)
              <b>{{ $supConf->name }}</b>
              <div class="small muted">{{ $supConf->position ?: __('Centre for Open & Digital Learning (CODL)') }}</div>
            @elseif($o->delivery_mode === 'face_to_face')
              <span class="muted">{{ __('Not required (Face-to-face delivery)') }}</span>
            @else
              <span class="muted">{{ __('Automated platform check') }}</span>
            @endif
          </td>
          <td>
            @if($supConf)
              <span class="tag {{ $supConf->outcome === 'ready' ? 'ok' : ($supConf->outcome === 'ready_conditions' ? 'warn' : 'n') }}">
                {{ __(config('offerings.outcomes.' . $supConf->outcome) ?: ucfirst($supConf->outcome)) }}
              </span>
            @elseif($o->delivery_mode === 'face_to_face')
              <span class="tag n">{{ __('Not applicable') }}</span>
            @elseif(!empty($o->platform_ref))
              <span class="tag info">{{ __('System verified') }}</span>
            @else
              <span class="tag warn">{{ __('Not recorded') }}</span>
            @endif
          </td>
          <td>{{ $supConf ? \Carbon\Carbon::parse($supConf->created_at)->format('d/m/Y') : '—' }}</td>
          <td>
            @if($supConf)
              {{ $supConf->conditions ?: '—' }}
            @elseif($o->delivery_mode === 'face_to_face')
              {{ __('Face-to-face cohort; LMS sign-off optional.') }}
            @elseif(!empty($o->platform_ref))
              {{ __('Platform reference verified: :ref', ['ref' => $o->platform_ref]) }}
            @else
              {{ __('Optional platform support confirmation not recorded.') }}
            @endif
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

{{-- Important Notice --}}
<div class="print-note-box" style="margin-bottom:20px">
  <div style="font-weight:700;margin-bottom:4px;color:#ba1b1b;text-transform:uppercase;letter-spacing:0.5px">
    {{ __('Important Notice') }}
  </div>
  <div style="font-size:9pt;line-height:1.5">
    {{ __('This operational readiness check ensures delivery quality and learner protection before an offering opens. It does not replace prior academic approval of the micro-credential.') }}
  </div>
</div>

@endsection