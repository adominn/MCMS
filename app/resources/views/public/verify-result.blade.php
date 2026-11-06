@extends('layouts.app')
@section('title', 'Verification Result')
@section('content')
@guest @include('public._nav') @endguest
@php
  $o = $r['outcome'];
  $p = $r['public'] ?? [];
  $d = $r['details'] ?? [];
  $cred = $r['credential'] ?? null;
  $statusLabels = [
    'valid' => 'Valid',
    'corrected' => 'Valid, corrected',
    'replaced' => 'Replaced',
    'revoked' => 'Revoked',
    'expired' => 'Expired',
    'invalid_signature' => 'Invalid Signature',
  ];
  $statusColor = match($o) {
    'valid' => 'var(--success, #24a148)',
    'corrected' => 'var(--primary, #1e2a6e)',
    'replaced', 'expired' => 'var(--warn, #f1c21b)',
    'revoked', 'invalid_signature' => 'var(--error, #da1e28)',
    default => 'var(--primary, #1e2a6e)',
  };
  $statusBg = match($o) {
    'valid' => 'var(--ok-bg, #defbe6)',
    'corrected' => 'var(--info-bg, #e8eaf6)',
    'replaced', 'expired' => 'var(--warn-bg, #fcf4d6)',
    'revoked', 'invalid_signature' => 'var(--err-bg, #fff1f1)',
    default => 'var(--info-bg, #e8eaf6)',
  };
  $statusInk = match($o) {
    'valid' => 'var(--ok-ink, #0e6027)',
    'corrected' => 'var(--primary, #1e2a6e)',
    'replaced', 'expired' => 'var(--warn-ink, #684e00)',
    'revoked', 'invalid_signature' => 'var(--err-ink, #a2191f)',
    default => 'var(--primary, #1e2a6e)',
  };
@endphp

<style>
  .vr-wrap {
    max-width: 860px;
    margin: 0 auto;
    padding: 24px 20px 48px;
  }
  .vr-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
  }
  .vr-header h1 {
    margin: 0 0 6px;
    font-size: 26px;
    font-weight: 600;
    color: var(--ink);
    letter-spacing: -0.3px;
  }
  .vr-header .sub {
    font-size: 14px;
    color: var(--muted);
    line-height: 1.4;
  }
  .vr-card {
    background: #ffffff;
    border: 1px solid var(--hairline);
    border-top: 4px solid {{ $statusColor }};
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  }
  .vr-status-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px 24px;
    background: {{ $statusBg }};
    border-bottom: 1px solid var(--hairline);
    color: {{ $statusInk }};
  }
  .vr-status-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .vr-status-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .vr-status-title {
    font-size: 17px;
    font-weight: 600;
    line-height: 1.2;
  }
  .vr-status-desc {
    font-size: 13px;
    margin-top: 2px;
    opacity: 0.9;
  }
  .vr-code-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1px solid var(--hairline);
    padding: 6px 12px;
    font-family: var(--mono);
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
  }
  .vr-copy-btn {
    background: none;
    border: 0;
    padding: 2px 4px;
    cursor: pointer;
    color: var(--primary);
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: auto;
  }
  .vr-copy-btn:hover {
    background: var(--surface);
    text-decoration: underline;
  }
  .vr-body {
    padding: 24px;
  }
  .vr-credential-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--ink);
    margin: 0 0 10px;
    line-height: 1.3;
  }
  .vr-meta-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 24px;
  }
  .vr-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    background: var(--surface);
    color: var(--ink);
    border: 1px solid var(--hairline);
  }
  .vr-pill.primary {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
  }
  .vr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px 24px;
    padding-top: 16px;
    border-top: 1px solid var(--hairline);
  }
  .vr-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .vr-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
  }
  .vr-val {
    font-size: 15px;
    color: var(--ink);
    line-height: 1.4;
  }
  .vr-val.bold {
    font-weight: 600;
  }
  .vr-crypto-footer {
    padding: 16px 24px;
    background: var(--surface);
    border-top: 1px solid var(--hairline);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
  }
  .vr-crypto-meta {
    font-size: 12px;
    color: var(--muted);
    line-height: 1.4;
  }
  .vr-crypto-meta b {
    color: var(--ink);
  }
  .vr-downloads {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .vr-privacy-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    background: #ffffff;
    border: 1px solid var(--hairline);
    font-size: 12px;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 24px;
  }
  .vr-search-box {
    background: #ffffff;
    border: 1px solid var(--hairline);
    padding: 20px 24px;
  }
  .vr-search-form {
    display: flex;
    gap: 10px;
    max-width: 480px;
    margin-top: 12px;
  }
  .vr-search-form input {
    flex: 1;
    font-family: var(--mono);
    text-transform: uppercase;
  }
  @media print {
    .crumb, .vr-header .btn, .vr-downloads, .vr-search-box, .vr-privacy-box, header.top, nav.side, footer {
      display: none !important;
    }
    .vr-wrap {
      max-width: 100% !important;
      padding: 0 !important;
    }
    .vr-card {
      border: 1px solid #161616 !important;
      box-shadow: none !important;
    }
  }
</style>

<div class="vr-wrap">
  <div class="crumb" style="margin-bottom:12px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span>
    <a href="{{ route('public.verify') }}">{{ $lang === 'ms' ? 'Semak kredensial' : 'Verify a credential' }}</a> <span>/</span>
    <span>{{ $r['code'] ?? 'Result' }}</span>
  </div>

  <div class="vr-header">
    <div>
      <h1>{{ $lang === 'ms' ? 'Keputusan semakan' : 'Verification result' }}</h1>
      <div class="sub">{{ $lang === 'ms' ? 'Hasil pengesahan integriti dan status pendaftaran sijil di UTeM' : 'Integrity and registration verification result from UTeM' }}</div>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="window.print()" class="btn sec sm" style="font-size:12px">
        <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><path d="M4 5V1h8v4M4 12v3h8v-3M2 5h12a1 1 0 011 1v5a1 1 0 01-1 1h-2v-2H4v2H2a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
        {{ $lang === 'ms' ? 'Cetak slip pengesahan' : 'Print verification record' }}
      </button>
    </div>
  </div>

  @if($o === 'not_found')
    <div class="vr-card" style="border-top-color:var(--error, #da1e28)">
      <div class="vr-status-banner" style="background:var(--err-bg, #fff1f1);color:var(--err-ink, #a2191f)">
        <div class="vr-status-left">
          <div class="vr-status-icon">
            <svg viewBox="0 0 16 16" width="24" height="24" fill="currentColor">
              <path d="M8 1a7 7 0 100 14A7 7 0 008 1zm0 2.5a.75.75 0 01.75.75v4a.75.75 0 01-1.5 0v-4A.75.75 0 018 3.5zm0 8a.9.9 0 110-1.8.9.9 0 010 1.8z"/>
            </svg>
          </div>
          <div>
            <div class="vr-status-title"><b>{{ $lang === 'ms' ? 'Kredensial tidak ditemui.' : 'We could not find this credential.' }}</b></div>
            <div class="vr-status-desc">{{ $lang === 'ms' ? 'Semak kod dengan teliti: huruf O dan I tidak digunakan dalam kod rasmi. Sekiranya kod tepat, sila hubungi Pejabat CODL UTeM. Ini tidak bermakna kredensial ini palsu.' : 'Check the code carefully: the letters O and I are not used in official codes. If the code is right, ask the UTeM CODL office. This does not mean the credential is false.' }}</div>
          </div>
        </div>
      </div>
      <div class="vr-body">
        <h3 style="margin:0 0 8px;font-size:15px">{{ $lang === 'ms' ? 'Cuba masukkan semula kod kredensial' : 'Re-enter credential or digital badge code' }}</h3>
        <p class="muted" style="font-size:13px;margin:0 0 16px">
          {{ $lang === 'ms' ? 'Format kod kredensial: CND01-7K9MJ atau nombor lencana BADGE-CND-M1-01.' : 'Official codes typically look like CND01-7K9MJ or badge identifiers like BADGE-CND-M1-01.' }}
        </p>
        <form method="get" action="" class="vr-search-form" onsubmit="event.preventDefault(); var c = document.getElementById('code_retry').value.trim(); if (c) location.href = '{{ url('/public/verify') }}/' + encodeURIComponent(c.toUpperCase());">
          <input type="text" id="code_retry" placeholder="e.g. CND01-7K9MJ" required>
          <button type="submit" class="btn sm">{{ $lang === 'ms' ? 'Semak' : 'Verify' }}</button>
          <a href="{{ route('public.verify') }}" class="btn sec sm">{{ $lang === 'ms' ? 'Kembali' : 'Back to search' }}</a>
        </form>
      </div>
    </div>
  @else
    <!-- Official Verification Record Card -->
    <div class="vr-card">
      <!-- Status Banner -->
      <div class="vr-status-banner">
        <div class="vr-status-left">
          <div class="vr-status-icon">
            @if(in_array($o, ['valid', 'corrected']) || ($r['type'] ?? '') === 'badge')
              <svg viewBox="0 0 16 16" width="24" height="24" fill="currentColor">
                <path d="M8 0a8 8 0 100 16A8 8 0 008 0zm3.78 6.28l-4.5 4.5a.75.75 0 01-1.06 0l-2-2a.75.75 0 011.06-1.06L6.75 9.19l3.97-3.97a.75.75 0 011.06 1.06z"/>
              </svg>
            @elseif($o === 'replaced' || $o === 'expired')
              <svg viewBox="0 0 16 16" width="24" height="24" fill="currentColor">
                <path d="M8 1a7 7 0 100 14A7 7 0 008 1zm0 2.5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0V4.25A.75.75 0 018 3.5zm0 8a.9.9 0 110-1.8.9.9 0 010 1.8z"/>
              </svg>
            @else
              <svg viewBox="0 0 16 16" width="24" height="24" fill="currentColor">
                <path d="M8 1a7 7 0 100 14A7 7 0 008 1zm2.78 4.22a.75.75 0 010 1.06L8.81 8l1.97 1.97a.75.75 0 11-1.06 1.06L7.75 9.06 5.78 11.03a.75.75 0 01-1.06-1.06L6.69 8 4.72 6.03a.75.75 0 011.06-1.06L7.75 6.94l1.97-1.97a.75.75 0 011.06 0z"/>
              </svg>
            @endif
          </div>
          <div>
            <div class="vr-status-title">
              Status: <b id="vstatus">{{ ($r['type'] ?? '') === 'badge' ? __('Valid (Digital Badge)') : ($statusLabels[$o] ?? ucfirst($o)) }}</b>
            </div>
            <div class="vr-status-desc">
              @if(($r['type'] ?? '') === 'badge')
                {{ $lang === 'ms' ? 'Lencana digital modul ini telah dikeluarkan secara rasmi oleh UTeM setelah pencapaian penilaian modul disahkan.' : 'This module digital badge was officially issued by UTeM upon verified assessment completion.' }}
              @elseif($o === 'revoked')
                {{ $lang === 'ms' ? 'Kredensial ini telah dibatalkan pada ' . ($p['revoked_on'] ?? 'rekod') . '. Kredensial ini tidak boleh diguna pakai.' : 'This credential was revoked on ' . ($p['revoked_on'] ?? 'record') . '. This credential must not be relied on.' }}
              @elseif($o === 'replaced')
                {{ $lang === 'ms' ? 'Kredensial ini telah digantikan dengan yang lebih baharu. Sila sahkan kod baharu:' : 'This credential was replaced by a newer one. Verify the new code instead:' }}
                <a href="{{ route('public.verify.show', $p['replaced_by']) }}" style="text-decoration:underline"><b>{{ $p['replaced_by'] }}</b></a>.
              @elseif($o === 'corrected')
                {{ $lang === 'ms' ? 'Sah. Telah dibetulkan selepas dikeluarkan (' . ($p['corrected_on'] ?? '') . '); butiran di bawah adalah semakan terkini.' : 'Valid. Corrected after issue (' . ($p['corrected_on'] ?? '') . '); the details below are the current revision.' }}
              @elseif($o === 'expired')
                {{ $lang === 'ms' ? 'Tempoh sah kredensial ini telah berakhir.' : 'This credential validity period has ended.' }}
              @else
                {{ $lang === 'ms' ? 'Sah. Kredensial ini dikeluarkan oleh UTeM dan tidak diubah atau dibatalkan.' : 'Valid. This credential was issued by UTeM and has not been changed or revoked.' }}
              @endif
            </div>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:8px">
          <div class="vr-code-pill">
            <span style="font-size:11px;color:var(--muted);text-transform:uppercase;font-family:var(--sans)">{{ ($r['type'] ?? '') === 'badge' ? __('Badge Code') : __('Code') }}:</span>
            <span id="ver-code-text">{{ $r['code'] ?? ($p['badge_code'] ?? '-') }}</span>
            <button type="button" class="vr-copy-btn" onclick="navigator.clipboard.writeText('{{ $r['code'] ?? ($p['badge_code'] ?? '') }}'); this.textContent='{{ __('Copied!') }}'; setTimeout(() => this.textContent='{{ __('Copy') }}', 2000)">
              {{ __('Copy') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Card Body -->
      <div class="vr-body">
        @if(($r['type'] ?? '') === 'badge')
          <!-- Module Digital Badge Body -->
          <div class="vr-credential-title">
            {{ $lang === 'ms' ? ($p['module_name_ms'] ?? $p['credential']) : ($p['module_name_en'] ?? $p['credential']) }}
          </div>

          <div class="vr-meta-pills">
            <span class="vr-pill primary">{{ __('Module Digital Badge') }}</span>
            <span class="vr-pill">Open Badges 3.0</span>
            @if(!empty($p['slt_hours']))
              <span class="vr-pill">{{ $p['slt_hours'] }} {{ __('hours SLT') }}</span>
            @endif
          </div>

          <div class="vr-grid">
            @isset($p['recipient'])
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Nama Penerima' : 'Recipient Name' }}</span>
                <span class="vr-val bold">{{ $p['recipient'] }}</span>
              </div>
            @endisset

            <div class="vr-field">
              <span class="vr-label">{{ $lang === 'ms' ? 'Kursus Kredensial Induk' : 'Parent Micro-Credential Course' }}</span>
              <span class="vr-val">{{ $lang === 'ms' ? ($p['course_name_ms'] ?? $p['course_name_en']) : $p['course_name_en'] }}</span>
            </div>

            @if(!empty($p['faculty']))
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Fakulti (PTj)' : 'Faculty (PTj)' }}</span>
                <span class="vr-val">{{ $p['faculty'] }}</span>
              </div>
            @endif

            @isset($p['issuer'])
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Institusi Pengeluar' : 'Issuing Institution' }}</span>
                <span class="vr-val">{{ $p['issuer'] }}</span>
              </div>
            @endisset

            @isset($p['issued'])
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Tarikh Pengeluaran' : 'Date Issued' }}</span>
                <span class="vr-val">{{ \Carbon\Carbon::parse($p['issued'])->format('d/m/Y') }}</span>
              </div>
            @endisset

            @if(!empty($p['external_url']))
              <div class="vr-field">
                <span class="vr-label">{{ __('External Backpack') }}</span>
                <div style="margin-top:2px">
                  <a href="{{ $p['external_url'] }}" target="_blank" rel="noopener" class="btn sec sm" style="font-size:12px;padding:3px 10px;height:28px">
                    {{ __('View on UCreds / Open Badges') }} &rarr;
                  </a>
                </div>
              </div>
            @endif
          </div>

        @elseif($o !== 'revoked')
          <!-- Full Micro-Credential Body -->
          <div class="vr-credential-title">
            {{ is_array($p['credential'] ?? null) ? ($lang === 'ms' ? $p['credential']['ms'] : $p['credential']['en']) : ($p['credential'] ?? ($d['course']['en'] ?? 'Micro-Credential')) }}
          </div>

          <div class="vr-meta-pills">
            @if(!empty($d['category']))
              <span class="vr-pill primary">{{ $d['category'] === 'umc' || $d['category'] === 'UMC' ? __('Unbundled Micro-Credential (UMC)') : __('Stand-Alone Micro-Credential (SAMC)') }}</span>
            @endif
            @if(!empty($d['credits']))
              <span class="vr-pill"><b>{{ $d['credits'] }}</b> {{ __('graduating credits') }}</span>
            @endif
            @if(!empty($d['slt_hours']))
              <span class="vr-pill"><b>{{ $d['slt_hours'] }}h</b> {{ __('Student Learning Time (SLT)') }}</span>
            @endif
            @if(!empty($d['mqf_level']))
              <span class="vr-pill">{{ __('MQF Level') }} <b>{{ $d['mqf_level'] }}</b></span>
            @endif
          </div>

          <div class="vr-grid">
            @isset($p['recipient'])
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Nama Penerima' : 'Recipient Name' }}</span>
                <span class="vr-val bold" style="font-size:16px">{{ $p['recipient'] }}</span>
              </div>
            @endisset

            @isset($p['issuer'])
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Institusi Pengeluar' : 'Issuing Institution' }}</span>
                <span class="vr-val">{{ $p['issuer'] }}</span>
              </div>
            @endisset

            @if(!empty($d['course']) && isset($p['credential']) && (is_array($p['credential']) ? $p['credential']['en'] : $p['credential']) !== ($d['course']['en'] ?? ''))
              <div class="vr-field">
                <span class="vr-label">{{ __('Parent Course') }}</span>
                <span class="vr-val">{{ is_array($d['course']) ? ($lang === 'ms' ? ($d['course']['ms'] ?? $d['course']['en']) : $d['course']['en']) : $d['course'] }}</span>
              </div>
            @endif

            @if(!empty($d['faculty']))
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Fakulti / PTj' : 'Faculty / Academic PTj' }}</span>
                <span class="vr-val">{{ $d['faculty'] }}</span>
              </div>
            @endif

            @isset($p['issued'])
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Tarikh Pengeluaran' : 'Date Awarded' }}</span>
                <span class="vr-val">{{ \Carbon\Carbon::parse($p['issued'])->format('d/m/Y') }}</span>
              </div>
            @endisset

            @if(!empty($d['signatory']))
              <div class="vr-field">
                <span class="vr-label">{{ __('Authorized Signatory') }}</span>
                <span class="vr-val">{{ $d['signatory'] }}</span>
              </div>
            @endif

            @if($o === 'corrected' && isset($p['corrected_on']))
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Dibetulkan pada' : 'Correction Date' }}</span>
                <span class="vr-val">{{ $p['corrected_on'] }}</span>
              </div>
            @endif

            @if($o === 'replaced' && isset($p['replaced_by']))
              <div class="vr-field">
                <span class="vr-label">{{ $lang === 'ms' ? 'Digantikan oleh' : 'Replaced By' }}</span>
                <span class="vr-val"><a href="{{ route('public.verify.show', $p['replaced_by']) }}">{{ $p['replaced_by'] }}</a></span>
              </div>
            @endif
          </div>

          <!-- Earned Module Digital Badges (if any) -->
          @if(!empty($r['module_badges']) && count($r['module_badges']) > 0)
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--hairline)">
              <div class="vr-label" style="margin-bottom:12px">{{ __('Earned Component Modules & Badges') }}</div>
              <div style="display:flex;flex-direction:column;gap:8px">
                @foreach($r['module_badges'] as $mb)
                  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 12px;background:var(--surface);border:1px solid var(--hairline);font-size:13px">
                    <div>
                      <b>Module {{ $mb->seq }}:</b> {{ $lang === 'ms' ? ($mb->name_ms ?? $mb->name_en) : $mb->name_en }}
                      @if(!empty($mb->slt_hours))
                        <span class="muted">&middot; {{ $mb->slt_hours }}h SLT</span>
                      @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                      <code style="font-size:11px">{{ $mb->badge_code }}</code>
                      <a href="{{ route('public.verify.show', $mb->badge_code) }}" class="btn sec sm" style="font-size:11px;padding:2px 6px;height:24px">
                        {{ __('Verify') }} &rarr;
                      </a>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

        @else
          <!-- Revoked State Notice -->
          <div style="padding:16px;background:var(--err-bg, #fff1f1);border:1px solid var(--error, #da1e28);color:var(--err-ink, #a2191f);font-size:14px;line-height:1.5">
            <b>{{ __('Credential Withdrawn') }}</b>
            <p style="margin:4px 0 0">
              {{ __('The details of revoked credentials are withdrawn from the public registry per academic governance policy. If you have questions regarding this credential, contact the UTeM Centre for Open and Distance Learning (CODL).') }}
            </p>
          </div>
        @endif
      </div>

      <!-- Cryptographic Trust & Downloads Footer -->
      <div class="vr-crypto-footer">
        <div class="vr-crypto-meta">
          <div style="display:flex;align-items:center;gap:6px">
            <svg viewBox="0 0 16 16" width="14" height="14" fill="var(--success, #24a148)"><path d="M8 0a8 8 0 100 16A8 8 0 008 0zm3.78 6.28l-4.5 4.5a.75.75 0 01-1.06 0l-2-2a.75.75 0 011.06-1.06L6.75 9.19l3.97-3.97a.75.75 0 011.06 1.06z"/></svg>
            <b>{{ __('Cryptographic Integrity Verified') }}</b> &middot; Ed25519 (W3C VC 2.0 / Open Badges 3.0)
          </div>
          <div style="font-size:11px;margin-top:2px">
            {{ __('Public keys discoverable via UTeM standard JWKS endpoint at') }}
            <a href="{{ route('jwks') }}" target="_blank" style="font-family:var(--mono)">/.well-known/jwks.json</a>
          </div>
        </div>

        @if($cred && $o !== 'revoked')
          <div class="vr-downloads">
            <a href="{{ route('public.verify.download', [$cred->public_code, 'json']) }}" class="btn ter sm" style="font-size:12px;padding:4px 10px;height:32px" title="{{ __('Download verifiable JSON document adhering to W3C Verifiable Credentials Data Model') }}">
              <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><path d="M2 10v4h12v-4M8 2v9M4 7l4 4 4-4"/></svg>
              {{ __('Download Credential (.json)') }}
            </a>
            <a href="{{ route('public.verify.download', [$cred->public_code, 'jwt']) }}" class="btn ter sm" style="font-size:12px;padding:4px 10px;height:32px" title="{{ __('Download compact signed cryptographic token for machine verification') }}">
              <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:4px"><path d="M2 10v4h12v-4M8 2v9M4 7l4 4 4-4"/></svg>
              {{ __('Download Signed Token (.jwt)') }}
            </a>
          </div>
        @endif
      </div>
    </div>

    <!-- PDPA Compliance Statement -->
    <div class="vr-privacy-box">
      <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor" style="flex-shrink:0;margin-top:2px">
        <path d="M8 1a3.5 3.5 0 00-3.5 3.5V6H3a1 1 0 00-1 1v7a1 1 0 001 1h10a1 1 0 001-1V7a1 1 0 00-1-1h-1.5V4.5A3.5 3.5 0 008 1zm2 5H6V4.5a2 2 0 114 0V6z"/>
      </svg>
      <div>
        <b>{{ __('Personal Data Protection Act (PDPA 2010) Compliance Notice') }}:</b>
        {{ $lang === 'ms' ? 'Hanya maklumat minimum dipaparkan. Keputusan peperiksaan, nombor matrik dan e-mel tidak dipaparkan (mematuhi PDPA 2010).' : 'Only name, credential, issuer, issue date and status are shown. Results, marks, matric numbers and personal email addresses are never public.' }}
      </div>
    </div>

    <!-- Check Another Credential Search Section -->
    <div class="vr-search-box">
      <h3 style="margin:0 0 4px;font-size:16px">{{ $lang === 'ms' ? 'Semak kod kredensial atau lencana lain' : 'Check another credential or badge' }}</h3>
      <p class="muted" style="margin:0 0 12px;font-size:13px">
        {{ $lang === 'ms' ? 'Masukkan kod rasmi sijil mikro-kredensial atau pengenal pasti lencana modul:' : 'Enter any official UTeM micro-credential code or badge identifier:' }}
      </p>
      <form method="get" action="" class="vr-search-form" onsubmit="event.preventDefault(); var c = document.getElementById('code_next').value.trim(); if (c) location.href = '{{ url('/public/verify') }}/' + encodeURIComponent(c.toUpperCase());">
        <input type="text" id="code_next" name="code" placeholder="e.g. CND01-7K9MJ or BADGE-CND-M1-01" required>
        <button type="submit" class="btn sm">{{ $lang === "ms" ? "Sahkan" : "Verify" }}</button>
      </form>
    </div>
  @endif
</div>

@include('public._foot')
@endsection
