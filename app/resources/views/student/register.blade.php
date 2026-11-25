@extends('layouts.app')
@section('title', __('Register'))
@section('content')
@if($step > 1 && !($e && $e->status === 'pending_payment'))
<x-breadcrumb :crumbs="[
  'Find a micro-credential' => route('catalogue'),
  __('Step 1: eligibility') => route('register', ['offering' => $o->id, 'step' => 1]),
  __('Register: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) => null,
]" />
@else
<x-breadcrumb :crumbs="[
  'Find a micro-credential' => route('catalogue'),
  __('Register: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) => null,
]" />
@endif
<div class="title">
  <div>
    <h1>{{ __('Register') }}: {{ $o->name_en }} {{ $o->cohort_name }}</h1>
    <div class="sub">{{ $o->cohort_name }} @if(!empty($o->lecturer_name))&middot; {{ $o->lecturer_name }} @endif&middot; {{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}</div>
  </div>
</div>

<div class="track" style="margin-bottom:24px">
  @php $trackSteps = [1 => 'Eligibility and details', 2 => 'Acknowledgement', 3 => 'Payment', 4 => 'Confirmation']; @endphp
  @foreach($trackSteps as $n => $label)
    @if($n < $step && !($e && $e->status === 'pending_payment' && $n < 3))
      <a href="{{ route('register', ['offering' => $o->id, 'step' => $n]) }}" class="stp done" style="text-decoration:none;cursor:pointer">
        <b>{{ $n }}. {{ __($label) }}</b>
      </a>
    @else
      <div class="stp {{ $n === $step ? 'now' : '' }}">
        <b>{{ $n }}. {{ __($label) }}</b>
      </div>
    @endif
  @endforeach
</div>

<div class="cols">
  <div>
    @if($step === 1)
      <div class="panel">
        <div class="hd">
          <h2>{{ __('Step 1: eligibility') }}</h2>
          <span class="r"><span class="tag {{ empty($reasons) ? 'ok' : 'err' }}">{{ empty($reasons) ? __('Eligible') : __('Not eligible') }}</span></span>
        </div>
        <div class="bd">
          @if($reasons)
            <div class="note err" role="alert" style="margin-bottom:20px">
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
            <div class="note ok" style="margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <span class="tag ok" style="flex-shrink:0">{{ __('Checks pass') }}</span>
              <span>{{ __('The registration window is open, there is a place, and your group is eligible.') }}</span>
            </div>
          @endif

          {{-- Show automated check breakdown only when a check fails; otherwise keep silent behind the green Eligible confirmation --}}
          @if(!empty($reasons))
          <div style="margin-bottom:24px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
              <h3 style="font-size:14px;font-weight:600;margin:0;letter-spacing:0.2px">{{ __('Automated Pre-Registration Checks') }}</h3>
              <span class="tag err" style="white-space:nowrap !important;display:inline-flex !important">
                {{ __('Checks failed') }}
              </span>
            </div>
            <div class="table-wrap" style="border:1px solid var(--hairline);border-radius:2px;overflow:hidden">
              <table data-plain style="width:100%;border-collapse:collapse;margin:0">
                <thead>
                  <tr style="background:var(--table-header-bg);border-bottom:1px solid var(--hairline)">
                    <th style="width:110px;padding:8px 16px;font-size:12px;text-align:left">{{ __('Status') }}</th>
                    <th style="padding:8px 16px;font-size:12px;text-align:left">{{ __('Eligibility Criterion') }}</th>
                    <th style="width:220px;padding:8px 16px;font-size:12px;text-align:right">{{ __('Verification Details') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($checks ?? [] as $chk)
                  <tr style="border-bottom:1px solid var(--hairline)">
                    <td style="padding:12px 16px;vertical-align:middle;white-space:nowrap">
                      <span class="tag {{ $chk['tag'] }}" style="white-space:nowrap !important;display:inline-flex !important;font-weight:600">{{ __($chk['status']) }}</span>
                    </td>
                    <td style="padding:12px 16px;vertical-align:middle">
                      <div style="font-weight:600;font-size:13.5px">{{ __($chk['criterion']) }}</div>
                    </td>
                    <td style="padding:12px 16px;vertical-align:middle;text-align:right;white-space:nowrap">
                      <span style="font-size:12px;color:var(--muted)">{{ $chk['details'] }}</span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          @endif

          @if($o->prerequisites)
            @php $activeLang = session('lang', app()->getLocale() ?: 'en'); @endphp
            <div style="background:var(--surface);border:1px solid var(--hairline);border-left:4px solid var(--accent);border-radius:2px;padding:14px 18px;margin-bottom:24px">
              <div style="display:flex;align-items:flex-start;gap:12px">
                <div>
                  <div style="font-weight:600;font-size:13.5px;color:var(--ink);margin-bottom:4px">
                    {{ $activeLang === 'ms' ? 'Pengetahuan Sedia Ada yang Disyorkan (Nasihat)' : 'Recommended Prior Knowledge (Advisory)' }}
                  </div>
                  <div style="font-size:13px;line-height:1.5;color:var(--ink-light)">
                    @if($activeLang === 'ms')
                      Micro-credential ini mengandaikan latar belakang sedia ada dalam <strong>{{ $o->prerequisites }}</strong>. Tiada kursus prasyarat akademik formal diperlukan untuk mendaftar.
                    @else
                      This micro-credential assumes prior background in <strong>{{ $o->prerequisites }}</strong>. No formal academic prerequisite course is required to register.
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @endif

          @if(empty($reasons))
          @php $savedPhone = trim((string) (auth()->user()->phone ?? '')); @endphp
          {{-- Authenticated Learner Profile Card --}}
          <div style="margin-bottom:24px">
            <h3 style="font-size:14px;font-weight:600;margin:0 0 10px;letter-spacing:0.2px">{{ __('Participant Profile (From UTeM Identity Records)') }}</h3>
            <div style="background:var(--surface);border:1px solid var(--hairline);border-radius:2px;padding:16px">
              <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px">
                <div>
                  <div class="small muted">{{ __('Full Name') }}</div>
                  <div style="font-weight:600;font-size:14px;margin-top:2px">{{ auth()->user()->name }}</div>
                </div>
                <div>
                  <div class="small muted">{{ __('Learner / Matric ID') }}</div>
                  <div style="font-weight:600;font-size:14px;margin-top:2px">{{ auth()->user()->institution_id ?? 'ID: '.auth()->user()->id }}</div>
                </div>
                <div>
                  <div class="small muted">{{ __('Registered Email') }}</div>
                  <div style="font-weight:600;font-size:14px;margin-top:2px">{{ auth()->user()->email }}</div>
                </div>
                @if($savedPhone !== '')
                  <div>
                    <div class="small muted">{{ __('Phone Number') }}</div>
                    <div style="font-weight:600;font-size:14px;margin-top:2px">
                      {{ $savedPhone }}
                      <a href="{{ route('profile') }}" class="small" style="margin-left:6px;font-weight:500">{{ __('Edit') }}</a>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>

          <form method="post" action="{{ route('register.eligibility', $o->id) }}">
            @csrf
            @if($savedPhone !== '' && !$errors->has('phone'))
              <input type="hidden" name="phone" value="{{ $savedPhone }}">
            @else
              <div class="field" style="margin-bottom:20px;max-width:440px">
                <label for="phone" style="font-weight:600;margin-bottom:6px;display:block">
                  {{ __('Phone number') }} <span style="color:var(--err)">*</span>
                </label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $savedPhone) }}" required placeholder="+60123456789" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:2px">
                <div class="help" style="font-size:12px;color:var(--muted);margin-top:4px">
                  {{ __('Saved to your profile for cohort communications — you will not be asked again.') }}
                </div>
                @error('phone')<div class="help err" role="alert" style="color:var(--err);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
              </div>
            @endif

            @if($o->prerequisites)
              <div class="field" style="margin-bottom:20px;max-width:540px">
                <label style="display:inline-flex;align-items:flex-start;gap:10px;cursor:pointer;font-weight:600;font-size:13px">
                  <input type="checkbox" id="prerequisites_ok" name="prerequisites_ok" value="1" {{ old('prerequisites_ok') ? 'checked' : '' }}>
                  <span>{{ __('I confirm I meet the recommended prerequisites.') }} <span style="color:var(--err)">*</span></span>
                </label>
                @error('prerequisites_ok')<div class="help err" role="alert" style="color:var(--err);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
              </div>
            @endif

            <div class="row" style="gap:12px;margin-top:24px">
              <button type="submit" class="btn" style="min-width:140px">{{ __('Continue') }} &rarr;</button>
              <a class="btn ter" href="{{ route('offering', $o->id) }}">&larr; {{ __('Back to the offering') }}</a>
            </div>
          </form>
          @else
          <div class="row" style="gap:12px;margin-top:20px">
            <a class="btn ter" href="{{ route('catalogue') }}">&larr; {{ __('Back to the catalogue') }}</a>
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
            <div class="panel" style="margin-bottom:20px;border:1px solid var(--border)">
              <div class="hd" style="background:var(--surface);padding:12px 16px">
                <h3 style="font-size:15px;margin:0">{{ __('Recognition statement') }}</h3>
              </div>
              <div class="bd" style="padding:16px">
                <p style="white-space:pre-line;margin:0 0 16px;line-height:1.6">{{ $ack }}</p>

                @if(($o->category ?? '') !== 'mce')
                <div style="background:var(--surface);border:1px solid var(--hairline);border-left:3px solid var(--accent);padding:12px 16px;margin:16px 0;font-size:13px">
                  <div style="font-weight:600;margin-bottom:6px;color:var(--accent)">{{ __('Key Recognition Principles:') }}</div>
                  <ul style="margin:0;padding-left:18px;line-height:1.5">
                    <li><b>{{ __('Learning Achievement') }}</b>: {{ __('Completion of individual modules earns digital badges; only full MC completion qualifies for an Academic Credit Record (ACR).') }}</li>
                    <li><b>{{ __('ACR & ABC Deposit') }}</b>: {{ __('ACR preparation and ABC deposit are separate administrative and quality processes.') }}</li>
                    <li><b>{{ __('Credit Transfer') }}</b>: {{ __('Credit transfer is an academic evaluation decided solely by the receiving degree programme, never an automatic guarantee.') }}</li>
                  </ul>
                </div>
                @endif

                <label style="display:inline-flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:13px;margin-top:8px">
                  <input type="checkbox" id="ack_statement" name="ack_statement" value="1">
                  <span>{{ __('I have read the recognition statement.') }}</span>
                </label>
              </div>
            </div>

            @if($pdpa)
            @php $activeLang = session('lang', app()->getLocale() ?: 'en'); @endphp
            <div class="panel" style="margin-bottom:20px;border:1px solid var(--border)">
              <div class="hd" style="background:var(--surface);padding:12px 16px">
                <h3 style="font-size:15px;margin:0">{{ __('Personal data notice (PDPA)') }}</h3>
              </div>
              <div class="bd" style="padding:16px">
                <p style="margin:0 0 16px;line-height:1.6">{{ $pdpaText[$activeLang] ?? ($pdpaText['en'] ?? '') }}</p>
                <label style="display:inline-flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:13px">
                  <input type="checkbox" id="pdpa" name="pdpa" value="1">
                  <span>{{ __('I have read the personal data notice.') }}</span>
                </label>
              </div>
            </div>
            @endif

            <div class="row" style="gap:12px;margin-top:20px">
              <button type="submit" class="btn">{{ $o->fee > 0 ? __('Continue to payment') : __('Register') }}</button>
              <a class="btn ter" href="{{ route('register', ['offering' => $o->id, 'step' => 1]) }}">&larr; {{ __('Back') }}</a>
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

                <dialog id="confirm" aria-labelledby="ctitle" style="border:1px solid var(--hairline);padding:24px;max-width:480px;background:var(--card-bg);color:var(--ink)">
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

            @php
              $platformRef = trim((string) ($platform ?? ''));
              $platformIsUrl = $platformRef !== '' && filter_var($platformRef, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $platformRef);
            @endphp

            <p style="margin-bottom:20px">
              @if($platformIsUrl)
                {!! __('Open the learning platform course (:link) when the cohort starts on :start. We also sent you a confirmation.', [
                  'link' => '<a href="'.e($platformRef).'" target="_blank" rel="noopener noreferrer">'.e($platformRef).'</a>',
                  'start' => \Carbon\Carbon::parse($o->start_date)->format('d/m/Y'),
                ]) !!}
              @else
                {{ __('Open the learning platform course:platform when the cohort starts on :start. We also sent you a confirmation.', ['platform' => $platformRef !== '' ? ': '.$platformRef : '', 'start' => \Carbon\Carbon::parse($o->start_date)->format('d/m/Y')]) }}
              @endif
            </p>

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
                    <div class="grow">
                      {{ __('Open the course named in your confirmation') }}
                      <span class="s" style="display:block;color:var(--muted);font-size:12px">
                        {{ $o->name_en }} {{ $o->cohort_name }}.
                        @if($platformIsUrl)
                          {{ __('Course link:') }} <a href="{{ $platformRef }}" target="_blank" rel="noopener noreferrer">{{ $platformRef }}</a>.
                        @elseif($platformRef !== '')
                          {{ __('Course reference:') }} <code>{{ $platformRef }}</code>.
                        @endif
                        {{ __('Modules open in order.') }}
                      </span>
                    </div>
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
    <div class="panel" style="border:1px solid var(--border)">
      <div class="hd" style="background:var(--surface);padding:12px 16px">
        <h2 style="font-size:16px;margin:0">{{ __('Your registration') }}</h2>
      </div>
      <div class="bd" style="padding:16px">
        <dl class="dl" style="margin:0">
          <dt style="font-size:12px;color:var(--muted)">{{ __('Micro-credential') }}</dt>
          <dd style="font-size:14px;font-weight:600;margin-bottom:10px">{{ $o->name_en }}</dd>

          <dt style="font-size:12px;color:var(--muted)">{{ __('Category') }}</dt>
          <dd style="margin-bottom:10px">
            <span class="tag info">{{ $o->category === 'umc' ? 'Unbundled (UMC)' : ($o->category === 'samc' ? 'Standalone (SAMC)' : 'Executive (MCE)') }}</span>
          </dd>

          @if(!empty($o->parent_course_code))
            <dt style="font-size:12px;color:var(--muted)">{{ __('Parent Course') }}</dt>
            <dd style="margin-bottom:10px;font-size:13px">{{ $o->parent_course_code }} &middot; {{ $o->parent_course_name }}</dd>
          @endif

          <dt style="font-size:12px;color:var(--muted)">{{ __('Academic Credit & MQF') }}</dt>
          <dd style="margin-bottom:10px;font-size:13px">
            @if($o->category === 'mce')
              <span class="tag warn">{{ __('No academic credit') }}</span>
            @elseif($o->category === 'samc' && $o->qv_status !== 'verified')
              <span class="tag info">{{ __('Credit upon Quality Verification (QV)') }}</span>
            @else
              <b>{{ $o->proposed_credit ? mcms_num($o->proposed_credit) . ' ' . __('credits') : __('Credit upon QV') }}</b>
              @if(!empty($o->proposed_mqf_level)) &middot; {{ __('MQF Level :level', ['level' => $o->proposed_mqf_level]) }}@endif
            @endif
          </dd>

          @if(!empty($o->planned_slt))
            <dt style="font-size:12px;color:var(--muted)">{{ __('Notional SLT') }}</dt>
            <dd style="margin-bottom:10px;font-size:13px">{{ $o->planned_slt }} {{ __('hours') }}</dd>
          @endif

          @if(!empty($o->delivery_mode) || !empty($o->version_delivery_mode))
            <dt style="font-size:12px;color:var(--muted)">{{ __('Delivery Mode') }}</dt>
            <dd style="margin-bottom:10px;font-size:13px;text-transform:capitalize">{{ __($o->delivery_mode ?? $o->version_delivery_mode) }}</dd>
          @endif

          @if(!empty($o->lecturer_name))
            <dt style="font-size:12px;color:var(--muted)">{{ __('Lecturer / Facilitator') }}</dt>
            <dd style="margin-bottom:10px;font-size:13px">{{ $o->lecturer_name }}</dd>
          @endif

          <dt style="font-size:12px;color:var(--muted)">{{ __('Cohort') }}</dt>
          <dd style="margin-bottom:10px;font-size:13px">{{ $o->cohort_name }}</dd>

          <dt style="font-size:12px;color:var(--muted)">{{ __('Duration') }}</dt>
          <dd style="margin-bottom:10px;font-size:13px">{{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }} {{ __('to') }} {{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}</dd>

          <dt style="font-size:12px;color:var(--muted)">{{ __('Fee') }}</dt>
          <dd style="font-size:15px;margin-bottom:0"><b>{{ $o->fee > 0 ? 'RM '.number_format($o->fee, 2) : __('Free') }}</b></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection