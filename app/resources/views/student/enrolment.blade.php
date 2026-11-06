@extends('layouts.app')
@section('title', __('Registration'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('enrolments') }}">{{ __('Registrations and payments') }}</a> <span>/</span> <span>{{ $e->reference }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ $o->name_en }} {{ $o->cohort_name }}</h1>
    <div class="sub">
      {{ $e->reference }} &middot; 
      @if(!empty($o->start_date) && !empty($o->end_date))
        {{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }} &middot;
      @endif
      <x-pill :s="$labels[$e->status]" />
    </div>
  </div>
  @if($e->status === 'completed')
    <div class="act">
      <a class="btn ter sm" href="{{ route('feedback.show', $e->id) }}">{{ __('Give feedback') }}</a>
    </div>
  @endif
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
  <!-- Left Column: Modules & Badges, Terms, Log -->
  <div>
    <!-- Modules Table -->
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Modules') }}</h2>
        <span class="r">{{ $e->progress_percent !== null ? __(':percent% done on the learning platform', ['percent' => $e->progress_percent]) : '' }}</span>
      </div>
      <table>
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
              <strong>{{ $m->seq }}. {{ $m->name_en }}</strong>
              @if(!empty($m->name_ms))
                <div class="s muted">{{ $m->name_ms }}</div>
              @endif
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

    <!-- The Wording You Acknowledged -->
    <details class="fold" style="margin-top:16px">
      <summary>
        <b>{{ __('The wording you acknowledged') }}</b> 
        <span class="muted small">{{ __('kept as you saw it when you registered') }}</span>
      </summary>
      <div class="bd">
        <p style="white-space:pre-line">{{ $e->ack_text }}</p>
      </div>
    </details>

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
