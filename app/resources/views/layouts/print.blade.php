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
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
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
      background: #f4f5f8 !important;
      color: #525252 !important;
      border-bottom: 1px solid #c6c6c6 !important;
      font-weight: 600 !important;
    }
    .a4 th, .a4 table th {
      color: #161616 !important;
      border-bottom: 1px solid #c6c6c6 !important;
      font-weight: 600 !important;
    }
    .a4 td, .a4 table td {
      background: #ffffff !important;
      color: #161616 !important;
      border-bottom: 1px solid #d0d0d0 !important;
    }
    .a4 tfoot td, .a4 tfoot th,
    .a4 table tfoot td, .a4 table tfoot th {
      background: #f4f5f8 !important;
      border-top: 1.5px solid #161616 !important;
      border-bottom: 1.5px solid #161616 !important;
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
      border-top: 1px solid #c6c6c6 !important;
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
      background: #eef0f6 !important;
      color: #1e2a6e !important;
      border: 1px solid #d0d5e8 !important;
      padding: 1px 5px !important;
      font-size: 8pt !important;
      font-weight: 600 !important;
    }
    .a4 .tag {
      background: #e8e8e8 !important;
      color: #161616 !important;
    }

    /* Redesigned Official A4 Compliance Document System (Carbon Print Hierarchy) */
    .a4 .print-kpi-bar {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      border: 1px solid #c6c6c6;
      border-left: 4px solid #1e2a6e;
      background: #f4f5f8 !important;
      margin: 0 0 14px;
    }
    .a4 .print-kpi-cell {
      padding: 7px 10px;
      border-left: 1px solid #d8dbe2;
    }
    .a4 .print-kpi-cell:first-child {
      border-left: 0;
    }
    .a4 .print-kpi-lbl {
      font-size: 6.8pt;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.45px;
      color: #525252 !important;
      margin-bottom: 2px;
    }
    .a4 .print-kpi-val {
      font-size: 9.2pt;
      font-weight: 600;
      color: #161616 !important;
      line-height: 1.25;
    }
    .a4 .print-kpi-sub {
      font-size: 7.2pt;
      color: #525252 !important;
      margin-top: 1px;
    }

    .a4 .print-sec {
      margin-top: 13px;
      page-break-inside: avoid;
    }
    .a4 .print-sec-title {
      font-size: 9.5pt;
      font-weight: 600;
      color: #161616 !important;
      margin: 0 0 6px;
      padding-bottom: 4px;
      border-bottom: 1.5px solid #1e2a6e;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .a4 .print-sec-title .f-code {
      background: #1e2a6e !important;
      color: #ffffff !important;
      border: 1px solid #1e2a6e !important;
      padding: 1px 6px !important;
      font-size: 7.6pt !important;
      margin-right: 7px !important;
      letter-spacing: 0.4px;
    }
    .a4 .print-sec-title .bm {
      font-weight: 400;
      font-size: 7.6pt;
      font-style: italic;
      color: #525252 !important;
    }

    /* Key-Value Field Tables (F1, F3, F4, F6): unmistakable separation of Field Label vs Filled Info */
    .a4 table.print-kv-table {
      width: 100% !important;
      border-collapse: collapse !important;
      border: 1.25px solid #393939 !important;
      margin: 0 0 8px !important;
    }
    .a4 table.print-kv-table tbody th {
      background: #f2f4f8 !important;
      color: #4a4f5a !important;
      font-size: 7.5pt !important;
      font-weight: 600 !important;
      line-height: 1.28 !important;
      letter-spacing: 0.1px;
      padding: 6px 8px !important;
      border: 1px solid #d0d4dc !important;
      border-right: 1px solid #b8bec9 !important;
      vertical-align: top !important;
      text-align: left !important;
    }
    .a4 table.print-kv-table tbody th:nth-child(3) {
      border-left: 1.5px solid #8d94a5 !important;
    }
    .a4 table.print-kv-table tbody th .lbl-bm {
      display: block;
      font-size: 6.9pt;
      font-weight: 400;
      font-style: italic;
      color: #6f7582 !important;
      margin-top: 1px;
    }
    .a4 table.print-kv-table tbody td {
      background: #ffffff !important;
      color: #161616 !important;
      font-size: 8.9pt !important;
      font-weight: 500 !important;
      line-height: 1.38 !important;
      padding: 6px 10px !important;
      border: 1px solid #d0d4dc !important;
      vertical-align: top !important;
    }
    .a4 table.print-kv-table tbody td .val-strong {
      font-weight: 600;
      color: #161616 !important;
    }
    .a4 table.print-kv-table tbody td .val-sub {
      display: block;
      font-size: 7.6pt;
      font-weight: 400;
      color: #525252 !important;
      margin-top: 2px;
    }

    /* Multi-Row Structured Tables (F2, F7, F8, F9, F10, F11) */
    .a4 table.print-audit-table {
      width: 100% !important;
      border-collapse: collapse !important;
      border: 1.25px solid #393939 !important;
      font-size: 8.2pt !important;
      line-height: 1.35 !important;
      margin: 0 0 8px !important;
    }
    .a4 table.print-audit-table thead th {
      background: #eef0f6 !important;
      color: #1e2a6e !important;
      font-size: 7.4pt !important;
      font-weight: 600 !important;
      letter-spacing: 0.15px;
      padding: 6px 7px !important;
      border: 1px solid #c6cbd6 !important;
      border-bottom: 1.5px solid #1e2a6e !important;
      vertical-align: bottom !important;
    }
    .a4 table.print-audit-table tbody td {
      background: #ffffff !important;
      color: #161616 !important;
      padding: 5.5px 7px !important;
      border: 1px solid #d4d7de !important;
      vertical-align: top !important;
    }
    .a4 table.print-audit-table tbody tr:nth-child(even) td {
      background: #fafbfc !important;
    }
    .a4 table.print-audit-table tfoot th,
    .a4 table.print-audit-table tfoot td {
      background: #f2f4f8 !important;
      color: #161616 !important;
      font-size: 8.2pt !important;
      font-weight: 600 !important;
      padding: 6px 7px !important;
      border: 1px solid #b8bec9 !important;
      border-top: 1.5px solid #161616 !important;
    }

    /* Crisp Print Checkboxes & Status Pills */
    .a4 .print-check {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 11px;
      height: 11px;
      border: 1px solid #6f6f6f;
      font-family: var(--mono);
      font-size: 7.5pt;
      line-height: 1;
      font-weight: 700;
      margin-right: 4px;
      vertical-align: -1px;
      background: #ffffff !important;
      color: #161616 !important;
    }
    .a4 .print-check.checked {
      background: #1e2a6e !important;
      border-color: #1e2a6e !important;
      color: #ffffff !important;
    }
    .a4 .print-check.checked::after {
      content: "X";
      color: #ffffff !important;
    }
    .a4 .print-badge {
      display: inline-block;
      font-size: 7.4pt;
      font-weight: 600;
      padding: 1px 6px;
      border: 1px solid #8d8d8d;
      background: #f4f4f4 !important;
      color: #161616 !important;
    }
    .a4 .print-badge.ok {
      background: #e6f4ea !important;
      border-color: #24a148 !important;
      color: #0e6027 !important;
    }
    .a4 .print-badge.info {
      background: #eef0f6 !important;
      border-color: #1e2a6e !important;
      color: #1e2a6e !important;
    }
    .a4 .not-recorded {
      color: #6f6f6f !important;
      font-style: italic;
      font-weight: 400 !important;
    }
    .a4 .print-note-box {
      border: 1px solid #c6cbd6 !important;
      border-left: 3px solid #1e2a6e !important;
      padding: 7px 10px !important;
      font-size: 8pt !important;
      line-height: 1.4 !important;
      margin: 7px 0 !important;
      background: #f4f5f8 !important;
      color: #262626 !important;
    }

    @media print {
      body, .doc-wrap, .a4 {
        background: #ffffff !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .a4 .print-sec {
        page-break-inside: avoid;
      }
      .a4 .doc-foot {
        border-top: 1px solid #8d8d8d !important;
      }
    }
  </style>
  @hasSection('land')<style>@page { size: A4 landscape; margin: 0; }</style>@endif
</head>
<body style="background:#e0e0e0;padding:0">
{{-- The document frame of the design prototype: a dark bar (not printed), an A4 sheet with the university letterhead and a footer line --}}
<div class="doc-bar">
  <button type="button" class="btn ter sm" onclick="goDocBack()" style="cursor:pointer">{{ __('Back') }}</button>
  <span>@if(app()->getLocale() === 'ms') @hasSection('doc_bm')@yield('doc_bm')@else @yield('doc_title')@endif @else @yield('doc_title') @endif @hasSection('doc_file')&middot; @yield('doc_file') @endif</span><span class="grow"></span>
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
      <div><b style="font-size:16pt">UTeM</b><div style="font-size:9pt">Universiti Teknikal Malaysia Melaka</div><div class="muted" style="font-size:8pt">{{ __('Centre for Open and Distance Learning (CODL)') }}</div></div>
      <div style="margin-left:auto;text-align:right"><b>@if(app()->getLocale() === 'ms') @hasSection('doc_bm')@yield('doc_bm')@else @yield('doc_title')@endif @else @yield('doc_title') @endif</b><div class="muted" style="font-size:9pt">@yield('doc_ref') &middot; {{ now()->format('d/m/Y') }}</div></div>
    </div>
    @yield('content')
    <div class="doc-foot"><span>@yield('doc_ref')</span><span>{{ __('Official MCMS Record (A4)') }}</span><span>{{ __('Generated') }} {{ now()->format('d/m/Y H:i') }}</span><span>{{ __('Generated by MCMS') }}</span></div>
  </div>
</div>
</body>
</html>
