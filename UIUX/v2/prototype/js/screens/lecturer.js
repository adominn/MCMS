/* Lecturer (P1, also X1 reviewer) and industry expert (X2) screens.
   Pattern: render(p, s) reads live state; forms inside [data-ws="kind:id"] autosave into state through ROOT[kind] (see onEdit). */
(function () {
const { screen, modal, act, flow, head, panel, table, tag, field, inp, sel, area, radios, note, checks, dl, tabs, kpis, track } = MC;
const ME = 'Dr. Aina Zulkifli', TODAY = '26 Oct 2026';
const MONS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/* ---------- seed: new keys and records only ---------- */
const COMP = { DS: ['Quiz and tests', 'Assignment', 'Final project'], CND: ['Module 1 assessment', 'Module 2 assessment', 'Project'] };
const cndNames = MC.seed.cohorts.find(c => c.id === 'DS-2026-2').learners.map(l => l.n).slice(0, 24);
const cndIds = { 'Daniel Ong': 'X2026-0196', 'Wong Kar Seng': 'X2026-0201' };
const cndLearners = cndNames.map((n, i) => ({ n, id: cndIds[n] || `X2026-${String(180 + i).padStart(4, '0')}`, src: 'UCreds import 25 Oct', flag: '',
  m: [82 - (i % 7) * 3, 78 - (i % 5) * 2, 85 - (i % 6) * 3].map((v, k) => (n === 'Daniel Ong' && k === 2) || (n === 'Wong Kar Seng' && k === 1) ? null : v) }))
  .map(l => l.m.includes(null) ? Object.assign(l, { src: 'UCreds row rejected: mark empty' }) : l);
// The 12 item titles and groups are the shared list (same as the deputy dean's checklist); ev is the seeded evidence for DS-2027-1.
const RD = [['B2.1', 'B2 Content', 'Teaching materials loaded in UCreds', 'Lecturer', 'Dr. Aina Zulkifli, 20 Oct'], ['B2.2', 'B2 Content', 'Assessment instruments prepared and moderated', 'Lecturer', 'Dr. Aina Zulkifli, 20 Oct'],
  ['B3.1', 'B3 Facilitation', 'Facilitator assigned', 'Lecturer', 'Dr. Aina Zulkifli, 21 Oct'], ['B3.2', 'B3 Facilitation', 'Facilitator qualification evidence', 'Lecturer', 'Qualification certificate and CV'],
  ['B4.1', 'B4 Platform', 'UCreds course shell created', 'CODL', 'CODL, 21 Oct'], ['B4.2', 'B4 Platform', 'Enrolment export tested', 'CODL', 'CODL, 22 Oct'],
  ['B5.1', 'B5 Support', 'Learner support contact published', 'Lecturer', 'Dr. Aina Zulkifli, 22 Oct'], ['B5.2', 'B5 Support', 'Orientation session scheduled', 'Lecturer', '28 Dec 2026'],
  ['B6.1', 'B6 Fee and places', 'Fee and places set', 'Lecturer', 'RM 450.00, 40 places'], ['B6.2', 'B6 Fee and places', 'Payment service configured', 'CODL', 'Test mode'],
  ['B7.1', 'B7 Wording', 'Recognition wording checked against the template', 'Deputy dean', 'System check, 23 Oct'], ['B7.2', 'B7 Wording', 'Credential template previewed in BM and EN', 'Lecturer', 'Dr. Aina Zulkifli, 23 Oct']];
// seeded: DS-2027-1 has one open lecturer item; default: a new cohort where nothing is done yet
const rdSeed = () => ({ sent: false, items: RD.map(([k, g, t, owner, ev]) => ({ k, g, t, owner, st: k === 'B3.2' ? 'Not yet' : 'Yes', ev: k === 'B3.2' ? '' : ev })) });
const rdDefault = () => ({ sent: false, items: RD.map(([k, g, t, owner]) => ({ k, g, t, owner, st: 'Not yet', ev: owner === 'Lecturer' ? '' : owner === 'CODL' ? 'Waiting for CODL' : 'Waiting for the deputy dean' })) });
// parent courses are computed live from reference data (courses + programmes), plus one demo course in a programme that is not accredited
const parentsOf = s => (s.courses || MC.seed.courses).map(c => { const pg = (s.programmes || MC.seed.programmes).find(x => x.code === (c.prog || 'BITZ')); return { code: c.code, name: c.name.replace(' (elective)', ''), prog: pg ? pg.code : c.prog, acc: !!(pg && pg.accredited), slt: c.slt, credits: c.credits }; })
  .concat([{ code: 'BITL 2113', name: 'Legacy Systems Maintenance', prog: 'BITL', acc: false, slt: 120, credits: 3 }]);
const RV_ITEMS = ['The category and its reason are correct', 'Learning outcomes are measurable and linked to assessment', 'Learning time (SLT) adds up and meets the minimum', 'Assessment design and weightings are sound',
  'The alignment matrix has no gaps', 'The credential design makes no unsupported credit claim'];
MC.seed.reviews.push({ id: 'RV-027', mc: 'LE', reviewer: 'aina', due: '12 Oct 2026', status: 'Submitted', rec: 'Support', comments: 1, coi: 'No conflict, 8 Oct 2026',
  items: RV_ITEMS.map(() => ({ rate: 'Satisfactory', comment: '', blocking: false })), summary: 'Clear and ready.' });
const cqiBase = (cohort, ind, iss, act, fb) => ({ cohort, ind, fb, iss, act });
Object.assign(MC.seed, {
  lecUi: {}, lecDocs: {},
  lecRes: { 'CND-2026-2': { weightsOf: 'CND', learners: cndLearners } },
  lecRead: { 'DS-2027-1': rdSeed() },
  lecQv: {},
  lecCqi: {
    'CQI-DS-2026-1': cqiBase('DS-2026-1', [['Completion', '26 of 29 enrolled', 90, 85, '%'], ['Pass rate', '26 of 28 assessed', 93, 80, '%'], ['Credentials issued within 5 working days', '26 of 26', 100, 100, '%'], ['Outcome attainment, lowest (LO3 trees and graphs)', '19 of 26', 73, 75, '%']],
      [{ id: 'I1', text: 'LO3 attainment is 73%, below the 75% target', root: 'One lab session on graph traversal; learners said it was rushed', impact: '7 of 26 learners needed the resit on LO3', priority: 'High' }],
      [{ id: 'A1', issue: 'I1', text: 'Add a second graph traversal lab and a worked example video', owner: ME, due: '30 Nov 2026', ind: 'LO3 attainment at or above 75% in Cohort 2027/1', status: 'In progress', eff: '', outcome: false },
        { id: 'A2', issue: 'I1', text: 'Reword LO3 so shortest-path algorithms are named explicitly', owner: ME, due: '15 Dec 2026', ind: 'Updated LO3 approved in version 3', status: 'Open', eff: '', outcome: true },
        { id: 'A3', issue: 'I1', text: 'Update the facilitator guide with a graph traversal marking rubric', owner: 'Dr. Rosli Hamzah', due: '20 Oct 2026', ind: 'Rubric used for all 2026/2 marking', status: 'Done', eff: '', outcome: false }],
      'Feedback: 21 of 29 responded (72%). Satisfaction 4.2 out of 5. Common themes: clear weekly structure; graph topics too fast; wants more worked examples.'),
    'CQI-CND-2026-1': cqiBase('CND-2026-1', [['Completion', '19 of 22 enrolled', 86, 85, '%'], ['Pass rate', '19 of 21 assessed', 90, 80, '%'], ['Credentials issued within 5 working days', '19 of 19', 100, 100, '%'], ['Outcome attainment, lowest (LO2 pipelines)', '16 of 19', 84, 75, '%']],
      [{ id: 'I1', text: 'Three learners left before the project', root: 'Project started in week 9 with no early checkpoint', impact: 'Completion 86%, 1 point above target', priority: 'Medium' }],
      [{ id: 'A1', issue: 'I1', text: 'Add a project checkpoint in week 6', owner: ME, due: '15 Jan 2027', ind: 'Completion at or above 90%', status: 'Open', eff: '', outcome: false },
        { id: 'A2', issue: 'I1', text: 'Publish the project brief in week 1', owner: ME, due: '15 Jan 2027', ind: 'Brief visible in week 1', status: 'Open', eff: '', outcome: false },
        { id: 'A3', issue: 'I1', text: 'Renew QV before 6 Dec 2026', owner: ME, due: '6 Nov 2026', ind: 'Renewal package sent', status: 'In progress', eff: '', outcome: false }],
      'Feedback: 17 of 22 responded (77%). Satisfaction 4.4 out of 5.') },
});

/* ---------- small helpers ---------- */
const E = v => String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
const hm = () => new Date().toTimeString().slice(0, 5);
const NOW = () => `${TODAY}, ${hm()}`;
const aud = (s, what, rec, who = ME) => s.audit.unshift([NOW(), who, what, rec]);
const nfy = (s, role, t, sub, go) => s.notifs[role].unshift({ t, s: sub, when: '26 Oct', go, unread: true });
let tn = 0;
const addTask = (s, o) => s.tasks.push(Object.assign({ id: `LT${Date.now().toString(36)}${++tn}`, from: ME, waiting: 'Today' }, o));
const iso = v => { const [y, m, d] = String(v).split('-'); return `${+d} ${MONS[+m - 1]} ${y}`; };
const KIND = { Approved: 'ok', 'Pending approval': 'info', 'In review': 'info', Submitted: 'info', Draft: 'n', 'Not ready': 'warn', Ready: 'ok', Open: 'info', Full: 'n', Running: 'info', Ended: 'n', Verified: 'ok', Provisional: 'info',
  'Held (integrity)': 'warn', Missing: 'warn', Invited: 'info', Accepted: 'ok', Declined: 'err', Superseded: 'n', Confirmed: 'ok', Answered: 'info', Resolved: 'ok', Closed: 'ok', 'Not yet': 'warn', Available: 'ok', 'Not applicable': 'n',
  'In progress': 'info', High: 'err', Medium: 'warn', Low: 'n', Pass: 'ok', Fail: 'err', 'Quality-Verified': 'ok', Preparing: 'info', 'Not applied': 'n', Yes: 'ok', Satisfactory: 'ok', 'Needs amendment': 'warn', Support: 'ok', Amend: 'warn',
  'Do not support': 'err', Effective: 'ok', 'Partly effective': 'warn', 'Not effective': 'err', Done: 'warn', Superseded_: 'n' };
KIND['Meets target'] = 'ok';
const T = t => tag(t, KIND[t]);
const nf = msg => `${head({ title: 'Not found' })}${note(msg, 'warn')}<div><a data-go="#/lec/home">Back to my micro-credentials</a></div>`;
const mine = s => s.mcs.filter(m => m.owner === 'aina');
// one comment store, shared with the deputy dean's screen (status words: open, answered, resolved)
const cmsOf = (s, id) => { if (!s.ddComments) s.ddComments = JSON.parse(JSON.stringify(MC.seed.ddComments)); return s.ddComments.filter(c => c.mc === id); };
const cst = x => T(x.status[0].toUpperCase() + x.status.slice(1));
const pendLbl = p => p.status === 'Submitted' ? 'submitted for review' : p.status === 'In review' ? 'in review' : 'pending approval';
const cf = o => Object.entries(o).map(([k, v]) => `data-${k}="${E(v)}"`).join(' ');
const three = (what, not, who) => `<p><b>What happens:</b> ${what}</p><p><b>What does not happen:</b> ${not}</p><p><b>Who is told:</b> ${who}</p>`;
const mcLink = id => `<a data-go="#/lec/mc/${id}">${MC.mcName(id)}</a>`;
const btn = (label, go, cls = 'ter') => `<span class="btn ${cls}" data-go="${go}">${label}</span>`;
const dis = (label, why) => `<span class="btn dis">${label}</span><span class="small muted" style="max-width:300px">${why}</span>`;
const I = (label, name, v, o = {}) => `<div class="field"><label class="${o.req ? 'req' : ''}">${label}</label>${o.area ? `<textarea class="input" name="${name}">${E(v)}</textarea>`
  : o.opts ? `<select class="input" name="${name}">${o.opts.map(x => `<option ${x === v ? 'selected' : ''}>${x}</option>`).join('')}</select>`
  : `<input class="input" name="${name}" type="${o.type || 'text'}" value="${E(v)}" ${o.req ? 'required' : ''} ${o.attr || ''}>`}${o.help ? `<div class="help">${o.help}</div>` : ''}</div>`;
const choice = (name, label, on, sub = '') => `<label class="choice ${on ? 'on' : ''}"><input type="checkbox" name="${name}" ${on ? 'checked' : ''}><div>${label}${sub ? `<span class="small muted" style="display:block">${sub}</span>` : ''}</div></label>`;

/* Local confirm dialog: same anatomy as core's "confirm", but act/then/done come from the button's data-* through run/then/done,
   so it does not depend on how core.js builds the generic dialog. lcfr adds the required reason field. */
const LCF = { title: c => c.title, kicker: c => c.kicker, body: c => c.body, confirm: c => c.confirm, danger: c => 'danger' in c, run: (c, s) => { const f = MC.acts[c.act]; return f && f(c, s); },
  done: c => c.done ? c.done.split('|') : '', then: c => c.then };
modal('lcf', LCF); modal('lcfr', Object.assign({}, LCF, { reason: 'Your written response (kept on the record)' }));

/* ---------- autosave: any control with a name inside [data-ws="kind:id"] writes to ROOT[kind](id) ---------- */
const ROOT = {
  prop: (id, s) => { const m = MC.mc(id); return m && { d: doc(s, m), mc: m }; },
  rev: (id, s) => s.reviews.find(r => r.id === id),
  coh: (id, s) => resOf(s, MC.co(id)),
  cqi: (id, s) => s.lecCqi[id],
  rd: (id, s) => rdOf(s, MC.co(id)),
};
const AFTER = {};
const setPath = (o, path, v) => { const k = path.split('.'), last = k.pop(); k.forEach(x => { o = o[x]; }); o[last] = v; };
// Replace only when the markup really changed: a swap between mousedown and mouseup would swallow the user's click on the button.
const put = (el, h) => { const t = document.createElement('div'); t.innerHTML = h; if (t.innerHTML !== el.innerHTML) el.innerHTML = h; };
const onEdit = e => {
  const t = e.target, ws = t.closest && t.closest('[data-ws]'); if (!ws || !t.name || !MC.state) return;
  const [kind, id] = ws.dataset.ws.split(':'), root = ROOT[kind](id, MC.state); if (!root) return;
  if (t.type === 'radio' && !t.checked) return;
  const v = t.type === 'checkbox' ? t.checked : t.type === 'number' ? (t.value === '' ? null : +t.value) : t.value;
  try { setPath(root, t.name, v); } catch (x) { return; }
  MC.save(); if (AFTER[kind]) AFTER[kind](ws, id, t);
};
document.addEventListener('input', onEdit); document.addEventListener('change', onEdit);
document.addEventListener('click', e => { const t = e.target.closest('[data-tab]'), box = t && t.closest('[data-ui]'); if (box && MC.state) { MC.state.lecUi = MC.state.lecUi || {}; MC.state.lecUi[box.dataset.ui] = t.dataset.tab; MC.save(); } });

/* ---------- proposal document model (Appendix A) ---------- */
const BM = { ST: 'Pengujian Perisian', DB: 'Sistem Pangkalan Data', DS: 'Struktur Data', CND: 'Pembangunan Asli Awan' };
function doc(s, m) {
  s.lecDocs = s.lecDocs || {};
  if (s.lecDocs[m.id]) return s.lecDocs[m.id];
  const names = COMP[m.id] || (m.weights.length === 2 ? ['Practical task', 'Reflection'] : ['Quiz and tests', 'Assignment', 'Final project']);
  const filled = m.status !== 'Draft' || m.id === 'ST';
  const los = filled ? [['Explain the principles and levels of software testing', 0], ['Design test cases with black-box and white-box techniques', 1], ['Automate regression tests for a web application', 2], ['Evaluate test results and report defects', m.id === 'ST' ? '' : 2]]
    .map(([t, a]) => ({ t: m.id === 'ST' ? t : `${m.name}: outcome ${a + 1}`, a })).slice(0, m.id === 'ST' ? 4 : 3) : [];
  return (s.lecDocs[m.id] = {
    basics: { titleEN: m.name, titleBM: filled ? (BM[m.id] || m.name) : '', ptj: m.ptj, type: 'New', field: 'Computing and ICT', target: filled ? 'Working IT staff and final-year students' : '', mode: 'Online, facilitated', lang: 'English', period: m.cat === 'MCE' ? '2 days' : '14 weeks' },
    just: { need: filled ? 'Employers report that new graduates cannot design or automate tests. A short, assessed module closes the gap.' : '', evidence: filled ? 'Industry advisory panel minutes, 14 Aug 2026; graduate tracer study 2025.' : '', added: filled ? 'Hands-on automation project assessed against a rubric agreed with industry.' : '' },
    comps: names, pass: 50, integrity: 'Identity checked at UCreds sign-in. The final project is checked for similarity.', feedback: 'Written feedback within 10 working days of each component.',
    outcomes: los, model: 'Separate', cred: { digital: true, cert: true, qv: m.cat === 'SAMC' ? 'Will apply' : 'Not applicable', credit: m.credits ? `${m.credits} (proposed)` : 'None', mqf: m.cat === 'SAMC' ? '4 (proposed)' : (m.cat === 'UMC' ? '6 (proposed)' : 'None'), verify: 'Public verification page' },
    deliv: { schedule: filled ? 'Weekly facilitated session plus self-paced work' : '', platform: 'UCreds learning platform', facil: ME, r: ['Yes', 'Yes', 'Not yet'] },
    cod: m.id === 'ST' ? [{ n: 'Dr. Rosli Hamzah', ptj: 'FTMK' }] : [], decl: { ip: filled, note: filled ? 'Original material; reading list under UTeM library licence.' : '' }, saved: filled ? '10:42' : hm() });
}
const totSlt = m => m.modules.reduce((a, x) => a + (+x[1] || 0), 0), totW = m => m.weights.reduce((a, x) => a + (+x || 0), 0);
const linked = o => o.a !== '' && o.a != null;
function runChecks(m, d) {
  const L = [], tot = totSlt(m), lbl = m.cat === 'UMC' ? 'parent course SLT' : 'declared SLT', par = parentsOf(MC.state).find(x => x.code === m.code);
  if (!m.modules.length) L.push(['err', 'Add at least one module', 'Modules and SLT', 'mods']);
  else if (tot !== m.slt) L.push(['err', `Total module SLT (${tot} h) must equal ${lbl} (${m.slt} h)`, 'Submit is blocked until the hours add up', 'mods']);
  else L.push(['', `Total module SLT (${tot} h) equals ${lbl} (${m.slt} h)`, '', 'mods']);
  if (m.cat === 'UMC') {
    if (par && !par.acc) L.push(['err', `Parent programme ${par.prog} is not accredited`, 'A UMC needs an accredited parent programme', 'basics']); else L.push(['', 'Parent course is in an accredited programme', par ? `${m.code} in ${par.prog}` : '', 'basics']);
    const low = m.modules.filter(x => x[1] < 40);
    if (d.model === 'Credit-based' && low.length) L.push(['err', `${low.length} module(s) under 40 h in the credit-based model`, 'Each module needs 40 h or more', 'mods']);
  } else if (m.cat === 'SAMC' && tot < 40) L.push(['err', `Total learning time ${tot} h is under the 40 h minimum for a SAMC`, '', 'mods']);
  if (m.cat === 'MCE' && ((d.cred.credit && !/^None/i.test(d.cred.credit)) || (d.cred.mqf && !/^None/i.test(d.cred.mqf)))) L.push(['err', 'An MCE carries no credit or MQF level', 'Set both to None', 'cred']);
  const w = totW(m); L.push(w === 100 ? ['', 'Assessment weightings total 100%', '', 'assess'] : ['err', `Assessment weightings total ${w}%, they must total 100%`, '', 'assess']);
  const miss = [['Title (English)', d.basics.titleEN], ['Title (Bahasa Melayu)', d.basics.titleBM], ['Target group', d.basics.target], ['Need', d.just.need]].filter(x => !String(x[1]).trim()).map(x => x[0]);
  L.push(miss.length ? ['err', `Required fields empty: ${miss.join(', ')}`, '', 'basics'] : ['', 'All required fields are filled', '', 'basics']);
  if (!d.outcomes.length) L.push(['err', 'Add at least one learning outcome', '', 'outcomes']);
  d.outcomes.forEach((o, i) => { if (!linked(o)) L.push(['warn', `LO${i + 1} has no assessment linked`, 'Warning: a written response is needed to submit', 'align']); });
  d.comps.forEach((c, i) => { if (!d.outcomes.some(o => linked(o) && +o.a === i)) L.push(['warn', `Component "${c}" assesses no outcome`, 'Warning', 'align']); });
  L.push(d.decl.ip ? ['', 'Copyright and IP declaration confirmed', '', 'decl'] : ['err', 'Copyright and IP declaration is not confirmed', 'Required before submit', 'decl']);
  return { L, err: L.filter(x => x[0] === 'err').length, warn: L.filter(x => x[0] === 'warn').length };
}
const chkHtml = c => MC.checks(c.L.map(([k, t, sub, tab]) => [k, t, sub, k && tab ? `<a data-tab="${tab}">Fix</a>` : '']));
const locked = m => !/^(Draft|Returned)/.test(m.status);
function subHtml(m, d, c) {
  if (locked(m)) return `${T(m.status)}<span class="small muted">Content is locked</span>`;
  if (c.err) return dis('Submit for review', `Disabled: ${c.err} blocking error${c.err > 1 ? 's' : ''} to fix first`);
  return `<span class="btn" data-modal="${c.warn ? 'lcfr' : 'lcf'}" ${cf({ title: `Submit ${m.name} v${m.version} for review?`, kicker: m.name,
    body: three('The deputy dean (Hakim Rahman) assigns reviewers. The content is locked while it is reviewed. You can still answer reviewer comments.', 'It is not approved, not endorsed and not visible in the catalogue.', 'Hakim Rahman gets a task in his queue.'),
    confirm: 'Submit for review', act: 'submitprop', id: m.id, done: `Submitted|${m.name} is locked. Hakim Rahman will assign reviewers.`, then: `#/lec/proposals` })}>Submit for review</span>`;
}
const sltLine = m => { const t = totSlt(m), ok = t === m.slt; return `<b>Total module SLT: ${t} h</b> of ${m.slt} h required${ok ? '<div class="help">Matches ' + (m.cat === 'UMC' ? 'the parent course.' : 'the declared SLT.') + '</div>' : `<div class="help err">Total module SLT (${t} h) must equal ${m.cat === 'UMC' ? 'parent course' : 'declared'} SLT (${m.slt} h)</div>`}`; };
const wLine = m => { const w = totW(m); return `<b>Total weighting: ${w}%</b>${w === 100 ? '<div class="help">Weightings total 100%.</div>' : `<div class="help err">Weightings total ${w}%. They must total 100%.</div>`}`; };
const alignHtml = (m, d) => `<table><thead><tr><th>Outcome</th>${d.comps.map(c => `<th>${E(c)}</th>`).join('')}</tr></thead><tbody>${d.outcomes.map((o, i) => `<tr><td>LO${i + 1} ${E(o.t)}</td>${d.comps.map((c, k) => `<td>${linked(o) && +o.a === k ? 'Assessed' : '<span class="muted">—</span>'}</td>`).join('')}</tr>`).join('') || '<tr><td colspan="9"><div class="empty" style="margin:12px 0">No outcomes yet.</div></td></tr>'}</tbody></table>`;
AFTER.prop = (ws, id) => {
  const m = MC.mc(id), d = doc(MC.state, m), c = runChecks(m, d), q = x => ws.querySelector(x); d.saved = hm();
  put(q('#chk'), chkHtml(c)); q('#chk-r').textContent = `${c.err} blocking · ${c.warn} warning${c.warn === 1 ? '' : 's'}`; put(q('#sub'), subHtml(m, d, c)); q('#stamp').textContent = `Autosaved ${d.saved}`;
  if (q('#slt-line')) q('#slt-line').innerHTML = sltLine(m); if (q('#w-line')) q('#w-line').innerHTML = wLine(m); if (q('#align')) q('#align').innerHTML = alignHtml(m, d);
};

/* ---------- home ---------- */
const missingOf = (s, c) => { const r = resOf(s, c); return r ? r.learners.reduce((a, l) => a + l.m.filter(v => v == null).length, 0) : 0; };
const rdOpen = (s, c) => rdOf(s, c).items.filter(i => i.owner === 'Lecturer' && i.st === 'Not yet').length;
screen('lec/home', { title: 'My micro-credentials', role: 'lec', nav: 'home', render: (p, s) => {
  const need = [];
  mine(s).filter(m => m.status === 'Draft').forEach(m => need.push([`Finish the draft: ${m.name}`, `${runChecks(m, doc(s, m)).err} blocking error(s) to fix before you can submit`, `#/lec/proposal/${m.id}`, 'Open draft']));
  mine(s).forEach(m => { const o = cmsOf(s, m.id).filter(c => c.status === 'open' && c.blocking).length; if (o) need.push([`Answer ${o} blocking comment${o > 1 ? 's' : ''}: ${m.name}`, 'The deputy dean cannot endorse until it is answered', `#/lec/proposal/${m.id}?tab=comments`, 'Respond']); });
  s.cohorts.filter(c => c.owner === 'aina' && c.results === 'Draft').forEach(c => need.push([`Submit results: ${MC.mcName(c.mc)}, ${c.name}`, `${missingOf(s, c)} marks missing`, `#/lec/cohort/${c.id}`, 'Enter marks']));
  mine(s).filter(m => m.cat === 'SAMC' && m.qv && m.qv.status === 'Quality-Verified' && m.qv.daysLeft <= 90).forEach(m => need.push([`QV expires in ${m.qv.daysLeft} days: ${m.name}`, `Quality-Verified to ${m.qv.to}. Prepare the renewal evidence`, `#/lec/qv/${m.id}`, 'Open evidence']));
  s.cqi.filter(c => c.owner === 'aina' && c.status === 'Draft').forEach(c => need.push([`Quality review due ${c.due}: ${MC.mcName(c.mc)}`, c.id, `#/lec/cqi/${c.id}`, 'Open']));
  s.reviews.filter(r => r.reviewer === 'aina' && /Invited|Accepted/.test(r.status)).forEach(r => need.push([`Review invitation: ${MC.mcName(r.mc)}`, `Due ${r.due}. Declare any conflict of interest first`, `#/lec/review/${r.id}`, 'Open']));
  s.cohorts.filter(c => c.owner === 'aina' && c.status === 'Not ready' && rdOpen(s, c)).forEach(c => need.push([`Readiness item open: ${MC.mcName(c.mc)}, ${c.name}`, `${rdOpen(s, c)} item(s) for you; ${rdOf(s, c).items.filter(i => i.st !== 'Not yet').length} of 12 done`, `#/lec/readiness/${c.id}`, 'Open']));
  const ms = mine(s);
  return `${head({ title: 'My micro-credentials', sub: `${ME} · FTMK · ${TODAY}`, actions: btn('Create offering', '#/lec/offering/new') + btn('New proposal', '#/lec/new', '') })}
  ${kpis([['Approved', ms.filter(m => m.status === 'Approved').length], ['In review or pending', ms.filter(m => /review|Pending|Submitted/i.test(m.status)).length], ['Drafts', ms.filter(m => m.status === 'Draft').length], ['Open or upcoming offerings', s.cohorts.filter(c => c.owner === 'aina' && /Open|Not ready/.test(c.status)).length]])}
  ${panel('Needs you', need.length ? need.map(n => `<div class="li" style="padding:12px 20px" data-go="${n[2]}"><div class="t">${n[0]}<span class="s">${n[1]}</span></div><a data-go="${n[2]}">${n[3]}</a></div>`).join('') : '<div class="empty" style="margin:16px">Nothing needs you today.</div>', `${need.length} item${need.length === 1 ? '' : 's'}`, false)}
  ${table({ id: 'lec-home-mcs', title: 'My micro-credentials', search: 'Search my micro-credentials', cols: [
    { h: 'Micro-credential', r: m => MC.mcNm(m.id) }, { h: 'Status', r: m => T(m.status) }, { h: 'Version', r: m => `v${m.version}${m.pending ? `<span class="s">v${m.pending.version} ${pendLbl(m.pending)}</span>` : ''}` },
    { h: 'Learning time', num: true, r: m => `${m.slt} h` }, { h: 'Next step', r: m => m.status === 'Draft' ? 'Finish and submit' : m.status === 'Approved' ? 'Create an offering or request a change' : m.status === 'In review' ? 'Answer reviewer comments' : m.status === 'Submitted' ? 'Waiting for the deputy dean to assign reviewers' : m.status === 'Pending approval' ? `Endorsed ${m.endorsed}; waiting for the Senate decision` : 'Waiting for the decision' }],
    rows: s => mine(s), go: m => m.status === 'Draft' ? `#/lec/proposal/${m.id}` : `#/lec/mc/${m.id}` })}`; } });

/* ---------- micro-credential overview ---------- */
screen('lec/mc/:id', { title: 'Micro-credential', role: 'lec', nav: 'home', ex: 'DS', render: (p, s) => {
  const m = MC.mc(p.id); if (!m) return nf('That micro-credential does not exist.');
  const ok = m.status === 'Approved', on = p.tab || 'versions', rx = new RegExp(`(^|-)${m.id}(-|$)`);
  const vers = [].concat(m.pending ? [{ v: m.pending.version, st: m.pending.status || 'Submitted', d: `${m.pending.cls || 'Material'} change: ${m.pending.note || m.pending.summary}`, e: 'Not in force until approved. Enrolled learners stay on their version.' }] : [],
    [{ v: m.version, st: m.status, d: m.approvedBy || (m.submitted ? `Submitted ${m.submitted}` : 'Not submitted'), e: ok ? 'In force for new offerings' : 'Not offered yet' }],
    Array.from({ length: m.version - 1 }, (_, i) => ({ v: m.version - 1 - i, st: 'Superseded', d: 'Replaced when the next version was approved', e: 'Learners enrolled under it keep it' })));
  return `${head({ crumb: [['My micro-credentials', '#/lec/home'], [m.name]], title: MC.mcNm(m.id), sub: `${m.ptj} · version ${m.version} · ${m.slt} h learning time`, status: T(m.status),
    actions: (ok ? btn('Request a change', `#/lec/change/${m.id}`) + btn('Create offering', `#/lec/offering/new?mc=${m.id}`, '') : dis('Request a change', 'Only an approved version can be changed. Create offering is also off until approval.')) })}
  ${panel('', dl([['Category', `${MC.cat(m.cat)} ${MC.CATNAME[m.cat]}`], ['Parent course', m.code || 'None, own offering'], ['Credit', m.cat === 'SAMC' ? (m.qv && m.qv.status === 'Quality-Verified' ? `${m.credits} credits, MQF level ${m.mqf} (QV record, valid to ${m.qv.to})` : 'Credit after QV') : m.status === 'Approved' ? (m.credits ? `${m.credits} credits (approved)` : 'None') : (m.credits ? `${m.credits} proposed` : 'None')],
    ['Modules', m.modules.map(x => `${x[0]} (${x[1]} h)`).join('; ')], ['Weightings', m.weights.join(' / ') + ' %']]))}
  <div data-tabs>${tabs([['versions', 'Versions', vers.length], ['cohorts', 'Cohorts', s.cohorts.filter(c => c.mc === m.id).length]].concat(m.cat === 'SAMC' ? [['qv', 'QV']] : [], [['cqi', 'CQI', s.cqi.filter(c => c.mc === m.id).length], ['history', 'History']]), on)}
    <div data-pane="versions">${table({ id: 'lec-ver-' + m.id, cols: [{ h: 'Version', r: v => `v${v.v}` }, { h: 'Status', r: v => T(v.st) }, { h: 'Decision', k: 'd' }, { h: 'Effect on participants', k: 'e' }], rows: vers })}</div>
    <div data-pane="cohorts">${table({ id: 'lec-mcco-' + m.id, cols: [{ h: 'Cohort', k: 'name' }, { h: 'Dates', r: c => `${c.start} to ${c.end}` }, { h: 'Status', r: c => T(c.status) }, { h: 'Learners', num: true, r: c => c.enrolled || c.taken || 0 }],
      rows: s => s.cohorts.filter(c => c.mc === m.id), go: c => c.status === 'Not ready' ? `#/lec/readiness/${c.id}` : `#/lec/cohort/${c.id}`, empty: 'No cohorts yet. Create an offering from an approved version.' })}</div>
    ${m.cat === 'SAMC' ? `<div data-pane="qv">${panel('Quality verification', dl([['QV status', T((m.qv || {}).status || 'Not applied')], ['Valid', m.qv && m.qv.from ? `${m.qv.from} to ${m.qv.to}` : 'Not verified'], ['Claim', m.qv && m.qv.status === 'Quality-Verified' ? 'Quality-Verified claim allowed' : 'Credit after QV; no QV claim shown']]) + `<div style="margin-top:12px">${btn('Open QV evidence', `#/lec/qv/${m.id}`)}</div>`)}</div>` : ''}
    <div data-pane="cqi">${table({ id: 'lec-mccqi-' + m.id, cols: [{ h: 'Record', k: 'id' }, { h: 'Cohort', k: 'cohort' }, { h: 'Status', r: c => T(c.status) }], rows: s => s.cqi.filter(c => c.mc === m.id), go: c => `#/lec/cqi/${c.id}`, empty: 'No quality review yet for this micro-credential.' })}</div>
    <div data-pane="history">${MC.auditTable('lec-hist-' + m.id, a => rx.test(a[3]))}</div></div>`; } });

/* ---------- proposals list and wizard ---------- */
const PF = [['all', 'All'], ['Draft', 'Draft'], ['Returned', 'Returned'], ['Submitted', 'Submitted'], ['In review', 'In review'], ['Pending approval', 'Pending approval'], ['Approved', 'Approved']];
screen('lec/proposals', { title: 'Proposals and versions', role: 'lec', nav: 'proposals', render: (p, s) => {
  const on = p.f || 'all', ms = mine(s), cnt = k => k === 'all' ? ms.length : ms.filter(m => m.status.startsWith(k)).length;
  const rows = ms.filter(m => on === 'all' || m.status.startsWith(on));
  return `${head({ title: 'Proposals and versions', sub: 'Every version you own, from draft to approved', actions: btn('New proposal', '#/lec/new', '') })}
  ${tabs(PF.map(([k, l]) => [k, l, cnt(k)]), on).replace(/data-tab="([^"]+)"/g, (m0, k) => `data-go="#/lec/proposals?f=${encodeURIComponent(k)}"`)}
  ${table({ id: 'lec-proposals', search: 'Search proposals', cols: [{ h: 'Micro-credential', r: m => MC.mcNm(m.id) }, { h: 'Version', r: m => `v${m.version}${m.pending ? `<span class="s">v${m.pending.version} ${pendLbl(m.pending)}</span>` : ''}` },
    { h: 'Status', r: m => T(m.status) }, { h: 'Where it is', r: m => m.status === 'Draft' ? 'Draft: not submitted' : m.status === 'In review' ? `Reviews ${m.reviews || 'in progress'}${m.openComments ? ', ' + m.openComments + ' comment open' : ''}` : m.status === 'Approved' ? m.approvedBy : m.status === 'Pending approval' ? `Endorsed ${m.endorsed}; waiting for the Senate decision` : 'Waiting for the deputy dean to assign reviewers' },
    { h: '', r: m => `<a data-go="#/lec/proposal/${m.id}">${locked(m) ? 'View' : 'Edit'}</a>` }], rows, go: m => `#/lec/proposal/${m.id}`, empty: 'No proposals in this group.' })}`; } });

function wizResult(v, s) {
  const pr = parentsOf(s).find(x => x.code === v.parent);
  if (v.origin === 'course') return pr ? (pr.acc ? { cat: 'UMC', ok: true, why: `${pr.code} ${pr.name} is a UTeM credit course, so the only category is UMC (${MC.CATNAME.UMC}). Other categories are switched off. Module hours must add up to ${pr.slt} h.` }
    : { cat: 'UMC', ok: false, why: `${pr.code} sits in programme ${pr.prog}, which is not accredited. A UMC cannot be submitted from it.` }) : { cat: '', ok: false, why: 'Pick the parent course from reference data.' };
  if (v.origin === 'own') { const h = +v.slt; if (!h) return { cat: '', ok: false, why: 'Enter the total learning time in hours.' };
    return h < 40 ? { cat: 'MCE', ok: true, why: `An own offering of ${h} h is under the 40 h minimum for a SAMC, so it can only be an MCE (${MC.CATNAME.MCE}). It is non-credit${v.credit === 'yes' ? '; credit and MQF level cannot be sought at this size' : ''}.` }
      : { cat: 'SAMC', ok: true, why: `An own offering of ${h} h meets the 40 h minimum, so it is a SAMC.${v.credit === 'yes' ? ' Credit and MQF level are proposed only; they appear after QV is recorded.' : ' No credit or MQF level is sought.'}` }; }
  return { cat: '', ok: false, why: 'Answer the questions to see the category.' };
}
const wizOut = (r) => `<div class="col" style="gap:8px">${r.cat ? `<div>${MC.cat(r.cat)} <b style="margin-left:6px">Suggested category: ${r.cat}</b> <span class="small muted">proposed until the deputy dean verifies it</span></div>` : ''}<div>${r.why}</div></div>`;
screen('lec/new', { title: 'New proposal', role: 'lec', nav: 'proposals', render: (p, s) => `${head({ crumb: [['Proposals and versions', '#/lec/proposals'], ['New proposal']], title: 'New proposal', sub: 'Answer three questions. The system suggests a category and says why.' })}
  <div class="row" data-form id="wiz"><div class="col grow" style="max-width:640px">
    ${panel('1. Where does it come from?', radios('origin', [['course', 'Modules from a UTeM credit course', 'Unbundling: the category is UMC'], ['own', 'Our own offering', 'Not part of a credit course: SAMC or MCE by learning time']], ''))}
    <div id="wz-course" style="display:none">${panel('2. Parent course (from reference data)', field('Parent course', `<select class="input" name="parent"><option value="">Choose a course</option>${parentsOf(s).map(c => `<option value="${c.code}">${c.code} ${c.name} · ${c.slt} h · ${c.prog}${c.acc ? '' : ' (programme not accredited)'}</option>`).join('')}</select>`, 'Parent programme, course and SLT come from reference data and cannot be typed.'))}</div>
    <div id="wz-own" style="display:none">${panel('2. Size and credit', field('Total learning time (hours)', MC.inp('slt', '', 'For example 30', false, 'number'), 'A SAMC needs at least 40 h.') + field('Do you want to claim credit or an MQF level?', radios('credit', [['no', 'No'], ['yes', 'Yes, plan a QV']], 'no')))}</div>
    ${panel('3. Name', field('Working title', MC.inp('title', '', 'For example Software Testing', true), 'You can add the Bahasa Melayu title in the workspace.', true))}</div>
  <div style="width:380px">${panel('Suggested category', `<div id="wz-res">${wizOut({ cat: '', why: 'Answer the questions to see the category.' })}</div><div id="wz-btn" style="margin-top:16px;display:flex;gap:8px;flex-direction:column">${dis('Create draft', 'Disabled: answer the questions first')}</div>`)}</div></div>`,
  mount: (root, p, s) => { const w = root.querySelector('#wiz'); const upd = () => { const v = {}; w.querySelectorAll('input,select').forEach(i => { if (i.type === 'radio' && !i.checked) return; v[i.name] = i.value; });
    w.querySelector('#wz-course').style.display = v.origin === 'course' ? '' : 'none'; w.querySelector('#wz-own').style.display = v.origin === 'own' ? '' : 'none';
    const r = wizResult(v, MC.state); put(w.querySelector('#wz-res'), wizOut(r));
    put(w.querySelector('#wz-btn'), r.ok ? `<span class="btn" data-act="newprop" data-validate>Create draft and open the workspace</span>` : dis('Create draft', r.cat ? 'Disabled: ' + r.why : 'Disabled: answer the questions first')); };
    w.addEventListener('input', upd); w.addEventListener('change', upd); } });
act('newprop', (c, s) => {
  const r = wizResult(c, s); if (!r.ok) { MC.toast('Cannot create the draft', r.why, 'err'); return false; }
  const n = s.mcs.filter(m => /^NP\d+$/.test(m.id)).length + 1, id = 'NP' + n, pr = parentsOf(s).find(x => x.code === c.parent);
  s.mcs.push({ id, cat: r.cat, code: r.cat === 'UMC' ? pr.code : '', name: c.title, ptj: 'FTMK', owner: 'aina', credits: r.cat === 'UMC' ? pr.credits : 0, slt: r.cat === 'UMC' ? pr.slt : +c.slt, version: 1, status: 'Draft', modules: [], weights: r.cat === 'MCE' ? [50, 50] : [40, 30, 30], ...(r.cat === 'SAMC' ? { qv: { status: 'Not applied' } } : {}) });
  aud(s, `Created draft ${c.title} (${r.cat})`, id); MC.save(); MC.go(`#/lec/proposal/${id}`); MC.toast('Draft created', `${c.title} is a ${r.cat}. Autosave is on.`); return false; });

/* ---------- Appendix A workspace ---------- */
const SECS = [['basics', 'Basics'], ['just', 'Justification'], ['outcomes', 'Outcomes'], ['mods', 'Modules and SLT'], ['assess', 'Assessment'], ['align', 'Alignment'], ['cred', 'Credential design'], ['deliv', 'Delivery'], ['cod', 'Co-developers'], ['decl', 'Declaration']];
screen('lec/proposal/:id', { title: 'Proposal workspace', role: 'lec', nav: 'proposals', ex: 'ST', render: (p, s) => {
  const m = MC.mc(p.id); if (!m) return nf('That proposal does not exist.');
  const d = doc(s, m), c = runChecks(m, d), lk = locked(m), cms = cmsOf(s, m.id), open = cms.filter(x => x.status === 'open').length;
  const on = p.tab || (s.lecUi || {})['prop:' + m.id] || 'basics';
  const pane = (k, inner) => `<div data-pane="${k}"><fieldset class="col" style="border:0;min-width:0" ${lk ? 'disabled' : ''}>${inner}</fieldset></div>`;
  const sub = (a, b) => `<h2>${a}</h2>${b ? `<div class="small muted">${b}</div>` : ''}`;
  const B = d.basics;
  return `<style>.vtabs{flex-direction:column;border-bottom:0;width:190px;flex:none}.vtabs .tab{padding:9px 12px}.vtabs .tab.on{box-shadow:inset 3px 0 0 var(--primary);background:var(--s1)}</style>
  <div class="col" style="gap:20px" data-tabs data-ws="prop:${m.id}" data-ui="prop:${m.id}">
  ${head({ crumb: [['Proposals and versions', '#/lec/proposals'], [m.name]], title: MC.mcNm(m.id), status: T(m.status), sub: `Appendix A · version ${m.version} · <span id="stamp">${lk ? 'Locked' : 'Autosaved ' + d.saved}</span>`,
    actions: `<span class="btn ter" data-go="#/doc/proposal/${m.id}">Print preview</span>${lk ? '' : '<span class="btn ter" data-toast="Draft saved|Autosave already keeps every change.">Save draft</span>'}<span id="sub" style="display:flex;gap:8px;align-items:center">${subHtml(m, d, c)}</span>` })}
  ${lk ? note(m.status === 'In review' ? 'This version is in review. Content is locked. You can answer reviewer comments on the Comments tab.' : m.status === 'Approved' ? `This version is approved. <a data-go="#/lec/change/${m.id}">Request a change</a> to edit it.` : 'This version is submitted. Content is locked until it is returned.', '') : ''}
  <div class="row"><div class="tabs vtabs">${SECS.concat([['comments', 'Comments']]).map(([k, l]) => `<div class="tab ${k === on ? 'on' : ''}" data-tab="${k}">${l}${k === 'comments' && cms.length ? `<span class="c">${open ? open + ' open' : cms.length}</span>` : ''}</div>`).join('')}</div>
  <div class="grow">
   ${pane('basics', sub('Basics') + I('Title (English)', 'd.basics.titleEN', B.titleEN, { req: 1 }) + I('Title (Bahasa Melayu)', 'd.basics.titleBM', B.titleBM, { req: 1 }) +
     `<div class="row">${field('PTj', B.ptj, 'Your PTj from your role scope')}${I('Proposal type', 'd.basics.type', B.type, { opts: ['New', 'Revision', 'Re-offer'] })}</div>` +
     field('Category', `${MC.cat(m.cat)} ${MC.CATNAME[m.cat]} <span class="small muted">proposed; the deputy dean verifies it at endorsement</span>`) + (m.cat === 'UMC' ? field('Parent course', `${m.code} ${m.name} · ${m.slt} h · ${m.credits} credits`, `From reference data.${(pp => pp ? ` Programme ${pp.prog}${pp.acc ? ', accredited' : ', not accredited'}.` : '')(parentsOf(s).find(x => x.code === m.code))}`) : '') +
     `<div class="row">${I('Field of study', 'd.basics.field', B.field, { opts: ['Computing and ICT', 'Engineering', 'Management'] })}${I('Mode', 'd.basics.mode', B.mode, { opts: ['Online, facilitated', 'Blended', 'On campus'] })}${I('Language', 'd.basics.lang', B.lang, { opts: ['English', 'Bahasa Melayu', 'Both'] })}</div>` +
     I('Target group', 'd.basics.target', B.target, { req: 1 }) + I('Period', 'd.basics.period', B.period))}
   ${pane('just', sub('Justification') + I('Need', 'd.just.need', d.just.need, { area: 1, req: 1 }) + I('Evidence', 'd.just.evidence', d.just.evidence, { area: 1, help: 'Attach minutes, surveys or tracer studies.' }) + I('Added value', 'd.just.added', d.just.added, { area: 1 }) +
     (lk ? '' : '<div><span class="btn ter sm" data-toast="File attached|evidence-industry-panel.pdf, 240 KB">Attach a file</span></div>'))}
   ${pane('outcomes', sub('Learning outcomes', 'Each outcome links to the assessment that proves it.') + d.outcomes.map((o, i) => `<div class="row" style="align-items:flex-end;gap:12px"><div class="grow">${I(`LO${i + 1}`, `d.outcomes.${i}.t`, o.t)}</div>
       <div style="width:240px"><div class="field"><label>Assessed by</label><select class="input" name="d.outcomes.${i}.a"><option value="">Not linked</option>${d.comps.map((cn, k) => `<option value="${k}" ${linked(o) && +o.a === k ? 'selected' : ''}>${E(cn)}</option>`).join('')}</select></div></div>
       ${lk ? '' : `<a data-act="rmlo" data-id="${m.id}" data-i="${i}" style="padding-bottom:10px">Remove</a>`}</div>${linked(o) ? '' : '<div class="help err" style="margin-top:-8px">LO' + (i + 1) + ' has no assessment linked. This is a warning; you must explain it to submit.</div>'}`).join('') +
     (lk ? '' : `<div><span class="btn ter sm" data-act="addlo" data-id="${m.id}">Add an outcome</span></div>`))}
   ${pane('mods', sub('Modules and learning time (SLT)', m.cat === 'UMC' ? `Parent course ${m.code}: ${m.slt} h (${m.credits} credits), from reference data.` : `Declared learning time: ${m.slt} h.`) +
     (m.cat === 'UMC' ? I('Unbundling model', 'd.model', d.model, { opts: ['Credit-based', 'Separate'], help: 'Credit-based needs each module at 40 h or more.' }) : '') +
     m.modules.map((x, i) => `<div class="row" style="align-items:flex-end;gap:12px"><div class="grow">${I(`Module ${i + 1}`, `mc.modules.${i}.0`, x[0])}</div><div style="width:120px">${I('Hours', `mc.modules.${i}.1`, x[1], { type: 'number', attr: 'min="0"' })}</div>${lk ? '' : `<a data-act="rmmod" data-id="${m.id}" data-i="${i}" style="padding-bottom:10px">Remove</a>`}</div>`).join('') +
     `<div id="slt-line">${sltLine(m)}</div>` + (lk ? '' : `<div class="row" style="gap:8px"><span class="btn ter sm" data-act="addmod" data-id="${m.id}">Add a module</span>${m.id === 'ST' && m.modules[2] && m.modules[2][1] !== 42 ? `<span class="btn ter sm" data-act="fixslt" data-id="ST" data-done="Module 3 set to 42 h|Total is now 120 h and matches the parent course.">Set module 3 to 42 h</span>` : ''}</div>`))}
   ${pane('assess', sub('Assessment') + `<div class="row"><div style="width:160px">${I('Pass mark (%)', 'd.pass', d.pass, { type: 'number' })}</div></div>` +
     d.comps.map((cn, i) => `<div class="row" style="align-items:flex-end;gap:12px"><div class="grow">${I(`Component ${i + 1}`, `d.comps.${i}`, cn)}</div><div style="width:120px">${I('Weight (%)', `mc.weights.${i}`, m.weights[i], { type: 'number', attr: 'min="0" max="100"' })}</div></div>`).join('') +
     `<div id="w-line">${wLine(m)}</div>` + I('Academic integrity', 'd.integrity', d.integrity, { area: 1 }) + I('Feedback to learners', 'd.feedback', d.feedback, { area: 1 }) + (lk ? '' : `<div><span class="btn ter sm" data-act="addcomp" data-id="${m.id}">Add a component</span></div>`))}
   ${pane('align', sub('Alignment matrix', 'Built from the links on the Outcomes tab. A gap shows as a warning.') + `<div class="panel" id="align">${alignHtml(m, d)}</div>`)}
   ${pane('cred', sub('Credential design') + `<div class="col" style="gap:8px">${choice('d.cred.digital', 'Digital credential', d.cred.digital)}${choice('d.cred.cert', 'Certificate of completion', d.cred.cert)}</div>` +
     (m.cat === 'SAMC' ? I('QV status', 'd.cred.qv', ['Not applied', 'Will apply'].includes(d.cred.qv) ? d.cred.qv : 'Will apply', { opts: ['Not applied', 'Will apply'], help: 'Quality-Verified is recorded by the deputy dean after the QV decision, not here.' }) : field('QV status', 'Not applicable', 'Quality verification applies only to a stand-alone micro-credential.')) +
     (m.cat === 'MCE' ? `<div class="row">${field('Credit', 'None', 'An MCE carries no credit.')}${field('MQF level', 'None', 'An MCE carries no MQF level.')}</div>`
       : m.cat === 'UMC' ? `<div class="row">${field('Credit', `${m.credits} credits`, 'From the parent course.')}${I('MQF level', 'd.cred.mqf', d.cred.mqf)}</div>`
       : `<div class="row">${I('Credit', 'd.cred.credit', d.cred.credit)}${I('MQF level', 'd.cred.mqf', d.cred.mqf)}</div>`) +
     note('Credit and MQF level stay <b>proposed</b>. They are not shown in the catalogue or on credentials until approval (and QV for a SAMC) confirms them.') + I('How it is verified', 'd.cred.verify', d.cred.verify, { opts: ['Public verification page', 'Verification page and QR code'] }))}
   ${pane('deliv', sub('Delivery and readiness (Appendix A8)') + I('Schedule', 'd.deliv.schedule', d.deliv.schedule) + I('Platform', 'd.deliv.platform', d.deliv.platform, { opts: ['UCreds learning platform', 'Other (explain in schedule)'] }) + I('Facilitators', 'd.deliv.facil', d.deliv.facil) +
     ['Learning platform shell', 'Facilitators available', 'Assessment instruments ready'].map((t, i) => I(t, `d.deliv.r.${i}`, d.deliv.r[i], { opts: ['Yes', 'Not yet', 'Not applicable'] })).join(''))}
   ${pane('cod', sub('Co-developers', 'Only staff from the same PTj (FTMK) can be added. Their edits are attributed to them.') + `<div class="panel">${d.cod.map(x => `<div class="li" style="padding:10px 20px"><div class="t">${x.n}<span class="s">${x.ptj}</span></div></div>`).join('') || '<div class="empty" style="margin:12px">No co-developers yet.</div>'}</div>` +
     (lk ? '' : `<div data-form class="row" style="align-items:flex-end;gap:12px"><div class="grow"><div class="field"><label>Add a co-developer</label><select class="input" name="cod"><option value="Dr. Rosli Hamzah|FTMK">Dr. Rosli Hamzah (FTMK)</option><option value="Dr. Zainal Abidin|FTMK">Dr. Zainal Abidin (FTMK)</option><option disabled>Ir. Dr. Zul Hilmi Che Ani (FKE: different PTj, not allowed)</option></select></div></div><span class="btn ter" data-act="addcod" data-id="${m.id}">Add</span></div>`))}
   ${pane('decl', sub('Declaration') + `<label class="choice ${d.decl.ip ? 'on' : ''}"><input type="checkbox" name="d.decl.ip" ${d.decl.ip ? 'checked' : ''}><div>I confirm the content is original or licensed, and UTeM may use it for this micro-credential.<span class="small muted" style="display:block">Required before submit</span></div></label>` +
     I('Licence note', 'd.decl.note', d.decl.note, { area: 1 }))}
   <div data-pane="comments">${cms.length ? cms.map(x => `<div class="panel"><div class="bd col" style="gap:8px"><div class="row" style="align-items:center;gap:8px"><b>${x.by}</b><span class="small muted">${x.role || ''}</span>${cst(x)}${x.blocking ? '<span class="tag err">Blocking</span>' : '<span class="tag n">Non-blocking</span>'}</div>
       <div>${E(x.text)}</div>${x.response ? `<div class="small muted">Response: ${E(x.response)}</div>` : ''}
       ${x.status === 'open' ? `<div class="row" style="gap:8px"><span class="btn ter sm" data-modal="respond" data-id="${m.id}" data-cid="${x.id}">Respond</span></div>` : ''}
       ${x.blocking && x.status !== 'resolved' ? `<div class="help err">${x.status === 'open' ? 'A blocking comment that is not resolved stops endorsement and approval.' : 'Answered. The deputy dean decides whether it is resolved.'}</div>` : ''}</div></div>`).join('') : `<div class="empty">No reviewer comments yet. Comments appear here once reviewers are assigned.</div>`}</div>
  </div>
  <div style="width:320px;flex:none">${panel('Checks before you submit', `<div id="chk">${chkHtml(c)}</div>`, `<span id="chk-r">${c.err} blocking · ${c.warn} warning${c.warn === 1 ? '' : 's'}</span>`)}</div></div></div>`;
}, mount: (root, p) => { if (p.tab) { const t = root.querySelector(`[data-tab="${p.tab}"]`); if (t) { MC.state.lecUi = MC.state.lecUi || {}; MC.state.lecUi['prop:' + p.id] = p.tab; } } } });
modal('respond', { title: 'Respond to the reviewer comment', kicker: 'Reviewer comment', reason: 'Your response (the reviewer and deputy dean will read it)', confirm: 'Send response', act: 'respcomment', done: 'Response sent|The comment is now Answered. The deputy dean decides whether it is resolved.' });
const cmt = c => cmsOf(MC.state, c.id).find(x => x.id === c.cid);
const syncOpen = id => { const m = MC.mc(id); m.openComments = cmsOf(MC.state, id).filter(x => x.status !== 'resolved').length; };
act('respcomment', c => { const x = cmt(c); x.response = `${ME}: ${c.reason}`; x.status = 'answered'; syncOpen(c.id); aud(MC.state, `Answered reviewer comment ${x.id}`, c.id); nfy(MC.state, 'dd', `Comment answered: ${MC.mcName(c.id)}`, `${ME} responded to ${x.by}`, `#/dd/proposal/${c.id}`); });
act('addmod', c => { MC.mc(c.id).modules.push([`Module ${MC.mc(c.id).modules.length + 1}`, 40]); });
act('rmmod', c => { MC.mc(c.id).modules.splice(+c.i, 1); });
act('addlo', c => { const d = doc(MC.state, MC.mc(c.id)); d.outcomes.push({ t: 'New learning outcome', a: '' }); });
act('rmlo', c => { doc(MC.state, MC.mc(c.id)).outcomes.splice(+c.i, 1); });
act('addcomp', c => { const m = MC.mc(c.id); doc(MC.state, m).comps.push(`Component ${m.weights.length + 1}`); m.weights.push(0); });
act('addcod', c => { const [n, ptj] = String(c.cod).split('|'), d = doc(MC.state, MC.mc(c.id)); if (!d.cod.some(x => x.n === n)) { d.cod.push({ n, ptj }); MC.toast('Co-developer added', `${n} can now edit. Their edits are attributed to them.`); } });
act('fixslt', c => { MC.mc(c.id).modules[2][1] = 42; doc(MC.state, MC.mc(c.id)).saved = hm(); aud(MC.state, 'Autosaved: module 3 set to 42 h', c.id); });
act('submitprop', (c, s) => {
  const m = MC.mc(c.id), d = doc(s, m), ck = runChecks(m, d); if (ck.err) { MC.toast('Cannot submit', `${ck.err} blocking error(s) remain.`, 'err'); return false; }
  m.status = 'Submitted'; m.submitted = TODAY; d.warnResp = c.reason || '';
  addTask(s, { area: 'proposals', what: 'Assign reviewers', ref: m.id, check: `${ck.L.filter(x => !x[0]).length} checks pass, ${ck.warn} warning${ck.warn === 1 ? '' : 's'} answered in writing`, go: `#/dd/proposal/${m.id}` });
  nfy(s, 'dd', `Proposal submitted: ${m.name}`, `${ME}, version ${m.version}. Assign reviewers.`, `#/dd/proposal/${m.id}`); aud(s, `Submitted proposal ${m.name} v${m.version}`, m.id); });

/* ---------- change to an approved version ---------- */
const CH = [['pass', 'Pass standard (pass mark or rule)', 1], ['lo', 'Learning outcomes', 1], ['slt', 'Learning time (SLT)', 1], ['credit', 'Credit or MQF level sought', 1], ['sched', 'Delivery schedule or platform', 0], ['ref', 'Reading list and resources', 0], ['fac', 'Facilitator team', 0], ['typo', 'Corrections to wording', 0]];
const chgClass = c => { const picked = CH.filter(x => c['c_' + x[0]]); return !picked.length ? '' : picked.some(x => x[2]) ? 'Material' : 'Minor'; };
const chgNote = cls => cls === 'Material' ? 'Material change: a new version is created and goes through review and approval again. It is not in force until approved.' : cls === 'Minor' ? 'Minor change: recorded with your impact note and logged to the quality review. The deputy dean acknowledges it; no new approval.' : 'Choose what you want to change. The system classifies it for you.';
screen('lec/change/:id', { title: 'Request a change', role: 'lec', nav: 'home', ex: 'DS', render: (p, s) => {
  const m = MC.mc(p.id); if (!m) return nf('That micro-credential does not exist.');
  if (m.status !== 'Approved') return `${head({ title: 'Request a change' })}${note(`Only an approved version can be changed. ${m.name} is ${m.status}.`, 'warn')}`;
  const enrolled = s.cohorts.filter(c => c.mc === m.id && /Open|Running|Full/.test(c.status)).reduce((a, c) => a + (c.enrolled || c.taken || 0), 0);
  if (m.pending) return `${head({ crumb: [['My micro-credentials', '#/lec/home'], [m.name, `#/lec/mc/${m.id}`], ['Change']], title: 'Change requested' })}${note(`Version ${m.pending.version} (${(m.pending.cls || 'Material').toLowerCase()} change) was created on ${m.pending.created || m.pending.date}; it is ${pendLbl(m.pending)}. Version ${m.version} stays in force.`, 'ok')}<div>${btn('Back to ' + m.name, `#/lec/mc/${m.id}`)}</div>`;
  return `${head({ crumb: [['My micro-credentials', '#/lec/home'], [m.name, `#/lec/mc/${m.id}`], ['Change']], title: `Request a change to ${m.name}`, sub: `Current approved version: v${m.version} (${m.approvedBy})` })}
  <div class="row" data-form id="chg"><div class="col grow" style="max-width:680px">
   ${panel('1. What do you want to change?', `<div class="col" style="gap:8px">${CH.map(([k, l, mat]) => choice('c_' + k, l, false, mat ? 'Always a material change' : '')).join('')}</div>`)}
   ${panel('2. Impact', field('Impact note', MC.area('note', '', 'What changes for learners, facilitators and assessment?', true), 'Required. It is kept on the version history.', true))}</div>
   <div style="width:380px" class="col">${panel('Classification', `<div id="chg-cls">${note(chgNote(''))}</div>`)}
    ${panel('Learners already enrolled', `<div>${enrolled} learners are in current cohorts of ${m.name}. They <b>stay on version ${m.version}</b>. A new version applies only to later offerings unless the deputy dean records a transition rule.</div>`)}
    <div id="chg-btn">${dis('Create the change', 'Disabled: choose at least one thing to change')}</div></div></div>`; },
  mount: root => { const f = root.querySelector('#chg'); if (!f) return; f.addEventListener('change', () => { const c = {}; f.querySelectorAll('input[type=checkbox]').forEach(i => { if (i.checked) c[i.name] = 1; }); const k = chgClass(c);
    put(f.querySelector('#chg-cls'), note(`<div><b>${k || 'Not classified'}</b><br>${chgNote(k)}</div>`, k === 'Material' ? 'warn' : ''));
    put(f.querySelector('#chg-btn'), k ? `<span class="btn" data-modal="chgconfirm" data-validate data-id="${location.hash.split('/').pop()}">Create the change</span>` : dis('Create the change', 'Disabled: choose at least one thing to change')); }); } });
modal('chgconfirm', { kicker: c => MC.mcName(c.id), title: c => chgClass(c) === 'Material' ? `Create version ${MC.mc(c.id).version + 1} for approval?` : 'Record this minor change?', confirm: c => chgClass(c) === 'Material' ? `Create version ${MC.mc(c.id).version + 1}` : 'Record minor change',
  body: c => { const m = MC.mc(c.id), k = chgClass(c); return k === 'Material' ? three(`Version ${m.version + 1} is created and submitted to the deputy dean, who assigns reviewers. Version ${m.version} stays in force.${m.qv && m.qv.status === 'Quality-Verified' ? ' Because this is a Quality-Verified SAMC, a QV re-verification task is also created.' : ''}`, `Learners already enrolled do not move: they stay on version ${m.version}.`, 'Deputy dean Hakim Rahman gets a task.')
    : three('The change and your impact note are recorded against the current version and logged to the quality review.', 'No new version and no new approval.', 'Deputy dean Hakim Rahman is asked to acknowledge it.'); },
  act: 'chgcreate', done: c => chgClass(c) === 'Material' ? ['Version created', 'Submitted for review. The current version stays in force until the new one is approved.'] : ['Minor change recorded', 'Logged to the quality review. The deputy dean will acknowledge it.'], then: c => `#/lec/mc/${c.id}` });
act('chgcreate', (c, s) => {
  const m = MC.mc(c.id), k = chgClass(c); if (!k) { MC.toast('Choose what to change', '', 'err'); return false; }
  const what = CH.filter(x => c['c_' + x[0]]).map(x => x[1].toLowerCase()).join(', ');
  if (k === 'Material') { m.pending = { version: m.version + 1, status: 'Submitted', kind: 'material', summary: `${what}. ${c.note}`, created: TODAY, cls: 'Material', note: c.note, date: TODAY, items: what };
    addTask(s, { area: 'proposals', what: 'Assign reviewers', ref: m.id, check: `Version ${m.version + 1}: ${what}`, go: `#/dd/proposal/${m.id}?v=pending` });
    if (m.qv && m.qv.status === 'Quality-Verified') addTask(s, { area: 'quality', what: 'QV re-verification', ref: m.id, check: `Material change to a Quality-Verified SAMC: ${what}`, go: '#/dd/quality' }); }
  else addTask(s, { area: 'proposals', what: 'Acknowledge minor change', ref: m.id, check: `Minor: ${what}. ${c.note}`, go: `#/dd/proposal/${m.id}` });
  nfy(s, 'dd', `${k} change: ${m.name}`, `${ME}: ${what}`, `#/dd/proposal/${m.id}`); aud(s, `${k} change to ${m.name}: ${what}`, m.id); });

/* ---------- reviews (X1) and the industry expert (X2) ---------- */
const revOf = (s, id) => { const r = s.reviews.find(x => x.id === id); if (r) { r.items = r.items || RV_ITEMS.map(() => ({ rate: '', comment: '', blocking: false })); r.cm = r.cm || { general: '', industry: '', outcomes: '' }; } return r; };
const stepOf = r => r.status === 'Submitted' ? 4 : !r.coi ? 1 : /^Conflict/.test(r.coi) ? 0 : 2;
const revReady = r => { const miss = r.items.filter(i => !i.rate).length, noc = r.items.filter(i => i.rate === 'Needs amendment' && !i.comment.trim()).length;
  return miss ? `Disabled: ${miss} item(s) not rated yet` : noc ? `Disabled: ${noc} item(s) marked Needs amendment need a comment` : !r.draftRec ? 'Disabled: choose a recommendation' : !(r.summary || '').trim() ? 'Disabled: write a short summary' : ''; };
const revSub = r => { const why = revReady(r); return why ? dis('Submit review', why) : `<span class="btn" data-modal="lcf" ${cf({ title: `Submit your review of ${MC.mcName(r.mc)}?`, kicker: r.id, confirm: 'Submit review', act: 'revsubmit', id: r.id,
  body: three(`Your ratings, comments and recommendation (${r.draftRec}) go into the review summary for the deputy dean. They are then locked.`, 'This does not decide the proposal. You cannot change the review afterwards.', 'Deputy dean Hakim Rahman.'), done: 'Review submitted|Thank you. The deputy dean now has your recommendation.', then: '#/lec/reviews' })}>Submit review</span>`; };
const summaryOf = m => panel('Proposal', dl([['Category', `${MC.cat(m.cat)} ${MC.CATNAME[m.cat]}`], ['Proposed by', m.owner === 'rosli' ? 'Dr. Rosli Hamzah, FTMK' : m.owner === 'aina' ? ME : 'FTMK'], ['Learning time', `${m.slt} h`],
  ['Modules', m.modules.map(x => `${x[0]} (${x[1]} h)`).join('; ')], ['Weightings', m.weights.join(' / ') + ' %'], ['Credit and QV', m.cat === 'SAMC' ? 'Credit and MQF level proposed only; claimed after QV' : 'Credit as proposed, confirmed on approval']]) + `<div style="margin-top:12px"><span class="btn ter sm" data-go="#/doc/proposal/${m.id}">Open the full proposal</span></div>`);
// the proposal opens only after a conflict-of-interest declaration (or once the review was submitted)
const seeProp = r => r.status === 'Submitted' || (r.coi && !/^Conflict/.test(r.coi));
const propSide = (r, m) => seeProp(r) ? summaryOf(m) : panel('Proposal', '<div class="empty" style="margin:12px">The proposal opens after you declare.</div>');
const coiPanel = (r, who) => {
  const d = r.coi ? (/^Conflict/.test(r.coi) ? note(`You declared a conflict of interest and declined this review. The deputy dean has been asked to assign another reviewer. Details: ${E(r.coi.replace(/^Conflict: /, ''))}`, 'warn') : note(`Declared: ${r.coi}. You may continue.`, 'ok')) : '';
  return panel('Step 1. Conflict of interest', r.coi ? d : r.status === 'Submitted' ? note('Declared before this review was submitted.', 'ok') : `<div class="col" style="gap:12px"><div>Before you open the proposal, declare whether you have any conflict with this proposal: you co-developed it, supervise or are supervised by the proposer, hold a financial interest, or compete with it.</div>
    <div class="row" style="gap:8px"><span class="btn" data-modal="lcf" ${cf({ title: 'Declare no conflict of interest?', kicker: r.id, confirm: 'Declare and continue', act: 'coiok', id: r.id, body: three('Your declaration is recorded and you can start the review.', 'No rating is saved yet.', 'Nobody is told yet; the deputy dean can see the declaration.'), done: 'Declaration recorded|You can now continue.' })}>I have no conflict of interest</span>
    <span class="btn ter" data-modal="coiconflict" data-id="${r.id}">I have a conflict: decline this review</span></div></div>`);
};
modal('coiconflict', { title: 'Decline this review because of a conflict?', kicker: 'Conflict of interest', danger: true, confirm: 'Decline review', reason: 'Describe the conflict (kept in the audit trail)', act: 'coiconf',
  body: '<p><b>What happens:</b> the review is declined and the deputy dean is asked to assign someone else.</p><p><b>What does not happen:</b> you see no ratings or comments.</p><p><b>Who is told:</b> deputy dean Hakim Rahman.</p>', done: 'Review declined|The deputy dean will assign another reviewer.', then: c => c.id === 'RV-033' ? '#/ext/home' : '#/lec/reviews' });
act('coiok', (c, s) => { const r = revOf(s, c.id); r.coi = `No conflict, ${TODAY}`; r.status = 'Accepted'; aud(s, `Declared no conflict of interest for ${r.id}`, r.id, r.reviewer === 'siti' ? 'Ir. Siti Aminah Yusof' : ME); });
act('coiconf', (c, s) => { const r = revOf(s, c.id), who = r.reviewer === 'siti' ? 'Ir. Siti Aminah Yusof' : ME; r.coi = 'Conflict: ' + c.reason; r.status = 'Declined';
  addTask(s, { area: 'proposals', what: 'Reassign reviewer', ref: r.mc, from: who, check: `Conflict of interest declared on ${r.id}`, go: `#/dd/proposal/${r.mc}` }); nfy(s, 'dd', `Reviewer declined: ${MC.mcName(r.mc)}`, `${who} declared a conflict (${r.id})`, `#/dd/proposal/${r.mc}`); aud(s, `Declined review ${r.id}: conflict of interest`, r.id, who); });
act('revsubmit', (c, s) => { const r = revOf(s, c.id); r.status = 'Submitted'; r.rec = r.draftRec; r.comments = r.items.filter(i => i.comment.trim()).length; nfy(s, 'dd', `Review submitted: ${MC.mcName(r.mc)}`, `${ME} recommends: ${r.rec}`, `#/dd/proposal/${r.mc}`); aud(s, `Submitted review ${r.id}: ${r.rec}`, r.id); });

screen('lec/reviews', { title: 'Reviews for colleagues', role: 'lec', nav: 'reviews', render: (p, s) => `${head({ title: 'Reviews for colleagues', sub: 'You review as a peer when the deputy dean invites you. Declare any conflict first.' })}
  ${table({ id: 'lec-reviews', search: 'Search reviews', cols: [{ h: 'Review', k: 'id' }, { h: 'Proposal', r: r => MC.mcNm(r.mc) }, { h: 'Due', k: 'due' }, { h: 'Status', r: r => T(r.status) }, { h: 'Recommendation', r: r => r.rec ? T(r.rec) : '<span class="muted">Not yet</span>' },
    { h: '', r: r => `<a data-go="#/lec/review/${r.id}">${r.status === 'Submitted' || r.status === 'Declined' ? 'View' : 'Review'}</a>` }], rows: s => s.reviews.filter(r => r.reviewer === 'aina'), go: r => `#/lec/review/${r.id}`, empty: 'No review invitations.' })}
  ${note(`You will never be asked to review your own proposals (${mine(s).filter(m => /DB|ST/.test(m.id)).map(m => m.name).join(', ')}). The system refuses it. <a data-go="#/lec/review/SELF">See what the refusal looks like</a>.`)}` });
screen('lec/review/:id', { title: 'Review a proposal', role: 'lec', nav: 'reviews', ex: 'RV-031', render: (p, s) => {
  const r = p.id === 'SELF' ? { id: 'SELF', mc: 'DB', reviewer: 'aina', due: '', status: 'Invited', coi: '' } : revOf(s, p.id); if (!r) return nf('That review does not exist.');
  const m = MC.mc(r.mc), crumb = [['Reviews for colleagues', '#/lec/reviews'], [r.id]];
  if (m.owner === 'aina') return `${head({ crumb, title: `Review: ${m.name}`, status: T('Invited') })}${note(`<div><b>You cannot review this proposal.</b> ${m.name} is your own proposal. The same person cannot propose and review the same record. The deputy dean cannot assign it to you, and if it happens the system refuses it. Nothing was recorded.</div>`, 'err')}<div>${btn('Back to my reviews', '#/lec/reviews')}</div>`;
  const st = stepOf(r), done = r.status === 'Submitted';
  const trk = track([[r.coi || done ? 'done' : 'now', 'Step 1', 'Conflict of interest'], [st >= 2 && !done ? 'now' : done ? 'done' : '', 'Step 2', 'Rate each item'], [done ? 'done' : '', 'Step 3', 'Recommend and submit']]);
  let body = coiPanel(r);
  if (st === 2 || st === 4) body += panel('Step 2. Rate each item', r.items.map((it, i) => `<div style="padding:12px 0;border-bottom:1px solid var(--rule)"><div class="row" style="gap:12px;align-items:center"><div class="grow"><b>${i + 1}.</b> ${RV_ITEMS[i]}</div>
      <select class="input" style="width:190px" name="items.${i}.rate" ${done ? 'disabled' : ''}><option value="">Choose a rating</option>${['Satisfactory', 'Needs amendment'].map(o => `<option ${it.rate === o ? 'selected' : ''}>${o}</option>`).join('')}</select></div>
      <div class="row" style="gap:12px;margin-top:8px;align-items:center"><textarea class="input" style="height:56px" name="items.${i}.comment" placeholder="Comment (required if Needs amendment)" ${done ? 'disabled' : ''}>${E(it.comment)}</textarea>
      <label class="choice" style="white-space:nowrap"><input type="checkbox" name="items.${i}.blocking" ${it.blocking ? 'checked' : ''} ${done ? 'disabled' : ''}><div>Blocking</div></label></div></div>`).join('') + '<div class="small muted" style="margin-top:8px">A blocking comment must be answered before the deputy dean can endorse.</div>')
    + panel('Step 3. Recommendation', done ? `<div>${T(r.rec)} <span class="muted">${E(r.summary)}</span></div>` : `<div class="col" style="gap:12px">${radios('draftRec', [['Support', 'Support', 'Ready to go forward as it is'], ['Amend', 'Amend', 'Go forward once the listed amendments are made'], ['Do not support', 'Do not support', 'Should not go forward']], r.draftRec)}
      ${field('Summary for the deputy dean', `<textarea class="input" name="summary">${E(r.summary)}</textarea>`, '', true)}<div id="rev-sub" class="row" style="gap:8px;align-items:center">${revSub(r)}</div></div>`);
  return `${head({ crumb, title: `Review: ${MC.mcName(r.mc)}`, sub: `${r.id} · assigned by deputy dean Hakim Rahman · due ${r.due}`, status: T(r.status) })}${trk}
  <div class="row"><div class="col grow" data-ws="rev:${r.id}">${body}</div><div style="width:380px">${propSide(r, m)}</div></div>`; } });

screen('ext/home', { title: 'My review invitations', role: 'ext', nav: 'home', render: (p, s) => `${head({ title: 'My review invitations', sub: 'Ir. Siti Aminah Yusof · industry expert, CloudWorks Sdn Bhd' })}
  ${note('As an industry expert you comment on a proposal. You do not rate it, recommend a decision or approve it. Those are academic decisions for the reviewers and the Senate.')}
  ${table({ id: 'ext-reviews', cols: [{ h: 'Proposal', r: r => MC.mcNm(r.mc) }, { h: 'Due', k: 'due' }, { h: 'Status', r: r => T(r.status) }, { h: 'Comments', num: true, r: r => r.comments || '' }, { h: '', r: r => `<a data-go="#/ext/review/${r.id}">${/Invited|Accepted/.test(r.status) ? 'Open' : 'View'}</a>` }],
    rows: s => s.reviews.filter(r => r.reviewer === 'siti'), go: r => `#/ext/review/${r.id}` })}` });
const XNAME = 'Ir. Siti Aminah Yusof';
const EXT_CM = [['general', 'General comments on the proposal'], ['industry', 'Relevance to current industry practice'], ['outcomes', 'Learning outcomes and assessment']];
screen('ext/review/:id', { title: 'Comment on a proposal', role: 'ext', nav: 'home', ex: 'RV-033', render: (p, s) => {
  const r = revOf(s, p.id); if (!r || r.reviewer !== 'siti') return `${head({ title: 'Comment on a proposal' })}${note('That invitation does not exist.', 'warn')}`;
  const m = MC.mc(r.mc), done = r.status === 'Submitted', st = stepOf(r), n = EXT_CM.filter(([k]) => r.cm[k].trim()).length;
  const own = (s.ddComments || MC.seed.ddComments).filter(c => c.mc === r.mc && c.by === XNAME);
  const late = m.status === 'Pending approval' ? note(`This proposal is endorsed and waiting for the Senate decision (${m.senate || 'date to be set'}). Comments are still accepted until the decision is recorded.`) : '';
  return `${head({ crumb: [['My review invitations', '#/ext/home'], [r.id]], title: `Comment on ${MC.mcName(r.mc)}`, sub: `${r.id} · due ${r.due}`, status: T(r.status) })}${late}
  ${track([[r.coi || done ? 'done' : 'now', 'Step 1', 'Conflict of interest'], [st === 2 ? 'now' : done ? 'done' : '', 'Step 2', 'Read and comment'], [done ? 'done' : '', 'Step 3', 'Send comments']])}
  <div class="row"><div class="col grow" data-ws="rev:${r.id}">${coiPanel(r)}
    ${st === 2 || done ? note('You can only comment. There is no rating and no recommendation for the industry expert role: those are academic judgements for the peer reviewers and the Senate.') +
      panel('Step 2. Your comments', (done && !EXT_CM.some(([k]) => r.cm[k].trim()) ? own.map(c => `<div style="padding:8px 0">${E(c.text)}</div>`).join('') : EXT_CM.map(([k, l]) => field(l, `<textarea class="input" name="cm.${k}" ${done ? 'disabled' : ''}>${E(r.cm[k])}</textarea>`)).join('')) +
      (done ? `<div class="muted">Sent ${r.comments} comment(s).</div>` : `<div id="ext-sub" style="margin-top:16px">${extSub(r)}</div>`)) : ''}</div><div style="width:380px">${propSide(r, m)}</div></div>`; } });
const extSub = r => EXT_CM.some(([k]) => r.cm[k].trim()) ? `<span class="btn" data-modal="lcf" ${cf({ title: 'Send your comments?', kicker: r.id, confirm: 'Send comments', act: 'extsubmit', id: r.id,
  body: three('Your comments go to the deputy dean and the proposer, and are locked.', 'You give no rating or recommendation, and this does not decide the proposal.', 'Deputy dean Hakim Rahman and the proposer.'), done: 'Comments sent|Thank you. Your comments are on the proposal record.', then: '#/ext/home' })}>Send comments</span>` : dis('Send comments', 'Disabled: write at least one comment');
AFTER.rev = (ws, id) => { const r = revOf(MC.state, id); if (ws.querySelector('#rev-sub')) put(ws.querySelector('#rev-sub'), revSub(r)); if (ws.querySelector('#ext-sub')) put(ws.querySelector('#ext-sub'), extSub(r)); };
act('extsubmit', (c, s) => { const r = revOf(s, c.id); r.status = 'Submitted'; r.rec = ''; r.comments = EXT_CM.filter(([k]) => r.cm[k].trim()).length;
  cmsOf(s, r.mc); EXT_CM.filter(([k]) => r.cm[k].trim()).forEach(([k, l], i) => s.ddComments.push({ id: `EXT${Date.now().toString(36)}${i}`, mc: r.mc, by: XNAME, role: 'Industry expert', when: TODAY, blocking: false, status: 'open', response: '', text: `${l}: ${r.cm[k].trim()}` }));
  nfy(s, 'lec', `Industry comments received: ${MC.mcName(r.mc)}`, `${XNAME}, ${r.comments} comment(s). Respond on the Comments tab.`, `#/lec/proposal/${r.mc}?tab=comments`); nfy(s, 'dd', `Industry comments received: ${MC.mcName(r.mc)}`, `Ir. Siti Aminah Yusof, ${r.comments} comment(s)`, `#/dd/proposal/${r.mc}`); aud(s, `Submitted industry comments for ${r.id}`, r.id, 'Ir. Siti Aminah Yusof'); });

/* ---------- cohorts and results ---------- */
function resOf(s, c) {
  if (!c) return null;
  return c.learners ? c : (s.lecRes || {})[c.id] || null; // DS: learners live on the cohort record itself
}
const compsOf = c => COMP[c.mc] || [], wOf = c => MC.mc(c.mc).weights;
const finOf = (l, w) => l.m.some(v => v == null) ? null : Math.round(l.m.reduce((a, v, k) => a + v * w[k] / 100, 0));
const heldOf = r => r.learners.filter(l => l.flag).length;
const resSub = (c, r) => {
  const miss = r.learners.reduce((a, l) => a + l.m.filter(v => v == null).length, 0), names = r.learners.flatMap(l => l.m.map((v, k) => v == null ? `${l.n} (${compsOf(c)[k]})` : null)).filter(Boolean);
  const oor = r.learners.reduce((a, l) => a + l.m.filter(v => v != null && (v < 0 || v > 100)).length, 0);
  return miss ? dis('Submit for verification', `Disabled: ${miss} mark${miss > 1 ? 's' : ''} missing: ${names.join(', ')}`) : oor ? dis('Submit for verification', `Disabled: ${oor} mark${oor > 1 ? 's' : ''} outside 0 to 100`) : `<span class="btn" data-modal="lcf" ${cf({ title: `Submit ${r.learners.length} results for verification?`, kicker: `${MC.mcName(c.mc)}, ${c.name}`, confirm: 'Submit for verification', act: 'subres', id: c.id,
    body: three(`All ${r.learners.length} results go to the deputy dean for verification and are locked. ${heldOf(r) ? heldOf(r) + ' held result(s) stay held. ' : ''}You cannot verify results you entered.`, 'No module badge, certificate or credential is created yet. Those follow verification and a separate completion step.', 'Deputy dean Hakim Rahman gets a task.'),
    done: 'Results submitted|Hakim Rahman will verify them. Badges and credentials come after.', then: '#/lec/cohorts' })}>Submit for verification</span>`;
};
AFTER.coh = (ws, id, t) => {
  const c = MC.co(id), r = resOf(MC.state, c), m = /learners\.(\d+)\.m\.(\d+)/.exec(t.name); if (!m) return;
  const i = +m[1], l = r.learners[i], f = finOf(l, wOf(c)), q = x => document.querySelector(x);
  const v = l.m[+m[2]]; if (v != null) { l.src = `Entered by ${ME}, ${TODAY}`; if (q('#src-' + i)) q('#src-' + i).textContent = l.src; }
  t.classList.toggle('bad', t.value === '' || v < 0 || v > 100); if (q('#fin-' + i)) q('#fin-' + i).textContent = f == null ? '' : f; if (q('#rs-' + i)) q('#rs-' + i).innerHTML = f == null ? T('Missing') : T(f >= 50 ? 'Pass' : 'Fail');
  const miss = r.learners.reduce((a, x) => a + x.m.filter(v => v == null).length, 0); q('#res-miss').textContent = miss; put(q('#res-sub'), resSub(c, r));
};
screen('lec/cohorts', { title: 'Cohorts and results', role: 'lec', nav: 'cohorts', render: (p, s) => `${head({ title: 'Cohorts and results', sub: 'Every offering of your micro-credentials', actions: btn('Create offering', '#/lec/offering/new', '') })}
  ${table({ id: 'lec-cohorts', search: 'Search cohorts', cols: [{ h: 'Micro-credential', r: c => MC.mcNm(c.mc) + `<span class="s">${c.name}</span>` }, { h: 'Dates', r: c => `${c.start} to ${c.end}` }, { h: 'Status', r: c => T(c.status) },
    { h: 'Learners', num: true, r: c => c.enrolled || `${c.taken || 0} of ${c.places}` }, { h: 'Results', r: c => c.results ? T(c.results) : '<span class="muted">Not started</span>' }, { h: '', r: c => c.status === 'Not ready' ? `<a data-go="#/lec/readiness/${c.id}">Readiness</a>` : `<a data-go="#/lec/cohort/${c.id}">Open</a>` }],
    rows: s => s.cohorts.filter(c => c.owner === 'aina'), go: c => c.status === 'Not ready' ? `#/lec/readiness/${c.id}` : `#/lec/cohort/${c.id}` })}` });
modal('intflag', { title: 'Hold this result for an integrity case?', kicker: c => `${c.name}`, danger: true, confirm: 'Hold result', reason: 'Reason for the integrity flag (kept in the audit trail)', act: 'intflag',
  body: c => `<p><b>What happens:</b> the result of <b>${c.name}</b> is held. Held results cannot be verified until the deputy dean closes the case.</p><p><b>What does not happen:</b> the mark is not changed and the learner is not told yet.</p><p><b>Who is told:</b> deputy dean Hakim Rahman.</p>`, done: 'Result held|The deputy dean can see the flag and the reason.' });
act('intflag', (c, s) => { const co = MC.co(c.id), l = resOf(s, co).learners[+c.i]; l.flag = c.reason; nfy(s, 'dd', `Integrity flag: ${MC.mcName(co.mc)} ${co.name}`, `${ME} held the result of ${l.n}`, `#/dd/verify/${co.id}`); aud(s, `Held result of ${l.n} (integrity): ${c.reason}`, co.id); });
act('subres', (c, s) => { const co = MC.co(c.id), r = resOf(s, co), w = wOf(co); co.results = 'Submitted';
  co.learners = r.learners.map(l => { const f = finOf(l, w); return Object.assign(l, { fin: f, res: l.flag ? 'Held' : f >= 50 ? 'Pass' : 'Fail' }); });
  addTask(s, { area: 'results', what: 'Verify results', ref: co.id, check: `${r.learners.length} results, ${heldOf(r)} held · entered by ${ME}`, go: `#/dd/verify/${co.id}` });
  nfy(s, 'dd', `Results ready to verify: ${MC.mcName(co.mc)} ${co.name}`, `${ME} submitted ${r.learners.length} results`, `#/dd/verify/${co.id}`); aud(s, `Submitted results, ${MC.mcName(co.mc)} ${co.name} (${r.learners.length})`, co.id); });
screen('lec/cohort/:id', { title: 'Cohort results', role: 'lec', nav: 'cohorts', ex: 'CND-2026-2', render: (p, s) => {
  const c = MC.co(p.id); if (!c) return nf('That cohort does not exist.');
  const r = resOf(s, c), crumb = [['Cohorts and results', '#/lec/cohorts'], [`${MC.mcName(c.mc)} ${c.name}`]], title = `${MC.mcName(c.mc)}, ${c.name}`;
  const exp = '<span class="btn ter" data-go="#/doc/export/' + c.id + '">Export participant list</span>';
  if (!r) return `${head({ crumb, title, sub: `${c.start} to ${c.end}`, status: T(c.status), actions: exp })}
    ${kpis([['Status', c.status], ['Learners', c.enrolled || `${c.taken || 0} of ${c.places}`], ['Results', c.results || 'Not started']])}
    ${note(c.results === 'Verified' ? `Results were verified. ${c.achieved || 0} of ${c.enrolled} learners achieved.` : c.status === 'Not ready' ? 'This offering is not ready yet. Finish the readiness checklist first.' : 'No results to enter yet: the cohort has not ended.')}
    ${c.status === 'Not ready' ? `<div>${btn('Open readiness', '#/lec/readiness/' + c.id, '')}</div>` : ''}`;
  const edit = c.results === 'Draft', comps = compsOf(c), w = wOf(c), miss = r.learners.reduce((a, l) => a + l.m.filter(v => v == null).length, 0), prov = r.learners.filter(l => /import/i.test(l.src)).length;
  return `${head({ crumb, title, sub: `${c.start} to ${c.end} · pass mark 50 · weights ${w.join(' / ')} %`, status: T(c.results), actions: exp + (edit ? `<span id="res-sub" style="display:flex;gap:8px;align-items:center">${resSub(c, r)}</span>` : '') })}
  ${kpis([['Learners', r.learners.length], ['Imported from UCreds', prov, 'Shown as Provisional until verified'], ['Marks missing', `<span id="res-miss">${miss}</span>`], ['Held for integrity', heldOf(r)]])}
  ${edit ? (miss ? note(`<div><b>${miss} mark${miss > 1 ? 's' : ''} missing.</b> The 25 Oct UCreds import rejected the rows below for empty marks. Type the mark in the highlighted cell to continue.</div>`, 'warn') : note('All marks are in. You can submit for verification.', 'ok')) : note(c.results === 'Submitted' ? 'Submitted to the deputy dean. Marks are locked until verification.' : 'Verified. Marks are locked.', 'ok')}
  ${note('You entered or reviewed these results, so you cannot verify them. Verification belongs to the deputy dean.')}
  <div data-ws="coh:${c.id}">${table({ id: 'lec-res-' + c.id, search: 'Search learners', cols: [{ h: 'Learner', r: x => `${x.l.n}<span class="s">${x.l.id}</span>` }]
    .concat(comps.map((n, k) => ({ h: `${n} (${w[k]}%)`, num: true, r: x => edit && !x.l.flag ? `<input class="input ${x.l.m[k] == null ? 'bad' : ''}" style="width:76px;height:32px;display:inline-block" type="number" min="0" max="100" name="learners.${x.i}.m.${k}" value="${x.l.m[k] == null ? '' : x.l.m[k]}" ${x.l.m[k] == null ? 'required' : ''} aria-label="${x.l.n} ${n}">` : (x.l.m[k] == null ? '' : x.l.m[k]) })),
      [{ h: 'Final', num: true, r: x => `<span id="fin-${x.i}">${finOf(x.l, w) == null ? '' : finOf(x.l, w)}</span>` }, { h: 'Result', r: x => `<span id="rs-${x.i}">${x.l.flag ? T('Held (integrity)') : finOf(x.l, w) == null ? T('Missing') : T(finOf(x.l, w) >= 50 ? 'Pass' : 'Fail')}</span>` },
        { h: 'Source', r: x => `${c.results === 'Verified' ? T('Verified') : T('Provisional')}<span class="s" id="src-${x.i}">${x.l.src}</span>` },
        { h: '', r: x => x.l.flag ? `<span class="small muted" title="${E(x.l.flag)}">Held</span>` : edit ? `<a data-modal="intflag" data-id="${c.id}" data-i="${x.i}" data-name="${E(x.l.n)}">Flag integrity</a>` : '' }]),
    // rows needing a mark come first, so the note's "rows below" are on page 1
    rows: s => resOf(s, MC.co(c.id)).learners.map((l, i) => ({ i, l })).sort((a, b) => (b.l.m.some(v => v == null) - a.l.m.some(v => v == null))), empty: 'No learners.' })}</div>`; } });

/* ---------- create an offering and readiness ---------- */
const rdOf = (s, c) => { s.lecRead = s.lecRead || {}; const r = (s.lecRead[c.id] = s.lecRead[c.id] || rdDefault());
  if ((c.readiness || 0) >= 12) r.items.forEach(i => { if (i.st === 'Not yet') { i.st = 'Yes'; i.ev = 'Resolved by the deputy dean'; } }); // the deputy dean marked the checklist resolved
  return r; };
const rdDone = r => r.items.filter(i => i.st === 'Yes' || i.st === 'Not applicable').length;
const rdSub = (c, r) => { const o = r.items.filter(i => i.owner === 'Lecturer' && i.st === 'Not yet').length;
  return r.sent ? `${T('Sent')}<span class="small muted">Sent to the deputy dean</span>` : o ? dis('Send to deputy dean', `Disabled: ${o} of your items still Not yet`) : `<span class="btn" data-modal="lcf" ${cf({ title: 'Send the readiness checklist to the deputy dean?', kicker: `${MC.mcName(c.mc)}, ${c.name}`, confirm: 'Send to deputy dean', act: 'sendready', id: c.id,
    body: three('Your part of the checklist (Appendix B) goes to the deputy dean, who confirms Ready, Ready with conditions or Not ready.', 'The offering is not opened for registration and does not appear in the catalogue yet.', 'Deputy dean Hakim Rahman gets a task.'), done: 'Sent to the deputy dean|Registration opens only after he confirms readiness.' })}>Send to deputy dean</span>`; };
AFTER.rd = (ws, id) => { const c = MC.co(id), r = rdOf(MC.state, c), q = x => document.querySelector(x); c.readiness = rdDone(r); put(q('#rd-sub'), rdSub(c, r)); q('#rd-n').textContent = c.readiness; q('#rd-open').textContent = r.items.filter(i => i.owner === 'Lecturer' && i.st === 'Not yet').length; };
screen('lec/offering/new', { title: 'Create an offering', role: 'lec', nav: 'cohorts', render: (p, s) => {
  const ms = mine(s), okm = ms.filter(m => m.status === 'Approved'), pre = okm.find(m => m.id === p.mc) ? p.mc : okm[0].id;
  return `${head({ crumb: [['Cohorts and results', '#/lec/cohorts'], ['Create an offering']], title: 'Create an offering', sub: 'An offering is a cohort of an approved version. It stays hidden until the deputy dean confirms it is ready.' })}
  <div class="col" data-form style="max-width:720px">
   ${panel('Micro-credential', field('Approved version to offer', `<select class="input" name="mc">${ms.map(m => `<option value="${m.id}" ${m.status === 'Approved' ? '' : 'disabled'} ${m.id === pre ? 'selected' : ''}>${m.name} v${m.version}${m.status === 'Approved' ? '' : ` (${m.status}: cannot be offered)`}</option>`).join('')}</select>`,
     `Only Approved versions can be offered. Not offerable now: ${ms.filter(m => m.status !== 'Approved').map(m => `${m.name} is ${m.status}`).join('; ')}.`, true))}
   ${panel('Dates, capacity and fee', `<div class="col">${I('Cohort name', 'name', 'Cohort 2027/2', { req: 1 })}<div class="row">${I('Start date', 'start', '2027-07-05', { req: 1, type: 'date' })}${I('End date', 'end', '2027-10-24', { req: 1, type: 'date' })}</div>
     <div class="row">${I('Places', 'places', 40, { req: 1, type: 'number', attr: 'min="1"' })}${I('Fee (RM)', 'fee', 450, { req: 1, type: 'number', attr: 'min="0"' })}${I('Mode', 'mode', 'Online, facilitated', { opts: ['Online, facilitated', 'Blended', 'On campus'] })}</div>
     <div class="row">${I('Registration opens', 'ropen', '2027-05-03', { req: 1, type: 'date' })}${I('Registration closes', 'rclose', '2027-06-25', { req: 1, type: 'date' })}</div></div>`)}
   ${panel('Facilitators', `<div class="col" style="gap:8px">${choice('fac_aina', 'Dr. Aina Zulkifli (you)', true)}${choice('fac_rosli', 'Dr. Rosli Hamzah', false)}</div>`)}
   <div class="row" style="gap:8px"><span class="btn" data-act="newoff" data-validate>Create offering</span>${btn('Cancel', '#/lec/cohorts')}</div></div>`; } });
act('newoff', (c, s) => {
  const m = MC.mc(c.mc); if (!m || m.status !== 'Approved') { MC.toast('Cannot create the offering', 'Only an approved version can be offered.', 'err'); return false; }
  if (c.end <= c.start) { MC.toast('Check the dates', 'The end date must be after the start date.', 'err'); return false; }
  if (c.rclose >= c.start) { MC.toast('Check the dates', 'Registration must close before the cohort starts.', 'err'); return false; }
  const yr = c.start.slice(0, 4), id = `${m.id}-${yr}-${s.cohorts.filter(x => x.id.startsWith(`${m.id}-${yr}-`)).length + 1}`;
  s.cohorts.push({ id, mc: m.id, name: c.name, start: iso(c.start), end: iso(c.end), places: +c.places, taken: 0, fee: +c.fee, status: 'Not ready', readiness: 0, owner: 'aina', mode: c.mode, regOpen: iso(c.ropen), regClose: iso(c.rclose), facilitators: [c.fac_aina && ME, c.fac_rosli && 'Dr. Rosli Hamzah'].filter(Boolean) });
  aud(s, `Created offering ${m.name} ${c.name} (Not ready)`, id); MC.save(); MC.go(`#/lec/readiness/${id}`); MC.toast('Offering created', 'It is Not ready. Finish your part of the readiness checklist.'); return false; });
screen('lec/readiness/:id', { title: 'Readiness checklist', role: 'lec', nav: 'cohorts', ex: 'DS-2027-1', render: (p, s) => {
  const c = MC.co(p.id); if (!c) return nf('That offering does not exist.');
  const r = rdOf(s, c), m = MC.mc(c.mc), qvLate = m.qv && m.qv.to && /Dec 2026/.test(m.qv.to) && /2027/.test(c.end);
  return `${head({ crumb: [['Cohorts and results', '#/lec/cohorts'], [`${m.name} ${c.name}`]], title: `Readiness: ${m.name}, ${c.name}`, sub: `${c.start} to ${c.end} · ${c.places} places · ${MC.rm(c.fee)}`, status: T(c.status), actions: `<span id="rd-sub" style="display:flex;gap:8px;align-items:center">${rdSub(c, r)}</span>` })}
  ${kpis([['Items done', `<span id="rd-n">${rdDone(r)}</span> of ${r.items.length}`], ['Yours still open', `<span id="rd-open">${r.items.filter(i => i.owner === 'Lecturer' && i.st === 'Not yet').length}</span>`]])}
  ${qvLate ? note(`QV for ${m.name} ends ${m.qv.to}, before this cohort ends. The Quality-Verified claim, credit and MQF level stay hidden for this cohort until QV is renewed.`, 'warn') : ''}
  <div data-ws="rd:${c.id}">${table({ id: 'lec-rd-' + c.id, title: 'Appendix B checklist', size: 25, cols: [{ h: 'Item', r: x => `${x.it.t}<span class="s">${x.it.g || x.it.k}</span>` }, { h: 'Owner', r: x => x.it.owner === 'Lecturer' ? 'You' : x.it.owner },
    { h: 'Status', r: x => x.it.owner === 'Lecturer' ? `<select class="input" style="height:32px" name="items.${x.i}.st">${['Yes', 'Not yet', 'Not applicable'].map(o => `<option ${x.it.st === o ? 'selected' : ''}>${o}</option>`).join('')}</select>` : T(x.it.st) },
    { h: 'Evidence note', r: x => x.it.owner === 'Lecturer' ? `<input class="input" style="height:32px" name="items.${x.i}.ev" value="${E(x.it.ev)}" placeholder="Evidence note">` : E(x.it.ev) },
    { h: '', r: x => x.it.owner === 'Lecturer' && x.it.st === 'Not yet' ? `<a data-act="uploadrec" data-id="${c.id}" data-i="${x.i}" data-done="Record uploaded|The item is now Yes. Send the checklist to the deputy dean.">Upload a record</a>` : '' }],
    rows: s => rdOf(s, MC.co(c.id)).items.map((it, i) => ({ it, i })) })}</div>`; } });
act('uploadrec', (c, s) => { const r = rdOf(s, MC.co(c.id)), it = r.items[+c.i]; it.st = 'Yes'; it.ev = `Uploaded ${it.k.toLowerCase()}-record-${c.id}.pdf, ${TODAY}`; MC.co(c.id).readiness = rdDone(r); aud(s, `Uploaded record for ${it.k} ${it.t}`, c.id); });
act('sendready', (c, s) => { const co = MC.co(c.id), r = rdOf(s, co); r.sent = true; co.readiness = rdDone(r); const t = s.tasks.find(x => x.ref === co.id && x.area === 'offerings');
  if (t) { t.check = `${rdDone(r)} of 12 readiness items done`; t.waiting = 'Today'; } else addTask(s, { area: 'offerings', what: 'Confirm readiness', ref: co.id, check: `${rdDone(r)} of 12 readiness items done`, go: `#/dd/readiness/${co.id}` });
  nfy(s, 'dd', `Readiness sent: ${MC.mcName(co.mc)} ${co.name}`, `${ME}: ${rdDone(r)} of 12 items done`, `#/dd/readiness/${co.id}`); aud(s, `Sent readiness checklist to the deputy dean (${rdDone(r)} of 12)`, co.id); });

/* ---------- CQI ---------- */
const effKinds = ['Effective', 'Partly effective', 'Not effective'];
screen('lec/cqi', { title: 'Quality review (CQI)', role: 'lec', nav: 'cqi', render: (p, s) => `${head({ title: 'Quality review (CQI)', sub: 'One record per cohort: indicators, issues, actions and a check that the action worked' })}
  ${table({ id: 'lec-cqi', cols: [{ h: 'Record', r: c => `${c.id}<span class="s">${MC.mcName(c.mc)}</span>` }, { h: 'Cohort', r: c => (MC.co(c.cohort) || {}).name }, { h: 'Status', r: c => T(c.status) }, { h: 'Due or submitted', r: c => c.submitted ? `Submitted ${c.submitted}` : `Due ${c.due}` }, { h: '', r: c => `<a data-go="#/lec/cqi/${c.id}">Open</a>` }],
    rows: s => s.cqi.filter(c => c.owner === 'aina'), go: c => `#/lec/cqi/${c.id}` })}` });
const actBtns = (id, a, edit) => !edit ? '' : a.status === 'Open' ? `<a data-act="cqistat" data-id="${id}" data-a="${a.id}" data-to="In progress" data-done="Action started|Status is In progress.">Start</a>`
  : a.status === 'In progress' ? `<a data-act="cqistat" data-id="${id}" data-a="${a.id}" data-to="Done" data-done="Action marked done|It cannot be closed until the effectiveness check is recorded.">Mark done</a>`
  : a.status === 'Done' ? `<div class="col" style="gap:4px;align-items:flex-start"><a data-modal="cqieff" data-id="${id}" data-a="${a.id}">Record effectiveness check</a>${a.eff ? `<span class="btn ter sm" data-modal="lcf" ${cf({ title: 'Close this action?', kicker: a.id, confirm: 'Close action', act: 'cqiclose', id, a: a.id, body: three(`The action is closed with its effectiveness result (${a.eff}).`, 'The deputy dean still confirms the whole CQI record.', 'Nobody is told yet.'), done: 'Action closed|Effectiveness check is on record.' })}>Close action</span>` : `<span class="btn dis sm">Close action</span><span class="small muted">Disabled: record an effectiveness check first</span>`}</div>` : '';
screen('lec/cqi/:id', { title: 'Quality review record', role: 'lec', nav: 'cqi', ex: 'CQI-DS-2026-1', render: (p, s) => {
  const rec = s.cqi.find(x => x.id === p.id), d = (s.lecCqi || {})[p.id]; if (!rec || !d) return nf('That quality review does not exist.');
  const edit = rec.status === 'Draft', co = MC.co(rec.cohort), allActed = d.iss.every(i => d.act.some(a => a.issue === i.id)), open = d.act.filter(a => a.status !== 'Closed').length;
  return `${head({ crumb: [['Quality review', '#/lec/cqi'], [rec.id]], title: `${MC.mcName(rec.mc)}, ${co.name}`, sub: `${rec.id} · ${edit ? 'due ' + rec.due : 'submitted ' + rec.submitted}`, status: T(rec.status),
    actions: edit ? (allActed ? `<span class="btn" data-modal="lcf" ${cf({ title: 'Submit this quality review to the deputy dean?', kicker: rec.id, confirm: 'Submit to deputy dean', act: 'cqisubmit', id: rec.id,
      body: three(`The indicators, ${d.iss.length} issue(s) and ${d.act.length} action(s) go to the deputy dean to confirm. ${open} action(s) are still open; they continue after confirmation.`, 'The record is not confirmed yet and nothing changes for learners.', 'Deputy dean Hakim Rahman gets a task.'), done: 'Submitted to the deputy dean|He will confirm the record.', then: '#/lec/cqi' })}>Submit to deputy dean</span>` : dis('Submit to deputy dean', 'Disabled: every issue needs at least one action')) : '' })}
  ${panel('Indicators against targets', `<table><thead><tr><th>Indicator</th><th>Basis</th><th class="num">Result</th><th class="num">Target</th><th>Against target</th></tr></thead><tbody>${d.ind.map(i => `<tr><td>${i[0]}</td><td>${i[1]}</td><td class="num">${i[2]}${i[4]}</td><td class="num">${i[3]}${i[4]}</td><td>${i[2] >= i[3] ? T('Meets target') : '<span class="tag err">Below target</span>'}</td></tr>`).join('')}</tbody></table>`, '', false)}
  ${panel('Learner feedback', d.fb)}
  <div data-ws="cqi:${rec.id}">${panel('Issues', d.iss.map((i, k) => `<div style="padding:10px 0;border-bottom:1px solid var(--rule)"><div class="row" style="gap:8px;align-items:center"><b>${i.id}</b> ${i.text} ${T(i.priority)}</div>
    <div class="small muted" style="margin-top:4px">Root cause: ${i.root} · Impact: ${i.impact}</div></div>`).join(''))}</div>
  ${panel('Actions', `<table><thead><tr><th>Action</th><th>Owner</th><th>Due</th><th>Success indicator</th><th>Status</th><th></th></tr></thead><tbody>${d.act.map(a => `<tr><td>${a.id} ${a.text}<span class="s">For issue ${a.issue}${a.eff ? ' · effectiveness: ' + a.eff : ''}</span></td><td>${a.owner}</td><td>${a.due}</td><td>${a.ind}</td>
    <td>${a.status === 'Done' ? '<span class="tag warn">Done (effectiveness pending)</span>' : T(a.status)}</td><td>${actBtns(rec.id, a, /Draft|Submitted|Confirmed/.test(rec.status))}${a.outcome && a.status !== 'Closed' ? `<div style="margin-top:4px"><a data-go="#/lec/change/${rec.mc}">Create change request</a><span class="s">This action changes a learning outcome</span></div>` : ''}</td></tr>`).join('')}</tbody></table>`,
    edit ? `<span class="btn ter sm" data-modal="cqiadd" data-id="${rec.id}">Add action</span>` : '', false)}
  ${edit ? note('An action can be closed only after someone records whether it worked: Effective, Partly effective or Not effective. The deputy dean confirms the whole record after you submit.') : ''}`; } });
modal('cqieff', { title: 'Record the effectiveness check', kicker: c => `Action ${c.a}`, confirm: 'Save check', act: 'cqieffsave', done: 'Effectiveness check saved|You can now close the action.',
  body: c => field('Did it work?', MC.sel('eff', effKinds, 'Effective'), 'Compare the success indicator with the result.') + field('Evidence', MC.area('evidence', '', 'For example: LO3 attainment was 78% in the next cohort', true), '', true) });
modal('cqiadd', { title: 'Add an action', kicker: c => c.id, confirm: 'Add action', act: 'cqiadd', done: 'Action added|It starts as Open.',
  body: c => field('Issue', MC.sel('issue', ((MC.state.lecCqi || {})[c.id] || { iss: [] }).iss.map(i => i.id), 'I1'), '', true) + field('Action', MC.area('text', '', '', true), '', true) + field('Owner', MC.inp('owner', ME, '', true), '', true) + field('Due date', MC.inp('due', '', '', true, 'date'), '', true) + field('Success indicator', MC.inp('ind', '', '', true), '', true) });
const cqiAct = c => ((MC.state.lecCqi || {})[c.id] || { act: [] }).act.find(a => a.id === c.a);
act('cqistat', c => { cqiAct(c).status = c.to; aud(MC.state, `CQI action ${c.a} set to ${c.to}`, c.id); });
act('cqieffsave', c => { const a = cqiAct(c); a.eff = c.eff; a.evid = c.evidence; aud(MC.state, `Recorded effectiveness check for action ${a.id}: ${c.eff}`, c.id); });
act('cqiclose', c => { const a = cqiAct(c); if (!a.eff) { MC.toast('Cannot close', 'Record an effectiveness check first.', 'err'); return false; } a.status = 'Closed'; aud(MC.state, `Closed CQI action ${a.id}`, c.id); });
act('cqiadd', (c, s) => { const d = s.lecCqi[c.id]; d.act.push({ id: 'A' + (d.act.length + 1), issue: c.issue, text: c.text, owner: c.owner, due: iso(c.due), ind: c.ind, status: 'Open', eff: '', outcome: false }); });
act('cqisubmit', (c, s) => { const rec = s.cqi.find(x => x.id === c.id); rec.status = 'Submitted'; rec.submitted = TODAY;
  addTask(s, { area: 'quality', what: 'Confirm quality review', ref: rec.id, check: `${s.lecCqi[rec.id].act.length} actions · ${s.lecCqi[rec.id].act.filter(a => a.eff).length} effectiveness check(s) recorded`, go: `#/dd/cqi/${rec.id}` });
  nfy(s, 'dd', `Quality review submitted: ${MC.mcName(rec.mc)}`, `${ME}, ${rec.id}`, `#/dd/cqi/${rec.id}`); aud(s, `Submitted quality review ${rec.id}`, rec.id); });

/* ---------- QV evidence ---------- */
const doneCoh = (s, id) => s.cohorts.filter(c => c.mc === id && c.results === 'Verified').length;
function qvOf(s, m) {
  s.lecQv = s.lecQv || {};
  if (!s.lecQv[m.id]) { const n = doneCoh(s, m.id), cq = s.cqi.filter(c => c.mc === m.id && /Submitted|Confirmed/.test(c.status)).length;
    s.lecQv[m.id] = { ready: false, sent: false, items: [['D3.1', 'Learning outcomes (Appendix A)', 'Available', `Proposal v${m.version}`, true], ['D3.2', 'SLT breakdown', 'Available', `${m.slt} h across ${m.modules.length} modules`, true], ['D4.1', 'Assessment design and weightings', 'Available', m.weights.join('/') + ' %', true],
      ['D5.1', 'Cohort results', n ? 'Available' : 'Not yet', `${n} verified cohort(s)`, true], ['D5.2', 'Learner feedback summaries', n ? 'Available' : 'Not yet', n ? 'From completed cohorts' : 'No cohort feedback yet', true], ['D6.1', 'CQI records', cq ? 'Available' : 'Not yet', `${cq} submitted`, true],
      ['D6.2', 'Version history', 'Available', `v${m.version}`, true], ['D4.2', 'Facilitator qualification records', m.id === 'CND' ? 'Available' : 'Not yet', m.id === 'CND' ? 'facilitators-cnd-2026.pdf' : '', false],
      ['D6.3', 'Industry or stakeholder input', m.id === 'CND' ? 'Not yet' : 'Not yet', '', false], ['D7.2', 'Provider registration evidence', m.id === 'CND' ? 'Available' : 'Not yet', m.id === 'CND' ? 'provider-registration.pdf' : '', false]].map(([k, t, st, f, auto]) => ({ k, t, st, f, auto })) }; }
  return s.lecQv[m.id];
}
const qvElig = (s, m) => { const n = doneCoh(s, m.id); return [[m.cat === 'SAMC' ? '' : 'err', 'Stand-alone offering', ''], [m.slt >= 40 ? '' : 'err', `Learning time ${m.slt} h is at least 40 h`, ''], ['', 'Provider is registered', ''],
  [n >= 1 ? '' : 'err', n >= 1 ? `${n} completed cohort(s) with verified results` : `Not ready to submit: at least 1 completed cohort is required. ${m.name} has 0.`, ''], [m.credits && m.mqf ? '' : 'err', m.credits && m.mqf ? `Credit (${m.credits}) and MQF level ${m.mqf} are justified` : 'Proposed credit or MQF level has no justification yet', '']]; };
const qvSub = (s, m, q) => { const renew = m.qv && m.qv.status === 'Quality-Verified', el = qvElig(s, m), bad = el.filter(x => x[0]).length, open = q.items.filter(i => i.st === 'Not yet').length;
  if (q.sent) return `${T('Sent')}<span class="small muted">Package sent to the deputy dean</span>`;
  if (q.ready) return `<span class="btn" data-modal="lcf" ${cf({ title: 'Send the QV package to the deputy dean?', kicker: m.name, confirm: 'Send package', act: 'qvsend', id: m.id, body: three('The evidence checklist and auto-linked evidence go to the deputy dean, who confirms readiness.', 'Nothing is submitted to QVC/MQA yet. CODL staff do that after the deputy dean confirms.', 'Deputy dean Hakim Rahman gets a task.'), done: 'Package sent|The deputy dean will confirm readiness.' })}>Send package to deputy dean</span>`;
  return bad ? dis('Ready to submit', `Disabled: ${bad} eligibility check${bad > 1 ? 's' : ''} failing${m.id !== 'CND' && doneCoh(s, m.id) < 1 ? ': at least 1 completed cohort is required' : ''}`) : open ? dis('Ready to submit', `Disabled: ${open} evidence item(s) still Not yet`)
    : `<span class="btn" data-modal="lcf" ${cf({ title: 'Mark as Ready to submit?', kicker: m.name, confirm: 'Mark ready', act: 'qvready', id: m.id,
      body: three(renew ? 'The renewal package is marked ready. You can then send it to the deputy dean.' : 'QV moves from Preparing to Ready. You can then send the package to the deputy dean.', renew ? 'Nothing is submitted to QVC/MQA, and the current Quality-Verified status continues until the new result is recorded.' : 'Nothing is submitted to QVC/MQA yet.', 'Nobody is told yet.'),
      done: renew ? 'Renewal package marked ready|Now send the package to the deputy dean.' : 'Ready to submit|Now send the package to the deputy dean.' })}>Mark ready to submit</span>`; };
screen('lec/qv', { title: 'QV evidence', role: 'lec', nav: 'qv', render: (p, s) => `${head({ title: 'QV evidence', sub: 'Quality verification applies only to stand-alone micro-credentials (SAMC). Credit and MQF level appear only after QV is recorded.' })}
  ${table({ id: 'lec-qv', cols: [{ h: 'SAMC', r: m => MC.mcNm(m.id) }, { h: 'QV status', r: m => T((m.qv || {}).status || 'Not applied') }, { h: 'Valid until', r: m => (m.qv || {}).to || '—' }, { h: 'Completed cohorts', num: true, r: m => doneCoh(s, m.id) }, { h: '', r: m => `<a data-go="#/lec/qv/${m.id}">Open evidence</a>` }],
    rows: s => s.mcs.filter(m => m.cat === 'SAMC' && m.owner === 'aina'), go: m => `#/lec/qv/${m.id}` })}` });
screen('lec/qv/:id', { title: 'QV evidence', role: 'lec', nav: 'qv', ex: 'CND', render: (p, s) => {
  const m = MC.mc(p.id); if (!m || m.cat !== 'SAMC') return nf('QV applies only to a SAMC.');
  if (m.owner !== 'aina') return nf('You are not the owner of this micro-credential, so its QV evidence is not yours to prepare.');
  const q = qvOf(s, m), el = qvElig(s, m);
  return `${head({ crumb: [['QV evidence', '#/lec/qv'], [m.name]], title: `QV evidence: ${m.name}`, status: T((m.qv || {}).status || 'Not applied'), sub: m.qv && m.qv.to ? `Quality-Verified to ${m.qv.to}${m.qv.daysLeft ? ` · ${m.qv.daysLeft} days left` : ''}` : 'Not quality-verified', actions: `${btn('View QV package', `#/doc/qvpack/${m.id}`)}<span id="qv-sub" style="display:flex;gap:8px;align-items:center">${qvSub(s, m, q)}</span>` })}
  ${doneCoh(s, m.id) < 1 ? note(`<div><b>Not ready to submit:</b> at least 1 completed cohort is required. ${m.name} has 0 completed cohorts with verified results, so the gate stays shut.</div>`, 'err') : ''}
  <div class="row"><div class="grow">${panel('Evidence checklist (D3 to D7)', `<table><thead><tr><th>Item</th><th>Status</th><th>Reference</th><th></th></tr></thead><tbody>${q.items.map((i, k) => `<tr><td>${i.k} ${i.t}${i.auto ? '<span class="s">Linked automatically from MCMS</span>' : '<span class="s">Upload</span>'}</td><td>${T(i.st)}</td><td>${E(i.f)}</td>
      <td>${i.st === 'Not yet' && !i.auto ? `<a data-act="qvupload" data-id="${m.id}" data-i="${k}" data-done="Evidence uploaded|The item is now Available.">Upload evidence</a>` : ''}</td></tr>`).join('')}</tbody></table>`, '', false)}</div>
  <div style="width:360px">${panel('Eligibility (D2)', checks(el.map(([k, t, sub]) => [k, t, sub])))}</div></div>`; } });
act('qvupload', (c, s) => { const i = qvOf(s, MC.mc(c.id)).items[+c.i]; i.st = 'Available'; i.f = `${i.k.toLowerCase()}-evidence.pdf`; aud(s, `Uploaded QV evidence ${i.k}`, c.id); });
act('qvready', (c, s) => { const m = MC.mc(c.id); qvOf(s, m).ready = true; if (!m.qv) m.qv = {}; if (m.qv.status !== 'Quality-Verified') m.qv.status = 'Ready'; aud(s, `Set Ready to submit for QV: ${MC.mc(c.id).name}`, c.id); });
act('qvsend', (c, s) => { const m = MC.mc(c.id); qvOf(s, m).sent = true; addTask(s, { area: 'quality', what: 'Confirm QV readiness', ref: m.id, check: 'QV evidence package ready; all D3 to D7 items available', go: '#/dd/quality' }); nfy(s, 'dd', `QV package ready: ${m.name}`, `${ME} sent the evidence package`, '#/dd/quality'); aud(s, `Sent QV package to the deputy dean: ${m.name}`, m.id); });

/* ---------- reports ---------- */
MC.reportsScreen('lec', 'reports', 'My micro-credentials');
MC.reportScreen('lec', 'reports', 'My micro-credentials');

/* ---------- flows ---------- */
flow({ id: 'FL-02', title: 'Propose a UMC, fix the SLT, submit', actor: 'Lecturer', summary: 'Category wizard, a blocked draft at 110 h, the fix to 120 h, and submit',
  steps: [{ go: '#/lec/new', say: 'Dr. Aina starts a new proposal. Pick "Modules from a UTeM credit course" and a parent course to see the category and the reason. A 30 h own offering would be an MCE only.' },
    { go: '#/lec/proposal/ST?tab=mods', say: 'Her Software Testing draft has modules of 40 + 38 + 32 = 110 h. The checks panel shows the blocking error and Submit is disabled with the reason beside it.' },
    { go: '#/lec/proposal/ST?tab=mods', say: 'Click "Set module 3 to 42 h" (or type 42). The total becomes 120 h, the error clears, and the panel shows one warning about an outcome with no assessment.' },
    { go: '#/lec/proposal/ST?tab=mods', say: 'Click Submit for review, write a short response to the warning, and confirm. The status becomes Submitted and the content locks.' },
    { go: '#/dd/queue?area=proposals', say: 'Hakim Rahman now has a task "Assign reviewers" for Software Testing.' }] });
flow({ id: 'FL-03', title: 'Review a colleague\'s proposal', actor: 'Lecturer as reviewer', summary: 'Conflict of interest first, then rate, then recommend',
  steps: [{ go: '#/lec/reviews', say: 'Dr. Aina has an invitation to review Cyber Security Operations Fundamentals, due 30 Oct.' },
    { go: '#/lec/review/RV-031', say: 'Step 1: declare a conflict of interest. The proposal stays closed until you do. Choose "I have no conflict of interest" and confirm. Choosing a conflict declines the review instead.' },
    { go: '#/lec/review/RV-031', say: 'Step 2: rate each item Satisfactory or Needs amendment. A Needs amendment rating needs a comment and can be marked blocking.' },
    { go: '#/lec/review/RV-031', say: 'Step 3: choose Support, Amend or Do not support, add a summary, and submit. Submit stays disabled until everything is filled.' }] });
flow({ id: 'FL-15', title: 'Quality review: close an action only after the effectiveness check', actor: 'Lecturer, deputy dean', summary: 'The lecturer view, then submit to the deputy dean',
  steps: [{ go: '#/lec/cqi/CQI-DS-2026-1', say: 'Indicators against targets: outcome attainment is 73% against 75%. Action A3 is Done, but "Close action" is disabled until an effectiveness check exists.' },
    { go: '#/lec/cqi/CQI-DS-2026-1', say: 'Click "Record effectiveness check" on A3, choose a result and add evidence. Then "Close action" becomes available. A2 changes an outcome, so it offers "Create change request".' },
    { go: '#/lec/cqi/CQI-DS-2026-1', say: 'Click Submit to deputy dean and confirm. The record becomes Submitted and a task is pushed.' },
    { go: '#/dd/cqi/CQI-DS-2026-1', say: 'The deputy dean sees the submitted record and confirms it.' }] });
flow({ id: 'FL-20', title: 'Industry expert comments on a proposal', actor: 'Industry expert', summary: 'Conflict of interest, then comment only',
  steps: [{ go: '#/ext/home', say: 'Ir. Siti Aminah Yusof has an invitation to Applied Data Analytics with Python.' },
    { go: '#/ext/review/RV-033', say: 'The proposal is already endorsed and waits for the Senate; comments are still accepted until that decision is recorded. Declare no conflict, then write comments. There is no rating and no recommendation for this role. Send comments and confirm.' },
    { go: '#/lec/proposal/ADA?tab=comments', say: 'The comments reach the proposer on the Comments tab, where she can respond.' }] });
})();


