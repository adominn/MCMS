/* Deputy dean (P2), part B: quality (QV, CQI, periodic review, suspension), ACR and ABC deposits, credit transfer and redemption,
   stackable pathways and plans, reports, traceability. Facts follow srs-fr-inventory 3.10 to 3.16. */
(function () {
const { screen, modal, act, flow, head, panel, table, field, inp, sel, area, note, checks, dl, tabs, kpis, track } = MC;
const HK = 'Hakim Rahman';
const MONS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const fmtD = d => `${d.getDate()} ${MONS[d.getMonth()]} ${d.getFullYear()}`;
// dates are typed as "26 Oct 2026" (shortcut: other free-text forms rely on Date.parse)
// the screen shows dates as DD/MM/YYYY, so typed values arrive in that form too
const PD = v => { const m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(String(v).trim()); return m ? new Date(+m[3], m[2] - 1, +m[1]) : new Date(Date.parse(v)); };
const badDate = v => isNaN(PD(v));
const isoFmt = v => (badDate(v) ? (v || 'the date entered') : fmtD(PD(v)));
const addYears = (t, n) => { const d = new Date(t); d.setFullYear(d.getFullYear() + n); return fmtD(d); };
const daysBetween = (a, b) => Math.round((new Date(b) - new Date(a)) / 864e5);
const minusDays = (t, n) => { const d = new Date(t); d.setDate(d.getDate() - n); return fmtD(d); };
const T = (t, k) => `<span class="tag ${k || ''}">${t}</span>`;
const TK = { Approved: 'ok', 'Partly approved': 'warn', 'Not approved': 'err', 'More evidence needed': 'warn', 'Under evaluation': 'info' };
const nameOf = w => ({ amir: 'Amir Hakimi', wong: 'Wong Kar Seng', leejh: 'Lee Jia Hui' }[w] || w);
const ownerName = o => ({ aina: 'Dr. Aina Zulkifli', rosli: 'Dr. Rosli Hamzah' }[o] || o);
const disBtn = (label, why) => `<span class="btn dis">${label}</span><span class="small muted">${why}</span>`;
const log = (s, who, what, rec) => s.audit.unshift([`${s.today}, 11:20`, who, what, rec]);
const notify = (s, who, n) => (s.notifs[who] = s.notifs[who] || []).unshift(Object.assign({ when: '26 Oct', unread: true }, n));
const finishTask = (s, ref, noteText) => { const t = s.tasks.find(x => x.ref === ref && !x.done); if (t) { t.done = true; t.doneNote = noteText; } };
const wnt = (a, b, c) => `<p>${a}</p><p class="small muted"><b>What does not change:</b> ${b}</p><p class="small muted"><b>Who is told:</b> ${c}</p>`;
const notStarted = (s, c) => /Open|Full/.test(c.status) && new Date(c.start) > new Date(s.today);
const crumbQ = [['Quality, QV and review', '#/dd/quality']];

/* ---------- seed: only new keys and new records ---------- */
if (!MC.seed.acrs.some(a => a.id === 'MCMS-A-2026-000188')) MC.seed.acrs.push({ id: 'MCMS-A-2026-000188', owner: 'wong', mc: 'CND', credits: 2, status: 'Deposited', consent: 'Given 14 Aug 2026', abcRef: 'ABC-MY-2026-0004302', deposited: '3 Sep 2026', expires: '3 Sep 2036', credit: 'Available', confirmed: '10 Aug 2026' });
if (!MC.seed.credentials.some(c => c.id === 'MCMS-C-2026-000230')) MC.seed.credentials.push({ id: 'MCMS-C-2026-000230', code: '4NRB-7TQ9', who: 'Wong Kar Seng', owner: 'wong', mc: 'CND', cohort: 'CND-2026-1', issued: '12 Jul 2026', status: 'Issued', revision: 1, result: 'Pass, 66', qvClaim: 'Quality-Verified, MQF level 4, 2 credits' });
Object.assign(MC.seed, {
  qvRecs: {
    CND: { stage: 'Ready to submit', renewal: true, submitted: null, result: null, evidence: [['Learning outcomes and assessment map', 'Available'], ['Learning time calculation, 80 hours', 'Available'], ['Cohort 2026/1 results: 22 enrolled, 19 achieved', 'Available'], ['Learner feedback summary, cohort 2026/1', 'Available'], ['Quality review CQI-CND-2026-1', 'Available'], ['Employer letter of support', 'Not yet']] },
    ADA: { stage: 'Not applied', evidence: [] },
    CSO: { stage: 'Preparing', evidence: [['Learning outcomes and assessment map', 'Available'], ['Learning time calculation, 80 hours', 'Available'], ['Cohort results', 'Not yet'], ['Learner feedback summary', 'Not yet'], ['Quality review record', 'Not yet']] },
  },
  cqiDetail: {
    'CQI-CND-2026-1': {
      indicators: [['Completion rate', 'at least 80%', '86% (19 of 22)', true], ['Learner satisfaction (1 to 5)', 'at least 4.0', '4.3', true], ['Project pass rate', 'at least 85%', '79%', false], ['Outcome 3 attainment: operate running services', 'at least 70%', '64%', false], ['Feedback response rate', 'at least 50%', '41%', false]],
      issues: [['Outcome 3 attainment below target', 'The pipeline lab ran one week short in the 2026/1 timetable', '6 learners passed the project with marks under 55', 'High'], ['Feedback response low', 'Questionnaire sent only after the final assessment', 'Feedback does not cover 9 of 22 learners', 'Medium']],
      actions: [['Add a second pipeline lab week to the next cohort', 'Dr. Aina Zulkifli', '11 Jan 2027', 'Outcome 3 attainment at least 70%', 'Open'], ['Send the questionnaire at module 3 and again at the end', 'Dr. Aina Zulkifli', '11 Jan 2027', 'Response rate at least 50%', 'In progress'], ['Add a worked incident example to the project brief', 'Dr. Aina Zulkifli', '30 Nov 2026', 'Project pass rate at least 85%', 'Done (effectiveness pending)']],
    },
  },
  periodic: [
    { id: 'CND', last: '12 Nov 2025', due: '6 Nov 2026', status: 'Due', outcome: '' },
    { id: 'DS', last: '14 Jan 2026', due: '30 Nov 2026', status: 'Scheduled', outcome: '' },
    { id: 'LE', last: '20 Jan 2026', due: '20 Jan 2027', status: 'Scheduled', outcome: '' },
    { id: 'LA', last: '14 Sep 2026', due: '14 Mar 2027', status: 'Scheduled', outcome: '' },
  ],
  abcDeposits: [{ id: 'ABC-FTMK-2026-02', count: 28, ref: 'ABC-MY-2026-0004391..4419', date: '20 Oct 2026', status: 'Successful' }],
  abcDisc: [
    { id: 'DSC-004', acr: 'MCMS-A-2026-000071', batch: 'ABC-FTMK-2026-02', issue: 'ABC shows 2 credits; the MCMS record shows 3', raised: '22 Oct 2026', status: 'Open' },
    { id: 'DSC-003', acr: 'MCMS-A-2026-000044', batch: 'ABC-FTMK-2026-01', issue: 'ABC reference missing on the ABC side', raised: '2 Sep 2026', status: 'Resolved', note: 'ABC re-sent the reference on 4 Sep 2026' },
  ],
  pathMatches: { 'PW-BITZ-1': ['High', 'High', 'Partial', 'Partial'] },
});

/* ---------- QV helpers ---------- */
const SAMC = s => s.mcs.filter(m => m.cat === 'SAMC' && m.ptj === 'FTMK');
const cohortsDone = (s, id) => s.cohorts.filter(c => c.mc === id && c.results === 'Verified').length;
const qvDays = (s, m) => (m.qv && m.qv.to ? daysBetween(s.today, m.qv.to) : null);
const alertsOf = (s, m) => { const d = qvDays(s, m); return d === null ? '–' : [180, 90, 30].map(n => (d <= n ? `${n}: sent` : `${n}: ${minusDays(m.qv.to, n)}`)).join(' · '); };
const activeCount = (s, id) => s.cohorts.filter(c => c.mc === id && /Open|Running|Full/.test(c.status)).reduce((a, c) => a + (c.taken || c.enrolled || 0), 0);
const QV_STEPS = ['Preparing', 'Ready to submit', 'Submitted to QVC/MQA', 'Quality-Verified'];
const stageKind = st => (/Quality-Verified/.test(st) ? 'ok' : /Not verified/.test(st) ? 'err' : /Submitted/.test(st) ? 'info' : /Ready/.test(st) ? 'ok' : 'warn');

screen('dd/quality', { title: 'Quality, QV and review', role: 'dd', nav: 'quality', render: (p, s) => {
  const on = p.tab || 'qv';
  return `${head({ title: 'Quality, QV and review', sub: 'QV status of FTMK Stand-alone Micro-Credentials (SAMC), quality reviews to confirm, periodic reviews, and suspension or termination' })}
  <div data-tabs>${tabs([['qv', 'QV of SAMC', SAMC(s).length], ['cqi', 'Quality reviews', s.cqi.filter(c => c.status === 'Submitted').length], ['periodic', 'Periodic review schedule', s.periodic.filter(x => x.status !== 'Reviewed').length], ['susp', 'Suspension and termination']], on)}
  <div data-pane="qv">${table({ id: 'ddqv', size: 10, search: 'Search SAMC', cols: [
    { h: 'SAMC', r: m => MC.mcNm(m.id) }, { h: 'QV status', r: m => T(m.qv ? m.qv.status : 'Not applied', stageKind(m.qv ? m.qv.status : '')) },
    { h: 'Preparation', r: m => (s.qvRecs[m.id] || {}).stage || 'Not applied' }, { h: 'Valid until', r: m => (m.qv && m.qv.to) || '–' },
    { h: 'Days left', num: true, r: m => { const d = qvDays(MC.state, m); return d === null ? '–' : d <= 0 ? `<span style="color:var(--err-ink)">${d} · expired</span>` : d <= 90 ? `<span style="color:var(--err-ink)">${d} · renew soon</span>` : d; } },
    { h: 'Alerts', r: m => `<span class="small">${alertsOf(MC.state, m)}</span>` }, { h: '', r: m => `<a data-go="#/dd/qv/${m.id}">Open</a>` }],
    rows: st => SAMC(st), go: m => `#/dd/qv/${m.id}`, empty: 'No stand-alone micro-credentials.' })}
    <div class="small muted" style="margin-top:8px">Only SAMC have QV. A UMC is covered by its programme accreditation and an MCE carries no credit. QV never means transfer, admission or a place on a pathway.</div></div>
  <div data-pane="cqi">${table({ id: 'ddqcqi', size: 10, cols: [{ h: 'Quality review', k: 'id' }, { h: 'Micro-credential', r: c => MC.mcNm(c.mc) }, { h: 'Cohort', k: 'cohort' }, { h: 'Prepared by', r: c => ownerName(c.owner) },
    { h: 'Status', r: c => T(c.status, c.status === 'Submitted' ? 'info' : c.status === 'Confirmed' ? 'ok' : c.status === 'Returned' ? 'warn' : '') }, { h: '', r: c => `<a data-go="#/dd/cqi/${c.id}">Open</a>` }], rows: st => st.cqi, go: c => `#/dd/cqi/${c.id}` })}</div>
  <div data-pane="periodic">${table({ id: 'ddqper', size: 10, cols: [{ h: 'Micro-credential', r: x => MC.mcNm(x.id) }, { h: 'Last review', k: 'last' }, { h: 'Next due', k: 'due' },
    { h: 'Days', num: true, r: x => daysBetween(MC.state.today, x.due) }, { h: 'Status', r: x => T(x.status, x.status === 'Due' ? 'warn' : x.status === 'Reviewed' ? 'ok' : 'info') }, { h: 'Outcome', r: x => x.outcome || '–' }, { h: '', r: x => `<a data-go="#/dd/review/${x.id}">Open</a>` }],
    rows: st => st.periodic, go: x => `#/dd/review/${x.id}` })}</div>
  <div data-pane="susp">${table({ id: 'ddqsusp', size: 10, cols: [{ h: 'Micro-credential', r: m => MC.mcNm(m.id) }, { h: 'Status', r: m => T(m.status, m.status === 'Approved' ? 'ok' : m.status === 'Suspended' ? 'warn' : 'err') },
    { h: 'Active participants', num: true, r: m => activeCount(MC.state, m.id) }, { h: '', r: m => `<a data-go="#/dd/suspend/${m.id}">Open</a>` }],
    rows: st => st.mcs.filter(m => m.ptj === 'FTMK' && /Approved|Suspended|Terminated/.test(m.status)), go: m => `#/dd/suspend/${m.id}` })}
    <div class="small muted" style="margin-top:8px">Suspension or termination never cancels credentials or ACR already earned.</div></div></div>`; } });

/* ---------- QV record ---------- */
screen('dd/qv/:id', { title: 'QV record', role: 'dd', nav: 'quality', ex: 'CND', render: (p, s) => {
  const m = MC.mc(p.id);
  if (!m || m.cat !== 'SAMC') return `${head({ crumb: crumbQ, title: 'QV record' })}${note('QV applies only to a ${MC.CATNAME.SAMC} (SAMC). This record is not a SAMC.', 'warn')}`;
  const r = s.qvRecs[p.id] || { stage: 'Not applied', evidence: [] }, done = cohortsDone(s, p.id), days = qvDays(s, m);
  const stIdx = QV_STEPS.indexOf(r.stage), eligible = done >= 1 && m.slt >= 40 && !!m.mqf, whyNot = done < 1 ? 'Cannot be Ready: no completed cohort yet. QV needs at least 1 completed cohort with evidence.' : m.slt < 40 ? 'Cannot be Ready: learning time is under the 40-hour minimum for a SAMC.' : 'Cannot be Ready: the MQF level is not set and justified yet.', missing = r.evidence.filter(e => e[1] === 'Not yet').length;
  const prep = r.stage === 'Not applied' || r.stage === 'Not verified' || r.stage === 'Expired';
  const ready = ['Ready to submit', 'Submitted to QVC/MQA', 'Quality-Verified'].includes(r.stage);
  const readyBtn = prep ? `<span class="btn" data-modal="confirm" data-title="Start preparing QV evidence?" data-body="The record moves to Preparing. Nothing is sent to QVC/MQA." data-confirm="Start preparing" data-act="ddQvStart" data-id="${p.id}" data-done="Preparation started">Start preparing evidence</span>`
    : r.stage === 'Preparing' ? (eligible ? `<span class="btn" data-modal="qvReady" data-id="${p.id}">Confirm ready to submit</span>` : disBtn('Confirm ready to submit', whyNot)) : T(r.stage, stageKind(r.stage));
  return `${head({ crumb: crumbQ, title: `QV record: ${m.name}`, sub: `${MC.CATNAME.SAMC} · ${m.credits} credits · ${m.slt} learning hours${r.renewal ? ' · renewal in progress' : ''}`, status: T(m.qv ? m.qv.status : 'Not applied', stageKind(m.qv ? m.qv.status : '')) })}
  ${kpis([['QV status', m.qv ? m.qv.status : 'Not applied'], ['Valid until', (m.qv && m.qv.to) || '–', m.qv && m.qv.from ? `From ${m.qv.from}` : ''], ['Days left', days === null ? '–' : days, days === null ? '' : alertsOf(s, m)], ['Completed cohorts', done, 'Needs at least 1']])}
  ${track(QV_STEPS.map((x, i) => [i < stIdx || (r.stage === 'Quality-Verified' && i === 3) ? 'done' : i === stIdx ? 'now' : '', x, '']))}
  <div class="row">
  <div class="col grow">
    ${panel('1. Readiness for submission', `${checks([[done >= 1 ? '' : 'err', `Completed cohorts with evidence: ${done}`, done >= 1 ? 'Meets the minimum of 1' : 'None yet. A SAMC cannot be Ready without a completed cohort.'],
      [m.slt >= 40 ? '' : 'err', `Learning time ${m.slt} hours`, 'Minimum 40 hours for a SAMC'], ['', 'Stand-alone, provider registered', 'Checked at approval'],
      [m.mqf ? '' : 'warn', m.mqf ? `Proposed credit ${m.credits} and MQF level ${m.mqf} justified` : `Proposed credit ${m.credits}; MQF level not set`, m.mqf ? 'Justification on file' : 'Set and justify the MQF level before submission']])}
      <div class="sep"></div><h3 style="margin:12px 0 4px">Evidence checklist</h3>
      ${r.evidence.length ? checks(r.evidence.map(e => [e[1] === 'Available' ? '' : 'warn', e[0], e[1] === 'Available' ? 'Available' : 'Not yet', ''])) : '<div class="muted">No evidence collected yet.</div>'}
      ${missing && ready ? note(`${missing} evidence item is not yet available. Submit only if QVC/MQA does not need it.`, 'warn') : ''}
      <div class="row" style="gap:12px;align-items:center;margin-top:12px">${readyBtn}</div>`)}
    ${panel('2. Submission package', `<div class="muted" style="margin-bottom:12px">Evidence index, summary and compressed evidence files, generated from MCMS records.</div>
      ${ready ? `<span class="btn ter" data-go="#/doc/qvpack/${p.id}">Open submission package</span>` : disBtn('Open submission package', 'Available once the record is Ready to submit.')}`)}
    ${panel('3. Record submission', r.stage === 'Ready to submit' ? `<div data-form class="col">
        ${field('Date submitted to QVC/MQA', inp('sdate', s.today, '26 Oct 2026', true), 'Write the date as 26 Oct 2026', true)}${field('QVC/MQA reference', inp('sref', '', 'For example QVC/2026/0457', true), '', true)}
        <span class="btn" style="align-self:flex-start" data-modal="qvSubmit" data-validate data-id="${p.id}">Record submission</span></div>`
      : r.submitted ? dl([['Submitted', r.submitted.date], ['QVC/MQA reference', r.submitted.ref], ['Recorded by', r.submitted.by]]) : disBtn('Record submission', 'Needs the record to be Ready to submit first.'))}
    ${panel('4. Record QV result', r.stage === 'Submitted to QVC/MQA' ? `<div data-form class="col">
        ${field('Result', sel('outcome', ['Quality-Verified', 'Not verified']))}${field('QVC/MQA decision reference', inp('ref', '', 'For example QV/2026/1180', true), '', true)}
        <div class="row">${field('Verified credit', inp('credit', m.credits, '', false, 'number'))}${field('MQF level', sel('mqf', ['3', '4', '5', '6'], String(m.mqf || 4)))}</div>
        <div class="row">${field('Valid from', inp('from', '7 Dec 2026', '7 Dec 2026'), 'Required when verified')}${field('Valid until', inp('until', '6 Dec 2029', '6 Dec 2029'), 'At most 3 years after the start')}</div>
        <div class="help err" data-qverr style="display:none"></div>
        <span class="btn" style="align-self:flex-start" data-act="ddQvCheck" data-validate data-id="${p.id}">Record result</span></div>`
      : r.result ? dl([['Result', T(r.result.outcome, r.result.outcome === 'Quality-Verified' ? 'ok' : 'err')], ['Reference', r.result.ref], ['Verified credit', r.result.credit], ['MQF level', r.result.mqf], ['Valid', r.result.outcome === 'Quality-Verified' ? `${r.result.from} to ${r.result.until}` : '–'], ['Recorded by', r.result.by]])
        : disBtn('Record QV result', 'Needs the submission to be recorded first.'))}
  </div>
  <div class="col" style="width:360px">
    ${panel('When QV lapses', `<div class="small">${m.qv && m.qv.to ? `QV for this SAMC ends on ${m.qv.to}. If no renewal result is recorded by then:` : 'If QV ends, is suspended or is withdrawn:'}</div>
      ${checks([['warn', 'The QV claim is removed', 'From the catalogue, new credentials and new ACR'], ['warn', 'New ACR stops', 'No ACR can be prepared for later completions'], ['', 'Earned credentials and deposited ACR stay', 'Nothing already issued is changed'], ['n', 'A task is created for you', 'To renew or withdraw the SAMC']])}
      <div class="small muted" style="margin-top:8px">QV is the sole source for QV claims on the catalogue, credentials and ACR. It never implies transfer, admission or stackable status.</div>`)}
    ${panel('Linked records', `<div class="col" style="gap:6px"><a data-go="#/dd/proposal/${p.id}">Approved micro-credential</a>${s.cqi.find(x => x.mc === p.id) ? `<a data-go="#/dd/cqi/${s.cqi.find(x => x.mc === p.id).id}">Quality review, cohort 2026/1</a>` : ''}${s.periodic.some(x => x.id === p.id) ? `<a data-go="#/dd/review/${p.id}">Periodic review</a>` : ''}${s.cqi.some(x => x.mc === p.id) || s.periodic.some(x => x.id === p.id) ? '' : '<span class="small muted">No quality review or periodic review yet.</span>'}</div>`)}
  </div></div>`; } });

modal('qvReady', { title: 'Confirm ready to submit to QVC/MQA?', kicker: 'QV record', body: (c, s) => wnt(`The record for ${MC.mcName(c.id)} moves from Preparing to Ready to submit. You are confirming the lecturer's evidence is complete.`, 'Nothing is sent to QVC/MQA. The approval and credentials are unchanged.', 'The lecturer'), confirm: 'Confirm ready', act: 'ddQvReady', done: ['Ready to submit', 'The submission package can now be built.'] });
act('ddQvReady', (c, s) => { s.qvRecs[c.id].stage = 'Ready to submit'; log(s, HK, `Confirmed QV ready to submit, ${MC.mcName(c.id)}`, c.id); });
act('ddQvStart', (c, s) => { s.qvRecs[c.id] = Object.assign(s.qvRecs[c.id] || { evidence: [] }, { stage: 'Preparing' }); log(s, HK, `Started QV preparation, ${MC.mcName(c.id)}`, c.id); });
modal('qvSubmit', { title: 'Record the submission to QVC/MQA?', kicker: 'QV record', body: c => `${wnt(`Records that the package was submitted on ${isoFmt(c.sdate)} with reference ${c.sref}. The record moves to Submitted to QVC/MQA.`, 'The current QV validity is unchanged until a result is recorded.', 'The lecturer')}`, confirm: 'Record submission', act: 'ddQvSubmit', done: ['Submission recorded', 'Next: record the QV result when QVC/MQA replies.'] });
act('ddQvSubmit', (c, s) => { if (badDate(c.sdate)) { MC.toast('Not recorded', 'Enter the submission date, for example 26 Oct 2026.', 'err'); return false; } const r = s.qvRecs[c.id]; r.stage = 'Submitted to QVC/MQA'; r.submitted = { date: isoFmt(c.sdate), ref: c.sref, by: HK }; log(s, HK, `Recorded QV submission ${c.sref}, ${MC.mcName(c.id)}`, c.id); });
act('ddQvCheck', (c, s) => {
  const e = document.querySelector('[data-qverr]'), bad = msg => { if (e) { e.textContent = msg; e.style.display = 'block'; } MC.toast('Result not recorded', msg, 'err'); return false; };
  if (c.outcome === 'Quality-Verified') {
    if (!c.from || !c.until || badDate(c.from) || badDate(c.until)) return bad('Enter both the valid-from and valid-until dates, for example 7 Dec 2026.');
    if (PD(c.until) <= PD(c.from)) return bad('Valid until must be after valid from.');
    const max = PD(c.from); max.setFullYear(max.getFullYear() + 3);
    if (PD(c.until) > max) return bad(`Validity is over 3 years. For a start of ${isoFmt(c.from)} the latest end date is ${fmtD(max)}.`);
  }
  if (e) e.style.display = 'none'; MC.open('qvResult', c); return false;
});
modal('qvResult', { title: c => `Record the QV result: ${c.outcome}?`, kicker: 'QV record', body: c => c.outcome === 'Quality-Verified'
    ? wnt(`${MC.mcName(c.id)} becomes Quality-Verified for ${c.credit} credits at MQF level ${c.mqf}, valid ${isoFmt(c.from)} to ${isoFmt(c.until)}. These values become the only source for QV claims.`, 'Nothing is transferred or admitted because of QV.', 'The lecturer')
    : wnt(`${MC.mcName(c.id)} is recorded as Not verified. The current QV stays valid until its end date.`, 'Issued credentials and ACR are unchanged.', 'The lecturer, who can address the findings and prepare again'),
  confirm: 'Record result', act: 'ddQvResult', done: c => ['QV result recorded', c.outcome === 'Quality-Verified' ? 'The catalogue and new credentials now use the new validity.' : 'The record is Not verified.'] });
act('ddQvResult', (c, s) => {
  const m = MC.mc(c.id), r = s.qvRecs[c.id], ok = c.outcome === 'Quality-Verified';
  r.result = { outcome: c.outcome, ref: c.ref, credit: ok ? c.credit : '–', mqf: ok ? c.mqf : '–', from: ok ? isoFmt(c.from) : '', until: ok ? isoFmt(c.until) : '', by: HK };
  r.stage = ok ? 'Quality-Verified' : 'Not verified';
  if (ok) { if (m.qv && m.qv.status === 'Quality-Verified') (m.qvHistory = m.qvHistory || []).push(m.qv); // earlier period stays on record
    m.qv = { status: 'Quality-Verified', from: isoFmt(c.from), to: isoFmt(c.until), daysLeft: daysBetween(s.today, isoFmt(c.until)), ref: c.ref, credit: +c.credit }; m.mqf = +c.mqf; }
  log(s, HK, `Recorded QV result ${c.outcome} (${c.ref}), ${MC.mcName(c.id)}`, c.id);
  notify(s, 'lec', { t: `QV result recorded: ${MC.mcName(c.id)}`, s: ok ? `Quality-Verified to ${isoFmt(c.until)}` : 'Not verified', go: `#/lec/qv/${c.id}` });
});

/* ---------- quality review (CQI) ---------- */
screen('dd/cqi/:id', { title: 'Quality review', role: 'dd', nav: 'quality', ex: 'CQI-CND-2026-1', render: (p, s) => {
  const c = s.cqi.find(x => x.id === p.id), d = s.cqiDetail[p.id];
  if (!c) return `${head({ crumb: crumbQ, title: 'Quality review' })}${note('This quality review does not exist.', 'err')}`;
  const sub = c.status === 'Submitted';
  return `${head({ crumb: crumbQ, title: `Quality review: ${MC.mcName(c.mc)}, ${c.cohort}`, sub: `${c.id} · prepared by ${ownerName(c.owner)}${c.submitted ? ` · submitted ${c.submitted}` : ''}`, status: T(c.status, sub ? 'info' : c.status === 'Confirmed' ? 'ok' : c.status === 'Returned' ? 'warn' : ''),
    actions: sub ? `<span class="btn ter" data-modal="cqiAsk" data-id="${p.id}">Ask for changes</span><span class="btn" data-modal="cqiConfirm" data-id="${p.id}">Confirm quality review</span>` : disBtn('Confirm quality review', c.status === 'Draft' ? 'The lecturer has not submitted this review yet.' : `Already ${c.status.toLowerCase()}.`) })}
  ${c.returnReason ? note(`<b>Returned on ${c.returnedOn}:</b> ${c.returnReason}`, 'warn') : ''}
  ${!d ? note('This review is still a draft. Indicators and actions appear after the lecturer submits it.') : `
  ${panel('Indicators against targets', table({ id: 'ddcqi-ind', nopager: true, cols: [{ h: 'Indicator', k: 0 }, { h: 'Target', k: 1 }, { h: 'Actual', k: 2 }, { h: 'Result', r: x => T(x[3] ? 'Met' : 'Not met', x[3] ? 'ok' : 'err') }], rows: d.indicators }), '', false)}
  <div class="row"><div class="grow">${table({ id: 'ddcqi-iss', title: 'Issues', nopager: true, cols: [{ h: 'Issue', k: 0 }, { h: 'Root cause', k: 1 }, { h: 'Impact', k: 2 }, { h: 'Priority', r: x => T(x[3], x[3] === 'High' ? 'err' : 'warn') }], rows: d.issues })}</div></div>
  ${table({ id: 'ddcqi-act', title: 'Proposed actions', nopager: true, cols: [{ h: 'Action', k: 0 }, { h: 'Owner', k: 1 }, { h: 'Due', k: 2 }, { h: 'Success indicator', k: 3 }, { h: 'Status', r: x => T(x[4], x[4] === 'Closed' ? 'ok' : x[4] === 'Open' ? '' : 'info') + (x[5] ? `<span class="small muted" style="display:block">${x[5]}</span>` : '') },
    { h: '', r: x => (x[4] === 'Done (effectiveness pending)' ? `<a data-modal="cqiClose" data-id="${p.id}" data-i="${d.actions.indexOf(x)}">Close action</a>` : '') }], rows: d.actions })}
  ${note('Confirming records that you accept the review and its action plan. The lecturer carries out each action; you close it after checking it worked (Effective, Partly effective or Not effective). An action that changes outcomes, learning time, credit, assessment or mode goes through a change request, not through this screen.')}`}`; } });
modal('cqiConfirm', { title: 'Confirm this quality review?', kicker: 'Quality review', body: c => wnt(`You confirm ${c.id} and its action plan.`, 'The approved micro-credential and any QV record are unchanged. Actions stay open until their effectiveness is checked.', 'Dr. Aina Zulkifli'), confirm: 'Confirm', act: 'ddCqiConfirm', done: ['Quality review confirmed', 'The lecturer can now start the actions.'], then: '#/dd/quality?tab=cqi' });
act('ddCqiConfirm', (c, s) => { const r = s.cqi.find(x => x.id === c.id); r.status = 'Confirmed'; r.confirmed = s.today; finishTask(s, c.id, 'Confirmed today'); log(s, HK, `Confirmed quality review ${c.id}`, c.id); notify(s, 'lec', { t: `Quality review confirmed: ${c.id}`, s: 'You can start the actions', go: `#/lec/cqi/${c.id}` }); });
modal('cqiAsk', { title: 'Ask the lecturer for changes?', kicker: 'Quality review', reason: 'What needs to change (sent to the lecturer)', body: c => wnt(`${c.id} goes back to the lecturer as Returned.`, 'Nothing is confirmed. The task stays on your queue until it is resubmitted.', 'Dr. Aina Zulkifli, with your reason'), confirm: 'Send back', act: 'ddCqiAsk', done: ['Sent back', 'The lecturer has your reason.', 'info'], then: '#/dd/quality?tab=cqi' });
act('ddCqiAsk', (c, s) => { const r = s.cqi.find(x => x.id === c.id); r.status = 'Returned'; r.returnReason = c.reason; r.returnedOn = s.today; log(s, HK, `Asked for changes to quality review ${c.id}: ${c.reason}`, c.id); notify(s, 'lec', { t: `Quality review returned: ${c.id}`, s: c.reason, go: `#/lec/cqi/${c.id}` }); });
modal('cqiClose', { title: 'Close this action?', kicker: 'Quality review', body: (c, s) => `${wnt(`"${s.cqiDetail[c.id].actions[+c.i][0]}" is closed with your effectiveness finding.`, 'The quality review, the approval and any QV record are unchanged.', 'Dr. Aina Zulkifli')}${field('Was the action effective?', sel('eff', ['Effective', 'Partly effective', 'Not effective']))}`, confirm: 'Close action', act: 'ddCqiClose', done: ['Action closed', 'The effectiveness finding is on the quality review.'] });
act('ddCqiClose', (c, s) => { const x = s.cqiDetail[c.id].actions[+c.i]; x[4] = 'Closed'; x[5] = c.eff; log(s, HK, `Closed quality action "${x[0]}": ${c.eff}`, c.id); notify(s, 'lec', { t: `Quality action closed: ${c.id}`, s: c.eff, go: `#/lec/cqi/${c.id}` }); });

/* ---------- periodic review ---------- */
const OUTCOMES = [['Continue', 'Continue', 'No change. The next review is scheduled in 12 months.'], ['Continue with minor CQI', 'Continue with minor CQI', 'The offering continues. The lecturer is asked to act on small quality improvements.'],
  ['Content or assessment review', 'Content or assessment review', 'The lecturer reviews the content or assessment and sends any change through a change request. The offering continues.'], ['Review and re-approval', 'Review and re-approval', 'The micro-credential needs a new version approved by the Senate before it continues unchanged.'],
  ['Consider suspension or termination', 'Consider suspension or termination', 'Takes you to the suspension screen to decide. Nothing is suspended by recording this outcome.']];
const isSusp = o => /suspension/i.test(o || '');
screen('dd/review/:id', { title: 'Periodic review', role: 'dd', nav: 'quality', ex: 'DS', render: (p, s) => {
  const x = s.periodic.find(r => r.id === p.id), m = MC.mc(p.id);
  if (!x || !m) return `${head({ crumb: crumbQ, title: 'Periodic review' })}${note('No periodic review is scheduled for this micro-credential.', 'warn')}`;
  const cos = s.cohorts.filter(c => c.mc === p.id && c.results === 'Verified'), enr = cos.reduce((a, c) => a + c.enrolled, 0), ach = cos.reduce((a, c) => a + (c.achieved || 0), 0);
  const cq = s.cqi.filter(c => c.mc === p.id), reviewed = x.status === 'Reviewed';
  return `${head({ crumb: crumbQ, title: `Periodic review: ${m.name}`, sub: `${m.cat} · version ${m.version} · ${ownerName(m.owner)}`, status: T(x.status, x.status === 'Due' ? 'warn' : reviewed ? 'ok' : 'info') })}
  ${kpis([['Last review', x.last], ['Next due', x.due, `${daysBetween(s.today, x.due)} days; the lecturer and you were reminded`], ['Completed cohorts', cos.length, `${ach} of ${enr} learners achieved${enr ? ` (${Math.round(ach / enr * 100)}%)` : ''}`], ['Quality reviews', cq.length, cq.map(c => c.status).join(', ') || 'None']])}
  <div class="row"><div class="col grow">
    ${reviewed ? panel('Outcome recorded', dl([['Outcome', T(x.outcome, isSusp(x.outcome) ? 'warn' : 'ok')], ['Reviewed on', x.reviewedOn], ['By', HK], ['Note', x.note || '–'], ['Next review', isSusp(x.outcome) ? 'Decided after the suspension screen' : x.due]]) + (isSusp(x.outcome) ? `<div style="margin-top:12px"><a data-go="#/dd/suspend/${p.id}">Open the suspension screen</a></div>` : ''))
      : panel('Outcome of this review', `<div data-form class="col"><div class="field"><label class="req">Outcome</label>${MC.radios('outcome', OUTCOMES, '')}<div class="help err" data-rverr style="display:none">Choose an outcome.</div></div>${field('Note for the record', area('note', '', 'What the evidence showed'))}
        <span class="btn" style="align-self:flex-start" data-act="ddReviewCheck" data-id="${p.id}">Record outcome</span></div>`)}
  </div><div style="width:360px">${panel('Evidence used', checks(cos.map(c => ['', `${c.name}`, `${c.enrolled} enrolled, ${c.achieved || 0} achieved`]).concat(cq.map(c => [c.status === 'Confirmed' ? '' : 'warn', c.id, c.status])).concat(m.qv ? [['n', `QV ${m.qv.status}`, m.qv.to ? `Valid to ${m.qv.to}` : '']] : [])) + '<div class="small muted" style="margin-top:8px">Suspension or termination never cancels credentials or ACR already earned.</div>')}</div></div>`; } });
act('ddReviewCheck', (c, s) => { if (!c.outcome) { const e = document.querySelector('[data-rverr]'); if (e) e.style.display = 'block'; MC.toast('Choose an outcome', 'Pick one outcome before recording the review.', 'err'); return false; } MC.open('reviewOutcome', c); return false; });
modal('reviewOutcome', { title: c => `Record the review outcome: ${c.outcome}?`, kicker: 'Periodic review', body: c => wnt(`${MC.mcName(c.id)} periodic review is recorded as "${c.outcome}".${isSusp(c.outcome) ? ' You will go on to the suspension screen to decide. Nothing is suspended yet.' : ' The next review is scheduled 12 months from today.'}`, 'The approval, credentials and ACR are unchanged.', 'The lecturer'), confirm: 'Record outcome', act: 'ddReviewOutcome',
  done: (c, s) => ['Review outcome recorded', isSusp(c.outcome) ? 'Now decide on the suspension.' : `Next review scheduled for ${s.periodic.find(r => r.id === c.id).due}.`], then: c => (isSusp(c.outcome) ? `#/dd/suspend/${c.id}` : '#/dd/quality?tab=periodic') });
act('ddReviewOutcome', (c, s) => { const x = s.periodic.find(r => r.id === c.id); if (!c.outcome) { MC.toast('Not recorded', 'Choose an outcome first.', 'err'); return false; }
  (x.history = x.history || []).push({ on: x.last, outcome: x.outcome || '' }); x.last = s.today; if (!isSusp(c.outcome)) x.due = addYears(s.today, 1);
  Object.assign(x, { status: 'Reviewed', outcome: c.outcome, reviewedOn: s.today, note: c.note || '' }); log(s, HK, `Periodic review outcome "${c.outcome}", ${MC.mcName(c.id)}`, c.id); notify(s, 'lec', { t: `Periodic review outcome: ${MC.mcName(c.id)}`, s: c.outcome, go: '#/lec/home' }); });

/* ---------- suspension and termination ---------- */
screen('dd/suspend/:id', { title: 'Suspension or termination', role: 'dd', nav: 'quality', ex: 'CND', render: (p, s) => {
  const m = MC.mc(p.id); if (!m) return `${head({ crumb: crumbQ, title: 'Suspension or termination' })}${note('Micro-credential not found.', 'err')}`;
  const n = activeCount(s, p.id), live = s.cohorts.filter(c => c.mc === p.id && /Open|Running|Full/.test(c.status));
  const people = s.enrolments.filter(e => live.some(c => c.id === e.cohort) && !/Withdrawn|Cancelled|Pending/.test(e.status)).map(e => [nameOf(e.who), e.id, live.find(c => c.id === e.cohort).name]);
  const done = /Suspended|Terminated/.test(m.status);
  return `${head({ crumb: crumbQ, title: `Suspend or terminate: ${m.name}`, sub: `${m.cat} · ${ownerName(m.owner)} · you decide as the configured authority`, status: T(m.status, m.status === 'Approved' ? 'ok' : m.status === 'Suspended' ? 'warn' : 'err') })}
  ${done ? note(`<b>${m.status} on ${m.suspended ? m.suspended.date : s.today}.</b> ${m.suspended ? `Transition plan: ${m.suspended.plan}` : ''}`, 'warn') : ''}
  <div class="row"><div class="col grow">
    ${people.length ? table({ id: 'ddsusp-part', title: `Active participants (${n})`, size: 5, cols: [{ h: 'Participant', k: 0 }, { h: 'Enrolment', k: 1 }, { h: 'Offering', k: 2 }], rows: people }) : panel(`Active participants (${n})`, n ? `<div class="small">The list of the ${n} participants is held by the registry and is not itemised here. Each one is told by email when you record the decision.</div>` : '<div class="small">No participants are registered or learning now. Only the notice to the lecturer is needed.</div>')}
    ${panel('Offerings affected', checks(live.map(c => ['warn', `${c.name}: ${c.status}`, `${c.taken || c.enrolled || 0} participants · ${c.start} to ${c.end} · ${notStarted(s, c) ? 'will be cancelled' : 'continues under the transition plan'}`]).concat(live.length ? [] : [['n', 'No open or running offering', '']])))}
    ${m.status === 'Suspended' ? panel('Next step', `<div class="small muted" style="margin-bottom:12px">The micro-credential is suspended. Lift the suspension to reopen it, or terminate it.</div><div class="row" style="gap:8px"><span class="btn ter" data-modal="confirm" data-title="Lift the suspension?" data-body="${m.name} returns to Approved. Offerings already cancelled stay cancelled; the lecturer can open new ones. The lecturer is told." data-reason="Why the suspension is lifted (kept in the audit trail)" data-confirm="Lift suspension" data-act="ddLift" data-id="${p.id}" data-done="Suspension lifted|The micro-credential is Approved again.">Lift suspension</span>
      <span class="btn danger ter" data-modal="confirm" data-title="Terminate this micro-credential?" data-body="${m.name} ends for good. Credentials, badges and ACR already earned stay valid. The lecturer is told." data-danger="1" data-reason="Why it is terminated (kept in the audit trail)" data-confirm="Terminate" data-act="ddTerminate" data-id="${p.id}" data-done="Terminated|The lecturer has been told.">Terminate</span></div>`) : ''}
    ${done ? '' : panel('Decision', `<div data-form class="col">${MC.radios('action', [['Suspend', 'Suspend', 'Pauses the micro-credential. It can be reinstated later.'], ['Terminate', 'Terminate', 'Ends the micro-credential for good.']], 'Suspend')}
      ${field('Effective from', inp('eff', s.today, '26 Oct 2026', true), 'Write the date as 26 Oct 2026', true)}
      ${field('Transition plan for current participants', area('plan', '', 'For example: learners in the open cohort may finish in the 2027/1 cohort or move to the next intake at no cost', true), 'Required before a final decision. Include options, the period and the effect on credentials.', true)}
      <span class="btn danger" style="align-self:flex-start" data-modal="suspendConfirm" data-validate data-id="${p.id}">Record decision</span></div>`)}
  </div><div class="col" style="width:380px">
    ${panel('Notice to participants (preview)', `<div style="margin-bottom:8px"><a data-go="#/doc/suspend/${p.id}">Open the notice as a document</a></div><div class="small" style="line-height:20px"><b>Subject: Change to ${m.name}</b><br><br>Dear participant,<br><br>UTeM will ${done && m.status === 'Terminated' ? 'terminate' : 'suspend or terminate'} <b>${m.name}</b> from the effective date set by the deputy dean (Academic), FTMK. Your options, the period you have and what it means for any credential or recognition are in the transition plan below the decision.<br><br>Credentials, badges and Academic Credit Records you have already earned stay valid and are not changed.<br><br>CODL, codl@utem.edu.my</div>`)}
    ${panel('What stays as it is', checks([['', 'Earned credentials stay valid', 'Issued credentials are not revoked'], ['', 'ACR and deposited credit stay', 'Credit in ABC is unchanged'], ['n', 'No new registrations', 'From the effective date']]))}
  </div></div>`; } });
modal('suspendConfirm', { title: c => `${c.action} ${MC.mcName(c.id)}?`, kicker: 'Danger: suspension or termination', danger: true, reason: true, body: (c, s) => { const cancel = s.cohorts.filter(k => k.mc === c.id && notStarted(s, k)).map(k => k.name), keep = s.cohorts.filter(k => k.mc === c.id && k.status === 'Running');
    return wnt(`${c.action === 'Terminate' ? 'Terminates' : 'Suspends'} ${MC.mcName(c.id)} from ${isoFmt(c.eff)}. New registrations stop.${cancel.length ? ` Offerings not yet started are cancelled: ${cancel.join(', ')}.` : ''}${keep.length ? ` Running offerings continue under the plan: ${keep.map(k => k.name).join(', ')}.` : ''} Current participants get the transition plan: "${c.plan}".`, 'Credentials, badges and ACR already earned stay valid.', `All ${activeCount(s, c.id)} active participants and the lecturer, by email`); }, confirm: c => `${c.action} now`, act: 'ddSuspend', done: c => [`${c.action === 'Terminate' ? 'Terminated' : 'Suspended'}`, 'Participants have been notified with the transition plan.', 'warn'], then: '#/dd/quality?tab=susp' });
act('ddSuspend', (c, s) => { const m = MC.mc(c.id), st = c.action === 'Terminate' ? 'Terminated' : 'Suspended';
  if (badDate(c.eff)) { MC.toast('Not recorded', 'Enter the effective date, for example 26 Oct 2026.', 'err'); return false; }
  m.status = st; m.suspended = { date: isoFmt(c.eff), plan: c.plan, reason: c.reason };
  const hit = s.cohorts.filter(k => k.mc === c.id && /Open|Running|Full/.test(k.status)); hit.filter(k => notStarted(s, k)).forEach(k => { k.status = 'Cancelled'; k.cancelReason = `${st} on ${isoFmt(c.eff)}`; }); // Running offerings stay, under the plan
  log(s, HK, `${st} ${MC.mcName(c.id)} from ${isoFmt(c.eff)}: ${c.reason}`, c.id); notify(s, 'lec', { t: `${st}: ${MC.mcName(c.id)}`, s: 'Transition plan sent to participants', go: '#/lec/home' });
  if (s.enrolments.some(e => e.who === 'amir' && hit.some(k => k.id === e.cohort))) notify(s, 'st', { t: `Change to ${m.name}`, s: 'Your earned credentials stay valid. Read the transition plan.', go: '#/st/home' }); });
act('ddLift', (c, s) => { const m = MC.mc(c.id); (m.suspendedHistory = m.suspendedHistory || []).push(Object.assign({ liftedOn: s.today, liftReason: c.reason }, m.suspended)); delete m.suspended; m.status = 'Approved';
  log(s, HK, `Lifted suspension of ${m.name}: ${c.reason}`, c.id); notify(s, 'lec', { t: `Suspension lifted: ${m.name}`, s: 'The micro-credential is Approved again', go: '#/lec/home' }); });
act('ddTerminate', (c, s) => { const m = MC.mc(c.id); m.status = 'Terminated'; m.suspended = Object.assign(m.suspended || {}, { terminatedOn: s.today, reason: c.reason, date: (m.suspended || {}).date || s.today });
  log(s, HK, `Terminated ${m.name} after suspension: ${c.reason}`, c.id); notify(s, 'lec', { t: `Terminated: ${m.name}`, s: 'Earned credentials stay valid', go: '#/lec/home' }); });

/* ---------- ACR and ABC: list ---------- */
const NAMES = ['Muhammad Danish Roslan', 'Kavitha Raman', 'Nur Iman Azlan', 'Tan Wei Ming', 'Chong Mei Ling', 'Siti Khadijah Omar', 'Haziq Firdaus', 'Arjun Pillai', 'Nurul Ain Kamarudin', 'Daniel Ong', 'Wong Kar Seng', 'Fatimah Zahra Idris', 'Rajesh Kumar', 'Ahmad Faiz Rahim', 'Grace Lim Hui Min', 'Aiman Hakim Zulkifli', 'Lee Jia Hui', 'Priya Devi', 'Hafiz Rosli', 'Nadia Sofea', 'Jason Teoh', 'Syafiqah Ali', 'Mohd Irfan Musa', 'Ooi Kah Wai', 'Balqis Hani', 'Ravi Shankar', 'Liyana Aziz', 'Ng Pei Shan', 'Zulaikha Noor'];
const queueRows = s => [
  ...s.acrBatches.filter(b => b.status === 'Verified').map(b => ({ id: b.id, what: `${MC.mcName(b.mc)}, ${b.cohort}`, kind: 'Cohort batch', n: b.consented, state: 'Ready to export', go: `#/dd/deposit/${b.id}` })),
  ...s.acrs.filter(a => /^(Verified|Correction needed)$/.test(a.status) && /^Given/.test(a.consent)).map(a => ({ id: a.id, what: `${MC.mcName(a.mc)}, ${nameOf(a.owner)}`, kind: 'Single ACR', n: 1, state: `Consent ${a.consent.toLowerCase()}`, go: `#/dd/deposit/${a.id}` })),
  ...s.acrBatches.filter(b => b.count && /^Sent/.test(b.status)).map(b => ({ id: b.id, what: `Sent to ABC on ${b.sent}`, kind: 'Export sent', n: b.count, state: b.status, go: `#/dd/deposit/${b.id}` }))];
const depositedRows = s => [
  ...s.abcDeposits.map(d => ({ id: d.id, n: d.count, ref: d.ref, date: d.date, st: d.status, go: `#/dd/deposit/${d.id}` })),
  ...s.acrBatches.filter(b => b.status === 'Deposited').map(b => ({ id: b.id, n: b.count || b.consented, ref: b.abcRef, date: b.depositedOn, st: 'Successful', go: `#/dd/deposit/${b.id}` })),
  ...s.acrs.filter(a => a.status === 'Deposited').map(a => ({ id: a.id, n: 1, ref: a.abcRef, date: a.deposited, st: 'Successful', go: `#/dd/deposit/${a.id}` }))];
screen('dd/acr', { title: 'ACR and ABC deposits', role: 'dd', nav: 'acr', render: (p, s) => {
  const on = p.tab || 'confirm', waiting = s.acrs.filter(a => a.status === 'Verified' && !a.consent).length + s.acrBatches.filter(b => b.status === 'ACR prepared').reduce((a, b) => a + b.achieved - b.consented, 0);
  return `${head({ title: 'ACR and ABC deposits', sub: 'Confirm Academic Credit Records, then record deposits made in the Academic Bank of Credit. An ACR is not a deposit and a deposit is storage, not a transfer.' })}
  <div data-tabs>${tabs([['confirm', 'Confirm ACR', s.acrBatches.filter(b => b.status === 'ACR prepared').length], ['queue', 'Deposit queue', queueRows(s).length], ['deposited', 'Deposited', depositedRows(s).length], ['disc', 'Discrepancies', s.abcDisc.filter(d => d.status === 'Open').length]], on)}
  <div data-pane="confirm">${table({ id: 'ddacr-confirm', size: 10, cols: [{ h: 'Batch', k: 'id' }, { h: 'Micro-credential', r: b => MC.mcNm(b.mc) }, { h: 'Cohort', k: 'cohort' }, { h: 'Achieved', num: true, k: 'achieved' }, { h: 'Consented to ABC', num: true, k: 'consented' }, { h: 'Status', r: b => T(b.status, 'info') }, { h: '', r: b => `<a data-go="#/dd/acr/${b.id}">Open</a>` }],
    rows: st => st.acrBatches.filter(b => b.status === 'ACR prepared'), go: b => `#/dd/acr/${b.id}`, empty: 'No ACR batch is waiting for confirmation.' })}</div>
  <div data-pane="queue">${note(`Only confirmed ACR with the learner's consent appear here. ${waiting} ACR are confirmed or prepared but wait for consent and stay out of the queue.`)}
    ${table({ id: 'ddacr-queue', size: 10, cols: [{ h: 'Record', k: 'id' }, { h: 'What', k: 'what' }, { h: 'Type', k: 'kind' }, { h: 'ACR', num: true, k: 'n' }, { h: 'State', k: 'state' }, { h: '', r: q => `<a data-go="${q.go}">Open</a>` }], rows: queueRows, go: q => q.go, empty: 'Nothing is waiting for deposit.' })}</div>
  <div data-pane="deposited">${table({ id: 'ddacr-dep', size: 10, cols: [{ h: 'Record', k: 'id' }, { h: 'ACR', num: true, k: 'n' }, { h: 'ABC reference', k: 'ref' }, { h: 'Recorded', k: 'date' }, { h: 'Status', r: d => T(d.st, d.st === 'Successful' ? 'ok' : 'warn') }], rows: depositedRows, go: d => d.go })}</div>
  <div data-pane="disc">${table({ id: 'ddacr-disc', size: 10, cols: [{ h: 'Case', k: 'id' }, { h: 'ACR', k: 'acr' }, { h: 'Batch', k: 'batch' }, { h: 'Issue', k: 'issue' }, { h: 'Raised', k: 'raised' }, { h: 'Status', r: d => T(d.status, d.status === 'Open' ? 'warn' : 'ok') },
    { h: '', r: d => d.status === 'Open' ? `<a data-modal="discResolve" data-id="${d.id}">Resolve</a>` : `<span class="small muted">${d.note || ''}</span>` }], rows: st => st.abcDisc, empty: 'No discrepancies.' })}</div></div>`; } });
modal('discResolve', { title: 'Mark this discrepancy resolved?', kicker: 'ABC discrepancy', reason: 'How it was resolved (kept in the audit trail)', body: c => wnt(`${c.id} is closed.`, 'The ACR and its credit status are unchanged.', 'No one; it is recorded in the audit trail'), confirm: 'Mark resolved', act: 'ddDiscResolve', done: ['Discrepancy resolved', 'Recorded in the audit trail.'] });
act('ddDiscResolve', (c, s) => { const d = s.abcDisc.find(x => x.id === c.id); d.status = 'Resolved'; d.note = c.reason; log(s, HK, `Resolved ABC discrepancy ${c.id}: ${c.reason}`, d.acr); });

/* ---------- ACR batch ---------- */
// shortcut: learners in the seed are few, so the roster is the real issued credentials plus generated achievers (never the one failed learner); ACR number follows the credential number
const learnerRows = (b, s) => {
  const real = s.credentials.filter(c => c.cohort === b.cohort).map(c => { const a = s.acrs.find(x => x.owner === c.owner && x.mc === b.mc); return { n: c.who, cred: c.id, acr: a ? a.id : c.id.replace('-C-', '-A-'), has: !!a, credits: MC.mc(b.mc).credits, cst: c.status, consent: a && /^Given/.test(a.consent) ? a.consent : 'Not yet' }; });
  const names = NAMES.filter(x => x !== 'Arjun Pillai' && !real.some(r => r.n === x)), base = [...b.cohort].reduce((a, ch) => a + ch.charCodeAt(0), 0) * 100, fill = Math.max(0, b.achieved - real.length);
  const notYet = Math.max(0, b.achieved - b.consented - real.filter(r => r.consent === 'Not yet').length), miss = new Set(); for (let k = 1; k <= notYet; k++) miss.add(Math.floor(k * fill / (notYet + 1)));
  return real.concat(Array.from({ length: fill }, (_, i) => { const no = String(base + i).padStart(6, '0'); return { n: names[i % names.length], cred: `MCMS-C-2026-${no}`, acr: `MCMS-A-2026-${no}`, has: false, credits: MC.mc(b.mc).credits, cst: 'Issued', consent: miss.has(i) ? 'Not yet' : `Given ${3 + (i % 20)} Jul 2026` }; }));
};
screen('dd/acr/:id', { title: 'Confirm ACR', role: 'dd', nav: 'acr', ex: 'ACR-DS-2026-1', render: (p, s) => {
  const b = s.acrBatches.find(x => x.id === p.id), crumb = [['ACR and ABC deposits', '#/dd/acr']];
  if (!b || !b.mc) return `${head({ crumb, title: 'Confirm ACR' })}${note('This ACR batch does not exist.', 'err')}`;
  const m = MC.mc(b.mc), co = MC.co(b.cohort), qd = qvDays(s, m), prepared = b.status === 'ACR prepared';
  const typeOk = m.cat === 'UMC' || (m.cat === 'SAMC' && qd !== null && qd > 0), pass = co.results === 'Verified' && typeOk;
  const rows = learnerRows(b, s).map(r => Object.assign(r, { ok: co.results === 'Verified' && typeOk && /Issued|Corrected/.test(r.cst) })), given = rows.filter(r => r.consent !== 'Not yet').length;
  return `${head({ crumb, title: `Confirm ACR: ${m.name}, ${b.cohort}`, sub: `Batch ${b.id} · ${m.cat} · ${m.credits} credits each`, status: T(b.status, prepared ? 'info' : 'ok'),
    actions: prepared ? (pass ? `<span class="btn" data-modal="acrConfirm" data-id="${b.id}">Confirm ${b.achieved} ACR</span>` : disBtn('Confirm ACR', 'A failing eligibility check blocks confirmation.')) : disBtn('Confirm ACR', `Already ${b.status.toLowerCase()}${b.confirmedOn ? ` on ${b.confirmedOn}` : ''}.`) })}
  ${kpis([['Achieved', b.achieved], ['Consented to ABC', given, 'Enter the deposit queue after you confirm'], ['Not yet consented', rows.length - given, 'Stay out of the queue'], ['Credits per ACR', m.credits]])}
  <div class="row"><div class="grow">${table({ id: 'ddacr-learners', size: 10, search: 'Search learners', title: 'ACR per learner', cols: [{ h: 'Learner', k: 'n' }, { h: 'ACR', r: r => (r.has ? `<a data-go="#/doc/acr/${r.acr}"><code>${r.acr}</code></a>` : `<code>${r.acr}</code>`) }, { h: 'Credits', num: true, k: 'credits' }, { h: 'Eligibility', r: r => T(r.ok ? 'Pass' : 'Fail', r.ok ? 'ok' : 'err') },
    { h: 'Consent to ABC', r: r => (r.consent === 'Not yet' ? T('Not yet', 'warn') : T(r.consent, 'ok')) }, { h: 'After you confirm', r: r => (r.consent === 'Not yet' ? 'Waits for consent' : 'Enters deposit queue') }], rows })}</div>
  <div class="col" style="width:360px">${panel('ACR eligibility checks', `<div class="small muted" style="margin-bottom:8px">Each ACR needs verified results, an issued credential, valid QV for a SAMC, and credit equal to the approved credit.</div>` + checks([[co.results === 'Verified' ? '' : 'err', 'Results verified', co.results === 'Verified' ? `${b.cohort} verified` : `${b.cohort} results are ${co.results}`], [rows.every(r => /Issued|Corrected/.test(r.cst)) ? '' : 'err', 'Credentials issued', `${rows.filter(r => /Issued|Corrected/.test(r.cst)).length} of ${rows.length} credentials issued to achievers`],
      [typeOk ? '' : 'err', m.cat === 'SAMC' ? `QV valid (${m.qv.status}, to ${m.qv.to})` : 'Accredited UMC', m.cat === 'SAMC' ? `${qd} days left` : 'Eligible for ACR'], ['', `Credit equals the approved ${m.credits}`, 'Matches the approved record'], ['', 'No earlier ACR for these credentials', '']]))}
    ${note('Never eligible for ACR: an MCE, a SAMC without valid QV, a single module, a badge or a certificate. This batch contains none of them.')}
    ${note('Confirming checks the generated ACR. It is not a deposit and nothing is sent to ABC. A deposit is storage, not a credit transfer.')}</div></div>`; } });
modal('acrConfirm', { title: c => { const b = MC.state.acrBatches.find(x => x.id === c.id); return `Confirm ${b.achieved} ACR?`; }, kicker: 'ACR confirmation', body: (c, s) => { const b = s.acrBatches.find(x => x.id === c.id);
  return wnt(`${b.achieved} ACR for ${MC.mcName(b.mc)}, ${b.cohort} become confirmed. The ${b.consented} with consent enter the deposit queue; the other ${b.achieved - b.consented} wait for the learner's consent.`, 'This is not a deposit. Nothing is sent to ABC and no credit is transferred.', 'Learners are told only when you record the deposit'); }, confirm: c => `Confirm ${MC.state.acrBatches.find(x => x.id === c.id).achieved} ACR`, act: 'ddAcrConfirm', done: ['ACR verified', 'Consented ACR are now in the deposit queue.'], then: '#/dd/acr?tab=queue' });
act('ddAcrConfirm', (c, s) => { const b = s.acrBatches.find(x => x.id === c.id); b.status = 'Verified'; b.confirmedOn = s.today; finishTask(s, c.id, `${b.achieved} ACR verified`); log(s, HK, `Confirmed ${b.achieved} ACR, ${MC.mcName(b.mc)} ${b.cohort}`, c.id); });

/* ---------- ABC deposit ---------- */
const depTarget = (id, s) => { const a = s.acrs.find(x => x.id === id); if (a) return { k: 'acr', a }; const b = s.acrBatches.find(x => x.id === id); if (b) return { k: b.count ? 'sent' : 'batch', b }; const d = s.abcDeposits.find(x => x.id === id); return d ? { k: 'done', d } : null; };
screen('dd/deposit/:id', { title: 'Record ABC deposit', role: 'dd', nav: 'acr', ex: 'ABC-FTMK-2026-03', render: (p, s) => {
  const t = depTarget(p.id, s), crumb = [['ACR and ABC deposits', '#/dd/acr?tab=queue']];
  if (!t) return `${head({ crumb, title: 'Record ABC deposit' })}${note('This deposit record does not exist.', 'err')}`;
  let n, label, finished, blockWhy = '', summary = null, extra = '';
  if (t.k === 'acr') { const a = t.a; n = 1; label = `${MC.mcName(a.mc)}, ${nameOf(a.owner)}`; finished = a.status === 'Deposited';
    if (!finished && !/^Given/.test(a.consent)) blockWhy = a.consent ? `Consent is ${a.consent.toLowerCase()}. Only consented ACR can be deposited.` : `${nameOf(a.owner)} has not given consent. Only consented ACR can be deposited.`;
    if (finished) summary = [['ABC reference', a.abcRef], ['Deposited', a.deposited], ['Credit valid until', a.expires], ['Credit status', T(a.credit, a.credit === 'Available' ? 'ok' : 'info')]];
    if (!finished && !blockWhy && !/^(Verified|Correction needed)$/.test(a.status)) blockWhy = `The ACR is ${a.status}. Only Verified ACR can be deposited.`;
    extra = checks([[/^(Verified|Deposited)$/.test(a.status) || a.status === 'Correction needed' ? '' : 'err', 'ACR verified', a.confirmed ? `Verified ${a.confirmed}` : a.status], [/^Given/.test(a.consent) ? '' : 'err', 'Consent', a.consent || 'Not given yet'], [finished ? '' : 'n', 'Deposit', finished ? 'Recorded' : 'Not yet recorded']]); }
  else if (t.k === 'batch') { const b = t.b; n = b.consented; label = `${MC.mcName(b.mc)}, ${b.cohort}`; finished = b.status === 'Deposited'; if (b.status === 'ACR prepared') blockWhy = 'Confirm the ACR first. Only confirmed ACR can be deposited.';
    if (finished) summary = [['ABC reference', b.abcRef], ['Deposited', b.depositedOn], ['Credit valid until', b.expires]]; }
  else if (t.k === 'sent') { const b = t.b; n = b.count; label = `FTMK batch sent on ${b.sent}`; finished = b.status === 'Deposited'; if (finished) summary = [['ABC reference', b.abcRef], ['Deposited', b.depositedOn], ['Credit valid until', b.expires]]; }
  else { const d = t.d; n = d.count; label = 'FTMK batch'; finished = true; summary = [['ABC reference', d.ref], ['Recorded', d.date], ['Status', T(d.status, 'ok')]]; }
  const depSt = (t.a || t.b || {}).depStatus;
  const rec = finished || blockWhy ? '' : `<div data-form class="col">
      ${field('ABC reference', inp('ref', '', 'For example ABC-MY-2026-0004502', true), t.k === 'acr' ? '' : 'One reference, or the first and last of a range (0004391..4419)', true)}
      ${field('Deposit date', inp('date', s.today, '26 Oct 2026', true), 'Write the date as 26 Oct 2026', true)}${field('Status from ABC', sel('status', ['Successful', 'In process', 'Correction needed']), 'Credit becomes Available only when the status is Successful')}
      <span class="btn" style="align-self:flex-start" data-modal="depositRecord" data-validate data-id="${p.id}">Record deposit</span></div>`;
  return `${head({ crumb, title: `Record ABC deposit: ${p.id}`, sub: `${label} · ${n} ACR`, status: T(finished ? 'Deposited' : t.k === 'sent' ? 'Sent' : 'Verified', finished ? 'ok' : 'info') })}
  <div class="row"><div class="col grow">
    ${finished ? '' : panel('Export for ABC', `<div class="muted" style="margin-bottom:12px">The export lists the ${n} ACR in this record. The exact format is still to be agreed with ABC.${t.k === 'sent' ? ' This record was already sent; open the export again only if ABC asks for it.' : ''}</div>
      <span class="btn ter" data-go="#/doc/abc/${p.id}">${t.k === 'sent' ? 'Open export again' : 'Open export for ABC'}</span>`)}
    ${panel('Deposit recorded by you', finished ? dl(summary.concat(depSt ? [['Last status', depSt]] : [])) + `<div class="small muted" style="margin-top:8px">Each learner's credit is Available and valid for 10 years from the deposit date.</div>` : (blockWhy ? `${note(blockWhy, 'warn')}<div style="margin-top:12px">${disBtn('Record deposit', 'Blocked until consent is given.')}</div>` : rec + (depSt ? note(`Last status recorded: <b>${depSt}</b>. The credit stays out of the ledger until ABC reports Successful.`, 'warn') : '')))}
    ${extra ? panel('Checks', extra + `<div style="margin-top:8px"><a data-go="#/doc/acr/${p.id}">Open the ACR as a document</a></div>`) : ''}
  </div><div class="col" style="width:360px">${panel('What learners are told', `<div class="small" style="line-height:20px">Your credit for ${t.a ? MC.mcName(t.a.mc) : 'your micro-credential'} is now stored in the Academic Bank of Credit and stays valid for 10 years. <b>This is storage, not a credit transfer.</b> Credit is transferred to a programme only after a separate decision by the receiving faculty.</div>`)}
    ${note('Deposits are recorded by hand in Release 1. There is no live connection to ABC.')}</div></div>`; } });
modal('depositRecord', { title: 'Record this ABC deposit?', kicker: 'ABC deposit', body: (c, s) => { const ok = c.status === 'Successful';
  return wnt(ok ? `ABC reference ${c.ref} dated ${isoFmt(c.date)} is recorded for ${c.id}. The credit becomes Available and expires on ${addYears(isoFmt(c.date), 10)} (10 years).` : `Status "${c.status}" is recorded for ${c.id}. The credit does not become Available yet.`, 'The ACR itself and any credential are unchanged. This is storage, not a credit transfer.', ok ? 'The learner, by notification and email: stored, not transferred' : 'No one yet'); },
  confirm: 'Record deposit', act: 'ddDepositRecord', done: c => (c.status === 'Successful' ? ['Deposit recorded', 'The credit is Available for 10 years. The learner has been told it is storage, not a transfer.'] : ['Status saved', 'The credit stays unavailable until ABC reports Successful.', 'warn']) });
act('ddDepositRecord', (c, s) => {
  if (badDate(c.date)) { MC.toast('Not recorded', 'Enter the deposit date, for example 26 Oct 2026.', 'err'); return false; }
  const date = isoFmt(c.date), ok = c.status === 'Successful', exp = addYears(date, 10), a = s.acrs.find(x => x.id === c.id), b = s.acrBatches.find(x => x.id === c.id);
  if (a && !/^(Verified|Correction needed)$/.test(a.status)) { MC.toast('Not recorded', `The ACR is ${a.status}. Only Verified ACR can be deposited.`, 'err'); return false; }
  if (a) { if (ok) Object.assign(a, { status: 'Deposited', abcRef: c.ref, deposited: date, expires: exp, credit: 'Available' }); else { a.depStatus = c.status; a.status = c.status === 'Correction needed' ? 'Correction needed' : 'Verified'; }
    if (ok && a.owner === 'amir') notify(s, 'st', { t: 'Credit deposited in ABC', s: `${MC.mcName(a.mc)}, ${a.credits} credits, valid to ${exp}. Storage, not a transfer.`, go: '#/st/ledger' }); }
  else if (b) { if (ok) Object.assign(b, { status: 'Deposited', abcRef: c.ref, depositedOn: date, expires: exp }); else b.depStatus = c.status; }
  if (ok) finishTask(s, c.id, `ABC reference ${c.ref}`);
  log(s, HK, `Recorded ABC deposit ${c.id}: ${c.status}, ref ${c.ref}`, c.id);
});

/* ---------- credit transfer ---------- */
const OUTC = { DS: ['Apply linear data structures', 'Implement trees and graphs', 'Analyse algorithm complexity', 'Choose a structure for a problem'], CND: ['Package and run containers', 'Build release pipelines', 'Operate running services', 'Design for resilience'] };
const COURSE_OUTC = { 'BITS 1123': ['Use arrays, lists, stacks and queues', 'Implement trees and graphs', 'Analyse time and space cost', 'Select a structure for a given problem'], 'BITU 3973': ['Containerise an application', 'Automate build and release', 'Operate cloud services', 'Design for failure and recovery'] };
screen('dd/transfer', { title: 'Credit transfer', role: 'dd', nav: 'transfer', render: (p, s) => `${head({ title: 'Credit transfer', sub: 'Cases for BITZ and BITD, programmes owned by FTMK. You decide each course by comparing learning outcomes. Approval transfers credit only; it does not admit anyone.' })}
  ${table({ id: 'ddtransfer', size: 10, search: 'Search cases', cols: [{ h: 'Case', k: 'id' }, { h: 'Learner', k: 'name' }, { h: 'Programme', k: 'programme' }, { h: 'Lines', num: true, r: t => t.lines.length }, { h: 'Credits', num: true, r: t => t.lines.reduce((a, l) => a + l.credits, 0) },
    { h: 'Applied', k: 'applied' }, { h: 'Status', r: t => T(t.status, TK[t.status]) }, { h: '', r: t => `<a data-go="#/dd/transfer/${t.id}">Open</a>` }], rows: st => st.transfers, go: t => `#/dd/transfer/${t.id}` })}` });

// a line can be approved only when every outcome is rated and none is "None"
const unrated = mt => !mt || mt === 'Not rated';
const noMatch = l => !l.match || !l.match.length || l.match.some(mt => mt === 'None' || unrated(mt));
const RED = { Available: 'ok', 'Provisionally redeemed': 'warn', Redeemed: 'info', Expired: 'err' };
screen('dd/transfer/:id', { title: 'Transfer decision', role: 'dd', nav: 'transfer', ex: 'TR-2026-0012', render: (p, s) => {
  const t = s.transfers.find(x => x.id === p.id), crumb = [['Credit transfer', '#/dd/transfer']];
  if (!t) return `${head({ crumb, title: 'Transfer decision' })}${note('This transfer case does not exist.', 'err')}`;
  const reopened = s.tasks.some(x => x.ref === t.id && !x.done), waiting = t.status === 'More evidence needed' && !reopened;
  const adm = s.admissions.find(a => a.who === t.who && a.programme === t.programme), open = t.status === 'Under evaluation' || (t.status === 'More evidence needed' && reopened);
  const lineInfo = t.lines.map(l => ({ l, a: s.acrs.find(x => x.id === l.acr), m: MC.mc(l.mc), blocked: noMatch(l) }));
  const allBlocked = lineInfo.every(x => x.blocked);
  const linePanel = (x, i) => { const { l, a, m } = x, out = OUTC[l.mc] || [], cout = COURSE_OUTC[l.course] || [], cred = s.credentials.find(c => c.owner === t.who && c.mc === l.mc);
    const avail = a && a.credit === 'Available' || (a && l.decision === 'Approved');
    return `<div class="panel"><div class="hd"><h2>Line ${i + 1}: ${MC.mcName(l.mc)} to ${l.course}</h2><span class="r">${l.credits} credits ${l.decision ? T(l.decision, TK[l.decision]) : ''}</span></div><div class="bd col">
      ${table({ id: `ddtr-eq-${t.id}-${i}`, nopager: true, cols: [{ h: 'Outcome of the micro-credential', r: r => r[0] }, { h: `Outcome of ${l.course}`, r: r => r[1] }, { h: 'Match', r: r => T(unrated(r[2]) ? 'Not rated' : r[2], r[2] === 'Full' ? 'ok' : r[2] === 'Partial' ? 'warn' : unrated(r[2]) ? 'warn' : 'err') }], rows: (l.match && l.match.length ? l.match : out.map(() => '')).map((mt, k) => [out[k] || `Outcome ${k + 1}`, cout[k] || `Course outcome ${k + 1}`, mt]) })}
      ${x.blocked ? note(l.match && l.match.includes('None') ? `Outcome ${l.match.indexOf('None') + 1} has no match in ${l.course}. Credit or learning time alone is not evidence of equivalence, so this line cannot be approved.` : `The outcomes have not all been compared with ${l.course} yet. Credit or learning time alone is not evidence of equivalence, so this line cannot be approved until every outcome is rated.`, 'err') : ''}
      <div class="row"><div class="grow">${checks([[a && a.status === 'Deposited' ? '' : 'err', 'ACR deposited in ABC', a ? `${a.id} · ${a.abcRef || 'no ABC reference'}` : 'ACR not found'], [a && a.expires && new Date(a.expires) > new Date(s.today) ? '' : 'err', 'Credit valid', a ? `Valid to ${a.expires}` : ''],
        [avail ? '' : 'err', 'Single redemption', a ? (l.decision === 'Approved' ? 'Held for this case (Provisionally redeemed)' : `Credit status: ${a.credit}`) : ''], [adm ? '' : 'warn', 'Programme admission recorded', adm ? `${t.programme} ${adm.intake}, ${adm.recorded}` : 'Not recorded. Approval does not imply admission; Pejabat Pendaftar records it separately.'],
        ['warn', 'Transfer limit and non-transferable components', 'Not configured yet. No limit is applied.']].concat(m.cat === 'SAMC' ? [[m.qv && qvDays(s, m) > 0 ? '' : 'err', 'QV valid', m.qv ? `${m.qv.status}, to ${m.qv.to}` : 'No QV']] : []))}</div>
      <div style="width:300px"><div class="small muted" style="margin-bottom:6px">Evidence</div><div class="col" style="gap:4px"><a data-go="#/dd/deposit/${l.acr}">ACR ${l.acr}</a>${cred ? `<a data-go="#/dd/cred/${cred.id}">Credential ${cred.id}</a>` : '<span class="small muted">Credential on file</span>'}<a data-go="#/dd/proposal/${l.mc}">Learning outcomes, ${m.name}</a>${m.cat === 'SAMC' ? `<a data-go="#/dd/qv/${l.mc}">QV record</a>` : ''}</div></div></div>
      ${open ? `<div class="field"><label class="req">Decision for ${l.course}</label><div class="col" style="gap:8px">
        <label class="choice" style="${x.blocked ? 'opacity:.6' : ''}"><input type="radio" name="d${i}" value="Approved" ${x.blocked ? 'disabled' : ''}><div>Approve ${l.credits} credits for ${l.course}<span class="small muted" style="display:block">${x.blocked ? 'Approve is off: an outcome has no match or is not rated.' : 'The credit becomes Provisionally redeemed for this programme.'}</span></div></label>
        <label class="choice ${x.blocked ? 'on' : ''}"><input type="radio" name="d${i}" value="Not approved" ${x.blocked ? 'checked' : ''} required><div>Do not approve this line<span class="small muted" style="display:block">The ACR and credentials stay exactly as they are.</span></div></label></div></div>` : ''}
      </div></div>`; };
  const reds = lineInfo.filter(x => x.l.decision === 'Approved' && x.a);
  return `${head({ crumb, title: `Credit transfer ${t.id}: ${t.name}`, sub: `Into ${t.programme} · applied ${t.applied} · ${t.lines.length} line${t.lines.length > 1 ? 's' : ''}, ${t.lines.reduce((a, l) => a + l.credits, 0)} credits`, status: T(t.status, TK[t.status]), actions: t.decidedOn && !open ? `<span class="btn ter" data-go="#/doc/transfer/${t.id}">Open decision letter</span>` : '' })}
  ${t.status === 'More evidence needed' ? note(`<b>More evidence requested on ${t.decidedOn}:</b> ${t.reason || '–'}${waiting ? ' <b>Waiting for the applicant</b> to upload it. You can decide again when it arrives.' : ' The applicant has sent evidence; decide again below.'}`, 'warn') : t.reason && open ? note(`<b>Earlier note (${t.decidedOn}):</b> ${t.reason}`) : ''}
  <div ${open ? 'data-form' : ''} class="col" style="gap:16px">${lineInfo.map(linePanel).join('')}
  ${open ? panel('Overall decision', `${field('Decision', sel('overall', ['Approved', 'Partly approved', 'Not approved', 'More evidence needed'], allBlocked ? 'Not approved' : 'Approved'), 'Approved needs every line approved. A reason is required for any other decision.')}
    <span class="btn" data-modal="transferDecide" data-validate data-id="${t.id}">Record decision</span>`) : panel(waiting ? 'Waiting for the applicant' : 'Decision recorded', dl([['Decision', T(t.status, TK[t.status])], ['Date', t.decidedOn || '–'], ['Decided by', `${HK}, deputy dean (Academic), FTMK`], ['Reason', t.reason || '–']]))}
  ${reds.length ? panel('Redemption', reds.map(x => `<div class="check" style="align-items:center"><div class="grow">${x.l.course} · ${x.a.id}<span class="small muted" style="display:block">${x.a.credit === 'Redeemed' ? `Programme completion recorded${x.a.redeemRef ? `, evidence ${x.a.redeemRef}` : ''}. The credit cannot be used again.` : x.a.credit === 'Available' ? 'Released. The credit is Available again.' : x.a.credit === 'Expired' ? 'Released, but the credit has expired and cannot be used again.' : 'Held for this programme. No one else can use it.'}</span></div>
    ${T(x.a.credit, RED[x.a.credit])} ${x.a.credit === 'Provisionally redeemed' ? `<span class="btn ter sm" data-modal="redeemComplete" data-id="${x.a.id}">Record programme completion</span><span class="btn danger ter sm" data-modal="redeemRelease" data-id="${x.a.id}">Release the credit</span>` : ''}</div>`).join(''), 'Single redemption') : ''}
  </div>`; } });
modal('transferDecide', { title: c => `Record the decision: ${c.overall}?`, kicker: 'Credit transfer', body: (c, s) => { const t = s.transfers.find(x => x.id === c.id), ap = t.lines.filter((l, i) => c['d' + i] === 'Approved').length;
  const what = c.overall === 'Approved' ? `${ap} line${ap > 1 ? 's' : ''} approved. Each credit becomes Provisionally redeemed for ${t.programme} and cannot be used for another application.` : c.overall === 'Partly approved' ? `${ap} of ${t.lines.length} lines approved. Approved credit becomes Provisionally redeemed; the rest are not approved.` : c.overall === 'Not approved' ? 'No line is approved.' : 'The case goes back to the applicant for more evidence.';
  return wnt(what, c.overall === 'Approved' || c.overall === 'Partly approved' ? 'Admission is not implied. The ACR, credentials and badges are never deleted or altered.' : 'The ACR, credentials and credit status stay exactly as they are.', `${t.name}, by notification and email`)
    + (c.overall === 'Approved' ? '' : field('Reason (sent to the learner)', area('reason', '', '', true), '', true)); }, confirm: 'Record decision', act: 'ddTransferDecide', done: c => [`Decision recorded: ${c.overall}`, c.overall === 'Approved' || c.overall === 'Partly approved' ? 'Approved credit is now Provisionally redeemed.' : 'The learner has been told. Their credit is unchanged.', c.overall === 'More evidence needed' ? 'info' : 'ok'] });
act('ddTransferDecide', (c, s) => {
  const t = s.transfers.find(x => x.id === c.id), ds = t.lines.map((l, i) => c['d' + i]), ap = ds.filter(x => x === 'Approved').length, bad = m => { MC.toast('Decision not recorded', m, 'err'); return false; };
  const more = c.overall === 'More evidence needed';
  if (t.status !== 'Under evaluation' && !(t.status === 'More evidence needed' && s.tasks.some(x => x.ref === t.id && !x.done))) return bad(`This case is ${t.status}. It is waiting for the applicant or already decided.`);
  if (!more) {
    if (c.overall === 'Approved' && ap !== t.lines.length) return bad('Approved needs every line approved. Choose Partly approved or Not approved instead.');
    if (c.overall === 'Partly approved' && (ap === 0 || ap === t.lines.length)) return bad('Partly approved needs at least one approved and one refused line.');
    if (c.overall === 'Not approved' && ap) return bad('Not approved cannot include an approved line.');
    for (let i = 0; i < t.lines.length; i++) { const l = t.lines[i], a = s.acrs.find(x => x.id === l.acr);
      if (ds[i] === 'Approved' && noMatch(l)) return bad(`${l.course} has an outcome with no match, or not rated, and cannot be approved.`);
      if (ds[i] === 'Approved' && !(a && a.credit === 'Available')) return bad(`The credit for ${l.course} is not Available${a ? ` (${a.credit})` : ''}. It cannot be redeemed again.`); }
    t.lines.forEach((l, i) => { l.decision = ds[i]; if (ds[i] === 'Approved') { const a = s.acrs.find(x => x.id === l.acr); a.credit = 'Provisionally redeemed'; a.redeemFor = `${t.programme}, ${t.id}`; } });
  }
  t.status = c.overall; t.decidedOn = s.today; t.reason = c.reason || '';
  finishTask(s, t.id, c.overall); log(s, HK, `Decided credit transfer ${t.id}: ${c.overall}${c.reason ? `, ${c.reason}` : ''}`, t.id);
  if (t.who === 'amir') notify(s, 'st', { t: `Credit transfer ${t.id}: ${c.overall}`, s: c.reason || 'Approved credit is held for your programme', go: `#/st/transfer/${t.id}` });
});
const abcUpd = field('ABC update reference and date (if ABC was already updated)', inp('abcupd', '', 'For example ABC-UPD-2026-0012, 26 Oct 2026'), 'Optional. Leave empty if the ABC record is updated later.');
modal('redeemComplete', { title: 'Record programme completion?', kicker: 'Redemption', body: (c, s) => `${wnt(`The credit in ${c.id} moves from Provisionally redeemed to Redeemed. It can never be used again.`, 'The ACR and credentials are kept and not edited.', 'The learner')}${field('Evidence reference (programme record)', inp('evid', '', 'For example the graduation or result record number', true), '', true)}${abcUpd}`, confirm: 'Record completion', act: 'ddRedeem', done: ['Redeemed', 'The credit is now permanently used.'] });
act('ddRedeem', (c, s) => { const a = s.acrs.find(x => x.id === c.id); a.credit = 'Redeemed'; a.redeemRef = c.evid; if (c.abcupd) a.abcUpdate = c.abcupd; log(s, HK, `Recorded programme completion, credit Redeemed (${c.evid})`, c.id); });
modal('redeemRelease', { title: 'Release this credit?', kicker: 'Danger: redemption', danger: true, reason: 'Why the programme was not completed (kept in the audit trail)', body: c => `${wnt(`The credit in ${c.id} moves from Provisionally redeemed back to Available, if it is still valid.`, 'The transfer decision stays on record. The ACR and credentials are unchanged.', 'The learner')}${field('Evidence reference', inp('evid', '', 'For example the withdrawal record number', true), '', true)}${abcUpd}`, confirm: 'Release credit', act: 'ddRelease', done: (c, s) => { const a = s.acrs.find(x => x.id === c.id); return a.credit === 'Available' ? ['Credit released', 'It is Available again and can be used for another application.', 'warn'] : ['Credit released', 'The credit has expired, so it is Expired and cannot be used again.', 'warn']; } });
act('ddRelease', (c, s) => { const a = s.acrs.find(x => x.id === c.id); a.credit = a.expires && new Date(a.expires) > new Date(s.today) ? 'Available' : 'Expired'; delete a.redeemFor; if (c.abcupd) a.abcUpdate = c.abcupd; log(s, HK, `Released provisionally redeemed credit (${c.evid}): ${c.reason}`, c.id); });

/* ---------- stackable pathways and plans ---------- */
screen('dd/stack', { title: 'Stackable pathways', role: 'dd', nav: 'stack', render: (p, s) => {
  const on = p.tab || 'pathways';
  return `${head({ title: 'Stackable pathways', sub: 'Pathway mappings need Senate approval before they are published. A mapping transfers no credit; each credit is still decided as a credit transfer.' })}
  <div data-tabs>${tabs([['pathways', 'Pathway mappings', s.pathways.length], ['plans', 'Individual plans', s.plans.length]], on)}
  <div data-pane="pathways">${table({ id: 'ddstack-pw', size: 10, cols: [{ h: 'Pathway', k: 'id' }, { h: 'Programme', k: 'programme' }, { h: 'Mapped sources', num: true, r: x => x.maps.length }, { h: 'Status', r: x => T(x.status, x.status === 'Published' ? 'ok' : x.status === 'Draft' ? '' : 'info') }, { h: '', r: x => `<a data-go="#/dd/pathway/${x.id}">Open</a>` }], rows: st => st.pathways, go: x => `#/dd/pathway/${x.id}` })}</div>
  <div data-pane="plans">${table({ id: 'ddstack-pl', size: 10, search: 'Search plans', cols: [{ h: 'Plan', k: 'id' }, { h: 'Learner', r: x => nameOf(x.who) }, { h: 'Programme', k: 'programme' }, { h: 'Credit earned at UTeM', num: true, r: x => `${Math.round((x.total - x.proposed) / x.total * 1000) / 10}%` }, { h: 'Status', r: x => T(x.status, /^Suitable/.test(x.status) ? 'ok' : /Not yet/.test(x.status) ? 'warn' : 'err') }, { h: '', r: x => `<a data-go="#/dd/plan/${x.id}">Open</a>` }], rows: st => st.plans, go: x => `#/dd/plan/${x.id}` })}</div></div>`; } });

screen('dd/pathway/:id', { title: 'Pathway mapping', role: 'dd', nav: 'stack', ex: 'PW-BITZ-1', render: (p, s) => {
  const w = s.pathways.find(x => x.id === p.id), crumb = [['Stackable pathways', '#/dd/stack']];
  if (!w) return `${head({ crumb, title: 'Pathway mapping' })}${note('This pathway does not exist.', 'err')}`;
  const pr = s.programmes.find(x => x.code === w.programme), mt = s.pathMatches[w.id] || [], draft = w.status === 'Draft';
  return `${head({ crumb, title: `Pathway to ${w.programme}`, sub: `${pr ? pr.name : ''} · ${pr ? pr.credits : ''} credits · MQF ${pr ? pr.mqf : ''}`, status: T(w.status, w.status === 'Published' ? 'ok' : draft ? '' : 'info'),
    actions: draft ? `<span class="btn ter" data-modal="pwAdd" data-id="${w.id}">Add a mapping</span><span class="btn" data-modal="pwSubmit" data-id="${w.id}">Submit for Senate approval</span>` : w.status === 'Published' ? `<span class="btn ter" data-go="#/public/pathways">See the published pathway</span>` : `<span class="btn" data-modal="pwSenate" data-id="${w.id}">Record Senate decision</span>` })}
  ${w.senateNote ? note(w.senateNote, w.status === 'Draft' ? 'warn' : 'ok') : ''}
  ${note('An approved mapping transfers no credit. Each credit still goes through a credit transfer decision, course by course. Only credit-bearing credentials can be mapped; an MCE cannot.')}
  ${table({ id: `ddpw-map-${w.id}`, nopager: true, title: 'Mapping to degree courses', cols: [{ h: 'Source', r: x => MC.mcNm(x[0]) }, { h: 'Target course', r: x => { const c = s.courses.find(k => k.code === x[1]); return `${x[1]}${c ? `<span class="s">${c.name}</span>` : ''}`; } }, { h: 'Credits', num: true, k: 2 },
    { h: 'Outcome match', r: x => { const i = w.maps.indexOf(x); return T(mt[i] || 'Not rated', mt[i] === 'High' ? 'ok' : mt[i] === 'Partial' ? 'warn' : mt[i] === 'None' ? 'err' : ''); } },
    { h: '', r: x => { const i = w.maps.indexOf(x); return draft ? `<a data-modal="pwMatch" data-id="${w.id}" data-i="${i}">Change match</a>` : ''; } }], rows: st => (st.pathways.find(k => k.id === w.id) || w).maps })}
  <div class="row">${panel('Published view for participants', `<div class="muted" style="margin-bottom:8px">Participants see each micro-credential, the course it may count towards and a plain reminder that approval of a transfer is separate.</div>${w.maps.map(x => `<div class="check"><span class="dot"></span><div class="grow">${MC.mcName(x[0])} may be considered for ${x[1]}</div></div>`).join('')}
    <div class="small muted" style="margin-top:8px">Wording never says that micro-credentials lead to a degree. <a data-go="#/public/pathways">Open the public pathways page</a></div>`).replace('class="panel"', 'class="panel grow"')}
    <div style="width:360px">${panel('Release 2', `<span class="btn dis">PLO and body of knowledge matrices</span><div class="small muted" style="margin-top:8px">Coverage matrices and gap warnings arrive in Release 2.</div>`)}</div></div>`; } });
modal('pwMatch', { title: 'Change the outcome match', kicker: 'Pathway mapping', body: (c, s) => { const w = s.pathways.find(x => x.id === c.id), x = w.maps[+c.i]; return `<p>${MC.mcName(x[0])} to ${x[1]}</p>${field('Outcome match', sel('match', ['High', 'Partial', 'None'], (s.pathMatches[c.id] || [])[+c.i]))}`; }, confirm: 'Save match', act: 'ddPwMatch', done: ['Match saved', 'Draft mapping updated.'] });
act('ddPwMatch', (c, s) => { s.pathMatches[c.id][+c.i] = c.match; });
modal('pwAdd', { title: 'Add a mapping', kicker: 'Pathway mapping', body: (c, s) => `<div class="help err" data-pwerr style="display:none;margin-bottom:8px"></div>
  ${field('Source micro-credential', `<select class="input" name="src">${s.mcs.filter(m => m.status === 'Approved').map(m => `<option value="${m.id}">${m.name} (${m.cat})</option>`).join('')}</select>`, '', true)}
  ${field('Target course', `<select class="input" name="course">${s.courses.map(k => `<option value="${k.code}">${k.code} ${k.name}</option>`).join('')}</select>`, '', true)}${field('Outcome match', sel('match', ['High', 'Partial', 'None']))}`, confirm: 'Add mapping', act: 'ddPwAdd', done: ['Mapping added', 'Draft mapping updated.'] });
act('ddPwAdd', (c, s) => { const w = s.pathways.find(x => x.id === c.id), m = MC.mc(c.src), e = document.querySelector('.scrim [data-pwerr]'), bad = msg => { if (e) { e.textContent = msg; e.style.display = 'block'; } return false; };
  if (m.cat === 'MCE') return bad(`${m.name} is an MCE. It carries no credit, so it cannot be mapped to a degree course.`);
  if (w.maps.some(x => x[0] === c.src && x[1] === c.course)) return bad('This source is already mapped to that course.');
  w.maps.push([c.src, c.course, m.credits]); s.pathMatches[w.id].push(c.match); log(s, HK, `Added mapping ${m.name} to ${c.course}, ${w.id}`, w.id); });
modal('pwSubmit', { title: 'Submit this pathway for Senate approval?', kicker: 'Pathway mapping', body: c => wnt(`${c.id} goes to the Senate as it stands. It stays hidden from participants until the Senate approves it.`, 'No credit is transferred by a mapping. Editing is locked while the Senate considers it.', 'The Senate secretariat'), confirm: 'Submit to Senate', act: 'ddPwSubmit', done: ['Submitted for Senate approval', 'Record the Senate decision when it is made.', 'info'] });
act('ddPwSubmit', (c, s) => { const w = s.pathways.find(x => x.id === c.id); w.status = 'Submitted for Senate approval'; w.senateNote = ''; log(s, HK, `Submitted pathway ${w.id} for Senate approval`, w.id); });
modal('pwSenate', { title: 'Record the Senate decision', kicker: 'Pathway mapping', body: () => `${field('Decision', sel('dec', ['Approved', 'Not approved']))}${field('Senate meeting', inp('mtg', '', 'For example Senate 8/2026, 18 Nov', true), '', true)}<div class="small muted">Approved pathways are published at once. Not approved pathways return to Draft with your note.</div>`, confirm: 'Record decision', act: 'ddPwSenate', done: c => [c.dec === 'Approved' ? 'Pathway published' : 'Returned to draft', c.dec === 'Approved' ? 'Participants can see it on the public pathways page.' : 'Edit the mapping and submit again.', c.dec === 'Approved' ? 'ok' : 'warn'] });
act('ddPwSenate', (c, s) => { const w = s.pathways.find(x => x.id === c.id), ok = c.dec === 'Approved'; w.status = ok ? 'Published' : 'Draft'; w.senateNote = ok ? `Approved by ${c.mtg}. Published.` : `Not approved by ${c.mtg}. Edit and submit again.`; log(s, HK, `Recorded Senate decision on ${w.id}: ${c.dec} (${c.mtg})`, w.id); });

screen('dd/plan/:id', { title: 'Individual stackable plan', role: 'dd', nav: 'stack', ex: 'SP-2026-004', render: (p, s) => {
  const pl = s.plans.find(x => x.id === p.id), crumb = [['Stackable pathways', '#/dd/stack?tab=plans']];
  if (!pl) return `${head({ crumb, title: 'Plan' })}${note('This plan does not exist.', 'err')}`;
  const home = pl.total - pl.proposed, pct = Math.round(home / pl.total * 1000) / 10, meets = pct >= 30, res = pl.residency >= 1, adm = s.admissions.find(a => a.who === pl.who && a.programme === pl.programme);
  const rest = [['Final year project', 6], ['Industrial training', 10], ['University courses', 6]], core = home - 22;
  const opts = [['Suitable to proceed', !meets ? 'Off: below 30% earned at UTeM.' : !res ? 'Off: residency not met.' : !adm ? 'Off: programme admission is not recorded.' : ''], ['Suitable with conditions', !meets ? 'Off: below 30% earned at UTeM.' : !res ? 'Off: residency not met.' : ''], ['Not yet suitable', ''], ['Not suitable', '']];
  const first = opts.find(o => !o[1])[0], open = !pl.decidedOn;
  return `${head({ crumb, title: `Stackable plan ${pl.id}: ${nameOf(pl.who)}`, sub: `Into ${pl.programme} · ${pl.total} credits`, status: T(pl.status, /^Suitable/.test(pl.status) ? 'ok' : /Not yet/.test(pl.status) ? 'warn' : 'err') })}
  ${kpis([['Credit proposed from outside UTeM', pl.proposed], ['Credit earned at UTeM', `${home} of ${pl.total}`, `${pct}%`], ['Residency', `${pl.residency} semester${pl.residency > 1 ? 's' : ''}`, 'At least 1 for a conventional programme'], ['Admission', adm ? 'Recorded' : 'Not recorded', adm ? adm.intake : '']])}
  <div class="row"><div class="col grow">
    ${panel('Checks', checks([[meets ? '' : 'err', `UTeM share ${pct}%: ${meets ? 'meets' : 'Not yet suitable'}`, `${home} of ${pl.total} credits earned at UTeM. At least 30% is required.`], [res ? '' : 'err', `Residency ${pl.residency} semester${pl.residency > 1 ? 's' : ''}`, res ? 'Meets the 1 semester minimum' : 'Below the minimum'],
      [adm ? '' : 'warn', 'Admission to the programme', adm ? `${adm.programme}, intake ${adm.intake}, ${adm.recorded}` : 'Not recorded. "Suitable to proceed" is blocked without it.']]) + note('The 30% rule is a minimum for credit earned at UTeM. It does not mean that the other 70% will be transferred; each credit is decided separately as a credit transfer.'))}
    ${table({ id: `ddplan-rest-${pl.id}`, nopager: true, title: 'Remaining requirements', cols: [{ h: 'Requirement', k: 0 }, { h: 'Credits', num: true, k: 1 }, { h: 'Status', r: () => 'Not started' }], rows: rest.concat([['Core and elective courses', core]]) })}
    <div class="small muted">Remaining requirements total ${home} credits, the share still to be earned at UTeM.</div>
  </div><div class="col" style="width:420px">
    ${!open ? panel('Decision recorded', dl([['Decision', T(pl.status, /^Suitable/.test(pl.status) ? 'ok' : /Not yet/.test(pl.status) ? 'warn' : 'err')], ['Date', pl.decidedOn], ['Decided by', `${HK}, deputy dean (Academic), FTMK`], ['Conditions and follow-up', pl.cond || '–']])) : panel('Decision', `<div data-form class="col"><div class="field"><label class="req">Plan decision</label><div class="col" style="gap:8px">${opts.map(([o, why]) => `<label class="choice ${o === first ? 'on' : ''}" style="${why ? 'opacity:.6' : ''}"><input type="radio" name="decision" value="${o}" ${why ? 'disabled' : ''} ${o === first ? 'checked' : ''} ${why ? '' : 'required'}><div>${o}${why ? `<span class="small muted" style="display:block">${why}</span>` : ''}</div></label>`).join('')}</div></div>
      ${field('Conditions and follow-up', area('cond', '', 'For example: earn 3 more credits at UTeM before admission'))}
      <span class="btn" data-modal="planDecide" data-validate data-id="${pl.id}">Record decision</span><span class="btn dis">PLO and BoK matrices (Release 2)</span></div>`)}
  </div></div>`; } });
modal('planDecide', { title: c => `Record the plan decision: ${c.decision}?`, kicker: 'Stackable plan', body: (c, s) => wnt(`${c.id} is recorded as "${c.decision}".${c.cond ? ` Conditions: ${c.cond}` : ''}`, 'No credit is transferred and nobody is admitted by this decision. MCMS does not award the degree.', 'The learner and the lecturer'), confirm: 'Record decision', act: 'ddPlanDecide', done: ['Plan decision recorded', 'The learner can see the outcome.'], then: '#/dd/stack?tab=plans' });
act('ddPlanDecide', (c, s) => { const pl = s.plans.find(x => x.id === c.id), pct = (pl.total - pl.proposed) / pl.total * 100, bad = m => { MC.toast('Not recorded', m, 'err'); return false; };
  if (pl.decidedOn) return bad(`This plan was decided on ${pl.decidedOn}.`);
  if (/^Suitable/.test(c.decision) && pct < 30) return bad('Below 30% earned at UTeM, so the plan cannot be suitable.');
  if (/^Suitable/.test(c.decision) && pl.residency < 1) return bad('Residency is not met, so the plan cannot be suitable.');
  if (c.decision === 'Suitable to proceed' && !s.admissions.some(a => a.who === pl.who && a.programme === pl.programme)) return bad('Programme admission is not recorded, so the plan cannot be Suitable to proceed.');
  pl.status = c.decision; pl.decidedOn = s.today; pl.cond = c.cond || ''; log(s, HK, `Stackable plan ${pl.id} decision: ${c.decision}`, pl.id); });

/* ---------- reports ---------- */
if (MC.reportsScreen) { MC.reportsScreen('dd', 'reports', 'FTMK'); MC.reportScreen('dd', 'reports', 'FTMK'); }

/* ---------- traceability ---------- */
const PEOPLE = [['amir', 'X2026-0193'], ['wong', 'X2026-0201'], ['leejh', 'B032410111']];
screen('dd/trace', { title: 'Traceability', role: 'dd', nav: 'trace', render: (p, s) => `${head({ title: 'Traceability', sub: 'Follow a participant from approval through to use of credit. Search by name, ID or credential number.' })}
  ${table({ id: 'ddtrace-list', size: 10, search: 'Search a participant or credential', cols: [{ h: 'Type', k: 't' }, { h: 'Name or number', k: 'n' }, { h: 'Detail', k: 'd' }, { h: '', r: r => `<a data-go="${r.go}">Trace</a>` }],
    rows: st => PEOPLE.map(([k, id]) => ({ t: 'Participant', n: nameOf(k), d: id, go: `#/dd/trace/${k}` })).concat(st.credentials.map(c => ({ t: 'Credential', n: c.id, d: `${c.who}, ${MC.mcName(c.mc)}, ${c.status}`, go: `#/dd/trace/${c.owner}` }))), go: r => r.go })}` });
const chain = (s, who) => {
  const out = [];
  s.credentials.filter(c => c.owner === who).forEach(c => {
    const m = MC.mc(c.mc), co = MC.co(c.cohort), en = s.enrolments.find(e => e.who === who && e.cohort === c.cohort), a = s.acrs.find(x => x.owner === who && x.mc === c.mc), t = a && s.transfers.find(x => x.lines.some(l => l.acr === a.id));
    const g = [['Approval', `${m.name}, version ${m.version}`, `Senate, endorsed by ${HK}`, m.approvedBy, `#/dd/proposal/${m.id}`, true], ['Offering', `${co.name}, ${co.start} to ${co.end}`, ownerName(co.owner), co.start, '#/dd/offerings', true],
      ['Enrolment', en ? `${en.id}, ${en.paid}` : 'Enrolment record', nameOf(who), en ? en.paid.split(', ')[1] : '', '#/dd/offerings', true], ['Results', `${c.result}, verified`, HK, co.end, '#/dd/results', true],
      ['Completion', 'Completion confirmed', HK, c.issued, '#/dd/results', true], ['Credential', `${c.id}, ${c.status}`, 'System', c.issued, `#/dd/cred/${c.id}`, true],
      ['ACR', a ? `${a.id}, ${a.credits} credits, ${a.status}` : 'No ACR prepared', a ? `System, confirmed by ${HK}` : '–', a ? a.confirmed || '' : '', a ? `#/dd/deposit/${a.id}` : '#/dd/acr', !!a],
      ['Consent', a && a.consent ? a.consent : 'Not given yet', nameOf(who), a && a.consent ? a.consent.replace(/^\w+ /, '') : '', '#/dd/acr?tab=queue', !!(a && a.consent)],
      ['Deposit', a && a.abcRef ? `${a.abcRef}, valid to ${a.expires}` : 'Not deposited', HK, a && a.deposited || '', a ? `#/dd/deposit/${a.id}` : '#/dd/acr', !!(a && a.abcRef)],
      ['Transfer', t ? `${t.id}, ${t.status}` : 'No transfer application', t ? `${HK} decides` : '–', t ? t.applied : '', t ? `#/dd/transfer/${t.id}` : '#/dd/transfer', !!t],
      ['Redemption', a && a.credit ? `Credit ${a.credit}${a.redeemFor ? ` for ${a.redeemFor}` : ''}` : 'Not applicable yet', a && a.credit ? 'System' : '–', '', a ? `#/dd/deposit/${a.id}` : '#/dd/acr', !!(a && a.credit)]];
    out.push({ title: `${m.name}, ${co.name}`, steps: g });
  });
  return out;
};
screen('dd/trace/:who', { title: 'Trace a participant', role: 'dd', nav: 'trace', ex: 'amir', render: (p, s) => {
  const nm = nameOf(p.who), ch = chain(s, p.who), ids = s.credentials.filter(c => c.owner === p.who).map(c => c.id).concat(s.acrs.filter(a => a.owner === p.who).map(a => a.id), s.transfers.filter(t => t.who === p.who).map(t => t.id));
  return `${head({ crumb: [['Traceability', '#/dd/trace']], title: `Trace: ${nm}`, sub: 'From approval to credit use. Each step shows who acted, when, and links to the record.' })}
  ${ch.length ? ch.map(c => panel(c.title, MC.checks(c.steps.map(([st, what, who, when, go, done]) => [done ? '' : 'n', `<b>${st}</b>: ${what}`, `${who}${when ? ` · ${when}` : ''}`, `<a data-go="${go}">Open</a>`]))).replace('class="panel"', 'class="panel"')).join('') : note('No credentials found for this person.', 'warn')}
  ${MC.auditTable('ddtrace-audit', a => a[1] === nm || ids.some(i => a[3] === i || a[2].includes(i)))}`; } });

/* ---------- flows ---------- */
flow({ id: 'FL-11', title: 'Consent to ABC deposit, confirm ACR, record deposit, see ledger', actor: 'Student, deputy dean', summary: 'Amir gives consent, Hakim finds the ACR in the deposit queue and records the deposit, and the ledger shows the credit',
  steps: [{ go: '#/st/acr/MCMS-A-2026-000214', say: 'Amir opens his Cloud Native Development ACR and gives consent to deposit it in ABC. Click Give consent, then Next step.' },
    { go: '#/dd/acr?tab=queue', say: 'Hakim sees the consented, confirmed ACR in the deposit queue. Only ACR with consent appear here. Open the row.' },
    { go: '#/dd/deposit/MCMS-A-2026-000214', say: 'Open the export for ABC, go back, then enter the ABC reference and date and click Record deposit. The credit becomes Available for 10 years and Amir is told it is storage, not a transfer.' },
    { go: '#/st/ledger', say: 'Amir sees the credit as Available with its expiry date and the ABC reference.' }] });
flow({ id: 'FL-12', title: 'Apply for credit transfer; decide by outcome; provisionally redeemed; second use blocked', actor: 'Student, deputy dean', summary: 'Amir applies, Hakim approves, the credit is held, and a second case with an unmatched outcome is blocked',
  steps: [{ go: '#/st/transfer/TR-2026-0012', say: 'Amir\'s application TR-2026-0012 is under evaluation. It was submitted from the new application page; nothing more to do here.' },
    { go: '#/dd/transfer/TR-2026-0012', say: 'Hakim compares each outcome (all Full) and checks the ACR is deposited, valid and unused. Choose Approve, then Record decision.' },
    { go: '#/st/ledger', say: 'The credit now shows Provisionally redeemed. It cannot be used for another application.' },
    { go: '#/st/transfer/new', say: 'Amir starts another application. The Data Structures credit is no longer selectable and the page says why.' },
    { go: '#/dd/transfer/TR-2026-0009', say: 'Wong Kar Seng\'s case has one outcome with no match. Approve is disabled for that line; you can only refuse it or ask for more evidence. A line whose outcomes are not rated is blocked the same way.' }] });
flow({ id: 'FL-13', title: 'Stackable plan: 25% "Not yet suitable" and a suitable plan', actor: 'Deputy dean', summary: 'Two individual plans for BITZ, one below 30% UTeM credit and one that is suitable',
  steps: [{ go: '#/dd/stack?tab=plans', say: 'Hakim opens the individual plans. Wong Kar Seng is Not yet suitable; Amir is Suitable.' },
    { go: '#/dd/plan/SP-2026-004', say: 'Wong proposes 90 of 120 credits from outside UTeM, so only 25% is earned at UTeM. The 30% minimum fails and the suitable options are off. Note that 30% does not mean 70% transfers.' },
    { go: '#/dd/plan/SP-2026-003', say: 'Amir has 95.8% at UTeM, residency met and admission recorded, so Suitable to proceed is available. Record the decision.' },
    { go: '#/dd/pathway/PW-BITZ-1', say: 'The pathway mapping behind the plans. Try adding Leadership Essentials: an MCE is refused. Then submit for Senate approval.' }] });
flow({ id: 'FL-14', title: 'QV evidence, gate, record QV result; expiry removes claims', actor: 'Lecturer, deputy dean', summary: 'Evidence on the lecturer side, then the deputy dean builds the package, records the submission and the result',
  steps: [{ go: '#/lec/qv/CND', say: 'Dr. Aina keeps the QV evidence for Cloud Native Development. QV expires on 6 Dec 2026, 41 days away.' },
    { go: '#/dd/qv/CND', say: 'Hakim builds the package, records the submission date and reference, then records the result. Try a validity longer than 3 years: it is refused.' },
    { go: '#/dd/qv/CSO', say: 'Cyber Security Operations has no completed cohort, so it cannot be Ready to submit. The button is off and says why.' },
    { go: '#/dd/quality', say: 'The QV list shows alerts at 180, 90 and 30 days, and the lapse rule: when QV ends, the QV claim goes but earned credentials stay.' }] });
flow({ id: 'FL-16', title: 'Suspend an offering with a transition plan', actor: 'Deputy dean', summary: 'Periodic review leads to a suspension with a transition plan; earned credentials stay valid',
  steps: [{ go: '#/dd/quality?tab=periodic', say: 'Hakim sees the periodic reviews that are due. Cloud Native Development is due on 6 Nov.' },
    { go: '#/dd/review/CND', say: 'Choose the outcome "Consider suspension or termination" and record it. That takes you to the suspension screen.' },
    { go: '#/dd/suspend/CND', say: 'The active participants are listed. A transition plan is required. Review the notice, then confirm with a reason. Earned credentials stay valid.' },
    { go: '#/dd/quality?tab=susp', say: 'The micro-credential now shows Suspended.' }] });
flow({ id: 'FL-18', title: 'Trace a participant from approval to credit use; audit trail', actor: 'Deputy dean', summary: 'Follow Amir through every stage, then read the audit rows for the same records',
  steps: [{ go: '#/dd/trace', say: 'Hakim searches for a participant or a credential number.' },
    { go: '#/dd/trace/amir', say: 'The chain runs from approval, offering, enrolment, results, completion and credential to ACR, consent, deposit, transfer and redemption. Each step shows who acted and when.' },
    { go: '#/dd/trace/wong', say: 'Wong Kar Seng\'s chain shows a transfer still under evaluation. Scroll to the audit table at the bottom: every action on these records is listed with who did it and when.' }] });
})();
