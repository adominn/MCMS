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
      background: #e2e8f0 !important;
      color: #090a0f !important;
      --border: #090a0f !important;
      --hairline: #090a0f !important;
      --hairline-strong: #090a0f !important;
      --rule: #090a0f !important;
      --hair: #090a0f !important;
      --text: #090a0f !important;
      --ink: #090a0f !important;
    }
    .doc-wrap {
      background: #e2e8f0 !important;
    }
    .doc-bar {
      background: #090a0f !important;
      color: #ffffff !important;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .a4, .a4 * {
      color-scheme: light !important;
      --border: #090a0f !important;
      --hairline: #090a0f !important;
      --hairline-strong: #090a0f !important;
      --rule: #090a0f !important;
      --hair: #090a0f !important;
      --text: #090a0f !important;
      --ink: #090a0f !important;
    }
    .a4 {
      background: #ffffff !important;
      color: #090a0f !important;
      overflow: visible !important;
      position: relative;
      margin: 0 auto 24px !important;
      box-shadow: 0 2px 10px rgba(15, 23, 42, .12) !important;
      border: 1px solid #cbd5e1 !important;
      box-sizing: border-box !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .a4:last-child {
      margin-bottom: 0 !important;
    }
    .a4-land-pill {
      display: inline-block;
      font-size: 7.5pt;
      font-weight: 600;
      color: #0b1a3d !important;
      background: #edf4ff;
      border: 1px solid #bfdbfe;
      padding: 1px 7px;
      border-radius: 3px;
      margin-bottom: 8px;
    }
    .ev-attachment-block {
      margin: 0;
      padding: 0;
    }
    .ev-att-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 6px 0 8px;
      margin-bottom: 10px;
      border-bottom: 1.5px solid #0b1a3d;
      font-size: 9pt;
    }
    .ev-att-body, .pdf-inline-render {
      background: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 0;
    }
    .pdf-page-img {
      display: block;
      max-width: 100%;
      width: auto;
      max-height: 245mm;
      height: auto;
      object-fit: contain;
      margin: 0 auto;
      background: #ffffff;
    }
    .a4 .table-wrap {
      overflow-x: visible !important;
      overflow-y: visible !important;
      max-width: 100% !important;
    }
    .a4 table {
      background: #ffffff !important;
      color: #090a0f !important;
      border-collapse: collapse !important;
      width: 100% !important;
      max-width: 100% !important;
      table-layout: auto !important;
    }
    .a4 thead {
      display: table-header-group !important;
    }
    .a4 tfoot {
      display: table-footer-group !important;
    }
    .a4 tr {
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .a4 th, .a4 td, .a4 table th, .a4 table td, .a4 dd, .a4 p, .a4 li {
      white-space: normal !important;
      word-wrap: break-word !important;
      overflow-wrap: break-word !important;
      text-overflow: clip !important;
      vertical-align: top !important;
    }
    .a4 code, .a4 .mono, .a4 a {
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
    }
    .a4.land {
      width: 297mm !important;
      max-width: calc(100vw - 32px) !important;
      min-height: 210mm !important;
    }
    .a4-chunk-label {
      font-size: 8pt;
      font-weight: 600;
      color: #475569 !important;
      margin: 8px 0 4px;
    }
    .a4 thead th, .a4 table thead th {
      background: #f1f5f9 !important;
      color: #090a0f !important;
      border-bottom: 2px solid #090a0f !important;
      font-weight: 600 !important;
    }
    .a4 tbody th, .a4 table tbody th {
      background: #f4f5f8 !important;
      color: #475569 !important;
      border-bottom: 1px solid #c6c6c6 !important;
      font-weight: 600 !important;
    }
    .a4 th, .a4 table th {
      color: #090a0f !important;
      border-bottom: 1px solid #c6c6c6 !important;
      font-weight: 600 !important;
    }
    .a4 td, .a4 table td {
      background: #ffffff !important;
      color: #090a0f !important;
      border-bottom: 1px solid #d0d0d0 !important;
    }
    .a4 tfoot td, .a4 tfoot th,
    .a4 table tfoot td, .a4 table tfoot th {
      background: #f4f5f8 !important;
      border-top: 1.5px solid #090a0f !important;
      border-bottom: 1.5px solid #090a0f !important;
      font-weight: 600 !important;
    }
    .a4 tr:hover td {
      background: #ffffff !important;
      color: #090a0f !important;
    }
    .a4 .panel, .a4 .card {
      background: #ffffff !important;
      border: 1px solid #090a0f !important;
      color: #090a0f !important;
    }
    .a4 .panel > .hd, .a4 .card > .hd,
    .a4 .panel .hd, .a4 .card .hd {
      border-bottom: 1px solid #090a0f !important;
      color: #090a0f !important;
    }
    .a4 .doc-foot {
      border-top: 1px solid #c6c6c6 !important;
    }
    .a4 #summary, .a4 .bd, .a4 .hd, .a4 p {
      color: #090a0f !important;
    }
    .a4 .muted, .a4 .bm, .a4 .small, .a4 .s {
      color: #475569 !important;
    }
    .a4 .doc-head b, .a4 h1, .a4 h2, .a4 h3, .a4 b, .a4 strong {
      color: #090a0f !important;
    }
    .a4 code, .a4 code.vc {
      background: #eef0f6 !important;
      color: #0b1a3d !important;
      border: 1px solid #d0d5e8 !important;
      padding: 1px 5px !important;
      font-size: 8pt !important;
      font-weight: 600 !important;
    }
    .a4 .tag {
      background: #e8e8e8 !important;
      color: #090a0f !important;
    }

    /* Redesigned Official A4 Compliance Document System (Carbon Print Hierarchy) */
    .a4 .print-kpi-bar {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      border: 1px solid #c6c6c6;
      border-left: 4px solid #0b1a3d;
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
      color: #475569 !important;
      margin-bottom: 2px;
    }
    .a4 .print-kpi-val {
      font-size: 9.2pt;
      font-weight: 600;
      color: #090a0f !important;
      line-height: 1.25;
    }
    .a4 .print-kpi-sub {
      font-size: 7.2pt;
      color: #475569 !important;
      margin-top: 1px;
    }

    .a4 .print-sec {
      margin-top: 13px;
    }
    .a4 .print-sec-title, .a4 .panel > .hd, .a4 h2, .a4 h3 {
      break-after: avoid;
      page-break-after: avoid;
    }
    .a4 .print-sec-title {
      font-size: 9.5pt;
      font-weight: 600;
      color: #090a0f !important;
      margin: 0 0 6px;
      padding-bottom: 4px;
      border-bottom: 1.5px solid #0b1a3d;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .a4 .print-sec-title .f-code {
      background: #0b1a3d !important;
      color: #ffffff !important;
      border: 1px solid #0b1a3d !important;
      padding: 1px 6px !important;
      font-size: 7.6pt !important;
      margin-right: 7px !important;
      letter-spacing: 0.4px;
    }
    .a4 .print-sec-title .bm {
      font-weight: 400;
      font-size: 7.6pt;
      font-style: italic;
      color: #475569 !important;
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
      color: #090a0f !important;
      font-size: 8.9pt !important;
      font-weight: 500 !important;
      line-height: 1.38 !important;
      padding: 6px 10px !important;
      border: 1px solid #d0d4dc !important;
      vertical-align: top !important;
    }
    .a4 table.print-kv-table tbody td .val-strong {
      font-weight: 600;
      color: #090a0f !important;
    }
    .a4 table.print-kv-table tbody td .val-sub {
      display: block;
      font-size: 7.6pt;
      font-weight: 400;
      color: #475569 !important;
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
      color: #0b1a3d !important;
      font-size: 7.4pt !important;
      font-weight: 600 !important;
      letter-spacing: 0.15px;
      padding: 6px 7px !important;
      border: 1px solid #c6cbd6 !important;
      border-bottom: 1.5px solid #0b1a3d !important;
      vertical-align: bottom !important;
    }
    .a4 table.print-audit-table tbody td {
      background: #ffffff !important;
      color: #090a0f !important;
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
      color: #090a0f !important;
      font-size: 8.2pt !important;
      font-weight: 600 !important;
      padding: 6px 7px !important;
      border: 1px solid #b8bec9 !important;
      border-top: 1.5px solid #090a0f !important;
    }

    /* Crisp Print Checkboxes & Status Pills */
    .a4 .print-check {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 11px;
      height: 11px;
      border: 1px solid #475569;
      font-family: var(--mono);
      font-size: 7.5pt;
      line-height: 1;
      font-weight: 700;
      margin-right: 4px;
      vertical-align: -1px;
      background: #ffffff !important;
      color: #090a0f !important;
    }
    .a4 .print-check.checked {
      background: #0b1a3d !important;
      border-color: #0b1a3d !important;
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
      background: #f1f5f9 !important;
      color: #090a0f !important;
    }
    .a4 .print-badge.ok {
      background: #e6f4ea !important;
      border-color: #24a148 !important;
      color: #0e6027 !important;
    }
    .a4 .print-badge.info {
      background: #eef0f6 !important;
      border-color: #0b1a3d !important;
      color: #0b1a3d !important;
    }
    .a4 .not-recorded {
      color: #475569 !important;
      font-style: italic;
      font-weight: 400 !important;
    }
    .a4 .print-note-box {
      border: 1px solid #c6cbd6 !important;
      border-left: 3px solid #0b1a3d !important;
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
      .doc-bar, .no-print, .a4-land-pill {
        display: none !important;
      }
      .doc-wrap {
        padding: 0 !important;
        margin: 0 !important;
      }
      .a4 {
        padding: 12mm !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: auto !important;
        box-sizing: border-box !important;
        break-after: page;
        page-break-after: always;
      }
      .a4:last-child {
        break-after: auto;
        page-break-after: auto;
      }
      .a4.land {
        page: a4-landscape;
        width: 100% !important;
        max-width: 100% !important;
      }
      .a4-land-pill {
        display: none !important;
      }
      .a4 .doc-foot {
        border-top: 1px solid #8d8d8d !important;
      }
    }
  </style>
  @hasSection('land')
    <style id="dynamic-page-orientation">@page { size: A4 landscape; margin: 0; } @page a4-landscape { size: A4 landscape; margin: 0; }</style>
  @else
    <style id="dynamic-page-orientation">@page { size: A4 portrait; margin: 0; } @page a4-landscape { size: A4 landscape; margin: 0; }</style>
  @endif
  @if(request()->boolean('embed'))
    @hasSection('multi_sheet')
    <style>
      body { background: #e2e8f0 !important; padding: 0 !important; }
      .doc-wrap { background: #e2e8f0 !important; padding: 16px 12px 24px !important; }
      .a4 { box-shadow: 0 2px 10px rgba(15,23,42,.12) !important; border: 1px solid #cbd5e1 !important; width: 100% !important; max-width: 210mm !important; min-height: auto !important; margin: 0 auto 20px !important; padding: 22px 26px 26px !important; background: #ffffff !important; box-sizing: border-box !important; }
      .a4:last-child { margin-bottom: 0 !important; }
      .a4 .doc-foot { position: static !important; margin-top: 20px !important; }
    </style>
    @else
    <style>
      body { background: #ffffff !important; padding: 0 !important; }
      .doc-wrap { background: #ffffff !important; padding: 0 !important; }
      .a4 { box-shadow: none !important; border: 0 !important; width: 100% !important; max-width: 100% !important; min-height: auto !important; padding: 20px 24px 28px !important; }
      .a4 .doc-foot { position: static !important; margin-top: 20px !important; }
    </style>
    @endif
  @endif
</head>
<body style="background:#e2e8f0;padding:0">
{{-- The document frame of the design prototype: a dark bar (not printed), an A4 sheet with the university letterhead and a footer line --}}
@if(!request()->boolean('embed'))
<div class="doc-bar">
  <button type="button" class="btn ter sm" onclick="goDocBack()" style="cursor:pointer">{{ __('Back') }}</button>
  <span>@if(app()->getLocale() === 'ms') @hasSection('doc_bm')@yield('doc_bm')@else @yield('doc_title')@endif @else @yield('doc_title') @endif @hasSection('doc_file')&middot; @yield('doc_file') @endif</span><span class="grow"></span>
  @yield('doc_actions')
  @php
    $docLang = app()->getLocale() === 'ms' ? 'ms' : 'en';
    $docLangQueryEn = array_merge($requestQuery = request()->query(), ['doc_lang' => 'en']);
    unset($docLangQueryEn['lang']);
    $docLangQueryMs = array_merge($requestQuery, ['doc_lang' => 'ms']);
    unset($docLangQueryMs['lang']);
    $docLangEnUrl = url()->current() . '?' . http_build_query($docLangQueryEn);
    $docLangMsUrl = url()->current() . '?' . http_build_query($docLangQueryMs);
  @endphp
  <div class="doc-lang-toggle" role="group" aria-label="{{ __('Document language') }}" style="display:inline-flex;border:1px solid rgba(255,255,255,0.35);border-radius:4px;overflow:hidden;margin-right:6px">
    <a href="{{ $docLangEnUrl }}" style="padding:4px 10px;font-size:12px;font-weight:600;text-decoration:none;background:{{ $docLang === 'en' ? '#0052ff' : 'transparent' }};color:#ffffff" @if($docLang === 'en') aria-current="true" @endif>EN</a>
    <a href="{{ $docLangMsUrl }}" style="padding:4px 10px;font-size:12px;font-weight:600;text-decoration:none;border-left:1px solid rgba(255,255,255,0.35);background:{{ $docLang === 'ms' ? '#0052ff' : 'transparent' }};color:#ffffff" @if($docLang === 'ms') aria-current="true" @endif>BM</a>
  </div>
  <button type="button" class="btn sm" onclick="printDoc()" style="cursor:pointer">{{ __('Print or save as PDF') }}</button>
</div>
@endif
@if(request()->boolean('autoprint'))
<script>
  window.addEventListener('load', function() {
    setTimeout(function() {
      try { window.print(); } catch(e) {}
    }, 400);
  });
</script>
@endif
<script>
function printDoc() {
  try { window.focus(); } catch (e) {}
  window.print();
}

(function () {
  var MAX_MATRIX_DATA_COLS = 5;
  var MIN_PORTRAIT_COL_PX = 105; // ~28mm at 96dpi
  var PORTRAIT_BODY_MAX_PX = 940; // usable A4 portrait body height before starting a new sheet
  var isMs = document.documentElement.lang === 'ms';

  function chunkWideMatrixTable(tbl) {
    var headRow = tbl.querySelector('thead tr');
    if (!headRow) return;
    var ths = Array.prototype.slice.call(headRow.children);
    var totalCols = ths.length;
    if (totalCols <= 1 + MAX_MATRIX_DATA_COLS) return;

    var dataCols = totalCols - 1;
    var chunks = Math.ceil(dataCols / MAX_MATRIX_DATA_COLS);
    var bodyRows = Array.prototype.slice.call(tbl.querySelectorAll('tbody tr'));
    var parent = tbl.parentNode;
    var frag = document.createDocumentFragment();

    for (var c = 0; c < chunks; c++) {
      var start = 1 + c * MAX_MATRIX_DATA_COLS;
      var end = Math.min(totalCols, start + MAX_MATRIX_DATA_COLS);

      var lbl = document.createElement('div');
      lbl.className = 'a4-chunk-label';
      lbl.textContent = isMs
        ? ('Bahagian ' + (c + 1) + ' daripada ' + chunks)
        : ('Part ' + (c + 1) + ' of ' + chunks);
      frag.appendChild(lbl);

      var sub = document.createElement('table');
      sub.className = tbl.className;
      sub.setAttribute('data-matrix', '1');
      sub.setAttribute('data-chunked', '1');

      var thead = document.createElement('thead');
      var htr = document.createElement('tr');
      htr.appendChild(ths[0].cloneNode(true));
      for (var i = start; i < end; i++) {
        htr.appendChild(ths[i].cloneNode(true));
      }
      thead.appendChild(htr);
      sub.appendChild(thead);

      var tbody = document.createElement('tbody');
      bodyRows.forEach(function (r) {
        var cells = r.children;
        var ntr = document.createElement('tr');
        if (cells[0]) ntr.appendChild(cells[0].cloneNode(true));
        for (var j = start; j < end; j++) {
          if (cells[j]) ntr.appendChild(cells[j].cloneNode(true));
        }
        tbody.appendChild(ntr);
      });
      sub.appendChild(tbody);
      frag.appendChild(sub);
    }

    parent.replaceChild(frag, tbl);
  }

  function markLandscapeBlock(tbl) {
    var sheet = tbl.closest('.a4');
    if (!sheet || sheet.classList.contains('land')) return;
    if (tbl.closest('[data-landscape-block="1"]')) return;

    var container = tbl.closest('.panel, .print-sec');
    if (container && container.parentNode === sheet) {
      container.setAttribute('data-landscape-block', '1');
      return;
    }

    var target = tbl.closest('.table-wrap') || tbl;
    var wrap = document.createElement('div');
    wrap.setAttribute('data-landscape-block', '1');
    var prev = target.previousElementSibling;
    var hasHeading = (prev && /^(H2|H3|H4)$/.test(prev.tagName)) || (prev && prev.classList && (prev.classList.contains('sec-title') || prev.classList.contains('sec-hd')));
    target.parentNode.insertBefore(wrap, hasHeading ? prev : target);
    if (hasHeading) wrap.appendChild(prev);
    wrap.appendChild(target);
  }

  function paginateIntoSheets() {
    var wrap = document.querySelector('.doc-wrap');
    if (!wrap) return;
    var sheets = wrap.querySelectorAll(':scope > .a4');
    // Only auto-paginate single-sheet portrait documents (leave full-landscape certificates and explicit multi_sheet views untouched)
    if (sheets.length !== 1 || sheets[0].classList.contains('land')) return;

    var orig = sheets[0];
    var head = orig.querySelector(':scope > .doc-head');
    var foot = orig.querySelector(':scope > .doc-foot');
    var blocks = [];
    Array.prototype.slice.call(orig.children).forEach(function (el) {
      if (el === head || el === foot) return;
      if (el.tagName === 'STYLE' || el.tagName === 'SCRIPT') return;
      blocks.push(el);
    });
    if (blocks.length === 0) return;

    // Pre-measure heights while blocks are rendered in the live sheet
    var heights = blocks.map(function (el) {
      var cs = window.getComputedStyle(el);
      var mt = parseFloat(cs.marginTop) || 0;
      var mb = parseFloat(cs.marginBottom) || 0;
      return el.offsetHeight + mt + mb;
    });
    var headH = head ? (head.offsetHeight + 16) : 0;

    var pages = [];
    var current = { land: false, items: [], usedH: headH };

    blocks.forEach(function (el, idx) {
      var isLand = el.getAttribute('data-landscape-block') === '1';
      var forceBreak = el.getAttribute('data-page-break') === '1'
        || (el.style && (el.style.pageBreakBefore === 'always' || el.style.breakBefore === 'page'))
        || el.classList.contains('page-break');
      var h = heights[idx] || 0;

      if (isLand) {
        if (current.items.length > 0) {
          pages.push(current);
        }
        pages.push({ land: true, items: [el], usedH: h });
        current = { land: false, items: [], usedH: 0 };
        return;
      }

      if (forceBreak && current.items.length > 0) {
        pages.push(current);
        current = { land: false, items: [], usedH: 0 };
      }

      if (current.items.length > 0 && (current.usedH + h) > PORTRAIT_BODY_MAX_PX) {
        pages.push(current);
        current = { land: false, items: [], usedH: 0 };
      }

      current.items.push(el);
      current.usedH += h;
    });

    if (current.items.length > 0 || pages.length === 0) {
      pages.push(current);
    }

    if (pages.length <= 1 && !pages[0].land) {
      return; // Fits cleanly on 1 portrait sheet already
    }

    // Build real separated A4 sheets with crisp gray spacing between them
    var frag = document.createDocumentFragment();
    pages.forEach(function (pg, pIdx) {
      var sheet = document.createElement('div');
      sheet.className = pg.land ? 'a4 land' : 'a4';
      if (pIdx === 0 && head) {
        sheet.appendChild(head);
      }
      if (pg.land) {
        var pill = document.createElement('div');
        pill.className = 'a4-land-pill';
        pill.textContent = isMs ? 'Halaman Landskap A4 (Automatik)' : 'A4 Landscape Page (Automatic)';
        sheet.appendChild(pill);
      }
      pg.items.forEach(function (item) {
        if (item.style && item.style.pageBreakBefore === 'always') {
          item.style.pageBreakBefore = 'auto';
        }
        sheet.appendChild(item);
      });
      if (pIdx === pages.length - 1 && foot) {
        sheet.appendChild(foot);
      }
      frag.appendChild(sheet);
    });

    // Preserve any inline <style> tags from the original sheet
    orig.querySelectorAll(':scope > style').forEach(function (st) {
      wrap.insertBefore(st, orig);
    });
    wrap.replaceChild(frag, orig);
  }

  function renderInlinePdfs() {
    var containers = document.querySelectorAll('.pdf-inline-render[data-pdf-url]');
    if (!containers.length) return;

    function runPdfJs() {
      if (!window.pdfjsLib) return;
      window.pdfjsLib.GlobalWorkerOptions.workerSrc = '{{ asset("js/pdfjs/pdf.worker.min.js") }}';

      containers.forEach(function (box) {
        var url = box.getAttribute('data-pdf-url');
        if (!url) return;
        var attBlock = box.closest('.ev-attachment-block');
        var hostSheet = box.closest('.a4');
        var attTitle = attBlock ? (attBlock.getAttribute('data-att-title') || '') : '';
        var attFile = attBlock ? (attBlock.getAttribute('data-att-file') || '') : '';

        window.pdfjsLib.getDocument({ url: url, withCredentials: true }).promise.then(function (pdf) {
          box.innerHTML = '';
          var insertAfterSheet = hostSheet;
          var chain = Promise.resolve();
          for (var p = 1; p <= pdf.numPages; p++) {
            (function (pageNum) {
              chain = chain.then(function () {
                return pdf.getPage(pageNum).then(function (page) {
                  var viewport = page.getViewport({ scale: 2.0 });
                  var canvas = document.createElement('canvas');
                  var ctx = canvas.getContext('2d');
                  canvas.width = viewport.width;
                  canvas.height = viewport.height;
                  return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                    var img = document.createElement('img');
                    img.className = 'pdf-page-img';
                    img.alt = (attFile || 'PDF') + ' - page ' + pageNum;
                    img.src = canvas.toDataURL('image/png');

                    if (pageNum === 1 || !hostSheet || !hostSheet.parentNode) {
                      box.appendChild(img);
                    } else {
                      // Put page 2+ of a multi-page PDF on its own clean A4 sheet
                      var newSheet = document.createElement('div');
                      newSheet.className = 'a4';
                      var bar = document.createElement('div');
                      bar.className = 'ev-att-bar';
                      var lbl = document.createElement('div');
                      lbl.innerHTML = '<strong>' + attTitle + '</strong> <span class="muted">&middot; ' + attFile + ' (' + (isMs ? 'Muka surat ' : 'Page ') + pageNum + ' / ' + pdf.numPages + ')</span>';
                      bar.appendChild(lbl);
                      var body = document.createElement('div');
                      body.className = 'ev-att-body';
                      body.appendChild(img);
                      newSheet.appendChild(bar);
                      newSheet.appendChild(body);
                      insertAfterSheet.parentNode.insertBefore(newSheet, insertAfterSheet.nextSibling);
                      insertAfterSheet = newSheet;
                    }
                  });
                });
              });
            })(p);
          }
        }).catch(function () {
          box.innerHTML = '<div class="small muted" style="padding:10px">'
            + (isMs ? 'Tidak dapat memaparkan pratonton PDF. Gunakan pautan "Buka fail" di atas.' : 'Unable to preview PDF inline. Use the "Open file" link above.')
            + '</div>';
        });
      });
    }

    if (window.pdfjsLib) {
      runPdfJs();
      return;
    }
    var s = document.createElement('script');
    s.src = '{{ asset("js/pdfjs/pdf.min.js") }}';
    s.onload = runPdfJs;
    document.head.appendChild(s);
  }

  document.addEventListener('DOMContentLoaded', function () {
    // 1. Chunk extra-wide matrix tables (> 5 data columns) before landscape layout
    document.querySelectorAll('.a4 table[data-matrix]').forEach(chunkWideMatrixTable);

    // 2. Automatically mark wide tables/matrices for A4 Landscape sheets
    document.querySelectorAll('.a4 table').forEach(function (tbl) {
      if (tbl.classList.contains('print-kv-table') || tbl.classList.contains('header-table')) return;
      var sheet = tbl.closest('.a4');
      if (!sheet || sheet.classList.contains('land')) return;

      var headRow = tbl.querySelector('thead tr') || tbl.querySelector('tr');
      if (!headRow) return;
      var cols = 0;
      Array.prototype.forEach.call(headRow.children, function (cell) {
        cols += parseInt(cell.getAttribute('colspan') || '1', 10) || 1;
      });

      var isMatrix = tbl.hasAttribute('data-matrix');
      var tblWidth = tbl.clientWidth || 680;
      var avgColPx = cols > 0 ? (tblWidth / cols) : tblWidth;
      var overflows = tbl.scrollWidth > tblWidth + 4;

      if (cols >= 6 || (isMatrix && cols >= 4) || (cols >= 4 && avgColPx < MIN_PORTRAIT_COL_PX) || overflows) {
        markLandscapeBlock(tbl);
      }
    });

    // 3. Split into clean separated A4 sheets with gray gaps between pages (zero cut lines)
    paginateIntoSheets();

    // 4. Render any attached PDFs directly as crisp flat images (no PDF viewer toolbar)
    renderInlinePdfs();
  });
})();

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
  @hasSection('multi_sheet')
    @yield('multi_sheet')
  @else
    <div class="a4 @hasSection('land') land @endif">
      <div class="doc-head">
        <div><b style="font-size:16pt">UTeM</b><div style="font-size:9pt">Universiti Teknikal Malaysia Melaka</div><div class="muted" style="font-size:8pt">{{ __('Centre for Open and Distance Learning (CODL)') }}</div></div>
        <div style="margin-left:auto;text-align:right"><b>@if(app()->getLocale() === 'ms') @hasSection('doc_bm')@yield('doc_bm')@else @yield('doc_title')@endif @else @yield('doc_title') @endif</b><div class="muted" style="font-size:9pt">@yield('doc_ref') &middot; {{ now()->format('d/m/Y') }}</div></div>
      </div>
      @yield('content')
      <div class="doc-foot"><span>@yield('doc_ref')</span><span>{{ __('Official MCMS Record (A4)') }}</span><span>{{ __('Generated') }} {{ now()->format('d/m/Y H:i') }}</span><span>{{ __('Generated by MCMS') }}</span></div>
    </div>
  @endif
</div>
</body>
</html>
