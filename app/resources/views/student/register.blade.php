@extends('layouts.app')
@section('title', __('Register'))
@section('content')
<div class="crumb"><a href="{{ route('catalogue') }}">{{ __('Find a micro-credential') }}</a> <span>/</span> <span>{{ __('Register') }}: {{ $o->name_en }} {{ $o->cohort_name }}</span></div>
<div class="title">
  <div>
    <h1>{{ __('Register') }}: {{ $o->name_en }} {{ $o->cohort_name }}</h1>
    <div class="sub">{{ $o->cohort_name }} &middot; {{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}</div>
  </div>
</div>

<div class="track" style="margin-bottom:24px">
  @php($trackSteps = [1 => 'Eligibility and details', 2 => 'Acknowledgement', 3 => 'Payment', 4 => 'Confirmation'])
  @foreach($trackSteps as $n => $label)
    <div class="stp {{ $n < $step ? 'done' : ($n === $step ? 'now' : '') }}">
      <b>{{ $n }}. {{ __($label) }}</b>
    </div>
  @endforeach
</div>

<div class="cols">
  <div>
    @if($step === 1)
      <div class="panel">
        <div class="hd"><h2>{{ __('Step 1: eligibility') }}</h2><span class="r"><span class="tag {{ empty($reasons) ? 'ok' : 'err' }}">{{ empty($reasons) ? __('Eligible') : __('Not eligible') }}</span></span></div>
        <div class="bd">
          @if($reasons)
            <div class="note err" role="alert" style="margin-bottom:16px">
              <div>
                <b>{{ __('You cannot register:') }}</b>
                <ul style="margin:6px 0 0;padding-left:18px">
                  @foreach($reasons as $r)
                    <li>{{ $r }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          @else
            <div class="note ok" style="margin-bottom:16px">{{ __('The checks pass: the registration window is open, there is a place, and your group is eligible.') }}</div>
          @endif

          <div class="checks" style="margin-bottom:16px">
            <div class="check">
              <span class="dot ok"></span>
              <div class="grow">{{ __('Active UTeM learner account') }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ auth()->user()->matric_no ?? 'ID: '.auth()->user()->id }}, {{ auth()->user()->name }}</span></div>
            </div>
            <div class="check">
              <span class="dot {{ !in_array('Micro-credential is not approved', $reasons ?? []) ? 'ok' : 'err' }}"></span>
              <div class="grow">{{ __('Micro-credential is approved') }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ $o->name_en }}</span></div>
            </div>
            <div class="check">
              <span class="dot {{ empty($reasons) ? 'ok' : 'err' }}"></span>
              <div class="grow">{{ __('Registration is open and a place is free') }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('Registration closes :date', ['date' => \Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y')]) }}</span></div>
            </div>
            @if($o->prerequisites)
            <div class="check">
              <span class="dot warn"></span>
              <div class="grow">{{ __('Prerequisite: :prereq', ['prereq' => $o->prerequisites]) }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('Confirm you meet this requirement below') }}</span></div>
            </div>
            @endif
          </div>

          @if(empty($reasons))
          <form method="post" action="{{ route('register.eligibility', $o->id) }}">
            @csrf
            <div class="field" style="margin-bottom:16px">
              <label for="name">{{ __('Name') }}</label>
              <input type="text" id="name" readonly value="{{ auth()->user()->name }}" class="read" style="max-width:400px">
            </div>
            <div class="field" style="margin-bottom:16px">
              <label for="email">{{ __('Email') }}</label>
              <input type="email" id="email" readonly value="{{ auth()->user()->email }}" class="read" style="max-width:400px">
              <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('From your UTeM account.') }}</div>
            </div>
            <div class="field" style="margin-bottom:16px">
              <label for="phone">{{ __('Confirm your phone number') }}</label>
              <input type="text" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required style="max-width:300px">
              @error('phone')<div class="help err" role="alert" style="color:var(--err);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            @if($o->prerequisites)
              <div style="margin-bottom:20px">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="checkbox" id="prerequisites_ok" name="prerequisites_ok" value="1" {{ old('prerequisites_ok') ? 'checked' : '' }}>
                  <span>{{ __('I meet the prerequisites: :prereq', ['prereq' => $o->prerequisites]) }}</span>
                </label>
                @error('prerequisites_ok')<div class="help err" role="alert" style="color:var(--err);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
              </div>
            @endif
            <div class="row" style="gap:12px;margin-top:20px">
              <button type="submit" class="btn">{{ __('Continue') }}</button>
              <a class="btn ter" href="{{ route('offering', $o->id) }}">{{ __('Back to the offering') }}</a>
            </div>
          </form>
          @else
          <div class="row" style="gap:12px;margin-top:20px">
            <a class="btn ter" href="{{ route('catalogue') }}">{{ __('Back to the catalogue') }}</a>
          </div>
          @endif
        </div>
      </div>
    @elseif($step === 2)
      <form method="post" action="{{ route('register.acknowledge', $o->id) }}" novalidate>
        @csrf
        <div class="panel">
          <div class="hd"><h2>{{ __('Step 2: what this does and does not give you') }}</h2></div>
          <div class="bd">
            <div class="panel" style="margin-bottom:20px">
              <div class="hd"><h2>{{ __('Recognition statement') }}</h2></div>
              <div class="bd">
                <p style="white-space:pre-line;margin:0 0 16px">{{ $ack }}</p>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="checkbox" id="ack_statement" name="ack_statement" value="1">
                  <span>{{ __('I have read the recognition statement.') }}</span>
                </label>
              </div>
            </div>

            @if($pdpa)
            <div class="panel" style="margin-bottom:20px">
              <div class="hd"><h2>{{ __('Personal data notice (PDPA)') }}</h2></div>
              <div class="bd">
                <p style="margin:0 0 12px">{{ $pdpaText['en'] }}</p>
                <p lang="ms" class="muted" style="margin:0 0 16px">{{ $pdpaText['ms'] }}</p>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="checkbox" id="pdpa" name="pdpa" value="1">
                  <span>{{ __('I have read the personal data notice.') }}</span>
                </label>
              </div>
            </div>
            @endif

            <div class="row" style="gap:12px;margin-top:20px">
              <button type="submit" class="btn">{{ $o->fee > 0 ? __('Continue to payment') : __('Register') }}</button>
              <a class="btn ter" href="{{ route('register', $o->id) }}">{{ __('Back') }}</a>
            </div>
          </div>
        </div>
      </form>
    @elseif($step === 3)
      <div class="panel" id="payment-block">
        <div class="hd"><h2>{{ __('Step 3: payment') }}</h2></div>
        <div class="bd">
          <p style="margin-top:0">{{ __('Registration :ref is pending payment of RM :amount. Pay within :hours hours or the place is released.', ['ref' => $e->reference, 'amount' => number_format($e->fee_amount, 2), 'hours' => app(\App\Modules\Core\Settings::class)->get('enrolment.payment_window_hours')]) }}</p>

          @if($errors->has('payment'))
            <div class="note err" role="alert" style="margin-bottom:16px">
              <div>{{ $errors->first('payment') }}</div>
            </div>
          @endif

          <div class="note" style="margin-bottom:20px">
            <div>{{ __('Test mode: this is a dummy payment service. Any card number works except 4000 0000 0000 0002, which is declined so you can test error recovery.') }}</div>
          </div>

          <div class="panel" style="margin-bottom:20px">
            <div class="hd"><h2>{{ __('Payment method') }}</h2></div>
            <div class="bd">
              <div class="row" style="gap:24px;margin-bottom:16px">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="radio" name="pay_method" value="card" checked onchange="document.getElementById('card-fields').style.display='block';document.getElementById('fpx-fields').style.display='none'">
                  <span><b>{{ __('Card') }}</b> ({{ __('Dummy card form') }})</span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="radio" name="pay_method" value="fpx" onchange="document.getElementById('card-fields').style.display='none';document.getElementById('fpx-fields').style.display='block'">
                  <span><b>{{ __('FPX online banking') }}</b> ({{ __('Simulated') }})</span>
                </label>
              </div>

              <form id="payform" method="post" action="{{ route('register.pay', $o->id) }}">
                @csrf
                <input type="hidden" name="attempt" value="{{ $token }}">

                <div id="card-fields">
                  <div class="field" style="margin-bottom:16px">
                    <label for="card">{{ __('Card number') }}</label>
                    <input type="text" id="card" name="card" inputmode="numeric" autocomplete="off" value="4242 4242 4242 4242" style="max-width:320px">
                  </div>
                  <div class="row" style="gap:16px;margin-bottom:16px">
                    <div class="field" style="max-width:140px">
                      <label for="exp">{{ __('Expiry') }}</label>
                      <input type="text" id="exp" value="12/28" placeholder="MM/YY">
                    </div>
                    <div class="field" style="max-width:100px">
                      <label for="cvv">{{ __('CVV') }}</label>
                      <input type="text" id="cvv" value="123" placeholder="123">
                    </div>
                  </div>
                </div>

                <div id="fpx-fields" style="display:none;margin-bottom:16px">
                  <div class="field" style="max-width:320px">
                    <label for="bank">{{ __('Bank') }}</label>
                    <select id="bank">
                      <option>Maybank2u</option>
                      <option>CIMB Clicks</option>
                      <option>Bank Islam</option>
                      <option>RHB Now</option>
                    </select>
                    <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">{{ __('In this prototype the bank step is simulated.') }}</div>
                  </div>
                </div>

                <div class="row" style="gap:12px;margin-top:20px">
                  <button type="button" id="paybtn" class="btn">{{ $payment && $payment->status === 'failed' ? __('Retry payment') : __('Pay') }}</button>
                  <a class="btn ter" href="{{ route('register', $o->id) }}">{{ __('Back') }}</a>
                </div>

                <dialog id="confirm" aria-labelledby="ctitle" style="border:1px solid var(--ink);padding:24px;max-width:480px;background:#fff">
                  <div class="small muted" style="margin-bottom:6px">{{ __('Registration') }} &middot; {{ $o->cohort_name }}</div>
                  <h2 id="ctitle" style="margin:0 0 12px;font-size:20px">{{ __('Pay RM :amount?', ['amount' => number_format($e->fee_amount, 2)]) }}</h2>
                  <p style="margin:0 0 8px">{{ __('Your card will be charged once.') }}</p>
                  <p class="muted small" style="margin:0 0 20px">{{ __('If you click twice, you are charged once: the payment carries one reference per registration.') }}</p>
                  <div class="row" style="gap:10px">
                    <button type="submit" id="paynow" class="btn">{{ __('Pay now') }}</button>
                    <button type="button" id="paycancel" class="btn ter">{{ __('Cancel') }}</button>
                  </div>
                </dialog>
              </form>
            </div>
          </div>
        </div>
      </div>
      <script>
        (function () {
          var d = document.getElementById('confirm');
          var paybtn = document.getElementById('paybtn');
          var paycancel = document.getElementById('paycancel');
          var payform = document.getElementById('payform');
          var paynow = document.getElementById('paynow');
          if (paybtn && d) paybtn.addEventListener('click', function () { d.showModal(); });
          if (paycancel && d) paycancel.addEventListener('click', function () { d.close(); });
          if (payform && paynow) payform.addEventListener('submit', function () { paynow.disabled = true; });
        })();
      </script>
    @else
      <div class="panel">
        <div class="hd"><h2>{{ __('Step 4: confirmation') }}</h2><span class="r"><span class="tag ok">{{ __('Enrolled') }}</span></span></div>
        <div class="bd">
          @if($e->status === 'enrolled' || $e->status === 'completed')
            <div class="note ok" style="margin-bottom:20px">
              <div>
                <b>{{ __('You are enrolled.') }}</b> {{ __('Enrolment ID') }} <b>{{ $e->reference }}</b>{{ $payment && $payment->status === 'succeeded' ? ', '.__('paid RM :amount', ['amount' => number_format($payment->amount, 2)]) : '' }}. {{ __('A confirmation was sent to :email.', ['email' => auth()->user()->email]) }}
              </div>
            </div>

            <p style="margin-bottom:20px">{{ __('Open the learning platform course:platform when the cohort starts on :start. We also sent you a confirmation.', ['platform' => $platform ? ': '.$platform : '', 'start' => \Carbon\Carbon::parse($o->start_date)->format('d/m/Y')]) }}</p>

            <div class="panel" style="margin-bottom:20px">
              <div class="hd"><h2>{{ __('How to start') }}</h2></div>
              <div class="bd">
                <div class="checks">
                  <div class="check">
                    <span class="dot ok"></span>
                    <div class="grow">{{ __('Course space opens on UCreds before :date', ['date' => \Carbon\Carbon::parse($o->start_date)->format('d/m/Y')]) }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('You get a notification when it is ready.') }}</span></div>
                  </div>
                  <div class="check">
                    <span class="dot ok"></span>
                    <div class="grow">{{ __('Sign in to UCreds with your UTeM account') }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('Use the same account you use here. There is no separate password.') }}</span></div>
                  </div>
                  <div class="check">
                    <span class="dot ok"></span>
                    <div class="grow">{{ __('Open the course named in your confirmation') }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ $o->name_en }} {{ $o->cohort_name }}. {{ __('Modules open in order.') }}</span></div>
                  </div>
                  <div class="check">
                    <span class="dot ok"></span>
                    <div class="grow">{{ __('Come back here to follow progress') }}<span class="s" style="display:block;color:var(--muted);font-size:12px">{{ __('Results and credentials appear here after the lecturer submits and the deputy dean verifies them.') }}</span></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row" style="gap:12px;flex-wrap:wrap">
              <a class="btn" href="{{ route('tasks') }}">{{ __('Go to my learning') }}</a>
              <a class="btn ter" href="{{ route('enrolments.show', $e->id) }}">{{ __('View this registration') }}</a>
              <a class="btn ter" href="{{ route('enrolments') }}">{{ __('Registrations and payments') }}</a>
              @if($e->fee_amount > 0)
                <a class="btn ter" href="{{ route('enrolments.receipt', $e->id) }}">{{ __('Receipt') }}</a>
              @endif
            </div>
          @else
            <p>{{ __('Your registration :ref is :status.', ['ref' => $e->reference, 'status' => strtolower(config('enrolment.status')[$e->status] ?? $e->status)]) }}</p>
            <div class="row" style="gap:12px">
              <a class="btn ter" href="{{ route('enrolments') }}">{{ __('Registrations and payments') }}</a>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>

  <div>
    <div class="panel">
      <div class="hd"><h2>{{ __('Your registration') }}</h2></div>
      <div class="bd">
        <dl class="dl">
          <dt>{{ __('Micro-credential') }}</dt>
          <dd><b>{{ $o->name_en }}</b></dd>
          <dt>{{ __('Cohort') }}</dt>
          <dd>{{ $o->cohort_name }}</dd>
          <dt>{{ __('Dates') }}</dt>
          <dd>{{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}</dd>
          <dt>{{ __('Credit') }}</dt>
          <dd>{{ $o->category === 'MCE' ? __('No credit. Certificate only') : ($o->category === 'UMC' ? __('Credit bearing') : __('Credit after Quality Verification')) }}</dd>
          <dt>{{ __('Fee') }}</dt>
          <dd><b>{{ $o->fee > 0 ? 'RM '.number_format($o->fee, 2) : __('Free') }}</b></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection
