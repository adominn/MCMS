/* MCMS front end: the behaviour of the approved design prototype (core.js) on server-rendered pages.
   Everything works without it; this only adds sorting, search and paging to tables, dialogs, toasts and menus. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var el = function (tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; };
  var esc = function (t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var SORT = '<svg class="si" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M5 6l3-3 3 3M5 10l3 3 3-3"/></svg>';

  /* ---------- tables: search, sortable headers, rows per page, pager (UI/UX 4.5) ---------- */
  function key(txt, type) {
    txt = txt.trim();
    if (type === 'num') { return parseFloat(txt.replace(/[^0-9.\-]/g, '')) || 0; }
    if (type === 'date') { var m = txt.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/); return m ? Date.UTC(+m[3], +m[2] - 1, +m[1]) : 0; }
    return txt.toLowerCase();
  }
  function colType(rows, i) {
    var cells = rows.map(function (r) { return (r.cells[i] ? r.cells[i].textContent.trim() : ''); }).filter(Boolean);
    if (!cells.length) { return 'text'; }
    if (cells.every(function (c) { return /\d{1,2}\/\d{1,2}\/\d{4}/.test(c); })) { return 'date'; }
    if (cells.every(function (c) { return /^(RM\s*)?-?[\d,]+(\.\d+)?%?$/.test(c); })) { return 'num'; }
    return 'text';
  }
  function enhance(tbl) {
    var body = tbl.tBodies[0];
    if (!body || tbl.hasAttribute('data-plain') || tbl.closest('[data-plain]') || tbl.querySelector('[rowspan], tbody input:not([type=hidden]), tbody select, tbody textarea') || tbl.tHead === null) { return; }
    var rows = $$('tr', body).filter(function (r) { return r.parentNode === body; });
    if (!rows.length) { return; }
    if (rows.length === 1 && rows[0].cells.length === 1 && (rows[0].cells[0].colSpan > 1 || rows[0].cells[0].classList.contains('muted'))) { return; }
    var host = tbl.parentNode;
    if (!host.classList.contains('panel')) { var p = el('div', 'panel'); host.insertBefore(p, tbl); p.appendChild(tbl); host = p; }

    var wrap = tbl.parentNode;
    if (!wrap.classList.contains('table-wrap') && !tbl.hasAttribute('data-no-wrap')) {
      wrap = el('div', 'table-wrap');
      wrap.style.cssText = 'overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%';
      tbl.parentNode.insertBefore(wrap, tbl);
      wrap.appendChild(tbl);
    }

    var state = { q: '', per: 10, page: 1, col: -1, dir: 1 };
    var types = [];
    var showSearch = !tbl.hasAttribute('data-no-search');
    var showPager = rows.length > 5 || tbl.hasAttribute('data-paginated');

    var bar = el('div', 'tbar'), count = el('span', 'small muted');
    var searchWrap = el('div', 'tbar-search-wrap');
    searchWrap.style.cssText = 'position:relative;display:inline-flex;align-items:center';
    var search = el('input', 'input');
    search.type = 'search';
    search.setAttribute('aria-label', 'Search this table');
    search.placeholder = tbl.getAttribute('data-search-placeholder') || 'Search records...';
    searchWrap.appendChild(search);

    var hasOwn = !!host.querySelector('.toolbar, [role=search]') || !!(host.previousElementSibling && host.previousElementSibling.matches('form'));
    if (showSearch && !hasOwn) {
      bar.appendChild(searchWrap);
      bar.appendChild(el('span', 'sp'));
      bar.lastChild.style.flex = '1';
      bar.appendChild(count);
      host.insertBefore(bar, wrap);
    }

    var pager = el('div', 'pager');
    var per = el('select');
    [5, 10, 25, 50].forEach(function (n) {
      var o = el('option', null, n);
      o.value = n;
      if (n === 10) { o.selected = true; }
      per.appendChild(o);
    });
    per.setAttribute('aria-label', 'Rows per page');
    var range = el('span'), pg = el('span', 'sp'), prev = el('button', 'pb', '&lsaquo;'), next = el('button', 'pb', '&rsaquo;');
    prev.type = next.type = 'button';
    prev.setAttribute('aria-label', 'Previous page');
    next.setAttribute('aria-label', 'Next page');
    var lbl = el('span', null, 'Rows per page');
    pager.appendChild(lbl);
    pager.appendChild(per);
    pager.appendChild(range);
    pager.appendChild(pg);
    pager.appendChild(prev);
    pager.appendChild(next);
    if (showPager) { host.appendChild(pager); }

    var none = el('tr');
    none.innerHTML = '<td colspan="99" class="muted" style="text-align:center;padding:24px 16px"><div style="font-size:20px;margin-bottom:6px;opacity:0.4"><svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" style="vertical-align:middle"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg></div><strong>No matching records found</strong><div class="s muted" style="margin-top:4px">No records match your search query. <button type="button" class="btn ghost sm clear-search-btn" style="margin-left:6px;padding:2px 8px;font-size:12px">Clear search</button></div></td>';
    none.addEventListener('click', function (e) {
      if (e.target.closest('.clear-search-btn')) {
        search.value = '';
        state.q = '';
        state.page = 1;
        draw();
        search.focus();
      }
    });

    var storageKey = 'mcms_tbl_' + location.pathname + '_' + (tbl.id || 'tbl');
    try {
      var saved = JSON.parse(sessionStorage.getItem(storageKey));
      if (saved) {
        if (saved.q && showSearch) { state.q = saved.q; search.value = saved.q; }
        if (saved.col != null && saved.col > -1) { state.col = saved.col; state.dir = saved.dir || 1; }
        if (saved.per != null) { state.per = saved.per; per.value = saved.per; }
        if (saved.page != null) { state.page = saved.page; }
      }
    } catch (e) {}

    $$('th', tbl.tHead).forEach(function (th, i) {
      if (!th.textContent.trim()) { return; }
      types[i] = colType(rows, i);
      if (types[i] === 'num') { th.classList.add('num'); rows.forEach(function (r) { if (r.cells[i]) { r.cells[i].classList.add('num'); } }); }
      var b = el('button', 'sortbtn', esc(th.textContent.trim()) + SORT); b.type = 'button';
      th.textContent = ''; th.appendChild(b); th.setAttribute('aria-sort', 'none');
      b.addEventListener('click', function () {
        state.dir = state.col === i ? -state.dir : 1; state.col = i; state.page = 1; draw();
      });
    });

    function draw() {
      var q = state.q.toLowerCase();
      var list = rows.filter(function (r) { return !q || r.textContent.toLowerCase().indexOf(q) > -1; });
      if (state.col > -1) {
        var t = types[state.col];
        list.sort(function (a, b) {
          var x = key(a.cells[state.col] ? a.cells[state.col].textContent : '', t), y = key(b.cells[state.col] ? b.cells[state.col].textContent : '', t);
          return (x < y ? -1 : x > y ? 1 : 0) * state.dir;
        });
        list.forEach(function (r) { body.appendChild(r); });
      }
      $$('th', tbl.tHead).forEach(function (th, i) {
        th.setAttribute('aria-sort', i === state.col ? (state.dir > 0 ? 'ascending' : 'descending') : 'none'); th.classList.toggle('sorted', i === state.col);
      });
      var pages = Math.max(1, Math.ceil(list.length / state.per));
      state.page = Math.min(state.page, pages);
      var from = (state.page - 1) * state.per;
      rows.forEach(function (r) { r.hidden = true; });
      list.slice(showPager ? from : 0, showPager ? from + state.per : undefined).forEach(function (r) { r.hidden = false; });
      if (list.length) { if (none.parentNode) { none.remove(); } } else { body.appendChild(none); }
      count.textContent = rows.length === list.length ? rows.length + ' rows' : list.length + ' of ' + rows.length + ' rows';
      range.textContent = list.length ? (from + 1) + '–' + Math.min(from + state.per, list.length) + ' of ' + list.length : '0 of 0';
      pg.textContent = 'Page ' + state.page + ' of ' + pages;
      prev.disabled = state.page <= 1; next.disabled = state.page >= pages;

      try {
        sessionStorage.setItem(storageKey, JSON.stringify({ q: state.q, col: state.col, dir: state.dir, page: state.page, per: state.per }));
      } catch (e) {}
    }
    search.addEventListener('input', function () { state.q = search.value; state.page = 1; draw(); });
    per.addEventListener('change', function () { state.per = +per.value; state.page = 1; draw(); });
    prev.addEventListener('click', function () { state.page--; draw(); });
    next.addEventListener('click', function () { state.page++; draw(); });
    draw();
  }

  /* ---------- toasts (UI/UX 4.3): flash messages become toasts; errors stay inline ---------- */
  function toasts() {
    var flashes = $$('main .status:not(.error)');
    if (!flashes.length) { return; }
    var box = el('div', 'toasts'); box.setAttribute('aria-live', 'polite'); document.body.appendChild(box);
    flashes.forEach(function (f) {
      var t = el('div', 'toast'); t.appendChild(f); f.className = ''; box.appendChild(t);
      var x = el('button', 'btn ghost sm', '&times;'); x.type = 'button'; x.setAttribute('aria-label', 'Dismiss'); x.style.marginLeft = 'auto'; t.appendChild(x);
      var gone = function () { t.remove(); }; x.addEventListener('click', gone); setTimeout(gone, 9000);
    });
  }

  function showToast(title, body, kind) {
    var box = $('.toasts');
    if (!box) {
      box = el('div', 'toasts');
      box.setAttribute('aria-live', 'polite');
      document.body.appendChild(box);
    }
    var t = el('div', 'toast' + (kind ? ' ' + kind : ''));
    t.innerHTML = '<div><b>' + esc(title) + '</b>' + (body ? '<div class="muted small">' + esc(body) + '</div>' : '') + '</div>';
    var x = el('button', 'btn ghost sm', '&times;');
    x.type = 'button'; x.setAttribute('aria-label', 'Dismiss'); x.style.marginLeft = 'auto';
    t.appendChild(x);
    box.appendChild(t);
    var gone = function () { t.remove(); };
    x.addEventListener('click', gone);
    setTimeout(gone, 6000);
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
    window.addEventListener('hashchange', function () { var h = (location.hash.slice(1) || '').toLowerCase(); var a = { 'notifications': 'notif', 'sessions': 'security', 'signin': 'security' }; if (a[h]) h = a[h]; if ($('[data-pane="' + h + '"]', root)) open(h); });
    var first = panes.filter(function (p) { return !p.hasAttribute('data-empty'); })[0] || panes[0];
    var hash = (location.hash.slice(1) || '').toLowerCase();
    var param = (new URLSearchParams(location.search)).get('tab') || '';
    var target = param || hash;
    var aliases = { 'notifications': 'notif', 'sessions': 'security', 'signin': 'security' };
    if (aliases[target]) { target = aliases[target]; }
    var targetPane = target ? $('[data-pane="' + target + '"]', root) : null;
    open(targetPane ? target : (root.hasAttribute('data-auto') ? first.getAttribute('data-pane') : btns[0].getAttribute('data-tab')));
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
              if (newRes) { currentResults.innerHTML = newRes.innerHTML; }
              var newCnt = $(countSelector, doc);
              var curCnt = $(countSelector);
              if (newCnt && curCnt) { curCnt.innerHTML = newCnt.innerHTML; }
            });
        }
      });
    });
  }
  /* ---------- sidebar toggle (collapse / expand) ---------- */
  function setupSidebar() {
    var app = $('.app');
    var side = $('.side');
    var navToggle = $('#nav-toggle');
    var sideCollapseBtn = $('#side-collapse-btn');
    if (!app || !side) return;

    try {
      if (localStorage.getItem('mcms_side_collapsed') === '1') {
        app.classList.add('side-collapsed');
        side.classList.add('collapsed');
      }
    } catch (e) {}

    function toggle() {
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

    if (navToggle) {
      navToggle.addEventListener('click', toggle);
    }
    if (sideCollapseBtn) {
      sideCollapseBtn.addEventListener('click', toggle);
    }
  }


  document.addEventListener('DOMContentLoaded', function () {
    $$('[data-tabs]').forEach(tabs);
    $$('main table').forEach(enhance);
    rowLinks();
    switches();
    toasts();
    autoFilterForms();
    setupSidebar();
  });
})();
