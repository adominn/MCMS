/* Common screens: personas, sign-in, profile, notifications, help, search, prototype indexes, errors.
   Also shared builders for reports and the audit trail, used by several roles. */
(function () {
const { screen, modal, act, icon, head, panel, table, tag, field, inp, sel, area, radios, note, checks, dl, tabs } = MC;

/* ---------- personas: the prototype's front door ---------- */
const PERSONAS = [
  ['st', 'amir', 'Student', 'Registers, learns, shares credentials, manages credit and transfer'],
  ['lec', 'aina', 'Lecturer', 'Proposes micro-credentials, runs cohorts, quality review, QV evidence, reviews colleagues'],
  ['dd', 'hakim', 'Deputy dean, FTMK', 'Endorses, records Senate decisions, verifies, confirms completion, ACR, deposits, transfers'],
  ['codl', 'farid', 'CODL administrator', 'Users, roles, settings, templates, UCreds files, adapters, audit. No academic decisions'],
  ['ext', 'siti', 'Industry expert', 'Comments on proposals when invited. Cannot rate or decide'],
];
screen('', { title: 'Personas', shell: 'bare', render: () => `
  <div style="padding:48px 64px;display:flex;flex-direction:column;gap:32px;max-width:1200px">
    <div><div class="brand" style="font-size:14px"><b>UTeM MCMS</b> <span class="muted">Micro-Credential Management System · clickable prototype v2</span></div>
      <h1 style="font-size:42px;line-height:50px;font-weight:300;margin-top:16px">Choose who you are</h1>
      <div class="muted" style="margin-top:8px;max-width:760px">Every button in this prototype does something: opens a screen, a confirmation, a side panel or changes the sample data. Use <b>Flows</b> to walk a process step by step, <b>Hotspots</b> (or press H) to outline everything clickable, and <b>Reset data</b> to start over.</div></div>
    <div class="grid3">${PERSONAS.map(([r, p, role, d]) => `<div class="card" data-go="${MC.roles[r].home}">
      <div class="row" style="gap:12px;align-items:center"><span class="avatar" style="width:40px;height:40px;border-radius:20px;font-size:14px">${MC.people[p].initials}</span><div><div style="font-weight:600">${MC.people[p].name}</div><div class="small muted">${role}</div></div></div>
      <div class="muted">${d}</div><a style="margin-top:auto">Sign in as ${MC.people[p].name.split(' ').slice(-2).join(' ')} ›</a></div>`).join('')}
      <div class="card" data-go="#/public/home"><div class="row" style="gap:12px;align-items:center"><span class="avatar" style="width:40px;height:40px;border-radius:20px;font-size:14px;background:#8d8d8d">TM</span><div><div style="font-weight:600">Public visitor or employer</div><div class="small muted">Tan Mei Ling, no sign-in</div></div></div>
      <div class="muted">Browses the catalogue and pathways, verifies a credential from a link or code</div><a style="margin-top:auto">Open the public site ›</a></div>
      <div class="card" data-go="#/signin"><div style="font-weight:600">Real sign-in screens</div><div class="muted">UTeM Microsoft sign-in, MFA, applicant not recognised, test stub</div><a style="margin-top:auto">Open sign-in ›</a></div>
    </div>
    <div class="row" style="gap:12px"><span class="btn" data-go="#/flows">Walk a flow</span><span class="btn ter" data-go="#/screens">See all screens</span><span class="btn ghost" data-go="#/help">Design rules and help</span></div>
  </div>` });

/* ---------- sign-in (fr-sso, sr-mfa, fr-devidp, A-06) ---------- */
const signFrame = inner => `<div style="min-height:100vh;display:flex;background:var(--s1)"><div style="flex:1;padding:64px;display:flex;flex-direction:column;justify-content:space-between">
  <div class="brand"><b>UTeM MCMS</b> <span class="muted">Micro-Credential Management</span></div>
  <div style="max-width:420px"><h1 style="font-size:42px;line-height:50px;font-weight:300">Micro-credentials at UTeM</h1><div class="muted" style="margin-top:12px">Propose, run and recognise micro-credentials. Track your learning, credentials and credit.</div></div>
  <div class="small muted">Prototype. Sample data only.</div></div>
  <div style="width:520px;background:#fff;padding:64px 56px;display:flex;flex-direction:column;gap:20px;justify-content:center">${inner}</div></div>`;
screen('signin', { title: 'Sign in', shell: 'bare', render: () => signFrame(`
  <h2 style="font-size:28px;line-height:36px;font-weight:400">Sign in</h2>
  <div class="muted">Use your UTeM Microsoft account. Students, staff and registered external learners all sign in the same way. There is no separate MCMS password.</div>
  <span class="btn" data-go="#/signin/mfa" style="justify-content:space-between">Continue with UTeM Microsoft account ${icon('right', 16, '#fff')}</span>
  <div class="small muted">Not registered with UTeM yet? External learners register first at Pejabat Pendaftar or HEPA. <a data-go="#/signin/noaccount">What if my account is not recognised?</a></div>
  <div class="small muted">Participants sign in without the second step: <a data-go="#/st/home">continue as Amir Hakimi</a>.</div>
  <div style="border-top:1px solid var(--hair);padding-top:16px" class="small muted">Test environment only: <a data-go="#/signin/stub">sign in as a test user</a></div>
  <div class="small"><a data-go="#/public/home">Back to the public site</a></div>`) });
screen('signin/mfa', { title: 'Verify it is you', shell: 'bare', render: () => signFrame(`
  <h2 style="font-size:28px;line-height:36px;font-weight:400">Verify it's you</h2>
  <div class="muted">Your role approves or verifies records, so MCMS asks for a second step. Approve the request in Microsoft Authenticator, or enter the code.</div>
  <div data-form class="col">${field('6-digit code', inp('code', '', 'e.g. 482915', true), 'Sent to the Authenticator app on the phone ending 47', true)}
  <span class="btn" data-validate data-go="#/" data-toast="Signed in|Choose a persona to continue the prototype.">Verify and sign in</span></div>
  <a data-toast="New code sent|Check Microsoft Authenticator.">Send a new code</a>`) });
screen('signin/noaccount', { title: 'Account not recognised', shell: 'bare', render: () => signFrame(`
  <h2 style="font-size:28px;line-height:36px;font-weight:400">We couldn't find your UTeM account</h2>
  ${note('The Microsoft account <b>amir.hakimi@outlook.com</b> is not a UTeM account. MCMS has no public sign-up (D-10).', 'warn')}
  <div>To join a micro-credential as an external learner:</div>
  ${checks([['n', 'Register at Pejabat Pendaftar or HEPA', 'Bring your MyKad or passport. You receive a UTeM learner account within 2 working days.'], ['n', 'Sign in again with the new account', 'Your registration can then continue from the offering you chose.']])}
  <div class="row" style="gap:8px"><span class="btn" data-go="#/signin">Try another account</span><span class="btn ter" data-go="#/public/faq">Read the FAQ</span></div>
  <div class="small muted">Account deactivated instead? You'd see: "This account was deactivated on 01/09/2026. Contact codl@utem.edu.my." Your records are kept.</div>`) });
screen('signin/stub', { title: 'Test sign-in', shell: 'bare', render: () => signFrame(`
  <h2 style="font-size:28px;line-height:36px;font-weight:400">Test sign-in</h2>
  ${note('Test environment only. This page cannot be switched on in production (fr-devidp).', 'warn')}
  ${table({ id: 'stubusers', size: 5, cols: [{ h: 'Test user', r: r => `${r[0]}<span class="s">${r[2]}</span>` }, { h: 'Scope', k: 3 }], rows: MC.seed.users, go: r => /Deputy/.test(r[2]) ? '#/dd/queue' : /Lecturer/.test(r[2]) ? '#/lec/home' : /administrator|operator/.test(r[2]) ? '#/codl/users' : /Industry/.test(r[2]) ? '#/ext/home' : '#/st/home' })}`) });

/* ---------- profile (fr-profile, fr-ntprefs, sr-mfa, sr-access) ---------- */
screen('profile', { title: 'My profile', role: null, render: (p, s) => {
  const r = MC.roles[s.as], u = MC.user(r), on = p.tab || 'details';
  const staff = s.as !== 'st';
  return `${head({ crumb: [['Home', r.home], ['My profile']], title: 'My profile', sub: `${u.name} · ${u.post}` })}
  <div data-tabs>${tabs([['details', 'Details'], ['notif', 'Notifications'], ['security', 'Sign-in and sessions'], ['data', 'My personal data']], on)}
  <div data-pane="details"><div class="row"><div class="panel grow"><div class="bd col">
    <div class="row" style="gap:16px;align-items:center"><span class="avatar" style="width:56px;height:56px;border-radius:28px;font-size:18px">${u.initials}</span><div><div style="font-size:20px;line-height:28px">${u.name}</div><div class="muted">${u.post}</div></div></div>
    ${dl([['Email', u.email], [staff ? 'Staff number' : 'Learner ID', u.id], ['Roles', staff ? (s.as === 'lec' ? 'Lecturer (FTMK); Reviewer when invited' : u.post) : 'Participant'], ['Source', 'UTeM Microsoft account. Change these at UTeM HR or Pejabat Pendaftar.']])}
  </div></div>
  <div class="panel" style="width:420px"><div class="hd"><h2>Preferences</h2></div><div class="bd col" data-form>
    ${field('Language for screens, email and certificates', sel('lang', ['English', 'Bahasa Melayu'], s.lang === 'BM' ? 'Bahasa Melayu' : 'English'))}
    ${field('Contact phone', inp('phone', '012-345 6789', ''), 'Used only for urgent notices about your records')}
    ${field('Name on certificates', inp('cname', u.name, ''), 'As in your UTeM record, including bin, binti, a/l or a/p. Ask for a correction if it is wrong.')}
    <span class="btn" style="align-self:flex-start" data-act="saveprefs" data-done="Preferences saved">Save preferences</span></div></div></div></div>
  <div data-pane="notif">${panel('Email notifications', `<div class="col" style="gap:0">${[
    ['Decisions on my records', 'Always sent', true, true], ['Credential issued, corrected or revoked', 'Always sent', true, true], ['Consent requests', 'Always sent', true, true], ['Enrolment changes', 'Always sent', true, true],
    ['Tasks assigned to me', 'In the system and by email', true, false], ['Reminders for overdue tasks', 'After 14 days', true, false], ['Daily digest instead of single emails', 'One email at 08:00', false, false]]
    .filter(x => staff || !/Tasks|Reminders|digest/.test(x[0])).filter(x => !/digest/.test(x[0]) || ['lec', 'dd'].includes(s.as)).map(([t, d, v, lock]) => `<div class="check" style="align-items:center"><div class="grow">${t}<span class="small muted" style="display:block">${d}</span></div>${lock ? '<span class="tag">Required</span>' : `<label class="choice" style="border:0;padding:0"><input type="checkbox" ${v ? 'checked' : ''}> On</label>`}</div>`).join('')}</div>
    <span class="btn" style="margin-top:16px" data-act="saveprefs" data-done="Notification preferences saved">Save</span>`, 'Required notices cannot be switched off')}</div>
  <div data-pane="security"><div class="row">${panel('Sign-in', dl([['Method', 'UTeM Microsoft account (single sign-on)'], ['Second step', staff ? 'Required for your role · Microsoft Authenticator' : 'Not required for participants'], ['Idle sign-out', 'After 30 minutes without activity']]) + `<span class="btn ter" style="margin-top:16px" data-modal="timeout">Preview the idle warning</span>`)}
    <div class="grow">${table({ id: 'sessions', title: 'Active sessions', nopager: true, cols: [{ h: 'Device', k: 0 }, { h: 'Last active', k: 1 }, { h: '', r: r => r[2] ? '<span class="muted">This session</span>' : `<a data-modal="confirm" data-title="Sign out of ${r[0]}?" data-body="That device must sign in again." data-confirm="Sign out device" data-done="Signed out of ${r[0]}">Sign out</a>` }],
      rows: [['Edge on Windows, Melaka', 'Now', true], ['Chrome on Android, Ayer Keroh', '25/10/2026, 21:14', false]] })}</div></div></div>
  <div data-pane="data">${panel('Personal data we hold', `${dl([['Full name', u.name], ['Email', u.email], ['Phone', '012-345 6789'], ['Identity document', 'Held encrypted; shown to you only on request'], ['Used for', 'Enrolment, results, credentials and credit records (PDPA notice accepted 02/08/2025)']])}
    <div class="row" style="gap:8px;margin-top:16px"><span class="btn ter" data-modal="datacorrect">Request a correction</span>${s.as === 'st' ? '<span class="btn ghost" data-go="#/doc/record/amir">Download my record (PDF)</span>' : ''}</div>`)}</div>
  </div>`; } });
modal('datacorrect', { title: 'Request a correction to your personal data', kicker: 'My personal data', body: () => `${field('What is wrong', sel('what', ['Name spelling', 'Email', 'Phone', 'Other']))}${field('What it should be', inp('should', '', '', true), '', true)}
  <div class="small muted">Pejabat Pendaftar checks the request against your documents. Credentials already issued are corrected as a new revision, never overwritten.</div>`, confirm: 'Send request', done: ['Correction requested', 'Reference DC-2026-0118. You will be notified of the outcome.'] });
modal('timeout', { title: 'You will be signed out in 2 minutes', kicker: 'Idle session', noCancel: true,
  body: '<p>You have not used MCMS for 28 minutes. Unsaved changes on this screen will be lost when you are signed out.</p><span class="btn ter" style="align-self:flex-start" data-go="#/signin">Sign out now</span>',
  confirm: 'Stay signed in', done: ['Session extended', 'Idle sign-out is in 30 minutes.', 'info'] });
act('noop', () => {});
act('saveprefs', (c, s) => { s.prefs = s.prefs || {}; s.prefs[s.as] = Object.assign({}, c); if (c.lang) s.lang = c.lang === 'Bahasa Melayu' ? 'BM' : 'EN'; });

/* ---------- notifications (fr-ntevents, fr-ntlog) ---------- */
screen('notifications', { title: 'Notifications', render: (p, s) => {
  const r = MC.roles[s.as];
  return `${head({ crumb: [['Home', r.home], ['Notifications']], title: 'Notifications', sub: `${(s.notifs[s.as] || []).filter(n => n.unread).length} unread`, actions: `<span class="btn ter" data-act="readall" data-done="All marked as read">Mark all read</span><span class="btn ghost" data-go="#/profile?tab=notif">Preferences</span>` })}
  ${tabs([['all', 'All'], ['unread', 'Unread'], ['read', 'Read']], p.f || 'all').replace(/data-tab="(\w+)"/g, 'data-go="#/notifications?f=$1"')}
  ${table({ id: 'notifs', search: 'Search notifications', cols: [
    { h: 'State', w: '90px', r: n => n.unread ? '<span class="tag info">Unread</span>' : '<span class="muted">Read</span>' },
    { h: 'Notification', r: n => `<a data-act="readnotif" data-i="${(s.notifs[s.as] || []).indexOf(n)}">${n.unread ? '<b style="font-weight:600">' + n.t + '</b>' : n.t}</a><span class="s">${n.s}</span>` },
    { h: 'Received', k: 'when' }, { h: 'Sent by email', r: () => 'Yes' }],
    rows: s2 => (s2.notifs[s2.as] || []).filter(n => p.f === 'unread' ? n.unread : p.f === 'read' ? !n.unread : true), empty: 'No notifications in this view.' })}`; } });

/* ---------- help (ir-help) ---------- */
screen('help', { title: 'Help', render: (p, s) => `${head({ title: 'Help and guides', sub: 'Plain-language answers about each recognition stage, and a quick guide for your role' })}
  <div class="grid3">${[['Badge', 'Shows you achieved one module. It is not a certificate and carries no credit on its own.'], ['Certificate and Digital Credential', 'Issued by the system after the deputy dean confirms you completed the whole micro-credential.'], ['Academic Credit Record (ACR)', 'Records credit for an eligible UMC, or a SAMC with valid QV. Never for an MCE.'],
    ['ABC deposit', 'Stores your credit in the Academic Bank of Credit for 10 years. Storage only; it is not a transfer.'], ['Credit transfer', 'The deputy dean of the degree\'s faculty compares learning outcomes and decides each course.'], ['Stackable pathway', 'A published map of micro-credentials to degree courses. At least 30% of credit must still be earned at UTeM.']]
    .map(([t, d]) => `<div class="card"><h3>${t}</h3><div class="muted">${d}</div></div>`).join('')}</div>
  ${panel('Quick guide for your role', checks((MC.roles[s.as] ? MC.roles[s.as].nav.filter(n => n[1]).map(n => ['n', n[2], `Open from the side menu. ${n[0] === MC.roles[s.as].nav[1][0] ? 'This is your home page.' : ''}`]) : [])))}
  ${panel('Contact', 'CODL helpdesk · codl@utem.edu.my · 06-270 2000 (08:00–17:00, Monday to Friday)')}` });

/* ---------- search ---------- */
screen('search', { title: 'Search', render: (p, s) => {
  const q = (p.q || '').toLowerCase();
  // results stay inside the signed-in role's own screens, so a search never switches persona (BR-42)
  const by = {
    dd: () => [...s.mcs.filter(m => m.ptj === 'FTMK').map(m => ({ t: MC.mcName(m.id), k: `${m.cat} · ${m.status}`, go: `#/dd/proposal/${m.id}` })),
      ...s.credentials.map(c => ({ t: `${c.id} · ${c.who}`, k: `Credential · ${c.status}`, go: `#/dd/cred/${c.id}` })),
      ...s.cohorts.filter(c => MC.mc(c.mc) && MC.mc(c.mc).ptj === 'FTMK').map(c => ({ t: `${MC.mcName(c.mc)}, ${c.name}`, k: `Cohort · ${c.status}`, go: '#/dd/offerings' }))],
    lec: () => [...s.mcs.filter(m => m.owner === 'aina').map(m => ({ t: MC.mcName(m.id), k: `${m.cat} · ${m.status}`, go: `#/lec/mc/${m.id}` })),
      ...s.cohorts.filter(c => c.owner === 'aina').map(c => ({ t: `${MC.mcName(c.mc)}, ${c.name}`, k: `Cohort · ${c.status}`, go: `#/lec/cohort/${c.id}` }))],
    codl: () => [...s.users.map(u => ({ t: `${u[0]} · ${u[1]}`, k: `User · ${u[2]}`, go: `#/codl/user/${u[1]}` })),
      ...s.settings.map(x => ({ t: x[1], k: `Setting · ${x[2]}`, go: '#/codl/settings' }))],
    st: () => s.cohorts.filter(c => /Open|Full/.test(c.status)).map(c => ({ t: `${MC.mcName(c.mc)}, ${c.name}`, k: `Offering · ${c.status}`, go: `#/public/offering/${c.id}` })),
  };
  const hits = ((by[s.as] || (() => []))()).filter(h => !q || (h.t + h.k).toLowerCase().includes(q));
  return `${head({ title: `Search: "${p.q || ''}"`, sub: `${hits.length} results in your scope` })}
  ${table({ id: 'search', cols: [{ h: 'Result', k: 't' }, { h: 'Type', k: 'k' }], rows: hits, go: h => h.go, empty: 'Nothing matches. Try a course code such as BITS 1123 or a learner name.' })}`; } });

/* ---------- prototype indexes ---------- */
const ROLE_LABEL = { '': 'Common and sign-in', public: 'Public website', st: 'Student', lec: 'Lecturer', ext: 'Industry expert', dd: 'Deputy dean', codl: 'CODL administrator' };
screen('screens', { title: 'All screens', shell: 'bare', render: () => {
  const groups = {};
  MC.screens.filter(x => !['screens', 'flows', '404'].includes(x.route)).forEach(x => { const k = x.route.startsWith('public') ? 'public' : (x.role || ''); (groups[k] = groups[k] || []).push(x); });
  const link = x => '#/' + x.parts.map(seg => seg[0] === ':' ? encodeURIComponent(x.ex || 'x') : seg).join('/');
  return `<div style="padding:40px 64px;display:flex;flex-direction:column;gap:24px">
    <div class="title"><div><h1>All screens</h1><div class="sub">${MC.screens.length} screens. Click any to open it with sample data.</div></div><div class="act"><span class="btn ter" data-go="#/">Personas</span><span class="btn" data-go="#/flows">Flows</span></div></div>
    <div class="grid3">${Object.keys(ROLE_LABEL).filter(k => groups[k]).map(k => `<div class="panel"><div class="hd"><h2>${ROLE_LABEL[k]}</h2><span class="r">${groups[k].length}</span></div><div class="bd col" style="gap:6px">
      ${groups[k].map(x => `<a data-go="${link(x)}">${x.title}</a>`).join('')}</div></div>`).join('')}</div></div>`; } });
screen('flows', { title: 'Flows', shell: 'bare', render: () => `<div style="padding:40px 64px;display:flex;flex-direction:column;gap:24px">
  <div class="title"><div><h1>Flows</h1><div class="sub">Each flow walks a process across screens and roles. A bar at the bottom tells you what happens at each step and what to click.</div></div><div class="act"><span class="btn ter" data-go="#/">Personas</span><span class="btn ter" data-go="#/screens">All screens</span></div></div>
  ${table({ id: 'flowlist', size: 25, cols: [{ h: 'ID', k: 'id', w: '80px' }, { h: 'Flow', r: f => `${f.title}<span class="s">${f.summary || ''}</span>` }, { h: 'Who', k: 'actor' }, { h: 'Steps', num: true, r: f => f.steps.length }, { h: '', r: f => `<span class="btn ter sm" data-act="startflow" data-id="${f.id}">Start</span>` }],
    rows: () => MC.flows, empty: 'No flows registered yet.' })}</div>` });
act('startflow', c => { MC.startFlow(c.id); return false; });

screen('404', { title: 'Not found', shell: 'bare', render: () => `<div style="padding:64px;display:flex;flex-direction:column;gap:16px;max-width:640px"><h1 style="font-size:28px;font-weight:400">This screen isn't designed yet</h1>
  <div class="muted">The address <code>${location.hash}</code> has no screen in this prototype.</div><div class="row" style="gap:8px"><span class="btn" data-go="#/screens">All screens</span><span class="btn ter" data-act="back">Go back</span></div></div>` });
act('back', () => { history.back(); return false; });
screen('error', { title: 'Something went wrong', shell: 'bare', render: () => `<div style="padding:64px;display:flex;flex-direction:column;gap:16px;max-width:640px"><h1 style="font-size:28px;font-weight:400">Something went wrong</h1>
  <div class="muted">We couldn't complete that request. Nothing was saved. If it happens again, quote this reference to the CODL helpdesk.</div><div><code>ERR-26102026-7F3A</code></div><div class="row" style="gap:8px"><span class="btn" data-act="back">Try again</span><span class="btn ter" data-go="#/">Home</span></div></div>` });

/* ---------- shared: reports (fr-reports, fr-export, nfr-report) ---------- */
MC.REPORTS = [
  ['credentials-issued', 'Credentials issued', 'By micro-credential, cohort and month', 'Credentials issued in 2026'],
  ['workflow-timing', 'Proposal timing', 'Days from submission to approval (KPI-02)', 'Median 34 days in 2026'],
  ['issuance-timing', 'Issuance timing', 'Working days from completion to issue (KPI-04)', 'All within 5 working days'],
  ['enrolment', 'Enrolment and completion', 'Per offering: registered, enrolled, completed, withdrawn', '9 offerings in 2026'],
  ['qv-status', 'QV status and expiry', 'Every SAMC with QV validity and alerts', '1 expiring within 90 days'],
  ['acr-abc', 'ACR and ABC deposits', 'ACR prepared, consented, deposited, discrepancies', '59 deposited in 2026'],
  ['transfer', 'Credit transfer decisions', 'By programme and outcome', '1 under evaluation'],
  ['verification-log', 'Public verification log', 'Verifications and not-found lookups (KPI-03)', '99.6% found'],
  ['claims-check', 'Unsupported claims check', 'Issued records compared with approval and QV (KPI-05)', '0 exceptions'],
  ['audit-completeness', 'Audit completeness', 'Decisions with an audit entry (KPI-06)', '100%'],
];
MC.reportsScreen = (role, nav, scope) => screen(`${role}/reports`, { title: 'Reports', role, nav, render: () => `${head({ title: 'Reports', sub: `Scope: ${scope}. Each report opens on screen and exports as CSV, Excel or PDF.` })}
  <div class="row" style="gap:8px;align-items:center">${field('Year', sel('y', ['2026', '2025']))}${field('Scope', sel('sc', [scope]))}</div>
  ${table({ id: `${role}-reports`, cols: [{ h: 'Report', r: r => `${r[1]}<span class="s">${r[2]}</span>` }, { h: 'Latest figure', k: 3 }, { h: '', r: r => `<a data-go="#/${role}/report/${r[0]}">Open</a>` }], rows: MC.REPORTS, go: r => `#/${role}/report/${r[0]}` })}` });
MC.reportScreen = (role, nav, scope) => screen(`${role}/report/:id`, { title: 'Report', role, nav, ex: 'credentials-issued', render: p => {
  const r = MC.REPORTS.find(x => x[0] === p.id) || MC.REPORTS[0];
  const rows = [['BITS 1123 Data Structures', 'UMC', 3, 25, 26, 0], ['BITI 1213 Linear Algebra', 'UMC', 2, 31, 0, 0], ['Cloud Native Development', 'SAMC', 2, 19, 0, 0], ['Leadership Essentials', 'MCE', 1, 0, 0, 0]];
  return `${head({ crumb: [['Reports', `#/${role}/reports`], [r[1]]], title: r[1], sub: `${r[2]} · ${scope} · 2026 · generated ${MC.state.today} 10:14`, actions: `<span class="btn ter" data-go="#/doc/report/${r[0]}">Full report (PDF)</span><span class="btn ter" data-go="#/doc/report/${r[0]}?csv=1">CSV</span><span class="btn dis" title="Excel export comes in Release 2">Excel · Release 2</span>` })}
  ${MC.kpis([['Credentials issued', '75'], ['Micro-credentials', '4'], ['Corrected', '1'], ['Revoked', '1']])}
  ${table({ id: `rep-${p.id}`, cols: [{ h: 'Micro-credential', k: 0 }, { h: 'Category', k: 1 }, { h: 'Cohorts', k: 2, num: true }, { h: 'Issued', k: 3, num: true }, { h: 'Waiting to issue', k: 4, num: true }, { h: 'Revoked', k: 5, num: true }], rows })}
  <div class="small muted">Reports over 10 seconds run in the background and notify you when ready. Figures count only verified records.</div>`; } });

/* ---------- shared: audit trail (fr-auditview, D-20) ---------- */
MC.auditTable = (id, filter) => table({ id, search: 'Search by person, action or record', tools: `<span class="btn ter sm" style="margin-left:auto" data-go="#/doc/audit">Export</span>`,
  cols: [{ h: 'When (MYT)', k: 0, w: '170px' }, { h: 'Who', k: 1 }, { h: 'What', k: 2 }, { h: 'Record', r: a => `<code>${a[3]}</code>` }],
  rows: s => filter ? s.audit.filter(filter) : s.audit, empty: 'No audit entries for this record.' });
})();
