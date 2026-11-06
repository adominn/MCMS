@extends('layouts.app')
@section('title', __('Registrations and payments'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Registrations and payments') }}</h1>
    <div class="sub">{{ __('Your registrations, what you owe and your receipts.') }}</div>
  </div>
  <div class="act">
    <a class="btn" href="{{ route('catalogue') }}">{{ __('Find a micro-credential') }}</a>
  </div>
</div>

<!-- Financial & Registration KPI Summary Banner -->
<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Total Registrations') }}</div>
    <div class="v">{{ $summary['total_registrations'] ?? $rows->count() }}</div>
    <div class="s muted">{{ ($summary['active_count'] ?? 0).' '.__('active / in study') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Total Paid') }}</div>
    <div class="v" style="color:var(--ink)">RM {{ number_format($summary['total_paid'] ?? 0, 2) }}</div>
    <div class="s muted">{{ count($payments->where('status', 'succeeded')) }} {{ __('transactions') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Outstanding Balance') }}</div>
    <div class="v" style="color:{{ ($summary['total_outstanding'] ?? 0) > 0 ? '#da1e28' : '#24a148' }}">
      RM {{ number_format($summary['total_outstanding'] ?? 0, 2) }}
    </div>
    <div class="s">
      @if(($summary['total_outstanding'] ?? 0) > 0)
        <span class="tag warn" style="font-size:11px">{{ __('Action required') }}</span>
      @else
        <span class="tag ok" style="font-size:11px">{{ __('Fully settled') }}</span>
      @endif
    </div>
  </div>
</div>

<!-- Carbon Tabs for Clean Separation of Registration vs Payment Information -->
<div class="tabs" style="margin-top:20px;border-bottom:1px solid var(--rule);display:flex;gap:24px" role="tablist">
  <button type="button" class="tab-btn active" data-tab="tab-registrations" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid var(--accent, #1e2a6e);color:var(--accent, #1e2a6e);cursor:pointer">
    {{ __('Registrations') }} ({{ $rows->count() }})
  </button>
  <button type="button" class="tab-btn" data-tab="tab-payments" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
    {{ __('Payment History & Receipts') }} ({{ $payments->count() }})
  </button>
</div>

<!-- Tab 1: Registrations List -->
<div id="tab-registrations" class="tab-pane" style="margin-top:16px">
  <div class="panel">
    <table>
      <thead>
        <tr>
          <th>{{ __('Registration') }}</th>
          <th>{{ __('Micro-credential') }}</th>
          <th>{{ __('Study Period') }}</th>
          <th>{{ __('Fee & Payment') }}</th>
          <th>{{ __('Status') }}</th>
          <th class="num">{{ __('Actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        <tr>
          <td>
            <a href="{{ route('enrolments.show', $r->id) }}" style="font-weight:600">{{ $r->reference }}</a>
            @if(!empty($r->registered_at))
              <div class="s muted">{{ \Carbon\Carbon::parse($r->registered_at)->format('d M Y') }}</div>
            @endif
          </td>
          <td>
            <div style="font-weight:600">{{ $r->name_en }} {{ $r->cohort_name }}</div>
            <div class="s muted">
              @if(!empty($r->mc_code)) {{ $r->mc_code }} &middot; @endif
              @if(!empty($r->module_count)) {{ $r->module_count }} {{ __('modules') }} &middot; @endif
              @if(!empty($r->proposed_credit)) {{ $r->proposed_credit }} {{ __('credits') }} @else {{ __('Non-credit') }} @endif
            </div>
          </td>
          <td class="s">
            @if(!empty($r->start_date) && !empty($r->end_date))
              <div>{{ \Carbon\Carbon::parse($r->start_date)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($r->end_date)->format('d/m/Y') }}</div>
              <div class="muted">{{ ucfirst(str_replace('_', ' ', $r->delivery_mode ?? 'online')) }}</div>
            @else
              <span class="muted">{{ __('Self-paced') }}</span>
            @endif
          </td>
          <td>
            <div><strong>{{ (float) $r->fee_amount > 0 ? 'RM '.number_format((float) $r->fee_amount, 2) : __('Free') }}</strong></div>
            <div class="s">
              @if((float) $r->fee_amount <= 0)
                <span class="muted">{{ __('No charge') }}</span>
              @elseif($r->status === 'pending_payment')
                <span style="color:#da1e28;font-weight:600">{{ __('Due: RM :amount', ['amount' => number_format((float) $r->fee_amount, 2)]) }}</span>
              @elseif(!empty($r->payment))
                <span style="color:#24a148">{{ __('Paid (Card *' . $r->payment->card_last4 . ')') }}</span>
              @elseif(in_array($r->status, ['enrolled', 'completed']))
                <span style="color:#24a148">{{ __('Paid in full') }}</span>
              @else
                <span class="muted">{{ __('Unpaid') }}</span>
              @endif
            </div>
          </td>
          <td>
            <x-pill :s="$labels[$r->status]" />
          </td>
          <td class="num">
            <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
              @if($r->status === 'pending_payment')
                <a class="btn sm" href="{{ route('register', $r->offering_id) }}">{{ __('Pay now') }}</a>
              @elseif((float) $r->fee_amount > 0 && in_array($r->status, ['enrolled', 'completed', 'withdrawn']))
                <a class="btn ter sm" href="{{ route('enrolments.receipt', $r->id) }}">{{ __('Receipt') }}</a>
              @endif
              @if($r->status === 'completed')
                <a class="btn ter sm" href="{{ route('feedback.show', $r->id) }}">{{ __('Feedback') }}</a>
              @endif
              <a class="btn ter sm" href="{{ route('enrolments.show', $r->id) }}">{{ __('Details') }}</a>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted">{{ __('You have no registrations.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Tab 2: Payments & Receipts Ledger -->
<div id="tab-payments" class="tab-pane" style="margin-top:16px;display:none">
  <div class="panel">
    <div class="hd">
      <h2>{{ __('Official Payment Records & Receipts') }}</h2>
      <span class="r">{{ count($payments) }} {{ __('records') }}</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>{{ __('Transaction Ref') }}</th>
          <th>{{ __('Micro-credential & Cohort') }}</th>
          <th>{{ __('Registration Ref') }}</th>
          <th class="num">{{ __('Amount') }}</th>
          <th>{{ __('Payment Method') }}</th>
          <th>{{ __('Date & Time') }}</th>
          <th>{{ __('Status') }}</th>
          <th class="num">{{ __('Receipt') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($payments as $p)
        <tr>
          <td>
            <strong>{{ $p->reference }}</strong>
            @if(!empty($p->provider_ref))
              <div class="s muted">{{ __('Gateway:') }} {{ $p->provider_ref }}</div>
            @endif
          </td>
          <td>
            <div style="font-weight:600">{{ $p->course_name }}</div>
            <div class="s muted">{{ $p->cohort_name }}</div>
          </td>
          <td>
            <a href="{{ route('enrolments.show', $p->enrolment_id) }}">{{ $p->enrolment_ref }}</a>
          </td>
          <td class="num">
            <strong>RM {{ number_format((float) $p->amount, 2) }}</strong>
          </td>
          <td>
            @if(!empty($p->card_last4))
              <span><i class="bi bi-credit-card" style="margin-right:4px"></i>{{ __('Card ending') }} <strong>{{ $p->card_last4 }}</strong></span>
            @else
              <span class="muted">{{ __('Online Payment') }}</span>
            @endif
          </td>
          <td class="s">
            {{ \Carbon\Carbon::parse($p->created_at)->format('d M Y, H:i') }}
          </td>
          <td>
            @if($p->status === 'succeeded')
              <span class="tag ok">{{ __('Succeeded') }}</span>
            @else
              <span class="tag warn">{{ __('Failed') }}</span>
              @if(!empty($p->failure_reason))
                <div class="s muted">{{ $p->failure_reason }}</div>
              @endif
            @endif
          </td>
          <td class="num">
            @if($p->status === 'succeeded')
              <a class="btn ter sm" href="{{ route('enrolments.receipt', $p->enrolment_id) }}">
                <i class="bi bi-receipt" style="margin-right:4px"></i>{{ __('Receipt') }}
              </a>
            @else
              <span class="muted">—</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="muted">{{ __('No payment transactions found on your account.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.querySelectorAll('.tab-btn');
  tabs.forEach(btn => {
    btn.addEventListener('click', function() {
      tabs.forEach(t => {
        t.classList.remove('active');
        t.style.borderBottomColor = 'transparent';
        t.style.color = 'var(--ink-muted)';
      });
      document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
      
      this.classList.add('active');
      this.style.borderBottomColor = 'var(--accent, #1e2a6e)';
      this.style.color = 'var(--accent, #1e2a6e)';
      const target = document.getElementById(this.getAttribute('data-tab'));
      if (target) target.style.display = 'block';
    });
  });
});
</script>
@endsection
