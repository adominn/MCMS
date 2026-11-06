@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', 'Academic Credit Record')
@section('doc_title', 'Academic Credit Record')
@section('doc_bm', 'Rekod Kredit Akademik')
@section('doc_ref', $a->reference)
@section('doc_file', 'acr-'.$a->reference.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
@endphp

@if(!$isPrint)
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ $mine ? route('credit') : route('deposits') }}">{{ $mine ? __('Credit ledger and ABC') : __('ACR and ABC deposits') }}</a>
    <span>/</span>
    <span>{{ $a->reference }}</span>
  </div>

  <div class="title">
    <div>
      <h1>{{ $p['name']['en'] }}: Academic Credit Record</h1>
      <div class="sub">{{ $a->reference }} &middot; {{ rtrim(rtrim((string) $a->credits, '0'), '.') }} credits</div>
    </div>
    <div class="act" style="align-items:center;display:flex;gap:8px">
      <span class="tag {{ $a->credit_status === 'available' ? 'ok' : ($a->credit_status === 'provisional' ? 'info' : ($a->status === 'deposited' ? 'ok' : 'info')) }}">
        {{ $a->credit_status ? $creditStatus[$a->credit_status] : $acrStatus[$a->status] }}
      </span>
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter" target="_blank">{{ __('Print ACR (A4)') }}</a>
    </div>
  </div>

  @php
    $stage = 1;
    $consentGiven = in_array($a->status, ['consent_given', 'deposited'], true);
    $consentDeclined = $a->status === 'consent_declined';
    $isDeposited = $a->status === 'deposited';
    if ($a->status === 'verified') $stage = 2;
    elseif ($a->status === 'consent_given') $stage = 3;
    elseif ($isDeposited) {
      $stage = ($a->credit_status && $a->credit_status !== 'available') ? 4 : 3;
    }
  @endphp

  <div class="track" style="margin-bottom:24px">
    <div class="stp {{ $stage > 1 ? 'done' : 'now' }}">
      <b>{{ __('1. ACR confirmed') }}</b>
      {{ $a->confirmed_at ? \Carbon\Carbon::parse($a->confirmed_at)->format('d/m/Y') : 'Verified' }}
    </div>
    <div class="stp {{ $stage > 2 ? 'done' : ($stage === 2 ? 'now' : '') }}">
      <b>{{ __('2. Consent') }}</b>
      {{ $consentGiven ? 'Given' : ($consentDeclined ? 'Declined/Postponed' : 'Needed') }}
    </div>
    <div class="stp {{ $isDeposited ? 'done' : ($stage === 3 ? 'now' : '') }}">
      <b>{{ __('3. Deposited') }}</b>
      {{ $isDeposited ? ($a->deposit_date ? \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') : 'Deposited') : ($consentGiven ? 'In deposit queue' : 'Waiting for consent') }}
    </div>
    <div class="stp {{ $stage === 4 ? 'done' : '' }}">
      <b>{{ __('4. Transfer or redeem') }}</b>
      {{ $a->credit_status ? $creditStatus[$a->credit_status] : 'Storage only' }}
    </div>
  </div>

  <div class="cols">
    <div>
      @if($mine && in_array($a->status, ['verified', 'consent_declined']))
        <form method="post" action="{{ route('credit.consent', $a->id) }}" class="panel" id="consent">
          @csrf
          <div class="hd"><h2>{{ __('Deposit in the Academic Bank of Credit') }}</h2></div>
          <div class="bd">
            <div class="note" style="margin-bottom:16px">
              <div>
                <p style="margin:0 0 8px">{{ $consent['en'] }}</p>
                <p lang="ms" class="muted small" style="margin:0">{{ $consent['ms'] }}</p>
              </div>
            </div>
            <p class="small muted" style="margin-bottom:14px">{{ __("It is not a credit transfer. Depositing stores your credit in ABC for up to 10 years.") }}</p>
            <fieldset style="border:0;padding:0;margin:0 0 16px">
              <div style="display:flex;flex-direction:column;gap:10px">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="radio" name="decision" value="given" required>
                  <span><b>{{ __("I consent to the deposit") }}</b> &middot; <span class="muted small">{{ __("The ACR enters the deputy dean's deposit queue") }}</span></span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="radio" name="decision" value="postponed">
                  <span><b>{{ __("Postpone") }}</b> &middot; <span class="muted small">{{ __("Nothing changes; decide at any time") }}</span></span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="radio" name="decision" value="declined">
                  <span><b>{{ __("Decline") }}</b> &middot; <span class="muted small">{{ __("The ACR is kept in MCMS and not deposited") }}</span></span>
                </label>
              </div>
            </fieldset>
            <button type="submit" class="btn">{{ __('Save my choice') }}</button>
          </div>
        </form>
      @elseif($mine && $isDeposited)
        <div class="note ok" style="margin-bottom:20px">
          <div><b>{{ __("Consent recorded and credit deposited.") }}</b> {{ __("This Academic Credit Record is deposited in the Academic Bank of Credit. Consent can no longer be changed here. Contact CODL for questions.") }}</div>
        </div>
      @endif

      @if(! $mine && $a->status === 'prepared')
        <form method="post" action="{{ route('deposits.confirm', $a->id) }}" class="panel">
          @csrf
          <div class="hd"><h2>{{ __('Review & Confirmation') }}</h2></div>
          <div class="bd">
            <p class="small" style="margin-top:0">{{ __("Review only. You confirm that the generated record is right; you cannot change it.") }}</p>
            <button type="submit" class="btn">{{ __('Confirm Academic Credit Record') }}</button>
          </div>
        </form>
      @endif

      @if(! $mine && in_array($a->status, ['consent_given', 'correction']))
        <form method="post" action="{{ route('deposits.record', $a->id) }}" class="panel" id="deposit">
          @csrf
          <div class="hd"><h2>{{ __('Record the ABC deposit') }}</h2></div>
          <div class="bd">
            <p class="muted small" style="margin-top:0">A deposit is storage only and is not a credit transfer. On success the credit is Available for {{ $years ?? 10 }} years from the deposit date.</p>
            <div class="field" style="margin-bottom:14px">
              <label for="abc_reference">{{ __("ABC reference") }}</label>
              <input id="abc_reference" name="abc_reference" type="text" required style="max-width:320px">
            </div>
            <div class="field" style="margin-bottom:14px">
              <label for="deposit_date">{{ __("Deposit date") }}</label>
              <input id="deposit_date" name="deposit_date" type="date" required style="max-width:200px">
            </div>
            <div class="field" style="margin-bottom:14px">
              <label for="credits">{{ __("Credits deposited") }}</label>
              <input id="credits" name="credits" type="number" step="0.5" min="0.5" max="{{ $a->credits }}" value="{{ $a->credits }}" required style="max-width:140px">
            </div>
            <div class="field" style="margin-bottom:18px">
              <label for="deposit_status">{{ __("Status") }}</label>
              <select id="deposit_status" name="deposit_status" style="max-width:240px">
                <option value="successful">{{ __("Successful") }}</option>
                <option value="in_process">{{ __("In process") }}</option>
                <option value="correction">{{ __("Correction needed") }}</option>
              </select>
            </div>
            <button type="submit" class="btn">{{ __('Record deposit') }}</button>
          </div>
        </form>
      @endif

      <div class="panel" id="acr">
        <div class="hd"><h2>{{ __('Record details') }}</h2><span class="r"><span class="tag info">{{ $acrStatus[$a->status] }}</span></span></div>
        <div class="bd" style="padding:0">
          <table style="margin:0">
            <tbody>
              <tr><th scope="row" style="width:180px">{{ __("Participant") }}</th><td>{{ $p['participant']['name'] }} ({{ $p['participant']['matric'] }})</td></tr>
              <tr><th scope="row">{{ __("Provider") }}</th><td>{{ $p['provider'] }}</td></tr>
              <tr><th scope="row">{{ __("Course or credential") }}</th><td>{{ $p['name']['en'] }} ({{ $p['category'] }})</td></tr>
              <tr><th scope="row">{{ __("Credit value") }}</th><td><b>{{ rtrim(rtrim((string) $a->credits, '0'), '.') }} credits</b></td></tr>
              <tr><th scope="row">{{ __("MQF level") }}</th><td>{{ $a->mqf_level }}</td></tr>
              <tr><th scope="row">{{ __("Completion date") }}</th><td>{{ \Carbon\Carbon::parse($a->completion_date)->format('d/m/Y') }}</td></tr>
              <tr><th scope="row">{{ __("Assessment basis") }}</th><td>{{ $a->assessment_basis }}</td></tr>
              <tr><th scope="row">{{ __("Verification") }}</th><td>Credential {{ $p['verification']['credential'] }}; completion confirmed by {{ $p['verification']['completion_confirmed_by'] }}@if(!empty($p['verification']['qv_reference'])); Quality Verification {{ $p['verification']['qv_reference'] }}@endif @if(!empty($p['verification']['parent_course'])); parent course {{ $p['verification']['parent_course'] }}@endif</td></tr>
              <tr><th scope="row">{{ __("Status") }}</th><td id="acr-status">{{ $acrStatus[$a->status] }}@if($a->credit_status) &middot; credit {{ mb_strtolower($creditStatus[$a->credit_status]) }}@endif</td></tr>
              @if($a->abc_reference)<tr><th scope="row">{{ __("ABC reference") }}</th><td>{{ $a->abc_reference }}</td></tr>@endif
              @if($a->deposit_date)<tr><th scope="row">{{ __("Deposited on") }}</th><td>{{ \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') }}</td></tr>@endif
              @if($a->credit_expiry)<tr><th scope="row">{{ __("Credit expires") }}</th><td>{{ \Carbon\Carbon::parse($a->credit_expiry)->format('d/m/Y') }}</td></tr>@endif
            </tbody>
          </table>
        </div>
      </div>

      <details class="fold" style="margin-top:16px">
        <summary><b>{{ __("Learning outcomes") }}</b> <span class="muted small">({{ count($p['outcomes']) }} {{ __("outcomes") }})</span></summary>
        <div class="bd">
          <ul style="margin:0;padding-left:18px">
            @foreach($p['outcomes'] as $o)
              <li style="margin-bottom:6px">{{ $o }}</li>
            @endforeach
          </ul>
        </div>
      </details>

      @if($checks->count())
        <details class="fold" style="margin-top:16px">
          <summary><b>{{ __("Checks before preparation") }}</b> <span class="muted small">({{ $checks->count() }} {{ __("checks") }})</span></summary>
          <div class="bd">
            <ul id="checks" style="margin:0;padding-left:18px">
              @foreach($checks as $c)
                <li style="margin-bottom:6px"><b>{{ $c->passed ? __("Passed") : __("Failed") }}</b>: {{ \App\Modules\CreditBank\Acr::CHECKS[$c->check_key] }}. {{ $c->detail }}</li>
              @endforeach
            </ul>
          </div>
        </details>
      @endif

      @if($consents->count())
        <details class="fold" style="margin-top:16px">
          <summary><b>{{ __("Consent history") }}</b> <span class="muted small">({{ $consents->count() }} {{ __("records") }})</span></summary>
          <div class="bd" style="padding:0">
            <table id="consents" style="margin:0">
              <thead>
                <tr><th>{{ __("Date") }}</th><th>{{ __("Decision") }}</th><th>{{ __("Channel") }}</th><th>{{ __("Wording version") }}</th></tr>
              </thead>
              <tbody>
                @foreach($consents as $c)
                  <tr>
                    <td>{{ \Carbon\Carbon::parse($c->decided_at)->format('d/m/Y H:i') }}</td>
                    <td><span class="tag {{ $c->decision === 'given' ? 'ok' : 'warn' }}">{{ ['given' => __("Consent given"), 'declined' => __("Declined"), 'postponed' => __("Postponed")][$c->decision] }}</span></td>
                    <td>{{ $c->channel }}</td>
                    <td>{{ collect(json_decode($c->template_versions, true))->map(fn ($v, $l) => "$l v$v")->implode(', ') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </details>
      @endif

      @if($ledger->count())
        <details class="fold" style="margin-top:16px">
          <summary><b>{{ __("Credit ledger history") }}</b> <span class="muted small">({{ $ledger->count() }} {{ __("entries") }})</span></summary>
          <div class="bd" style="padding:0">
            <table id="credit-history" style="margin:0">
              <thead>
                <tr><th>{{ __("Date") }}</th><th>{{ __("Event") }}</th><th>{{ __("From") }}</th><th>{{ __("To") }}</th></tr>
              </thead>
              <tbody>
                @foreach($ledger as $l)
                  <tr>
                    <td>{{ \Carbon\Carbon::parse($l->occurred_at)->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($l->event) }}</td>
                    <td>{{ $l->from_status ? ($creditStatus[$l->from_status] ?? ucfirst($l->from_status)) : '—' }}</td>
                    <td><b>{{ $creditStatus[$l->to_status] ?? ucfirst($l->to_status) }}</b></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </details>
      @endif
    </div>

    <div>
      <div class="panel">
        <div class="hd"><h2>{{ __('What this means') }}</h2></div>
        <div class="bd">
          <p class="small" style="margin:0 0 12px">{{ __("An Academic Credit Record (ACR) is a separate record from your certificate and Digital Credential. Its fields are generated from verified records and cannot be edited.") }}</p>
          <p class="small" style="margin:0 0 12px">{{ __("Depositing in the Academic Bank of Credit (ABC) is storage of your record, not a credit transfer. No programme accepts credit automatically; each transfer is decided case by case by the receiving faculty.") }}</p>
          @if($a->credit_status === 'available')
            <p class="small" style="margin:0"><a class="btn sm" href="{{ route('transfer.create') }}">{{ __("Apply for credit transfer") }}</a></p>
          @endif
        </div>
      </div>

      <div class="small" style="margin-top:12px">
        <a href="{{ $mine ? route('credit') : route('deposits') }}">&larr; {{ __("Back to") }} {{ $mine ? __("credit ledger") : __("deposit queue") }}</a>
      </div>
    </div>
  </div>

@else
  {{-- Official Print / PDF Sheet --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ __("Academic Credit Record") }}</h1>
    <div style="font-size:10pt">Records the academic credit earned in one verified micro-credential.</div>
    <div class="bm" style="font-size:9pt;color:var(--text-secondary)">Merekodkan kredit akademik yang diperoleh dalam satu mikrokredensial yang disahkan.</div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <table style="margin:0">
        <tbody>
          <tr><th style="width:200px">{{ __("ACR reference") }}</th><td><b>{{ $a->reference }}</b></td></tr>
          <tr><th>{{ __('Holder') }}</th><td><b>{{ $p['participant']['name'] }}</b></td></tr>
          <tr><th>{{ __('Learner ID') }}</th><td>{{ $p['participant']['matric'] }}</td></tr>
          <tr><th>{{ __('Micro-credential') }}</th><td>{{ $p['name']['en'] }} ({{ $p['category'] }})</td></tr>
          <tr><th>{{ __('Credits') }}</th><td><b>{{ rtrim(rtrim((string) $a->credits, '0'), '.') }} credits</b></td></tr>
          <tr><th>{{ __('MQF level') }}</th><td>{{ $a->mqf_level ?? 'Not stated' }}</td></tr>
          <tr><th>{{ __('Completion date') }}</th><td>{{ \Carbon\Carbon::parse($a->completion_date)->format('d/m/Y') }}</td></tr>
          <tr><th>{{ __('Verified by') }}</th><td>Deputy Dean (Academic) on {{ $a->confirmed_at ? \Carbon\Carbon::parse($a->confirmed_at)->format('d/m/Y') : now()->format('d/m/Y') }}</td></tr>
        </tbody>
      </table>
      <div class="bm small" style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--border)">
        Kredit: {{ rtrim(rtrim((string) $a->credits, '0'), '.') }} &middot; Tahap MQF: {{ $a->mqf_level ?? 'tidak dinyatakan' }} &middot; Tarikh penamatan: {{ \Carbon\Carbon::parse($a->completion_date)->format('d/m/Y') }}
      </div>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __('Consent and ABC deposit') }}</h2></div>
    <div class="bd">
      <table style="margin:0">
        <tbody>
          <tr><th style="width:200px">{{ __("Consent status") }}</th><td>{{ $acrStatus[$a->status] }}</td></tr>
          <tr><th>{{ __('ABC reference') }}</th><td>{{ $a->abc_reference ?? '—' }}</td></tr>
          <tr><th>{{ __('Deposited') }}</th><td>{{ $a->deposit_date ? \Carbon\Carbon::parse($a->deposit_date)->format('d/m/Y') : 'Not deposited' }}</td></tr>
          <tr><th>{{ __('Valid until') }}</th><td>{{ $a->credit_expiry ? \Carbon\Carbon::parse($a->credit_expiry)->format('d/m/Y') : '—' }}</td></tr>
          <tr><th>{{ __('Credit status') }}</th><td>{{ $a->credit_status ? $creditStatus[$a->credit_status] : 'Not yet in ABC' }}</td></tr>
          <tr><th>{{ __('Record status') }}</th><td>{{ $acrStatus[$a->status] }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="note" style="margin-bottom:16px;font-size:9.5pt">
    <div>
      <b>{{ __("Deposit in ABC is storage, not a transfer.") }}</b> {{ __("Depositing keeps this credit in the Academic Bank of Credit for 10 years. It does not move credit to another institution; each credit transfer is decided case by case by the receiving faculty, and a credit can be redeemed once.") }}
    </div>
  </div>
  <div class="bm small" style="margin-bottom:20px;font-size:8.5pt">
    Deposit dalam ABC ialah penyimpanan, bukan pindahan. Setiap pindahan kredit diputuskan kes demi kes oleh fakulti penerima.
  </div>

  <div style="font-size:9pt;color:var(--text-secondary);margin-bottom:24px">
    {{ $p['category'] === 'SAMC' ? 'Credit is shown because Quality Verification was valid when the credential was issued.' : 'Credit follows the approved Unbundled Micro-Credential (UMC) and its parent course.' }}
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
