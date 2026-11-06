/* Student (P8): Amir Hakimi, external learner X2026-0193. Web first; v1's clarity (progress bars, recognition track, plain words) on desktop screens.
   Routes: st/home, catalogue, register/:cohort, enrolments, enrolment/:id, record, credentials, credential/:id, ledger, acr/:id, transfer, transfer/new, transfer/:id, plan. */
(function () {
const { screen, modal, act, flow, icon, head, panel, table, tag, field, inp, sel, area, note, checks, dl, kpis, track } = MC;
const ME = 'amir', TODAY = '26 Oct 2026';
const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const D = s => new Date(s);
const dstr = d => `${d.getDate()} ${MON[d.getMonth()]} ${d.getFullYear()}`;
const L = (h, t) => `<a data-go="${h}">${t}</a>`;
const bar = pct => `<div class="bar" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" aria-label="${pct} percent"><i style="width:${pct}%"></i></div>`;
const MODE = { DS: 'Online', CND: 'Online', LA: 'Online', LE: 'Face to face', IIOT: 'Blended', LSS: 'Face to face' };
const NEXT = { 'IIOT-2026-2': 'Module 2 quiz, due 2 Nov 2026' };
const CREDK = { 'Available': 'ok', 'Provisionally redeemed': 'info', 'Redeemed': '', 'Expired': 'err' };
const crTag = c => c ? tag(c, CREDK[c]) : tag('Not deposited yet', '');
const TRK = { 'Submitted': 'info', 'Under evaluation': 'info', 'Approved': 'ok', 'Partly approved': 'warn', 'Not approved': 'err', 'More evidence needed': 'warn' };
const mine = s => s.enrolments.filter(e => e.who === ME);
const myAcrs = s => s.acrs.filter(a => a.owner === ME);
const myCreds = s => s.credentials.filter(c => c.owner === ME);
const cname = e => { const c = MC.co(e.cohort); return c ? `${MC.mcNm(c.mc)}<span class="s">${c.name}</span>` : e.cohort; };
const activeTr = (s, acr) => s.transfers.find(t => t.who === ME && /^(Submitted|Under evaluation|More evidence needed)$/.test(t.status) && t.lines.some(l => l.acr === acr));
const slot = (n, w = 4) => String(n).padStart(w, '0');
const log = (s, who, what, ref) => s.audit.unshift([`${TODAY}, 10:20`, who, what, ref]);
const notif = (s, role, t, sub, go) => (s.notifs[role] = s.notifs[role] || []).unshift({ t, s: sub, when: '26 Oct', go, unread: true });

/* Credit wording follows the verified record only: a SAMC shows credit while any QV period (current or earlier) covers the cohort dates. */
const qvList = m => [m.qv, ...(m.qvHistory || [])].filter(q => q && q.status === 'Quality-Verified' && q.to);
const qvState = (m, c) => qvList(m).some(q => D(c.end) <= D(q.to) && (!q.from || D(c.start) >= D(q.from))) ? 'ok' : qvList(m).length ? 'renew' : 'none';
function creditOf(m, c) {
  if (m.cat === 'MCE') return 'No credit. Certificate only';
  if (m.cat === 'UMC') return `${m.credits} credits`;
  const k = qvState(m, c);
  return k === 'ok' ? `${m.credits} credits${m.mqf ? `, MQF level ${m.mqf}` : ''}` : k === 'renew' ? 'QV renewal pending: credit shown once QV is renewed' : 'Credit after QV';
}
const CAT = k => `${MC.CATNAME[k]} (${k})`;
const recog = (m, c) => { const k = m.cat === 'SAMC' ? qvState(m, c) : 'ok', last = qvList(m).map(q => q.to).pop();
  if (m.cat === 'MCE') return `${m.name} is a ${CAT('MCE')}. It earns a certificate and badges. It carries no academic credit, so no Academic Credit Record is made and it cannot be used for credit transfer.`;
  return `${m.name} is a ${CAT(m.cat)}. ${k === 'renew' ? `The Quality Verification (QV) for this SAMC ends on ${last}, before this cohort ends. Credit and MQF level are shown only while QV covers the cohort dates, so this offering is not described as credit-bearing until QV is renewed. If QV is not renewed in time you still receive a certificate and badges, but no Academic Credit Record.`
    : k === 'none' ? 'This SAMC has not been Quality-Verified yet. Credit and MQF level are shown only once QV is complete and covers the cohort dates. Until then you would receive a certificate and badges, but no Academic Credit Record.'
    : `Completing all modules earns ${m.credits} credits, a certificate and a Digital Credential, and an Academic Credit Record (ACR) you may choose to deposit in ABC.`} Deposit in ABC is storage, not a transfer. Every credit transfer is decided case by case by the receiving faculty.`; };
/* waiting label comes from the cohort, not the stored status */
const stat = e => /waiting/.test(e.status) ? ((MC.co(e.cohort) || {}).results !== 'Verified' ? 'Waiting for result verification' : 'Waiting for completion to be confirmed') : e.status;
const gone = e => /^(Withdrawn|Cancelled)/.test(e.status);
const paidTxt = e => e.paid ? (e.amount ? `${MC.rm(e.amount)}, ${e.paidOn}` : e.paid.replace(/^RM\s?([\d,]+)/, (_, n) => MC.rm(+n.replace(/,/g, '')))) : '';
const SEE = { Issued: 'Valid', Corrected: 'Corrected, valid', Revoked: 'Revoked', Replaced: 'Replaced' };
const MATCH = { 'DS|BITS 1123': ['Full', 'Full', 'Full', 'Full'], 'CND|BITU 3973': ['Full', 'Partial', 'Full', 'None'] };
/* why an ACR cannot go into a transfer application (used when choosing and again on submit) */
const acrWhy = (a, s) => { const t = activeTr(s, a.id);
  return a.owner !== ME ? 'This record is not yours.' : a.status !== 'Deposited' ? 'Not deposited in ABC yet. Give consent first.' : a.credit === 'Provisionally redeemed' ? 'Provisionally redeemed in an approved transfer.' : a.credit === 'Redeemed' ? 'Already redeemed for a qualification.' : a.credit === 'Expired' ? 'Expired in ABC.' : t ? `You already have an active application on this credit: ${t.id}. Wait for its decision.` : ''; };
const notYours = '<div class="col">' + note('This record is not yours or does not exist.', 'err') + '</div>';

/* wizard progress state survives re-renders (kept in the tab, like a draft) */
const reg = {}, tr = { acrs: [], courses: {} };
const steps = (list, now) => track(list.map((b, i) => [i + 1 < now ? 'done' : i + 1 === now ? 'now' : '', `${i + 1}. ${b}`, '']));

/* ======================= home ======================= */
screen('st/home', { title: 'My learning', role: 'st', nav: 'home', render: (p, s) => {
  const en = mine(s), co = e => MC.co(e.cohort);
  const run = en.filter(e => e.status === 'Enrolled' && co(e) && co(e).status === 'Running');
  const soon = en.filter(e => e.status === 'Enrolled' && co(e) && co(e).status === 'Open');
  const wait = en.filter(e => /waiting/.test(e.status)), done = en.filter(e => e.status === 'Completed');
  const pend = en.filter(e => e.status === 'Pending payment');
  const consent = myAcrs(s).filter(a => a.status === 'Verified' && !a.consent);
  const acts = [...consent.map(a => ['warn', `Consent needed: deposit your ${MC.mcName(a.mc)} credit in ABC`, `ACR ${a.id} confirmed ${a.confirmed}. Give, decline or postpone.`, L('#/st/acr/' + a.id, 'Respond')]),
    ...pend.map(e => ['warn', `Finish payment for ${MC.mcName(co(e).mc)} ${co(e).name}`, `${MC.rm(co(e).fee)} due. Your place is held until the registration window ends.`, L(`#/st/register/${e.cohort}?step=3`, 'Pay now')])];
  return `${head({ title: 'My learning', sub: 'Amir Hakimi · external learner X2026-0193', actions: `<span class="btn" data-go="#/st/catalogue">Find a micro-credential</span>` })}
  ${panel('Actions for you', acts.length ? checks(acts.map(([k, t, sub, x]) => [k, t, sub, x])) : '<div class="muted">Nothing is waiting for you.</div>', `${acts.length} open`)}
  <div class="row">
    <div class="grow col">
      ${panel('In progress', run.map(e => { const m = MC.mc(co(e).mc), done = e.modules.filter(x => x[0] === 'Achieved').length, cur = Math.min(done + 1, e.modules.length);
        return `<div class="col" style="gap:8px;padding:4px 0 12px">
          <div class="row" style="align-items:baseline;gap:12px"><b>${MC.mcName(m.id)}</b><span class="muted">${co(e).name}</span><span class="small muted" style="margin-left:auto">Ends ${co(e).end}</span></div>
          ${bar(e.progress)}<div class="row small" style="justify-content:space-between"><span>Module ${cur} of ${e.modules.length}: ${m.modules[cur - 1][0]} · ${e.progress}% done</span><span class="muted">Next: ${NEXT[e.cohort] || 'see UCreds'}</span></div>
          <div><a data-go="#/st/enrolment/${e.id}">Modules and payment</a></div></div>`; }).join('') || '<div class="muted">You are not studying anything right now.</div>')}
      ${panel('Waiting for verification', wait.map(e => { const c = co(e), m = MC.mc(c.mc), l = stat(e); return `<div class="check"><span class="dot warn"></span><div class="grow">${MC.mcName(c.mc)} <span class="muted">${c.name}</span><span class="small muted" style="display:block">${l === 'Waiting for result verification' ? 'Your module results are in. The deputy dean verifies them first, then confirms completion.' : 'Your results are verified. The deputy dean now confirms completion.'} Your certificate is issued after that. Credit: ${creditOf(m, c)}.</span></div>${tag(l, 'info')}</div>`; }).join('') || '<div class="muted">No results are waiting.</div>')}
      ${panel('Achieved', table({ id: 'sthomedone', nopager: true, size: 10, cols: [{ h: 'Micro-credential', r: e => cname(e) }, { h: 'Modules', r: e => `${e.modules.length} of ${e.modules.length} achieved` }, { h: '', r: e => L('#/st/credentials', 'Credential') }], rows: done, go: e => '#/st/enrolment/' + e.id }), '', false)}
    </div>
    <div class="col" style="width:340px">
      ${panel('Upcoming registrations', [...soon, ...pend].map(e => { const c = co(e); return `<div class="check"><span class="dot n"></span><div class="grow">${MC.mcName(c.mc)}<span class="small muted" style="display:block">${c.name} starts ${c.start}</span></div>${tag(e.status)}</div>`; }).join('') || '<div class="muted">Nothing booked yet.</div>', '')}
      ${panel('Your recognition track', (() => { const A = myAcrs(s), need = A.filter(a => !a.consent || /^(Declined|Postponed)/.test(a.consent)).length, T = s.transfers.filter(t => t.who === ME), act_ = T.find(t => /^(Submitted|Under evaluation|More evidence needed)$/.test(t.status)), ach = en.reduce((n, e) => n + e.modules.filter(x => x[0] === 'Achieved').length, 0);
        return track([[ach ? 'done' : '', 'Learn', `${ach} modules`], [myCreds(s).length ? 'done' : '', 'Credential', `${myCreds(s).length} issued`], [!A.length ? '' : need ? 'now' : 'done', 'Consent', !A.length ? 'No ACR yet' : need ? `${need} open` : 'Done'], [act_ ? 'now' : T.some(t => /^(Approved|Partly approved)$/.test(t.status)) ? 'done' : '', 'Transfer', act_ ? 'Decision due' : 'Optional']]); })() + `<div class="small muted" style="margin-top:8px">Each step is a separate decision. See ${L('#/st/ledger', 'credit ledger')}.</div>`)}
      <div class="band">Looking for more? ${L('#/st/catalogue', 'Browse open offerings')}.</div>
    </div></div>`; } });

/* ======================= catalogue ======================= */
screen('st/catalogue', { title: 'Find a micro-credential', role: 'st', nav: 'catalogue', render: (p, s) => {
  const all = s.cohorts.filter(c => /^(Open|Full)$/.test(c.status) && (MC.mc(c.mc) || {}).status === 'Approved'), mcOf = c => MC.mc(c.mc), startOf = c => c.start.split(' ').slice(1).join(' ');
  const f = { cat: p.cat || '', fac: p.fac || '', mode: p.mode || '', start: p.start || '' };
  const rows = all.filter(c => (!f.cat || mcOf(c).cat === f.cat) && (!f.fac || mcOf(c).ptj === f.fac) && (!f.mode || MODE[c.mc] === f.mode) && (!f.start || startOf(c) === f.start));
  const so = (k, label, opts) => `<select class="input" style="width:170px" data-filter="${k}" aria-label="${label}"><option value="">${label}</option>${opts.map(o => `<option ${o === f[k] ? 'selected' : ''}>${o}</option>`).join('')}</select>`;
  const mine_ = new Set(mine(s).filter(e => !gone(e)).map(e => e.cohort));
  const cats = `<select class="input" style="width:230px" data-filter="cat" aria-label="All categories"><option value="">All categories</option>${['UMC', 'SAMC', 'MCE'].map(k => `<option value="${k}" ${k === f.cat ? 'selected' : ''}>${MC.CATNAME[k]}</option>`).join('')}</select>`;
  return `${head({ title: 'Find a micro-credential', sub: `${rows.length} of ${all.length} offerings open for registration or full` })}
  ${table({ id: 'stcat', search: 'Search by name', tools: cats + so('fac', 'All faculties', [...new Set(all.map(c => mcOf(c).ptj))]) + so('mode', 'All modes', [...new Set(all.map(c => MODE[c.mc]))]) + so('start', 'Any start month', [...new Set(all.map(startOf))]),
    cols: [{ h: 'Micro-credential', r: c => `${MC.mcNm(c.mc)}<span class="s">${c.name}</span>` }, { h: 'Faculty', r: c => mcOf(c).ptj }, { h: 'Mode', r: c => MODE[c.mc] }, { h: 'Dates', r: c => `${c.start} to ${c.end}` },
      { h: 'Credit', r: c => creditOf(mcOf(c), c) }, { h: 'Fee', num: true, r: c => MC.rm(c.fee) },
      { h: 'Places', r: c => c.status === 'Full' ? tag('Full', 'warn') + '<span class="s">Waiting list: Release 2</span>' : `${c.places - c.taken} of ${c.places} left` },
      { h: '', r: c => c.status === 'Full' ? '<span class="muted">Full, no register</span>' : mine_.has(c.id) ? '<span class="muted">Registered</span>' : L('#/st/register/' + c.id, 'Register') }],
    rows, go: c => '#/public/offering/' + c.id, empty: 'No offering matches these filters. Clear a filter to see more.' })}
  <div class="small muted">Click a row for the public offering page. SAMC credit is shown only while Quality Verification covers the cohort dates.</div>`; },
  mount: root => root.querySelectorAll('[data-filter]').forEach(el => el.addEventListener('change', () => { const q = new URLSearchParams(); root.querySelectorAll('[data-filter]').forEach(x => x.value && q.set(x.dataset.filter, x.value)); MC.go('#/st/catalogue' + (q.toString() ? '?' + q : '')); })) });

/* ======================= register wizard ======================= */
screen('st/register/:cohort', { title: 'Register', role: 'st', nav: 'catalogue', ex: 'CND-2027-1', render: (p, s) => {
  const c = MC.co(p.cohort); if (!c) return note('No such offering.', 'err');
  const m = MC.mc(c.mc), r = reg[c.id] = reg[c.id] || {};
  const rec = s.enrolments.find(e => e.who === ME && e.cohort === c.id && !gone(e)), dr = (s.regDrafts || {})[c.id] || {};
  const crumb = [['Find a micro-credential', '#/st/catalogue'], [`Register: ${m.name} ${c.name}`]];
  const side = `<div style="width:320px">${panel('Your registration', dl([['Micro-credential', MC.mcName(m.id)], ['Cohort', c.name], ['Dates', `${c.start} to ${c.end}`], ['Credit', creditOf(m, c)], ['Fee', MC.rm(c.fee)]]))}</div>`;
  if (m.status !== 'Approved' && !rec) return `${head({ crumb, title: `${m.name} ${c.name}`, status: tag(m.status) })}${note(`Registration is closed: this micro-credential is ${m.status.toLowerCase()}.`, 'warn')}<div><span class="btn ter" data-go="#/st/catalogue">Back to the catalogue</span></div>`;
  if (c.status === 'Full' && !rec) return `${head({ crumb, title: `${m.name} ${c.name}`, status: tag('Full', 'warn') })}${note(`This offering is full: ${c.taken} of ${c.places} places are taken. Registration is closed for it. A waiting list is planned for Release 2.`, 'warn')}<div class="row"><span class="btn dis">Register: offering is full</span><span class="btn ter" data-go="#/st/catalogue">Back to the catalogue</span></div>`;
  if (!/^(Open|Full)$/.test(c.status) && !rec) return `${head({ crumb, title: `${m.name} ${c.name}`, status: tag(c.status) })}${note(`Registration is not open for this offering (${c.status}).`, 'warn')}<div><span class="btn ter" data-go="#/st/catalogue">Back to the catalogue</span></div>`;
  let step = Math.min(4, Math.max(1, +p.step || 1));
  const done = rec && rec.status === 'Enrolled';
  if (done) step = 4; else { if (step === 4) step = rec ? 3 : 1; if (step > 2 && !(dr.ackVer || (rec && rec.ack))) step = 2; }
  const here = n => `#/st/register/${c.id}?step=${n}`;
  const bar_ = `${head({ crumb, title: `Register: ${m.name} ${c.name}` })}${steps(['Eligibility and details', 'Acknowledgement', 'Payment', 'Confirmation'], step)}`;
  if (step === 1) { const left = (c.places || 0) - (c.taken || 0), pre = m.prereq && !mine(s).some(e => e.status === 'Completed' && MC.co(e.cohort).mc === m.prereq);
    const chk = [['', 'Active UTeM learner account', 'X2026-0193, Amir Hakimi'], [m.status === 'Approved' ? '' : 'err', 'Micro-credential is approved', m.status === 'Approved' ? m.approvedBy || '' : `Registration is closed: this micro-credential is ${m.status.toLowerCase()}.`],
      [left > 0 && c.status === 'Open' ? '' : 'err', 'Registration is open and a place is free', left > 0 ? `${left} of ${c.places} places left` : 'No places left'], [pre ? 'err' : '', m.prereq ? `Prerequisite: ${MC.mcName(m.prereq)}` : 'No prerequisite for this offering', pre ? 'You have not completed it yet.' : ''],
      [rec && rec.status === 'Enrolled' ? 'err' : '', 'Not already registered in this cohort', rec && rec.status === 'Enrolled' ? `You are enrolled (${rec.id}).` : '']], bad = chk.filter(x => x[0] === 'err');
    return `${bar_}<div class="row"><div class="grow col">
    ${panel('Eligibility', checks(chk), bad.length ? tag('Not eligible', 'err') : tag('Eligible', 'ok'))}
    <form data-form class="col" onsubmit="return false">${panel('Your details', `<div class="col">${field('Name', `<div class="input read">Amir Hakimi</div>`)}${field('Email', `<div class="input read">amir.hakimi@learner.utem.edu.my</div>`, 'From your UTeM account. Change it on your profile.')}${field('Mobile phone for access messages', inp('phone', r.phone || '012-345 6789', '', true), '', true)}</div>`)}
    <div class="row">${bad.length ? `<span class="btn dis">Continue: ${bad[0][2].replace(/\.$/, '')}</span>` : `<span class="btn" data-validate data-act="st-details" data-id="${c.id}" data-then="${here(2)}">Continue to acknowledgement</span>`}<span class="btn ter" data-go="#/public/offering/${c.id}">Back to the offering</span></div></form></div>${side}</div>`; }
  if (step === 2) return `${bar_}<div class="row"><div class="grow col"><form data-form class="col" onsubmit="return false">
    ${panel('Recognition statement', `<p>${recog(m, c)}</p><label class="choice" style="margin-top:12px"><input type="checkbox" name="ack1" required><div>I have read and understood the recognition statement above.</div></label>`)}
    ${panel('Personal data notice (PDPA)', `<p>UTeM uses your name, ID, contact details, results and payment reference to run this offering, issue credentials and, only with your consent, deposit credit in ABC. Data is kept under UTeM retention rules and is not sold or shared with employers except what you choose to share on a credential. You may ask to see or correct your data from your profile.</p><label class="choice" style="margin-top:12px"><input type="checkbox" name="ack2" required><div>I have read the personal data notice and agree to this use of my data.</div></label>`)}
    <div class="row"><span class="btn" data-validate data-act="st-ack" data-id="${c.id}" data-then="${here(3)}">Continue to payment</span><span class="btn ter" data-go="${here(1)}">Back</span></div></form></div>${side}</div>`;
  if (step === 3) { const meth = r.method || 'card', fee = c.fee;
    return `${bar_}<div class="row"><div class="grow col"><form data-form class="col" onsubmit="return false">
    ${r.error ? note(`<div><b>Payment did not go through.</b> The dummy payment service declined the card. No money was taken, and your place is not charged twice. Reference ${r.error}. Try the card again or use another method.</div>`, 'err') : ''}
    ${note('Test mode: this is a dummy payment service. Card 4000 0000 0000 0002 always fails so you can try the retry path. Any other number succeeds.', '')}
    ${panel('Payment method', `<div class="col">${MC.radios('method', [['card', 'Card', 'Dummy card form'], ['fpx', 'FPX online banking', 'Dummy bank page']], meth)}
      <div data-card class="col" style="${meth === 'card' ? '' : 'display:none'}">${field('Card number', inp('card', r.card || '4242 4242 4242 4242', '', meth === 'card'), '', true)}<div class="row">${field('Expiry', inp('exp', '12/28', 'MM/YY'))}${field('CVV', inp('cvv', '123', ''))}</div></div>
      <div data-fpx style="${meth === 'fpx' ? '' : 'display:none'}">${field('Bank', MC.sel('bank', ['Maybank2u', 'CIMB Clicks', 'Bank Islam', 'RHB Now']), 'You would be sent to your bank. In this prototype the bank step is simulated.')}</div></div>`)}
    ${panel('Amount', dl([['Fee', MC.rm(fee)], ['Total to pay', `<b>${MC.rm(fee)}</b>`]]))}
    <div class="row"><span class="btn" data-validate data-modal="st-pay" data-id="${c.id}">${r.error ? 'Retry payment' : `Pay ${MC.rm(fee)}`}</span><span class="btn ter" data-go="${here(2)}">Back</span></div></form></div>${side}</div>`; }
  const e = rec || {};
  return `${bar_}<div class="row"><div class="grow col">${note(`<div><b>You are registered.</b> Enrolment ${e.id}${e.paid ? `, paid ${paidTxt(e)}` : ''}. A confirmation was sent to amir.hakimi@learner.utem.edu.my.</div>`, 'ok')}
    ${panel('How to start', checks([['', `Course space opens on UCreds before ${c.start}`, 'You get a message when it is ready.'], ['', 'Sign in to UCreds with your UTeM account', 'Use the same account you use here. There is no separate password.'], ['', 'Open the course named in your confirmation', `${MC.mcName(m.id)} ${c.name}. Modules open in order.`], ['', 'Come back here to follow progress', 'Results and credentials appear here after the lecturer submits and the deputy dean verifies them.']]))}
    <div class="row"><span class="btn" data-go="#/st/home">Go to my learning</span><span class="btn ter" data-go="#/st/enrolment/${e.id}">View this registration</span><span class="btn ter" data-go="#/doc/enrolment/${e.id}">Enrolment confirmation</span><span class="btn ter" data-go="#/doc/receipt/${e.id}">Receipt</span></div></div>${side}</div>`; },
  mount: root => { const f = root.querySelector('[data-form]'); if (!f) return; f.querySelectorAll('[name=method]').forEach(i => i.addEventListener('change', () => { const card = i.value === 'card'; f.querySelector('[data-card]').style.display = card ? '' : 'none'; f.querySelector('[data-fpx]').style.display = card ? 'none' : ''; f.querySelector('[name=card]').required = card; })); } });
act('st-details', (c, s) => { (reg[c.id] = reg[c.id] || {}).phone = c.phone; });
act('st-ack', (c, s) => { s.regDrafts = s.regDrafts || {}; const d = s.regDrafts[c.id] = { ackVer: 'v1.0', ackOn: TODAY, pdpaOn: TODAY };
  const e = s.enrolments.find(x => x.who === ME && x.cohort === c.id && x.status === 'Pending payment'); if (e) Object.assign(e, { ack: true }, d); });
modal('st-pay', { kicker: c => `Registration · ${c.id}`, title: c => `Pay ${MC.rm(MC.co(c.id).fee)} for ${MC.mcName(MC.co(c.id).mc)}?`, confirm: 'Pay now', act: 'st-pay',
  body: c => `<p>You are paying ${MC.rm(MC.co(c.id).fee)} by ${c.method === 'fpx' ? 'FPX online banking' : 'card'} to the dummy payment service.</p><p class="muted">If you press twice, you are charged once: the payment carries one reference per registration. If it fails, nothing is charged. Amir Hakimi and CODL are told the result. Refund rules are not set yet.</p>` });
act('st-pay', (c, s) => {
  const co = MC.co(c.id), r = reg[c.id] = reg[c.id] || {}, num = String(c.card || '').replace(/\D/g, '');
  let e = s.enrolments.find(x => x.who === ME && x.cohort === c.id && !gone(x));
  if (e && e.status === 'Enrolled') { MC.close(); MC.go(`#/st/register/${c.id}?step=4`); MC.toast('Already paid', 'Your registration is complete. You were not charged again.', 'info'); return false; }
  const left = (co.places || 0) - (co.taken || 0), mm = MC.mc(co.mc);
  if (mm.status !== 'Approved' || !/^(Open|Full)$/.test(co.status) || left <= 0) { if (left <= 0 && mm.status === 'Approved') co.status = 'Full'; MC.close(); MC.save(); MC.go(`#/st/register/${c.id}?step=1`);
    MC.toast('Registration is closed', left <= 0 ? 'The last place was taken. You were not charged.' : 'This offering is no longer open. You were not charged.', 'err'); return false; }
  r.method = c.method || 'card'; r.card = c.card;
  const dr = (s.regDrafts || {})[c.id] || { ackVer: 'v1.0', ackOn: TODAY, pdpaOn: TODAY };
  if (!e) { let n = 700 + s.enrolments.length; while (s.enrolments.some(x => x.id === 'E-2026-' + slot(n))) n++;
    e = Object.assign({ id: 'E-2026-' + slot(n), who: ME, cohort: c.id, status: 'Pending payment', paid: '', ack: true, modules: mm.modules.map(() => ['Not started', '']) }, dr); s.enrolments.push(e); }
  if (r.method === 'card' && num === '4000000000000002') {
    r.error = 'ERR-PAY-' + slot(n_(s), 4); MC.close(); MC.save(); MC.go(`#/st/register/${c.id}?step=3`); MC.toast('Payment failed', 'Nothing was charged. Check the card and retry.', 'err'); return false; }
  r.error = ''; Object.assign(e, dr, { status: 'Enrolled', amount: co.fee, paidOn: TODAY, paid: `${MC.rm(co.fee)}, ${TODAY}`, receipt: 'RCP-2026-' + slot(s.enrolments.length * 7 + 100) }); co.taken = (co.taken || 0) + 1; if (co.taken >= co.places) co.status = 'Full';
  notif(s, 'st', `Registered: ${MC.mcName(co.mc)} ${co.name}`, `Enrolment ${e.id}, payment ${MC.rm(co.fee)} received`, '#/st/enrolment/' + e.id);
  log(s, 'Amir Hakimi', `Registered and paid ${MC.rm(co.fee)} for ${MC.mcName(co.mc)} ${co.name}`, e.id);
  MC.close(); MC.save(); MC.go(`#/st/register/${c.id}?step=4`); MC.toast('Payment received', `You are enrolled (${e.id}). Open UCreds on the start date.`); return false; });
const n_ = s => s.audit.length;

/* ======================= enrolments ======================= */
screen('st/enrolments', { title: 'Registrations and payments', role: 'st', nav: 'enrolments', render: (p, s) =>
  `${head({ title: 'Registrations and payments', sub: 'Every registration with its payment. Receipts are available once a payment is received.', actions: '<span class="btn" data-go="#/st/catalogue">Register for another</span>' })}
  ${table({ id: 'stenrol', search: 'Search registrations', empty: 'You have no registrations yet.', cols: [{ h: 'Registration', r: e => `<b>${e.id}</b>` }, { h: 'Micro-credential', r: e => cname(e) },
    { h: 'Status', r: e => tag(stat(e), /waiting/.test(e.status) ? 'info' : '') + (e.status === 'Pending payment' ? '<span class="s">Place held until the window ends</span>' : '') }, { h: 'Payment', r: e => paidTxt(e) || (e.status === 'Cancelled' ? 'Not charged' : 'Not paid yet') },
    { h: '', r: e => e.status === 'Pending payment' ? L(`#/st/register/${e.cohort}?step=3`, 'Pay now') : e.paid ? L('#/doc/receipt/' + e.id, 'Receipt') : '' }],
    rows: s => mine(s).slice().reverse(), go: e => '#/st/enrolment/' + e.id })}`});

screen('st/enrolment/:id', { title: 'Registration', role: 'st', nav: 'enrolments', ex: 'E-2026-0590', render: (p, s) => {
  const e = s.enrolments.find(x => x.id === p.id && x.who === ME); if (!e) return note('This record is not yours or does not exist.', 'err');
  const c = MC.co(e.cohort), m = MC.mc(c.mc), dl_ = new Date(D(c.end).getTime() - 20 * 864e5), left = dstr(dl_), wd = e.status === 'Enrolled' && D(TODAY) <= dl_;
  return `${head({ crumb: [['Registrations and payments', '#/st/enrolments'], [e.id]], title: `${m.name} ${c.name}`, sub: `${e.id} · ${c.start} to ${c.end}`, status: tag(stat(e), /waiting/.test(e.status) ? 'info' : ''),
    actions: wd ? `<span class="btn danger ter" data-modal="st-withdraw" data-id="${e.id}">Withdraw</span>` : e.status === 'Pending payment' ? `<span class="btn danger ter" data-modal="st-cancelreg" data-id="${e.id}">Cancel registration</span>` : `<span class="btn dis">Withdraw: ${e.status === 'Withdrawn' ? 'already withdrawn' : e.status === 'Cancelled' ? 'registration was cancelled' : e.status === 'Enrolled' ? `the withdrawal deadline (${left}) has passed` : 'only Enrolled registrations can be withdrawn'}</span>` })}
  <div class="row"><div class="grow col">
    ${panel('Modules', table({ id: 'stenmod', nopager: true, cols: [{ h: 'Module', r: r => r[0] }, { h: 'Status', r: r => tag(r[1], r[1] === 'Achieved' ? 'ok' : r[1] === 'In progress' ? 'info' : '') }, { h: 'Date', r: r => r[2] || '—' }],
      rows: e.modules.map((x, i) => [m.modules[i][0], e.status === 'Withdrawn' && x[0] !== 'Achieved' ? 'Stopped (withdrawn)' : x[0], x[1]]) }), e.progress !== undefined ? `${e.progress}% done` : '', false)}
    ${e.status === 'Withdrawn' ? note(`Withdrawn on ${e.withdrawn}. Reason: ${e.reason}. Modules you achieved stay on your record.`, 'warn') : e.status === 'Cancelled' ? note(`Cancelled on ${e.cancelled}. Reason: ${e.reason}. You were not charged.`, 'warn') : ''}</div>
  <div class="col" style="width:340px">${panel('Payment', dl([['Paid', paidTxt(e) || (e.status === 'Cancelled' ? 'Not charged' : 'Not paid yet')], ['Fee', MC.rm(c.fee)], ['Receipt', e.paid ? L('#/doc/receipt/' + e.id, 'Open receipt') : e.status === 'Pending payment' ? L(`#/st/register/${e.cohort}?step=3`, 'Pay now') : '—'], ['Confirmation', e.paid ? L('#/doc/enrolment/' + e.id, 'Open enrolment confirmation') : '—']]))}
    ${panel('Withdrawing', `<p class="small">You can withdraw until ${left}. Modules you have achieved stay on your record and your credentials are not changed. Refund rules are not decided yet: CODL decides refunds case by case, outside MCMS.${e.status === 'Pending payment' ? ' You have not paid, so you can cancel this registration at any time and nothing is charged.' : ''}</p>`)}</div></div>`; } });
modal('st-withdraw', { kicker: c => `Registration ${c.id}`, danger: true, reason: 'Reason for withdrawing (kept in the audit trail)', confirm: 'Withdraw', act: 'st-withdraw',
  title: c => { const e = MC.state.enrolments.find(x => x.id === c.id); return `Withdraw from ${MC.mcName(MC.co(e.cohort).mc)} ${MC.co(e.cohort).name}?`; },
  body: c => { const e = MC.state.enrolments.find(x => x.id === c.id), n = e.modules.filter(x => x[0] === 'Achieved').length; return `<p>This stops registration ${e.id} for ${MC.mcName(MC.co(e.cohort).mc)}. It cannot be undone; you would register again if you change your mind.</p>
    <p>${n ? `The ${n} module${n > 1 ? 's' : ''} you achieved stay on your record.` : 'You have not achieved any module yet.'} Your existing credentials are not changed. Refunds are not decided yet; CODL contacts you. The cohort lecturer and CODL are told.</p>`; },
  then: c => '#/st/enrolments' });
modal('st-cancelreg', { kicker: c => `Registration ${c.id}`, danger: true, reason: 'Reason for cancelling (kept in the audit trail)', confirm: 'Cancel registration', cancel: 'Keep registration', act: 'st-cancelreg',
  title: c => { const e = MC.state.enrolments.find(x => x.id === c.id); return `Cancel your registration for ${MC.mcName(MC.co(e.cohort).mc)} ${MC.co(e.cohort).name}?`; },
  body: c => `<p>You have not paid, so nothing is charged and no place is used. The registration ${c.id} is closed. You can register again while the offering is open.</p>`, then: () => '#/st/enrolments' });
act('st-cancelreg', (c, s) => { const e = s.enrolments.find(x => x.id === c.id); e.status = 'Cancelled'; e.cancelled = TODAY; e.reason = c.reason; const co = MC.co(e.cohort); (reg[e.cohort] || {}).error = '';
  log(s, 'Amir Hakimi', `Cancelled unpaid registration for ${MC.mcName(co.mc)} ${co.name}: ${c.reason}`, e.id); MC.toast('Registration cancelled', 'Nothing was charged.', 'info'); });
act('st-withdraw', (c, s) => { const e = s.enrolments.find(x => x.id === c.id); e.status = 'Withdrawn'; e.withdrawn = TODAY; e.reason = c.reason; const co = MC.co(e.cohort); if (co.taken) co.taken--;
  notif(s, 'st', `Withdrawn: ${MC.mcName(co.mc)} ${co.name}`, 'Refund decision to follow from CODL', '#/st/enrolment/' + e.id); log(s, 'Amir Hakimi', `Withdrew from ${MC.mcName(co.mc)} ${co.name}: ${c.reason}`, e.id);
  MC.toast('Withdrawal recorded', 'Achieved modules stay on your record. CODL will contact you about a refund.'); });

/* ======================= achievement record ======================= */
screen('st/record', { title: 'Achievement record', role: 'st', nav: 'record', render: (p, s) => {
  const rows = []; mine(s).slice().reverse().forEach(e => { const c = MC.co(e.cohort), m = MC.mc(c.mc); if (e.status === 'Pending payment' || e.status === 'Cancelled') return; e.modules.forEach((x, i) => rows.push({ mc: m.id, cohort: c.name, mod: m.modules[i][0], st: e.status === 'Withdrawn' && x[0] !== 'Achieved' ? 'Stopped (withdrawn)' : x[0], date: x[1], id: e.id })); });
  const comp = mine(s).filter(e => e.status === 'Completed');
  return `${head({ title: 'Achievement record', sub: 'Internal record, not an ACR', status: tag('Internal record, not an ACR', 'info'), actions: '<span class="btn ter" data-go="#/doc/record/amir">Download record (PDF)</span>' })}
  ${note('This is MCMS\'s own record of your modules and completions. It is not an Academic Credit Record (ACR) and cannot be deposited or transferred. Your ACRs are on the ' + L('#/st/ledger', 'credit ledger') + '.')}
  ${kpis([['Modules achieved', rows.filter(r => r.st === 'Achieved').length, 'Each earns a module badge'], ['Completions', comp.length, 'All modules achieved and confirmed'], ['In progress', rows.filter(r => r.st === 'In progress').length, '']])}
  ${table({ id: 'strecord', size: 10, search: 'Search modules', cols: [{ h: 'Micro-credential', r: r => MC.mcNm(r.mc) + `<span class="s">${r.cohort}</span>` }, { h: 'Module', k: 'mod' }, { h: 'Status', r: r => tag(r.st, r.st === 'Achieved' ? 'ok' : r.st === 'In progress' ? 'info' : '') },
    { h: 'Date', r: r => r.date || '—' }, { h: 'Badge', r: r => r.st === 'Achieved' ? 'Module badge' : '—' }], rows, go: r => '#/st/enrolment/' + r.id })}
  ${panel('Completions', comp.length ? checks(comp.map(e => { const cr = myCreds(s).find(c => c.cohort === e.cohort); return ['', `${MC.mcName(MC.co(e.cohort).mc)} ${MC.co(e.cohort).name}`, `All ${e.modules.length} modules achieved`, cr ? L('#/st/credential/' + cr.id, 'Credential') : '']; })) : '<div class="muted">No completions yet.</div>')}`; } });

/* ======================= credentials ======================= */
screen('st/credentials', { title: 'Credentials', role: 'st', nav: 'credentials', render: (p, s) =>
  `${head({ title: 'Credentials', sub: 'Certificates and Digital Credentials issued to you. Credit is on the credit ledger, not here.' })}
  ${table({ id: 'stcreds', empty: 'No credentials issued yet. They are issued after the deputy dean confirms completion.', cols: [{ h: 'Credential', r: c => `${MC.mcNm(c.mc)}<span class="s">${c.id}</span>` }, { h: 'Issued', r: c => c.issued }, { h: 'Result', k: 'result' }, { h: 'Verify code', r: c => `<code>${c.code}</code>` }, { h: 'Status', r: c => tag(c.status) }],
    rows: s => myCreds(s), go: c => '#/st/credential/' + c.id })}`});

const qr = code => { let h = 7; for (const ch of code) h = (h * 31 + ch.charCodeAt(0)) >>> 0; const n = 21, u = 5; let o = '';
  for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) { let on = 0, hit = false;
    for (const [a, b] of [[0, 0], [n - 7, 0], [0, n - 7]]) { const i = x - a, j = y - b; if (i >= -1 && i <= 7 && j >= -1 && j <= 7) { hit = true; on = i >= 0 && i <= 6 && j >= 0 && j <= 6 && (i === 0 || i === 6 || j === 0 || j === 6 || (i >= 2 && i <= 4 && j >= 2 && j <= 4)); } }
    if (!hit) { h = (h * 1103515245 + 12345) >>> 0; on = (h >> 16) & 1; } if (on) o += `<rect x="${x * u}" y="${y * u}" width="${u}" height="${u}"/>`; }
  return `<svg width="${n * u + 10}" height="${n * u + 10}" viewBox="-5 -5 ${n * u + 10} ${n * u + 10}" role="img" aria-label="QR code for verification link ${code}"><rect x="-5" y="-5" width="100%" height="100%" fill="#fff"/><g fill="#161616">${o}</g></svg>`; };

screen('st/credential/:id', { title: 'Credential', role: 'st', nav: 'credentials', ex: 'MCMS-C-2026-000098', render: (p, s) => {
  const c = s.credentials.find(x => x.id === p.id && x.owner === ME); if (!c) return note('This record is not yours or does not exist.', 'err');
  const m = MC.mc(c.mc), link = `https://mcms.utem.edu.my/verify/${c.code}`, nw = c.replacedBy && s.credentials.find(x => x.id === c.replacedBy);
  const norm = h => Array.isArray(h) ? h : [h.rev || h.revision || '', h.event || h.status || '', h.on || h.date || '', h.note || h.reason || ''];
  let hist = (c.history || []).map(norm);
  if (!hist.length) { hist = [['Revision 1', 'Issued', c.issued, 'Issued after completion was confirmed']];
    if (c.revision > 1) hist.push([`Revision ${c.revision}`, c.status === 'Replaced' ? 'Replaced' : 'Corrected', c.correctedOn || c.replacedOn || 'See audit trail', 'Earlier revision stays viewable and marked']);
    if (c.status === 'Revoked') hist.push(['Revision ' + c.revision, 'Revoked', c.revoked, c.reason]); }
  const live = c.status === 'Issued' || c.status === 'Corrected';
  return `${head({ crumb: [['Credentials', '#/st/credentials'], [c.id]], title: `${m.name}`, sub: `${c.id} · issued ${c.issued} · ${c.result}`, status: tag(c.status),
    actions: live ? `<span class="btn" data-go="#/doc/certificate/${c.id}">Download certificate (PDF)</span><span class="btn ter" data-go="#/doc/credential/${c.id}">Signed JSON</span>` : `<span class="btn dis">Download: ${c.status === 'Replaced' ? 'this credential was replaced by a newer one' : 'credential is revoked'}</span>` })}
  ${c.status === 'Revoked' ? note(`This credential was revoked on ${c.revoked}. Reason: ${c.reason}. Employers who check it see "Revoked".`, 'err') : ''}
  ${c.status === 'Replaced' ? note(`This credential was replaced${c.replacedOn ? ' on ' + c.replacedOn : ''}${c.reason ? '. Reason: ' + c.reason : ''}. Employers who check it see "Replaced". ${nw ? L('#/st/credential/' + nw.id, 'Open the new credential ' + nw.id) : c.replacedBy ? 'The new credential is ' + c.replacedBy + '.' : ''}`, 'warn') : ''}
  ${c.status === 'Corrected' ? note(`This credential was corrected${c.correctedOn ? ' on ' + c.correctedOn : ''}. The earlier revision stays viewable and marked in the history below.`, 'info') : ''}
  <div class="row"><div class="grow col">
    ${!live ? note(`Sharing is switched off: the verification link now shows "${c.status}".`, 'warn') : panel('Share this credential', `<div class="row" style="gap:20px"><div class="col grow">${field('Verification link', `<div class="input read" style="padding:0 8px"><code>${link}</code></div>`, 'Anyone with the link or code can check that this credential is genuine. They do not need an account.')}
      <div class="row" style="gap:8px"><span class="btn ter sm" data-act="st-copy" data-link="${link}">Copy link</span><span class="btn ter sm" data-go="#/public/verify/${c.code}">Open what an employer sees</span></div><div class="small muted">Verify code <code>${c.code}</code></div></div><div>${qr(c.code)}</div></div>`)}
    ${panel('Revision history', table({ id: 'stcredrev', nopager: true, cols: [{ h: 'Revision', r: r => r[0] }, { h: 'Event', r: r => tag(r[1]) }, { h: 'Date', r: r => r[2] }, { h: 'Note', r: r => r[3] }], rows: hist }), '', false)}</div>
  <div class="col" style="width:360px">${panel('What an employer sees', checks([['', 'Your name', c.who], ['', 'The credential', m.name], ['', 'Issuer', 'Universiti Teknikal Malaysia Melaka'], ['', 'Date issued', c.issued], ['', 'Status', SEE[c.status] || c.status]]) + `<div class="small muted" style="margin-top:8px">Only these fields. Marks, credit and ABC details are not shown. ${L('#/public/verify/' + c.code, 'See the page')}</div>`)}
    ${c.qvClaim ? panel('Recognition claim', `<p>${c.qvClaim}, as at issue.</p>`) : ''}
    ${panel('Credit', `<p class="small">Academic credit, if any, is a separate record. See the ${L('#/st/ledger', 'credit ledger')}.</p>`)}</div></div>`; } });
act('st-copy', c => { try { navigator.clipboard.writeText(c.link); } catch (e) {} MC.toast('Link copied', c.link); return false; });

/* ======================= ledger and ACR ======================= */
const consentKind = a => !a.consent ? 'pending' : /^Given/.test(a.consent) ? 'given' : /^Declined/.test(a.consent) ? 'declined' : 'postponed';
const acrStages = (a, s) => { const k = consentKind(a), dep = a.status === 'Deposited', t = activeTr(s, a.id);
  return [['done', 'ACR confirmed', a.confirmed || 'Verified'], [k === 'given' ? 'done' : 'now', 'Consent', k === 'pending' ? 'Needed' : a.consent.split(' ')[0]], [dep ? 'done' : k === 'given' ? 'now' : '', 'Deposited', dep ? a.deposited : k === 'given' ? 'Deputy dean to record' : 'Waiting for consent'],
    [a.credit === 'Redeemed' ? 'done' : a.credit === 'Provisionally redeemed' ? 'now' : '', 'Transfer or redeem', a.credit === 'Available' ? (t ? `In ${t.id}, not decided` : 'Not used') : a.credit || '—']]; };
const acrTrack = (a, s) => track(acrStages(a, s));
/* compact form for table cells: four segments, then the current step in words (the full track is on the ACR page) */
const acrMini = (a, s) => { const st = acrStages(a, s), cur = st.find(x => x[0] === 'now') || st[st.length - 1];
  return `<div style="display:flex;gap:3px;min-width:150px">${st.map(x => `<i style="flex:1;height:4px;background:${x[0] === 'done' ? 'var(--ink)' : x[0] === 'now' ? 'var(--primary)' : 'var(--s2)'}"></i>`).join('')}</div><span class="small" style="display:block;margin-top:4px"><b>${cur[1]}</b>: ${cur[2]}</span>`; };
screen('st/ledger', { title: 'Credit ledger and ABC', role: 'st', nav: 'ledger', render: (p, s) => {
  const a = myAcrs(s), sum = f => a.filter(f).reduce((n, x) => n + x.credits, 0);
  return `${head({ title: 'Credit ledger and ABC', sub: 'One row for each Academic Credit Record (ACR)' })}
  ${note('Depositing in the Academic Bank of Credit (ABC) is storage of your record, not a transfer. No programme accepts credit automatically; each transfer is decided case by case. Micro-Credential Eksekutif (MCE) courses carry no credit and are not listed here.')}
  ${kpis([['Credits earned', sum(() => true), 'All ACRs, deposited or not'], ['Available', sum(x => x.credit === 'Available'), 'Deposited, can be used'], ['Provisionally redeemed', sum(x => x.credit === 'Provisionally redeemed'), 'In an approved transfer'], ['Redeemed', sum(x => x.credit === 'Redeemed'), 'Used for a qualification'], ['Expired', sum(x => x.credit === 'Expired'), 'Past 10 years in ABC']])}
  ${table({ id: 'stledger', nopager: false, empty: 'No ACRs yet. An ACR is made after completion for a UMC or a QV-valid SAMC.', cols: [{ h: 'ACR', r: x => `${MC.mcNm(x.mc)}<span class="s">${x.id}</span>` }, { h: 'Credits', num: true, r: x => x.credits },
    { h: 'Credit', r: x => crTag(x.credit) }, { h: 'Where it is', r: x => acrMini(x, s) }, { h: 'Deposited', r: x => x.deposited || '—' }, { h: 'Expires', r: x => x.expires || '—' },
    { h: '', r: x => x.status === 'Verified' && !x.consent ? '<b>' + L('#/st/acr/' + x.id, 'Give or decline consent') + '</b>' : L('#/st/acr/' + x.id, 'Open') }], rows: s => myAcrs(s), go: x => '#/st/acr/' + x.id })}`; } });

screen('st/acr/:id', { title: 'Academic Credit Record', role: 'st', nav: 'ledger', ex: 'MCMS-A-2026-000214', render: (p, s) => {
  const a = s.acrs.find(x => x.id === p.id && x.owner === ME); if (!a) return note('This record is not yours or does not exist.', 'err');
  const k = consentKind(a), dep = a.status === 'Deposited', m = MC.mc(a.mc), t = activeTr(s, a.id);
  const consentBox = dep ? note(`Consent ${a.consent} (wording ${a.consentVer || 'v1.0'}). The credit is deposited, so consent can no longer be changed here; contact CODL to ask for removal.`, 'ok')
    : panel('Consent to deposit in ABC', `<form data-form class="col" onsubmit="return false"><div class="band small">Consent wording v1.2, 1 Sep 2026: "I agree that UTeM may deposit this Academic Credit Record in the Academic Bank of Credit for ${credit_(a)} credits. I understand that deposit is storage, not a credit transfer, and that credit is valid for 10 years from deposit."</div>
      ${k !== 'pending' ? `<div class="small">Your current choice: <b>${a.consent}</b>. You can change it until the credit is deposited.</div>` : ''}
      ${MC.radios('choice', [['give', 'Give consent', 'The ACR joins the deputy dean\'s deposit queue'], ['decline', 'Decline', 'The ACR is kept but not deposited. You can change your mind later'], ['postpone', 'Postpone', 'Nothing changes; we remind you in 14 days']], k === 'given' ? 'give' : k === 'declined' ? 'decline' : k === 'postponed' ? 'postpone' : '').replace(/type="radio"/g, 'type="radio" required')}
      <div><span class="btn" data-validate data-modal="st-consent" data-id="${a.id}">Continue</span></div></form>`);
  return `${head({ crumb: [['Credit ledger and ABC', '#/st/ledger'], [a.id]], title: `${m.name}: Academic Credit Record`, sub: `${a.id} · ${credit_(a)} credits`, status: crTag(a.credit), actions: `<span class="btn ter" data-go="#/doc/acr/${a.id}">Download ACR (PDF)</span>` })}
  ${acrTrack(a, s)}
  <div class="row"><div class="grow col">${consentBox}
    ${panel('Record', dl([['ACR', a.id], ['Micro-credential', MC.mcName(a.mc)], ['Credits', credit_(a)], ['ACR status', tag(a.status === 'Deposited' ? 'Deposited' : 'Verified', a.status === 'Deposited' ? 'ok' : 'info')], ['Confirmed', a.confirmed || 'Before deposit'], ['Consent', a.consent || 'Not given yet'], ['ABC reference', a.abcRef || 'Assigned when deposited'], ['Deposited', a.deposited || '—'], ['Credit expires', a.expires || '—'], ['Credit status', crTag(a.credit)]]))}</div>
  <div class="col" style="width:340px">${panel('What this means', `<p class="small">An ACR is a separate record from your certificate and Digital Credential. Depositing it is storage only. ${t ? `It is part of application ${L('#/st/transfer/' + t.id, t.id)}, which is not decided.` : a.credit === 'Available' ? 'You can use it in a ' + L('#/st/transfer/new', 'credit transfer application') + '.' : 'It can be used for transfer only after it is deposited.'}</p>`)}
    ${L('#/st/ledger', 'Back to the credit ledger')}</div></div>`; } });
const credit_ = a => a.credits;
const CH = { give: ['Give consent to deposit', 'Give consent', 'The ACR enters the deputy dean\'s deposit queue. The deputy dean records the deposit and the ABC reference, and you are told. Deposit is storage, not a transfer. Nothing is deposited until the deputy dean records it.'],
  decline: ['Decline to deposit', 'Decline', 'The ACR is kept in MCMS and is not deposited. Your certificate and credentials are not affected. You can give consent later from this page.'],
  postpone: ['Postpone the decision', 'Postpone', 'Nothing is deposited and nothing is lost. We remind you in 14 days. You can decide at any time.'] };
/* changing a consent already given is a withdrawal: danger dialog with a reason */
const undo = c => c.choice !== 'give' && /^Given/.test((MC.state.acrs.find(x => x.id === c.id) || {}).consent || '');
modal('st-consent', { kicker: c => `ACR ${c.id}`, title: c => (undo(c) ? 'Withdraw your consent: ' + CH[c.choice || 'give'][0].toLowerCase() : CH[c.choice || 'give'][0]) + '?', confirm: c => undo(c) ? 'Withdraw consent' : CH[c.choice || 'give'][1], act: 'st-consent', danger: c => undo(c),
  body: c => `<p>${CH[c.choice || 'give'][2]}</p>${undo(c) ? `<p>You gave consent earlier. This withdraws it, and the deputy dean's deposit task is closed. The deputy dean is told.</p>${MC.field('Reason for withdrawing consent (kept in the audit trail)', MC.area('reason', '', '', true), '', true)}` : ''}<p class="small muted">Recorded with wording v1.2 and today's date.</p>` });
act('st-consent', (c, s) => { const a = s.acrs.find(x => x.id === c.id), was = consentKind(a), word = { give: 'Given', decline: 'Declined', postpone: 'Postponed' }[c.choice];
  a.consent = `${word} ${TODAY}`; a.consentVer = 'v1.2'; const tid = 'T-DEP-' + a.id, tk = s.tasks.find(x => x.id === tid);
  if (c.choice === 'give') { if (!tk) s.tasks.push({ id: tid, area: 'acr', what: 'Record ABC deposit', ref: a.id, from: 'Amir Hakimi', check: `Consent given ${TODAY}, ACR confirmed ${a.confirmed || ''}`, waiting: 'Today', go: '#/dd/deposit/' + a.id }); else tk.done = false;
    notif(s, 'dd', `Consent given: ${MC.mcName(a.mc)} ACR`, 'Amir Hakimi, ready for ABC deposit', '#/dd/deposit/' + a.id); }
  else if (tk && !tk.done) { tk.done = true; tk.doneNote = 'Consent withdrawn by the participant ' + TODAY; }
  if (was === 'given' && c.choice !== 'give') notif(s, 'dd', `Consent withdrawn: ${MC.mcName(a.mc)} ACR`, `Amir Hakimi: ${c.reason || 'no reason given'}`, '#/dd/deposit/' + a.id);
  log(s, 'Amir Hakimi', `${word === 'Given' ? 'Gave consent to deposit' : was === 'given' ? 'Withdrew consent: ' + word.toLowerCase() + ' deposit of' : word === 'Declined' ? 'Declined deposit of' : 'Postponed deposit of'} ${MC.mcName(a.mc)} ACR in ABC (wording v1.2)${was === 'given' && c.reason ? '. Reason: ' + c.reason : ''}`, a.id);
  MC.toast(c.choice === 'give' ? 'Consent recorded' : c.choice === 'decline' ? 'Deposit declined' : 'Reminder set', c.choice === 'give' ? 'The deputy dean will record the deposit. You will be told.' : c.choice === 'decline' ? 'The ACR is kept. You can change this later.' : 'We will remind you in 14 days.', c.choice === 'give' ? 'ok' : 'info'); });

/* ======================= credit transfer ======================= */
screen('st/transfer', { title: 'Credit transfer', role: 'st', nav: 'transfer', render: (p, s) =>
  `${head({ title: 'Credit transfer', sub: 'Applications to have deposited credit counted towards a UTeM programme', actions: '<span class="btn" data-go="#/st/transfer/new">New application</span>' })}
  ${note('A transfer is decided case by case by the receiving faculty. An approved transfer is not an admission, and a refused one changes nothing about your ACR or credentials.')}
  ${table({ id: 'sttrans', empty: 'You have not applied yet. Start a new application when you have deposited credit.', cols: [{ h: 'Application', r: t => `<b>${t.id}</b><span class="s">Applied ${t.applied}</span>` }, { h: 'Programme', r: t => t.programme }, { h: 'Lines', r: t => t.lines.map(l => `${MC.mcName(l.mc)} → ${l.course}`).join('<br>') },
    { h: 'Credits', num: true, r: t => t.lines.reduce((n, l) => n + l.credits, 0) }, { h: 'Status', r: t => tag(t.status, TRK[t.status]) }], rows: s => s.transfers.filter(t => t.who === ME), go: t => '#/st/transfer/' + t.id })}`});

screen('st/transfer/new', { title: 'New transfer application', role: 'st', nav: 'transfer', render: (p, s) => {
  const step = Math.min(5, Math.max(1, +p.step || 1)), adm = s.admissions.find(a => a.who === ME), prog = adm && s.programmes.find(x => x.code === adm.programme), here = n => `#/st/transfer/new?step=${n}`;
  const crumb = [['Credit transfer', '#/st/transfer'], ['New application']], hd = `${head({ crumb, title: 'New transfer application' })}${steps(['Programme', 'Credit', 'Target courses', 'Documents', 'Review and submit'], step)}`;
  const picked = tr.acrs.map(id => s.acrs.find(a => a.id === id)).filter(Boolean);
  if (step === 1) return `${hd}<div class="col" style="max-width:760px">
    ${adm ? panel('Programme', `<div class="choice on"><div><b>${prog.code}: ${prog.name}</b><span class="small muted" style="display:block">Admission recorded: ${adm.programme} intake ${adm.intake}, by ${adm.recorded}</span></div></div>`) : note('No admission is recorded for you. A credit transfer is a separate decision from admission: you cannot apply for a programme you have not been admitted to. Ask Pejabat Pendaftar to record your admission first.', 'warn')}
    ${note('Applying does not admit you, and an approved transfer does not either. Admission is recorded separately by Pejabat Pendaftar.')}
    <div class="row">${adm ? `<span class="btn" data-act="st-tr-prog" data-then="${here(2)}">Continue to credit</span>` : '<span class="btn dis">Continue: no admission recorded</span>'}<span class="btn ter" data-go="#/st/transfer">Cancel</span></div></div>`;
  if (step === 2) { const opts = [];
    myAcrs(s).forEach(a => { const why = acrWhy(a, s);
      opts.push([a.id, `${MC.mcName(a.mc)}, ${a.credits} credits`, `${a.id}${a.deposited ? ', deposited ' + a.deposited : ''}`, why, why && a.status !== 'Deposited' ? '#/st/acr/' + a.id : '']); });
    mine(s).filter(e => /^Completed/.test(e.status) && MC.mc(MC.co(e.cohort).mc).cat === 'MCE').forEach(e => opts.push(['mce' + e.id, MC.mcName(MC.co(e.cohort).mc), 'Micro-Credential Eksekutif', 'MCE carries no credit and cannot be transferred.', '']));
    return `${hd}<form data-form class="col" style="max-width:820px" onsubmit="return false"><div class="col">${opts.map(([id, t, sub, why, go]) => `<label class="choice ${tr.acrs.includes(id) ? 'on' : ''}" style="${why ? 'opacity:.75' : ''}"><input type="checkbox" name="acr" value="${id}" ${why ? 'disabled' : ''} ${tr.acrs.includes(id) ? 'checked' : ''}><div>${t}<span class="small muted" style="display:block">${sub}</span>${why ? `<span class="small" style="display:block;color:var(--err-ink)">Not selectable: ${why} ${go ? L(go, 'Open ACR') : ''}</span>` : ''}</div></label>`).join('')}</div>
    <div class="small muted">Only deposited credit that is Available can be chosen. Badges and certificates cannot be transferred.</div>
    <div class="row"><span class="btn" data-act="st-tr-pick" data-then="${here(3)}">Continue to target courses</span><span class="btn ter" data-go="${here(1)}">Back</span></div></form>`; }
  if (!picked.length) return `${hd}${note('Choose the credit first.', 'warn')}<div><span class="btn" data-go="${here(2)}">Back to credit</span></div>`;
  if (step === 3) return `${hd}<form data-form class="col" style="max-width:760px" onsubmit="return false"><div class="col">${picked.map(a => field(`${MC.mcName(a.mc)} (${a.credits} credits) counts towards`, MC.sel('c_' + a.id, s.courses.map(c => `${c.code} ${c.name}`), (() => { const m = MC.mc(a.mc), k = s.courses.find(c => tr.courses[a.id] ? c.code === tr.courses[a.id] : (m.code ? c.code === m.code : c.code === 'BITU 3973')); return k ? `${k.code} ${k.name}` : undefined; })()), 'The evaluator checks course outcomes one by one. Credit hours alone are not enough.')).join('')}</div>
    <div class="row"><span class="btn" data-act="st-tr-courses" data-then="${here(4)}">Continue to documents</span><span class="btn ter" data-go="${here(2)}">Back</span></div></form>`;
  if (step === 4) return `${hd}<div class="col" style="max-width:760px">${panel('Attached for you', picked.map(a => { const cr = myCreds(s).find(c => c.mc === a.mc), m = MC.mc(a.mc);
      return checks([['', `ACR ${a.id}`, 'Academic Credit Record, with ABC reference ' + a.abcRef], ['', `Digital Credential ${cr ? cr.id : ''}`, cr ? 'Issued ' + cr.issued : ''], ['', `Course outcomes for ${m.name}`, 'From the approved version ' + m.version], ...(m.cat === 'SAMC' ? [['', 'Quality Verification evidence', m.qv && m.qv.status === 'Quality-Verified' ? `Valid to ${m.qv.to}` : 'QV status']] : [])]); }).join('<hr style="border:0;border-top:1px solid var(--hair)">'))}
    <div class="small muted">You cannot remove these: the evaluator needs them to check authenticity and outcomes.</div>
    <div class="row"><span class="btn" data-go="${here(5)}">Continue to review</span><span class="btn ter" data-go="${here(3)}">Back</span></div></div>`;
  const total = picked.reduce((n, a) => n + a.credits, 0);
  return `${hd}<div class="col" style="max-width:820px">${panel('Review', dl([['Programme', `${adm.programme} intake ${adm.intake}`], ...picked.map(a => [MC.mcName(a.mc), `${a.credits} credits → ${tr.courses[a.id] || ''}`]), ['Total credits', total], ['Documents', 'ACR, credential, course outcomes' + (picked.some(a => MC.mc(a.mc).cat === 'SAMC') ? ', QV evidence' : '') + ' (attached)']]))}
    ${note('Submitting sends this to the deputy dean of the receiving faculty. It does not change your ACR, and it does not guarantee approval or admission.')}
    <div class="row"><span class="btn" data-modal="st-tr-submit">Submit application</span><span class="btn ter" data-go="${here(4)}">Back</span></div></div>`; } });
act('st-tr-prog', () => { tr.acrs = []; tr.courses = {}; });
act('st-tr-pick', () => { const v = [...document.querySelectorAll('[name=acr]:checked')].map(i => i.value); if (!v.length) { MC.toast('Choose a credit', 'Select at least one available credit to continue.', 'err'); return false; } tr.acrs = v; });
act('st-tr-courses', c => { tr.acrs.forEach(id => { if (c['c_' + id]) tr.courses[id] = c['c_' + id].split(' ').slice(0, 2).join(' '); }); });
modal('st-tr-submit', { kicker: 'New application', title: 'Submit this transfer application?', confirm: 'Submit', act: 'st-tr-submit', then: c => '#/st/transfer',
  body: () => `<p>The deputy dean of the receiving faculty is told and starts the evaluation. You can follow the status here and upload more evidence if asked.</p><p class="muted">Nothing changes in your ACR or credentials. You cannot start a second application on the same credit while this one is active.</p>` });
act('st-tr-submit', (c, s) => { const n = s.transfers.length + 11; let id = 'TR-2026-' + slot(n); while (s.transfers.some(t => t.id === id)) id += 'a';
  const adm = s.admissions.find(a => a.who === ME), acc = tr.acrs.map(aid => s.acrs.find(x => x.id === aid)), bi = acc.findIndex(a => !a || acrWhy(a, s)), bad = acc[bi];
  if (!adm || bi >= 0 || !tr.acrs.length) { MC.close(); MC.toast('Application not sent', !adm ? 'No admission is recorded for you.' : bad ? `${MC.mcName(bad.mc)} cannot be used: ${acrWhy(bad, s)}` : 'Choose the credit first.', 'err'); MC.go('#/st/transfer/new?step=2'); return false; }
  const lines = tr.acrs.map(aid => { const a = s.acrs.find(x => x.id === aid); return { acr: aid, mc: a.mc, course: tr.courses[aid], credits: a.credits, match: (MATCH[a.mc + '|' + tr.courses[aid]] || ['Not rated', 'Not rated', 'Not rated', 'Not rated']).slice(), decision: '' }; }), total = lines.reduce((x, l) => x + l.credits, 0);
  s.transfers.push({ id, who: ME, name: 'Amir Hakimi', programme: adm.programme, applied: TODAY, status: 'Submitted', lines });
  s.tasks.push({ id: 'T-' + id, area: 'transfer', what: 'Decide credit transfer', ref: id, from: 'Amir Hakimi', check: `${lines.length} line${lines.length > 1 ? 's' : ''}, ${total} credits · single redemption checked`, waiting: 'Today', go: '#/dd/transfer/' + id });
  notif(s, 'dd', `Credit transfer ${id} submitted`, `Amir Hakimi, ${lines.length} line${lines.length > 1 ? 's' : ''}`, '#/dd/transfer/' + id); notif(s, 'st', `Credit transfer ${id} submitted`, 'The deputy dean has been told', '#/st/transfer/' + id); log(s, 'Amir Hakimi', `Applied for credit transfer ${id}`, id);
  tr.acrs = []; tr.courses = {}; MC.toast('Application submitted', `${id} is with the deputy dean. You will be told when it is decided.`); });

screen('st/transfer/:id', { title: 'Transfer application', role: 'st', nav: 'transfer', ex: 'TR-2026-0012', render: (p, s) => {
  const t = s.transfers.find(x => x.id === p.id && x.who === ME); if (!t) return note('This record is not yours or does not exist.', 'err');
  const dec = !/^(Submitted|Under evaluation|More evidence needed)$/.test(t.status), ev = t.status === 'More evidence needed';
  const stage = { 'Submitted': 1, 'Under evaluation': 2, 'More evidence needed': 2 }[t.status] || 4;
  const tl = track([['done', 'Submitted', t.applied], [stage > 1 ? 'done' : 'now', 'Record check', stage > 1 ? 'Done' : 'In progress'], [stage > 2 ? 'done' : stage === 2 ? 'now' : '', 'Equivalence evaluation', ev ? 'Waiting for your evidence' : stage === 2 ? 'In progress' : stage > 2 ? 'Done' : ''], [dec ? 'done' : '', 'Decision', dec ? `${t.status}${t.decidedOn ? ', ' + t.decidedOn : ''}` : 'Pending']]);
  return `${head({ crumb: [['Credit transfer', '#/st/transfer'], [t.id]], title: `Transfer application ${t.id}`, sub: `${t.programme} · applied ${t.applied}`, status: tag(t.status, TRK[t.status]), actions: dec ? `<span class="btn ter" data-go="#/doc/transfer/${t.id}">Decision letter (PDF)</span>` : '' })}
  ${tl}
  ${dec ? note(`<div><b>${t.status}${t.decidedOn ? ' on ' + t.decidedOn : ''}.</b>${t.reason ? ' Reason: ' + t.reason : ''}</div>`, t.status === 'Approved' ? 'ok' : t.status === 'Not approved' ? 'err' : 'warn') : ''}
  <div class="row"><div class="grow col">
    ${panel('Lines', table({ id: 'sttrline' + t.id, nopager: true, cols: [{ h: 'Credit', r: l => `${MC.mcNm(l.mc)}<span class="s">${l.acr}</span>` }, { h: 'Counts towards', r: l => `${l.course} ${(s.courses.find(c => c.code === l.course) || {}).name || ''}` }, { h: 'Credits', num: true, r: l => l.credits },
      { h: 'Decision', r: l => l.decision ? tag(l.decision, TRK[l.decision]) + (l.note ? `<span class="s">${l.note}</span>` : '') : '<span class="muted">Not decided yet</span>' }], rows: t.lines }), '', false)}
    ${ev ? panel('More evidence needed', `<form data-form class="col" onsubmit="return false">${note('The evaluator asked for more evidence before deciding.', 'warn')}${field('Document', '<input class="input" type="file" name="doc" required>', 'PDF or image, up to 10 MB.', true)}${field('Note to the evaluator', area('note', '', '', false))}<div><span class="btn" data-validate data-act="st-tr-evidence" data-id="${t.id}">Upload and send back</span></div></form>`)
      : panel('Send more evidence', `<div class="muted">Upload opens here if the evaluator asks for more evidence. <span class="btn dis sm" style="margin-left:8px">Upload: not requested</span></div>`)}</div>
  <div class="col" style="width:360px">${panel('Documents attached', checks(t.lines.flatMap(l => { const cr = myCreds(s).find(c => c.mc === l.mc), m = MC.mc(l.mc); return [['', `ACR ${l.acr}`, ''], ['', `Credential ${cr ? cr.id : ''}`, ''], ['', `Course outcomes: ${m.name}`, ''], ...(m.cat === 'SAMC' ? [['', 'QV evidence', '']] : [])]; })))}
    ${note('If a line is not approved, your ACR, badges and credentials stay unchanged, and the credit stays as it was. An approved transfer is not an admission.')}</div></div>`; } });
act('st-tr-evidence', (c, s) => { const t = s.transfers.find(x => x.id === c.id); t.status = 'Under evaluation';
  s.tasks.filter(x => x.ref === t.id).forEach(x => { x.done = false; delete x.doneNote; x.check = 'Evidence received ' + TODAY; x.waiting = 'Today'; }); notif(s, 'dd', `More evidence sent: ${t.id}`, 'Amir Hakimi uploaded a document', '#/dd/transfer/' + t.id); log(s, 'Amir Hakimi', `Uploaded more evidence for ${t.id}`, t.id); MC.toast('Evidence sent', 'The evaluator is told and continues the evaluation.'); });

/* ======================= stackable plan ======================= */
screen('st/plan', { title: 'Stackable plan', role: 'st', nav: 'plan', render: (p, s) => {
  const pl = s.plans.find(x => x.who === ME), pw = s.pathways.find(x => x.programme === pl.programme), adm = s.admissions.find(a => a.who === ME), pct = Math.round((pl.total - pl.proposed) / pl.total * 100);
  const rows = myAcrs(s).map(a => { const t = activeTr(s, a.id), m = MC.mc(a.mc); return { a, m, st: a.status !== 'Deposited' ? 'Not deposited yet' : t ? `In ${t.id}, not decided` : a.credit }; });
  return `${head({ title: 'Stackable plan', sub: `${pl.id} · towards ${pl.programme}`, status: tag(pl.status, 'ok') })}
  ${kpis([['Credits proposed', pl.proposed, `of ${pl.total} in the programme`], ['Earned at UTeM', pct + '%', `${pl.total - pl.proposed} credits; at least 30% is required`], ['Residency', `${pl.residency} semesters`, 'At least 1 for a conventional programme'], ['Plan', pl.status, adm ? `Admission recorded: ${adm.intake}` : 'Needs admission recorded']])}
  <div class="row"><div class="grow col">
    ${panel('Credits in your plan', table({ id: 'stplan', nopager: true, cols: [{ h: 'Credit', r: r => MC.mcNm(r.a.mc) + `<span class="s">${r.a.id}</span>` }, { h: 'Credits', num: true, r: r => r.a.credits }, { h: 'Where it stands', r: r => r.st }], rows, go: r => '#/st/acr/' + r.a.id }), '', false)}
    ${panel('Published pathway', pw && pw.status === 'Published' ? table({ id: 'stpath', nopager: true, cols: [{ h: 'Credential', r: r => MC.mcName(r[0]) }, { h: 'Programme course', r: r => r[1] }, { h: 'Credits', num: true, r: r => r[2] }], rows: pw.maps }, '', false) : `<div class="col"><div>${pl.programme} pathway: status <b>${pw ? pw.status : 'none'}</b>. It is not published yet, so no mapping is promised. The plan above was prepared with the deputy dean from your own credit.</div><div>${L('#/public/pathways', 'See published pathways')}</div></div>`)}
  </div><div class="col" style="width:360px">${note('A plan does not award a degree. Each credit still needs its own transfer decision, and a degree is awarded only by the programme after admission and all requirements. Earning 30% at UTeM does not mean 70% can be transferred.')}
    ${panel('Next steps', checks([[adm ? '' : 'warn', 'Admission recorded', adm ? `${adm.programme} ${adm.intake}` : 'Not yet'], ...myAcrs(s).map(a => { const t = activeTr(s, a.id), k = consentKind(a), n = MC.mcName(a.mc), tt = s.transfers.find(x => x.who === ME && x.lines.some(l => l.acr === a.id) && /^(Approved|Partly approved|Not approved)$/.test(x.status));
        return a.status === 'Deposited' ? (t ? ['warn', `Transfer for ${n}`, `${t.id} ${t.status.toLowerCase()}`] : tt ? ['', `Transfer for ${n}`, `${tt.id} ${tt.status.toLowerCase()}`] : a.credit === 'Available' ? ['warn', `Apply to transfer ${n}`, 'Deposited and available, not applied yet'] : ['', `Credit for ${n}`, a.credit || 'Deposited'])
          : k === 'given' ? ['', `Deposit ${n} credit`, 'Consent given. The deputy dean records the deposit.'] : ['warn', `Deposit ${n} credit`, k === 'pending' ? 'Consent needed before it can be used' : `You chose to ${k === 'declined' ? 'decline' : 'postpone'}. You can give consent later.`]; })]) + `<div style="margin-top:8px">${L('#/st/transfer', 'Go to credit transfer')}</div>`)}</div></div>`; } });

/* ======================= seed ======================= */
// A registration waiting for payment, so Pending payment and the retry path are reachable (FL-06b).
MC.seed.enrolments.push({ id: 'E-2026-0701', who: 'amir', cohort: 'LSS-2026-12', status: 'Pending payment', paid: '', ack: true, modules: [['Not started', ''], ['Not started', '']] });

/* ======================= flows ======================= */
flow({ id: 'FL-06', title: 'Find, register and pay', actor: 'Student', summary: 'Public offering page to a paid registration for Cloud Native Development 2027/1',
  steps: [{ go: '#/public/offering/CND-2027-1', say: 'Amir reads the public offering page. Credit is not claimed because QV ends before the cohort does. Click Register.' },
    { go: '#/st/register/CND-2027-1?step=1', say: 'Step 1: eligibility checks pass and he confirms his phone number. Click Continue.' },
    { go: '#/st/register/CND-2027-1?step=2', say: 'Step 2: tick both the recognition statement and the PDPA notice. The statement says credit depends on QV renewal. Click Continue to payment.' },
    { go: '#/st/register/CND-2027-1?step=3', say: 'Step 3: pay with the dummy card, then confirm Pay now in the dialog. Any card number except 4000 0000 0000 0002 succeeds.' },
    { go: '#/st/register/CND-2027-1?step=4', say: 'Step 4: confirmation with the enrolment ID and how to open UCreds. The registration now shows under Registrations and payments.' },
    { go: '#/st/enrolments', say: 'The new registration is listed as Enrolled with its receipt link.' }] });
flow({ id: 'FL-06b', title: 'Payment failure and retry', actor: 'Student', summary: 'A declined card changes nothing; a second try works and charges once',
  steps: [{ go: '#/st/register/LSS-2026-12?step=3', say: 'Amir pays for Lean Six Sigma Yellow Belt. Replace the card number with 4000 0000 0000 0002 and click Pay, then confirm.' },
    { go: '#/st/register/LSS-2026-12?step=3', say: 'The failure shows inside the payment block with a reference. The registration stays Pending payment and nothing was charged. Put back 4242 4242 4242 4242 and click Retry payment.' },
    { go: '#/st/enrolments', say: 'After a successful retry the registration is Enrolled, with one payment only.' }] });
flow({ id: 'FL-09', title: 'Share a credential and have it verified', actor: 'Student, public', summary: 'Share link, a genuine check, a revoked credential and a code that does not exist',
  steps: [{ go: '#/st/credential/MCMS-C-2026-000098', say: 'Amir copies the share link for his Data Structures credential and sees what an employer will see.' },
    { go: '#/public/verify/3HXP-92LD', say: 'An employer opens the link: minimum fields only, status Valid.' },
    { go: '#/public/verify/R7KD-1MQA', say: 'A revoked credential says Revoked and shows no result.' },
    { go: '#/public/verify/ZZZZ-0000', say: 'A code that does not exist says it was not found and tells the employer what to check.' }] });
flow({ id: 'FL-21', title: 'Withdraw from an enrolment', actor: 'Student', summary: 'Withdraw with a reason; achieved modules stay on record',
  steps: [{ go: '#/st/enrolments', say: 'Amir opens his registrations and picks Industrial IoT Fundamentals 2026/2.' },
    { go: '#/st/enrolment/E-2026-0590', say: 'Click Withdraw. The dialog states what stays on his record, that refunds are undecided, and asks for a reason. Confirm to withdraw.' },
    { go: '#/st/enrolments', say: 'The registration now reads Withdrawn. Module 1, achieved on 6 Oct 2026, is still in his achievement record.' }] });
})();
