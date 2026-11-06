/* Deputy dean (P2), part A: task queue, proposals and approval, offerings and readiness, results and completion, credentials.
   Reference screen for the pattern: dd/queue. */
(function () {
const { screen, modal, act, flow, icon, head, panel, table, tag, field, inp, sel, area, radios, boxes, note, checks, dl, tabs, kpis, track } = MC;
const AREAS = [['all', 'All'], ['proposals', 'Proposals'], ['offerings', 'Offerings'], ['results', 'Results'], ['creds', 'Credentials'], ['quality', 'Quality'], ['acr', 'ACR and ABC'], ['transfer', 'Transfer']];

screen('dd/queue', { title: 'Task queue', role: 'dd', nav: 'queue', render: (p, s) => {
  const open = s.tasks.filter(t => !t.done), on = p.area || 'all';
  const rows = open.filter(t => on === 'all' || t.area === on);
  return `${head({ title: 'Task queue', sub: `${open.length} decisions waiting for FTMK · reminders go out after 14 days` })}
  ${tabs(AREAS.map(([k, l]) => [k, l, k === 'all' ? open.length : open.filter(t => t.area === k).length]), on).replace(/data-tab="(\w+)"/g, 'data-go="#/dd/queue?area=$1"')}
  ${table({ id: 'ddqueue', search: 'Search the queue', cols: [
    { h: 'Decision', w: '190px', r: t => t.what },
    { h: 'Record', r: t => `${MC.mc(t.ref) ? MC.mcNm(t.ref) : MC.co(t.ref) ? MC.mcNm(MC.co(t.ref).mc) + `<span class="s">${MC.co(t.ref).name}</span>` : t.ref}` },
    { h: 'From', k: 'from' }, { h: 'What the system checked', k: 'check' },
    { h: 'Waiting', num: true, r: t => parseInt(t.waiting) >= 14 ? `<span style="color:var(--err-ink)">${t.waiting}</span><span class="s">Overdue</span>` : t.waiting },
    { h: '', r: t => `<a data-go="${t.go}">Open</a>` }], rows, go: t => t.go, empty: 'Nothing is waiting for you in this group.' })}
  <div class="row">${panel('Decided in the last 7 days', checks(s.tasks.filter(t => t.done).map(t => ['n', `${t.what}: ${MC.mc(t.ref) ? MC.mcName(t.ref) : t.ref}`, t.doneNote || 'Done today'])) || '<div class="muted">Decisions you take appear here with a link to their record.</div>', '', true).replace('class="panel"', 'class="panel grow"')}
  <div style="width:360px">${panel('FTMK at a glance', dl([['Approved micro-credentials', s.mcs.filter(m => m.ptj === 'FTMK' && m.status === 'Approved').length], ['Open or running offerings', s.cohorts.filter(c => /Open|Running|Full/.test(c.status) && MC.mc(c.mc).ptj === 'FTMK').length], ['SAMC with QV expiring in 90 days', s.mcs.filter(m => m.ptj === 'FTMK' && m.cat === 'SAMC' && m.qv && m.qv.status === 'Quality-Verified' && (dt(m.qv.to) - dt(s.today)) / 864e5 <= 90 && dt(m.qv.to) >= dt(s.today)).length]]))}</div></div>`; } });

/* ======================= shared helpers (this file only) ======================= */
const esc = x => String(x == null ? '' : x).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const KIND = { 'In review': 'info', 'Pending approval': 'info', Endorsed: 'info', Approved: 'ok', 'Returned for amendment': 'warn', 'Not approved': 'err', Superseded: 'n', Draft: 'n', Submitted: 'info',
  Verified: 'ok', Open: 'ok', 'Not ready': 'warn', Ready: 'ok', Ended: 'n', Running: 'info', Full: 'info', 'To confirm': 'warn', Confirmed: 'ok', Done: 'ok', 'Not yet': 'warn', Held: 'err', 'Claim omitted': 'warn', Issued: 'ok', Corrected: 'warn', Replaced: 'info', Revoked: 'err',
  Provisional: 'warn', 'Held (integrity)': 'err', Pass: 'ok', Fail: 'err', 'Not issued': 'err', 'Will be issued': 'ok', Support: 'ok', Amend: 'warn', 'Do not support': 'err', Invited: 'n',
  open: 'warn', answered: 'info', resolved: 'ok', Blocking: 'err', 'Non-blocking': 'n', Valid: 'ok', 'Not applicable': 'n' };
const st = t => tag(t, KIND[t] || 'n');
const FT = s => s.mcs.filter(m => m.ptj === 'FTMK');
const FTC = s => s.cohorts.filter(c => (MC.mc(c.mc) || {}).ptj === 'FTMK');
const who = k => (MC.people[k] || {}).name || k;
const ensure = s => { ['ddComments', 'ddSenate'].forEach(k => { if (!s[k]) s[k] = JSON.parse(JSON.stringify(MC.seed[k])); }); return s; };
const log = (s, what, ref) => s.audit.unshift([`${s.today}, ${new Date().toTimeString().slice(0, 5)}`, 'Hakim Rahman', what, ref]);
const tell = (s, role, t, sub, go) => (s.notifs[role] = s.notifs[role] || []).unshift({ t, s: sub, when: '26 Oct', go, unread: true });
const closeTask = (s, f, note) => s.tasks.forEach(t => { if (!t.done && f(t)) { t.done = true; t.doneNote = note; } });
const linkTabs = (list, on, base) => tabs(list, on).replace(/data-tab="([\w-]+)"/g, (m, k) => `data-go="${base}${base.includes('?') ? '&' : '?'}s=${k}"`);
const dt = x => Date.parse(x);
// fr-offqv: a SAMC claim needs QV valid across the whole cohort; null = not a SAMC
// any period in [m.qv, ...m.qvHistory] counts (a renewal keeps the earlier period)
const qvPer = (m, c) => [m.qv].concat(m.qvHistory || []).find(q => q && q.status === 'Quality-Verified' && dt(q.from) <= dt(c.start) && dt(q.to) >= dt(c.end));
const qvOk = (m, c) => m.cat !== 'SAMC' ? null : !!qvPer(m, c);
// learner results: lecturer writes co.learners (DS) or s.lecRes[id].learners; fill fin and res from the weights when missing
const resOf = (s, c) => {
  const L = c.learners || ((s.lecRes || {})[c.id] || {}).learners; if (!L) return null;
  const w = (MC.mc(c.mc) || {}).weights || [];
  L.forEach(l => { if (l.fin == null) l.fin = Math.round(l.m.reduce((a, v, k) => a + (v || 0) * (w[k] || 0) / 100, 0)); if (!l.res) l.res = l.flag ? 'Held' : l.fin >= 50 ? 'Pass' : 'Fail'; });
  return L;
};
const isHeld = l => !!l.flag || l.res === 'Held';
const passed = l => !isHeld(l) && l.fin >= 50;
const compLbl = v => v === 'To confirm' ? 'Waiting for your confirmation' : v === 'Confirmed' ? 'Complete' : v;
const stc = v => tag(compLbl(v), KIND[v] || 'n');
const rdCount = r => r.items.filter(i => i.st !== 'Not yet').length;
const bar = pct => `<div style="height:8px;background:var(--s2)"><div style="height:8px;width:${pct}%;background:var(--primary)"></div></div>`;
const crumbs = (...a) => a;

Object.assign(MC.seed, {
  ddComments: [
    { id: 'DBC1', mc: 'DB', by: 'Ir. Siti Aminah Yusof', role: 'Industry expert', when: '15 Oct 2026', blocking: true, status: 'open', response: '', text: 'Outcome 3 (indexing and query plans) is not assessed by any component. Map it to the practical test or remove the outcome.' },
    { id: 'DBC2', mc: 'DB', by: 'Dr. Rosli Hamzah', role: 'Reviewer', when: '14 Oct 2026', blocking: false, status: 'answered', response: 'Dr. Aina Zulkifli: added a worked third-normal-form example to the Module 2 notes (saved 18 Oct).', text: 'Add a worked normalisation example to Module 2 so learners can check their own work.' },
    { id: 'DBC3', mc: 'DB', by: 'Dr. Rosli Hamzah', role: 'Reviewer', when: '14 Oct 2026', blocking: true, status: 'resolved', response: 'Dr. Aina Zulkifli: Module 1 changed from 44 to 48 hours. The total is now 120 hours.', text: 'Module hours add up to 116, not 120. Correct the learning time.' },
    { id: 'DBC4', mc: 'DB', by: 'Dr. Rosli Hamzah', role: 'Reviewer', when: '15 Oct 2026', blocking: true, status: 'answered', response: 'Dr. Aina Zulkifli: the quiz uses PostgreSQL 16. This is now stated in the delivery section (saved 24 Oct).', text: 'State which SQL dialect the Module 1 quiz uses, so all facilitators mark the same way.' },
  ],
  ddSenate: [],
});
// a newly submitted FTMK proposal (status Submitted) so the deputy dean can assign reviewers
MC.seed.mcs.push({ id: 'WEB', cat: 'UMC', code: 'BITW 2113', name: 'Web Application Development', ptj: 'FTMK', owner: 'rosli', credits: 3, slt: 120, version: 1, status: 'Submitted', submitted: '25 Oct 2026', modules: [['Client-side development', 40], ['Server-side development', 44], ['Deployment and security', 36]], weights: [30, 40, 30] });
MC.seed.tasks.push({ id: 'T-REV-WEB', area: 'proposals', what: 'Assign reviewers', ref: 'WEB', from: 'Dr. Rosli Hamzah', check: 'Submitted 25 Oct; no reviewers assigned yet', waiting: '1 day', go: '#/dd/proposal/WEB' });
// a cohort with an integrity case, so a held result is reachable (Linear Algebra 2026/2)
const LA_L = [['Hannah Wong', 'B032410201', [78, 72, 80]], ['Zafri Ahmad', 'B032410204', [66, 58, 71]], ['Kiran Pillai', 'B032410207', [91, 88, 86]], ['Syed Hakimi', 'B032410210', [45, 40, 52]], ['Mei Ling Chong', 'B032410213', [74, 69, 77]], ['Adam Rahim', 'B032410216', [70, 81, 66]]]
  .map(([n, id, m], i) => { const fin = Math.round(m[0] * .4 + m[1] * .3 + m[2] * .3); return { n, id, m, fin, res: i === 1 ? 'Held' : fin >= 50 ? 'Pass' : 'Fail', src: 'UCreds import 24 Oct', flag: i === 1 ? 'Integrity case IC-2026-07: two project submissions are identical' : '' }; });
MC.seed.cohorts.push({ id: 'LA-2026-2', mc: 'LA', name: 'Cohort 2026/2', start: '6 Jul 2026', end: '24 Oct 2026', enrolled: 6, achieved: 0, status: 'Ended', results: 'Submitted', owner: 'rosli', learners: LA_L });
MC.seed.tasks.push({ id: 'T-VR-LA', area: 'results', what: 'Verify results', ref: 'LA-2026-2', from: 'Dr. Rosli Hamzah', check: '6 results · 1 held for an integrity case', waiting: '2 days', go: '#/dd/verify/LA-2026-2' });
// older history rows so the History tab of ADA is not empty (appended: they are older than every existing row)
MC.seed.audit.push(['14 Oct 2026, 15:00', 'System', 'Placed on the Senate 7/2026 agenda', 'ADA'], ['2 Oct 2026, 11:20', 'Hakim Rahman', 'Endorsed Applied Data Analytics with Python v1; category verified as SAMC', 'ADA'],
  ['28 Sep 2026, 09:05', 'Dr. Aina Zulkifli', 'Submitted proposal Applied Data Analytics with Python v1', 'ADA']);
// earlier FTMK credentials (Data Structures 2026/1) so credential search has more than a handful of rows
['Nur Aisyah Zainal', 'Ethan Gan', 'Siti Hajar Roslan', 'Karthik Menon', 'Wan Nur Izzati', 'Lau Chee Keong', 'Farah Diyana', 'Imran Hakim', 'Joanne Tan', 'Azhar Mutalib', 'Nadiah Samsuri', 'Prakash Nair'].forEach((n, i) => {
  const num = 406 + i, c = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789', k = x => c[(num * 7 + x * 5) % c.length];
  MC.seed.credentials.push({ id: `MCMS-C-2026-000${num}`, code: `${k(1)}${k(2)}${k(3)}${k(4)}-${k(5)}${k(6)}${k(7)}${k(8)}`, who: n, owner: `x${num}`, mc: 'DS', cohort: 'DS-2026-1', issued: '14 Jul 2026', status: 'Issued', revision: 1, result: `Pass, ${58 + (i * 5) % 30}` });
});

/* ======================= dd/proposals ======================= */
// a material change from the lecturer waits as m.pending (version, status, kind, summary, created); it is its own row
const PV = (m, v) => v === 'pending' && m.pending ? m.pending : null;
const pst = (m, pv) => pv ? (pv.status || 'Submitted') : m.status;
const pver = (m, pv) => pv ? pv.version : m.version;
const inV = (m, v) => x => x.mc === m.id && (x.ver || m.version) === v;
const LIVE = /^(Submitted|In review)$/;
const verRows = s => FT(s).flatMap(m => (m.pending ? [{ m, pv: m.pending, v: m.pending.version, status: pst(m, m.pending), when: m.pending.created || m.pending.date || '—' }] : []).concat([{ m, v: m.version, status: m.status, when: m.submitted || m.endorsed || m.approvedBy || '—' }])
  .concat(Array.from({ length: m.version - 1 }, (_, i) => ({ m, v: m.version - 1 - i, status: m.id === 'CSO' ? 'Returned for amendment' : 'Superseded', when: m.id === 'CSO' ? '9 Oct 2026' : 'Earlier version' }))));
const rowGo = r => `#/dd/proposal/${r.m.id}${r.pv ? '?v=pending' : ''}`;
const PRIO = ['Submitted', 'In review', 'Pending approval', 'Returned for amendment', 'Draft', 'Approved', 'Not approved', 'Superseded'];
screen('dd/proposals', { title: 'Proposals and approval', role: 'dd', nav: 'proposals', render: (p, s) => {
  const all = verRows(s).sort((a, b) => PRIO.indexOf(a.status) - PRIO.indexOf(b.status));
  const grp = { review: r => /Submitted|In review|Pending approval/.test(r.status), approved: r => r.status === 'Approved', other: r => !/Submitted|In review|Pending approval|^Approved$/.test(r.status) };
  const on = grp[p.s] ? p.s : 'all', rows = all.filter(r => on === 'all' || grp[on](r));
  return `${head({ title: 'Proposals and approval', sub: `${all.length} proposal versions for FTMK · approval binds to the exact version` })}
  ${linkTabs([['all', 'All versions', all.length], ['review', 'Waiting for a decision', all.filter(grp.review).length], ['approved', 'Approved', all.filter(grp.approved).length], ['other', 'Draft, returned, superseded', all.filter(grp.other).length]], on, '#/dd/proposals')}
  ${table({ id: 'ddprop', search: 'Search proposals', cols: [
    { h: 'Micro-credential', r: r => MC.nm(r.m.cat, MC.mcName(r.m.id)) }, { h: 'Version', num: true, r: r => `v${r.v}` }, { h: 'Status', r: r => st(r.status) },
    { h: 'Proposer', r: r => who(r.m.owner) }, { h: 'Date or decision', r: r => r.when }, { h: '', r: r => `<a data-go="${rowGo(r)}">${/Submitted|In review|Pending/.test(r.status) ? 'Review' : 'Open'}</a>` }],
    rows, go: rowGo, empty: 'No proposals in this group.' })}
  <div class="small muted" style="margin-top:12px">Draft proposals are visible to you but only the proposer can edit them. Approved versions are never deleted; a later approved version supersedes them.</div>`; } });

/* ======================= dd/proposal/:id ======================= */
const REVIEWERS = { 'Dr. Rosli Hamzah (FTMK lecturer)': 'rosli', 'Ir. Siti Aminah Yusof (industry expert)': 'siti', 'Dr. Aina Zulkifli (FTMK lecturer)': 'aina', 'Dr. Farah Nadia Ismail (FKE deputy dean)': 'farah' };
const checkList = (m, rv, done, blk) => {
  const hours = m.modules.reduce((a, x) => a + x[1], 0), w = (m.weights || []).reduce((a, b) => a + b, 0);
  const slt = m.cat === 'UMC' ? m.slt === m.credits * 40 : m.cat === 'SAMC' ? m.slt >= 40 : true;
  return [[slt, 'Learning time', m.cat === 'UMC' ? `${m.slt} h for ${m.credits} credits (40 h per credit)` : m.cat === 'SAMC' ? `${m.slt} h, minimum for a SAMC is 40 h` : `${m.slt} h; no minimum for enrichment`],
    [hours === m.slt, 'Module hours add up', `${m.modules.map(x => x[1]).join(' + ')} = ${hours} h`], [w === 100, 'Assessment weights add up to 100%', m.weights.join(' + ') + ` = ${w}%`],
    [rv.length > 0 && done === rv.length, 'Reviews submitted', `${done} of ${rv.length} reviews submitted`], [true, 'Recognition wording', m.cat === 'SAMC' ? 'No credit or QV claim before QV is recorded' : 'No forbidden claim found'],
    [blk === 0, 'Blocking comments resolved', blk ? `${blk} blocking comment${blk > 1 ? 's' : ''} not resolved` : 'None open']];
};
const syncCm = (s, id) => { const m = MC.mc(id); if (m) m.openComments = s.ddComments.filter(c => inV(m, m.version)(c) && c.status !== 'resolved').length; };
const NEXT = 'Senate 8/2026';

screen('dd/proposal/:id', { title: 'Proposal review', role: 'dd', nav: 'proposals', ex: 'DB', render: (p, s) => {
  ensure(s); const m = MC.mc(p.id);
  if (!m) return head({ crumb: crumbs(['Proposals', '#/dd/proposals'], ['Not found']), title: 'Proposal not found' }) + note('No proposal has this identifier.', 'err');
  if (m.ptj !== 'FTMK') return head({ crumb: crumbs(['Proposals', '#/dd/proposals'], [MC.mcName(m.id)]), title: MC.mcName(m.id), status: st(m.status) }) + note(`This proposal belongs to ${m.ptj}. Only that faculty's deputy dean decides on it; you cannot act here.`, 'warn');
  const pv = PV(m, p.v), V = pver(m, pv), stat = pst(m, pv), vp = pv ? '?v=pending' : '', dv = pv ? ' data-v="pending"' : '';
  const rv = s.reviews.filter(inV(m, V)), done = rv.filter(r => r.status === 'Submitted').length;
  const cs = s.ddComments.filter(inV(m, V)), unres = cs.filter(c => c.status !== 'resolved'), blk = unres.filter(c => c.blocking), nb = unres.filter(c => !c.blocking);
  const live = LIVE.test(stat), ck = checkList(m, rv, done, blk.length), pass = ck.filter(x => x[0]).length;
  const blockers = []; if (blk.length) blockers.push(`${blk.length} blocking comment${blk.length > 1 ? 's' : ''} not resolved`); if (!pv && !m.verifiedCat) blockers.push('category not verified');
  if (!rv.length) blockers.push('no reviewers assigned'); else if (done < rv.length) blockers.push(`${rv.length - done} of ${rv.length} reviews not submitted`);
  const hist = s.audit.filter(a => a[3] === m.id); if (m.approvedBy && !pv && !hist.some(a => /Senate/.test(a[2]))) hist.push(['—', 'Senate', `Approved v${m.version} (${m.approvedBy})`]);
  const recs = ['Support', 'Amend', 'Do not support'].map(r => [r, rv.filter(x => x.rec === r).length]).filter(x => x[1]).map(x => `${x[0]} ${x[1]}`).join(', ') || 'None yet';
  const lastS = s.ddSenate.find(inV(m, V)), defer = lastS && lastS.decision === 'Deferred' ? lastS : null;
  const ack = !pv && s.tasks.find(t => !t.done && t.ref === m.id && t.what === 'Acknowledge minor change');
  const cmtBtn = c => !live ? '' : c.status === 'resolved' ? `<a data-act="cmtopen" data-cid="${c.id}">Reopen</a>` : c.status === 'answered' ? `<a data-modal="cmtres" data-cid="${c.id}">Mark resolved</a>` : '<span class="small muted">Waiting for the lecturer</span>';
  const cmtHtml = cs.length ? cs.map(c => `<div class="check" style="flex-wrap:wrap"><span class="dot ${c.status === 'resolved' ? '' : c.blocking ? 'err' : 'warn'}"></span><div class="grow">${esc(c.text)}
    <span class="small muted" style="display:block">${esc(c.by)}, ${esc(c.role)} · ${c.when}</span>${c.response ? `<span class="small" style="display:block;margin-top:4px"><b>Response:</b> ${esc(c.response)}</span>` : ''}</div>
    <div style="display:flex;gap:8px;align-items:center">${st(c.blocking ? 'Blocking' : 'Non-blocking')}${st(c.status)}${cmtBtn(c)}</div></div>`).join('') : '<div class="muted">No comments on this version.</div>';
  const endorsed = (pv || m).endorsed || '';
  const decision = live ? `${blockers.length ? note(`Endorse is blocked: ${blockers.join('; ')}.`, 'warn') : nb.length ? note(`${nb.length} non-blocking comment${nb.length > 1 ? 's are' : ' is'} not resolved. You can endorse; you will be asked to confirm.`, 'warn') : note('All checks pass. You can endorse.', 'ok')}
      <div class="col" style="gap:8px;margin-top:12px">${blockers.length ? '<span class="btn dis">Endorse (blocked, see above)</span>' : `<span class="btn" data-modal="endorse" data-id="${m.id}"${dv}>Endorse</span>`}
      <span class="btn ter" data-modal="returnprop" data-id="${m.id}"${dv}>Return for amendment</span></div>`
    : stat === 'Pending approval' ? `${note(`Endorsed ${endorsed}. Only Senate decides on approval. Record its decision after the meeting.`, 'ok')}${defer ? `<div style="margin-top:8px">${note(`Deferred at ${esc(defer.ref)}${defer.date ? ' (' + esc(defer.date) + ')' : ''}. Senate has not decided; the version stays Pending approval.`, 'warn')}</div>` : ''}<span class="btn" style="margin-top:12px" data-go="#/dd/senate/${m.id}${vp}">Record Senate decision</span>`
    : note(`This version is ${stat}${(pv || m).approvedBy && !pv ? ` (${m.approvedBy})` : ''}.${stat === 'Returned for amendment' && (pv || m).returnReason ? ' Reason: ' + esc((pv || m).returnReason) + '.' : ''} It is read-only; the History tab shows what happened.`, '');
  const catPanel = panel('Category verification', `${dl([['Proposed by the lecturer', m.cat], ['Verified by you', m.verifiedCat ? `<b>${m.verifiedCat}</b>` : 'Not yet verified']])}
    ${live ? `<div data-form class="col" style="margin-top:12px">${radios('vcat', [['UMC', 'UMC', 'Whole or part of a parent course'], ['SAMC', 'SAMC', 'Stand-alone; QV needed before any credit claim'], ['MCE', 'MCE', 'Enrichment, no credit']], m.verifiedCat || m.cat)}
      <span class="btn ter" data-act="verifycat" data-id="${m.id}">${m.verifiedCat ? 'Change verified category' : 'Verify category'}</span></div>` : ''}`);
  const chg = pv ? panel('Change requested', dl([['Kind', esc(pv.kind || pv.cls || 'Material')], ['Summary', esc(pv.summary || pv.note || '')], ['Created', pv.created || pv.date || '—'], ['Version in force', `v${m.version} stays in force until Senate approves v${pv.version}`]])) : '';
  return `${head({ crumb: crumbs(['Proposals', '#/dd/proposals'], [MC.mcName(m.id)]), title: MC.mcName(m.id), sub: `Version ${V} · proposed by ${who(m.owner)} · ${pv ? 'created ' + (pv.created || pv.date || '') : m.submitted ? 'submitted ' + m.submitted : 'status ' + m.status}`,
    status: st(stat), actions: `${pv ? '' : `<span class="btn ghost" data-go="#/dd/pack?id=${m.id}">Decision pack</span>`}<span class="btn ghost" data-go="#/doc/proposal/${m.id}">Print proposal</span>` })}
  ${ack ? `<div style="margin-bottom:16px">${panel('Minor change to acknowledge', `<div>${esc(ack.check)}</div><div class="small muted" style="margin:4px 0 12px">A minor change creates no new version and needs no Senate decision. Acknowledging records that you have seen it.</div>
    <span class="btn" data-modal="confirm" data-title="Acknowledge this minor change?" data-body="The change is recorded against version ${m.version} and the lecturer is told. No new version is created and nothing needs Senate." data-confirm="Acknowledge" data-act="ackminor" data-id="${m.id}" data-done="Minor change acknowledged|The lecturer is told. No new version was created.">Acknowledge</span>`)}</div>` : ''}
  <div data-tabs>${tabs([['sum', 'Review summary'], ['cm', 'Comments', unres.length || ''], ['hist', 'History']], 'sum')}
  <div data-pane="sum">${kpis([['Category', m.verifiedCat ? `${m.verifiedCat} verified` : `${m.cat} proposed`], ['Learning time', `${m.slt} h`, m.cat === 'UMC' ? `${m.credits} credits after verification` : 'no credit claim at approval'], ['Reviews', `${done} of ${rv.length}`, `Recommendations: ${recs}`], ['Comments not resolved', unres.length, `${blk.length} blocking`], ['System checks', `${pass} of ${ck.length}`, 'pass']])}
    <div class="row" style="margin-top:16px"><div class="col grow">
      ${panel('Reviews', table({ id: 'ddrev', nopager: true, cols: [{ h: 'Reviewer', r: r => `${who(r.reviewer)}<span class="s">${(MC.people[r.reviewer] || {}).post || ''}</span>` }, { h: 'Due', k: 'due' }, { h: 'Status', r: r => st(r.status) },
        { h: 'Recommendation', r: r => r.rec ? st(r.rec) : '—' }, { h: 'Comments', num: true, r: r => r.comments || '—' }], rows: rv, empty: 'No reviewers assigned yet.' }), live ? `<span class="btn ter sm" data-modal="assignrev" data-id="${m.id}"${dv}>Assign reviewers</span>` : '', false)}
      ${panel('System checks', checks(ck.map(([ok, t, d]) => [ok ? '' : 'err', t, d, st(ok ? 'Pass' : 'Fail')])))}
    </div><div class="col" style="width:380px">${pv ? chg : catPanel}${panel('Decision', decision)}</div></div></div>
  <div data-pane="cm">${panel('Comments from reviewers', cmtHtml, live ? `<span class="btn ter sm" data-modal="addcmt" data-id="${m.id}"${dv}>Add comment</span>` : '')}
    <div class="small muted" style="margin-top:8px">A blocking comment stops endorsement until it is resolved. Open means no response yet, answered means the lecturer responded, resolved means you accepted the response. You can resolve a comment only after the lecturer has answered it.</div></div>
  <div data-pane="hist">${table({ id: 'ddprophist', cols: [{ h: 'When', k: 0 }, { h: 'Who', k: 1 }, { h: 'What happened', k: 2 }], rows: hist, empty: 'No history yet.' })}</div></div>`; } });

modal('assignrev', { kicker: c => MC.mcName(c.id), title: 'Assign reviewers', confirm: 'Assign reviewer', done: ['Reviewer assigned', 'They are told and must declare any conflict of interest first. The proposal is now In review.'],
  body: (c, s) => `<p>The reviewer gets a task with the due date. Nothing changes in the proposal and it stays locked.</p><div class="col">
    ${field('Reviewer', sel('reviewer', Object.keys(REVIEWERS)), 'The proposer cannot review their own proposal.', true)}${field('Due date', inp('due', '9 Nov 2026', '', true), '', true)}
    ${field('Review scope', sel('scope', ['Whole proposal', 'Assessment and outcomes only', 'Industry relevance only']))}</div>
    ${note('Conflict of interest: the reviewer declares any conflict before they can open the proposal. If they declare one, the task returns to you.', '')}`, act: 'assignrev' });
act('assignrev', (c, s) => {
  const m = MC.mc(c.id), pv = PV(m, c.v), V = pver(m, pv), key = REVIEWERS[c.reviewer];
  if (key === m.owner) { MC.toast('Not allowed', `${who(key)} proposed this micro-credential and cannot review it.`, 'err'); return false; }
  if (s.reviews.some(r => r.mc === m.id && r.reviewer === key && (r.ver || m.version) === V)) { MC.toast('Already assigned', `${who(key)} already has a review task for this proposal.`, 'err'); return false; }
  s.reviews.push({ id: `RV-D${s.reviews.length + 1}`, mc: m.id, ver: V, reviewer: key, due: c.due, status: 'Invited', coi: '', scope: c.scope });
  if (pv) { if (!pv.status || pv.status === 'Submitted') pv.status = 'In review'; } else if (m.status === 'Submitted') m.status = 'In review';
  closeTask(s, t => t.ref === m.id && t.what === 'Assign reviewers', `Reviewer assigned: ${who(key)}`);
  if (key === 'siti') tell(s, 'ext', `Invitation to review: ${m.name}`, `FTMK, due ${c.due}`, '#/ext/home');
  log(s, `Assigned ${who(key)} to review ${m.name} v${V}, due ${c.due}`, m.id);
});

modal('addcmt', { kicker: c => MC.mcName(c.id), title: 'Add a comment', confirm: 'Add comment', done: ['Comment added', 'The proposer is told and can respond.'],
  body: () => `<div class="col">${field('Comment', area('text', '', 'What needs to change and why', true), '', true)}${boxes('blocking', [['1', 'Blocking', 'The proposal cannot be endorsed until this comment is resolved', false]])}</div>`, act: 'addcmt' });
act('addcmt', (c, s) => { ensure(s); const m = MC.mc(c.id), V = pver(m, PV(m, c.v)); s.ddComments.push({ id: `DDC${s.ddComments.length + 1}`, mc: c.id, ver: V, by: 'Hakim Rahman', role: 'Deputy dean', when: s.today, blocking: c.blocking === '1', status: 'open', response: '', text: c.text }); syncCm(s, c.id); tell(s, 'lec', `New comment on ${MC.mcName(c.id)}`, c.blocking === '1' ? 'Blocking comment from the deputy dean' : 'Comment from the deputy dean', `#/lec/proposal/${c.id}?tab=comments`); log(s, `Added ${c.blocking === '1' ? 'blocking ' : ''}comment on ${MC.mcName(c.id)}`, c.id); });
modal('cmtres', { kicker: c => c.cid, title: 'Mark this comment resolved?', confirm: 'Mark resolved', reason: 'Why the response is enough (kept in the audit trail)', act: 'cmtres', done: ['Comment resolved', 'It stays on record. A blocking comment no longer stops endorsement.'],
  body: (c, s) => { ensure(s); const x = s.ddComments.find(y => y.id === c.cid) || {}; return `<p><b>Comment:</b> ${esc(x.text)}</p><p><b>Lecturer's response:</b> ${esc(x.response)}</p><p class="muted">The comment becomes resolved and is never deleted. ${x.blocking ? 'Endorse is then no longer blocked by it.' : ''} No one is told.</p>`; } });
act('cmtres', (c, s) => { ensure(s); const x = s.ddComments.find(y => y.id === c.cid); if (!x || x.status !== 'answered') { MC.toast('Waiting for the lecturer', 'A comment can be resolved only after the lecturer has responded.', 'err'); return false; }
  x.status = 'resolved'; x.resolution = c.reason; syncCm(s, x.mc); log(s, `Marked comment ${x.id} resolved: ${c.reason}`, x.mc); });
act('cmtopen', (c, s) => { ensure(s); const x = s.ddComments.find(y => y.id === c.cid); x.status = 'open'; syncCm(s, x.mc); tell(s, 'lec', `Comment reopened: ${MC.mcName(x.mc)}`, 'The deputy dean asks for another response', `#/lec/proposal/${x.mc}?tab=comments`); log(s, `Reopened comment ${x.id}`, x.mc); MC.toast('Comment reopened', x.blocking ? 'Endorse is blocked again.' : 'The proposer is asked to respond.', 'warn'); });
act('verifycat', (c, s) => {
  const m = MC.mc(c.id), was = m.verifiedCat; m.verifiedCat = c.vcat; log(s, `Verified category of ${m.name} as ${c.vcat}${c.vcat !== m.cat ? ` (proposed ${m.cat})` : ''}`, m.id);
  if (c.vcat !== m.cat) tell(s, 'lec', `Category differs: ${m.name}`, `Proposed ${m.cat}, verified ${c.vcat}`, '#/lec/proposals');
  MC.toast('Category verified', `${m.name} is verified as ${c.vcat}${was ? ' (changed)' : ''}. The proposed category is kept separately.`);
});
act('ackminor', (c, s) => { const m = MC.mc(c.id); closeTask(s, t => t.ref === m.id && t.what === 'Acknowledge minor change', 'Acknowledged'); tell(s, 'lec', `Minor change acknowledged: ${m.name}`, 'Recorded against the current version', `#/lec/mc/${m.id}`); log(s, `Acknowledged minor change to ${m.name} v${m.version}`, m.id); });

modal('endorse', { kicker: c => MC.mcName(c.id), title: c => `Endorse ${MC.mcName(c.id)} v${pver(MC.mc(c.id), PV(MC.mc(c.id), c.v))}?`, confirm: 'Endorse', act: 'endorse',
  done: c => ['Proposal endorsed', `${MC.mcName(c.id)} is Pending approval and in the ${NEXT} pack. A task to record the Senate decision is now in your queue.`],
  body: (c, s) => { ensure(s); const m = MC.mc(c.id), pv = PV(m, c.v), n = s.ddComments.filter(x => inV(m, pver(m, pv))(x) && !x.blocking && x.status !== 'resolved').length;
    return `<p>Category ${pv ? 'stays' : 'verified as'} <b>${m.verifiedCat || m.cat}</b>. The status becomes <b>Pending approval</b> and the proposal joins the decision pack for ${NEXT}. The content stays locked. ${who(m.owner)} is told.</p>
    <p class="muted">This does not approve it: only Senate decides. No QV, credit or transfer status is set.</p>${n ? note(`${n} non-blocking comment${n > 1 ? 's are' : ' is'} still not resolved. They stay on record with the endorsement.`, 'warn') : ''}`; } });
act('endorse', (c, s) => {
  const m = MC.mc(c.id), pv = PV(m, c.v), t = pv || m, V = pver(m, pv); t.status = 'Pending approval'; t.endorsed = s.today; t.senate = NEXT;
  closeTask(s, k => k.ref === m.id && /^(Endorse proposal|Review new version)$/.test(k.what), `Endorsed; pending ${NEXT}`);
  const tid = `T-SEN-${m.id}${pv ? '-v' + V : ''}`, row = { id: tid, area: 'proposals', what: 'Record Senate decision', ref: m.id, from: NEXT, check: 'On the agenda; decision not recorded', waiting: 'Today', go: `#/dd/senate/${m.id}${pv ? '?v=pending' : ''}`, done: false };
  const old = s.tasks.find(k => k.id === tid); if (old) Object.assign(old, row, { doneNote: '' }); else s.tasks.push(row);
  tell(s, 'lec', `Proposal endorsed: ${m.name}`, 'Pending approval. Senate decides.', '#/lec/proposals'); log(s, `Endorsed ${m.name} v${V}; category verified as ${m.verifiedCat || m.cat}`, m.id);
});

modal('returnprop', { kicker: c => MC.mcName(c.id), title: c => `Return ${MC.mcName(c.id)} for amendment?`, confirm: 'Return for amendment', reason: 'Reason for return (the lecturer sees this)', act: 'returnprop',
  done: c => ['Returned for amendment', `${MC.mcName(c.id)} is unlocked for ${who(MC.mc(c.id).owner)}. They resubmit as a new submission of the same version.`],
  body: c => `<p>The status becomes <b>Returned for amendment</b>, the content is unlocked and ${who(MC.mc(c.id).owner)} is told with your reason. Reviews and comments stay on record. Nothing is endorsed or approved.</p>` });
act('returnprop', (c, s) => {
  const m = MC.mc(c.id), pv = PV(m, c.v), t = pv || m; t.status = 'Returned for amendment'; t.returnReason = c.reason;
  closeTask(s, k => k.ref === m.id && /^(Endorse proposal|Review new version)$/.test(k.what), 'Returned for amendment');
  tell(s, 'lec', `Returned for amendment: ${m.name}`, c.reason, '#/lec/proposals'); log(s, `Returned ${m.name} v${pver(m, pv)} for amendment: ${c.reason}`, m.id);
});

/* ======================= dd/senate/:id ======================= */
const DECISIONS = [['Approved', 'Approved', 'The version is approved as submitted'], ['Approved with conditions', 'Approved with conditions', 'Approved; the conditions are recorded and shown to the lecturer'],
  ['Returned for amendment', 'Returned for amendment', 'Senate asks for changes; the content is unlocked for the lecturer'], ['Not approved', 'Not approved', 'The version is not approved'], ['Deferred', 'Deferred', 'Senate has not decided; the item stays Pending approval']];
const dword = d => d === 'Deferred' ? 'Pending approval' : /^Approved/.test(d) ? 'Approved' : d;
const TRANS = [['Learners already enrolled finish the old version', 'Learners already enrolled finish the old version', 'New registrations use the new version'], ['All learners move to the new version', 'All learners move to the new version', 'Enrolled learners are told'], ['Both versions are treated as equivalent', 'Both versions are treated as equivalent', 'Credentials of either version have the same standing']];
screen('dd/senate/:id', { title: 'Record Senate decision', role: 'dd', nav: 'proposals', ex: 'ADA', render: (p, s) => {
  ensure(s); const m = MC.mc(p.id); const bc = crumbs(['Proposals', '#/dd/proposals'], [m ? MC.mcName(m.id) : 'Not found']);
  if (!m) return head({ crumb: bc, title: 'Proposal not found' }) + note('No proposal has this identifier.', 'err');
  const pv = PV(m, p.v), V = pver(m, pv), stat = pst(m, pv), t = pv || m, back = `#/dd/proposal/${m.id}${pv ? '?v=pending' : ''}`;
  const rec = s.ddSenate.find(inV(m, V));
  const hd = head({ crumb: bc, title: 'Record Senate decision', sub: `${MC.mcName(m.id)} · version ${V} · the Senate never signs in; you record what its minutes say`, status: st(stat) });
  if (stat !== 'Pending approval') return hd + (rec ? panel('Decision recorded', dl([['Meeting', esc(rec.ref)], ['Meeting date', esc(rec.date)], ['Decision', st(dword(rec.decision)) + ` ${esc(rec.decision)}`],
      ['Conditions', esc(rec.conditions) || 'None'], ...(rec.transition ? [['Learners already enrolled', esc(rec.transition)]] : []), ['Minutes extract', esc(rec.minutes)], ['Recorded by', `${rec.by}, ${rec.recorded}`]]) + `<div class="note" style="margin-top:12px">Recording a decision sets the status of this version only. No credit, QV or transfer status was set.</div>`) :
      note(LIVE.test(stat) ? 'This proposal has not been endorsed yet, so it has not gone to Senate.' : `The status is ${stat}. A Senate decision can only be recorded while a version is Pending approval.`, 'warn')) + `<div style="margin-top:16px"><span class="btn ter" data-go="${back}">Back to the proposal</span></div>`;
  const dflt = rec && rec.decision === 'Deferred', agenda = (t.senate || '').split(',')[0].replace(/\s*\(deferred\)/, '');
  return `${hd}${dflt ? note(`Deferred at ${esc(rec.ref)}. Record the new decision when Senate has decided.`, 'warn') : ''}
  <div class="row" style="margin-top:16px"><div class="panel grow"><div class="hd"><h2>Senate decision</h2></div><div class="bd col" data-form>
    <div class="row">${field('Meeting reference', inp('ref', agenda, 'e.g. Senate 8/2026', true), 'As on the minutes', true)}${field('Meeting date', inp('date', '', 'e.g. 14 Oct 2026', true), `Not before the endorsement (${t.endorsed || 'not recorded'}) and not in the future`, true)}</div>
    ${field('Decision', radios('decision', DECISIONS, 'Approved'), '', true)}
    ${field('Conditions or changes required', area('conditions', '', 'Required when the decision is approved with conditions or returned for amendment'), 'Shown to the lecturer and on the record', false)}
    ${pv ? field('Learners already enrolled', radios('transition', TRANS, TRANS[0][0]), 'Applies only if the new version is approved', true) : ''}
    ${field('Minutes extract', area('minutes', '', 'Paste the wording of the resolution from the minutes', true), 'As written in the minutes', true)}
    <span class="btn" style="align-self:flex-start" data-validate data-modal="senate" data-id="${m.id}"${pv ? ' data-v="pending"' : ''}>Review and record decision</span></div></div>
  <div class="col" style="width:380px">${panel('What this records', checks([['', 'Meeting reference, date and decision', 'Kept with the version'], ['', 'Status of this version changes', 'Approved, Not approved, Returned for amendment, or stays Pending approval if deferred'], ['n', 'Nothing else is set', 'No credit, QV, ABC deposit or transfer status. Those are separate decisions']]))}
  ${panel('Where it stands', dl([['Endorsed', t.endorsed || '—'], ['On the agenda of', agenda || 'Not yet'], ['Category', m.verifiedCat || m.cat], ['QV', m.qv ? m.qv.status : 'Not applicable']]))}</div></div>`; },
  mount: root => {
    const ta = root.querySelector('[name="conditions"]'), lab = ta && ta.closest('.field').querySelector('label');
    root.querySelectorAll('[name="decision"]').forEach(r => r.addEventListener('change', () => { const on = /conditions|amendment/.test(r.value) && r.checked; if (r.checked) { ta.required = on; lab.classList.toggle('req', on); } }));
  } });
modal('senate', { kicker: c => MC.mcName(c.id), title: c => `Record "${c.decision}" for ${MC.mcName(c.id)}?`, confirm: 'Record decision', act: 'recsenate', then: c => `#/dd/proposal/${c.id}${c.v === 'pending' ? '?v=pending' : ''}`,
  done: c => ['Senate decision recorded', c.decision === 'Deferred' ? 'The proposal stays Pending approval.' : c.decision === 'Returned for amendment' ? `${MC.mcName(c.id)} is returned for amendment. The lecturer is told.` : `${MC.mcName(c.id)} is now ${c.decision === 'Not approved' ? 'Not approved' : 'Approved'}. The lecturer is told. No QV or credit status was set.`],
  body: c => { const m = MC.mc(c.id), pv = PV(m, c.v), ret = c.decision === 'Returned for amendment';
    return `${dl([['Meeting', esc(c.ref)], ['Date', esc(c.date)], ['Decision', esc(c.decision)], ['Conditions or changes', esc(c.conditions) || 'None'], ...(pv ? [['Learners already enrolled', esc(c.transition)]] : [])])}
    <p style="margin-top:12px">${c.decision === 'Deferred' ? 'The version stays <b>Pending approval</b>.' : ret ? `The status of version ${pver(m, pv)} becomes <b>Returned for amendment</b> and the content is unlocked.` : `The status of version ${pver(m, pv)} becomes <b>${c.decision === 'Not approved' ? 'Not approved' : 'Approved'}</b>.${pv && c.decision !== 'Not approved' ? ` It then replaces version ${m.version}, which becomes Superseded.` : ''}`} The lecturer is told.</p>
    <p class="muted">This does not set any credit, QV or transfer status. Those are separate decisions with their own steps.</p>`; } });
act('recsenate', (c, s) => {
  ensure(s); const m = MC.mc(c.id), pv = PV(m, c.v), V = pver(m, pv), t = pv || m, d = c.decision, when = dt(c.date);
  if (isNaN(when)) { MC.toast('Check the date', 'Enter the meeting date like 14 Oct 2026.', 'err'); return false; }
  if (t.endorsed && when < dt(t.endorsed)) { MC.toast('Check the date', `The meeting cannot be before the endorsement on ${t.endorsed}.`, 'err'); return false; }
  if (when > dt(s.today)) { MC.toast('Check the date', 'The meeting date is in the future. Record the decision after the meeting.', 'err'); return false; }
  s.ddSenate.unshift({ mc: m.id, ver: V, ref: c.ref, date: c.date, decision: d, conditions: c.conditions || '', minutes: c.minutes, transition: pv ? c.transition : '', by: 'Hakim Rahman', recorded: s.today });
  const isTask = k => k.ref === m.id && k.what === 'Record Senate decision';
  if (d === 'Deferred') { s.tasks.forEach(k => { if (!k.done && isTask(k)) k.check = `Deferred at ${c.ref}; record the new decision when Senate decides`; }); t.senate = c.ref; t.deferred = true; }
  else if (d === 'Returned for amendment') { t.status = d; t.returnReason = c.conditions || c.minutes; closeTask(s, isTask, `Returned for amendment, ${c.ref}`); }
  else if (d === 'Not approved') { t.status = 'Not approved'; t.senateDate = c.date; closeTask(s, isTask, `${d}, ${c.ref}`); }
  else {
    if (pv) { s.reviews.forEach(r => { if (r.mc === m.id && !r.ver) r.ver = m.version; }); s.ddComments.forEach(x => { if (x.mc === m.id && !x.ver) x.ver = m.version; });
      m.versions = (m.versions || []).concat({ v: m.version, status: 'Superseded', approvedBy: m.approvedBy, supersededOn: s.today }); m.endorsed = pv.endorsed; m.senate = pv.senate; m.transition = c.transition; m.version = pv.version; delete m.pending; }
    m.status = 'Approved'; m.approvedBy = c.ref; m.senateDate = c.date; if (c.conditions) m.conditions = c.conditions; closeTask(s, isTask, `${d}, ${c.ref}`);
  }
  tell(s, 'lec', `Senate decision: ${m.name}`, `${d}, ${c.ref}${pv && !/^Not|^Return|^Defer/.test(d) ? `. Version ${V} is now in force: ${c.transition}` : ''}`, '#/lec/proposals'); log(s, `Recorded Senate decision for ${m.name} v${V}: ${d} (${c.ref}, ${c.date})`, m.id);
});

/* ======================= dd/pack ======================= */
screen('dd/pack', { title: 'Decision pack', role: 'dd', nav: 'proposals', render: (p, s) => {
  ensure(s); const list = FT(s).filter(m => /Submitted|In review|Pending approval/.test(m.status)); const m = MC.mc(p.id) || MC.mc('DB');
  const rv = s.reviews.filter(inV(m, m.version)), cs = s.ddComments.filter(inV(m, m.version)), done = rv.filter(r => r.status === 'Submitted').length, blk = cs.filter(c => c.blocking && c.status !== 'resolved').length;
  const ck = checkList(m, rv, done, blk), mt = (m.senate || NEXT).split(',')[0], sec = t => `<h3 style="font-size:14px;font-weight:600;margin:20px 0 6px;border-bottom:1px solid #161616;padding-bottom:4px">${t}</h3>`;
  return `${head({ crumb: crumbs(['Proposals', '#/dd/proposals'], ['Decision pack']), title: 'Decision pack', sub: `${mt} · one pack per item · preview of the printed A4 pages`, actions: `<span class="btn" data-go="#/doc/pack/${m.id}">Print pack</span>` })}
  <div class="row" style="margin-bottom:16px;gap:8px;align-items:center"><span class="small muted">Item</span>${list.map(x => `<span class="btn ter sm" ${x.id === m.id ? 'aria-current="page" style="font-weight:600;pointer-events:none"' : ''} data-go="#/dd/pack?id=${x.id}">${esc(x.name)}</span>`).join('')}</div>
  <div style="width:794px;max-width:100%;min-height:900px;margin:0 auto;background:#fff;color:#161616;border:1px solid var(--hair);padding:56px 64px;font-size:13px;line-height:20px">
    <div style="display:flex;justify-content:space-between;font-size:12px;color:#525252"><span>Universiti Teknikal Malaysia Melaka · Centre for Open and Distance Learning</span><span>${esc(mt)}</span></div>
    <h2 style="font-size:24px;line-height:32px;font-weight:400;margin:16px 0 4px">Decision pack: ${esc(MC.mcName(m.id))}</h2>
    <div class="muted">Version ${m.version} · ${m.ptj} · proposed by ${who(m.owner)} · generated ${s.today}</div>
    ${sec('1. Proposal')}${dl([['Category', `${m.cat} proposed${m.verifiedCat ? `, verified as ${m.verifiedCat}` : ', not yet verified by the deputy dean'}`], ['Learning time', `${m.slt} hours${m.cat === 'UMC' ? `, ${m.credits} credits on verification` : ''}`], ['Status', m.status]])}
    <table style="width:100%;margin-top:8px"><thead><tr><th>Module</th><th class="num">Hours</th><th class="num">Weight</th></tr></thead><tbody>${m.modules.map((x, i) => `<tr><td>${esc(x[0])}</td><td class="num">${x[1]}</td><td class="num">${m.weights[i]}%</td></tr>`).join('')}
      <tr><td><b>Total</b></td><td class="num"><b>${m.modules.reduce((a, x) => a + x[1], 0)}</b></td><td class="num"><b>${m.weights.reduce((a, b) => a + b, 0)}%</b></td></tr></tbody></table>
    ${sec('2. Reviews')}${rv.length ? rv.map(r => `<div>${who(r.reviewer)}: ${r.status === 'Submitted' ? `${r.rec}, ${r.comments} comment${r.comments > 1 ? 's' : ''}` : `${r.status}, due ${r.due}`}</div>`).join('') : '<div>No reviewers assigned.</div>'}
    ${sec('3. Comments and how they were resolved')}${cs.length ? cs.map(c => `<div style="margin-bottom:6px">${c.blocking ? 'Blocking' : 'Non-blocking'}, <b>${c.status}</b>: ${esc(c.text)}${c.response ? `<br><span style="color:#525252">${esc(c.response)}</span>` : ''}</div>`).join('') : '<div>None.</div>'}
    ${sec('4. System checks')}${ck.map(([ok, t, d]) => `<div>${ok ? 'Pass' : 'Fail'}: ${t}. ${d}</div>`).join('')}
    ${sec('5. Endorsement')}<div>${m.status === 'Pending approval' ? `Endorsed by Hakim Rahman, deputy dean (Academic) FTMK, on ${m.endorsed}.` : /Submitted|In review/.test(m.status) ? 'Not yet endorsed. The deputy dean endorses once blocking comments are resolved and the category is verified.' : `Status ${m.status}.`}</div>
    <div style="margin-top:28px;font-size:12px;color:#525252;border-top:1px solid #c6c6c6;padding-top:8px">Approval applies to this exact version. It does not grant QV, credit, ABC deposit or transfer status; each is a separate decision.</div></div>`; } });

/* ======================= dd/offerings ======================= */
// The checklist belongs to the lecturer (s.lecRead[cohortId]); RD0 is only used when a cohort has none yet.
const RD0 = [['B2.1', 'B2 Content', 'Teaching materials loaded in UCreds', 'Lecturer'], ['B2.2', 'B2 Content', 'Assessment instruments prepared and moderated', 'Lecturer'], ['B3.1', 'B3 Facilitation', 'Facilitator assigned', 'Lecturer'], ['B3.2', 'B3 Facilitation', 'Facilitator qualification evidence', 'Lecturer'],
  ['B4.1', 'B4 Platform', 'UCreds course shell created', 'CODL'], ['B4.2', 'B4 Platform', 'Enrolment export tested', 'CODL'], ['B5.1', 'B5 Support', 'Learner support contact published', 'Lecturer'], ['B5.2', 'B5 Support', 'Orientation session scheduled', 'Lecturer'],
  ['B6.1', 'B6 Fee and places', 'Fee and places set', 'Lecturer'], ['B6.2', 'B6 Fee and places', 'Payment service configured', 'CODL'], ['B7.1', 'B7 Wording', 'Recognition wording checked against the template', 'Deputy dean'], ['B7.2', 'B7 Wording', 'Credential template previewed in BM and EN', 'Lecturer']];
const rdGet = (s, c) => { s.lecRead = s.lecRead || {}; if (!s.lecRead[c.id]) { const n = c.readiness == null ? 12 : c.readiness; s.lecRead[c.id] = { sent: true, items: RD0.map(([k, g, t, owner], i) => ({ k, g, t, owner, st: i < n ? 'Yes' : 'Not yet', ev: i < n ? (owner === 'Lecturer' ? 'Provided by the lecturer' : `Done by ${owner}`) : '' })) }; } return s.lecRead[c.id]; };
const own = i => i.owner || i.o || 'Lecturer', grpOf = i => i.g || `B${String(i.k).slice(1, 2)}`;
// a lecturer item with fresh evidence still needs the deputy dean's acceptance
const needsAcc = (i, s) => own(i) === 'Lecturer' && i.st === 'Yes' && !i.accepted && !!i.ev && (/^Uploaded/.test(i.ev) || i.ev.includes(s.today));
const rdBlock = (rd, s) => { const open = rd.items.filter(i => i.st === 'Not yet'), acc = rd.items.filter(i => needsAcc(i, s));
  return !rd.sent ? 'the lecturer has not sent the checklist yet' : open.length ? `${open.length} of ${rd.items.length} items open: ${open.map(i => i.t.toLowerCase()).join(', ')}` : acc.length ? `${acc.length} item${acc.length > 1 ? 's need' : ' needs'} your acceptance: ${acc.map(i => i.t.toLowerCase()).join(', ')}` : ''; };
const goCohort = c => c.status === 'Ended' ? (c.results === 'Submitted' ? `#/dd/verify/${c.id}` : c.results === 'Verified' && c.completion !== 'Confirmed' && roster(c) ? `#/dd/completion/${c.id}` : '#/dd/results') : `#/dd/readiness/${c.id}`;
screen('dd/offerings', { title: 'Offerings and readiness', role: 'dd', nav: 'offerings', render: (p, s) => {
  const all = FTC(s), grp = { ready: c => c.status === 'Not ready', live: c => /Open|Full|Running/.test(c.status), ended: c => c.status === 'Ended' };
  const on = grp[p.s] ? p.s : 'all', rows = all.filter(c => on === 'all' || grp[on](c)), rdRead = c => (s.lecRead || {})[c.id];
  return `${head({ title: 'Offerings and readiness', sub: `${all.length} FTMK cohorts · an offering opens for registration only after you confirm it is ready` })}
  ${linkTabs([['all', 'All cohorts', all.length], ['ready', 'Not ready', all.filter(grp.ready).length], ['live', 'Open, full or running', all.filter(grp.live).length], ['ended', 'Ended', all.filter(grp.ended).length]], on, '#/dd/offerings')}
  ${table({ id: 'ddoff', search: 'Search cohorts', cols: [
    { h: 'Micro-credential', r: c => `${MC.mcNm(c.mc)}<span class="s">${c.name}</span>` }, { h: 'Dates', r: c => `${c.start} to ${c.end}` }, { h: 'Status', r: c => st(c.status) },
    { h: 'Readiness', r: c => { const r = rdRead(c); return c.status === 'Not ready' ? `<span style="color:var(--warn-ink)">${r ? rdCount(r) : c.readiness == null ? 0 : c.readiness} of ${r ? r.items.length : 12} items done</span>${r && !r.sent ? '<span class="s">Not sent to you yet</span>' : ''}` : /Open|Full|Running/.test(c.status) ? 'Confirmed' : '—'; } },
    { h: 'QV covers dates', r: c => { const q = qvOk(MC.mc(c.mc), c); return q === null ? '<span class="muted">Not a SAMC</span>' : q ? st('Valid') : tag('QV not valid for dates', 'warn'); } },
    { h: 'Places', num: true, r: c => c.places ? `${c.taken} of ${c.places}` : `${c.enrolled || 0} enrolled` }, { h: '', r: c => `<a data-go="${goCohort(c)}">${c.status === 'Not ready' ? 'Review readiness' : 'Open'}</a>` }],
    rows, go: goCohort, empty: 'No cohorts in this group.' })}
  <div class="small muted" style="margin-top:12px">For a SAMC the QV must be valid for the whole cohort. When it is not, the catalogue hides the QV claim, credit and MQF level and shows "QV renewal pending".</div>`; } });

/* ======================= dd/readiness/:id ======================= */
screen('dd/readiness/:id', { title: 'Confirm readiness', role: 'dd', nav: 'offerings', ex: 'DS-2027-1', render: (p, s) => {
  const c = MC.co(p.id); const bc = crumbs(['Offerings', '#/dd/offerings'], [c ? `${MC.mcName(c.mc)} ${c.name}` : 'Not found']);
  if (!c) return head({ crumb: bc, title: 'Cohort not found' }) + note('No cohort has this identifier.', 'err');
  const m = MC.mc(c.mc), rd = rdGet(s, c), items = rd.items, open = items.filter(i => i.st === 'Not yet'), lecOpen = open.filter(i => own(i) === 'Lecturer'), q = qvOk(m, c), per = qvPer(m, c), isNR = c.status === 'Not ready', groups = [...new Set(items.map(grpOf))];
  const why = rdBlock(rd, s), who1 = i => own(i) === 'Lecturer' ? 'the lecturer' : own(i) === 'CODL' ? 'CODL' : 'you';
  const qvLine = q === null ? ['', 'QV', `Not needed: a ${MC.CATNAME[m.cat]} carries no QV claim`] : q ? ['', 'QV covers the cohort dates', `Valid ${per.from} to ${per.to}`] : ['warn', 'QV does not cover the cohort dates', `Cohort runs ${c.start} to ${c.end}; QV ${m.qv && m.qv.to ? 'ends ' + m.qv.to : 'is ' + (m.qv ? m.qv.status : 'missing')}. The QV claim, credit and MQF level stay hidden (QV renewal pending).`];
  const itemBtn = i => !isNR ? '' : needsAcc(i, s) ? `<span class="btn ter sm" data-act="rdaccept" data-id="${c.id}" data-k="${i.k}" data-done="Evidence accepted|${esc(i.t)} is signed off.">Accept evidence</span>`
    : i.st !== 'Not yet' ? '' : own(i) === 'Deputy dean' ? `<span class="btn ter sm" data-act="rdaccept" data-id="${c.id}" data-k="${i.k}" data-done="Item done|${esc(i.t)} is marked done by you.">Mark done</span>` : `<span class="small muted">Waiting for ${who1(i)}</span>`;
  const opts = why ? [['Not ready', 'Not ready', 'Send the checklist back to the lecturer with your reason']] : [['Ready', 'Ready', 'Opens the offering for registration'], ['Ready with conditions', 'Ready with conditions', 'Opens it; the conditions stay on the record and are shown to the lecturer'], ['Not ready', 'Not ready', 'Send the checklist back to the lecturer with your reason']];
  return `${head({ crumb: bc, title: `Confirm readiness: ${c.name}`, sub: `${MC.mcName(c.mc)} · ${c.start} to ${c.end} · ${c.places || '—'} places · ${c.fee ? MC.rm(c.fee) : ''}`, status: st(c.status) })}
  ${track([[rd.sent ? 'done' : 'now', 'Lecturer part', rd.sent ? 'Sent to you' : lecOpen.length ? `${lecOpen.length} of their items still open` : 'Not sent to you yet'], [isNR ? (why ? '' : 'now') : 'done', 'Your confirmation', isNR ? (why ? 'Waiting' : 'Ready to decide') : 'Confirmed'], [isNR ? '' : 'done', 'Open for registration', isNR ? 'After you confirm' : 'In the public catalogue']])}
  <div class="row" style="margin-top:16px"><div class="col grow">
    ${panel('Appendix B checklist', groups.map(g => `<div class="small muted" style="margin-top:12px;font-weight:600">${g}</div>${checks(items.filter(i => grpOf(i) === g).map(i => [i.st === 'Not yet' ? 'warn' : '', i.t, i.st === 'Not yet' ? `Waiting for ${who1(i)}` : esc(i.ev || i.st), `${i.st === 'Not yet' ? tag('Open', 'warn') : st(i.st === 'Yes' ? 'Done' : i.st)} ${itemBtn(i)}`]))}`).join(''), `${rdCount(rd)} of ${items.length} done`)}
    ${panel('System check', checks([qvLine, ['', 'Approved version', `${m.name} v${m.version}, ${m.approvedBy || m.status}`]]))}
  </div><div class="col" style="width:380px">
    ${panel('Lecturer part', dl([['Answered by', who(c.owner)], ['Sent to you', rd.sent ? 'Yes' : 'Not yet'], ['Items done', `${rdCount(rd)} of ${items.length}`], ['Status', why && !rd.sent ? tag('Waiting for the lecturer', 'warn') : lecOpen.length ? tag('Items open', 'warn') : st('Done')]]))}
    ${panel('Deputy dean confirmation', isNR ? `${why ? note(`Ready is not possible yet: ${why}.`, 'warn') : note(`All ${items.length} items are done. You can decide.`, 'ok')}
      ${rd.sent ? `<div data-form class="col" style="margin-top:12px">${field('Decision', radios('dec', opts, opts[0][0]), '', true)}
        ${field('Conditions or reason', area('rdnote', '', 'Conditions for Ready with conditions, or what the lecturer must fix'), 'Shown to the lecturer', false)}
        <span class="btn" style="align-self:flex-start" data-validate data-modal="readyconfirm" data-id="${c.id}">Review and record decision</span></div>` : '<div style="margin-top:12px"><span class="btn dis">Record decision (waiting for the lecturer to send the checklist)</span></div>'}`
      : `${note(/Open|Full|Running/.test(c.status) ? `Confirmed. The offering is ${c.status} and listed in the public catalogue.${c.conditions ? ' Conditions: ' + esc(c.conditions) : ''}` : `This cohort is ${c.status}; readiness is closed.`, 'ok')}<a style="display:block;margin-top:12px" data-go="#/public/catalogue">See it in the public catalogue</a>`)}
  </div></div>`; },
  mount: root => {
    const ta = root.querySelector('[name="rdnote"]'), lab = ta && ta.closest('.field').querySelector('label'); if (!ta) return;
    const sync = () => { const r = root.querySelector('[name="dec"]:checked'), on = !!r && r.value !== 'Ready'; ta.required = on; lab.classList.toggle('req', on); };
    root.querySelectorAll('[name="dec"]').forEach(r => r.addEventListener('change', sync)); sync();
  } });
act('rdaccept', (c, s) => {
  const k = MC.co(c.id), rd = rdGet(s, k), i = rd.items.find(x => x.k === c.k); if (!i) return false;
  const mine = own(i) === 'Deputy dean'; i.st = 'Yes'; i.accepted = true; i.ev = mine ? `Done by the deputy dean, ${s.today}` : `${i.ev} (accepted by the deputy dean)`; k.readiness = rdCount(rd);
  log(s, `${mine ? 'Marked done' : 'Accepted evidence for'} readiness item "${i.t}" for ${MC.mcName(k.mc)} ${k.name}`, k.id);
});
modal('readyconfirm', { kicker: c => c.id, title: c => c.dec === 'Not ready' ? `Send ${MC.co(c.id).name} back to the lecturer?` : `Confirm ${MC.co(c.id).name} is ${c.dec === 'Ready' ? 'ready' : 'ready with conditions'} and open it?`, confirm: c => c.dec === 'Not ready' ? 'Mark not ready' : 'Confirm ready', act: 'readyconfirm', then: c => `#/dd/readiness/${c.id}`,
  done: c => c.dec === 'Not ready' ? ['Sent back to the lecturer', 'The checklist is Not ready. The lecturer sends it again when the reason is fixed.'] : ['Cohort is open', `${MC.mcName(MC.co(c.id).mc)} ${MC.co(c.id).name} is in the public catalogue and open for registration.`],
  body: c => { const k = MC.co(c.id), m = MC.mc(k.mc), q = qvOk(m, k), n = esc(c.rdnote);
    if (c.dec === 'Not ready') return `<p>The status stays <b>Not ready</b>. The checklist goes back to ${who(k.owner)} with your reason: ${n || 'none given'}. She sends it again when it is fixed.</p><p class="muted">The cohort is not opened and does not appear in the catalogue.</p>`;
    return `<p>The status becomes <b>Open</b>. The cohort appears in the public catalogue with ${k.places} places at ${MC.rm(k.fee)} and registration starts.${c.dec === 'Ready with conditions' ? ` Conditions on the record: ${n}.` : ''} ${who(k.owner)} is told.</p><p class="muted">This does not verify any results or issue anything.</p>
    ${q === false ? note('QV does not cover this cohort\'s dates. It opens, but the QV claim, credit and MQF level stay hidden until QV is valid.', 'warn') : ''}`; } });
act('readyconfirm', (c, s) => {
  const k = MC.co(c.id), rd = rdGet(s, k), why = rdBlock(rd, s);
  if (c.dec !== 'Not ready' && why) { MC.toast('Not possible yet', `Ready is blocked: ${why}.`, 'err'); return false; }
  if (c.dec === 'Not ready') {
    rd.sent = false; s.tasks.forEach(t => { if (!t.done && t.ref === k.id && t.what === 'Confirm readiness') t.check = `Returned to the lecturer ${s.today}: ${c.rdnote}`; });
    tell(s, 'lec', `Readiness returned: ${MC.mcName(k.mc)} ${k.name}`, c.rdnote, `#/lec/readiness/${k.id}`); log(s, `Marked ${MC.mcName(k.mc)} ${k.name} not ready: ${c.rdnote}`, k.id); return;
  }
  k.status = 'Open'; k.taken = k.taken || 0; if (c.dec === 'Ready with conditions') k.conditions = c.rdnote; closeTask(s, t => t.ref === k.id && t.what === 'Confirm readiness', `${c.dec}; registration open`);
  tell(s, 'lec', `${MC.mcName(k.mc)} ${k.name} is open`, c.dec === 'Ready with conditions' ? `Conditions: ${c.rdnote}` : 'Listed in the public catalogue', '#/lec/cohorts'); log(s, `Confirmed ${MC.mcName(k.mc)} ${k.name} ${c.dec.toLowerCase()}; registration open${c.rdnote && c.dec !== 'Ready' ? ': ' + c.rdnote : ''}`, k.id);
});

/* ======================= dd/results ======================= */
const compOf = c => c.completion || (c.results === 'Verified' ? 'Confirmed' : '');
screen('dd/results', { title: 'Results and completion', role: 'dd', nav: 'results', render: (p, s) => {
  const all = FTC(s), rows = all.filter(c => /Submitted|Verified/.test(c.results || '')), draft = all.filter(c => c.results === 'Draft').length;
  const next = c => c.results === 'Submitted' ? ['Verify results', `#/dd/verify/${c.id}`] : compOf(c) === 'To confirm' ? ['Confirm completion', `#/dd/completion/${c.id}`] : ['View', `#/dd/completion/${c.id}`];
  return `${head({ title: 'Results and completion', sub: 'Two separate decisions: verify the results first, then confirm completion so the system issues credentials' })}
  ${kpis([['Waiting for verification', rows.filter(c => c.results === 'Submitted').length, 'results submitted by a lecturer'], ['Completion to confirm', rows.filter(c => compOf(c) === 'To confirm').length, 'results verified'], ['Still with the lecturer', draft, 'results in draft']])}
  <div style="height:16px"></div>${table({ id: 'ddres', search: 'Search cohorts', cols: [
    { h: 'Micro-credential', r: c => `${MC.mcNm(c.mc)}<span class="s">${c.name}</span>` }, { h: 'Enrolled', num: true, r: c => c.enrolled }, { h: 'Passed', num: true, r: c => { const L = resOf(s, c); return c.results === 'Verified' ? (c.achieved || (L ? L.filter(passed).length : '—')) : L ? L.filter(passed).length + ' provisional' : '—'; } },
    { h: 'Results', r: c => st(c.results) }, { h: 'Completion', r: c => compOf(c) ? stc(compOf(c)) : '<span class="muted">After verification</span>' }, { h: '', r: c => `<a data-go="${next(c)[1]}">${next(c)[0]}</a>` }],
    rows, go: c => next(c)[1], empty: 'No cohorts with submitted or verified results.' })}`; } });

/* ======================= dd/verify/:id ======================= */
const badgesOf = l => (l.m || []).filter(v => v >= 50).length;
screen('dd/verify/:id', { title: 'Verify results', role: 'dd', nav: 'results', ex: 'DS-2026-2', render: (p, s) => {
  const c = MC.co(p.id); const bc = crumbs(['Results and completion', '#/dd/results'], [c ? `${MC.mcName(c.mc)} ${c.name}` : 'Not found']);
  if (!c) return head({ crumb: bc, title: 'Cohort not found' }) + note('No cohort has this identifier.', 'err');
  const m = MC.mc(c.mc), L = resOf(s, c);
  if (!L) return head({ crumb: bc, title: `Verify results: ${c.name}`, status: st(c.results || 'Draft') }) + note(c.results === 'Verified' ? 'These results are already verified.' : 'No learner results were submitted for this cohort.', c.results === 'Verified' ? 'ok' : 'warn') + `<div style="margin-top:16px"><span class="btn ter" data-go="#/dd/results">Back to results</span></div>`;
  const held = L.filter(isHeld), live = L.filter(l => !isHeld(l)), pass = live.filter(passed).length, badges = live.reduce((a, l) => a + badgesOf(l), 0);
  const sub = c.results === 'Submitted', canClose = /Submitted|Verified/.test(c.results || '');
  const act1 = sub ? `<span class="btn ter" data-modal="retres" data-id="${c.id}">Return to lecturer</span><span class="btn" data-modal="verifyres" data-id="${c.id}">Verify ${live.length} results</span>` : '';
  return `${head({ crumb: bc, title: `Verify results: ${MC.mcName(c.mc)} ${c.name}`, sub: `${c.start} to ${c.end} · submitted by ${who(c.owner)} · verifying does not issue certificates`, status: st(c.results), actions: act1 })}
  ${c.results === 'Verified' ? note(`Verified ${c.verifiedOn || ''}. ${c.badges != null ? c.badges + ' module badges were created. ' : ''}Next: confirm completion so the system issues the credentials.`, 'ok') + `<div style="margin:12px 0"><span class="btn" data-go="#/dd/completion/${c.id}">Confirm completion</span></div>` : ''}
  ${c.results === 'Draft' ? note('Returned to the lecturer. They resubmit when the results are corrected.', 'warn') : ''}
  ${sub ? note('All results are Provisional until you verify them. Imported marks and marks edited by the lecturer are labelled with their source below.', '') : ''}
  <div style="height:16px"></div>${kpis([['Learners', L.length, `${c.enrolled} enrolled`], ['Pass', pass, `pass mark 50`], ['Below pass mark', live.length - pass, 'no module badge'], ['Held (integrity)', held.length, 'excluded until the case is closed'], ['Module badges', badges, 'created on verify']])}
  <div style="height:16px"></div>${table({ id: 'ddverify', search: 'Search learners', cols: [{ h: 'Learner', r: l => `${l.n}<span class="s">${l.id}</span>` },
    ...m.modules.map((x, i) => ({ h: `${x[0]}<span class="s">${m.weights[i]}%</span>`, num: true, r: l => l.m[i] })), { h: 'Final', num: true, r: l => `<b>${l.fin}</b>` }, { h: 'Result', r: l => st(isHeld(l) ? 'Held' : l.res) },
    { h: 'Source', r: l => l.src }, { h: 'Status', r: l => isHeld(l) ? `${st('Held (integrity)')}<span class="s">${esc(l.flag)}</span>` : c.results === 'Verified' ? st('Verified') : st('Provisional') },
    { h: '', r: l => isHeld(l) && canClose ? `<a data-modal="closecase" data-id="${c.id}" data-li="${L.indexOf(l)}">Close case and verify</a>` : '' }], rows: L, size: 10, empty: 'No learners.' })}
  <div class="small muted" style="margin-top:12px">A held row is excluded from verification until the integrity case is closed. A learner below the pass mark is verified as Fail and gets no module badge.</div>`; } });
modal('verifyres', { kicker: c => `${c.id}`, title: c => `Verify ${resOf(MC.state, MC.co(c.id)).filter(l => !isHeld(l)).length} results for ${MC.mcName(MC.co(c.id).mc)} ${MC.co(c.id).name}?`, confirm: c => `Verify ${resOf(MC.state, MC.co(c.id)).filter(l => !isHeld(l)).length} results`, act: 'verifyres',
  done: c => ['Results verified', `${MC.co(c.id).badges} module badges created. Confirm completion next.`],
  body: c => { const k = MC.co(c.id), L = resOf(MC.state, k), ls = L.filter(l => !isHeld(l)), b = ls.reduce((a, l) => a + badgesOf(l), 0);
    return `<p>The results become <b>Verified</b> and <b>${b} module achievements and badges</b> are created for ${ls.length} learners. ${L.length - ls.length ? `${L.length - ls.length} held result is excluded. ` : ''}${who(k.owner)} is told.</p>
    <p class="muted">This does <b>not</b> confirm completion and issues no certificate or Digital Credential. Completion is the next, separate decision.</p>`; } });
act('verifyres', (c, s) => {
  const k = MC.co(c.id), ls = resOf(s, k).filter(l => !isHeld(l)); let b = 0;
  ls.forEach(l => { l.verified = true; l.badges = badgesOf(l); b += l.badges; });
  k.results = 'Verified'; k.badges = b; k.achieved = ls.filter(passed).length; k.verifiedOn = s.today; k.completion = 'To confirm';
  closeTask(s, t => t.ref === k.id && t.what === 'Verify results', `Verified ${ls.length} results, ${b} module badges`);
  const tid = `T-CC-${k.id}`; if (!s.tasks.some(t => t.id === tid)) s.tasks.push({ id: tid, area: 'results', what: 'Confirm completion', ref: k.id, from: 'Hakim Rahman (results verified today)', check: `${k.achieved} pass, ${ls.length - k.achieved} below pass mark · credentials follow`, waiting: 'Today', go: `#/dd/completion/${k.id}` });
  tell(s, 'lec', `Results verified: ${MC.mcName(k.mc)} ${k.name}`, `${ls.length} results, ${b} module badges`, `#/lec/cohort/${k.id}`); log(s, `Verified ${ls.length} results, ${MC.mcName(k.mc)} ${k.name}; ${b} module badges created`, k.id);
});
modal('closecase', { kicker: c => c.id, title: c => `Close the integrity case for ${resOf(MC.state, MC.co(c.id))[+c.li].n} and verify the result?`, confirm: 'Close case and verify', reason: 'Outcome of the integrity case (kept in the audit trail)', act: 'closecase',
  done: ['Case closed', 'The result is released from hold.'],
  body: c => { const k = MC.co(c.id), l = resOf(MC.state, k)[+c.li], v = k.results === 'Verified';
    return `<p>The hold on ${esc(l.n)} (${esc(l.flag)}) is lifted. The final mark of ${l.fin} stays as entered. ${v ? 'The result is verified now and module badges are created.' : 'The result joins the other results and is verified with them.'} ${who(k.owner)} is told.</p>
    <p class="muted">${v ? 'Completion for this learner is a separate decision: a task is added to your queue.' : 'Nothing is issued by this step.'} Use this only when the integrity case has been closed by the proper process.</p>`; } });
act('closecase', (c, s) => {
  const k = MC.co(c.id), l = resOf(s, k)[+c.li], was = l.flag; l.flag = ''; l.res = l.fin >= 50 ? 'Pass' : 'Fail';
  if (k.results === 'Verified') { l.verified = true; l.badges = badgesOf(l); k.badges = (k.badges || 0) + l.badges; k.achieved = (k.achieved || 0) + (l.res === 'Pass' ? 1 : 0);
    if (k.completion === 'Confirmed') k.completion = 'To confirm'; const tid = `T-CC-${k.id}-${l.id}`;
    if (!s.tasks.some(t => t.id === tid)) s.tasks.push({ id: tid, area: 'results', what: 'Confirm completion', ref: k.id, from: 'Hakim Rahman (integrity case closed)', check: `${l.n}: case closed, result verified`, waiting: 'Today', go: `#/dd/completion/${k.id}` }); }
  tell(s, 'lec', `Integrity case closed: ${MC.mcName(k.mc)} ${k.name}`, `${l.n}: ${c.reason}`, `#/lec/cohort/${k.id}`); log(s, `Closed integrity case (${was}) for ${l.n}, ${MC.mcName(k.mc)} ${k.name}: ${c.reason}`, k.id);
});
modal('retres', { kicker: c => c.id, title: c => `Return ${MC.co(c.id).name} results to the lecturer?`, confirm: 'Return to lecturer', reason: 'What the lecturer must correct', act: 'retres',
  done: ['Results returned', 'The lecturer is told with your reason and resubmits when corrected.'], body: c => `<p>The results go back to <b>Draft</b> and ${who(MC.co(c.id).owner)} can edit them. Nothing is verified or issued.</p>` });
act('retres', (c, s) => {
  const k = MC.co(c.id); k.results = 'Draft'; s.tasks.forEach(t => { if (!t.done && t.ref === k.id && t.what === 'Verify results') t.check = `Returned to the lecturer ${s.today}; waiting for resubmission`; });
  tell(s, 'lec', `Results returned: ${MC.mcName(k.mc)} ${k.name}`, c.reason, `#/lec/cohort/${k.id}`); log(s, `Returned results of ${MC.mcName(k.mc)} ${k.name} to the lecturer: ${c.reason}`, k.id);
});

/* ======================= dd/completion/:id ======================= */
const LE_NAMES = ['Amir Hakimi', 'Nurul Huda Salleh', 'Farhan Ismail', 'Siew Ling Tan', 'Kogilavani Subramaniam', 'Mohd Zaki Hashim', 'Aisyah Binti Mansor', 'Edwin Lau', 'Rohana Abdullah', 'Hazwan Kamal', 'Intan Syazwani', 'Vijay Nair', 'Chan Wai Kit', 'Norhaslinda Yusof',
  'Ikmal Hisham', 'Sharifah Aini', 'Thomas Ng', 'Mastura Jamal', 'Azlan Shah', 'Evelyn Foo', 'Khairul Anwar', 'Puteri Balqis', 'Ganesh Rao', 'Lim Shu Fen', 'Zainal Abidin', 'Sofia Marzuki', 'Darren Koh', 'Hasniza Ramli', 'Yusri Latif', 'Cheryl Wong',
  'Muthu Krishnan', 'Faridah Osman', 'Ahmad Syukri', 'Janice Tay', 'Nazrin Idris', 'Roslina Ghani', 'Benjamin Lee', 'Syed Amirul', 'Hanis Zulaikha', 'Ong Boon Hock'];
const LE_L = LE_NAMES.map((n, i) => ({ n, id: `X2026-${String(200 + i).padStart(4, '0')}`, owner: n === 'Amir Hakimi' ? 'amir' : `x${200 + i}`, fin: 'Completed', held: i === 27, modOk: i !== 13, why: 'Module 2 (Coaching conversations) not achieved: practice assessment 38, pass mark 50' }));
const roster = c => { const L = resOf(MC.state, c); return L ? L.map(l => ({ n: l.n, id: l.id, owner: l.n === 'Amir Hakimi' ? 'amir' : `x${l.id}`, fin: l.fin, held: isHeld(l), modOk: l.fin >= 50, why: `Final mark ${l.fin} is below the pass mark of 50` })) : c.id === 'LE-2026-10' ? LE_L : null; };
// App E8: verified results, all modules achieved, wording from the template. A SAMC whose QV does not cover the cohort is still issued, without the QV claim.
const lcheck = (l, c, m) => { const q = qvOk(m, c), v = c.results === 'Verified' && !l.held, ok = v && l.modOk;
  return { v, mod: l.modOk, q, ok, omit: ok && q === false, why: !v ? (l.held ? 'Result held: integrity case open' : 'Result is still Provisional') : !l.modOk ? l.why : '' }; };
const outcomeOf = (m, c) => { const q = qvOk(m, c); return m.cat === 'MCE' ? { issue: 'a certificate', acr: false, note: 'No ACR is prepared: enrichment carries no credit.' }
  : m.cat === 'UMC' ? { issue: 'a certificate and Digital Credential', acr: true, note: 'An ACR batch is prepared for you to confirm later.' }
  : q ? { issue: 'a Digital Credential', acr: true, note: 'QV is valid for the cohort dates. An ACR batch is prepared for you to confirm later.' }
  : { issue: 'a Digital Credential without the QV claim', acr: false, note: 'QV does not cover the cohort dates, so the credential carries no QV claim and no ACR batch is prepared.' }; };
const hasCred = (s, c, l) => s.credentials.some(x => x.cohort === c.id && x.who === l.n);
const nextNum = s => Math.max(499, ...s.credentials.map(x => +String(x.id).split('-').pop() || 0)) + 1;
const cid = n => `MCMS-C-2026-${String(n).padStart(6, '0')}`;
const ccode = n => { const a = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; let x = n * 7919 + 13, o = ''; for (let i = 0; i < 8; i++) { o += a[x % a.length]; x = Math.floor(x / a.length) + n * 3 + i; } return `${o.slice(0, 4)}-${o.slice(4)}`; };
const jobHtml = j => j.phase === 'done' ? `${bar(100)}<div style="margin-top:8px"><b>Done.</b> ${j.total} credentials issued.</div>` : `${bar(j.phase === 'queued' ? 0 : Math.round(j.n / j.total * 100))}<div style="margin-top:8px">${j.phase === 'queued' ? '<b>Queued.</b> Waiting for a worker.' : `<b>Issuing</b> ${j.n} of ${j.total} credentials.`}</div>`;
let ticker = null;
function tick() {
  if (ticker) return;
  ticker = setInterval(() => {
    const s = MC.state, j = s.ddJob; if (!j || j.phase === 'done') { clearInterval(ticker); ticker = null; return; }
    if (j.phase === 'queued') j.phase = 'issuing'; else { j.n = Math.min(j.total, j.n + Math.max(1, Math.ceil(j.total / 10))); if (j.n >= j.total) { clearInterval(ticker); ticker = null; finalize(s, j); return; } }
    const el = document.querySelector('[data-job]'); if (el) el.innerHTML = jobHtml(j); MC.save();
  }, 450);
}
function finalize(s, j) {
  const c = MC.co(j.cohort), m = MC.mc(c.mc), L = roster(c), o = outcomeOf(m, c), q = qvOk(m, c), oks = L.filter(l => lcheck(l, c, m).ok && !hasCred(s, c, l)); let n = nextNum(s);
  oks.forEach(l => { const num = n++; s.credentials.push({ id: cid(num), code: ccode(num), who: l.n, owner: l.owner, mc: m.id, cohort: c.id, issued: s.today, status: 'Issued', revision: 1, result: m.cat === 'MCE' ? 'Completed' : `Pass, ${l.fin}`,
    ...(q ? { qvClaim: `Quality-Verified, MQF level ${m.mqf || 4}, ${m.credits} credits` } : q === false ? { claimOmitted: true } : {}) }); });
  let batch = '';
  if (o.acr && oks.length) { batch = `ACR-${c.id}`; const b = s.acrBatches.find(x => x.id === batch);
    if (b) b.achieved += oks.length; else { s.acrBatches.push({ id: batch, mc: m.id, cohort: c.id, achieved: oks.length, consented: 0, status: 'ACR prepared' });
      s.tasks.push({ id: `T-ACR-${c.id}`, area: 'acr', what: 'Confirm ACR', ref: batch, from: 'System', check: `${oks.length} achieved, consent not yet asked`, waiting: 'Today', go: `#/dd/acr/${batch}` }); } }
  c.completion = 'Confirmed'; c.issued = (c.issued || 0) + oks.length; c.notIssued = L.length - c.issued; j.phase = 'done'; j.n = j.total; j.acr = batch;
  // the learner's registration now shows the outcome
  s.enrolments.forEach(e => { const l = e.cohort === c.id && L.find(x => x.owner === e.who); if (l) e.status = lcheck(l, c, m).ok ? 'Completed' : 'Not completed'; });
  closeTask(s, t => t.ref === c.id && t.what === 'Confirm completion', `Completion confirmed; ${oks.length} credentials issued`);
  tell(s, 'lec', `Credentials issued: ${MC.mcName(c.mc)} ${c.name}`, `${oks.length} issued, ${L.length - c.issued} not issued`, `#/lec/cohort/${c.id}`);
  const al = L.find(x => x.owner === 'amir');
  if (al && oks.includes(al)) tell(s, 'st', `Your ${m.cat === 'MCE' ? 'certificate' : 'credential'} is issued: ${MC.mcName(m.id)}`, 'Open it or share the verification link', '#/st/credentials');
  else if (al && !lcheck(al, c, m).ok) tell(s, 'st', `Completion confirmed: ${MC.mcName(m.id)}`, 'No credential was issued. See the registration for the reason.', '#/st/enrolments');
  log(s, `Confirmed completion of ${MC.mcName(c.mc)} ${c.name}; system issued ${oks.length} credentials, ${L.length - c.issued} not issued${batch ? '; ACR batch prepared' : ''}`, c.id); MC.save();
  MC.toast('Credentials issued', `${oks.length} credentials issued for ${c.name}.${batch ? ' An ACR batch is waiting for your confirmation.' : ''}`);
  if (location.hash.split('?')[0] === `#/dd/completion/${c.id}`) { MC._keepScroll = true; MC.render(); }
}
screen('dd/completion/:id', { title: 'Confirm completion', role: 'dd', nav: 'results', ex: 'LE-2026-10', render: (p, s) => {
  const c = MC.co(p.id); const bc = crumbs(['Results and completion', '#/dd/results'], [c ? `${MC.mcName(c.mc)} ${c.name}` : 'Not found']);
  if (!c) return head({ crumb: bc, title: 'Cohort not found' }) + note('No cohort has this identifier.', 'err');
  const m = MC.mc(c.mc), L = roster(c), j = s.ddJob && s.ddJob.cohort === c.id ? s.ddJob : null, running = j && j.phase !== 'done', conf = c.completion === 'Confirmed';
  const ttl = `Confirm completion: ${MC.mcName(c.mc)} ${c.name}`;
  if (c.results !== 'Verified') return head({ crumb: bc, title: ttl, status: st(c.results || 'Draft') }) + note(`Results are ${c.results || 'not submitted'}, not Verified. Completion can only be confirmed after you verify the results.`, 'warn')
    + `<div style="margin-top:16px">${c.results === 'Submitted' ? `<span class="btn" data-go="#/dd/verify/${c.id}">Verify results first</span>` : '<span class="btn dis">Confirm completion (results are not verified)</span>'}</div>`;
  if (!L) return head({ crumb: bc, title: ttl, status: stc('Confirmed') }) + note(`Completion was confirmed and the system issued ${c.achieved} credentials. Learner-level checks are kept in the audit trail.`, 'ok') + `<div style="margin-top:16px"><span class="btn ter" data-go="#/dd/creds">See credentials</span></div>`;
  const R = L.map(l => ({ l, k: lcheck(l, c, m), has: hasCred(s, c, l) })), good = R.filter(r => r.k.ok), bad = R.filter(r => !r.k.ok), todo = good.filter(r => !r.has), issuedN = R.filter(r => r.has).length, o = outcomeOf(m, c);
  const cell = v => v === null ? tag('Not applicable', 'n') : st(v ? 'Pass' : 'Fail'), hasBatch = s.acrBatches.some(b => b.id === `ACR-${c.id}`);
  return `${head({ crumb: bc, title: ttl, sub: `${c.start} to ${c.end} · results verified${c.verifiedOn ? ' ' + c.verifiedOn : ''} · you confirm, the system issues`, status: stc(conf ? 'Confirmed' : 'To confirm'),
      actions: running || !todo.length ? '' : `<span class="btn" data-modal="confirmcomp" data-id="${c.id}">Confirm completion for ${todo.length} learners</span>` })}
  ${running ? panel('Issuing credentials', `<div data-job>${jobHtml(j)}</div><div class="small muted" style="margin-top:8px">This runs in the background. You can leave this page; you are told when it finishes.</div>`) + '<div style="height:16px"></div>' : ''}
  ${conf ? note(`Completion confirmed. The system issued ${issuedN} credentials${bad.length ? ` and did not issue ${bad.length}` : ''}. ${o.acr ? (hasBatch ? 'An ACR batch is prepared for your confirmation.' : '') : o.note}`, 'ok')
      + `<div class="row" style="margin:12px 0;gap:8px"><span class="btn ter" data-go="#/dd/creds">See credentials</span>${hasBatch ? `<span class="btn ter" data-go="#/dd/acr/ACR-${c.id}">ACR batch</span>` : ''}</div>` : ''}
  ${kpis([['Learners', L.length, `${c.enrolled || L.length} enrolled`], ['Issued', issuedN, 'credentials already issued'], ['Will be issued', todo.length, 'all pre-issue checks pass'], ['Not issued', bad.length, 'a check failed, reason listed'], ['Credential', m.cat === 'MCE' ? 'Certificate' : m.cat === 'UMC' ? 'Certificate + DC' : 'Digital Credential', o.acr ? 'ACR batch follows' : 'no ACR']])}
  <div class="row" style="margin-top:16px"><div class="col grow">${table({ id: 'ddcomp', search: 'Search learners', title: 'Pre-issue checks per learner', cols: [{ h: 'Learner', r: r => `${r.l.n}<span class="s">${r.l.id}</span>` }, { h: 'Results verified', r: r => cell(r.k.v) }, { h: 'All modules achieved', r: r => cell(r.k.mod) },
      { h: 'Wording', r: () => cell(true) }, { h: 'QV valid', r: r => r.k.q === null ? tag('Not applicable', 'n') : r.k.q ? st('Pass') : tag('Claim omitted', 'warn') }, { h: 'Outcome', r: r => r.k.ok ? st(r.has ? 'Issued' : 'Will be issued') + (r.k.omit ? '<span class="s">Without the QV claim</span>' : '') : `${tag('Not issued', 'err')}<span class="s">${r.k.why}</span>` }], rows: R, size: 10 })}</div>
  <div class="col" style="width:380px">${panel('Not issued', bad.length ? checks(bad.map(r => ['err', r.l.n, r.k.why])) + '<div class="small muted" style="margin-top:8px">These learners are listed here and receive nothing. Fix the cause (for example by closing an integrity case) and confirm completion again for them.</div>' : 'Every learner passes the pre-issue checks.')}
  ${panel('What confirming does', `<div class="small">Confirming makes the system issue ${o.issue}. ${o.note} It tells ${todo.length} learners. It does not change any result.</div>`)}</div></div>`; },
  mount: (root, p, s) => { if (s.ddJob && s.ddJob.cohort === p.id && s.ddJob.phase !== 'done') tick(); } });
modal('confirmcomp', { kicker: c => c.id, title: c => { const k = MC.co(c.id), m = MC.mc(k.mc), n = roster(k).filter(l => lcheck(l, k, m).ok && !hasCred(MC.state, k, l)).length; return `Confirm completion for ${n} learners?`; }, confirm: 'Confirm completion', act: 'confirmcomp',
  done: ['Issuing started', 'Credentials are issued in the background. You are told when it is done.', 'info'],
  body: c => { const k = MC.co(c.id), m = MC.mc(k.mc), R = roster(k), n = R.filter(l => lcheck(l, k, m).ok && !hasCred(MC.state, k, l)).length, f = R.filter(l => !lcheck(l, k, m).ok).length, o = outcomeOf(m, k);
    return `<p>${MC.mcName(k.mc)} ${k.name}: the system issues ${o.issue.replace(/^a certificate and Digital Credential$/, 'a <b>certificate and Digital Credential</b>').replace(/^a certificate$/, 'a <b>certificate</b>').replace(/^a Digital Credential$/, 'a <b>Digital Credential</b>')} to <b>${n}</b> learners, and tells them.</p>
    ${f ? `<p>${f} learner${f > 1 ? 's do' : ' does'} not pass the pre-issue checks and ${f > 1 ? 'are' : 'is'} not issued; the reasons stay on this page.</p>` : ''}
    <p class="muted">${o.note} No ABC deposit or transfer happens now. Issuing is by the system; you cannot issue by hand.</p>`; } });
act('confirmcomp', (c, s) => {
  const k = MC.co(c.id), m = MC.mc(k.mc), n = roster(k).filter(l => lcheck(l, k, m).ok && !hasCred(s, k, l)).length;
  s.ddJob = { cohort: k.id, phase: 'queued', n: 0, total: n }; log(s, `Confirmed completion of ${MC.mcName(k.mc)} ${k.name}: issue job queued for ${n} learners`, k.id);
});

/* ======================= dd/creds ======================= */
const cred = (s, id) => s.credentials.find(x => x.id === id);
screen('dd/creds', { title: 'Credentials', role: 'dd', nav: 'creds', render: (p, s) => {
  const all = s.credentials.filter(x => (MC.mc(x.mc) || {}).ptj === 'FTMK'), ks = ['Issued', 'Corrected', 'Replaced', 'Revoked'], on = ks.includes(p.s) ? p.s : 'all', rows = all.filter(x => on === 'all' || x.status === on);
  return `${head({ title: 'Credentials', sub: `${all.length} FTMK credentials · nothing is deleted; a correction makes a new revision` })}
  ${linkTabs([['all', 'All', all.length], ...ks.map(k => [k, k, all.filter(x => x.status === k).length])], on, '#/dd/creds')}
  ${table({ id: 'ddcreds', search: 'Search by ID, holder or code', cols: [{ h: 'Credential ID', r: x => `<span style="font-variant-numeric:tabular-nums">${x.id}</span>` }, { h: 'Holder', r: x => x.who }, { h: 'Micro-credential', r: x => `${MC.mcNm(x.mc)}<span class="s">${x.cohort}</span>` },
    { h: 'Issued', k: 'issued' }, { h: 'Revision', num: true, r: x => x.revision }, { h: 'Status', r: x => st(x.status) + (x.request && !x.requestDone ? `<span class="s">Correction requested</span>` : '') }, { h: '', r: x => `<a data-go="#/dd/cred/${x.id}">Open</a>` }],
    rows, go: x => `#/dd/cred/${x.id}`, empty: 'No credentials with this status.' })}`; } });

/* ======================= dd/cred/:id ======================= */
const hist = x => x.history || [{ rev: 1, when: x.issued, by: 'System', what: 'Issued after completion was confirmed' }].concat(x.status === 'Revoked' ? [{ rev: x.revision, when: x.revoked, by: 'Hakim Rahman', what: `Revoked: ${x.reason}` }] : []);
const EXPL = { Issued: 'Issued by the system and valid.', Corrected: 'A confirmed error was fixed in a new revision. The original is kept.', Replaced: 'A new credential with a new ID replaced this one.', Revoked: 'Revoked on a recorded decision of the authorised body. The record is kept.' };
screen('dd/cred/:id', { title: 'Credential', role: 'dd', nav: 'creds', ex: 'MCMS-C-2026-000418', render: (p, s) => {
  const x = cred(s, p.id); const bc = crumbs(['Credentials', '#/dd/creds'], [x ? x.id : 'Not found']);
  if (!x) return head({ crumb: bc, title: 'Credential not found' }) + note('No credential has this ID.', 'err');
  const live = /Issued|Corrected/.test(x.status), rep = x.replacedBy, link = `mcms.utem.edu.my/verify/${x.code}`;
  return `${head({ crumb: bc, title: x.id, sub: `${x.who} · ${MC.mcName(x.mc)} ${x.cohort}`, status: st(x.status), actions: live ? `<span class="btn danger ter" data-modal="credrevoke" data-id="${x.id}">Revoke</span><span class="btn ter" data-modal="credreplace" data-id="${x.id}">Replace</span><span class="btn" data-modal="credcorrect" data-id="${x.id}">Correct</span>` : '' })}
  ${note(`${x.status}: ${EXPL[x.status]}`, x.status === 'Revoked' ? 'err' : x.status === 'Issued' ? 'ok' : 'warn')}
  ${x.request && !x.requestDone ? `<div style="margin-top:16px">${panel('Correction request from the learner', `<div>${esc(x.who)} asked on 23 Oct 2026: ${esc(x.request)}</div><div class="small muted" style="margin:4px 0 12px">Check the student record first. The system wording is regenerated from verified records, so only a confirmed error is corrected.</div><span class="btn ter sm" data-modal="credcorrect" data-id="${x.id}" data-name="${esc((x.request.match(/"(.+?)"/) || [])[1] || x.who)}">Start correction</span>`)}</div>` : ''}
  <div class="row" style="margin-top:16px"><div class="col grow">${panel('Credential', dl([['Holder name on the credential', esc(x.who)], ['Micro-credential', MC.mcNm(x.mc)], ['Cohort', x.cohort], ['Result', x.result], ['Issued', x.issued], ['Revision', x.revision], ...(x.qvClaim ? [['Recognition', x.qvClaim]] : x.claimOmitted ? [['Recognition', 'QV claim omitted: QV did not cover the cohort dates']] : []),
      ...(x.status === 'Revoked' ? [['Revoked', x.revoked || ''], ['Decision reference', esc(x.decisionRef) || 'Recorded in the audit trail'], ['Reason', esc(x.reason)]] : []), ...(rep ? [['Replaced by', `<a data-go="#/dd/cred/${rep}">${rep}</a>`]] : []), ...(x.replaces ? [['Replaces', `<a data-go="#/dd/cred/${x.replaces}">${x.replaces}</a>`]] : [])]))}
    ${table({ id: 'ddcredhist', title: 'Revision history', nopager: true, cols: [{ h: 'Rev', num: true, k: 'rev' }, { h: 'When', k: 'when' }, { h: 'By', k: 'by' }, { h: 'What changed', r: h => esc(h.what) }], rows: hist(x).slice().reverse() })}</div>
  <div class="col" style="width:380px">${panel('Public verification link', `<div style="font-variant-numeric:tabular-nums;word-break:break-all">${link}</div><div class="small muted" style="margin:4px 0 12px">Shows name, credential, issuer, date and status only. After a correction the same link shows "Corrected".</div>
      <div class="row" style="gap:8px"><span class="btn ter sm" data-go="#/public/verify/${x.code}">Open public page</span><span class="btn ghost sm" data-toast="Link copied|${link}">Copy link</span></div>
      <div class="row" style="gap:8px;margin-top:8px">${(MC.mc(x.mc) || {}).cat !== 'SAMC' ? `<span class="btn ghost sm" data-go="#/doc/certificate/${x.id}">View certificate</span>` : ''}<span class="btn ghost sm" data-go="#/doc/credential/${x.id}">View credential data</span></div>`)}
    ${panel('Rules', checks([['n', 'Correct', 'A confirmed error. New revision, same ID and link.'], ['n', 'Replace', 'New ID; the old one points to it.'], ['n', 'Revoke', 'Needs the decision of the authorised body. You record its reference.']]))}</div></div>`; } });
modal('credcorrect', { kicker: c => c.id, title: c => `Correct credential ${c.id}?`, confirm: 'Correct credential', reason: 'Reason (kept in the audit trail)', act: 'credcorrect',
  done: c => ['Credential corrected', `Revision ${cred(MC.state, c.id).revision} is live and the public link shows Corrected. The original is kept. The learner is told.`],
  body: (c, s) => { const x = cred(s, c.id); return `<p>This creates <b>revision ${x.revision + 1}</b> and the status becomes <b>Corrected</b>. The ID and public link stay the same; revision ${x.revision} is kept in the history. ${esc(x.who)} is told. Results and recognition wording are not changed.</p>
    <div class="col">${field('Name as it will read on the credential', inp('name', c.name || x.who, '', true), 'As in the student record, including bin, binti, a/l or a/p', true)}</div>`; } });
act('credcorrect', (c, s) => {
  const x = cred(s, c.id), old = x.who; x.history = hist(x).concat([{ rev: x.revision + 1, when: s.today, by: 'Hakim Rahman', what: `Corrected holder name from "${old}" to "${c.name}". Reason: ${c.reason}` }]);
  x.who = c.name; x.revision++; x.status = 'Corrected'; x.correctedOn = s.today; x.requestDone = true; closeTask(s, t => t.ref === x.id && t.what === 'Correct credential', `Corrected to revision ${x.revision}`);
  if (x.owner === 'amir') tell(s, 'st', `Your credential was corrected: ${MC.mcName(x.mc)}`, `Revision ${x.revision}`, `#/st/credential/${x.id}`); log(s, `Corrected credential ${x.id} to revision ${x.revision}: ${c.reason}`, x.id);
});
modal('credreplace', { kicker: c => c.id, title: c => `Replace credential ${c.id}?`, confirm: 'Replace credential', reason: 'Reason (kept in the audit trail)', act: 'credreplace',
  done: c => ['Credential replaced', `A new credential was issued. ${c.id} now points to it.`],
  body: (c, s) => { const x = cred(s, c.id); return `<p>The system issues a new credential with a <b>new ID</b> to ${esc(x.who)}. This one becomes <b>Replaced</b> and its public page points to the new ID. Both are kept. ${esc(x.who)} is told.</p>`; } });
act('credreplace', (c, s) => {
  const x = cred(s, c.id), n = nextNum(s), nid = cid(n);
  s.credentials.push({ id: nid, code: ccode(n), who: x.who, owner: x.owner, mc: x.mc, cohort: x.cohort, issued: s.today, status: 'Issued', revision: 1, result: x.result, replaces: x.id, ...(x.qvClaim ? { qvClaim: x.qvClaim } : {}), history: [{ rev: 1, when: s.today, by: 'System', what: `Issued to replace ${x.id}. Reason: ${c.reason}` }] });
  x.history = hist(x).concat([{ rev: x.revision, when: s.today, by: 'Hakim Rahman', what: `Replaced by ${nid}. Reason: ${c.reason}` }]); x.status = 'Replaced'; x.replacedBy = nid;
  if (x.owner === 'amir') tell(s, 'st', `Your credential was replaced: ${MC.mcName(x.mc)}`, `New ID ${nid}`, '#/st/credentials'); log(s, `Replaced credential ${x.id} with ${nid}: ${c.reason}`, x.id);
});
modal('credrevoke', { kicker: c => c.id, title: c => `Revoke credential ${c.id}?`, danger: true, confirm: 'Revoke credential', reason: 'Reason for revocation (shown to the learner)', act: 'credrevoke',
  done: c => ['Credential revoked', `${c.id} is Revoked. The public page shows it as revoked. The record is kept.`, 'warn'],
  body: (c, s) => { const x = cred(s, c.id); return `<p>You are revoking <b>${c.id}</b> held by ${esc(x.who)}. The public page will show <b>Revoked</b> with today's date and ${esc(x.who)} is told. The record is never deleted.</p>
    <p class="muted">You do not decide this. Revocation records a decision of the authorised body, so its reference is required.</p><div class="col">${field('Decision reference of the authorised body', inp('decisionRef', '', 'e.g. Senate 8/2026, item 4.2', true), 'Meeting or committee and reference number', true)}</div>`; } });
act('credrevoke', (c, s) => {
  const x = cred(s, c.id); x.history = hist(x).concat([{ rev: x.revision, when: s.today, by: 'Hakim Rahman', what: `Revoked on decision ${c.decisionRef}. Reason: ${c.reason}` }]);
  x.status = 'Revoked'; x.revoked = s.today; x.reason = c.reason; x.decisionRef = c.decisionRef;
  if (x.owner === 'amir') tell(s, 'st', `Your credential was revoked: ${MC.mcName(x.mc)}`, c.reason, '#/st/credentials'); log(s, `Revoked ${x.id} on decision ${c.decisionRef}: ${c.reason}`, x.id);
});

/* ======================= flows ======================= */
flow({ id: 'FL-00', title: 'Tour the deputy dean task queue', actor: 'Deputy dean', summary: 'How the queue, tabs, search and pagination behave',
  steps: [{ go: '#/dd/queue', say: 'Hakim lands on the task queue: everything waiting for a decision, oldest flagged as overdue.' },
    { go: '#/dd/queue?area=results', say: 'Tabs filter by area and the counts follow. Click a row to open its decision screen.' }] });
flow({ id: 'FL-01', title: 'Sign in with SSO and MFA, land on the task page', actor: 'Any staff', summary: 'UTeM Microsoft sign-in, second step, then the task queue',
  steps: [{ go: '#/signin', say: 'Staff sign in with their UTeM Microsoft account. There is no separate MCMS password. Click Continue.' },
    { go: '#/signin/mfa', say: 'Roles that decide on records need a second step. Enter any 6-digit code and verify.' },
    { go: '#/dd/queue', say: 'Hakim lands on what needs him today: the task queue, not an empty home page.' }] });
flow({ id: 'FL-04', title: 'Endorse a proposal, then record the Senate decision', actor: 'Deputy dean', summary: 'Endorse is blocked until the category is verified and the blocking comment is resolved',
  steps: [{ go: '#/dd/queue?area=proposals', say: 'Two proposal tasks: endorse Database Systems, and record Senate\'s decision on Applied Data Analytics.' },
    { go: '#/dd/proposal/DB', say: 'Endorse is blocked. Open Comments: the answered blocking comment shows the lecturer\'s response, so read it and click Mark resolved with a reason. The other blocking comment has no response yet and cannot be resolved.' },
    { go: '#/lec/proposal/DB?tab=comments', say: 'Dr. Aina responds to the open blocking comment. It becomes answered.' },
    { go: '#/dd/proposal/DB', say: 'Back with the deputy dean: resolve the answered comment, verify the category on the Review summary tab, then click Endorse and confirm. A task to record the Senate decision is created.' },
    { go: '#/dd/pack?id=DB', say: 'The decision pack for Senate 8/2026 now shows the endorsement. Print pack opens the A4 document.' },
    { go: '#/dd/senate/ADA', say: 'Senate met on 14 Oct. Enter the meeting date and the wording from the minutes, choose Approved, and confirm. The dialog says no QV or credit status is set.' },
    { go: '#/dd/proposals', say: 'Both proposals now show their new status. Approval is one decision; QV and credit are separate.' }] });
flow({ id: 'FL-05', title: 'Create an offering and confirm readiness', actor: 'Lecturer, deputy dean', summary: 'The cohort opens only after every Appendix B item is done and you confirm',
  steps: [{ go: '#/lec/offering/new', say: 'Dr. Aina creates a new cohort for the approved Data Structures version.' },
    { go: '#/lec/readiness/DS-2027-1', say: 'She uploads the one open item (facilitator qualification evidence) and sends the checklist to the deputy dean.' },
    { go: '#/dd/readiness/DS-2027-1', say: 'Hakim can only confirm once the checklist is sent. Click Accept evidence on the new item, choose Ready, and record the decision. Ready with conditions and Not ready are the other choices.' },
    { go: '#/public/catalogue', say: 'The cohort is now Open and appears in the public catalogue.' }] });
flow({ id: 'FL-08', title: 'Verify results, then confirm completion', actor: 'Deputy dean', summary: 'Two separate decisions: verifying gives module badges, confirming completion makes the system issue credentials',
  steps: [{ go: '#/dd/verify/DS-2026-2', say: 'Review the 28 provisional results, then click Verify 28 results. This creates module badges only; nothing is issued yet.' },
    { go: '#/dd/completion/DS-2026-2', say: 'A new task appeared for completion. Check the pre-issue checks (one learner is below the pass mark and is not issued), then Confirm completion and watch the job run.' },
    { go: '#/st/credentials', say: 'Learners find issued credentials in their wallet. This is Amir\'s own list, showing what a participant sees.' }] });
flow({ id: 'FL-10', title: 'Correct a credential name', actor: 'Deputy dean', summary: 'A correction makes a new revision; the original is kept and the public link shows Corrected',
  steps: [{ go: '#/dd/cred/MCMS-C-2026-000418', say: 'Lee Jia Hui asked for her name to be corrected. Click Start correction, check the name, give a reason and confirm.' },
    { go: '#/public/verify/7Q2M-K4PA', say: 'The same public link now shows the credential as Corrected, with the corrected name.' }] });
})();

