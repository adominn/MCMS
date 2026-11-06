/* MCMS clickable prototype: router, shell, overlays and shared UI helpers.
   Plain scripts on purpose: the prototype must open from file:// with no server or build. */
(function () {
const MC = window.MC = { screens: [], modals: {}, drawers: {}, acts: {}, flows: [], tables: {}, roles: {}, menus: {} };

/* ---------- icons (24-box outline, as in v1) ---------- */
MC.P = {
  inbox: '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5 5h14l2 8v6H3v-6z"/>',
  doc: '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
  cal: '<path d="M4 6h16v14H4zM4 10h16M8 3v5M16 3v5"/>',
  check: '<path d="M4 4h16v16H4z"/><path d="m8 12 3 3 5-6"/>',
  badge: '<circle cx="12" cy="9" r="5"/><path d="m9 13.5-1.5 7L12 18l4.5 2.5-1.5-7"/>',
  shield: '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/>',
  bank: '<path d="M3 9 12 4l9 5M5 10v8M10 10v8M14 10v8M19 10v8M3 20h18"/>',
  swap: '<path d="M4 8h14l-4-4M20 16H6l4 4"/>',
  layers: '<path d="m12 4 9 5-9 5-9-5z"/><path d="m3 14 9 5 9-5"/>',
  chart: '<path d="M4 20V4M4 20h16M8 16v-5M12 16V8M16 16v-3"/>',
  users: '<circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.5 2.7-6 6-6s6 2.5 6 6M16 4.5a3.5 3.5 0 0 1 0 7M21 20c0-3-1.7-5.3-4-6"/>',
  gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
  sync: '<path d="M20 11a8 8 0 0 0-14.5-4M4 13a8 8 0 0 0 14.5 4M5 3v4h4M19 21v-4h-4"/>',
  plug: '<path d="M9 3v5M15 3v5M6 8h12v3a6 6 0 0 1-12 0zM12 17v4"/>',
  list: '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
  home: '<path d="m3 11 9-7 9 7M5 9.5V20h14V9.5"/>',
  wallet: '<path d="M3 7h18v13H3zM3 7l13-3v3M16 13.5h2"/>',
  user: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
  search: '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/>',
  clip: '<path d="M9 4h6v3H9zM6 5.5H4V21h16V5.5h-2M8 12h8M8 16h5"/>',
  back: '<path d="M15 5 8 12l7 7"/>', right: '<path d="m9 6 6 6-6 6"/>', left: '<path d="m15 6-6 6 6 6"/>',
  down: '<path d="m6 9 6 6 6-6"/>', up: '<path d="m6 15 6-6 6 6"/>', updown: '<path d="m8 9 4-4 4 4M8 15l4 4 4-4"/>', x: '<path d="M6 6l12 12M18 6 6 18"/>',
  share: '<path d="M12 15V3M7 8l5-5 5 5M5 13v8h14v-8"/>', dl: '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
  audit: '<path d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h3"/>',
  bell: '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0"/>',
  help: '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01"/>',
  out: '<path d="M10 4H4v16h6M15 8l4 4-4 4M19 12H9"/>',
  cart: '<path d="M3 4h3l2 11h11l2-8H7M10 20h.01M17 20h.01"/>',
  grad: '<path d="m2 9 10-5 10 5-10 5zM6 11v5c3 2 9 2 12 0v-5"/>',
  db: '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
  file: '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/>', upload: '<path d="M12 21V9M7 14l5-5 5 5M5 3h14"/>',
  warn: '<path d="M12 3 2 20h20zM12 10v4M12 17h.01"/>', info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/>',
  ok: '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>', flag: '<path d="M5 21V4h11l-2 4 2 4H5"/>',
  pause: '<path d="M8 5v14M16 5v14"/>', stop: '<path d="M6 6h12v12H6z"/>', mail: '<path d="M3 5h18v14H3zM3 6l9 7 9-7"/>',
  lock: '<path d="M6 11h12v10H6zM9 11V7a3 3 0 0 1 6 0v4"/>', globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
  plus: '<path d="M12 5v14M5 12h14"/>', edit: '<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>', eye: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
};
MC.icon = (n, s = 16, c = 'currentColor') => `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="${c}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${MC.P[n] || ''}</svg>`;

/* ---------- small html helpers every screen uses ---------- */
const TAGS = { ok: /approved|active|valid|verified|pass|ready|issued|deposited|accepted|working|confirmed|complete|done|paid|enrolled|published|current|given|in abc|sent|all imported/i,
  err: /^not |^invalid|^un|revoked|fail|rejected|not approved|terminated|failing|expired qv|overdue|withdrawn consent|cancelled|refused|invalid|error/i,
  warn: /returned|missing|due|expir|suspended|on hold|partial|partly|problem|waiting for consent|pending payment|conditions/i,
  info: /review|endorse|waiting|submitted|preparing|being decided|in progress|asked|scheduled|requested|open|new/i };
// negatives first, so "Not approved" or "Partly approved" never match "approved"
MC.tag = (t, k) => { k = k || ['err', 'warn', 'ok', 'info'].find(x => TAGS[x].test(t)) || ''; return `<span class="tag ${k}">${t}</span>`; };
MC.cat = c => `<span class="cat">${c}</span>`;
MC.CATNAME = { UMC: 'Unbundled Micro-Credential', SAMC: 'Stand-alone Micro-Credential', MCE: 'Micro-Credential Eksekutif' }; // Glossary names
MC.nm = (c, n) => `<span class="nm">${MC.cat(c)}<span>${n}</span></span>`;
MC.head = ({ crumb, title, sub, actions = '', status = '' }) => `
  ${crumb ? `<div class="crumb">${crumb.map(([l, h], i) => (i ? '<span>/</span>' : '') + (h ? `<a data-go="${h}">${l}</a>` : `<span>${l}</span>`)).join('')}</div>` : ''}
  <div class="title"><div><h1>${title}</h1>${sub ? `<div class="sub">${sub}</div>` : ''}</div><div class="act">${status}${actions}</div></div>`;
MC.kpis = list => `<div class="kpis">${list.map(([l, v, n]) => `<div class="kpi"><div class="l">${l}</div><div class="v">${v}</div>${n ? `<div class="n">${n}</div>` : ''}</div>`).join('')}</div>`;
MC.checks = list => list.map(([k, t, s, extra]) => `<div class="check"><span class="dot ${k || ''}"></span><div class="grow">${t}${s ? `<span class="small muted" style="display:block">${s}</span>` : ''}</div>${extra || ''}</div>`).join('');
MC.track = list => `<div class="track">${list.map(([k, b, s]) => `<div class="stp ${k || ''}"><b>${b}</b>${s || ''}</div>`).join('')}</div>`;
MC.panel = (title, body, right = '', pad = true) => `<div class="panel">${title ? `<div class="hd"><h2>${title}</h2><span class="r">${right}</span></div>` : ''}${pad ? `<div class="bd">${body}</div>` : body}</div>`;
MC.dl = pairs => `<dl class="dl">${pairs.map(([a, b]) => `<dt>${a}</dt><dd>${b}</dd>`).join('')}</dl>`;
MC.field = (label, ctl, help = '', req = false) => `<div class="field"><label class="${req ? 'req' : ''}">${label}</label>${ctl}${help ? `<div class="help">${help}</div>` : ''}</div>`;
MC.inp = (name, val = '', ph = '', req = false, type = 'text') => `<input class="input" name="${name}" type="${type}" value="${val}" placeholder="${ph}" ${req ? 'required' : ''}>`;
MC.sel = (name, opts, val) => `<select class="input" name="${name}">${opts.map(o => `<option ${o === val ? 'selected' : ''}>${o}</option>`).join('')}</select>`;
MC.area = (name, val = '', ph = '', req = false) => `<textarea class="input" name="${name}" placeholder="${ph}" ${req ? 'required' : ''}>${val}</textarea>`;
MC.radios = (name, opts, val) => `<div class="col" style="gap:8px">${opts.map(([v, t, s]) => `<label class="choice ${v === val ? 'on' : ''}"><input type="radio" name="${name}" value="${v}" ${v === val ? 'checked' : ''}><div>${t}${s ? `<span class="small muted" style="display:block">${s}</span>` : ''}</div></label>`).join('')}</div>`;
MC.boxes = (name, opts) => `<div class="col" style="gap:8px">${opts.map(([v, t, s, on]) => `<label class="choice ${on ? 'on' : ''}"><input type="checkbox" name="${name}" value="${v}" ${on ? 'checked' : ''}><div>${t}${s ? `<span class="small muted" style="display:block">${s}</span>` : ''}</div></label>`).join('')}</div>`;
MC.note = (t, k = '') => `<div class="note ${k}">${t}</div>`;
MC.tabs = (list, on) => `<div class="tabs">${list.map(([k, l, c]) => `<div class="tab ${k === on ? 'on' : ''}" data-tab="${k}">${l}${c !== undefined && c !== '' ? `<span class="c">${c}</span>` : ''}</div>`).join('')}</div>`;
MC.trace = t => `<div class="trace">Traces to ${t}</div>`;
// ir-dates: the interface shows DD/MM/YYYY. Data is written "26 Oct 2026" for readability and converted at render.
const MON = { Jan: '01', Feb: '02', Mar: '03', Apr: '04', May: '05', Jun: '06', Jul: '07', Aug: '08', Sep: '09', Oct: '10', Nov: '11', Dec: '12' };
MC.fmt = html => String(html).replace(/\b(\d{1,2}) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (\d{4})\b/g, (m, d, mo, y) => `${d.padStart(2, '0')}/${MON[mo]}/${y}`)
  .replace(/\b(\d{1,2}) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/g, (m, d, mo) => `${d.padStart(2, '0')}/${MON[mo]}`); // "26 Oct" without a year
MC.rm = n => `RM ${Number(n).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; // nfr-currency

/* ---------- state: seeded by data.js, kept per browser tab so flows survive reloads ---------- */
MC.load = () => { try { const s = sessionStorage.getItem('mcms.state'); if (s) return JSON.parse(s); } catch (e) {} return null; };
MC.save = () => { try { sessionStorage.setItem('mcms.state', JSON.stringify(MC.state)); } catch (e) {} };
MC.reset = () => { MC.state = JSON.parse(JSON.stringify(MC.seed)); MC.save(); MC.render(); MC.toast('Sample data reset', 'All records are back to their starting state.', 'info'); };

/* ---------- registration ---------- */
MC.role = (k, d) => (MC.roles[k] = Object.assign({ key: k }, d));
MC.screen = (route, d) => MC.screens.push(Object.assign({ route, parts: route.split('/').filter(Boolean) }, d));
MC.modal = (k, d) => (MC.modals[k] = d);
MC.drawer = (k, d) => (MC.drawers[k] = d);
MC.act = (k, f) => (MC.acts[k] = f);
MC.flow = d => MC.flows.push(d);

/* ---------- router ---------- */
MC.go = h => { if (location.hash === h) MC.render(); else location.hash = h; };
function match(hash) {
  const [path, q] = hash.replace(/^#\/?/, '').split('?');
  const segs = path.split('/').filter(Boolean);
  const query = Object.fromEntries(new URLSearchParams(q || ''));
  for (const s of MC.screens) {
    if (s.parts.length !== segs.length) continue;
    const p = {};
    if (s.parts.every((x, i) => x[0] === ':' ? (p[x.slice(1)] = decodeURIComponent(segs[i]), true) : x === segs[i])) return { s, p: Object.assign(query, p) };
  }
  return null;
}

MC.match = match;

/* ---------- shell ---------- */
function appTop(r) {
  const u = MC.user(r), n = (MC.state.notifs[r.key] || []).filter(x => x.unread).length;
  return `<div class="top"><div class="brand" data-go="${r.home}"><b>UTeM MCMS</b><span>Micro-Credential Management</span></div>
    ${r.search ? `<label class="search" style="margin-left:24px">${MC.icon('search')}<input data-search placeholder="${r.search}"></label>` : ''}
    <div class="scope" style="gap:4px">
      <span class="lang" data-act="lang" title="Switch language"><span class="${MC.state.lang === 'EN' ? 'on' : ''}">EN</span><span class="${MC.state.lang === 'BM' ? 'on' : ''}">BM</span></span>
      <button class="iconbtn" data-go="#/help" title="Help">${MC.icon('help', 20, '#161616')}</button>
      <button class="iconbtn" data-menu="notif" title="Notifications" aria-label="Notifications, ${n} unread">${MC.icon('bell', 20, '#161616')}${n ? `<span class="badge">${n}</span>` : ''}</button>
      <button class="who-btn" data-menu="user"><span class="avatar">${u.initials}</span><span>${u.name}<span class="r">${u.post}</span></span>${MC.icon('down', 16, '#525252')}</button>
    </div></div>`;
}
function side(r, active) {
  const count = k => { const f = r.counts && r.counts[k]; const v = typeof f === 'function' ? f(MC.state) : f; return v ? `<span class="c">${v}</span>` : ''; };
  return `<nav class="side">${r.nav.map(i => i[1] === undefined ? `<div class="grp">${i[0]}</div>`
    : `<div class="nav ${i[0] === active ? 'on' : ''}" data-go="#/${r.key}/${i[0]}">${MC.icon(i[1], 16)}${i[2]}${count(i[0])}</div>`).join('')}</nav>`;
}
function pubTop() {
  const items = [['#/public/home', 'Home'], ['#/public/catalogue', 'Catalogue'], ['#/public/pathways', 'Pathways to a degree'], ['#/public/how', 'How it works'], ['#/public/faq', 'FAQ'], ['#/public/verify', 'Verify a credential']];
  const here = location.hash.split('?')[0];
  return `<div class="top"><div class="brand" data-go="#/public/home"><b>UTeM</b><span>Micro-credentials</span></div>
    <div style="display:flex;gap:4px;margin-left:24px">${items.map(([h, l]) => `<span class="nav ${here.startsWith(h) ? 'on' : ''}" data-go="${h}" style="height:32px">${l}</span>`).join('')}</div>
    <div class="scope"><span class="lang" data-act="lang"><span class="${MC.state.lang === 'EN' ? 'on' : ''}">EN</span><span class="${MC.state.lang === 'BM' ? 'on' : ''}">BM</span></span>
    <span class="btn ter sm" data-go="#/signin">Sign in</span></div></div>`;
}
function pubFoot() {
  return `<div class="foot"><div><b>UTeM Micro-credentials</b>Centre for Open and Distance Learning (CODL)<br>Universiti Teknikal Malaysia Melaka<br>Hang Tuah Jaya, 76100 Durian Tunggal, Melaka</div>
    <div><b>Learn</b><a data-go="#/public/catalogue" style="color:#c6c6c6;display:block">Catalogue</a><a data-go="#/public/pathways" style="color:#c6c6c6;display:block">Pathways to a degree</a><a data-go="#/public/how" style="color:#c6c6c6;display:block">How it works</a></div>
    <div><b>Help</b><a data-go="#/public/faq" style="color:#c6c6c6;display:block">FAQ</a><a data-go="#/public/verify" style="color:#c6c6c6;display:block">Verify a credential</a><span style="display:block">codl@utem.edu.my</span></div>
    <div><b>Policies</b><a data-go="#/public/privacy" style="color:#c6c6c6;display:block">Privacy notice (PDPA)</a><a data-go="#/public/terms" style="color:#c6c6c6;display:block">Terms of use</a><a data-go="#/public/accessibility" style="color:#c6c6c6;display:block">Accessibility</a></div>
    <div style="margin-left:auto;max-width:360px">Prototype for review. Sample data only; not an official UTeM service.</div></div>`;
}

MC.user = r => (r && r.user) || { name: 'Guest', post: '', initials: 'G' };
MC.cur = null;
MC.render = function () {
  closeOverlays();
  const m = match(location.hash || '#/') || match('#/404');
  const { s, p } = m; MC.cur = { s, p };
  if (s.role && MC.roles[s.role]) MC.state.as = s.role; // following a link into another role signs in as its persona
  const r = MC.roles[s.role] || (s.shell ? null : MC.roles[MC.state.as]); // shared screens (profile, help) wear the current persona's shell
  let body;
  try { body = MC.fmt(s.render(p, MC.state)); } catch (e) { console.error(e); body = MC.note(`This screen failed to draw: ${e.message}. Reference ERR-${Date.now().toString(36).toUpperCase()}`, 'err'); }
  const root = document.getElementById('app');
  const bn = MC.state.banner ? `<div class="note warn" style="margin:0">${MC.icon('warn', 16)}<span>${MC.fmt(`Planned maintenance ${MC.state.banner.when}: ${MC.state.banner.text}`)}</span></div>` : ''; // nfr-maint
  if (s.shell === 'public') root.innerHTML = `<div class="app">${pubTop()}${bn}<div class="main full">${body}${pubFoot()}</div></div>`;
  else if (s.shell === 'bare' || !r) root.innerHTML = `<div class="app">${body}</div>`;
  else root.innerHTML = `<div class="app">${appTop(r)}${bn}<div class="body">${side(r, s.nav)}<main class="main">${body}</main></div></div>`;
  document.title = `${s.title} · MCMS prototype`;
  root.querySelectorAll('[data-tabs]').forEach(t => { const on = t.querySelector('.tab.on'); if (on) showPane(t, on.dataset.tab); });
  drawTables();
  if (s.mount) s.mount(root, p, MC.state);
  flowbar(); devbar();
  if (!MC._keepScroll) window.scrollTo(0, 0); MC._keepScroll = false;
  MC.save();
};

/* ---------- tabs ---------- */
function showPane(box, k) {
  box.querySelectorAll(':scope > .tabs .tab, :scope .tabs .tab').forEach(t => t.classList.toggle('on', t.dataset.tab === k));
  box.querySelectorAll('[data-pane]').forEach(p => p.classList.toggle('on', p.dataset.pane === k));
}

/* ---------- tables with search and pagination ---------- */
MC.table = function (o) {
  const id = o.id; MC.tables[id] = Object.assign({ page: 0, size: o.size || 10, q: '', by: -1, dir: 1 }, MC.tables[id] && { page: MC.tables[id].page, size: MC.tables[id].size, q: MC.tables[id].q, by: MC.tables[id].by, dir: MC.tables[id].dir }, o);
  const t = MC.tables[id];
  return `<div class="panel" data-table="${id}">${o.title || o.right ? `<div class="hd"><h2>${o.title || ''}</h2><span class="r">${o.right || ''}</span></div>` : ''}
    ${o.search || o.tools ? `<div class="tbar">${o.search ? `<input class="input" data-tsearch="${id}" placeholder="${o.search}" value="${t.q}">` : ''}${o.tools || ''}</div>` : ''}
    <table><thead><tr>${o.cols.map((c, i) => `<th class="${c.num ? 'num' : ''}" ${c.w ? `style="width:${c.w}"` : ''}>${sortable(o, c) ? `<button class="sortbtn" data-sort="${id}:${i}" title="Sort by ${strip(c.h)}">${c.h}<span class="si" aria-hidden="true"></span></button>` : c.h}</th>`).join('')}</tr></thead><tbody data-tbody></tbody></table><div data-pager></div></div>`;
};
/* sorting: any column with a header sorts, by c.sort(row) if given, else the raw value or the cell's text */
const strip = h => String(h ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
const sortable = (o, c) => !o.nosort && !c.nosort && strip(c.h) !== '';
const MONTHS = 'jan feb mar apr may jun jul aug sep oct nov dec'.split(' ');
function sortKey(v) {
  const s = strip(v);
  if (!s || /^[—–-]$/.test(s)) return null; // empty sorts last
  let m = s.match(/^(\d{1,2}) (jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\w* (\d{4})/i);
  if (m) return +new Date(+m[3], MONTHS.indexOf(m[2].toLowerCase()), +m[1]);
  m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})/);
  if (m) return +new Date(+m[3], +m[2] - 1, +m[1]);
  if (/^today/i.test(s)) return 0;
  m = s.replace(/^RM\s*/i, '').replace(/,/g, '').match(/^-?\d+(\.\d+)?/);
  if (m) return parseFloat(m[0]);
  return s.toLowerCase();
}
const cellVal = (c, r) => c.sort ? c.sort(r) : c.k !== undefined ? r[c.k] : c.r ? c.r(r) : '';
function rowsOf(t) {
  const all = typeof t.rows === 'function' ? t.rows(MC.state) : t.rows;
  const q = t.q.trim().toLowerCase();
  const rows = q ? all.filter(r => JSON.stringify(r).toLowerCase().includes(q)) : all;
  const c = t.cols[t.by]; if (!c) return rows;
  const keyed = rows.map((r, i) => ({ r, i, k: sortKey(cellVal(c, r)) }));
  keyed.sort((a, b) => {
    if (a.k === null || b.k === null) return a.k === b.k ? a.i - b.i : a.k === null ? 1 : -1;
    const na = typeof a.k === 'number', nb = typeof b.k === 'number';
    const d = na && nb ? a.k - b.k : na !== nb ? (na ? -1 : 1) : String(a.k).localeCompare(String(b.k), 'en', { numeric: true });
    return d * t.dir || a.i - b.i;
  });
  return keyed.map(x => x.r);
}
function drawTable(el) {
  const t = MC.tables[el.dataset.table]; if (!t) return;
  const rows = rowsOf(t), n = rows.length, pages = Math.max(1, Math.ceil(n / t.size));
  t.page = Math.min(t.page, pages - 1);
  const slice = rows.slice(t.page * t.size, t.page * t.size + t.size);
  el.querySelector('[data-tbody]').innerHTML = MC.fmt(slice.length ? slice.map(r => `<tr ${t.go ? `data-go="${t.go(r)}"` : ''} class="${t.sel && t.sel(r) ? 'sel' : ''}">${t.cols.map(c => `<td class="${c.num ? 'num' : ''}">${c.r ? c.r(r) : (r[c.k] ?? '')}</td>`).join('')}</tr>`).join('')
    : `<tr><td colspan="${t.cols.length}"><div class="empty" style="margin:12px 0">${t.q ? `No rows match "${t.q}".` : (t.empty || 'Nothing here yet.')}</div></td></tr>`);
  el.querySelectorAll('thead th').forEach((th, i) => {
    const on = i === t.by; th.classList.toggle('sorted', on);
    if (th.querySelector('.sortbtn')) { th.setAttribute('aria-sort', on ? (t.dir > 0 ? 'ascending' : 'descending') : 'none'); th.querySelector('.si').innerHTML = MC.icon(on ? (t.dir > 0 ? 'up' : 'down') : 'updown', 14); }
  });
  const from = n ? t.page * t.size + 1 : 0, to = Math.min(n, (t.page + 1) * t.size);
  el.querySelector('[data-pager]').innerHTML = t.nopager ? '' : `<div class="pager"><span>Rows per page <select data-psize="${el.dataset.table}">${[5, 10, 25, 50].map(v => `<option ${v === t.size ? 'selected' : ''}>${v}</option>`).join('')}</select></span>
    <span>${from}–${to} of ${n}</span><span class="sp">Page ${t.page + 1} of ${pages}</span>
    <button class="pb" data-page="${el.dataset.table}:-1" ${t.page === 0 ? 'disabled' : ''} aria-label="Previous page">${MC.icon('left')}</button><button class="pb" data-page="${el.dataset.table}:1" ${t.page >= pages - 1 ? 'disabled' : ''} aria-label="Next page">${MC.icon('right')}</button></div>`;
}
const drawTables = () => document.querySelectorAll('[data-table]').forEach(drawTable);
MC.redrawTables = drawTables;

/* ---------- toasts ---------- */
MC.toast = (title, text = '', kind = 'ok') => {
  let box = document.querySelector('.toasts'); if (!box) { box = document.createElement('div'); box.className = 'toasts'; box.setAttribute('role', 'status'); document.body.appendChild(box); }
  const t = document.createElement('div'); t.className = `toast ${kind}`;
  t.innerHTML = MC.fmt(`${MC.icon(kind === 'err' ? 'warn' : kind === 'warn' ? 'warn' : kind === 'info' ? 'info' : 'ok', 20, kind === 'err' ? '#da1e28' : kind === 'warn' ? '#8e6a00' : kind === 'info' ? '#1e2a6e' : '#24a148')}<div><b>${title}</b>${text}</div>`);
  box.appendChild(t); setTimeout(() => t.remove(), 5200);
};

/* ---------- modals ---------- */
let lastFocus = null; // focus returns to the control that opened a dialog or panel
function closeOverlays() {
  const had = document.querySelector('.scrim,.drawer');
  document.querySelectorAll('.scrim,.pop,.drawer').forEach(x => x.remove()); document.querySelectorAll('.open').forEach(x => x.classList.remove('open'));
  if (had && lastFocus && document.contains(lastFocus)) lastFocus.focus(); lastFocus = null;
}
MC.close = closeOverlays;
MC.open = function (key, ctx = {}) {
  closeOverlays(); lastFocus = document.activeElement;
  const d = key === 'confirm' ? {
    title: ctx.title, kicker: ctx.kicker, body: ctx.body || '', confirm: ctx.confirm || 'Confirm', danger: 'danger' in ctx,
    // a bare data-reason attribute means "ask for a reason"
    reason: ctx.reason !== undefined ? (ctx.reason || true) : undefined, done: ctx.done, then: ctx.then, act: ctx.act,
  } : MC.modals[key];
  if (!d) return MC.toast('Not designed yet', `No pop-up named "${key}".`, 'warn');
  const val = f => typeof f === 'function' ? f(ctx, MC.state) : f;
  const scrim = document.createElement('div'); scrim.className = 'scrim';
  scrim.innerHTML = MC.fmt(`<div class="modal ${val(d.danger) ? 'danger' : ''} ${d.wide ? 'wide' : ''}" role="dialog" aria-modal="true" aria-labelledby="mt">
    <div class="mh"><div>${d.kicker ? `<div class="k">${val(d.kicker)}</div>` : ''}<h2 id="mt">${val(d.title)}</h2></div><button class="iconbtn x" data-close aria-label="Close">${MC.icon('x', 20)}</button></div>
    <form class="mb" data-mform onsubmit="return false">${val(d.body) || ''}${d.reason ? MC.field(d.reason === true ? 'Reason (kept in the audit trail)' : d.reason, MC.area('reason', '', '', true), '', true) : ''}</form>
    <div class="mf">${d.noCancel ? '' : `<button class="btn sec" data-close>${d.cancel || 'Cancel'}</button>`}${d.confirm === false ? '' : `<button class="btn ${val(d.danger) ? 'danger' : ''}" data-mok>${val(d.confirm) || 'Confirm'}</button>`}</div></div>`);
  document.body.appendChild(scrim);
  scrim._def = d; scrim._ctx = ctx;
  // a destructive dialog starts on a safe control: its first field, else Cancel
  const f = scrim.querySelector(val(d.danger) ? 'input,textarea,select,.mf [data-close]' : 'input,textarea,select,button.btn:not([data-close])'); if (f) f.focus();
  if (d.mount) d.mount(scrim, ctx, MC.state);
};
function validate(scope) {
  const badNames = new Set(); // a radio group counts once
  scope.querySelectorAll('[required]').forEach(i => {
    const group = i.type === 'radio' || i.type === 'checkbox';
    const empty = group ? !scope.querySelector(`[name="${i.name}"]:checked`) : !String(i.value).trim();
    i.classList.toggle('bad', empty);
    const box = i.closest('.field') || (group && (i.closest('label.choice') || i).parentElement);
    const h = box && box.querySelector(':scope > .help.err');
    if (empty && box && !h) box.insertAdjacentHTML('beforeend', '<div class="help err">Required. Fill this in to continue.</div>');
    if (!empty && h) h.remove();
    if (empty) badNames.add(i.name || i);
  });
  const bad = badNames.size;
  if (bad) MC.toast('Check the highlighted fields', `${bad} required ${bad > 1 ? 'fields are' : 'field is'} empty.`, 'err');
  return !bad;
}
MC.validate = validate;
// works for <form> and for any [data-form] container
const formData = el => { const o = {}; if (el) el.querySelectorAll('[name]').forEach(i => { if ((i.type === 'radio' || i.type === 'checkbox') && !i.checked) return; o[i.name] = i.value; }); return o; };
function finish(d, ctx, form) {
  try {
    if (d.act) { const f = MC.acts[d.act]; if (f && f(Object.assign({}, ctx, formData(form)), MC.state) === false) return; }
    if (d.run && d.run(Object.assign({}, ctx, formData(form)), MC.state) === false) return;
  } catch (e) { console.error(e); return MC.toast('Something went wrong', `Nothing was saved. Reference ERR-${Date.now().toString(36).toUpperCase()}`, 'err'); } // nfr-errors
  const done = typeof d.done === 'function' ? d.done(ctx, MC.state) : d.done;
  const then = typeof d.then === 'function' ? d.then(ctx, MC.state) : d.then;
  closeOverlays(); MC.save();
  if (then) MC.go(then); else { MC._keepScroll = true; MC.render(); }
  if (done) { const [a, b, k] = Array.isArray(done) ? done : String(done).split('|'); MC.toast(a, b || '', k || 'ok'); }
}

/* ---------- menus (notifications, profile, row menus) ---------- */
MC.menus.notif = () => {
  const r = MC.roles[MC.state.as], list = MC.state.notifs[r.key] || [];
  return `<div class="pop notif"><div class="ph" style="display:flex"><b style="font-weight:600">Notifications</b><a style="margin-left:auto;font-size:13px" data-act="readall">Mark all read</a></div>
    ${list.slice(0, 5).map((x, i) => `<div class="ni ${x.unread ? 'unread' : ''}" data-act="readnotif" data-i="${i}" data-then="${x.go || ''}"><span class="dot ${x.unread ? '' : 'n'}" style="${x.unread ? 'background:var(--primary)' : ''}"></span><div><div class="t">${x.t}</div><div class="s">${x.s} · ${x.when}</div></div></div>`).join('') || '<div class="ph muted">No notifications.</div>'}
    <div class="mi" data-go="#/notifications" style="color:var(--primary)">View all notifications</div></div>`;
};
MC.menus.user = () => {
  const r = MC.roles[MC.state.as], u = MC.user(r);
  return `<div class="pop" style="width:280px"><div class="ph"><div style="font-weight:600">${u.name}</div><div class="small muted">${u.email || ''}</div><div class="small muted">${u.post}</div></div>
    <div class="mi" data-go="#/profile">${MC.icon('user')}My profile</div>
    <div class="mi" data-go="#/profile?tab=notif">${MC.icon('bell')}Notification preferences</div>
    <div class="mi" data-go="#/profile?tab=security">${MC.icon('lock')}Sign-in and sessions</div>
    <div class="mi" data-go="#/help">${MC.icon('help')}Help and guides</div>
    <div class="sep"></div>
    <div class="mi" data-go="#/">${MC.icon('swap')}Switch persona (prototype only)</div>
    <div class="mi" data-modal="signout">${MC.icon('out')}Sign out</div></div>`;
};
function openMenu(el) {
  const was = el.classList.contains('open'); closeOverlays(); if (was) return;
  const f = MC.menus[el.dataset.menu]; if (!f) return;
  const box = document.createElement('div'); box.innerHTML = MC.fmt(f(el.dataset, MC.state)); const pop = box.firstElementChild;
  document.body.appendChild(pop); el.classList.add('open');
  const b = el.getBoundingClientRect(); pop.style.top = `${b.bottom + window.scrollY + 4}px`;
  pop.style.left = `${Math.max(8, Math.min(b.right + window.scrollX - pop.offsetWidth, window.innerWidth - pop.offsetWidth - 8))}px`;
}

/* ---------- drawers ---------- */
MC.openDrawer = (k, ctx) => {
  closeOverlays(); const d = MC.drawers[k]; if (!d) return MC.toast('Not designed yet', `No panel named "${k}".`, 'warn');
  const el = document.createElement('div'); el.className = 'drawer';
  lastFocus = document.activeElement;
  el.setAttribute('role', 'dialog'); el.setAttribute('aria-modal', 'true'); el.setAttribute('aria-labelledby', 'dt');
  el.innerHTML = MC.fmt(`<div class="dh"><h2 id="dt">${typeof d.title === 'function' ? d.title(ctx, MC.state) : d.title}</h2><button class="iconbtn" style="margin-left:auto" data-close aria-label="Close">${MC.icon('x', 20)}</button></div><div class="db">${d.body(ctx, MC.state)}</div>`);
  document.body.appendChild(el); el.querySelector('[data-close]').focus();
};

/* ---------- one click handler for the whole prototype ---------- */
document.addEventListener('click', e => {
  const el = e.target.closest('[data-close],[data-mok],[data-menu],[data-tab],[data-page],[data-sort],[data-modal],[data-drawer],[data-act],[data-toast],[data-go],label.choice');
  if (!el) { if (!e.target.closest('.pop,.modal,.drawer')) closeOverlays(); return; }
  if (el.matches('label.choice')) { setTimeout(() => el.closest('.col, .row, form, .bd')?.querySelectorAll('label.choice').forEach(l => l.classList.toggle('on', !!l.querySelector('input:checked'))), 0); return; }
  e.preventDefault();
  if (el.dataset.close !== undefined) return closeOverlays();
  if (el.dataset.mok !== undefined) { const s = el.closest('.scrim'); const form = s.querySelector('[data-mform]'); if (!validate(form)) return; return finish(s._def, s._ctx, form); }
  if (el.dataset.menu) return openMenu(el);
  if (el.dataset.tab) { const box = el.closest('[data-tabs]'); if (box) { showPane(box, el.dataset.tab); if (box.dataset.tabs) MC.tables[box.dataset.tabs] && drawTables(); } return; }
  if (el.dataset.page) { const [id, d] = el.dataset.page.split(':'); MC.tables[id].page += +d; drawTable(el.closest('[data-table]')); return; }
  // header click: ascending, then descending, then back to the original order
  if (el.dataset.sort) { const [id, i] = el.dataset.sort.split(':'); const t = MC.tables[id]; if (t.by !== +i) { t.by = +i; t.dir = 1; } else if (t.dir > 0) t.dir = -1; else t.by = -1; t.page = 0; drawTable(el.closest('[data-table]')); return; }
  const scope = el.closest('[data-form]');
  if (el.dataset.validate !== undefined && scope && !validate(scope)) return;
  const ctx = Object.assign({}, el.dataset, formData(scope));
  if (el.dataset.modal) return MC.open(el.dataset.modal, ctx);
  if (el.dataset.drawer) return MC.openDrawer(el.dataset.drawer, ctx);
  if (el.dataset.act) return finish({ act: el.dataset.act, done: el.dataset.done, then: el.dataset.then }, ctx, scope);
  if (el.dataset.toast) { const [a, b] = el.dataset.toast.split('|'); MC.toast(a, b || '', el.dataset.kind || 'ok'); if (el.dataset.go) MC.go(el.dataset.go); return; }
  if (el.dataset.go) MC.go(el.dataset.go);
});
document.addEventListener('input', e => {
  const s = e.target.closest('[data-tsearch]'); if (s) { const t = MC.tables[s.dataset.tsearch]; t.q = s.value; t.page = 0; drawTable(s.closest('[data-table]')); }
});
document.addEventListener('change', e => {
  const s = e.target.closest('[data-psize]'); if (s) { const t = MC.tables[s.dataset.psize]; t.size = +s.value; t.page = 0; drawTable(s.closest('[data-table]')); }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeOverlays();
  if (e.key === 'Enter' && e.target.matches('[data-search]')) MC.go(`#/search?q=${encodeURIComponent(e.target.value)}`);
  // Enter or Space activates the non-native controls made focusable below
  if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[tabindex="0"][role]')) { e.preventDefault(); e.target.click(); }
  // keep Tab inside an open dialog or panel
  const trap = document.querySelector('.scrim .modal, .drawer');
  if (e.key === 'Tab' && trap) {
    const f = [...trap.querySelectorAll('button,input,select,textarea,a[href],[tabindex="0"]')].filter(x => !x.disabled && x.offsetParent);
    if (!f.length) return;
    if (!trap.contains(document.activeElement)) { e.preventDefault(); f[0].focus(); }
    else if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
    else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
  }
});
// Every clickable data-* element that is not a native control becomes keyboard reachable (WCAG 2.1 AA, DESIGN-BASIS 2.8).
const CLICKABLE = '[data-go],[data-modal],[data-act],[data-drawer],[data-menu],[data-tab],[data-toast],[data-page]';
const a11y = root => root.querySelectorAll && root.querySelectorAll(CLICKABLE).forEach(el => {
  if (el.matches('button,a[href],input,select,textarea,label') || el.hasAttribute('tabindex')) return;
  el.setAttribute('tabindex', '0'); el.setAttribute('role', el.dataset.go && !el.dataset.act ? 'link' : el.dataset.tab ? 'tab' : 'button');
});
new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(n => { if (n.nodeType === 1) { a11y(n); if (n.matches && n.matches(CLICKABLE)) a11y(n.parentElement); } })))
  .observe(document.documentElement, { childList: true, subtree: true });

/* ---------- built-in acts ---------- */
// switch in place: no re-render, so anything typed on the page stays (nfr-langswitch)
MC.act('lang', (c, s) => {
  s.lang = s.lang === 'EN' ? 'BM' : 'EN'; MC.save();
  document.querySelectorAll('.lang').forEach(l => [...l.children].forEach(x => x.classList.toggle('on', x.textContent === s.lang)));
  MC.toast(s.lang === 'BM' ? 'Bahasa Melayu dipilih' : 'English selected', 'Every screen, notification and credential has both languages; this prototype shows English text only.', 'info');
  return false;
});
MC.act('readall', s => { (s.notifs[s.as] || []).forEach(n => (n.unread = false)); });
MC.act('readnotif', (c, s) => { const n = s.notifs[s.as][+c.i]; n.unread = false; if (n.go) setTimeout(() => MC.go(n.go), 0); });
MC.modal('signout', { title: 'Sign out of MCMS?', body: '<p>Unsaved changes on this screen will be lost. You will return to the sign-in page.</p>', confirm: 'Sign out', then: '#/signin' });

/* ---------- flows: guided walk-throughs for inspection ---------- */
MC.flowState = () => { try { return JSON.parse(sessionStorage.getItem('mcms.flow') || 'null'); } catch (e) { return null; } };
MC.startFlow = (id, i = 0) => { sessionStorage.setItem('mcms.flow', JSON.stringify({ id, i })); MC.go(MC.flows.find(f => f.id === id).steps[i].go); };
MC.act('flownext', () => { const f = MC.flowState(); const d = MC.flows.find(x => x.id === f.id); if (f.i < d.steps.length - 1) MC.startFlow(f.id, f.i + 1); else { sessionStorage.removeItem('mcms.flow'); MC.go('#/flows'); MC.toast('Flow finished', d.title, 'ok'); } return false; });
MC.act('flowprev', () => { const f = MC.flowState(); if (f.i > 0) MC.startFlow(f.id, f.i - 1); return false; });
MC.act('flowexit', () => { sessionStorage.removeItem('mcms.flow'); flowbar(); return false; });
function flowbar() {
  document.querySelector('.flowbar')?.remove();
  const f = MC.flowState(); if (!f) return; const d = MC.flows.find(x => x.id === f.id); if (!d) return;
  const st = d.steps[f.i];
  const el = document.createElement('div'); el.className = 'flowbar';
  el.innerHTML = `<div><div class="fn">${d.id} ${d.title} · step ${f.i + 1} of ${d.steps.length}</div><div class="ft">${st.say}</div></div>
    <button data-act="flowprev" ${f.i ? '' : 'disabled'}>Back</button><button class="pri" data-act="flownext">${f.i === d.steps.length - 1 ? 'Finish' : 'Next step'}</button><button data-act="flowexit" aria-label="Exit flow">${MC.icon('x', 16, '#fff')}</button>`;
  document.body.appendChild(el);
}
function devbar() {
  if (document.querySelector('.devbar')) { document.querySelector('[data-act="hot"]').classList.toggle('on', document.body.classList.contains('hot')); return; }
  const el = document.createElement('div'); el.className = 'devbar';
  el.innerHTML = `<button data-go="#/">Personas</button><button data-go="#/screens">All screens</button><button data-go="#/flows">Flows</button><button data-act="hot" title="Outline everything clickable (H)">Hotspots</button><button data-act="reset">Reset data</button>`;
  document.body.appendChild(el);
}
MC.act('hot', () => { document.body.classList.toggle('hot'); devbar(); return false; });
MC.act('reset', () => { MC.reset(); return false; });
document.addEventListener('keydown', e => { if (e.key.toLowerCase() === 'h' && !e.target.closest('input,textarea,select')) MC.acts.hot(); });

/* ---------- boot ---------- */
window.addEventListener('hashchange', () => MC.render());
// Saved state wins, but keys and id'd records added to the seed since it was saved are filled in, so new screens don't break.
function merge(seed, saved) {
  if (!saved) return seed;
  Object.keys(seed).forEach(k => {
    if (!(k in saved)) saved[k] = seed[k];
    else if (Array.isArray(seed[k]) && Array.isArray(saved[k])) {
      const ids = new Set(saved[k].map(r => r && r.id).filter(Boolean));
      seed[k].forEach(r => { if (r && r.id && !ids.has(r.id)) saved[k].push(r); });
    }
  });
  return saved;
}
window.addEventListener('DOMContentLoaded', () => { MC.state = merge(JSON.parse(JSON.stringify(MC.seed)), MC.load()); MC.render(); idle(); });

/* ---------- idle sign-out warning: 2 minutes before the 30-minute timeout; ?idle=1 shows it after 5 s for demos ---------- */
let idleT;
function idle() {
  clearTimeout(idleT);
  const signedIn = MC.cur && !MC.cur.s.shell;
  if (!signedIn) return;
  idleT = setTimeout(() => { if (!document.querySelector('.scrim')) MC.open('timeout'); }, /[?&]idle=1/.test(location.search + location.hash) ? 5000 : 28 * 60 * 1000);
}
['click', 'keydown'].forEach(ev => document.addEventListener(ev, idle, true));
window.addEventListener('hashchange', idle);
})();
