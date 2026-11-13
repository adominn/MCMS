<!doctype html>
<html lang="{{ app()->getLocale() }}" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>@yield('title', 'Document') - UTeM MCMS</title>
  <link rel="stylesheet" href="{{ asset('css/mcms.css') }}?v={{ filemtime(public_path('css/mcms.css')) }}">
  <style>
    :root, html, body {
      color-scheme: light !important;
      background: #e0e0e0 !important;
      color: #161616 !important;
      --border: #161616 !important;
      --hairline: #161616 !important;
      --hairline-strong: #161616 !important;
      --rule: #161616 !important;
      --hair: #161616 !important;
      --text: #161616 !important;
      --ink: #161616 !important;
    }
    .doc-wrap {
      background: #e0e0e0 !important;
    }
    .doc-bar {
      background: #161616 !important;
      color: #ffffff !important;
    }
    .a4, .a4 * {
      color-scheme: light !important;
      --border: #161616 !important;
      --hairline: #161616 !important;
      --hairline-strong: #161616 !important;
      --rule: #161616 !important;
      --hair: #161616 !important;
      --text: #161616 !important;
      --ink: #161616 !important;
    }
    .a4 {
      background: #ffffff !important;
      color: #161616 !important;
    }
    .a4 table {
      background: #ffffff !important;
      color: #161616 !important;
      border-collapse: collapse !important;
      width: 100% !important;
    }
    .a4 thead th, .a4 table thead th {
      background: #f4f4f4 !important;
      color: #161616 !important;
      border-bottom: 2px solid #161616 !important;
      font-weight: 600 !important;
    }
    .a4 tbody th, .a4 table tbody th {
      background: transparent !important;
      color: #161616 !important;
      border-bottom: 1px solid #161616 !important;
      font-weight: 600 !important;
    }
    .a4 th, .a4 table th {
      color: #161616 !important;
      border-bottom: 1px solid #161616 !important;
      font-weight: 600 !important;
    }
    .a4 td, .a4 table td {
      background: #ffffff !important;
      color: #161616 !important;
      border-bottom: 1px solid #161616 !important;
    }
    .a4 tfoot td, .a4 tfoot th,
    .a4 table tfoot td, .a4 table tfoot th {
      border-top: 2px solid #161616 !important;
      border-bottom: 1px solid #161616 !important;
      font-weight: 600 !important;
    }
    .a4 tr:hover td {
      background: #ffffff !important;
      color: #161616 !important;
    }
    .a4 .panel, .a4 .card {
      background: #ffffff !important;
      border: 1px solid #161616 !important;
      color: #161616 !important;
    }
    .a4 .panel > .hd, .a4 .card > .hd,
    .a4 .panel .hd, .a4 .card .hd {
      border-bottom: 1px solid #161616 !important;
      color: #161616 !important;
    }
    .a4 .doc-foot {
      border-top: 1px solid #161616 !important;
    }
    .a4 #summary, .a4 .bd, .a4 .hd, .a4 p {
      color: #161616 !important;
    }
    .a4 .muted, .a4 .bm, .a4 .small, .a4 .s {
      color: #525252 !important;
    }
    .a4 .doc-head b, .a4 h1, .a4 h2, .a4 h3, .a4 b, .a4 strong {
      color: #161616 !important;
    }
    .a4 code, .a4 code.vc {
      background: #f0f0f0 !important;
      color: #161616 !important;
      border: none !important;
    }
    .a4 .tag {
      background: #e8e8e8 !important;
      color: #161616 !important;
    }
    @media print {
      body, .doc-wrap, .a4 {
        background: #ffffff !important;
        color: #000000 !important;
      }
      .a4 table {
        border-color: #000000 !important;
      }
      .a4 thead th {
        border-bottom: 2px solid #000000 !important;
      }
      .a4 th, .a4 td {
        border-bottom: 1px solid #000000 !important;
      }
      .a4 tfoot td, .a4 tfoot th {
        border-top: 2px solid #000000 !important;
        border-bottom: 1px solid #000000 !important;
      }
      .a4 .panel, .a4 .card {
        border: 1px solid #000000 !important;
      }
      .a4 .panel > .hd, .a4 .card > .hd,
      .a4 .panel .hd, .a4 .card .hd {
        border-bottom: 1px solid #000000 !important;
      }
      .a4 .doc-foot {
        border-top: 1px solid #000000 !important;
      }
    }
  </style>
  @hasSection('land')<style>@page { size: A4 landscape; margin: 0; }</style>@endif
</head>
<body style="background:#e0e0e0;padding:0">
{{-- The document frame of the design prototype: a dark bar (not printed), an A4 sheet with the university letterhead and a footer line --}}
<div class="doc-bar">
  <button type="button" class="btn ter sm" onclick="goDocBack()" style="cursor:pointer">{{ __('Back') }}</button>
  <span>@yield('doc_title') @hasSection('doc_file')&middot; @yield('doc_file') @endif</span><span class="grow"></span>
  @yield('doc_actions')
  <button type="button" class="btn sm" onclick="window.print()">{{ __('Print or save as PDF') }}</button>
</div>
<script>
function goDocBack() {
  if (document.referrer && document.referrer !== window.location.href && document.referrer.indexOf(window.location.host) !== -1) {
    window.location.href = document.referrer;
    return;
  }
  if (window.history.length > 1) {
    window.history.back();
    return;
  }
  var clean = window.location.href.replace(/([?&])print(=[^&]*)?(&|$)/, '$1').replace(/[?&]$/, '');
  if (clean !== window.location.href) {
    window.location.href = clean;
    return;
  }
  if (window.opener) {
    try { window.close(); return; } catch(e) {}
  }
  window.location.href = '{{ route("tasks") }}';
}
</script>
<div class="doc-wrap">
  <div class="a4 @hasSection('land') land @endif">
    <div class="doc-head">
      <div><b style="font-size:16pt">UTeM</b><div style="font-size:9pt">Universiti Teknikal Malaysia Melaka</div><div class="muted" style="font-size:8pt">Centre for Open and Distance Learning (CODL)</div></div>
      <div style="margin-left:auto;text-align:right"><b>@yield('doc_title')</b>@hasSection('doc_bm')<div class="bm" style="font-size:9pt">@yield('doc_bm')</div>@endif<div class="muted" style="font-size:9pt">@yield('doc_ref') &middot; {{ now()->format('d/m/Y') }}</div></div>
    </div>
    @yield('content')
    <div class="doc-foot"><span>@yield('doc_ref')</span><span>{{ __('Page') }} <span class="pg">1</span> {{ __('of') }} 1</span><span>Generated {{ now()->format('d/m/Y H:i') }}</span><span>{{ __('Generated by MCMS') }}</span></div>
  </div>
</div>
</body>
</html>
