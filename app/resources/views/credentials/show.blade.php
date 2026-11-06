@extends('layouts.app')
@section('title', __('Credential'))
@section('content')
@php
  $label = ['valid' => 'Issued', 'corrected' => 'Corrected', 'replaced' => 'Replaced', 'revoked' => 'Revoked'][$c->status] ?? $c->status;
  $sees = ['valid' => 'Valid', 'corrected' => 'Corrected, valid', 'replaced' => 'Replaced', 'revoked' => 'Revoked'][$c->status] ?? $c->status;
  $live = in_array($c->status, ['valid', 'corrected'], true);
  $issued = \Carbon\Carbon::parse($content['issued'])->format('d/m/Y');
  $notes = ['issued' => 'Issued after completion was confirmed', 'corrected' => 'Earlier revision stays viewable and marked', 'replaced' => 'Replaced by a newer credential', 'revoked' => 'Revoked'];
  $modules = $modules ?? collect();
@endphp
<div class="crumb"><a href="{{ route('credentials.wallet') }}">{{ __('Credentials') }}</a><span>/</span><span>{{ $c->number }}</span></div>
<div class="title">
  <div style="display:flex;align-items:center;gap:16px">
    <x-badge :category="$content['category'] ?? 'samc'" :size="64" />
    <div>
      <h1>{{ $content['name']['en'] }}</h1>
      <div class="sub">
        {{ $c->number }} &middot; {{ __('issued') }} {{ $issued }} &middot; {{ strtoupper($content['category'] ?? 'UMC') }}
        @if(!empty($enrolment->matric_no))
          &middot; {{ __('Matric:') }} {{ $enrolment->matric_no }}
        @endif
      </div>
    </div>
  </div>
  <div class="act" style="align-items:center">
    <span class="tag {{ $live ? 'ok' : 'err' }}">{{ __($label) }}</span>
    @if($live)
      <a class="btn" href="{{ route('credentials.certificate', $c->id) }}">{{ __('Download certificate (PDF)') }}</a>
      <a class="btn ter" href="{{ route('credentials.sheet', $c->id) }}">{{ __('Printable sheet') }}</a>
    @endif
  </div>
</div>

@if($c->status === 'replaced')
  <div class="note warn" role="alert" style="margin-bottom:20px">
    {{ __('This credential was replaced') }}{{ $c->status_reason ? ': '.$c->status_reason : '' }}. {{ __('Employers who check it see "Replaced".') }}
    @if($newer)
      <a href="{{ route('credentials.show', $newer->id) }}">{{ __('Open the new credential') }} {{ $newer->number }}</a>
    @endif
  </div>
@endif

@if($c->status === 'revoked')
  <div class="note err" role="alert" style="margin-bottom:20px">
    {{ __('This credential was revoked') }}: {{ $c->status_reason }}. {{ __('Employers who check it see "Revoked".') }}
  </div>
@endif

@if($c->status === 'corrected')
  <div class="note" style="margin-bottom:20px">
    {{ __('This credential was corrected. The earlier revision stays viewable and marked in the history below.') }}
  </div>
@endif

<div class="cols" style="grid-template-columns:minmax(0,1fr) 360px">
  <div>
    <!-- Sharing & Verification -->
    @if($live)
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Share & Verify Credential') }}</h2>
      </div>
      <div class="bd" style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap">
        <div style="flex:1;min-width:260px">
          <label for="share" style="margin-top:0;font-weight:600">{{ __('Public Verification Link') }}</label>
          <div style="display:flex;gap:8px;margin-bottom:8px">
            <input type="text" id="share" readonly value="{{ $url }}" aria-label="Share link" style="font-family:var(--mono);font-size:12px;background:#f4f4f4">
            <button type="button" id="copy-btn" class="btn sm" onclick="navigator.clipboard.writeText(document.getElementById('share').value).then(() => { this.innerHTML = '&#10003; Copied'; setTimeout(() => this.textContent = '{{ __('Copy link') }}', 2500); })" style="white-space:nowrap">{{ __('Copy link') }}</button>
          </div>
          <p class="small muted" style="margin:4px 0 16px;line-height:1.4">
            {{ __('Anyone with this link or verification code can verify that your credential was authentically issued by UTeM. No login or account required.') }}
          </p>

          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
            <a class="btn primary sm" href="{{ $url }}" target="_blank" rel="noopener">
              <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><path d="M6 3h7v7M13 3L7 9M3 6v7h7"/></svg>
              {{ __('Open what an employer sees') }}
            </a>
            @if(isset($c->public_code))
              <button type="button" class="btn secondary sm" id="share-native-btn" onclick="if(navigator.share){ navigator.share({ title: '{{ $content['name']['en'] ?? 'UTeM Credential' }}', text: 'Verify my official UTeM Micro-Credential: {{ $content['name']['en'] ?? '' }}', url: '{{ $url }}' }); } else { navigator.clipboard.writeText('{{ $url }}'); alert('Verification link copied to clipboard!'); }">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><circle cx="12" cy="4" r="2"/><circle cx="4" cy="8" r="2"/><circle cx="12" cy="12" r="2"/><path d="M5.8 7l4.4-2M5.8 9l4.4 2"/></svg>
                {{ __('Share...') }}
              </button>
            @endif
          </div>

          <div style="padding-top:12px;border-top:1px solid var(--rule, #e0e0e0);font-size:12px;color:var(--ink-muted, #525252)">
            {{ __('Official Verification Code') }}: <code style="font-size:13px;font-weight:700;color:var(--primary, #0f62fe)">{{ $c->public_code }}</code>
            <span style="margin:0 8px">&middot;</span>
            {{ __('Certificate No') }}: <span style="font-family:var(--mono)">{{ $c->number }}</span>
          </div>
        </div>

        <!-- QR Code Block with Direct Downloads -->
        <div style="flex:none;text-align:center;background:#ffffff;padding:16px;border:1px solid var(--rule, #e0e0e0);border-radius:2px">
          <div id="qr-container" aria-label="QR code that opens the verification page" role="img" style="display:inline-block">
            {!! $qr !!}
          </div>
          <div style="font-size:11px;color:var(--ink-muted, #6f6f6f);margin:8px 0">
            {{ __('Scan with camera or phone to verify') }}
          </div>
          <div style="display:flex;gap:6px;justify-content:center">
            <button type="button" class="btn ter sm" style="font-size:11px;padding:3px 8px" onclick="downloadQrAsPng('{{ $c->public_code }}')">
              <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:2px"><path d="M2 10v4h12v-4M8 2v9M4 7l4 4 4-4"/></svg>
              {{ __('PNG') }}
            </button>
            <button type="button" class="btn ter sm" style="font-size:11px;padding:3px 8px" onclick="downloadQrAsSvg('{{ $c->public_code }}')">
              <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:2px"><path d="M2 10v4h12v-4M8 2v9M4 7l4 4 4-4"/></svg>
              {{ __('SVG') }}
            </button>
          </div>
        </div>
      </div>
    </div>
    @else
      <div class="note warn" style="margin-bottom:20px">{{ __('Sharing is switched off: the verification link now shows') }} "{{ __($sees) }}".</div>
    @endif

    <!-- Cryptographic Downloads & Document Files -->
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Cryptographic Credential Files & Downloads') }}</h2>
      </div>
      <div class="bd" style="padding:16px 20px">
        <p class="small muted" style="margin:0 0 16px;line-height:1.5">
          {{ __('You can download your issued credential in standard cryptographic formats for job applications, university credit transfers, and digital backpacks.') }}
        </p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <!-- Option 1: Signed Token (.jwt) -->
          <div style="border:1px solid var(--rule, #e0e0e0);padding:14px;background:#fafafa">
            <div style="margin-bottom:6px">
              <strong style="font-size:13px">{{ __('Signed token (.jwt)') }}</strong>
            </div>
            <p style="font-size:12px;color:var(--ink-muted, #525252);margin:0 0 12px;line-height:1.4">
              {{ __('Compact cryptographic token signed with UTeM\'s private Ed25519 key. Used for automated machine-to-machine verification, API integrations, and digital identity wallets.') }}
            </p>
            <a class="btn secondary sm" href="{{ route('credentials.download', [$c->id, 'jwt']) }}" style="width:100%;justify-content:center">
              <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><path d="M2 10v4h12v-4M8 2v9M4 7l4 4 4-4"/></svg>
              {{ __('Download .jwt Token') }}
            </a>
          </div>

          <!-- Option 2: Verifiable Credential (.json) -->
          <div style="border:1px solid var(--rule, #e0e0e0);padding:14px;background:#fafafa">
            <div style="margin-bottom:6px">
              <strong style="font-size:13px">{{ __('Verifiable Credential (.json)') }}</strong>
            </div>
            <p style="font-size:12px;color:var(--ink-muted, #525252);margin:0 0 12px;line-height:1.4">
              {{ __('Human-readable JSON document adhering to the W3C Verifiable Credentials Data Model with embedded cryptographic proof. Best for HR archives and offline auditing.') }}
            </p>
            <a class="btn secondary sm" href="{{ route('credentials.download', [$c->id, 'json']) }}" style="width:100%;justify-content:center">
              <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><path d="M2 10v4h12v-4M8 2v9M4 7l4 4 4-4"/></svg>
              {{ __('Download .json File') }}
            </a>
          </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--rule, #e0e0e0);font-size:12px">
          <span class="muted">{{ __('Print & Alternate Versions:') }}</span>
          <a class="btn ter sm" href="{{ route('credentials.certificate', $c->id) }}" target="_blank">{{ __('Print Certificate (English)') }}</a>
          <a class="btn ter sm" href="{{ route('credentials.certificate', [$c->id, 'lang' => 'ms']) }}" target="_blank">{{ __('Sijil (Bahasa Melayu)') }}</a>
          <a class="btn ter sm" href="{{ route('credentials.sheet', $c->id) }}" target="_blank">{{ __('Credential Sheet') }}</a>
        </div>
      </div>
    </div>

    <!-- Course Modules & Earned Badges Hierarchy -->
    @if($modules->isNotEmpty())
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Completed Modules & Earned Badges') }}</h2>
      </div>
      <div class="bd" style="padding:0">
        <div style="padding:12px 16px;background:var(--bg-subtle, #f4f4f4);border-bottom:1px solid var(--rule);font-size:12px;color:var(--ink-muted);line-height:1.4">
          <i class="bi bi-info-circle" style="margin-right:4px"></i>
          {{ __('This micro-credential consists of the modules below. Digital badges are awarded for each module upon achieving assessment mastery, contributing to this full certificate.') }}
        </div>
        <table>
          <thead>
            <tr>
              <th>{{ __('Module') }}</th>
              <th class="num">{{ __('Hours') }}</th>
              <th>{{ __('Earned Badge') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Achieved Date') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($modules as $m)
              <tr>
                <td>
                  <strong>{{ $m->seq }}. {{ $m->name_en }}</strong>
                  @if(!empty($m->name_ms))
                    <div class="s muted">{{ $m->name_ms }}</div>
                  @endif
                </td>
                <td class="num">{{ $m->slt_hours }}</td>
                <td>
                  @if(!empty($m->badge_id))
                    <div style="display:flex;align-items:center;gap:8px">
                      <x-badge :category="$content['category'] ?? 'samc'" :size="28" />
                        <div>
                          <a href="{{ route('verify.show', $m->badge_code) }}" target="_blank" style="font-weight:600;font-size:12px" title="{{ __('Verify this badge on public verification portal') }}">
                            {{ $m->badge_code }} <i class="bi bi-box-arrow-up-right" style="font-size:10px"></i>
                          </a>
                          @if(!empty($m->badge_external_id))
                            <div class="s muted">UCreds {{ $m->badge_external_id }}</div>
                          @endif
                        </div>
                    </div>
                  @elseif(!empty($m->separately_recognisable))
                    <span class="tag neutral" style="font-size:11px">{{ __('Badge eligible') }}</span>
                  @else
                    <span class="muted" style="font-size:11px">{{ __('Course unit') }}</span>
                  @endif
                </td>
                <td>
                  @if($m->is_achieved)
                    <span class="tag ok">{{ __('Achieved') }}</span>
                  @else
                    <span class="tag neutral">{{ __('Incomplete') }}</span>
                  @endif
                </td>
                <td class="s">
                  {{ $m->achieved_at ? \Carbon\Carbon::parse($m->achieved_at)->format('d/m/Y') : '-' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    <!-- Revision History -->
    <div class="panel">
      <div class="hd"><h2>{{ __('Revision history') }}</h2></div>
      <table data-plain>
        <thead><tr><th>{{ __('Revision') }}</th><th>{{ __('Event') }}</th><th>{{ __('Date') }}</th><th>{{ __('Note') }}</th></tr></thead>
        <tbody>
          @foreach($revisions as $r)
            <tr>
              <td>{{ __('Revision') }} {{ $r->revision }}</td>
              <td><span class="tag {{ in_array($r->event, ['issued', 'corrected'], true) ? 'ok' : 'err' }}">{{ __(ucfirst($r->event)) }}</span></td>
              <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</td>
              <td>{{ $r->reason ?: __($notes[$r->event] ?? '') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- What the credential says -->
    <details class="fold">
      <summary>{{ __('What the credential says') }}</summary>
      <div>
        <p>{{ $content['statement']['en'] }}</p>
        @if(!empty($content['stages']))<p>{{ $content['stages']['en'] }}</p>@endif
        <p class="muted small" style="margin-bottom:8px">{{ __('Learning time') }} {{ $content['slt_hours'] }} h &middot; {{ __('signed by') }} {{ $content['signatory'] }}.</p>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <span class="small muted">{{ __('Cryptographic formats & verification') }}:</span>
          <a class="btn ter sm" href="{{ route('credentials.certificate', [$c->id, 'lang' => 'ms']) }}">{{ __('Sijil (Bahasa Melayu)') }}</a>
          <a class="btn ter sm" href="{{ route('credentials.download', [$c->id, 'jwt']) }}" title="{{ __('Signed Ed25519 token') }}">{{ __('Signed token (.jwt)') }}</a>
          <a class="btn ter sm" href="{{ route('credentials.download', [$c->id, 'json']) }}" title="{{ __('W3C Verifiable Credential document with cryptographic proof') }}">{{ __('Verifiable Credential (.json)') }}</a>
          <a class="btn ter sm" href="{{ route('verify') }}" target="_blank"><i class="bi bi-shield-check" style="margin-right:4px"></i>{{ __('Open Verifier') }}</a>
        </div>
      </div>
    </details>
  </div>

  <!-- Right Column -->
  <div>
    <div class="panel">
      <div class="hd"><h2>{{ __('What an employer sees') }}</h2></div>
      <div class="bd">
        @foreach([['Your name', $content['holder']], ['The credential', $content['name']['en']], ['Issuer', $content['issuer']], ['Date issued', $issued], ['Status', $sees]] as [$k, $v])
          <div class="item" style="padding:10px 0"><span class="dot" style="background:{{ $live ? 'var(--success)' : 'var(--error)' }}"></span><div class="grow">{{ __($k) }}<span class="s">{{ __($v) }}</span></div></div>
        @endforeach
        <p class="small muted" style="margin:12px 0 0">{{ __('This link shows an employer only your name, the credential, the issuer, the date and its status. Marks, credit and ABC details are not shown.') }}</p>
      </div>
    </div>

    @if(!empty($content['claims']['credit']))
      <div class="panel">
        <div class="hd"><h2>{{ __('Recognition claim') }}</h2></div>
        <div class="bd">
          <p style="margin:0">
            {{ __('Credit') }} {{ $content['claims']['credit'] }}@if(!empty($content['claims']['mqf'])), {{ __('MQF level') }} {{ $content['claims']['mqf'] }}@endif @if(!empty($content['claims']['qv_until'])) ({{ __('Quality Verification valid until') }} {{ $content['claims']['qv_until'] }})@endif, {{ __('as at issue.') }}
          </p>
        </div>
      </div>
    @endif

    <div class="panel">
      <div class="hd"><h2>{{ __('Credit') }}</h2></div>
      <div class="bd">
        <p class="small" style="margin:0">{{ __('Academic credit, if any, is a separate record. See the') }} <a href="{{ route('credit') }}">{{ __('credit ledger') }}</a>.</p>
      </div>
    </div>
  </div>
</div>

<script>
function downloadQrAsPng(code) {
  var svgEl = document.querySelector('#qr-container svg');
  if (!svgEl) return;
  var svgData = new XMLSerializer().serializeToString(svgEl);
  var svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  var url = URL.createObjectURL(svgBlob);
  var img = new Image();
  img.onload = function() {
    var canvas = document.createElement('canvas');
    var scale = 3; // 3x scale for crisp print & display
    canvas.width = (img.width || 200) * scale;
    canvas.height = (img.height || 200) * scale;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    URL.revokeObjectURL(url);
    var a = document.createElement('a');
    a.download = 'UTEM-QR-' + code + '.png';
    a.href = canvas.toDataURL('image/png');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  };
  img.src = url;
}

function downloadQrAsSvg(code) {
  var svgEl = document.querySelector('#qr-container svg');
  if (!svgEl) return;
  var svgData = new XMLSerializer().serializeToString(svgEl);
  var svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  var a = document.createElement('a');
  a.download = 'UTEM-QR-' + code + '.svg';
  a.href = URL.createObjectURL(svgBlob);
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(a.href);
}
</script>
@endsection
