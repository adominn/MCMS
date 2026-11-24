@extends('layouts.app')
@section('title', __('Registration'))
@section('content')
<x-breadcrumb :crumbs="[
  'My learning' => route('home'),
  'Registrations and payments' => route('enrolments'),
  ($o->name_en . ' ' . $o->cohort_name) => null,
]" />

<div class="title">
  <div>
    <h1>{{ $o->name_en }} {{ $o->cohort_name }}</h1>
    <div class="sub">
      {{ $e->reference }} &middot; 
      @if(!empty($o->lecturer_name)) {{ $o->lecturer_name }} &middot; @endif
      @if(!empty($o->start_date) && !empty($o->end_date))
        {{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }} &middot;
      @endif
      <x-pill :s="$labels[$e->status]" />
    </div>
  </div>
  <div class="act-bar">
    <div class="act-left">
      <a class="btn ter sm" href="{{ route('enrolments') }}">&larr; {{ __('All registrations') }}</a>
    </div>
    @if($e->status === 'completed')
      <div class="act-right">
        <a class="btn ter sm" href="{{ route('feedback.show', $e->id) }}">{{ __('Give feedback') }}</a>
      </div>
    @endif
  </div>
</div>

@if($e->status === 'withdrawn')
  <div class="note warn" style="margin-bottom:16px">{{ __('Withdrawn. Modules you achieved stay on your record and your credentials are not changed.') }}</div>
@endif

@if($e->status === 'cancelled')
  <div class="note warn" style="margin-bottom:16px">{{ __('This registration was cancelled. You were not charged.') }}</div>
@endif

@if($e->status === 'pending_payment')
  <div class="panel" style="margin-bottom:20px;border-left:4px solid #da1e28;background:linear-gradient(135deg, #fff1f1 0%, #ffffff 100%)">
    <div class="bd" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <div style="font-weight:700;font-size:16px;color:#da1e28">{{ __('Payment Pending') }} &middot; RM {{ number_format((float) $e->fee_amount, 2) }}</div>
        <div class="s muted" style="margin-top:2px">{{ __('Complete your payment to confirm your seat and access the course materials on the learning platform.') }}</div>
      </div>
      <div>
        <a class="btn primary" href="{{ route('register', $e->offering_id) }}">
          <i class="bi bi-credit-card" style="margin-right:6px"></i>{{ __('Pay now') }}
        </a>
      </div>
    </div>
  </div>
@endif

<div class="cols">
  <!-- Left Column: Micro-Credential Overview, Modules & Badges, Outcomes, Assessments, Log -->
  <div>
    {{-- Micro-Credential & Cohort Overview Card --}}
    @php
      $catUpper = strtoupper((string) ($o->category ?? 'MC'));
      $delivery = $o->delivery_mode ?: ($o->version_delivery_mode ?: null);
      $totSlt = $o->planned_slt ?: $modules->sum('slt_hours');
    @endphp
    <div class="panel" style="margin-bottom:20px">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h2 style="margin:0">{{ __('Micro-Credential Details') }}</h2>
        <div style="display:inline-flex;gap:6px;align-items:center;flex-wrap:wrap">
          <span class="tag info">{{ $catUpper }}</span>
          @if(!empty($o->proposed_mqf_level))
            <span class="tag neutral">MQF {{ $o->proposed_mqf_level }}</span>
          @endif
          @if(!empty($o->proposed_credit))
            <span class="tag neutral">{{ mcms_num($o->proposed_credit) }} {{ __('cr') }}</span>
          @endif
        </div>
      </div>
      <div class="bd" style="padding:14px 18px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px 18px">
          <div>
            <div class="s muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">{{ __('Lecturer / Coordinator') }}</div>
            <div style="font-weight:600;color:var(--color-ink, #090a0f)">{{ $o->lecturer_name ?: '—' }}</div>
            @if(!empty($o->lecturer_email))
              <div class="s muted" style="font-size:12px">{{ $o->lecturer_email }}</div>
            @endif
          </div>
          <div>
            <div class="s muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">{{ __('Offering Dates') }}</div>
            <div style="font-weight:600;color:var(--color-ink, #090a0f)">
              @if(!empty($o->start_date) && !empty($o->end_date))
                {{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}
              @elseif(!empty($o->start_date))
                {{ __('Starts') }} {{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }}
              @else
                —
              @endif
            </div>
            <div class="s muted" style="font-size:12px">{{ __('Cohort:') }} <code>{{ $o->cohort_name }}</code></div>
          </div>
          <div>
            <div class="s muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">{{ __('Faculty / PTj') }}</div>
            <div style="font-weight:600;color:var(--color-ink, #090a0f)">{{ $o->ptj_code ? $o->ptj_code.' — '.$o->ptj_name : 'UTeM' }}</div>
            @if(!empty($o->parent_course_code))
              <div class="s muted" style="font-size:12px">{{ __('Parent course:') }} <code>{{ $o->parent_course_code }}</code> {{ $o->parent_course_name }}</div>
            @endif
          </div>
          <div>
            <div class="s muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">{{ __('Delivery & Workload') }}</div>
            <div style="font-weight:600;color:var(--color-ink, #090a0f)">
              {{ $delivery ? ucfirst(str_replace('_', ' ', (string) $delivery)) : __('Online') }}@if(!empty($o->delivery_language)) &middot; {{ $o->delivery_language }}@endif
            </div>
            <div class="s muted" style="font-size:12px">
              {{ $totSlt }} {{ __('SLT hours') }} &middot; {{ __('Enrolled:') }} {{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modules Table -->
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Modules') }}</h2>
        <span class="r">{{ $e->progress_percent !== null ? __(':percent% done on the learning platform', ['percent' => $e->progress_percent]) : '' }}</span>
      </div>
      <table data-no-search data-no-paginate>
        <thead>
          <tr>
            <th>{{ __('Module') }}</th>
            <th class="num">{{ __('Hours') }}</th>
            <th>{{ __('Badge') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Date') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($modules as $m)
          <tr>
            <td>
              <strong>{{ $m->seq }}. {{ (app()->getLocale() === 'ms' && !empty($m->name_ms)) ? $m->name_ms : $m->name_en }}</strong>
            </td>
            <td class="num">{{ $m->slt_hours }}</td>
            <td>
              @if(!empty($m->badge))
                <span class="tag ok" style="font-size:11px" title="{{ $m->badge->code }}">
                  <i class="bi bi-award" style="margin-right:2px"></i>{{ $m->badge->code }}
                </span>
              @elseif(!empty($m->separately_recognisable))
                <span class="tag neutral" style="font-size:11px" title="{{ __('Separately Recognisable Module') }}">
                  <i class="bi bi-patch-check" style="margin-right:2px"></i>{{ __('Badge eligible') }}
                </span>
              @else
                <span class="muted" style="font-size:11px">—</span>
              @endif
            </td>
            <td>
              @if($m->achieved_at)
                <span class="tag ok">{{ __('Achieved') }}</span>
              @elseif($e->status === 'withdrawn')
                <span class="tag">{{ __('Stopped') }}</span>
              @else
                <span class="tag info">{{ __('In progress') }}</span>
              @endif
            </td>
            <td>{{ $m->achieved_at ? \Carbon\Carbon::parse($m->achieved_at)->format('d/m/Y') : '' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted">{{ __('No modules.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <!-- Learning Outcomes & Assessment Breakdown (Collapsible — Rule D-67) -->
    @if(!empty($outcomes) && $outcomes->isNotEmpty())
      <details class="fold" style="margin-top:16px">
        <summary>
          <b>{{ __('Learning Outcomes') }}</b>
          <span class="muted small">{{ $outcomes->count() }} {{ $outcomes->count() === 1 ? __('outcome') : __('outcomes') }}</span>
        </summary>
        <div class="bd">
          <ul style="margin:0;padding-left:18px;line-height:1.6">
            @foreach($outcomes as $lo)
              <li style="margin-bottom:4px"><strong>{{ $lo->code }}:</strong> {{ (app()->getLocale() === 'ms' && !empty($lo->text_ms)) ? $lo->text_ms : $lo->text_en }}</li>
            @endforeach
          </ul>
        </div>
      </details>
    @endif

    @if(!empty($assessments) && $assessments->isNotEmpty())
      <details class="fold">
        <summary>
          <b>{{ __('Assessment Components') }}</b>
          <span class="muted small">{{ $assessments->count() }} {{ $assessments->count() === 1 ? __('component') : __('components') }} &middot; {{ mcms_num($assessments->sum('weight_percent')) }}%</span>
        </summary>
        <div class="bd" style="padding:0">
          <table data-no-search data-no-paginate style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Component') }}</th>
                <th class="num" style="width:110px">{{ __('Weight (%)') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($assessments as $ac)
                <tr>
                  <td>{{ (app()->getLocale() === 'ms' && !empty($ac->name_ms)) ? $ac->name_ms : $ac->name_en }}</td>
                  <td class="num">{{ mcms_num($ac->weight_percent) }}%</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </details>
    @endif

    <!-- History Log -->
    <details class="fold">
      <summary>
        <b>{{ __('History') }}</b> 
        <span class="muted small">{{ count($log) }} {{ count($log) === 1 ? __('change') : __('changes') }}</span>
      </summary>
      <table>
        <thead>
          <tr>
            <th>{{ __('When') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Note') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($log as $l)
          <tr>
            <td>{{ \Carbon\Carbon::parse($l->changed_at)->format('d/m/Y H:i') }}</td>
            <td>{{ $labels[$l->to_status] ?? ucfirst($l->to_status) }}</td>
            <td>{{ $l->note }}</td>
          </tr>
        @empty
          <tr><td colspan="3" class="muted">{{ __('No changes yet.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </details>
  </div>

  <!-- Right Column: Payment & Finance, Withdrawal -->
  <div>
    <!-- Payment & Finance Panel -->
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Payment') }}</h2>
        @if($payment)
          <span class="r"><span class="tag ok">{{ __('Paid in full') }}</span></span>
        @elseif($e->status === 'pending_payment')
          <span class="r"><span class="tag warn">{{ __('Pending') }}</span></span>
        @endif
      </div>
      <div class="bd">
        <dl class="dl">
          <dt>{{ __('Fee') }}</dt>
          <dd><strong>{{ (float) $e->fee_amount > 0 ? 'RM '.number_format((float) $e->fee_amount, 2) : __('Free') }}</strong></dd>

          <dt>{{ __('Paid') }}</dt>
          <dd>
            @if($payment)
              {{ __('by card ending :last4 (reference :ref)', ['last4' => $payment->card_last4, 'ref' => $payment->provider_ref]) }}
              <div class="s muted">{{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y, H:i') }}</div>
            @elseif($e->status === 'cancelled')
              {{ __('Not charged') }}
            @else
              {{ __('Not paid yet') }}
            @endif
          </dd>

          <dt>{{ __('Receipt') }}</dt>
          <dd>
            @if($payment)
              <a href="{{ route('enrolments.receipt', $e->id) }}" class="btn ter sm" style="padding:4px 8px">
                <i class="bi bi-receipt" style="margin-right:4px"></i>{{ __('Open receipt') }}
              </a>
            @elseif($e->status === 'pending_payment')
              <a href="{{ route('register', $e->offering_id) }}" class="btn sm">
                {{ __('Pay now') }}
              </a>
            @else
              —
            @endif
          </dd>

          <dt>{{ __('Confirmation') }}</dt>
          <dd>
            <a href="{{ route('enrolments.confirmation', $e->id) }}" target="_blank">
              <i class="bi bi-file-earmark-text" style="margin-right:4px"></i>{{ __('Confirmation letter (A4)') }}
            </a>
          </dd>
        </dl>
      </div>
    </div>

    <!-- Withdrawal Panel -->
    @if($e->status === 'enrolled')
      <div class="panel" style="margin-top:16px">
        <div class="hd"><h2>{{ __('Withdrawing') }}</h2></div>
        <div class="bd col">
          @if($canWithdraw)
            <p class="small">{{ __('You can withdraw until :date. Modules you have achieved stay on your record and your credentials are not changed. Refunds are handled by CODL outside MCMS.', ['date' => $deadline->format('d/m/Y')]) }}</p>
            <form method="post" action="{{ route('enrolments.withdraw', $e->id) }}" class="col" data-confirm="{{ __('Withdraw from this cohort?') }}|{{ __('Refunds follow the withdrawal rules shown on this page.') }}" data-danger>
              @csrf
              <div class="field">
                <label for="reason">{{ __('Reason') }}</label>
                <input type="text" id="reason" name="reason" required minlength="5">
              </div>
              <div>
                <button type="submit" class="danger">{{ __('Withdraw') }}</button>
              </div>
            </form>
          @else
            <p class="small">{{ __('The withdrawal deadline (:date) has passed.', ['date' => $deadline->format('d/m/Y')]) }}</p>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
