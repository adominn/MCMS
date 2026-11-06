@extends('layouts.app')
@section('title', $lang === 'ms' ? 'Semak Kredensial' : 'Verify a credential')
@section('content')
@guest @include('public._nav') @endguest

<div class="pub-sec">
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span> <span>{{ $lang === 'ms' ? 'Semak kredensial' : 'Verify a credential' }}</span>
  </div>

  <div class="title" style="margin-bottom:24px">
    <div>
      <h1>{{ $lang === 'ms' ? 'Semak Kredensial & Lencana Digital' : 'Verify Credentials & Digital Badges' }}</h1>
      <div class="sub">{{ $lang === 'ms' ? 'Sahkan ketulenan sijil micro-credential dan lencana digital UTeM secara atas talian atau melalui fail tandatangan kriptografi.' : 'Verify the authenticity of UTeM micro-credential certificates and digital badges online or via cryptographic file signature.' }}</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;margin-bottom:24px">
    <!-- Method 1: Verification by Public Code -->
    <div class="card" style="background:#ffffff;border:1px solid #e0e0e0;border-top:3px solid #1e2a6e;padding:24px;margin:0">
      <div style="font-weight:700;font-size:16px;color:#1e2a6e;margin-bottom:6px;display:flex;align-items:center;gap:8px">
        <i class="bi bi-qr-code-scan"></i>
        <span>{{ $lang === 'ms' ? 'Kaedah 1: Semak Guna Kod' : 'Method 1: Verify by Code' }}</span>
      </div>
      <p class="small muted" style="margin:0 0 16px">
        {{ $lang === 'ms' ? 'Masukkan kod 8 atau 10 aksara yang tertera pada sijil atau lencana digital anda.' : 'Enter the verification code printed directly on the certificate or digital badge.' }}
      </p>

      <form method="get" action="" id="vform" onsubmit="event.preventDefault(); var c = document.getElementById('code').value.trim(); if (c) location.href = '{{ url('/public/verify') }}/' + encodeURIComponent(c.toUpperCase());">
        <div style="margin-bottom:12px">
          <label for="code" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">
            {{ $lang === 'ms' ? 'Kod Kredensial / Lencana' : 'Credential / Badge Code' }}
          </label>
          <input type="text" id="code" name="code" autocomplete="off" placeholder="e.g. CND01-7K9MJ" style="width:100%;font-size:16px;font-family:monospace;letter-spacing:1.5px;font-weight:700;text-transform:uppercase;padding:10px 12px;border:1px solid #8d8d8d;border-radius:0;outline:none" required onfocus="this.style.borderColor='#1e2a6e'" onblur="this.style.borderColor='#8d8d8d'">
        </div>
        <button type="submit" class="btn primary" style="width:100%;padding:10px;font-size:14px;font-weight:600;background:#1e2a6e;display:flex;align-items:center;justify-content:center;gap:8px">
          <span>{{ $lang === 'ms' ? 'Semak Kredensial' : 'Verify Credential' }}</span>
          <i class="bi bi-arrow-right"></i>
        </button>
      </form>
    </div>

    <!-- Method 2: Cryptographic File Verification (.json / .jwt) -->
    <div class="card" style="background:#ffffff;border:1px solid #e0e0e0;border-top:3px solid #24a148;padding:24px;margin:0">
      <div style="font-weight:700;font-size:16px;color:#24a148;margin-bottom:6px;display:flex;align-items:center;gap:8px">
        <i class="bi bi-shield-check"></i>
        <span>{{ $lang === 'ms' ? 'Kaedah 2: Sahkan Fail Kriptografi' : 'Method 2: Cryptographic File Verification' }}</span>
      </div>
      <p class="small muted" style="margin:0 0 16px">
        {{ $lang === 'ms' ? 'Muat naik fail Digital Credential (.json) atau token (.jwt) untuk semakan tandatangan digital Ed25519.' : 'Upload the Digital Credential (.json) or token (.jwt) to verify against UTeM\'s official Ed25519 signing keys.' }}
      </p>

      <form method="post" action="{{ route('public.verify.file') }}" enctype="multipart/form-data">
        @csrf
        <div style="margin-bottom:12px">
          <label for="file" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">
            {{ $lang === 'ms' ? 'Pilih fail (.json / .jwt / .txt)' : 'Select file (.json / .jwt / .txt)' }}
          </label>
          <input type="file" id="file" name="file" accept=".json,.jwt,.txt,application/json,application/jwt" required style="font-size:13px;padding:6px;border:1px solid #8d8d8d;width:100%">
        </div>
        <button type="submit" class="btn primary" style="width:100%;padding:10px;font-size:14px;font-weight:600;background:#24a148;display:flex;align-items:center;justify-content:center;gap:8px">
          <span>{{ $lang === 'ms' ? 'Sahkan Fail Tandatangan' : 'Verify File Signature' }}</span>
          <i class="bi bi-check2-circle"></i>
        </button>
      </form>
    </div>
  </div>

  <!-- Verification Result Display -->
  @isset($file)
    <div class="panel" style="margin-bottom:24px;border-left:4px solid {{ $file['ok'] ? '#24a148' : '#da1e28' }}">
      <div class="hd" style="background:#ffffff">
        <h2 style="font-size:16px;font-weight:700;color:{{ $file['ok'] ? '#24a148' : '#da1e28' }}">
          <i class="bi bi-{{ $file['ok'] ? 'check-circle-fill' : 'exclamation-circle-fill' }}" style="margin-right:6px"></i>
          {{ $file['ok'] ? ($lang === 'ms' ? 'Pengesahan Kriptografi Berjaya' : 'Cryptographic Verification Succeeded') : ($lang === 'ms' ? 'Ralat Pengesahan Fail' : 'File Verification Failed') }}
        </h2>
        <span class="r">
          <span class="tag {{ $file['ok'] ? 'ok' : 'err' }}">{{ $file['ok'] ? __('Valid') : __('Invalid') }}</span>
        </span>
      </div>
      <div class="bd" style="padding:20px">
        @if($file['ok'])
          <div style="font-size:15px;font-weight:600;color:var(--ink);margin-bottom:8px">
            {{ __('The signature is valid') }} against issuer key <code>{{ $file['kid'] }}</code>: unchanged and issued by UTeM.
          </div>
          <div class="s muted" style="margin-bottom:16px;line-height:1.5">
            {{ __('Standard: W3C Verifiable Credentials 2.0 & Open Badges 3.0 &middot; Algorithm: Ed25519 (EdDSA)') }}
          </div>

          @if(!empty($file['payload']['vc']))
            @php($vc = $file['payload']['vc'])
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;background:var(--bg-subtle, #f4f4f4);padding:14px;border-radius:4px;margin-bottom:16px">
              <div>
                <div class="small muted">{{ __('Credential Name') }}</div>
                <div style="font-weight:700">{{ $vc['name'] ?? '-' }}</div>
              </div>
              <div>
                <div class="small muted">{{ __('Recipient') }}</div>
                <div style="font-weight:600">{{ $vc['credentialSubject']['identifier'][0]['identityHash'] ?? ($vc['credentialSubject']['identifier'][0]['identity'] ?? '-') }}</div>
              </div>
              <div>
                <div class="small muted">{{ __('Issuer') }}</div>
                <div style="font-weight:600">{{ $vc['issuer']['name'] ?? 'Universiti Teknikal Malaysia Melaka' }}</div>
              </div>
              <div>
                <div class="small muted">{{ __('Issued Date') }}</div>
                <div>{{ !empty($vc['validFrom']) ? \Carbon\Carbon::parse($vc['validFrom'])->format('d M Y') : '-' }}</div>
              </div>
            </div>
          @endif

          @if($file['known'] && $file['current'])
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;border-top:1px solid var(--rule);padding-top:14px">
              <div>
                {{ __('Credential :code is currently:', ['code' => $file['code']]) }} 
                <span class="tag ok">{{ ucfirst($file['current']['outcome']) }}</span>
              </div>
              <a href="{{ route('public.verify.show', $file['code']) }}" class="btn primary sm">
                {{ __('Open official public verification record') }} &rarr;
              </a>
            </div>
          @else
            <div class="small muted" style="border-top:1px solid var(--rule);padding-top:12px">
              {{ __('This signature is mathematically genuine, but this specific code is not in current active live records.') }}
            </div>
          @endif
        @else
          <div style="font-weight:600;color:#a2191f;margin-bottom:6px">
            {{ __('The signature does not match the issuer key: the file was changed or was not issued by UTeM.') }}
          </div>
          <div class="s muted">{{ $file['error'] }}</div>
        @endif
      </div>
    </div>
  @endisset

  <div class="panel" style="background:#ffffff">
    <div class="hd"><h2>{{ __('Public Cryptographic Trust Framework') }}</h2></div>
    <div class="bd" style="font-size:13px;line-height:1.6;color:var(--ink-muted)">
      <p style="margin:0 0 8px">
        {{ __('Universiti Teknikal Malaysia Melaka implements the Open Badges 3.0 and W3C Verifiable Credentials 2.0 specifications. Every micro-credential and digital badge issued is cryptographically signed using detached Ed25519 public-key signatures.') }}
      </p>
      <p style="margin:0">
        {{ __('Public signing keys are discoverable by verifiers and external institutions via the standard JSON Web Key Set (JWKS) endpoint at') }} 
        <a href="{{ route('jwks') }}" target="_blank" style="font-family:monospace">/.well-known/jwks.json</a>.
      </p>
    </div>
  </div>
</div>

@include('public._foot')
@endsection
