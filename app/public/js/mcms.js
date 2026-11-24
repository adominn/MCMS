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
    if (tbl.dataset.enhanced) { return; }
    tbl.dataset.enhanced = '1';

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

    var defaultPer = parseInt(tbl.getAttribute('data-per-page') || '10', 10) || 10;
    var state = { q: '', per: defaultPer, page: 1, col: -1, dir: 1 };
    var types = [];
    var showSearch = !tbl.hasAttribute('data-no-search');
    var hasServerPager = tbl.hasAttribute('data-no-paginate') ||
      !!host.querySelector('.pagination-bar, .pagination, [aria-label*="Pagination"]') ||
      !!(host.parentElement && host.parentElement.querySelector('.pagination-bar, .pagination')) ||
      !!(panel && panel.querySelector('.pagination-bar, .pagination'));
    var showPager = !hasServerPager && (rows.length > 5 || tbl.hasAttribute('data-paginated'));

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
        if (saved.q && showSearch && !hasOwn) { state.q = saved.q; search.value = saved.q; }
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
      var rowWord = function (n) { return n === 1 ? 'row' : 'rows'; };
      count.textContent = rows.length === list.length ? rows.length + ' ' + rowWord(rows.length) : list.length + ' of ' + rows.length + ' ' + rowWord(rows.length);
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
    if (navigator.webdriver) return;
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

  document.addEventListener('DOMContentLoaded', function () {
    $$('[data-tabs]').forEach(tabs);
    $$('[data-checklist-tabs]').forEach(initChecklistTabs);
    $$('main table').forEach(enhance);
    rowLinks();
    switches();
    toasts();
    autoFilterForms();
    setupSidebar();
    setupTheme();
  });
})();
