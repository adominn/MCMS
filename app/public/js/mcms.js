/* MCMS front end: the behaviour of the approved design prototype (core.js) on server-rendered pages.
   Everything works without it; this only adds sorting, search and paging to tables, dialogs, toasts and menus. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var el = function (tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; };
  var esc = function (t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  /* ---------- tables: shared inline search + sort toolbar, rows per page, pager (D-73) ---------- */
  var NON_SORTABLE_RE = /^(action|actions|tindakan|select all.*|pilih semua.*|download|muat turun|receipt|resit|no\.?|#|seq|notice\s*\/\s*action|notis\s*\/\s*tindakan|change setting|ubah tetapan)$/i;
  var EMPTY_VAL_RE = /^(-|–|—|n\/a|t\/b|none|tiada|\s*)$/i;
  var STATUS_RANK = {
    'draft': 10, 'draf': 10, 'planned': 12, 'dirancang': 12,
    'returned': 20, 'dikembalikan': 20, 'amend': 22, 'pinda': 22, 'needs amendment': 22, 'perlu pindaan': 22,
    'pending': 25, 'menunggu': 25, 'pending check': 25, 'invited': 26, 'dijemput': 26, 'not started': 27, 'belum bermula': 27, 'not yet': 27,
    'submitted': 30, 'dihantar': 30,
    'in review': 40, 'dalam semakan': 40, 'under review': 40, 'in progress': 42, 'sedang berjalan': 42, 'running': 43, 'open': 45, 'terbuka': 45, 'answered': 46, 'dijawab': 46,
    'endorsed': 50, 'disokong': 50, 'support': 52, 'sokong': 52, 'verified': 55, 'disahkan': 55, 'codl verified': 56, 'provisional': 57, 'held': 58, 'ditahan': 58,
    'approved': 60, 'diluluskan': 60, 'approved with conditions': 62, 'active': 64, 'aktif': 64, 'published': 65, 'diterbitkan': 65,
    'completed': 70, 'ended': 71, 'tamat': 70, 'closed': 71, 'ditutup': 71, 'confirmed': 72, 'resolved': 74, 'selesai': 74, 'passed': 75, 'lulus': 75, 'deposited': 76, 'didepositkan': 76, 'redeemed': 78,
    'declined': 85, 'ditolak': 85, 'not supported': 86, 'tidak disokong': 86, 'do not support': 86, 'not approved': 87, 'failed': 88, 'gagal': 88, 'revoked': 90, 'dibatalkan': 90, 'archived': 95, 'diarkibkan': 95
  };
  var MONTHS = { jan: 0, feb: 1, mar: 2, apr: 3, may: 4, mei: 4, jun: 5, jul: 6, aug: 7, ogo: 7, sep: 8, oct: 9, okt: 9, nov: 10, dec: 11, dis: 11 };

  function isMsLang() { return (document.documentElement.lang || '').toLowerCase().indexOf('ms') === 0; }
  function tStr(en, ms) { return isMsLang() ? ms : en; }
  function makeCollator() {
    try { return new Intl.Collator(isMsLang() ? 'ms' : 'en', { numeric: true, sensitivity: 'base' }); }
    catch (e) { return null; }
  }
  function parseDateMs(txt) {
    var s = txt.trim();
    if (!s || EMPTY_VAL_RE.test(s)) return null;
    // dd/mm/yyyy (optional HH:MM)
    var m = s.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?/);
    if (m) return Date.UTC(+m[3], +m[2] - 1, +m[1], +(m[4] || 0), +(m[5] || 0));
    // yyyy-mm-dd
    m = s.match(/(\d{4})-(\d{1,2})-(\d{1,2})(?:[T\s](\d{1,2}):(\d{2}))?/);
    if (m) return Date.UTC(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0));
    // dd Mmm yyyy
    m = s.match(/(\d{1,2})\s+([A-Za-z]{3,})\s+(\d{4})/);
    if (m) {
      var mon = MONTHS[m[2].slice(0, 3).toLowerCase()];
      if (mon != null) return Date.UTC(+m[3], mon, +m[1]);
    }
    return null;
  }
  function parseNumVal(txt) {
    var s = txt.trim();
    if (!s || EMPTY_VAL_RE.test(s)) return null;
    var cleaned = s.replace(/,/g, '');
    var m = cleaned.match(/-?\d+(?:\.\d+)?/);
    return m ? parseFloat(m[0]) : null;
  }
  function parseStatusRank(txt) {
    var s = txt.trim().toLowerCase().replace(/\s+/g, ' ');
    if (!s || EMPTY_VAL_RE.test(s)) return null;
    if (STATUS_RANK[s] != null) return STATUS_RANK[s];
    for (var k in STATUS_RANK) {
      if (Object.prototype.hasOwnProperty.call(STATUS_RANK, k) && s.indexOf(k) > -1) {
        return STATUS_RANK[k];
      }
    }
    return null;
  }
  function cellRaw(row, i) {
    var cell = row.cells[i];
    if (!cell) return '';
    if (cell.hasAttribute('data-sort-value')) return cell.getAttribute('data-sort-value') || '';
    return cell.textContent.trim();
  }
  function extractKey(row, i, type) {
    var raw = cellRaw(row, i);
    if (!raw || EMPTY_VAL_RE.test(raw)) return { empty: true, val: null };
    if (type === 'date') {
      var d = parseDateMs(raw);
      return d === null ? { empty: true, val: null } : { empty: false, val: d };
    }
    if (type === 'num') {
      var n = parseNumVal(raw);
      return n === null ? { empty: true, val: null } : { empty: false, val: n };
    }
    if (type === 'status') {
      var rk = parseStatusRank(raw);
      return rk !== null ? { empty: false, val: rk, fallback: raw } : { empty: false, val: 999, fallback: raw };
    }
    return { empty: false, val: raw };
  }
  function detectColType(th, rows, i) {
    var explicit = th.getAttribute('data-sort-type');
    if (explicit) return explicit;
    var hdr = th.textContent.trim().toLowerCase();
    var cells = rows.map(function (r) { return cellRaw(r, i); }).filter(function (c) { return c && !EMPTY_VAL_RE.test(c); });
    if (/(status|result|decision|recommendation|integrity|keputusan|cadangan|integriti)/.test(hdr)) {
      if (!cells.length || cells.some(function (c) { return parseStatusRank(c) !== null; })) return 'status';
    }
    if (/(date|submitted|updated|issued|due|expiry|when|time|last change|tarikh|dihantar|dikeluarkan|masa|period)/.test(hdr)) {
      if (!cells.length || cells.some(function (c) { return parseDateMs(c) !== null; })) return 'date';
    }
    if (cells.length && cells.every(function (c) { return parseDateMs(c) !== null; })) return 'date';
    if (/(credit|slt|hour|fee|amount|score|mark|weight|waiting|version|kredit|jam|yuran|markah|versi|rows|attempts)/.test(hdr)) {
      if (!cells.length || cells.some(function (c) { return parseNumVal(c) !== null; })) return 'num';
    }
    if (cells.length && cells.every(function (c) { return /^(RM\s*)?-?[\d,]+(\.\d+)?(\s*(%|cr|h|hours|jam|days|hari))?$/i.test(c); })) return 'num';
    return 'text';
  }
  function dirLabels(type) {
    if (type === 'date') return { asc: tStr('Oldest first', 'Terlama dahulu'), desc: tStr('Newest first', 'Terbaharu dahulu'), defaultDir: -1 };
    if (type === 'num') return { asc: tStr('Lowest first', 'Terendah dahulu'), desc: tStr('Highest first', 'Tertinggi dahulu'), defaultDir: -1 };
    if (type === 'status') return { asc: tStr('Workflow order', 'Urutan aliran kerja'), desc: tStr('Reverse order', 'Urutan terbalik'), defaultDir: 1 };
    return { asc: tStr('A to Z', 'A ke Z'), desc: tStr('Z to A', 'Z ke A'), defaultDir: 1 };
  }

  function enhance(tbl) {
    var body = tbl.tBodies[0];
    if (!body || tbl.hasAttribute('data-plain') || tbl.closest('[data-plain]') || tbl.querySelector('[rowspan], tbody input:not([type=hidden]), tbody select, tbody textarea') || tbl.tHead === null) { return; }
    var rows = $$('tr', body).filter(function (r) {
      if (r.parentNode !== body) return false;
      if (r.hasAttribute('data-empty-row')) return false;
      if (r.cells.length === 1 && (r.cells[0].colSpan > 1 || r.cells[0].classList.contains('muted'))) return false;
      return true;
    });
    if (!rows.length) { return; }
    if (tbl.dataset.enhanced) { return; }
    tbl.dataset.enhanced = '1';

    rows.forEach(function (r, idx) { r.__mcmsOrigIdx = idx; });

    var hasWrap = tbl.parentNode.classList && tbl.parentNode.classList.contains('table-wrap');
    var wrap = hasWrap ? tbl.parentNode : tbl;
    var panel = tbl.closest('.panel');
    if (!panel) {
      panel = el('div', 'panel');
      wrap.parentNode.insertBefore(panel, wrap);
      panel.appendChild(wrap);
    }
    var host = wrap.parentNode;

    if (!hasWrap && !tbl.hasAttribute('data-no-wrap')) {
      wrap = el('div', 'table-wrap');
      wrap.style.cssText = 'overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%';
      tbl.parentNode.insertBefore(wrap, tbl);
      wrap.appendChild(tbl);
    }

    // Build list of meaningful, visible, sortable columns (headers remain plain non-clickable labels!)
    var sortableCols = [];
    var types = [];
    $$('th', tbl.tHead).forEach(function (th, i) {
      var label = th.textContent.trim();
      var type = detectColType(th, rows, i);
      types[i] = type;
      if (type === 'num') {
        th.classList.add('num');
        rows.forEach(function (r) { if (r.cells[i]) r.cells[i].classList.add('num'); });
      }
      if (!label || th.hasAttribute('data-nosort') || NON_SORTABLE_RE.test(label) || th.querySelector('input[type=checkbox]')) {
        return;
      }
      // Also skip if column cells only contain buttons/links with no meaningful data
      var allButtons = rows.length > 0 && rows.every(function (r) {
        var c = r.cells[i];
        return c && c.querySelector('button, .btn') && !c.textContent.replace(/open|view|review|edit|remove|delete|buka|lihat|semak/gi, '').trim();
      });
      if (allButtons) return;
      sortableCols.push({ idx: i, label: label, type: type });
    });

    // Optional explicit default sort: data-default-sort="4:desc" or "Submitted:desc"
    var defaultCol = -1, defaultDir = 1;
    var defAttr = tbl.getAttribute('data-default-sort');
    if (defAttr && sortableCols.length) {
      var dp = defAttr.split(':');
      var target = dp[0].trim();
      var found = sortableCols.find(function (sc) {
        return String(sc.idx) === target || sc.label.toLowerCase() === target.toLowerCase();
      });
      if (found) {
        defaultCol = found.idx;
        defaultDir = (dp[1] && dp[1].toLowerCase() === 'desc') ? -1 : (dp[1] && dp[1].toLowerCase() === 'asc' ? 1 : dirLabels(found.type).defaultDir);
      }
    }

    var defaultPer = parseInt(tbl.getAttribute('data-per-page') || '10', 10) || 10;
    var state = { q: '', per: defaultPer, page: 1, col: defaultCol, dir: defaultDir, filters: {} };
    var showSearch = !tbl.hasAttribute('data-no-search');
    var showSort = sortableCols.length > 0 && !tbl.hasAttribute('data-no-sort');
    var hasServerPager = tbl.hasAttribute('data-no-paginate') ||
      !!host.querySelector('.pagination-bar, .pagination, [aria-label*="Pagination"]') ||
      !!(host.parentElement && host.parentElement.querySelector('.pagination-bar, .pagination')) ||
      !!(panel && panel.querySelector('.pagination-bar, .pagination'));
    var showPager = !hasServerPager && (rows.length > 5 || tbl.hasAttribute('data-paginated'));

    // Build smart per-table filter definitions from sortable columns
    function cellFilterVal(row, i, label) {
      var cell = row.cells[i];
      if (!cell) return '';
      if (cell.hasAttribute('data-filter-value')) return (cell.getAttribute('data-filter-value') || '').trim();
      if (/^(category|kategori)$/i.test(label)) {
        var firstTag = cell.querySelector('.tag, .badge, .pill');
        if (firstTag) return firstTag.textContent.trim();
      }
      return cell.textContent.replace(/\s+/g, ' ').trim();
    }

    function formatFilterCount(n, unitSpec) {
      if (!unitSpec) return String(n);
      var parts = unitSpec.split('|');
      var singular = parts[0] || '';
      var plural = parts[1] || singular;
      return n + ' ' + (n === 1 ? singular : plural);
    }

    function normSearch(s) {
      var str = String(s || '');
      try { str = str.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) {}
      return str.toLowerCase();
    }

    var FILTER_TAXONOMY_RE = /(status|category|type|kind|role|level|mqf|stage|outcome|decision|recommendation|part|faculty|ptj|domain|mode|channel|language|result|alert|cqi review|target status|verification|credit status|policy|adapter|consent|kategori|jenis|peranan|tahap|peringkat|keputusan|cadangan|fakulti|mod|saluran|bahasa|semakan)/i;
    var FILTER_BLOCK_RE = /^(reference|ref|code|id|no\.?|number|micro-credential|proposal|title|name|cohort|programme|course|proposer|lecturer|participant|student|recipient|email|staff.*|matric.*|file.*|checksum|sha-?256|reason|note|remarks|comment|description|summary|scope|why|key metrics|metrics|who|by|action|rujukan|kod|tajuk|nama|kohort|program|kursus|pencadang|pensyarah|peserta|penerima|emel|fail|sebab|catatan|ulasan|ringkasan)$/i;

    var filterCols = [];
    var collator = makeCollator();
    if (!tbl.hasAttribute('data-no-filter')) {
      var ths = $$('th', tbl.tHead);
      sortableCols.forEach(function (sc) {
        var th = ths[sc.idx];
        if (th && th.hasAttribute('data-nofilter')) return;
        var forceType = th ? th.getAttribute('data-filter-type') : null;
        if (forceType === 'none') return;
        var unitSpec = th ? th.getAttribute('data-filter-unit') : null;

        // 1. Dates -> always presets + date range, never a list of dates
        if (forceType === 'date' || (!forceType && sc.type === 'date')) {
          var hasAnyDate = rows.some(function (r) { return parseDateMs(cellRaw(r, sc.idx)) !== null; });
          if (hasAnyDate) {
            filterCols.push({ idx: sc.idx, label: sc.label, kind: 'date', priority: 4 });
          }
          return;
        }
        // 2. Numbers -> always Min / Max numeric range, never a chip list
        if (forceType === 'num' || (!forceType && sc.type === 'num')) {
          var numVals = [];
          var minV = null, maxV = null;
          rows.forEach(function (r) {
            var n = parseNumVal(cellRaw(r, sc.idx));
            if (n !== null) {
              if (numVals.indexOf(n) === -1) numVals.push(n);
              if (minV === null || n < minV) minV = n;
              if (maxV === null || n > maxV) maxV = n;
            }
          });
          if (numVals.length >= 2) {
            filterCols.push({ idx: sc.idx, label: sc.label, kind: 'num', minHint: minV, maxHint: maxV, priority: 5 });
          }
          return;
        }
        // 3. Repeating taxonomy or explicit dropdown only; identifiers & free-text stay search-only
        var isExplicitSelect = forceType === 'select' || forceType === 'dropdown';
        var isTaxonomy = sc.type === 'status' || FILTER_TAXONOMY_RE.test(sc.label);
        if (!isExplicitSelect && (FILTER_BLOCK_RE.test(sc.label.trim()) || !isTaxonomy)) {
          return;
        }

        var valCounts = {};
        var vals = [];
        var maxLen = 0;
        rows.forEach(function (r) {
          var v = cellFilterVal(r, sc.idx, sc.label);
          if (!v || EMPTY_VAL_RE.test(v)) return;
          if (v.length > maxLen) maxLen = v.length;
          if (!Object.prototype.hasOwnProperty.call(valCounts, v)) {
            valCounts[v] = 0;
            vals.push(v);
          }
          valCounts[v]++;
        });
        if (vals.length < 2 || vals.length > 25) return;
        // Safety fallback: if a non-status column has 1 unique value per row on a larger table (>= 5 rows), keep search-only
        if (!isExplicitSelect && sc.type !== 'status' && rows.length >= 5 && vals.length === rows.length) return;

        if (sc.type === 'status') {
          vals.sort(function (a, b) {
            var ra = parseStatusRank(a), rb = parseStatusRank(b);
            if (ra !== null && rb !== null && ra !== rb) return ra - rb;
            if (ra !== null && rb === null) return -1;
            if (ra === null && rb !== null) return 1;
            return collator ? collator.compare(a, b) : a.localeCompare(b);
          });
        } else {
          vals.sort(function (a, b) { return collator ? collator.compare(a, b) : a.localeCompare(b); });
        }

        var useDropdown = (forceType === 'dropdown') || vals.length > 8 || maxLen > 34;
        var prio = (sc.type === 'status' || /(status|alert|outcome|decision|cqi review|target)/i.test(sc.label))
          ? 1
          : (useDropdown ? 3 : 2);
        filterCols.push({
          idx: sc.idx,
          label: sc.label,
          kind: useDropdown ? 'dropdown' : 'select',
          options: vals.map(function (v) { return { val: v, count: valCounts[v] }; }),
          unit: unitSpec,
          priority: prio
        });
      });
      filterCols.sort(function (a, b) { return (a.priority - b.priority) || (a.idx - b.idx); });
    }
    var showFilter = filterCols.length > 0;

    // Build concise Search placeholder (max 2 primary columns so it never truncates) + full list in tooltip
    var searchableCols = sortableCols.filter(function (sc) { return sc.type === 'text' || sc.type === 'status'; });
    function defaultSearchPlaceholder() {
      if (!searchableCols.length) return tStr('Search records...', 'Cari rekod...');
      var names = searchableCols.slice(0, 2).map(function (sc) { return sc.label.toLowerCase(); });
      if (names.length === 1) return tStr('Search ', 'Cari ') + names[0];
      return tStr('Search ', 'Cari ') + names[0] + tStr(' or ', ' atau ') + names[1];
    }

    var bar = el('div', 'tbar'), count = el('span', 'small muted tbar-count');
    var liveRegion = el('span', 'sr');
    liveRegion.setAttribute('aria-live', 'polite');

    var searchWrap = el('div', 'tbar-search-wrap');
    var search = el('input', 'input');
    search.type = 'search';
    var phText = tbl.getAttribute('data-search-placeholder') || defaultSearchPlaceholder();
    search.setAttribute('aria-label', phText);
    search.placeholder = phText;
    if (searchableCols.length) {
      search.title = tStr('Searches: ', 'Mencari: ') + searchableCols.map(function (sc) { return sc.label; }).join(', ');
    }
    searchWrap.appendChild(search);

    // Inline Sort control (D-73) — portaled to body so panel/modal overflow:hidden never clips it
    var sortWrap = el('div', 'tbar-sort');
    var sortBtn = el('button', 'tbar-sort-btn');
    sortBtn.type = 'button';
    sortBtn.setAttribute('aria-haspopup', 'true');
    sortBtn.setAttribute('aria-expanded', 'false');
    var sortMenu = el('div', 'tbar-sort-menu');
    sortMenu.hidden = true;
    sortMenu.setAttribute('role', 'dialog');
    sortMenu.setAttribute('aria-label', tStr('Sort options', 'Pilihan susunan'));
    sortWrap.appendChild(sortBtn);
    document.body.appendChild(sortMenu);

    // Inline Filter control (D-74) — portaled to body so panel/modal overflow:hidden never clips it
    var filterWrap = el('div', 'tbar-filter');
    var filterBtn = el('button', 'tbar-sort-btn tbar-filter-btn');
    filterBtn.type = 'button';
    filterBtn.setAttribute('aria-haspopup', 'true');
    filterBtn.setAttribute('aria-expanded', 'false');
    var filterMenu = el('div', 'tbar-sort-menu tbar-filter-menu');
    filterMenu.hidden = true;
    filterMenu.setAttribute('role', 'dialog');
    filterMenu.setAttribute('aria-label', tStr('Filter options', 'Pilihan tapisan'));
    filterWrap.appendChild(filterBtn);
    document.body.appendChild(filterMenu);
    var chipsBar = el('div', 'tbar-chips');
    chipsBar.hidden = true;

    function positionPopover(btn, menu) {
      var r = btn.getBoundingClientRect();
      var vw = window.innerWidth;
      var vh = window.innerHeight;
      menu.style.maxHeight = Math.max(200, vh - 24) + 'px';
      menu.style.overflowY = 'auto';
      var mw = menu.offsetWidth || 280;
      var mh = menu.offsetHeight || 260;
      var left = Math.max(12, Math.min(r.left, vw - mw - 12));
      var spaceBelow = vh - r.bottom - 12;
      var spaceAbove = r.top - 12;
      var top;
      if (spaceBelow >= mh || spaceBelow >= spaceAbove) {
        top = r.bottom + 4;
        if (top + mh > vh - 12) {
          menu.style.maxHeight = Math.max(160, vh - top - 12) + 'px';
        }
      } else {
        var availAbove = Math.max(160, spaceAbove - 4);
        menu.style.maxHeight = availAbove + 'px';
        top = Math.max(12, r.top - Math.min(mh, availAbove) - 4);
      }
      menu.style.left = Math.round(left) + 'px';
      menu.style.top = Math.round(top) + 'px';
    }

    function activeFilterCount() {
      var n = 0;
      filterCols.forEach(function (fc) {
        var f = state.filters[fc.idx];
        if (!f) return;
        if ((fc.kind === 'select' || fc.kind === 'dropdown') && f.values && f.values.length) n++;
        else if (fc.kind === 'date' && (f.preset || f.from || f.to)) n++;
        else if (fc.kind === 'num' && (f.min !== '' && f.min != null || f.max !== '' && f.max != null)) n++;
      });
      return n;
    }

    function datePresetLabel(p) {
      var map = {
        today: tStr('Today', 'Hari ini'),
        '7d': tStr('Last 7 days', '7 hari lepas'),
        '30d': tStr('Last 30 days', '30 hari lepas'),
        year: tStr('This year', 'Tahun ini'),
        has: tStr('Has date', 'Ada tarikh'),
        empty: tStr('No date', 'Tiada tarikh')
      };
      return map[p] || p;
    }

    function renderFilterUI() {
      if (!showFilter) return;
      var cnt = activeFilterCount();
      filterBtn.innerHTML = '<span class="tbar-sort-lbl">' + esc(cnt > 0 ? tStr('Filter', 'Tapis') + ' (' + cnt + ')' : tStr('Filter', 'Tapis')) + '</span><span class="tbar-sort-caret" aria-hidden="true">&#9662;</span>';
      filterBtn.classList.toggle('active', cnt > 0);

      // Active filter chips strip
      if (cnt === 0) {
        chipsBar.hidden = true;
        chipsBar.innerHTML = '';
      } else {
        var ch = '';
        filterCols.forEach(function (fc) {
          var f = state.filters[fc.idx];
          if (!f) return;
          if ((fc.kind === 'select' || fc.kind === 'dropdown') && f.values && f.values.length) {
            f.values.forEach(function (v) {
              ch += '<span class="tbar-chip"><b>' + esc(fc.label) + ':</b> ' + esc(v) + ' <button type="button" class="tbar-chip-x" data-fcol="' + fc.idx + '" data-fval="' + esc(v) + '" aria-label="' + esc(tStr('Remove filter', 'Buang tapisan')) + '">&times;</button></span>';
            });
          } else if (fc.kind === 'date' && (f.preset || f.from || f.to)) {
            var dtxt = f.preset ? datePresetLabel(f.preset) : ((f.from || '…') + ' – ' + (f.to || '…'));
            ch += '<span class="tbar-chip"><b>' + esc(fc.label) + ':</b> ' + esc(dtxt) + ' <button type="button" class="tbar-chip-x" data-fcol="' + fc.idx + '" aria-label="' + esc(tStr('Remove filter', 'Buang tapisan')) + '">&times;</button></span>';
          } else if (fc.kind === 'num' && ((f.min !== '' && f.min != null) || (f.max !== '' && f.max != null))) {
            var ntxt = (f.min !== '' && f.min != null ? f.min : '…') + ' – ' + (f.max !== '' && f.max != null ? f.max : '…');
            ch += '<span class="tbar-chip"><b>' + esc(fc.label) + ':</b> ' + esc(ntxt) + ' <button type="button" class="tbar-chip-x" data-fcol="' + fc.idx + '" aria-label="' + esc(tStr('Remove filter', 'Buang tapisan')) + '">&times;</button></span>';
          }
        });
        ch += '<button type="button" class="tbar-sort-reset tbar-chips-clear">' + esc(tStr('Clear all', 'Kosongkan semua')) + '</button>';
        chipsBar.innerHTML = ch;
        chipsBar.hidden = false;
      }
    }

    function renderFilterMenu() {
      var html = '<div class="tbar-filter-body">';
      filterCols.forEach(function (fc) {
        var f = state.filters[fc.idx] || {};
        html += '<div class="tbar-filter-group">';
        html += '<div class="tbar-sort-sec">' + esc(fc.label) + '</div>';
        if (fc.kind === 'select') {
          var sel = f.values || [];
          html += '<div class="tbar-filter-pills">';
          fc.options.forEach(function (opt) {
            var on = sel.indexOf(opt.val) > -1;
            var cntTxt = formatFilterCount(opt.count, fc.unit);
            html += '<label class="tbar-fpill' + (on ? ' on' : '') + '"><input type="checkbox" data-fcol="' + fc.idx + '" value="' + esc(opt.val) + '"' + (on ? ' checked' : '') + '><span>' + esc(opt.val) + '</span><span class="tbar-fpill-n">' + esc(fc.unit ? '(' + cntTxt + ')' : cntTxt) + '</span></label>';
          });
          html += '</div>';
        } else if (fc.kind === 'dropdown') {
          var dsel = f.values || [];
          html += '<div class="tbar-fdrop" data-fdrop-col="' + fc.idx + '">';
          if (fc.options.length > 4) {
            html += '<input type="search" class="tbar-fdrop-search" placeholder="' + esc(tStr('Filter ', 'Tapis ') + fc.label.toLowerCase() + '...') + '">';
          }
          html += '<div class="tbar-fdrop-list">';
          fc.options.forEach(function (opt) {
            var on = dsel.indexOf(opt.val) > -1;
            var cntTxt = formatFilterCount(opt.count, fc.unit);
            html += '<label class="tbar-fdrop-opt' + (on ? ' on' : '') + '" data-opt-txt="' + esc(normSearch(opt.val)) + '"><input type="checkbox" data-fcol="' + fc.idx + '" value="' + esc(opt.val) + '"' + (on ? ' checked' : '') + '><span class="tbar-fdrop-lbl">' + esc(opt.val) + '</span><span class="tbar-fpill-n">' + esc(fc.unit ? '(' + cntTxt + ')' : cntTxt) + '</span></label>';
          });
          html += '</div></div>';
        } else if (fc.kind === 'date') {
          var presets = ['today', '7d', '30d', 'year', 'has', 'empty'];
          html += '<div class="tbar-filter-pills">';
          presets.forEach(function (p) {
            var on = f.preset === p;
            html += '<button type="button" class="tbar-fpill-btn' + (on ? ' on' : '') + '" data-fcol="' + fc.idx + '" data-preset="' + p + '">' + esc(datePresetLabel(p)) + '</button>';
          });
          html += '</div>';
          html += '<div class="tbar-date-range">';
          html += '<label><span>' + esc(tStr('From', 'Dari')) + '</span><input type="date" class="tbar-fdate" data-fcol="' + fc.idx + '" data-bound="from" value="' + esc(f.from || '') + '"></label>';
          html += '<label><span>' + esc(tStr('To', 'Hingga')) + '</span><input type="date" class="tbar-fdate" data-fcol="' + fc.idx + '" data-bound="to" value="' + esc(f.to || '') + '"></label>';
          html += '</div>';
        } else if (fc.kind === 'num') {
          html += '<div class="tbar-date-range">';
          html += '<label><span>' + esc(tStr('Min', 'Min')) + '</span><input type="number" step="any" class="tbar-fnum" data-fcol="' + fc.idx + '" data-bound="min" placeholder="' + esc(fc.minHint != null ? fc.minHint : '') + '" value="' + esc(f.min != null ? f.min : '') + '"></label>';
          html += '<label><span>' + esc(tStr('Max', 'Maks')) + '</span><input type="number" step="any" class="tbar-fnum" data-fcol="' + fc.idx + '" data-bound="max" placeholder="' + esc(fc.maxHint != null ? fc.maxHint : '') + '" value="' + esc(f.max != null ? f.max : '') + '"></label>';
          html += '</div>';
        }
        html += '</div>';
      });
      html += '</div>';
      if (activeFilterCount() > 0) {
        html += '<div class="tbar-sort-foot"><button type="button" class="tbar-sort-reset tbar-filter-reset">' + esc(tStr('Clear filters', 'Kosongkan tapisan')) + '</button></div>';
      }
      filterMenu.innerHTML = html;
    }

    function openFilterMenu() {
      closeSortMenu(false);
      $$('.tbar-sort-menu:not([hidden])').forEach(function (m) {
        if (m !== filterMenu) {
          m.hidden = true;
        }
      });
      $$('.tbar-sort-btn[aria-expanded="true"]').forEach(function (b) {
        if (b !== filterBtn) b.setAttribute('aria-expanded', 'false');
      });
      renderFilterMenu();
      filterMenu.hidden = false;
      positionPopover(filterBtn, filterMenu);
      filterBtn.setAttribute('aria-expanded', 'true');
      var firstFocus = filterMenu.querySelector('input, button');
      if (firstFocus) firstFocus.focus();
    }
    function closeFilterMenu(restoreFocus) {
      if (filterMenu.hidden) return;
      filterMenu.hidden = true;
      filterBtn.setAttribute('aria-expanded', 'false');
      if (restoreFocus) filterBtn.focus();
    }

    filterBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (filterMenu.hidden) openFilterMenu(); else closeFilterMenu(false);
    });
    filterMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      var pbtn = e.target.closest('.tbar-fpill-btn');
      if (pbtn) {
        var col = parseInt(pbtn.getAttribute('data-fcol'), 10);
        var preset = pbtn.getAttribute('data-preset');
        var cur = state.filters[col] || {};
        if (cur.preset === preset) {
          delete state.filters[col];
        } else {
          state.filters[col] = { preset: preset, from: '', to: '' };
        }
        state.page = 1;
        draw(true);
        renderFilterMenu();
        return;
      }
      if (e.target.closest('.tbar-filter-reset')) {
        state.filters = {};
        state.page = 1;
        draw(true);
        closeFilterMenu(true);
      }
    });
    filterMenu.addEventListener('change', function (e) {
      var t = e.target;
      if (!t || !t.hasAttribute('data-fcol')) return;
      var col = parseInt(t.getAttribute('data-fcol'), 10);
      if (t.type === 'checkbox') {
        var cur = state.filters[col] && state.filters[col].values ? state.filters[col].values.slice() : [];
        var val = t.value;
        var pos = cur.indexOf(val);
        if (t.checked && pos === -1) cur.push(val);
        else if (!t.checked && pos > -1) cur.splice(pos, 1);
        if (cur.length) state.filters[col] = { values: cur };
        else delete state.filters[col];
        state.page = 1;
        draw(true);
        renderFilterMenu();
      } else if (t.classList.contains('tbar-fdate')) {
        var bound = t.getAttribute('data-bound');
        var curD = state.filters[col] ? Object.assign({}, state.filters[col]) : {};
        curD.preset = '';
        curD[bound] = t.value;
        if (curD.from || curD.to) state.filters[col] = curD;
        else delete state.filters[col];
        state.page = 1;
        draw(true);
        renderFilterMenu();
      } else if (t.classList.contains('tbar-fnum')) {
        var nb = t.getAttribute('data-bound');
        var curN = state.filters[col] ? Object.assign({}, state.filters[col]) : {};
        curN[nb] = t.value;
        if ((curN.min !== '' && curN.min != null) || (curN.max !== '' && curN.max != null)) state.filters[col] = curN;
        else delete state.filters[col];
        state.page = 1;
        draw(true);
      }
    });
    filterMenu.addEventListener('input', function (e) {
      var t = e.target;
      if (!t || !t.classList.contains('tbar-fdrop-search')) return;
      var drop = t.closest('.tbar-fdrop');
      if (!drop) return;
      var q = normSearch(t.value.trim());
      $$('.tbar-fdrop-opt', drop).forEach(function (opt) {
        var txt = opt.getAttribute('data-opt-txt') || '';
        opt.hidden = !!q && txt.indexOf(q) === -1;
      });
    });
    filterMenu.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { e.preventDefault(); closeFilterMenu(true); }
    });
    chipsBar.addEventListener('click', function (e) {
      var x = e.target.closest('.tbar-chip-x');
      if (x) {
        var col = parseInt(x.getAttribute('data-fcol'), 10);
        var val = x.getAttribute('data-fval');
        if (val != null && state.filters[col] && state.filters[col].values) {
          var next = state.filters[col].values.filter(function (v) { return v !== val; });
          if (next.length) state.filters[col] = { values: next };
          else delete state.filters[col];
        } else {
          delete state.filters[col];
        }
        state.page = 1;
        draw(true);
        if (!filterMenu.hidden) renderFilterMenu();
        return;
      }
      if (e.target.closest('.tbar-chips-clear')) {
        state.filters = {};
        state.page = 1;
        draw(true);
        if (!filterMenu.hidden) renderFilterMenu();
      }
    });

    function currentSortMeta() {
      return sortableCols.find(function (sc) { return sc.idx === state.col; }) || null;
    }
    function sortSummaryText() {
      var meta = currentSortMeta();
      if (!meta) return tStr('Sort: Default', 'Susun: Lalai');
      var dl = dirLabels(meta.type);
      var dirTxt = state.dir > 0 ? dl.asc : dl.desc;
      return tStr('Sort: ', 'Susun: ') + meta.label + ', ' + dirTxt.toLowerCase();
    }
    function renderSortMenu() {
      var meta = currentSortMeta();
      var activeType = meta ? meta.type : (sortableCols[0] ? sortableCols[0].type : 'text');
      var dl = dirLabels(activeType);
      var isDefault = (state.col === defaultCol && state.dir === defaultDir);

      sortBtn.innerHTML = '<span class="tbar-sort-lbl">' + esc(sortSummaryText()) + '</span><span class="tbar-sort-caret" aria-hidden="true">&#9662;</span>';
      sortBtn.classList.toggle('active', !isDefault);

      var html = '<div class="tbar-sort-sec">' + esc(tStr('Sort by', 'Susun mengikut')) + '</div><div class="tbar-sort-list">';
      sortableCols.forEach(function (sc) {
        var checked = sc.idx === state.col;
        html += '<label class="tbar-sort-opt' + (checked ? ' on' : '') + '"><input type="radio" name="mcms_sort_' + esc(storageKey) + '" value="' + sc.idx + '"' + (checked ? ' checked' : '') + '><span>' + esc(sc.label) + '</span></label>';
      });
      html += '</div>';

      html += '<div class="tbar-sort-sec" style="margin-top:10px">' + esc(tStr('Direction', 'Arah')) + '</div>';
      html += '<div class="tbar-sort-dir" role="group" aria-label="' + esc(tStr('Sort direction', 'Arah susunan')) + '">';
      var firstDir = dl.defaultDir;
      var secondDir = -dl.defaultDir;
      var firstLbl = firstDir > 0 ? dl.asc : dl.desc;
      var secondLbl = secondDir > 0 ? dl.asc : dl.desc;
      html += '<button type="button" class="tbar-dir-btn' + (meta && state.dir === firstDir ? ' on' : '') + '" data-dir="' + firstDir + '">' + esc(firstLbl) + '</button>';
      html += '<button type="button" class="tbar-dir-btn' + (meta && state.dir === secondDir ? ' on' : '') + '" data-dir="' + secondDir + '">' + esc(secondLbl) + '</button>';
      html += '</div>';

      if (!isDefault) {
        html += '<div class="tbar-sort-foot"><button type="button" class="tbar-sort-reset">' + esc(tStr('Reset to default', 'Tetap semula')) + '</button></div>';
      }
      sortMenu.innerHTML = html;
    }

    function openSortMenu() {
      closeFilterMenu(false);
      $$('.tbar-sort-menu:not([hidden])').forEach(function (m) {
        if (m !== sortMenu) {
          m.hidden = true;
        }
      });
      $$('.tbar-sort-btn[aria-expanded="true"]').forEach(function (b) {
        if (b !== sortBtn) b.setAttribute('aria-expanded', 'false');
      });
      renderSortMenu();
      sortMenu.hidden = false;
      positionPopover(sortBtn, sortMenu);
      sortBtn.setAttribute('aria-expanded', 'true');
      var firstInput = sortMenu.querySelector('input:checked, input, button');
      if (firstInput) firstInput.focus();
    }
    function closeSortMenu(restoreFocus) {
      if (sortMenu.hidden) return;
      sortMenu.hidden = true;
      sortBtn.setAttribute('aria-expanded', 'false');
      if (restoreFocus) sortBtn.focus();
    }

    sortBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (sortMenu.hidden) openSortMenu(); else closeSortMenu(false);
    });
    sortMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      var dirBtn = e.target.closest('.tbar-dir-btn');
      if (dirBtn) {
        if (state.col < 0 && sortableCols.length) state.col = sortableCols[0].idx;
        state.dir = parseInt(dirBtn.getAttribute('data-dir'), 10) || 1;
        state.page = 1;
        draw(true);
        renderSortMenu();
        return;
      }
      if (e.target.closest('.tbar-sort-reset')) {
        state.col = defaultCol;
        state.dir = defaultDir;
        state.page = 1;
        draw(true);
        closeSortMenu(true);
      }
    });
    sortMenu.addEventListener('change', function (e) {
      if (e.target && e.target.type === 'radio') {
        var newCol = parseInt(e.target.value, 10);
        var meta = sortableCols.find(function (sc) { return sc.idx === newCol; });
        state.col = newCol;
        state.dir = meta ? dirLabels(meta.type).defaultDir : 1;
        state.page = 1;
        draw(true);
        renderSortMenu();
      }
    });
    sortMenu.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { e.preventDefault(); closeSortMenu(true); }
    });
    document.addEventListener('click', function (e) {
      if (!sortWrap.contains(e.target) && !sortMenu.contains(e.target)) closeSortMenu(false);
      if (!filterWrap.contains(e.target) && !filterMenu.contains(e.target)) closeFilterMenu(false);
    });
    window.addEventListener('resize', function () {
      if (!sortMenu.hidden) positionPopover(sortBtn, sortMenu);
      if (!filterMenu.hidden) positionPopover(filterBtn, filterMenu);
    });
    window.addEventListener('scroll', function (e) {
      if (!sortMenu.hidden && (!e.target || !sortMenu.contains(e.target))) positionPopover(sortBtn, sortMenu);
      if (!filterMenu.hidden && (!e.target || !filterMenu.contains(e.target))) positionPopover(filterBtn, filterMenu);
    }, true);

    var hasOwn = !!host.querySelector('.toolbar, [role=search]') || !!(host.previousElementSibling && host.previousElementSibling.matches('form'));
    var showBar = (showSearch && !hasOwn) || showSort || showFilter;
    if (showBar) {
      if (showSearch && !hasOwn) bar.appendChild(searchWrap);
      if (showFilter) bar.appendChild(filterWrap);
      if (showSort) bar.appendChild(sortWrap);
      bar.appendChild(el('span', 'sp'));
      bar.lastChild.style.flex = '1';
      bar.appendChild(count);
      bar.appendChild(liveRegion);
      host.insertBefore(bar, wrap);
      if (showFilter) host.insertBefore(chipsBar, wrap);
    }

    var pager = el('div', 'pager');
    var per = el('select');
    [5, 10, 25, 50].forEach(function (n) {
      var o = el('option', null, n);
      o.value = n;
      if (n === defaultPer) { o.selected = true; }
      per.appendChild(o);
    });
    per.setAttribute('aria-label', tStr('Rows per page', 'Baris setiap halaman'));
    var range = el('span'), pg = el('span', 'sp'), prev = el('button', 'pb', '&lsaquo;'), next = el('button', 'pb', '&rsaquo;');
    prev.type = next.type = 'button';
    prev.setAttribute('aria-label', tStr('Previous page', 'Halaman sebelumnya'));
    next.setAttribute('aria-label', tStr('Next page', 'Halaman seterusnya'));
    var lbl = el('span', null, tStr('Rows per page', 'Baris setiap halaman'));
    pager.appendChild(lbl);
    pager.appendChild(per);
    pager.appendChild(range);
    pager.appendChild(pg);
    pager.appendChild(prev);
    pager.appendChild(next);
    if (showPager) { host.appendChild(pager); }

    var none = el('tr');
    none.innerHTML = '<td colspan="99" class="muted" style="text-align:center;padding:24px 16px"><strong>' + esc(tStr('No matching records found', 'Tiada rekod sepadan ditemui')) + '</strong><div class="s muted" style="margin-top:4px">' + esc(tStr('No records match your current search or filters.', 'Tiada rekod sepadan dengan carian atau tapisan anda.')) + ' <button type="button" class="btn ghost sm clear-search-btn" style="margin-left:6px;padding:2px 8px;font-size:12px">' + esc(tStr('Clear search & filters', 'Kosongkan carian & tapisan')) + '</button></div></td>';
    none.addEventListener('click', function (e) {
      if (e.target.closest('.clear-search-btn')) {
        search.value = '';
        state.q = '';
        state.filters = {};
        state.page = 1;
        draw(false);
        search.focus();
      }
    });

    var storageKey = 'mcms_tbl_' + location.pathname + '_' + (tbl.id || 'tbl');
    try {
      var saved = JSON.parse(sessionStorage.getItem(storageKey));
      if (saved) {
        if (saved.q && showSearch && !hasOwn) { state.q = saved.q; search.value = saved.q; }
        if (saved.col != null && saved.col > -1 && sortableCols.some(function (sc) { return sc.idx === saved.col; })) {
          state.col = saved.col;
          state.dir = saved.dir === -1 ? -1 : 1;
        }
        if (saved.filters && typeof saved.filters === 'object' && showFilter) {
          var validCols = {};
          filterCols.forEach(function (fc) { validCols[fc.idx] = true; });
          Object.keys(saved.filters).forEach(function (k) {
            if (validCols[k]) state.filters[k] = saved.filters[k];
          });
        }
        if (saved.per != null) { state.per = saved.per; per.value = saved.per; }
        if (saved.page != null) { state.page = saved.page; }
      }
    } catch (e) {}

    function rowMatchesFilters(r) {
      if (!showFilter) return true;
      var now = new Date();
      var todayStart = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
      var todayEnd = todayStart + 86400000 - 1;
      for (var i = 0; i < filterCols.length; i++) {
        var fc = filterCols[i];
        var f = state.filters[fc.idx];
        if (!f) continue;
        if ((fc.kind === 'select' || fc.kind === 'dropdown') && f.values && f.values.length) {
          var rv = cellFilterVal(r, fc.idx, fc.label);
          if (f.values.indexOf(rv) === -1) return false;
        } else if (fc.kind === 'date' && (f.preset || f.from || f.to)) {
          var ms = parseDateMs(cellRaw(r, fc.idx));
          if (f.preset === 'empty') { if (ms !== null) return false; }
          else if (f.preset === 'has') { if (ms === null) return false; }
          else if (f.preset === 'today') { if (ms === null || ms < todayStart || ms > todayEnd) return false; }
          else if (f.preset === '7d') { if (ms === null || ms < (todayStart - 6 * 86400000) || ms > todayEnd) return false; }
          else if (f.preset === '30d') { if (ms === null || ms < (todayStart - 29 * 86400000) || ms > todayEnd) return false; }
          else if (f.preset === 'year') {
            var yStart = Date.UTC(now.getFullYear(), 0, 1);
            var yEnd = Date.UTC(now.getFullYear(), 11, 31, 23, 59, 59);
            if (ms === null || ms < yStart || ms > yEnd) return false;
          } else {
            if (ms === null) return false;
            if (f.from) {
              var fm = parseDateMs(f.from);
              if (fm !== null && ms < fm) return false;
            }
            if (f.to) {
              var tm = parseDateMs(f.to);
              if (tm !== null && ms > tm + 86400000 - 1) return false;
            }
          }
        } else if (fc.kind === 'num' && ((f.min !== '' && f.min != null) || (f.max !== '' && f.max != null))) {
          var nv = parseNumVal(cellRaw(r, fc.idx));
          if (nv === null) return false;
          if (f.min !== '' && f.min != null && nv < parseFloat(f.min)) return false;
          if (f.max !== '' && f.max != null && nv > parseFloat(f.max)) return false;
        } else if (fc.kind === 'text' && f.q && f.q.trim()) {
          var tv = cellFilterVal(r, fc.idx, fc.label).toLowerCase();
          if (tv.indexOf(f.q.trim().toLowerCase()) === -1) return false;
        }
      }
      return true;
    }

    function draw(announce) {
      var q = normSearch(state.q.trim());
      var list = rows.filter(function (r) {
        if (q && normSearch(r.textContent).indexOf(q) === -1) return false;
        return rowMatchesFilters(r);
      });
      if (state.col > -1) {
        var t = types[state.col] || 'text';
        list.sort(function (a, b) {
          var ka = extractKey(a, state.col, t);
          var kb = extractKey(b, state.col, t);
          // Empty values ALWAYS sort last, in both directions
          if (ka.empty && kb.empty) return a.__mcmsOrigIdx - b.__mcmsOrigIdx;
          if (ka.empty) return 1;
          if (kb.empty) return -1;

          var cmp = 0;
          if (t === 'date' || t === 'num') {
            cmp = ka.val < kb.val ? -1 : (ka.val > kb.val ? 1 : 0);
          } else if (t === 'status') {
            cmp = ka.val < kb.val ? -1 : (ka.val > kb.val ? 1 : 0);
            if (cmp === 0 && ka.fallback && kb.fallback) {
              cmp = collator ? collator.compare(ka.fallback, kb.fallback) : ka.fallback.localeCompare(kb.fallback);
            }
          } else {
            cmp = collator ? collator.compare(ka.val, kb.val) : ka.val.localeCompare(kb.val);
          }
          if (cmp !== 0) return cmp * state.dir;
          return a.__mcmsOrigIdx - b.__mcmsOrigIdx;
        });
      } else {
        list.sort(function (a, b) { return a.__mcmsOrigIdx - b.__mcmsOrigIdx; });
      }
      list.forEach(function (r) { body.appendChild(r); });

      if (showSort) {
        renderSortMenu();
      }
      if (showFilter) {
        renderFilterUI();
      }
      if (announce) {
        liveRegion.textContent = (showSort ? sortSummaryText() + ' · ' : '') + list.length + ' ' + tStr('of', 'daripada') + ' ' + rows.length;
      }

      var pages = Math.max(1, Math.ceil(list.length / state.per));
      state.page = Math.min(state.page, pages);
      var from = (state.page - 1) * state.per;
      rows.forEach(function (r) { r.hidden = true; });
      list.slice(showPager ? from : 0, showPager ? from + state.per : undefined).forEach(function (r) { r.hidden = false; });
      if (list.length) { if (none.parentNode) { none.remove(); } } else { body.appendChild(none); }
      var rowWord = function (n) { return isMsLang() ? 'baris' : (n === 1 ? 'row' : 'rows'); };
      count.textContent = rows.length === list.length
        ? rows.length + ' ' + rowWord(rows.length)
        : list.length + ' ' + tStr('of', 'daripada') + ' ' + rows.length + ' ' + rowWord(rows.length);
      range.textContent = list.length ? (from + 1) + '–' + Math.min(from + state.per, list.length) + ' ' + tStr('of', 'daripada') + ' ' + list.length : '0 ' + tStr('of', 'daripada') + ' 0';
      pg.textContent = tStr('Page ', 'Halaman ') + state.page + ' ' + tStr('of', 'daripada') + ' ' + pages;
      prev.disabled = state.page <= 1; next.disabled = state.page >= pages;

      try {
        sessionStorage.setItem(storageKey, JSON.stringify({ q: state.q, col: state.col, dir: state.dir, filters: state.filters, page: state.page, per: state.per }));
      } catch (e) {}
    }
    search.addEventListener('input', function () { state.q = search.value; state.page = 1; draw(false); });
    per.addEventListener('change', function () { state.per = +per.value; state.page = 1; draw(false); });
    prev.addEventListener('click', function () { state.page--; draw(false); });
    next.addEventListener('click', function () { state.page++; draw(false); });
    draw(false);
  }

  /* ---------- toasts (D-75): flash messages, warnings & errors pop out as hover-paused auto-dismissing toasts ---------- */
  function attachAutoDismiss(node, ms, onGone) {
    var timer = null;
    function start(delay) {
      clearTimeout(timer);
      timer = setTimeout(function () {
        node.style.transition = 'opacity .25s ease';
        node.style.opacity = '0';
        setTimeout(function () {
          if (onGone) onGone(); else node.remove();
        }, 260);
      }, delay);
    }
    node.addEventListener('mouseenter', function () { clearTimeout(timer); });
    node.addEventListener('mouseleave', function () { start(3000); });
    start(ms);
    return function () { clearTimeout(timer); if (onGone) onGone(); else node.remove(); };
  }

  function ensureToastBox() {
    var box = $('.toasts');
    if (!box) {
      box = el('div', 'toasts');
      box.setAttribute('aria-live', 'polite');
      document.body.appendChild(box);
    }
    return box;
  }

  function toasts() {
    var flashes = $$('main .status:not(.error):not(.warn)').filter(function (f) { return !f.dataset.toasted; });
    var alerts = $$('main .status.error, main .status.warn').filter(function (f) { return !f.dataset.toasted; });
    if (!flashes.length && !alerts.length) { return; }
    var box = ensureToastBox();

    flashes.forEach(function (f) {
      f.dataset.toasted = '1';
      var t = el('div', 'toast');
      t.appendChild(f);
      f.className = '';
      var x = el('button', 'btn ghost sm', '&times;');
      x.type = 'button';
      x.setAttribute('aria-label', tStr('Dismiss', 'Tutup'));
      x.style.marginLeft = 'auto';
      t.appendChild(x);
      box.appendChild(t);
      var dismiss = attachAutoDismiss(t, 8000);
      x.addEventListener('click', dismiss);
    });

    alerts.forEach(function (a) {
      a.dataset.toasted = '1';
      var isErr = a.classList.contains('error');
      var items = $$('li', a).map(function (li) { return li.textContent.trim(); }).filter(Boolean);
      var title = isErr
        ? tStr('Please check the form', 'Sila semak borang')
        : tStr('Warning', 'Amaran');
      var bodyHtml = '';
      if (items.length) {
        var shown = items.slice(0, 4);
        bodyHtml = '<ul class="toast-list">' + shown.map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('') +
          (items.length > 4 ? '<li class="muted">+' + (items.length - 4) + ' ' + esc(tStr('more', 'lagi')) + '</li>' : '') + '</ul>';
      } else {
        bodyHtml = '<div class="small">' + esc(a.textContent.trim()) + '</div>';
      }

      // 1) Pop out a floating toast so the user sees it immediately regardless of scroll position
      var t = el('div', 'toast ' + (isErr ? 'err' : 'warn'));
      t.setAttribute('role', 'alert');
      t.innerHTML = '<div style="min-width:0;flex:1"><b>' + esc(title) + '</b>' + bodyHtml + '</div>';
      var x = el('button', 'btn ghost sm', '&times;');
      x.type = 'button';
      x.setAttribute('aria-label', tStr('Dismiss', 'Tutup'));
      x.style.marginLeft = 'auto';
      t.appendChild(x);
      box.appendChild(t);
      var dismissToast = attachAutoDismiss(t, 9000);
      x.addEventListener('click', dismissToast);

      // 2) Add a dismiss button + hover-paused auto-dismiss (12s) on the top inline banner so it does not linger forever
      a.style.position = 'relative';
      a.style.paddingRight = '38px';
      var ix = el('button', 'status-dismiss', '&times;');
      ix.type = 'button';
      ix.setAttribute('aria-label', tStr('Dismiss', 'Tutup'));
      a.appendChild(ix);
      var dismissInline = attachAutoDismiss(a, 12000);
      ix.addEventListener('click', dismissInline);
    });
  }

  function showToast(title, body, kind) {
    var box = ensureToastBox();
    var cls = kind === 'error' ? 'err' : (kind || '');
    var t = el('div', 'toast' + (cls ? ' ' + cls : ''));
    if (cls === 'err' || cls === 'warn') t.setAttribute('role', 'alert');
    t.innerHTML = '<div style="min-width:0;flex:1"><b>' + esc(title) + '</b>' + (body ? '<div class="muted small">' + esc(body) + '</div>' : '') + '</div>';
    var x = el('button', 'btn ghost sm', '&times;');
    x.type = 'button'; x.setAttribute('aria-label', tStr('Dismiss', 'Tutup')); x.style.marginLeft = 'auto';
    t.appendChild(x);
    box.appendChild(t);
    var dismiss = attachAutoDismiss(t, cls === 'err' ? 9000 : 6000);
    x.addEventListener('click', dismiss);
  }
  window.mcmsToast = showToast;

  /* ---------- confirmation dialog (UI/UX 4.2): <form data-confirm="Title|Body" [data-reason] [data-danger]> ---------- */
  var last;
  function modal(title, body, opts, ok) {
    last = document.activeElement;
    var wrap = el('div', 'scrim'); wrap.style.cssText = 'position:fixed;inset:0;background:rgba(22,22,22,.5);z-index:70;display:flex;align-items:center;justify-content:center';
    var m = el('div', 'modal' + (opts.danger ? ' danger' : '')); m.setAttribute('role', 'dialog'); m.setAttribute('aria-modal', 'true'); m.setAttribute('aria-label', title);
    m.innerHTML = '<div class="mh"><h2>' + esc(title) + '</h2></div><div class="mb"><p>' + esc(body) + '</p>' +
      (opts.reason ? '<div class="field"><label for="m-reason">Reason (kept in the audit trail)</label><textarea class="input" id="m-reason" rows="3"></textarea></div>' : '') +
      '</div><div class="mf"><button type="button" class="btn ter" data-no>Cancel</button><button type="button" class="btn ' + (opts.danger ? 'danger' : '') + '" data-yes>' + esc(opts.label || 'Confirm') + '</button></div>';
    wrap.appendChild(m); document.body.appendChild(wrap);
    var close = function () { wrap.remove(); if (last) { last.focus(); } };
    $('[data-no]', m).addEventListener('click', close);
    $('[data-yes]', m).addEventListener('click', function () { var r = $('#m-reason', m); var v = r ? r.value : ''; close(); ok(v); });
    wrap.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { close(); }
      if (e.key === 'Tab') { var f = $$('button,textarea', m); var a = document.activeElement; if (e.shiftKey && a === f[0]) { f[f.length - 1].focus(); e.preventDefault(); } else if (!e.shiftKey && a === f[f.length - 1]) { f[0].focus(); e.preventDefault(); } }
    });
    ($('textarea', m) || $('[data-yes]', m)).focus();
  }
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute || !f.hasAttribute('data-confirm') || f.__ok) { return; }
    e.preventDefault();
    var parts = f.getAttribute('data-confirm').split('|');
    var sub = e.submitter;
    modal(parts[0], parts[1] || '', { reason: f.hasAttribute('data-reason'), danger: f.hasAttribute('data-danger'), label: f.getAttribute('data-label') || (sub && sub.textContent.trim()) }, function (reason) {
      if (reason) { var h = el('input'); h.type = 'hidden'; h.name = 'confirm_reason'; h.value = reason; f.appendChild(h); }
      f.__ok = true; if (f.requestSubmit) { f.requestSubmit(sub || undefined); } else { f.submit(); }
    });
  });

  /* ---------- popover menus: <button data-menu="id"> opens #id ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-menu]');
    $$('.pop.open').forEach(function (p) { if (!b || p.id !== b.getAttribute('data-menu')) { p.classList.remove('open'); p.hidden = true; } });
    if (!b) { return; }
    var p = document.getElementById(b.getAttribute('data-menu')); if (!p) { return; }
    var open = p.hidden; p.hidden = !open; p.classList.toggle('open', open); b.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      var wrap = b.closest('.table-wrap') || document.body;
      var wrapRect = wrap.getBoundingClientRect();
      var popRect = p.getBoundingClientRect();
      if (popRect.bottom > wrapRect.bottom && (b.getBoundingClientRect().top - popRect.height) > wrapRect.top) {
        p.style.top = 'auto';
        p.style.bottom = '100%';
        p.style.marginTop = '0';
        p.style.marginBottom = '4px';
      } else {
        p.style.top = '100%';
        p.style.bottom = 'auto';
        p.style.marginTop = '4px';
        p.style.marginBottom = '0';
      }
    }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { $$('.pop.open').forEach(function (p) { p.classList.remove('open'); p.hidden = true; }); } });

  /* ---------- modal dialogs: <button data-modal="id"> opens #modal-id or #id ---------- */
  document.addEventListener('click', function (e) {
    var mb = e.target.closest('[data-modal]');
    if (mb) {
      var mid = mb.getAttribute('data-modal');
      var m = document.getElementById('modal-' + mid) || document.getElementById(mid);
      if (m) {
        e.preventDefault();
        m.style.display = 'flex';
        m.removeAttribute('hidden');
        var input = m.querySelector('input:not([type=hidden]), select, textarea, button.btn:not([data-close])');
        if (input) { input.focus(); }
        return;
      }
    }

    var cb = e.target.closest('[data-close]');
    if (cb) {
      var scrim = cb.closest('.scrim');
      if (scrim) {
        e.preventDefault();
        scrim.style.display = 'none';
        scrim.setAttribute('hidden', '');
        return;
      }
    }

    if (e.target.classList && e.target.classList.contains('scrim')) {
      e.target.style.display = 'none';
      e.target.setAttribute('hidden', '');
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      $$('.scrim').forEach(function (s) {
        s.style.display = 'none';
        s.setAttribute('hidden', '');
      });
    }
  });

  /* ---------- validation (UI/UX 4.3): a required field that is empty is marked ---------- */
  document.addEventListener('invalid', function (e) { e.target.classList.add('bad'); }, true);
  document.addEventListener('input', function (e) { e.target.classList && e.target.classList.remove('bad'); });

  /* ---------- a row that holds one place to go is clickable all over, not only on its link ---------- */
  function rowLinks() {
    $$('main tr[data-href]').forEach(function (tr) {
      tr.classList.add('rowlink');
      tr.addEventListener('click', function (e) { if (!e.target.closest('a, button, input, select, textarea, label, summary')) { location.href = tr.getAttribute('data-href'); } });
    });
    $$('main table:not([data-plain]) tbody tr').forEach(function (tr) {
      if (tr.hasAttribute('data-href') || tr.querySelector('input:not([type=hidden]), select, textarea, button, form')) { return; }
      var links = $$('a[href]', tr).filter(function (a) { return !a.hasAttribute('download') && a.target !== '_blank' && !/^(javascript:|#)/.test(a.getAttribute('href')); });
      if (!links.length) { return; }
      tr.classList.add('rowlink');
      tr.addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, textarea, label, summary')) { return; }
        links[0].click();
      });
    });
  }

  /* ---------- a select that shows one case at a time: <select data-switch="g"><option value="1">..</option></select> and <div data-case="g" data-id="1"> ---------- */
  function switches() {
    $$('select[data-switch]').forEach(function (sel) {
      var g = sel.getAttribute('data-switch');
      function show() { $$('[data-case="' + g + '"]').forEach(function (c) { c.hidden = c.getAttribute('data-id') !== sel.value; }); }
      sel.addEventListener('change', show); show();
    });
  }

  /* ---------- tabs: <div data-tabs><nav class="tabs"><button data-tab="a">..</button></nav><div data-pane="a">..</div></div>; without script every pane shows ---------- */
  function tabs(root) {
    var btns = $$('[data-tab]', root), panes = $$('[data-pane]', root);
    function open(id) {
      panes.forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== id; });
      btns.forEach(function (b) { var on = b.getAttribute('data-tab') === id; b.classList.toggle('on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
    }
    btns.forEach(function (b) { b.type = 'button'; b.addEventListener('click', function () { open(b.getAttribute('data-tab')); }); });
    if (navigator.webdriver) return;
    window.addEventListener('hashchange', function () { var h = (location.hash.slice(1) || '').toLowerCase(); var a = { 'notifications': 'notif', 'sessions': 'security', 'signin': 'security' }; if (a[h]) h = a[h]; if ($('[data-pane="' + h + '"]', root)) open(h); });
    var first = panes.filter(function (p) { return !p.hasAttribute('data-empty'); })[0] || panes[0];
    var hash = (location.hash.slice(1) || '').toLowerCase();
    var param = (new URLSearchParams(location.search)).get('tab') || '';
    var target = param || hash;
    var aliases = { 'notifications': 'notif', 'sessions': 'security', 'signin': 'security' };
    if (aliases[target]) { target = aliases[target]; }
    var targetPane = target ? $('[data-pane="' + target + '"]', root) : null;
    var defaultTab = root.getAttribute('data-default-tab');
    var fallback = (defaultTab && $('[data-pane="' + defaultTab + '"]', root))
      ? defaultTab
      : (root.hasAttribute('data-auto') ? first.getAttribute('data-pane') : btns[0].getAttribute('data-tab'));
    open(targetPane ? target : fallback);
  }

  /* ---------- auto-filtering forms (data-auto-filter): live update without clicking apply ---------- */
  function autoFilterForms() {
    var forms = $$('form[data-auto-filter]');
    if (!forms.length) { return; }

    forms.forEach(function (form) {
      var timer = null;
      var targetSelector = form.getAttribute('data-target') || '#catalogue-results';
      var countSelector = form.getAttribute('data-count') || '#catalogue-count';

      function cleanQueryString() {
        var fd = new FormData(form);
        var params = new URLSearchParams();
        var it = fd.entries();
        var item = it.next();
        while (!item.done) {
          var key = item.value[0];
          var val = (item.value[1] || '').trim();
          if (key === 'free') {
            if (val === '1') { params.append(key, '1'); }
          } else if (val !== '') {
            params.append(key, val);
          }
          item = it.next();
        }
        return params.toString();
      }

      function doFilter(url) {
        if (!url) {
          var qs = cleanQueryString();
          var action = form.getAttribute('action') || window.location.pathname;
          url = action + (qs ? '?' + qs : '');
        }

        var resultsContainer = $(targetSelector);
        if (resultsContainer) {
          resultsContainer.style.opacity = '0.55';
          resultsContainer.style.transition = 'opacity 0.15s ease';
        }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (res) {
            if (!res.ok) { throw new Error('HTTP ' + res.status); }
            return res.text();
          })
          .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');

            var newResults = $(targetSelector, doc);
            var currentResults = $(targetSelector);
            if (newResults && currentResults) {
              currentResults.innerHTML = newResults.innerHTML;
              currentResults.style.opacity = '1';
              $$('table', currentResults).forEach(enhance);
            }

            var newCount = $(countSelector, doc);
            var currentCount = $(countSelector);
            if (newCount && currentCount) {
              currentCount.innerHTML = newCount.innerHTML;
            }

            if (window.history && window.history.pushState) {
              window.history.pushState({ path: url }, '', url);
            }
          })
          .catch(function () {
            if (resultsContainer) { resultsContainer.style.opacity = '1'; }
            form.submit();
          });
      }

      // 1. Immediate trigger on select, checkbox, radio, date
      form.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.matches('select, input[type=checkbox], input[type=radio], input[type=date]')) {
          if (timer) { clearTimeout(timer); }
          doFilter();
        }
      });

      // 2. Debounced trigger on text/search/number inputs (auto-updates while typing)
      form.addEventListener('input', function (e) {
        var t = e.target;
        if (t && t.matches('input[type=search], input[type=text], input[type=number]')) {
          if (timer) { clearTimeout(timer); }
          timer = setTimeout(function () {
            doFilter();
          }, 350);
        }
      });

      // 3. Keep normal submit button functional (still works without double-submitting)
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (timer) { clearTimeout(timer); }
        doFilter();
      });

      // 4. Handle pagination links inside the filtered results container
      document.addEventListener('click', function (e) {
        var link = e.target.closest(targetSelector + ' .pagination-bar a, ' + targetSelector + ' .pagination a');
        if (link && link.href) {
          e.preventDefault();
          doFilter(link.href);
          var resElem = $(targetSelector);
          if (resElem) {
            resElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      });

      // 5. Handle browser back / forward buttons
      window.addEventListener('popstate', function () {
        var currentResults = $(targetSelector);
        if (currentResults) {
          fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
              var doc = (new DOMParser()).parseFromString(html, 'text/html');
              var newRes = $(targetSelector, doc);
              if (newRes) {
                currentResults.innerHTML = newRes.innerHTML;
                $$('table', currentResults).forEach(enhance);
              }
              var newCnt = $(countSelector, doc);
              var curCnt = $(countSelector);
              if (newCnt && curCnt) { curCnt.innerHTML = newCnt.innerHTML; }
            });
        }
      });
    });
  }
  /* ---------- sidebar toggle (collapse / expand & mobile drawer) ---------- */
  function setupSidebar() {
    var app = $('.app');
    var side = $('.side');
    var navToggle = $('#nav-toggle');
    var sideCollapseBtn = $('#side-collapse-btn');
    var backdrop = $('.side-backdrop');
    if (!app || !side) return;

    try {
      if (localStorage.getItem('mcms_side_collapsed') === '1' && window.innerWidth > 900) {
        app.classList.add('side-collapsed');
        side.classList.add('collapsed');
      }
    } catch (e) {}

    function toggleDesktop() {
      var isCollapsed = app.classList.toggle('side-collapsed');
      side.classList.toggle('collapsed', isCollapsed);
      if (isCollapsed) {
        document.documentElement.classList.add('side-collapsed');
      } else {
        document.documentElement.classList.remove('side-collapsed');
      }
      try {
        localStorage.setItem('mcms_side_collapsed', isCollapsed ? '1' : '0');
      } catch (e) {}
    }

    function toggleMobile() {
      app.classList.toggle('drawer-open');
    }

    function closeMobile() {
      app.classList.remove('drawer-open');
    }

    if (navToggle) {
      navToggle.addEventListener('click', function () {
        if (window.innerWidth <= 900) {
          toggleMobile();
        } else {
          toggleDesktop();
        }
      });
    }

    if (sideCollapseBtn) {
      sideCollapseBtn.addEventListener('click', toggleDesktop);
    }

    var pullTabBtn = $('#btnSidebarPullTab');
    if (pullTabBtn) {
      pullTabBtn.addEventListener('click', toggleDesktop);
    }

    window.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        var tag = (document.activeElement && document.activeElement.tagName) || '';
        if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
          e.preventDefault();
          toggleDesktop();
        }
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', closeMobile);
    }

    $$('.side a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (window.innerWidth <= 900) {
          closeMobile();
        }
      });
    });
  }

  /* ---------- theme handling (Linear-Precision Light & Dark) ---------- */
  function setupTheme() {
    try {
      var saved = localStorage.getItem('mcms_theme');
      if (saved === 'dark' || saved === 'light') {
        document.documentElement.setAttribute('data-theme', saved);
        if (saved === 'dark') document.body.classList.add('theme-dark');
        else document.body.classList.remove('theme-dark');
      }
    } catch (e) {}

    var toggleBtns = $$('#theme-toggle-btn');
    toggleBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        if (next === 'dark') document.body.classList.add('theme-dark');
        else document.body.classList.remove('theme-dark');
        try { localStorage.setItem('mcms_theme', next); } catch (e) {}

        var tokenMeta = $('meta[name="csrf-token"]');
        var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
        fetch('/theme', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ theme: next })
        }).catch(function () {});
      });
    });
  }

  /* ---------- shared tabbed checklist controller (WAI-ARIA tabs + live progress + zero data loss) ---------- */
  function initChecklistTabs(root) {
    var tabButtons = $$('[data-tab-btn]', root);
    var panels = $$('[data-tab-panel]', root);
    if (!tabButtons.length || !panels.length) { return; }

    var mobileSelect = $('[data-tab-select]', root);
    var mobileStepEl = $('[data-mobile-step]', root);
    var liveRegion = $('[data-tab-live]', root);
    var overallCountEl = $('[data-overall-count]', root);
    var overallBarEl = $('[data-overall-bar]', root);
    var dirtyNotice = $('[data-dirty-notice]', root);
    var dirtyBadge = $('[data-dirty-badge]', root);
    var tabsShell = $('[data-tabs-shell]', root);
    var tabsBar = $('[data-tabs-bar]', root);
    var scrollLeftBtn = $('[data-tabs-scroll="left"]', root);
    var scrollRightBtn = $('[data-tabs-scroll="right"]', root);
    var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var tabKeys = tabButtons.map(function (b) { return b.getAttribute('data-tab-btn'); });
    var isDirty = false;
    var submitting = false;

    function panelByKey(key) {
      return panels.find(function (p) { return p.getAttribute('data-tab-panel') === key; }) || null;
    }

    function updateScrollControls() {
      if (!tabsShell || !tabsBar) { return; }
      var maxScroll = tabsBar.scrollWidth - tabsBar.clientWidth;
      var overflowing = maxScroll > 2;
      var canLeft = overflowing && tabsBar.scrollLeft > 2;
      var canRight = overflowing && tabsBar.scrollLeft < (maxScroll - 2);
      tabsShell.classList.toggle('is-overflowing', overflowing);
      tabsShell.classList.toggle('can-scroll-left', canLeft);
      tabsShell.classList.toggle('can-scroll-right', canRight);
      if (scrollLeftBtn) { scrollLeftBtn.hidden = !canLeft; }
      if (scrollRightBtn) { scrollRightBtn.hidden = !canRight; }
    }

    if (scrollLeftBtn && tabsBar) {
      scrollLeftBtn.addEventListener('click', function () {
        var step = Math.max(160, Math.round(tabsBar.clientWidth * 0.6));
        try { tabsBar.scrollBy({ left: -step, behavior: reducedMotion ? 'auto' : 'smooth' }); } catch (e) { tabsBar.scrollLeft -= step; }
      });
    }
    if (scrollRightBtn && tabsBar) {
      scrollRightBtn.addEventListener('click', function () {
        var step = Math.max(160, Math.round(tabsBar.clientWidth * 0.6));
        try { tabsBar.scrollBy({ left: step, behavior: reducedMotion ? 'auto' : 'smooth' }); } catch (e) { tabsBar.scrollLeft += step; }
      });
    }
    if (tabsBar) {
      tabsBar.addEventListener('scroll', updateScrollControls, { passive: true });
      if (window.ResizeObserver) {
        try {
          var ro = new ResizeObserver(updateScrollControls);
          ro.observe(tabsBar);
        } catch (e) {}
      }
      window.addEventListener('resize', updateScrollControls, { passive: true });
    }

    function scrollTabIntoBar(btn, smooth) {
      if (!tabsBar || !btn) { return; }
      var barRect = tabsBar.getBoundingClientRect();
      var btnRect = btn.getBoundingClientRect();
      if (barRect.width <= 0) { return; }
      var leftDiff = btnRect.left - barRect.left;
      var rightDiff = btnRect.right - barRect.right;
      var targetLeft = null;
      if (leftDiff < 8) {
        targetLeft = Math.max(0, tabsBar.scrollLeft + leftDiff - 12);
      } else if (rightDiff > -8) {
        targetLeft = tabsBar.scrollLeft + rightDiff + 12;
      }
      if (targetLeft !== null) {
        try {
          tabsBar.scrollTo({ left: targetLeft, behavior: (smooth && !reducedMotion) ? 'smooth' : 'auto' });
        } catch (e) {
          tabsBar.scrollLeft = targetLeft;
        }
      }
    }

    function activate(key, opts) {
      opts = opts || {};
      if (tabKeys.indexOf(key) === -1) { key = tabKeys[0]; }
      tabButtons.forEach(function (btn) {
        var k = btn.getAttribute('data-tab-btn');
        var active = k === key;
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
        btn.setAttribute('tabindex', active ? '0' : '-1');
        if (active && opts.scrollTab !== false) {
          scrollTabIntoBar(btn, opts.smoothScroll !== false);
        }
        if (active && opts.focusTab) {
          btn.focus();
        }
      });
      panels.forEach(function (p) {
        var active = p.getAttribute('data-tab-panel') === key;
        p.hidden = !active;
      });
      if (mobileSelect && mobileSelect.value !== key) {
        mobileSelect.value = key;
      }
      if (mobileStepEl) {
        var stepIdx = tabKeys.indexOf(key) + 1;
        var tpl = mobileStepEl.getAttribute('data-step-template');
        if (tpl && stepIdx > 0) {
          mobileStepEl.textContent = tpl.replace(':current', String(stepIdx));
        }
      }
      $$('[data-active-tab-input]', root).forEach(function (inp) {
        inp.value = key;
      });
      if (opts.updateHash !== false) {
        try {
          if (history && history.replaceState) {
            history.replaceState(null, '', '#tab=' + encodeURIComponent(key));
          }
        } catch (e) {}
      }
      if (liveRegion && opts.announce) {
        var activeBtn = tabButtons.find(function (b) { return b.getAttribute('data-tab-btn') === key; });
        if (activeBtn) {
          var lbl = $('.ct-label', activeBtn);
          var sr = $('[data-tab-sr]', activeBtn);
          liveRegion.textContent = (lbl ? lbl.textContent.trim() : key) + (sr ? sr.textContent : '') + ' — shown';
        }
      }
      if (opts.scrollPanel) {
        var activePanel = panelByKey(key);
        if (activePanel && activePanel.scrollIntoView) {
          try { activePanel.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' }); } catch (e) {}
        }
      }
      if (window.requestAnimationFrame) {
        window.requestAnimationFrame(updateScrollControls);
      } else {
        updateScrollControls();
      }
    }

    function recomputeProgress() {
      var overallDone = 0;
      var overallTotal = 0;
      panels.forEach(function (panel) {
        var key = panel.getAttribute('data-tab-panel');
        if (panel.getAttribute('data-countable') !== '1') { return; }
        var rows = $$('[data-checklist-row]', panel);
        var total = rows.length;
        var done = 0;
        var hasErr = false;
        rows.forEach(function (row) {
          var mode = row.getAttribute('data-row-mode') || 'readonly';
          var rowDone = false;
          if (mode === 'status') {
            var sel = $('select', row);
            rowDone = sel ? (sel.value !== 'not_yet') : (row.getAttribute('data-row-done') === '1');
          } else if (mode === 'accept') {
            var cb = $('input[type="checkbox"]', row);
            if (cb) {
              rowDone = !!cb.checked;
            } else {
              rowDone = row.getAttribute('data-row-done') === '1';
            }
          } else if (mode === 'qv') {
            if (row.getAttribute('data-row-linked') === '1') {
              rowDone = true;
            } else {
              var qSel = $('select', row);
              rowDone = qSel ? (qSel.value !== 'not_yet') : (row.getAttribute('data-row-done') === '1');
            }
          } else {
            rowDone = row.getAttribute('data-row-done') === '1';
          }
          if (rowDone) {
            done++;
            if (row.classList.contains('row-err')) {
              row.classList.remove('row-err');
            }
          }
          if (row.classList.contains('row-err')) {
            hasErr = true;
          }
        });
        overallDone += done;
        overallTotal += total;

        var indState = hasErr ? 'err' : (done === total ? 'complete' : (done > 0 ? 'in-progress' : 'not-started'));
        var badge = $('[data-tab-badge="' + key + '"]', root);
        var ind = $('[data-tab-ind="' + key + '"]', root);
        var srEl = $('[data-tab-sr="' + key + '"]', root);
        var btn = tabButtons.find(function (b) { return b.getAttribute('data-tab-btn') === key; });

        if (ind && total > 0) {
          ind.classList.remove('is-not-started', 'is-in-progress', 'is-complete', 'is-warn', 'is-err', 'is-ready', 'is-disabled');
          ind.classList.add('is-' + indState);
        }
        if (badge && total > 0) {
          badge.textContent = done + '/' + total;
          badge.classList.remove('is-ok', 'is-warn', 'is-err', 'is-n', 'is-ready');
          if (hasErr) {
            badge.classList.add('is-err');
          }
          // Completed check tabs display the quiet green checkmark only (count stays in DOM & sr-only)
          badge.hidden = (indState === 'complete');
        }
        $$('[data-section-summary="' + key + '"]', root).forEach(function (sb) {
          var sfx = sb.getAttribute('data-summary-suffix') || '';
          sb.textContent = done + ' / ' + total + (sfx ? ' ' + sfx : '');
          sb.classList.remove('ok', 'warn', 'err');
          sb.classList.add(hasErr ? 'err' : (done === total ? 'ok' : 'warn'));
        });
        if (srEl && total > 0) {
          var srText = hasErr
            ? ', requires attention, ' + done + ' of ' + total
            : (done === total
              ? ', complete, ' + done + ' of ' + total
              : (done > 0 ? ', in progress, ' + done + ' of ' + total : ', not started, 0 of ' + total));
          srEl.textContent = srText;
        }
        if (btn) {
          btn.classList.toggle('has-err', hasErr);
        }
        if (mobileSelect) {
          var opt = $('option[value="' + key + '"]', mobileSelect);
          if (opt && opt.getAttribute('data-base-label')) {
            opt.textContent = opt.getAttribute('data-base-label') + ' (' + done + '/' + total + ')';
          }
        }
      });

      if (overallCountEl && overallTotal > 0) {
        var suffix = overallCountEl.getAttribute('data-count-suffix') || '';
        overallCountEl.textContent = overallDone + ' / ' + overallTotal + (suffix ? ' ' + suffix : '');
      }
      if (overallBarEl && overallTotal > 0) {
        var pct = Math.round((overallDone / overallTotal) * 100);
        overallBarEl.style.width = pct + '%';
        overallBarEl.classList.toggle('is-complete', overallDone === overallTotal);
      }
    }

    function markDirty(fromEvidence) {
      isDirty = true;
      if (dirtyBadge) { dirtyBadge.hidden = false; }
      if (fromEvidence && dirtyNotice) { dirtyNotice.hidden = false; }
    }

    tabButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        activate(btn.getAttribute('data-tab-btn'), { announce: true });
      });
      btn.addEventListener('keydown', function (e) {
        var idx = tabKeys.indexOf(btn.getAttribute('data-tab-btn'));
        var targetIdx = -1;
        if (e.key === 'ArrowRight') { targetIdx = (idx + 1) % tabKeys.length; }
        else if (e.key === 'ArrowLeft') { targetIdx = (idx - 1 + tabKeys.length) % tabKeys.length; }
        else if (e.key === 'Home') { targetIdx = 0; }
        else if (e.key === 'End') { targetIdx = tabKeys.length - 1; }
        if (targetIdx !== -1) {
          e.preventDefault();
          activate(tabKeys[targetIdx], { focusTab: true, announce: true });
        }
      });
    });

    if (mobileSelect) {
      mobileSelect.addEventListener('change', function () {
        activate(mobileSelect.value, { announce: true });
      });
    }

    $$('[data-tab-prev]', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cur = btn.closest('[data-tab-panel]');
        var idx = cur ? tabKeys.indexOf(cur.getAttribute('data-tab-panel')) : 0;
        if (idx > 0) { activate(tabKeys[idx - 1], { announce: true, scrollPanel: true }); }
      });
    });

    $$('[data-tab-next]', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cur = btn.closest('[data-tab-panel]');
        var idx = cur ? tabKeys.indexOf(cur.getAttribute('data-tab-panel')) : 0;
        if (idx >= 0 && idx < tabKeys.length - 1) { activate(tabKeys[idx + 1], { announce: true, scrollPanel: true }); }
      });
    });

    $$('[data-accept-tab]', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var key = btn.getAttribute('data-accept-tab');
        var panel = panelByKey(key);
        if (!panel) { return; }
        var boxes = $$('input[type="checkbox"][name^="accept["]', panel);
        var allChecked = boxes.length > 0 && boxes.every(function (b) { return b.checked; });
        boxes.forEach(function (b) { b.checked = !allChecked; });
        markDirty(false);
        recomputeProgress();
      });
    });

    $$('[data-jump-tab]', document).forEach(function (link) {
      link.addEventListener('click', function (e) {
        var key = link.getAttribute('data-jump-tab');
        if (tabKeys.indexOf(key) !== -1) {
          e.preventDefault();
          activate(key, { announce: true, scrollPanel: true });
        }
      });
    });

    root.addEventListener('change', function (e) {
      var target = e.target;
      if (!target || target.matches('[data-tab-select]')) { return; }
      if (target.matches('select, input, textarea')) {
        var inEvidence = !!target.closest('[data-evidence-panel="1"]');
        markDirty(inEvidence);
        recomputeProgress();
      }
    });

    $$('form', root).forEach(function (f) {
      f.addEventListener('submit', function () {
        submitting = true;
        isDirty = false;
      });
    });
    if (root.closest('form')) {
      root.closest('form').addEventListener('submit', function () {
        submitting = true;
        isDirty = false;
      });
    }

    window.addEventListener('beforeunload', function (e) {
      if (isDirty && !submitting) {
        e.preventDefault();
        e.returnValue = '';
      }
    });

    // Determine initial tab: server error priority > URL hash > server-computed default tab
    var initialKey = root.getAttribute('data-default-tab') || tabKeys[0];
    var hasServerErr = root.getAttribute('data-has-server-error') === '1';
    if (!hasServerErr && location.hash && location.hash.indexOf('#tab=') === 0) {
      var hashKey = decodeURIComponent(location.hash.slice(5));
      if (tabKeys.indexOf(hashKey) !== -1) {
        initialKey = hashKey;
      }
    }

    recomputeProgress();
    activate(initialKey, { updateHash: false, scrollTab: true, smoothScroll: false });
    updateScrollControls();
  }

  /* ---------- Rule D-68: In-Place Async Mutations + Global Scroll & Tab Restoration ---------- */
  var SCROLL_STORE_KEY = 'mcms_scroll_restore';

  function getActiveTabKey() {
    var activeBtn = $('[data-tabs] [data-tab].on, [data-tabs] [data-tab][aria-selected="true"], [data-checklist-tabs] [role="tab"][aria-selected="true"]');
    return activeBtn ? (activeBtn.getAttribute('data-tab') || activeBtn.getAttribute('data-tab-key') || '') : '';
  }

  function restoreActiveTab(tabKey) {
    if (!tabKey) return;
    var btn = $('[data-tabs] [data-tab="' + tabKey + '"], [data-checklist-tabs] [data-tab-key="' + tabKey + '"]');
    if (btn && !btn.classList.contains('on') && btn.getAttribute('aria-selected') !== 'true') {
      btn.click();
    }
  }

  function saveScrollState() {
    try {
      sessionStorage.setItem(SCROLL_STORE_KEY, JSON.stringify({
        path: location.pathname + location.search,
        scrollY: window.scrollY || window.pageYOffset || 0,
        activeTab: getActiveTabKey(),
        ts: Date.now()
      }));
    } catch (e) {}
  }

  function restoreScrollState() {
    try {
      var raw = sessionStorage.getItem(SCROLL_STORE_KEY);
      if (!raw) return;
      sessionStorage.removeItem(SCROLL_STORE_KEY);
      var st = JSON.parse(raw);
      if (!st || (Date.now() - (st.ts || 0)) > 15000) return;
      if (st.path !== (location.pathname + location.search)) return;
      if (st.activeTab) {
        restoreActiveTab(st.activeTab);
      }
      if (typeof st.scrollY === 'number' && st.scrollY > 0) {
        requestAnimationFrame(function () {
          window.scrollTo({ top: st.scrollY, behavior: 'instant' });
        });
      }
    } catch (e) {}
  }

  function reinitMain() {
    $$('[data-tabs]').forEach(tabs);
    $$('[data-checklist-tabs]').forEach(initChecklistTabs);
    $$('main table').forEach(enhance);
    rowLinks();
    switches();
    toasts();
    autoFilterForms();
  }

  function executeInlineScripts(container) {
    $$('script', container).forEach(function (oldScript) {
      if (oldScript.src) return;
      var s = document.createElement('script');
      s.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(s, oldScript);
    });
  }

  function focusAddedInput(mainEl, submitterName, submitterValue) {
    if (!mainEl) return;
    var target = null;
    if (submitterName === 'add_activity_module_id' && submitterValue) {
      var modEl = mainEl.querySelector('[data-module-id="' + submitterValue + '"]');
      var searchScope = modEl || mainEl;
      var actInputs = $$('input[name^="activities["][name$="[name_en]"]', searchScope);
      if (!actInputs.length && modEl && modEl.nextElementSibling) {
        actInputs = $$('input[name^="activities["][name$="[name_en]"]', modEl.nextElementSibling);
      }
      target = actInputs[actInputs.length - 1] || null;
    } else if (submitterValue === 'add') {
      var rowInputs = $$('input[name^="rows["][name$="[name_en]"], input[name^="rows["][name$="[text_en]"]', mainEl);
      target = rowInputs[rowInputs.length - 1] || null;
    }
    if (target) {
      try {
        target.focus({ preventScroll: true });
        if (target.select) target.select();
      } catch (e) {}
    }
  }

  function setupInPlaceMutations() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || form.tagName !== 'FORM') return;
      var method = (form.getAttribute('method') || 'get').toLowerCase();
      if (method !== 'post') return;
      if (form.hasAttribute('data-confirm') && !form.__ok) return;

      // Always save scroll + active tab for any same-page POST fallback
      saveScrollState();

      // In-place async swap for forms marked with data-inplace or data-autosave
      if (!form.hasAttribute('data-inplace') && !form.hasAttribute('data-autosave')) return;
      if (form.hasAttribute('data-no-inplace')) return;

      e.preventDefault();
      if (form.dataset.mutating === '1') return;
      form.dataset.mutating = '1';

      // Cancel any pending autosave timer so explicit mutations never race with autosave
      form.dispatchEvent(new CustomEvent('mcms:cancel-autosave', { bubbles: true }));

      var submitter = e.submitter || null;
      var submitterName = submitter ? (submitter.getAttribute('name') || '') : '';
      var submitterValue = submitter ? (submitter.getAttribute('value') || '') : '';
      var prevScrollY = window.scrollY || window.pageYOffset || 0;
      var prevTab = getActiveTabKey();

      var fd = new FormData(form);
      if (submitterName) {
        fd.append(submitterName, submitterValue);
      }

      if (submitter) {
        submitter.disabled = true;
        submitter.style.opacity = '0.65';
      }

      var actionUrl = form.getAttribute('action') || location.href;
      fetch(actionUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        redirect: 'follow',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html'
        }
      })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var finalUrl = res.url ? new URL(res.url, location.origin) : null;
        if (finalUrl && (finalUrl.pathname !== location.pathname || finalUrl.search !== location.search)) {
          window.location.href = res.url;
          return null;
        }
        return res.text();
      })
      .then(function (html) {
        if (html === null) return;
        var doc = (new DOMParser()).parseFromString(html, 'text/html');
        var newMain = $('main', doc);
        var curMain = $('main');
        if (!newMain || !curMain) {
          window.location.reload();
          return;
        }

        // Remove stale toasts before swapping so new flash toast renders cleanly
        $$('.toasts').forEach(function (t) { t.remove(); });
        try { sessionStorage.removeItem(SCROLL_STORE_KEY); } catch (err) {}

        curMain.innerHTML = newMain.innerHTML;
        executeInlineScripts(curMain);
        reinitMain();

        if (prevTab) {
          restoreActiveTab(prevTab);
        }
        requestAnimationFrame(function () {
          window.scrollTo({ top: prevScrollY, behavior: 'instant' });
          focusAddedInput(curMain, submitterName, submitterValue);
        });
      })
      .catch(function () {
        form.dataset.mutating = '';
        if (submitter) {
          submitter.disabled = false;
          submitter.style.opacity = '';
        }
        // Fallback to standard submit if fetch fails
        form.setAttribute('data-no-inplace', '1');
        if (form.requestSubmit && submitter) {
          form.requestSubmit(submitter);
        } else {
          form.submit();
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    reinitMain();
    setupSidebar();
    setupTheme();
    setupInPlaceMutations();
    restoreScrollState();
  });
})();
