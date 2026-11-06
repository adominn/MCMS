@extends('layouts.app')
@section('title', __('Help and guides'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('tasks') }}">{{ __('Home') }}</a>
  <span>/</span>
  <span>{{ __('Help and guides') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('Help and guides') }}</h1>
    <div class="sub">{{ __('Plain-language answers about each recognition stage, and a quick guide for your role') }}</div>
  </div>
</div>

<div class="grid3">
  <div class="card">
    <h3 style="margin-top:0">{{ __('Badge') }}</h3>
    <div class="muted small">{{ __('Shows you achieved one module. It is not a certificate and carries no academic credit on its own.') }}</div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">{{ __('Certificate and Digital Credential') }}</h3>
    <div class="muted small">{{ __('Issued automatically by the system after the deputy dean confirms completion of all modules in the micro-credential.') }}</div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">{{ __('Academic Credit Record (ACR)') }}</h3>
    <div class="muted small">{{ __('Records academic credit for an eligible UMC or a SAMC with valid Quality Verification. Never issued for an MCE.') }}</div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">{{ __('ABC deposit') }}</h3>
    <div class="muted small">{{ __('Stores your credit in the Academic Bank of Credit for up to 10 years. Storage only; it is not a credit transfer.') }}</div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">{{ __('Credit transfer') }}</h3>
    <div class="muted small">{{ __('The receiving programme\'s faculty evaluates learning outcome equivalence course-by-course and decides approval.') }}</div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">{{ __('Stackable pathway') }}</h3>
    <div class="muted small">{{ __('A published mapping of micro-credentials to degree courses. At least 30% of credit must still be earned directly at UTeM.') }}</div>
  </div>
</div>

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Quick guide for your role') }}</h2></div>
  <div class="bd">
    <div class="checks">
      @if($u?->hasRole('student'))
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('My learning') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Your dashboard for courses in progress, verification queues, and action notices.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Registrations and payments') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Review registrations, download official receipts, or complete pending payments.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Credentials') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Access issued certificates, verifiable Digital Credentials, and verification sharing links.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Credit ledger and ABC') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('View Academic Credit Records, give or decline ABC deposit consent, and check credit expiry.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Credit transfer') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Submit applications to count deposited micro-credential credits toward an admitted degree.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('My degree plan') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Follow faculty advice and stackable pathway rules on your journey toward a degree.') }}</span></div>
        </div>
      @elseif($u?->hasRole('lecturer'))
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Proposals and versions') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Draft new micro-credentials, formulate assessment matrices, and respond to reviewer comments.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Cohorts and results') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Track running cohort progress, upload component marks, and submit results for verification.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Quality review (CQI)') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Analyze cohort metrics and submit continuous quality improvement actions.') }}</span></div>
        </div>
      @elseif($u?->hasRole('deputy_dean'))
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Task queue') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Process endorsement requests, mark verifications, and ABC deposit submissions.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Offerings & readiness') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Audit delivery checklists and confirm cohort launch readiness.') }}</span></div>
        </div>
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Credit transfer evaluation') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Evaluate course-level learning outcome matches for applicant transfers.') }}</span></div>
        </div>
      @elseif($u?->hasRole('industry_expert') || $u?->hasRole('reviewer'))
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Review invitations') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Review assigned micro-credential proposals, declare conflicts of interest, and provide feedback on industry relevance.') }}</span></div>
        </div>
      @elseif($u?->hasRole('codl_admin'))
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('Administration & governance') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Manage system settings, roles, reference data, and integration adapters.') }}</span></div>
        </div>
      @else
        <div class="check">
          <span class="dot ok"></span>
          <div class="grow"><b>{{ __('General access') }}</b><span class="s" style="display:block;font-size:12px;color:var(--muted)">{{ __('Browse the catalogue, view documents, and access notifications.') }}</span></div>
        </div>
      @endif
    </div>
  </div>
</div>

<div class="panel">
  <div class="hd"><h2>{{ __('Contact') }}</h2></div>
  <div class="bd">
    <p style="margin:0">{{ __('CODL helpdesk · :email · :phone (:hours)', ['email' => 'codl@utem.edu.my', 'phone' => '06-270 2000', 'hours' => '08:00 – 17:00, Monday to Friday']) }}</p>
  </div>
</div>
@endsection
