<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('Sign in') }} - UTeM Micro-Credential Management System</title>
  <link rel="stylesheet" href="{{ asset('css/mcms.css') }}">
  <style>
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      width: 100%;
      font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #0b1335;
      overflow-x: hidden;
    }
    .signin-wrapper {
      display: flex;
      width: 100vw;
      min-height: 100vh;
      position: relative;
    }
    .hero-side {
      flex: 65;
      position: relative;
      background: #0b1335;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px;
      min-height: 100vh;
    }
    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      filter: brightness(0.8);
      z-index: 1;
    }
    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(30, 42, 110, 0.75) 0%, rgba(11, 19, 53, 0.92) 100%);
      z-index: 2;
    }
    .hero-content {
      position: relative;
      z-index: 3;
      color: #ffffff;
      max-width: 680px;
    }
    .signin-panel {
      flex: 35;
      min-width: 440px;
      background: #ffffff;
      position: relative;
      z-index: 10;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px 48px 36px 84px;
      clip-path: polygon(60px 0, 100% 0, 100% 100%, 0 100%);
      margin-left: -60px;
      box-shadow: -12px 0 36px rgba(0, 0, 0, 0.35);
      overflow-y: auto;
      max-height: 100vh;
    }

    /* Persona Modal Styles */
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(11, 19, 53, 0.75);
      backdrop-filter: blur(4px);
      z-index: 10000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      box-sizing: border-box;
      opacity: 0;
      transition: opacity 0.2s ease;
    }
    .modal-backdrop.open {
      display: flex;
      opacity: 1;
    }
    .modal-dialog {
      background: #ffffff;
      width: 100%;
      max-width: 960px;
      max-height: 88vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
      border: 1px solid #d0d0d0;
      overflow: hidden;
      animation: modalFadeIn 0.2s cubic-bezier(0, 0, 0.2, 1);
    }
    @keyframes modalFadeIn {
      from { transform: translateY(16px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .modal-header {
      background: #1e2a6e;
      color: #ffffff;
      padding: 18px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 2px solid #0f62fe;
    }
    .modal-close-btn {
      background: transparent;
      border: none;
      color: #ffffff;
      font-size: 26px;
      line-height: 1;
      cursor: pointer;
      padding: 4px 8px;
      opacity: 0.8;
      transition: opacity 0.15s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-close-btn:hover {
      opacity: 1;
    }
    .modal-controls {
      padding: 14px 24px;
      background: #ffffff;
      border-bottom: 1px solid #e0e0e0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .search-input-box {
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid #c6c6c6;
      padding: 8px 12px;
      background: #ffffff;
      transition: border-color 0.15s ease;
    }
    .search-input-box:focus-within {
      border-color: #0f62fe;
      outline: 2px solid #0f62fe;
      outline-offset: -2px;
    }
    .search-input-box input {
      border: none;
      outline: none;
      width: 100%;
      font-size: 13.5px;
      font-family: inherit;
      color: #161616;
      background: transparent;
    }
    .role-pills {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .role-pill {
      background: #f4f4f4;
      border: 1px solid #dcdcdc;
      padding: 6px 12px;
      font-size: 12px;
      font-weight: 600;
      color: #393939;
      cursor: pointer;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .role-pill:hover {
      background: #e8e8e8;
      border-color: #c6c6c6;
    }
    .role-pill.active {
      background: #1e2a6e;
      color: #ffffff;
      border-color: #1e2a6e;
    }
    .modal-body {
      padding: 18px 24px;
      overflow-y: auto;
      background: #f4f4f4;
      flex: 1;
      min-height: 320px;
      max-height: calc(88vh - 240px);
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
      gap: 12px;
      align-content: start;
    }
    .persona-modal-card {
      background: #ffffff;
      border: 1px solid #e0e0e0;
      padding: 12px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      transition: all 0.15s ease;
    }
    .persona-modal-card:hover {
      border-color: #0f62fe;
      box-shadow: 0 2px 8px rgba(15, 98, 254, 0.12);
      background: #fbfdff;
    }
    .persona-info-left {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
      flex: 1;
    }
    .persona-avatar {
      width: 40px;
      height: 40px;
      color: #ffffff;
      font-weight: 700;
      font-size: 13.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .persona-avatar.role-student { background: #0f62fe; }
    .persona-avatar.role-lecturer { background: #8a3ffc; }
    .persona-avatar.role-deputy_dean { background: #1e2a6e; }
    .persona-avatar.role-codl_admin { background: #005d5d; }
    .persona-avatar.role-industry_expert { background: #ba4e00; }
    .persona-avatar.role-default { background: #525252; }

    .persona-text-details {
      min-width: 0;
      flex: 1;
    }
    .persona-name-row {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 2px;
    }
    .persona-name {
      font-size: 13.5px;
      font-weight: 600;
      color: #161616;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .persona-roles-row {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
      margin-bottom: 2px;
    }
    .role-badge {
      font-size: 11px;
      font-weight: 600;
      padding: 1px 6px;
      border-radius: 2px;
      display: inline-block;
    }
    .role-badge.role-tag-student { background: #edf5ff; color: #0043ce; border: 1px solid #d0e2ff; }
    .role-badge.role-tag-lecturer { background: #f6f2ff; color: #6929c4; border: 1px solid #e8daff; }
    .role-badge.role-tag-deputy_dean { background: #e8ecfb; color: #1e2a6e; border: 1px solid #c7d2f8; }
    .role-badge.role-tag-codl_admin { background: #e5f6f6; color: #005d5d; border: 1px solid #9ef0f0; }
    .role-badge.role-tag-industry_expert { background: #fff2e8; color: #ba4e00; border: 1px solid #ffd8bd; }

    .scope-tag {
      font-size: 10.5px;
      font-weight: 700;
      color: #393939;
      background: #e0e0e0;
      padding: 1px 5px;
      border-radius: 2px;
      letter-spacing: 0.3px;
    }
    .persona-email {
      font-size: 11px;
      color: #6f6f6f;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .modal-footer {
      padding: 12px 24px;
      background: #ffffff;
      border-top: 1px solid #e0e0e0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    @media (max-width: 992px) {
      .signin-wrapper {
        flex-direction: column;
      }
      .hero-side {
        flex: none;
        min-height: 280px;
        padding: 32px 24px;
      }
      .signin-panel {
        flex: none;
        width: 100%;
        min-width: unset;
        clip-path: none;
        margin-left: 0;
        padding: 32px 24px;
        max-height: none;
      }
    }

    @media (max-width: 768px) {
      .modal-backdrop {
        padding: 8px;
      }
      .modal-dialog {
        max-height: 96vh;
      }
      .modal-body {
        grid-template-columns: 1fr;
        padding: 12px;
      }
      .modal-controls {
        padding: 12px;
      }
    }
  </style>
</head>
<body>

<div class="signin-wrapper">
  <!-- Left Side: Campus Hero (65% width) -->
  <div class="hero-side">
    <img src="{{ asset('images/Pic-2-4.jpg') }}" alt="UTeM Campus" class="hero-bg">
    <div class="hero-overlay"></div>

    <div class="hero-content">
      <div style="display:inline-flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
        <a href="{{ route('public.home') }}" class="btn ghost sm" style="color:#ffffff;border:1px solid rgba(255,255,255,0.4);background:rgba(255,255,255,0.12);padding:6px 14px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;backdrop-filter:blur(4px);text-decoration:none">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
          </svg>
          <span>{{ __('Back') }}</span>
        </a>
        <div style="display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,0.15);padding:6px 14px;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;backdrop-filter:blur(4px);color:#ffffff">
          <span>UNIVERSITI TEKNIKAL MALAYSIA MELAKA</span>
        </div>
      </div>

      <h1 style="font-size:38px;line-height:46px;font-weight:700;margin:0 0 16px;color:#ffffff;letter-spacing:-0.5px">
        Micro-Credential Management System
      </h1>
      <p style="font-size:16px;line-height:26px;color:#d8e2ff;margin:0 0 24px;max-width:540px">
        The official academic gateway for proposal governance, quality verification, verifiable digital credentials, and Academic Bank of Credit (ABC) articulation.
      </p>
    </div>

    <div style="position:relative;z-index:3;color:rgba(255,255,255,0.75);font-size:12px">
      <img src="{{ asset('images/codl-logo.png') }}" alt="CODL Logo" style="height:50px;width:auto;display:block;margin-bottom:8px">
      <div>Centre for Open and Distance Learning (CODL) &middot; UTeM Hang Tuah Jaya, 76100 Melaka</div>
    </div>
  </div>

  <!-- Right Side: Slanted Sign In Section (~35% width) -->
  <div class="signin-panel">
    <div style="width:100%;max-width:420px;margin:auto 0;display:flex;flex-direction:column;justify-content:center">
      <div style="margin-bottom:20px">
        <div style="font-size:12px;font-weight:700;color:#0f62fe;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">
          MCMS Authentication
        </div>
        <h2 style="font-size:24px;font-weight:700;color:#1e2a6e;margin:0 0 8px;line-height:1.25">
          {{ __('UTeM Single Sign-On') }}
        </h2>
        <p style="font-size:13.5px;color:#525252;margin:0 0 12px;line-height:1.45">
          {{ __("Please log in using your UTeM's Office 365 email credentials:") }}
        </p>

        <!-- Structured Credentials Guide -->
        <div style="background:#f8f9fa;border:1px solid #e0e0e0;border-left:3px solid #1e2a6e;padding:12px 14px;border-radius:2px;display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12px">
            <code style="font-family:var(--mono);font-size:12.5px;color:#161616;font-weight:600">UserId@utem.edu.my</code>
            <span style="font-size:12px;color:#525252;white-space:nowrap;font-weight:500">(for staff)</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12px">
            <code style="font-family:var(--mono);font-size:12.5px;color:#161616;font-weight:600">MatricNo@student.utem.edu.my</code>
            <span style="font-size:12px;color:#525252;white-space:nowrap;font-weight:500">(for student)</span>
          </div>
        </div>
      </div>

      @if($errors->any())
        <div class="card" style="background:#fff1f1;border-left:4px solid #da1e28;padding:12px 16px;margin-bottom:16px;color:#a2191f;font-size:13px">
          <b>{{ __('Sign-in failed:') }}</b>
          <ul style="margin:4px 0 0 16px;padding:0">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <!-- Official SSO Action -->
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;padding-bottom:18px;border-bottom:1px solid #e0e0e0">
        <button type="button" disabled class="btn" style="width:100%;padding:11px 16px;display:flex;align-items:center;justify-content:center;gap:10px;background:#f4f4f4;color:#8d8d8d;border:1px solid #dcdcdc;cursor:not-allowed;font-size:13.5px;font-weight:600">
          <svg width="18" height="18" viewBox="0 0 21 21">
            <path fill="#f25022" d="M1 1h9v9H1z"/>
            <path fill="#00a4ef" d="M1 11h9v9H1z"/>
            <path fill="#7fba00" d="M11 1h9v9h-9z"/>
            <path fill="#ffb900" d="M11 11h9v9h-9z"/>
          </svg>
          {{ __('Sign in with Microsoft (not connected in this build)') }}
        </button>
        <div class="muted small" style="font-size:11.5px;line-height:1.4">
          {{ __('The connection to Microsoft Entra ID is added once UTeM ICT settles open item OI-03.') }}
        </div>
      </div>

      <!-- Prototype Role Quick Access Section -->
      @if($stub)
        <div style="padding-top:2px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <h2 style="font-size:15px;font-weight:700;color:#161616;margin:0">
              {{ __('Test sign-in') }}
            </h2>
            <span class="tag" style="background:#e0e0e0;color:#393939;font-size:11px;padding:2px 6px">
              Prototype
            </span>
          </div>
          <p class="muted small" style="margin:0 0 14px;font-size:12px;line-height:1.4">
            {{ __('For development and demonstration only. Select from :count test personas across all 5 roles.', ['count' => $people->count()]) }}
          </p>

          <!-- Pop up button -->
          <button type="button" id="open-personas-modal" class="btn primary" style="width:100%;padding:11px 16px;display:flex;align-items:center;justify-content:center;gap:10px;font-size:13.5px;font-weight:600;background:#1e2a6e;color:#ffffff;border:none;cursor:pointer;transition:all 0.15s ease">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
              <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
              <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
            </svg>
            <span>{{ __('Select a Test Persona') }} ({{ $people->count() }})</span>
          </button>
        </div>
      @endif

      <div style="margin-top:20px;padding-top:14px;border-top:1px solid #f4f4f4;display:flex;align-items:center;justify-content:space-between;font-size:11.5px;color:#8d8d8d">
        <span>UTeM MCMS v1.2</span>
        <span>Secured Session</span>
      </div>
    </div>
  </div>
</div>

<!-- Persona Selection Modal -->
@if($stub)
@php
  $ptjLookup = isset($ptjs) ? $ptjs : (config('mcms.stub_idp') ? \Illuminate\Support\Facades\DB::table('ptjs')->pluck('code', 'id')->all() : []);
  $roleCounts = [
    'student' => 0,
    'lecturer' => 0,
    'deputy_dean' => 0,
    'codl_admin' => 0,
    'industry_expert' => 0,
  ];
  foreach ($people as $p) {
    foreach ($p->activeRoles() as $r) {
      if (isset($roleCounts[$r->value])) {
        $roleCounts[$r->value]++;
      }
    }
  }
@endphp
<div id="personas-modal" class="modal-backdrop" aria-hidden="true" role="dialog" aria-labelledby="modal-title">
  <div class="modal-dialog">
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#78a9ff;margin-bottom:2px">
          {{ __('MCMS Development & Demonstration') }}
        </div>
        <h3 id="modal-title" style="margin:0;font-size:19px;font-weight:700;color:#ffffff">
          {{ __('Select a Test Persona') }} <span style="font-size:14px;font-weight:400;opacity:0.85">({{ $people->count() }} {{ __('Accounts') }})</span>
        </h3>
      </div>
      <button type="button" id="close-personas-modal" class="modal-close-btn" aria-label="{{ __('Close') }}">
        &times;
      </button>
    </div>

    <!-- Modal Controls: Search & Role Filter Tabs -->
    <div class="modal-controls">
      <div class="search-input-box">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="#525252">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
        </svg>
        <input type="text" id="persona-search" placeholder="{{ __('Search by name, email, role, or faculty (e.g. Amir, FTMK, Dean)...') }}" autocomplete="off">
        <button type="button" id="persona-search-clear" style="display:none;background:none;border:none;color:#525252;cursor:pointer;font-size:18px;line-height:1;padding:0 4px" title="{{ __('Clear search') }}">&times;</button>
      </div>

      <div class="role-pills">
        <button type="button" class="role-pill active" data-role="all">
          {{ __('All') }} ({{ $people->count() }})
        </button>
        <button type="button" class="role-pill" data-role="student">
          {{ __('Student') }} ({{ $roleCounts['student'] }})
        </button>
        <button type="button" class="role-pill" data-role="lecturer">
          {{ __('Lecturer') }} ({{ $roleCounts['lecturer'] }})
        </button>
        <button type="button" class="role-pill" data-role="deputy_dean">
          {{ __('Deputy Dean') }} ({{ $roleCounts['deputy_dean'] }})
        </button>
        <button type="button" class="role-pill" data-role="codl_admin">
          {{ __('CODL Admin') }} ({{ $roleCounts['codl_admin'] }})
        </button>
        <button type="button" class="role-pill" data-role="industry_expert">
          {{ __('Industry Expert') }} ({{ $roleCounts['industry_expert'] }})
        </button>
      </div>
    </div>

    <!-- Modal Body: Persona Cards Grid -->
    <div class="modal-body" id="personas-container">
      @foreach($people as $p)
        @php($roles = $p->activeRoles())
        @php($first = $roles->first())
        @php($roleKeys = $roles->map(fn($r) => $r->value)->join(' '))
        @php($initials = collect(explode(' ', preg_replace('/^(Dr\.|Ir\.|Prof\.|Datuk|Assoc\.)\s+/', '', $p->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''))
        @php($ptjList = $p->roleAssignments->where('scope_type', 'ptj')->pluck('ptj_id')->map(fn($id) => $ptjLookup[$id] ?? null)->filter()->unique()->join(', '))

        <div class="persona-modal-card" 
             data-name="{{ strtolower($p->name) }}" 
             data-email="{{ strtolower($p->email ?? $p->upn ?? '') }}" 
             data-roles="{{ strtolower($roleKeys . ' ' . $roles->map(fn($r) => $r->label())->join(' ') . ' ' . $ptjList) }}"
             data-active-roles="{{ $roleKeys }}">
          <div class="persona-info-left">
            <span class="persona-avatar role-{{ $first?->value ?? 'default' }}">{{ $initials }}</span>
            <div class="persona-text-details">
              <div class="persona-name-row">
                <span class="persona-name" title="{{ $p->name }}">{{ $p->name }}</span>
                @if($p->status !== 'active')
                  <span class="tag" style="background:#da1e28;color:#ffffff;font-size:10px;padding:1px 5px">{{ __('Deactivated') }}</span>
                @endif
              </div>
              <div class="persona-roles-row">
                @foreach($roles as $r)
                  <span class="role-badge role-tag-{{ $r->value }}">{{ __($r->label()) }}</span>
                @endforeach
                @if($ptjList)
                  <span class="scope-tag">{{ $ptjList }}</span>
                @endif
              </div>
              <div class="persona-email">{{ $p->email ?? $p->upn }}</div>
            </div>
          </div>

          <form method="post" action="{{ route('signin.stub') }}" style="margin:0;flex-shrink:0">
            @csrf
            <input type="hidden" name="user" value="{{ $p->id }}">
            <button type="submit" class="btn sm primary" style="padding:6px 12px;font-size:12px;font-weight:600;white-space:nowrap;cursor:pointer">
              {{ __('Sign in') }} &rsaquo;
            </button>
          </form>
        </div>
      @endforeach

      <div id="no-personas-found" style="display:none;grid-column:1/-1;text-align:center;padding:48px 20px;color:#6f6f6f">
        <svg width="36" height="36" viewBox="0 0 16 16" fill="#8d8d8d" style="margin-bottom:8px">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
          <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
        </svg>
        <p style="margin:0;font-weight:600;font-size:14px;color:#161616">{{ __('No matching test personas found') }}</p>
        <p style="margin:4px 0 0;font-size:12px;color:#6f6f6f">{{ __('Try searching for a different keyword or select another role filter.') }}</p>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="modal-footer">
      <span style="font-size:12px;color:#6f6f6f">
        <kbd style="background:#e0e0e0;padding:2px 6px;font-size:11px;border:1px solid #ccc;font-family:inherit;border-radius:2px">Esc</kbd> {{ __('to close') }} &middot; {{ __('Signing in simulates authenticated session with active role.') }}
      </span>
      <button type="button" id="close-personas-btn" class="btn secondary sm" style="padding:6px 14px;font-weight:600">
        {{ __('Close') }}
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('personas-modal');
  var openBtn = document.getElementById('open-personas-modal');
  var closeBtn = document.getElementById('close-personas-modal');
  var footerCloseBtn = document.getElementById('close-personas-btn');
  var searchInput = document.getElementById('persona-search');
  var searchClear = document.getElementById('persona-search-clear');
  var rolePills = document.querySelectorAll('.role-pill');
  var cards = document.querySelectorAll('.persona-modal-card');
  var noFound = document.getElementById('no-personas-found');

  if (!modal || !openBtn) return;

  function openModal() {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() {
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }, 60);
  }

  function closeModal() {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (footerCloseBtn) footerCloseBtn.addEventListener('click', closeModal);

  // Close when clicking directly on backdrop
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });

  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('open')) {
      closeModal();
    }
  });

  var currentRole = 'all';

  function filterCards() {
    var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
    var visibleCount = 0;

    cards.forEach(function(card) {
      var name = card.getAttribute('data-name') || '';
      var email = card.getAttribute('data-email') || '';
      var roles = card.getAttribute('data-roles') || '';
      var activeRoles = (card.getAttribute('data-active-roles') || '').split(' ');

      var matchesRole = (currentRole === 'all') || activeRoles.indexOf(currentRole) !== -1;
      var matchesQuery = !query || name.indexOf(query) !== -1 || email.indexOf(query) !== -1 || roles.indexOf(query) !== -1;

      if (matchesRole && matchesQuery) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (noFound) {
      noFound.style.display = visibleCount === 0 ? 'block' : 'none';
    }
    if (searchClear) {
      searchClear.style.display = query ? 'block' : 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterCards);
  }

  if (searchClear) {
    searchClear.addEventListener('click', function() {
      searchInput.value = '';
      filterCards();
      searchInput.focus();
    });
  }

  rolePills.forEach(function(pill) {
    pill.addEventListener('click', function() {
      rolePills.forEach(function(p) { p.classList.remove('active'); });
      this.classList.add('active');
      currentRole = this.getAttribute('data-role');
      filterCards();
    });
  });
});
</script>
@endif

</body>
</html>
