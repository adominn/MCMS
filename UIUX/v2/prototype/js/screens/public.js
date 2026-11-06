/* public screens: the micro-credential portal anyone can read without signing in (shell 'public'). */
(function () {
const MC = window.MC;
const PUB = { shell: 'public' };
const MI = { Jan: 0, Feb: 1, Mar: 2, Apr: 3, May: 4, Jun: 5, Jul: 6, Aug: 7, Sep: 8, Oct: 9, Nov: 10, Dec: 11 };
const pd = t => { const [d, m, y] = t.split(' '); return new Date(+y, MI[m], +d); };
const mon = t => { const [, m, y] = t.split(' '); return `${m} ${y}`; };
const esc = t => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const H2 = t => `<h2 style="font-weight:300;font-size:28px;line-height:36px">${t}</h2>`;
const ptjName = (s, k) => (s.ptj.find(x => x[0] === k) || [k, k])[1];

/* Text the documents do not store per micro-credential; kept here so the detail page can be complete. */
const META = {
  DS: { mode: 'Online, weekly live session', lang: 'English', prereq: 'Basic programming in any language.', support: 'Weekly live clinic, discussion forum, facilitator replies within 2 working days.',
    outcomes: ['Implement linear data structures and choose between them for a given problem.', 'Apply trees and graphs to search and routing problems.', 'Compare algorithms by time and space cost.', 'Explain the trade-offs behind a data structure choice in writing.'], assess: ['Module quizzes', 'Programming assignment', 'Final test'] },
  CND: { mode: 'Online, project-based', lang: 'English', prereq: 'Working knowledge of Linux command line and one programming language.', support: 'Fortnightly mentoring call, shared lab environment, forum.',
    outcomes: ['Build and run container images for a service.', 'Set up an automated build and release pipeline.', 'Operate a small set of running services and respond to a failed release.'], assess: ['Lab quizzes', 'Pipeline exercise', 'Running-services project'] },
  LA: { mode: 'Online, weekly live session', lang: 'English', prereq: 'School-level algebra.', support: 'Weekly live clinic, discussion forum.',
    outcomes: ['Solve systems of equations using matrices.', 'Describe linear transformations geometrically and algebraically.', 'Compute and interpret eigenvalues and eigenvectors.'], assess: ['Module quizzes', 'Problem sets', 'Final test'] },
  LE: { mode: 'Face to face, 2 days', lang: 'English and Bahasa Melayu', prereq: 'None.', support: 'Facilitator on site; follow-up notes by email.',
    outcomes: ['Lead a small team through a change at work.', 'Hold a coaching conversation with a colleague.'], assess: ['Participation record', 'Reflective task'] },
  IIOT: { mode: 'Hybrid, 4 on-campus Saturdays', lang: 'English', prereq: 'Basic electronics or programming.', support: 'On-campus lab sessions, forum, facilitator replies within 2 working days.',
    outcomes: ['Connect sensors to a gateway and read their data.', 'Move factory-floor data to a store and keep it usable.', 'Build a dashboard that answers a production question.'], assess: ['Module quizzes', 'Lab report', 'Dashboards project'] },
  LSS: { mode: 'Face to face, 2 days', lang: 'English', prereq: 'None.', support: 'Facilitator on site; project coaching by email for 4 weeks.',
    outcomes: ['Describe lean principles and name the waste in a process.', 'Run a small DMAIC project with a facilitator.'], assess: ['Participation record', 'DMAIC project'] },
};
const meta = id => META[id] || { mode: 'Online', lang: 'English', prereq: 'None stated.', support: 'Forum and facilitator.', outcomes: ['See the approved syllabus.'], assess: ['Assessment 1', 'Assessment 2', 'Assessment 3'] };

/* Only approved micro-credentials are public. Credit is shown only when it can be relied on today. */
const approved = (s, id) => { const m = s.mcs.find(x => x.id === id); return m && m.status === 'Approved' ? m : null; };
const credit = (m, co) => {
  if (m.cat === 'MCE') return { t: 'No academic credit', ok: false };
  if (m.cat === 'UMC') return { t: `${m.credits} credits, UTeM course ${m.code}`, ok: true };
  const qs = [m.qv, ...(m.qvHistory || [])].filter(q => q && q.status === 'Quality-Verified' && q.to);
  if (qs.some(q => (!q.from || pd(q.from) <= pd(co.start)) && pd(q.to) >= pd(co.end))) return { t: `${m.credits} credits${m.mqf ? `, MQF level ${m.mqf}` : ''}`, ok: true };
  return qs.length ? { t: 'Credit after QV renewal', ok: false, renew: true } : { t: 'Credit after QV', ok: false };
};
const days = co => Math.round((pd(co.end) - pd(co.start)) / 864e5) + 1;
const length = (co, m) => { const d = days(co); return `${d < 14 ? d + ' days' : Math.round(d / 7) + ' weeks'}, ${m.slt} learning hours`; };
const effort = (co, m) => `${Math.ceil(m.slt / Math.max(1, Math.round(days(co) / 7)))} h/week`;
const CATCOL = { UMC: 'var(--info-bg);color:var(--info-ink)', SAMC: 'var(--ok-bg);color:var(--ok-ink)', MCE: 'var(--warn-bg);color:var(--warn-ink)' };
const left = co => Math.max(0, co.places - co.taken);
const listed = s => s.cohorts.filter(c => /^(Open|Full)$/.test(c.status) && approved(s, c.mc)).map(c => Object.assign({}, c, { m: s.mcs.find(x => x.id === c.mc) }));

/* ---------- catalogue filter state (kept while browsing) ---------- */
const F = { q: '', cat: 'Any', ptj: 'Any', mode: 'Any', lang: 'Any', month: 'Any', fee: 'Any', cr: 'Any', sort: 'Start date', page: 0 };
const FEE = { 'Any': () => true, 'Under RM 500': f => f < 500, 'RM 500 to RM 599': f => f >= 500 && f < 600, 'RM 600 and above': f => f >= 600 };
const MODES = c => { const x = meta(c.mc).mode; return /^Online/.test(x) ? 'Online' : /^Hybrid/.test(x) ? 'Hybrid' : 'Face to face'; };
const SIZE = 5;

function filtered(s) {
  const q = F.q.trim().toLowerCase();
  const rows = listed(s).filter(c => (!q || `${c.m.name} ${c.m.code} ${c.m.cat} ${ptjName(s, c.m.ptj)}`.toLowerCase().includes(q))
    && (F.cat === 'Any' || c.m.cat === F.cat) && (F.ptj === 'Any' || c.m.ptj === F.ptj) && (F.mode === 'Any' || MODES(c) === F.mode)
    && (F.lang === 'Any' || meta(c.mc).lang.includes(F.lang)) && (F.month === 'Any' || mon(c.start) === F.month) && FEE[F.fee](c.fee)
    && (F.cr === 'Any' || (F.cr === 'Yes') === credit(c.m, c).ok));
  const by = { 'Start date': (a, b) => pd(a.start) - pd(b.start), 'Fee, low to high': (a, b) => a.fee - b.fee, 'Name': (a, b) => a.m.name.localeCompare(b.m.name) }[F.sort];
  return rows.sort(by);
}

MC.act('pubfilter', (c, s) => { Object.keys(F).forEach(k => { if (k !== 'page' && c[k] !== undefined) F[k] = c[k]; }); F.page = 0; MC.go('#/public/catalogue'); return false; });
MC.act('pubclear', () => { Object.assign(F, { q: '', cat: 'Any', ptj: 'Any', mode: 'Any', lang: 'Any', month: 'Any', fee: 'Any', cr: 'Any', sort: 'Start date', page: 0 }); MC.go('#/public/catalogue'); return false; });
MC.act('pubpage', c => { F.page += +c.d; MC._keepScroll = true; });
MC.act('pubhome', c => { MC.go('#/public/catalogue?q=' + encodeURIComponent(c.q || '')); return false; });
MC.act('pubverify', c => { const v = String(c.code || '').trim(); if (!v) { MC.toast('Enter a code', 'Type the code printed on the credential, for example 3HXP-92LD.', 'err'); return false; } MC.go('#/public/verify/' + encodeURIComponent(v.split('/').pop().toUpperCase())); return false; });
const OPEN = new Set();
MC.act('faqtoggle', c => { OPEN.has(c.i) ? OPEN.delete(c.i) : OPEN.add(c.i); MC._keepScroll = true; });

/* ---------- shared bits ---------- */
const offerRow = (s, c) => {
  const cr = credit(c.m, c), full = c.status === 'Full';
  return `<div class="card" data-go="#/public/offering/${c.id}" style="cursor:pointer;flex-direction:row;gap:24px;align-items:flex-start">
    <div style="flex:1"><div>${MC.nm(c.m.cat, c.m.name)}</div><div class="small muted" style="margin-top:4px">${ptjName(s, c.m.ptj)} · ${meta(c.mc).mode} · ${meta(c.mc).lang}</div>
      <div style="margin-top:8px">${cr.ok ? MC.tag(cr.t, 'ok') : `<span class="muted">${cr.t}</span>`}</div></div>
    <div class="small" style="width:210px">${length(c, c.m)}<br>Starts ${c.start}</div>
    <div class="small" style="width:150px"><b style="font-size:16px;font-weight:400">${MC.rm(c.fee)}</b><br>${full ? MC.tag('Full', 'warn') : `${left(c)} of ${c.places} places left`}</div></div>`;
};
const link = (t, h) => `<a data-go="${h}">${t}</a>`;

/* ---------- public/home ---------- */
MC.screen('public/home', Object.assign({ title: 'Micro-credentials at UTeM', nav: 'home', render(p, s) {
  const open = listed(s).filter(c => c.status === 'Open').sort((a, b) => pd(a.start) - pd(b.start)), feat = open.slice(0, 3), shown = open.slice(3, 8);
  const facs = [...new Set(listed(s).map(c => c.m.ptj))];
  return `<div class="pub-hero"><div style="flex:1"><div class="small muted">Universiti Teknikal Malaysia Melaka · CODL</div><h1>Short, assessed learning that may be considered towards a degree</h1>
      <p style="max-width:560px;margin-top:12px">Take a micro-credential at UTeM, earn a verifiable credential, and where the programme carries credit, ask for that credit to be recognised later.</p></div>
    <div data-form style="width:400px" class="col">${MC.field('Search the catalogue', MC.inp('q', '', 'Topic, name or faculty'))}
      <div class="row" style="gap:8px"><button class="btn" data-act="pubhome">Search</button><span class="btn ter" data-go="#/public/catalogue">Browse all ${listed(s).length} offerings</span></div></div></div>

    ${feat.length ? `<div class="pub-sec">${H2('Featured offerings')}
      <div class="grid3">${feat.map(c => `<div class="card" data-go="#/public/offering/${c.id}" style="cursor:pointer;padding:0;gap:0;overflow:hidden"><div style="background:${CATCOL[c.m.cat]};padding:24px;min-height:130px;display:flex;align-items:flex-end;font-size:24px;line-height:30px;font-weight:300">${c.m.name}</div>
        <div class="col" style="padding:16px;gap:8px"><div class="small muted">${MC.CATNAME[c.m.cat]} · ${ptjName(s, c.m.ptj)}</div><div class="small">Starts ${c.start} · ${length(c, c.m)}<br>Effort: ${effort(c, c.m)}</div>
        <div class="row" style="align-items:center"><b style="font-weight:400;font-size:16px">${MC.rm(c.fee)}</b><span class="small muted" style="margin-left:auto">${left(c)} of ${c.places} places left</span></div></div></div>`).join('')}</div></div>` : ''}

    <div class="pub-sec">${H2('Three kinds of micro-credential')}
      <div class="grid3"><div class="card">${MC.cat('UMC')}<h3>${MC.CATNAME.UMC}</h3><p>One course of a UTeM programme, taught on its own. You earn the course credit (for example 3 credits). You may later apply to have it recognised; each transfer is decided case by case.</p><div class="small muted">Credit: yes, through ACR</div></div>
      <div class="card">${MC.cat('SAMC')}<h3>${MC.CATNAME.SAMC}</h3><p>A short course designed with industry. Credit applies only while its quality verification (QV) is valid for the cohort dates; each offering says so.</p><div class="small muted">Credit: only with valid QV</div></div>
      <div class="card">${MC.cat('MCE')}<h3>${MC.CATNAME.MCE}</h3><p>Skills for work and personal growth. You receive a certificate of completion. There is no academic credit and no ACR.</p><div class="small muted">Credit: none</div></div></div></div>

    <div class="pub-sec alt"><div class="row" style="align-items:flex-end">${H2(`${open.length} offerings open for registration`)}<span style="margin-left:auto">${link('See the full catalogue', '#/public/catalogue')}</span></div>
      <div class="col" style="gap:12px">${shown.map(c => offerRow(s, c)).join('') || '<div class="muted">The featured offerings above are all that is open today.</div>'}</div>
      <div class="small muted">Offerings are listed with the places left today. Full cohorts stay listed so you can see the next one.</div></div>

    <div class="pub-sec">${H2('Faculties offering micro-credentials')}
      <div class="grid4">${facs.map(k => `<div class="card" data-go="#/public/catalogue" style="cursor:pointer"><b>${k}</b><span class="small muted">${ptjName(s, k)}</span><span>${listed(s).filter(c => c.m.ptj === k).length} offerings listed</span></div>`).join('')}</div></div>

    <div class="pub-sec alt">${H2('How recognition works')}<p style="max-width:720px">Each step is a separate decision with its own record. Finishing one does not promise the next.</p>
      ${MC.track([['', 'Module badge', 'Awarded for each module you pass'], ['', 'Certificate or credential', 'Issued when you complete and results are verified'], ['', 'ACR', 'Academic credit record, only for credit-bearing programmes'], ['', 'ABC deposit', 'Your credit is stored nationally, with your consent'], ['', 'Credit transfer', 'A university decides, case by case']])}
      <div>${link('Read each step in detail', '#/public/how')}</div></div>

    <div class="pub-sec"><div class="grid3">
      <div class="card"><h3>Pathways to a degree</h3><p>See which micro-credentials map to courses in a UTeM programme, and the rules that apply. A degree is never automatic.</p>${link('View pathways', '#/public/pathways')}</div>
      <div class="card"><h3>Verify a credential</h3><p>Employers and institutions can check a credential with the code printed on it. No sign-in needed.</p>${link('Verify a credential', '#/public/verify')}</div>
      <div class="card"><h3>Questions</h3><p>Badge or certificate, ACR, ABC, fees, external learners and more.</p>${link('Read the FAQ', '#/public/faq')}</div></div>
      <div class="small muted">UTeM staff: ${link('sign in to MCMS', '#/signin')}</div></div>`;
} }, PUB));

/* ---------- public/catalogue ---------- */
MC.screen('public/catalogue', Object.assign({ title: 'Catalogue', nav: 'catalogue', ex: '', render(p, s) {
  if (p.q !== undefined) { F.q = p.q; F.page = 0; }
  const all = listed(s), rows = filtered(s), pages = Math.max(1, Math.ceil(rows.length / SIZE)); F.page = Math.min(F.page, pages - 1);
  const slice = rows.slice(F.page * SIZE, F.page * SIZE + SIZE);
  const opts = (name, list) => MC.sel(name, ['Any', ...list], F[name]);
  const months = [...new Set(all.map(c => mon(c.start)))];
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['Catalogue']], title: 'Micro-credential catalogue', sub: 'Approved micro-credentials open or full for registration. Credit is shown only where it can be relied on.' })}
    <div class="row" style="gap:32px"><div data-form class="col" style="width:280px;gap:8px">
      ${MC.field('Search', MC.inp('q', esc(F.q), 'Name, code or faculty'))}
      ${MC.field('Category', opts('cat', ['UMC', 'SAMC', 'MCE']))}${MC.field('Faculty', MC.sel('ptj', ['Any', ...new Set(all.map(c => c.m.ptj))], F.ptj))}
      ${MC.field('Mode', opts('mode', ['Online', 'Hybrid', 'Face to face']))}${MC.field('Language', opts('lang', ['English', 'Bahasa Melayu']))}
      ${MC.field('Start month', opts('month', months))}${MC.field('Fee', MC.sel('fee', Object.keys(FEE), F.fee))}${MC.field('Carries credit today', opts('cr', ['Yes', 'No']))}
      ${MC.field('Sort by', MC.sel('sort', ['Start date', 'Fee, low to high', 'Name'], F.sort))}
      <div class="row" style="gap:8px"><button class="btn" data-act="pubfilter">Apply</button><button class="btn ter" data-act="pubclear">Clear filters</button></div></div>
    <div class="col grow" style="gap:12px"><div class="small muted">${rows.length} of ${all.length} offerings${F.q ? ` for "${esc(F.q)}"` : ''}</div>
      ${slice.map(c => offerRow(s, c)).join('') || MC.note('No offering matches these filters. Clear a filter or search a different word.')}
      <div class="pager"><span>${rows.length ? F.page * SIZE + 1 : 0}–${Math.min(rows.length, (F.page + 1) * SIZE)} of ${rows.length}</span><span class="sp">Page ${F.page + 1} of ${pages}</span>
        <button class="pb" data-act="pubpage" data-d="-1" ${F.page ? '' : 'disabled'} aria-label="Previous page">${MC.icon('left')}</button><button class="pb" data-act="pubpage" data-d="1" ${F.page >= pages - 1 ? 'disabled' : ''} aria-label="Next page">${MC.icon('right')}</button></div></div></div></div>`;
} }, PUB));

/* ---------- public/offering/:id ---------- */
const STAGES = `<b>Completion, ACR, ABC deposit and credit transfer are separate decisions.</b> Completing this offering does not guarantee an ACR, a deposit in ABC, or that any university will accept the credit. Each is decided on its own, and you can stop at any step.`;
MC.screen('public/offering/:id', Object.assign({ title: 'Offering', nav: 'catalogue', ex: 'CND-2027-1', render(p, s) {
  const c = s.cohorts.find(x => x.id === p.id), m = c && s.mcs.find(x => x.id === c.mc);
  if (!c || !m) return `<div class="pub-sec">${MC.note('We could not find this offering. It may have been withdrawn.', 'err')}${link('Back to the catalogue', '#/public/catalogue')}</div>`;
  const me = meta(m.id), cr = credit(m, c), isOpen = c.status === 'Open' && !!approved(s, m.id);
  const why = isOpen ? '' : /^(Suspended|Terminated)$/.test(m.status) ? `Registration is closed: this micro-credential is ${m.status.toLowerCase()}.` : c.status === 'Full' ? 'This cohort is full. Choose another cohort below.' : c.status === 'Ended' || c.status === 'Running' ? 'This cohort has already started. Choose a later cohort below.' : !approved(s, m.id) ? 'This micro-credential is not yet approved.' : 'Registration is not open yet.';
  const others = s.cohorts.filter(x => x.mc === m.id && x.id !== c.id && /^(Open|Full)$/.test(x.status));
  const cred = m.cat === 'MCE' ? 'Certificate of completion. No ACR and no academic credit.' : m.cat === 'UMC' ? `Module badges, then a micro-credential certificate on completion. You may later receive an ACR for ${m.credits} credits (UTeM course ${m.code}).` : `Module badges, then a micro-credential certificate on completion. ${cr.ok ? `You may later receive an ACR for ${m.credits} credits${m.mqf ? ` at MQF level ${m.mqf}` : ''}.` : cr.renew ? 'Credit applies only if the QV is renewed before your cohort ends; otherwise you receive the certificate only.' : 'This micro-credential is not yet quality-verified (QV), so no academic credit is available; you receive the certificate only.'}`;
  const stmt = m.cat === 'MCE' ? `On completing ${m.name} (${c.name}) you will receive a certificate of completion from Universiti Teknikal Malaysia Melaka. This micro-credential carries no academic credit.`
    : `On completing ${m.name} (${c.name}) and passing the assessment at 50% or above, you will receive a micro-credential certificate from Universiti Teknikal Malaysia Melaka. ${cr.ok ? `The micro-credential is worth ${m.credits} credits (${m.slt} learning hours) and may be recorded in an ACR.` : cr.renew ? 'Academic credit is not available for this cohort unless its QV is renewed.' : 'Academic credit is not available because this micro-credential has no quality verification (QV) yet.'} Recognition by another university is that university's decision.`;
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['Catalogue', '#/public/catalogue'], [m.name]], title: m.name, sub: `${MC.cat(m.cat)} ${ptjName(s, m.ptj)} · ${c.name}` })}
    <div class="row" style="gap:32px"><div class="col grow" style="gap:20px" data-tabs>
      ${MC.tabs([['ov', 'Overview'], ['sy', 'Syllabus'], ['as', 'Assessment'], ['pr', 'Prerequisites'], ['rc', 'Recognition'], ['fq', 'FAQ']], 'ov')}
      <div data-pane="ov" class="col" style="gap:20px">${MC.note(STAGES, 'info')}
      <div>${H2('What you will be able to do')}<ul>${me.outcomes.map(o => `<li>${o}</li>`).join('')}</ul></div>
      ${others.length ? `<div>${H2('Other cohorts')}${others.map(o => `<div class="check"><div class="grow">${link(`${o.name}: starts ${o.start}`, '#/public/offering/' + o.id)}</div>${MC.tag(o.status, o.status === 'Full' ? 'warn' : 'ok')}</div>`).join('')}</div>` : ''}</div>
      <div data-pane="sy"><div>${H2('Modules and learning time')}<table><thead><tr><th>Module</th><th class="num">Learning hours</th></tr></thead><tbody>${m.modules.map((x, i) => `<tr><td>${i + 1}. ${x[0]}</td><td class="num">${x[1]}</td></tr>`).join('')}<tr><td><b>Total</b></td><td class="num"><b>${m.modules.reduce((a, x) => a + x[1], 0)}</b></td></tr></tbody></table>
        <div class="small muted">Each module earns its own badge when you pass it.</div></div></div>
      <div data-pane="as">${H2('Assessment')}<p>${me.assess.map((a, i) => `${a} ${m.weights[i]}%`).join(', ')}. Pass standard: 50% overall.</p></div>
      <div data-pane="pr">${H2('Before you register')}${MC.dl([['Prerequisites', me.prereq], ['Completion conditions', `Pass every module and reach 50% overall. Results are verified by the deputy dean before any credential is issued.`], ['Learning support', me.support], ['Language', me.lang]])}</div>
      <div data-pane="rc">${H2('What you receive')}<p>${cred}</p>
        <div class="panel"><div class="hd"><h2>Recognition statement</h2></div><div class="bd">${stmt}</div></div></div>
      <div data-pane="fq">${H2('Common questions')}${[['Can I get a refund?', 'The refund policy is still to be confirmed; do not rely on a refund until it is published.'], ['Do I need to be a UTeM student?', 'No. External learners first register at Pejabat Pendaftar or HEPA to get a learner ID.'], ['Will I get academic credit?', cr.ok ? `Yes, ${cr.t}, recorded in an ACR after completion. Using it elsewhere is that university's decision.` : m.cat === 'MCE' ? 'No. You receive a certificate of completion only.' : 'Not for this cohort at present; see the Recognition tab.'], ['Where are more answers?', `${link('Read the full FAQ', '#/public/faq')}.`]].map(([q, a]) => `<div class="check" style="display:block"><b style="font-weight:400">${q}</b><p style="margin-top:4px;max-width:760px">${a}</p></div>`).join('')}</div></div>
    <div class="card" style="width:320px;position:sticky;top:72px;gap:12px"><b style="font-size:20px;font-weight:300">${MC.rm(c.fee || 0)}</b>
      ${MC.dl([['Institution', 'Universiti Teknikal Malaysia Melaka'], ['Format', me.mode], ['Effort', effort(c, m)], ['Starts', c.start], ['Ends', c.end], ['Duration', length(c, m)], ['Cost', MC.rm(c.fee || 0)], ['Credit', cr.t], ['Places', c.places ? `${left(c)} left of ${c.places}` : 'Not applicable']])}
      ${isOpen ? `<span class="btn" data-go="#/st/register/${c.id}">Enrol</span>` : `<span class="btn dis">Registration closed</span><div class="small muted">${why}</div>`}
      <div class="small muted">Register opens after you sign in. External learners register first at Pejabat Pendaftar or HEPA to get a learner ID. Fee refunds are to be confirmed.</div></div></div></div>`;
} }, PUB));

/* ---------- public/pathways ---------- */
MC.screen('public/pathways', Object.assign({ title: 'Pathways to a degree', nav: 'pathways', render(p, s) {
  const pw = s.pathways.find(x => x.programme === 'BITZ' && x.status === 'Published'), prog = s.programmes.find(x => x.code === 'BITZ');
  if (!pw) return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['Pathways to a degree']], title: 'Pathways to a degree', sub: 'Micro-credentials that map to courses of a UTeM programme' })}${MC.note('No pathway is published yet. Pathways appear here after Senate approval.', 'info')}</div>`;
  const items = pw.maps.filter(([id]) => approved(s, id)).map(([id, course, cr]) => ({ m: s.mcs.find(x => x.id === id), course, cr }));
  const total = items.reduce((a, x) => a + x.cr, 0), min = Math.ceil(prog.credits * .3);
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['Pathways to a degree']], title: 'Pathways to a degree', sub: 'Micro-credentials that map to courses of a UTeM programme' })}
    ${MC.note('A pathway shows what may be asked for. It is not an offer of admission, exemption or a degree. Each transfer is decided separately by the faculty.', 'info')}
    <div class="panel"><div class="hd"><h2>${prog.name} (${prog.code})</h2><span class="r">${ptjName(s, prog.ptj)} · MQF level ${prog.mqf} · ${prog.credits} credits</span></div>
      <table><thead><tr><th>Micro-credential</th><th>Maps to course</th><th class="num">Credits</th><th>Offering</th></tr></thead><tbody>${items.map(x => {
        const o = s.cohorts.find(c => c.mc === x.m.id && c.status === 'Open');
        return `<tr><td>${MC.nm(x.m.cat, x.m.name)}</td><td>${x.course}</td><td class="num">${x.cr}</td><td>${o ? link('Next: ' + o.start, '#/public/offering/' + o.id) : '<span class="muted">No open cohort</span>'}</td></tr>`; }).join('')}
        <tr><td colspan="2"><b>Credits that could be mapped</b></td><td class="num"><b>${total}</b></td><td></td></tr></tbody></table></div>
    <div class="small muted">Only approved micro-credentials appear here. A stand-alone micro-credential counts only while its QV is valid for the cohort you took.</div>
    <div class="pub-sec alt" style="padding:24px"><div class="col" style="gap:8px">${H2('The rules')}
      ${MC.checks([['ok', `At least 30% of the degree credits must be earned at UTeM`, `For ${prog.code} that is ${min} of ${prog.credits} credits; transferred credit cannot replace them.`], ['ok', 'Residency applies', 'Conventional programmes require at least 1 semester of study at UTeM.'],
        ['ok', 'Admission is separate', 'Credit transfer needs an admitted student. Admission is decided by Pejabat Pendaftar.'], ['ok', 'Credit is valid for 10 years', 'From the date it is deposited in ABC.'],
        ['ok', 'No automatic degree', 'Collecting micro-credentials never awards a degree. You complete the programme requirements.']])}</div></div></div>`;
} }, PUB));

/* ---------- public/how ---------- */
MC.screen('public/how', Object.assign({ title: 'How recognition works', nav: 'how', render(p, s) {
  const st = [['Module badge', 'A badge for each module you pass, issued after the deputy dean verifies results.', 'It is not a qualification and carries no credit by itself.'],
    ['Certificate or credential', 'A signed, verifiable record that you completed the whole micro-credential. Anyone can check it with its code.', 'It is not proof that credit has been recorded or accepted anywhere.'],
    ['ACR', 'The academic credit record: UTeM confirms the credit you earned. Only UMC and quality-verified SAMC offerings have one.', 'It is not a transfer, and an MCE never has one.'],
    ['ABC deposit', 'With your consent, the credit is deposited in the national Academic Bank of Credit and stays valid for 10 years.', 'You may decline or postpone. Deposit is not an admission or an exemption.'],
    ['Credit transfer', 'You apply to an admitted programme; the faculty compares outcomes and decides course by course.', 'It is not guaranteed, and the credit can be used only once.']];
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['How it works']], title: 'How recognition works', sub: 'Five stages. Each one is decided separately and recorded.' })}
    ${MC.track(st.map(x => ['', x[0], '']))}
    ${st.map((x, i) => `<div class="card"><b>${i + 1}. ${x[0]}</b><div><b>What it is.</b> ${x[1]}</div><div class="muted"><b>What it is not.</b> ${x[2]}</div></div>`).join('')}
    ${MC.note(STAGES, 'info')}
    <div class="row" style="gap:8px"><span class="btn" data-go="#/public/catalogue">Find a micro-credential</span><span class="btn ter" data-go="#/public/pathways">Pathways to a degree</span></div></div>`;
} }, PUB));

/* ---------- public/faq ---------- */
const FAQ = [['Badges and credentials', [
  ['What is the difference between a badge and a certificate?', 'A badge is awarded for each module you pass. The certificate, called a credential in the system, is issued when you complete the whole micro-credential and results are verified.'],
  ['Does completing a micro-credential give me academic credit?', 'Only a UMC and a SAMC with valid quality verification (QV) can lead to credit. An MCE gives a certificate and no credit.'],
  ['What is QV and why does it matter?', 'Quality verification confirms a stand-alone micro-credential meets national standards. If the QV has lapsed for your cohort, the offering says "Credit after QV renewal" and you receive the certificate only.']]],
 ['Credit', [
  ['What is an ACR?', 'An academic credit record. UTeM issues one after it confirms you achieved the credit-bearing micro-credential. It is a record, not a transfer.'],
  ['What is ABC and do I have to deposit?', 'The Academic Bank of Credit stores your credit nationally for 10 years. Deposit needs your consent; you can give, decline or postpone it.'],
  ['Can I use my credit in a degree?', 'You may apply for credit transfer into an admitted programme. The faculty decides course by course, at least 30% of the degree credits must be earned at UTeM, and the same credit cannot be redeemed twice.'],
  ['Will collecting micro-credentials give me a degree?', 'No. Pathways show what may be mapped. You still complete the programme requirements, including residency.']]],
 ['Registering and paying', [
  ['How much does it cost and what about refunds?', 'The fee is shown on each offering. The refund policy is still to be confirmed; do not rely on a refund until it is published.'],
  ['I am not a UTeM student. Can I register?', 'Yes. External learners first register at Pejabat Pendaftar or HEPA to receive a learner ID, then sign in and register for a cohort.'],
  ['Which language is the course taught in?', 'Each offering lists its language. The portal and credentials are available in English and Bahasa Melayu.']]],
 ['Verifying and your data', [
  ['How do I check someone\'s credential?', 'Use "Verify a credential" and enter the code printed on it, or scan its QR code. The page shows name, credential, issuer, date and status only.'],
  ['What happens if a credential is corrected or revoked?', 'A corrected credential stays valid and shows the correction date. A revoked credential shows as revoked with its date. Replaced credentials point to the new one.'],
  ['Who sees my data, and do I have to consent to ABC?', 'Public verification shows only the fields above, never results or credit. Deposit in ABC happens only if you consent, and you may withdraw consent before deposit.']]]];
MC.screen('public/faq', Object.assign({ title: 'FAQ', nav: 'faq', render(p, s) {
  let n = -1;
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['FAQ']], title: 'Frequently asked questions', sub: `${FAQ.reduce((a, g) => a + g[1].length, 0)} answers about credentials, credit and registration` })}
    ${FAQ.map(([g, qs]) => `<div>${H2(g)}${qs.map(([q, a]) => { n++; const on = OPEN.has(String(n));
      return `<div class="check" style="display:block"><div data-act="faqtoggle" data-i="${n}" style="cursor:pointer;display:flex;gap:8px" role="button" aria-expanded="${on}"><b class="grow" style="font-weight:400">${q}</b>${MC.icon(on ? 'x' : 'plus', 16)}</div>${on ? `<p style="margin-top:8px;max-width:760px">${a}</p>` : ''}</div>`; }).join('')}</div>`).join('')}
    <div class="small muted">Not answered here? Write to codl@utem.edu.my.</div></div>`;
} }, PUB));

/* ---------- public/verify ---------- */
const verifyForm = v => `<div data-form class="col" style="max-width:520px">${MC.field('Credential code or link', MC.inp('code', esc(v || ''), 'For example 3HXP-92LD'), 'The code is printed under the QR code on the credential.')}
  <div class="row" style="gap:8px"><button class="btn" data-act="pubverify">Verify</button><span class="btn dis">Upload signed file: Release 2</span></div></div>`;
MC.screen('public/verify', Object.assign({ title: 'Verify a credential', nav: 'verify', render() {
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['Verify a credential']], title: 'Verify a credential', sub: 'For employers and institutions. No sign-in needed.' })}
    ${verifyForm('')}${MC.note('Scanning the QR code on a credential opens this check with the code filled in. The page shows only the holder name, credential, issuer, issue date and status. It never shows results or credit.', 'info')}
    <div class="small muted">Try a sample: ${['3HXP-92LD', '7Q2M-K4PA', 'R7KD-1MQA', 'ZZZZ-0000'].map(c => link(c, '#/public/verify/' + c)).join(' · ')}</div></div>`;
} }, PUB));

MC.screen('public/verify/:code', Object.assign({ title: 'Verification result', nav: 'verify', ex: '3HXP-92LD', render(p, s) {
  const code = String(p.code).trim().toUpperCase(), c = s.credentials.find(x => x.code === code);
  let body;
  if (!c) body = MC.note(`<b>We could not find a credential with the code ${esc(code)}.</b> Check the code for typing errors. If it is correct, ask the holder to confirm it with UTeM. This does not mean the credential is false.`);
  else {
    const m = s.mcs.find(x => x.id === c.mc), co = s.cohorts.find(x => x.id === c.cohort);
    const st = c.status === 'Revoked' ? ['Revoked', 'err'] : c.status === 'Replaced' ? ['Replaced', 'warn'] : c.status === 'Corrected' ? ['Corrected, valid', 'ok'] : ['Valid', 'ok'];
    const nw = c.replacedBy && s.credentials.find(x => x.id === c.replacedBy);
    const rows = [['Name', c.who], ['Credential', m ? m.name : c.mc], ['Issuer', 'Universiti Teknikal Malaysia Melaka (CODL)'], ['Issued', c.issued], ['Status', MC.tag(st[0], st[1])]];
    if (c.status === 'Revoked') rows.push(['Revoked on', c.revoked]);
    if (c.status === 'Corrected') rows.push(['Corrected on', c.correctedOn || c.corrected || s.today], ['Current revision', c.revision]);
    body = `${c.status === 'Revoked' ? MC.note(`This credential was revoked on ${c.revoked}. It must not be relied on.`, 'err')
      : c.status === 'Replaced' ? MC.note(`This credential was replaced by a newer one${nw ? `: ${link(nw.code, '#/public/verify/' + nw.code)}` : ''}. Verify the new credential instead.`, 'warn')
      : c.status === 'Corrected' ? MC.note(`Valid. It was corrected after issue; the details below are the current revision.`, 'ok') : MC.note('Valid. This credential was issued by UTeM and has not been changed or revoked.', 'ok')}
      ${MC.dl(rows)}<div class="small muted">Signature check: the digital signature matches UTeM's signing key (prototype test key).</div>`;
  }
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], ['Verify a credential', '#/public/verify'], [esc(code)]], title: c ? `Credential ${esc(code)}` : 'No match', sub: 'Verification result' })}
    <div style="max-width:640px" class="col">${body}<div class="small muted">Only name, credential, issuer, issue date and status are shown. Results and credit are never public.</div></div>
    <div class="pub-sec alt" style="padding:24px">${H2('Check another credential')}${verifyForm('')}</div></div>`;
} }, PUB));

/* ---------- policy pages (linked from the public footer) ---------- */
const policy = (route, title, sub, paras) => MC.screen(route, Object.assign({ title, nav: '', render() {
  return `<div class="pub-sec">${MC.head({ crumb: [['Home', '#/public/home'], [title]], title, sub })}<div class="col" style="max-width:720px;gap:12px">${paras.map(([h, t]) => `<div><b>${h}</b><p>${t}</p></div>`).join('')}</div></div>`;
} }, PUB));
policy('public/privacy', 'Privacy notice', 'Summary of how UTeM handles personal data in MCMS (Personal Data Protection Act 2010)', [
  ['What we collect', 'Your name, contact details, learner ID, enrolment, results and the credentials issued to you.'],
  ['Why', 'To run your enrolment, assess and verify results, issue credentials and, only with your consent, deposit credit in the Academic Bank of Credit.'],
  ['Who sees it', 'Staff see only what their role needs. Public verification shows name, credential, issuer, issue date and status; never results or credit.'],
  ['Your choices', 'You may ask to see or correct your data, and you may decline or postpone an ABC deposit. Write to codl@utem.edu.my.'],
  ['Prototype', 'This is a prototype with sample data; no real personal data is processed.']]);
policy('public/terms', 'Terms of use', 'Terms for this prototype', [
  ['Prototype only', 'MCMS here is a clickable prototype. Fees, payments, credentials and records are samples and have no legal effect.'],
  ['Credentials', 'A real credential is valid only when it passes verification on the live portal.'],
  ['Recognition', 'Completing an offering does not guarantee an ACR, an ABC deposit or credit transfer. Each is decided separately.'],
  ['Refunds', 'The refund policy is still to be confirmed.']]);
policy('public/accessibility', 'Accessibility statement', 'We aim to meet WCAG 2.1 level AA', [
  ['Our aim', 'Every page should work with a keyboard, a screen reader and at 200% zoom, with text contrast of at least 4.5:1.'],
  ['Known gaps', 'This prototype has not had a full audit with assistive technology. Some gaps may remain.'],
  ['Tell us', 'If you cannot use a page, write to codl@utem.edu.my and we will give you the information another way.']]);

/* ---------- flows ---------- */
MC.flow({ id: 'FL-22', title: 'A visitor explores the public site', actor: 'Public visitor', summary: 'From the home page to a filtered catalogue, an offering, how recognition works and pathways',
  steps: [{ go: '#/public/home', say: 'A visitor lands on the portal. The three categories and open offerings are explained before anything else. Click Next step.' },
    { go: '#/public/catalogue?q=cloud', say: 'The search from the home page opens the catalogue filtered to "cloud". Use the filters on the left to narrow further.' },
    { go: '#/public/offering/CND-2027-1', say: 'The offering shows modules, assessment and the credential received. Note "Credit after QV renewal" and the separate-stages notice.' },
    { go: '#/public/how', say: 'Each recognition stage says what it is and what it is not.' },
    { go: '#/public/pathways', say: 'Pathways show only approved micro-credentials, with the rules and the no-automatic-degree wording.' }] });
MC.flow({ id: 'FL-23', title: 'An employer verifies a credential', actor: 'Tan Mei Ling, HR manager (public)', summary: 'Valid, corrected, revoked and not-found results',
  steps: [{ go: '#/public/verify', say: 'Tan has a candidate\'s credential and opens the verification page. No sign-in is needed.' },
    { go: '#/public/verify/3HXP-92LD', say: 'The credential is valid. Only name, credential, issuer, date and status are shown.' },
    { go: '#/public/verify/7Q2M-K4PA', say: 'A credential that was corrected (once the deputy dean corrects it) stays valid and shows the correction date.' },
    { go: '#/dd/cred/MCMS-C-2026-000418', say: 'Before you continue, replace credential 7Q2M-K4PA here as the deputy dean (use the replace action and confirm). Then click Next step.' },
    { go: '#/public/verify/7Q2M-K4PA', say: 'Once replaced, the old code shows "Replaced" and links to the new credential. Employers should verify the new code instead. If you did not replace it, the page still shows the earlier result.' },
    { go: '#/public/verify/R7KD-1MQA', say: 'A revoked credential shows the revocation date and must not be relied on.' },
    { go: '#/public/verify/ZZZZ-0000', say: 'An unknown code gives a neutral not-found message, not an accusation.' }] });
})();
