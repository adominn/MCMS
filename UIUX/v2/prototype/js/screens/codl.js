/* CODL administrator (S1/P5, Farid Hashim): users and roles, reference data, settings, templates, UCreds exchange, adapters, mailbox, audit, reports, retention.
   CODL is admin and technical only (D-12): nothing here approves, verifies, issues, prepares ACR, deposits, decides a transfer or sets the module credit flag.
   Where an admin might expect such a button, a note names who decides. */
(function () {
const { screen, modal, drawer, act, flow, head, panel, table, tag, field, inp, sel, area, note, dl, tabs, kpis, track } = MC;
const MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const fd = iso => (iso ? `${+iso.slice(8, 10)} ${MN[+iso.slice(5, 7) - 1]} ${iso.slice(0, 4)}` : '');
const toIso = d => { const m = /^(\d+) (\w+) (\d{4})$/.exec(d || ''); return m ? `${m[3]}-${String(MN.indexOf(m[2]) + 1).padStart(2, '0')}-${m[1].padStart(2, '0')}` : '2026-10-26'; };
const NOW = '11:20';
const aud = (s, what, rec) => s.audit.unshift([`${s.today}, ${NOW}`, 'Farid Hashim', what, rec]);
const ROLES = ['Participant', 'Lecturer', 'Reviewer', 'Deputy dean', 'CODL operator', 'System administrator', 'Industry expert'];
const ASSIGNABLE = ROLES.slice(1);
const PTJ_CODES = ['FTMK', 'FKE', 'FPTT', 'FTKIP', 'CODL'];
const decider = who => `<div class="note"><div>${who}</div></div>`;
const clean = t => String(t == null ? '' : t).replace(/\s*\((?:BR|D|OI)-\d+\)/g, '').replace(/,\s*(?:BR|D|OI)-\d+/g, '').replace(/^(?:BR|D|OI)-\d+$/, '');
const OWNER = { S01842: 'aina', S01177: 'rosli' };
const PF = ['name', 'credential', 'issuer', 'date', 'status'];
const today0 = s => toIso(s.today);
/* roles and settings with a future effective date start when the demo clock reaches it */
const applyDue = s => { const t = today0(s);
  s.roleAssigns.forEach(a => { if (a.status === 'Scheduled' && toIso(a.eff) <= t) { a.status = 'Assigned'; syncRoles(s, a.user); } });
  s.settingHistory.forEach(h => { if (h.sched && toIso(h.eff) <= t) { h.sched = false; const r = s.settings.find(x => x[0] === h.key), q = s.approvalRoutes.find(x => x.key === h.key); if (r) r[2] = h.new; else if (q) q.role = h.new; } }); };
const splitNum = v => { const m = /^(\d[\d,\s]*?)\s*([^\d,\s].*)?$/.exec(v || ''); return m ? { n: m[1].trim(), unit: m[2] || '' } : null; };

/* ---------- seed ---------- */
const ra = (id, user, role, scope, reason, eff, by = 'Farid Hashim') => ({ id, user, role, scope, reason, eff, by, status: 'Assigned' });
Object.assign(MC.seed, {
  roleAssigns: [
    ra('RA-001', 'S00921', 'Deputy dean', 'PTj: FTMK', 'Appointed deputy dean (Academic), letter UTeM/R/2026/011', '1 Jan 2026'),
    ra('RA-002', 'S00954', 'Deputy dean', 'PTj: FKE', 'Appointed deputy dean (Academic), letter UTeM/R/2026/012', '1 Jan 2026'),
    ra('RA-003', 'S01842', 'Lecturer', 'PTj: FTMK', 'Staff record from HR', '3 Feb 2025', 'System (SSO first sign-in)'),
    ra('RA-004', 'S01842', 'Reviewer', 'Micro-credentials: CSO', 'Invited to review Cyber Security Operations Fundamentals', '23 Oct 2026'),
    ra('RA-005', 'S01177', 'Lecturer', 'PTj: FTMK', 'Staff record from HR', '3 Feb 2025', 'System (SSO first sign-in)'),
    ra('RA-006', 'S01177', 'Reviewer', 'Micro-credentials: DB', 'Peer review of Database Systems', '1 Oct 2026'),
    ra('RA-007', 'S01420', 'Lecturer', 'PTj: FKE', 'Staff record from HR', '3 Feb 2025', 'System (SSO first sign-in)'),
    ra('RA-008', 'S01388', 'Lecturer', 'PTj: FKE', 'Staff record from HR', '3 Feb 2025', 'System (SSO first sign-in)'),
    ra('RA-009', 'S02210', 'System administrator', 'Whole university', 'Initial set-up of MCMS', '1 Oct 2024', 'System (initial set-up)'),
    ra('RA-010', 'S02231', 'CODL operator', 'Whole university', 'CODL staff for UCreds exchange', '1 Oct 2024'),
    ra('RA-011', 'X2026-0050', 'Industry expert', 'Micro-credentials: ADA', 'Invited by Dr. Aina Zulkifli to comment on Applied Data Analytics', '20 Oct 2026'),
  ],
  approvalRoutes: [
    { key: 'approval.route.proposal', type: 'Endorse a proposal', role: 'Deputy dean of the proposing PTj' },
    { key: 'approval.route.senate', type: 'Record a Senate decision', role: 'Deputy dean of the proposing PTj' },
    { key: 'approval.route.results', type: 'Verify results', role: 'Deputy dean of the offering PTj' },
    { key: 'approval.route.completion', type: 'Confirm completion', role: 'Deputy dean of the offering PTj' },
    { key: 'approval.route.acr', type: 'Confirm ACR', role: 'Deputy dean of the offering PTj' },
    { key: 'approval.route.transfer', type: 'Decide a credit transfer', role: 'Deputy dean of the degree faculty' },
    { key: 'approval.route.disposal', type: 'Approve disposal of records', role: 'Registrar (Pejabat Pendaftar)' },
  ],
  demoClock: '', banner: null, ucReport: null, ucJob: null,
  tpls: [
    { id: 'cert-umc', kind: 'Certificate', name: 'Certificate of completion, UMC', ver: 3, status: 'Published', updated: '14 Sep 2026',
      en: 'This certifies that {{participant_name}} ({{participant_id}}) completed {{mc_name}}, {{credits}} credits, offered by {{ptj}}, on {{completion_date}}. Verify this certificate at {{verify_url}}.',
      bm: 'Dengan ini disahkan bahawa {{participant_name}} ({{participant_id}}) telah menamatkan {{mc_name}}, {{credits}} kredit, yang ditawarkan oleh {{ptj}}, pada {{completion_date}}. Sahkan sijil ini di {{verify_url}}.',
      hist: [[3, '14 Sep 2026', 'Farid Hashim', 'Added verification link'], [2, '2 Mar 2026', 'Farid Hashim', 'Credits shown in words and figures'], [1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'cert-samc', kind: 'Certificate', name: 'Certificate of completion, SAMC', ver: 2, status: 'Published', updated: '2 Mar 2026',
      en: 'This certifies that {{participant_name}} completed {{mc_name}}, {{credits}} credits, MQF level {{mqf_level}}, on {{completion_date}}. {{qv_statement}} Verify at {{verify_url}}.',
      bm: 'Dengan ini disahkan bahawa {{participant_name}} telah menamatkan {{mc_name}}, {{credits}} kredit, tahap MQF {{mqf_level}}, pada {{completion_date}}. {{qv_statement}} Sahkan di {{verify_url}}.',
      hist: [[2, '2 Mar 2026', 'Farid Hashim', 'QV statement added'], [1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'cert-mce', kind: 'Certificate', name: 'Certificate of attendance, MCE', ver: 1, status: 'Published', updated: '1 Oct 2025',
      en: 'This certifies that {{participant_name}} completed {{mc_name}} on {{completion_date}}. This certificate carries no academic credit.',
      bm: 'Dengan ini disahkan bahawa {{participant_name}} telah menamatkan {{mc_name}} pada {{completion_date}}. Sijil ini tidak mempunyai kredit akademik.',
      hist: [[1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'cred-display', kind: 'Credential display', name: 'Digital credential page', ver: 2, status: 'Published', updated: '3 Mar 2026',
      en: '{{participant_name}} achieved {{mc_name}}. Issued by {{issuer}} on {{issue_date}}. Status: {{status}}.',
      bm: '{{participant_name}} telah mencapai {{mc_name}}. Dikeluarkan oleh {{issuer}} pada {{issue_date}}. Status: {{status}}.',
      hist: [[2, '3 Mar 2026', 'Farid Hashim', 'Only the fields on the public verification page'], [1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'rec-statement', kind: 'Recognition statement', name: 'Recognition statement at registration', ver: 2, status: 'Published', updated: '10 Aug 2026',
      en: 'By completing {{mc_name}} you receive: {{recognition_list}}. Completion, ACR, ABC deposit and credit transfer are separate decisions. {{credit_statement}}',
      bm: 'Dengan menamatkan {{mc_name}} anda akan menerima: {{recognition_list}}. Penamatan, ACR, deposit ABC dan pindahan kredit ialah keputusan yang berasingan. {{credit_statement}}',
      hist: [[2, '10 Aug 2026', 'Farid Hashim', 'Separate decisions sentence added'], [1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'n-results', kind: 'Notification', name: 'Results verified', ver: 1, status: 'Published', updated: '1 Oct 2025',
      en: 'Dear {{participant_name}}, your results for {{mc_name}} have been verified. Result: {{result}}.',
      bm: 'Salam {{participant_name}}, keputusan anda bagi {{mc_name}} telah disahkan. Keputusan: {{result}}.', hist: [[1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'n-consent', kind: 'Notification', name: 'Consent request for ABC deposit', ver: 2, status: 'Published', updated: '24 Oct 2026',
      en: 'Dear {{participant_name}}, your ACR for {{mc_name}} is confirmed. Please give, decline or postpone consent to deposit it in the Academic Bank of Credit: {{consent_url}}.',
      bm: 'Salam {{participant_name}}, ACR anda bagi {{mc_name}} telah disahkan. Sila beri, tolak atau tangguh kebenaran untuk mendepositkannya dalam Bank Kredit Akademik: {{consent_url}}.',
      hist: [[2, '24 Oct 2026', 'Farid Hashim', 'Postpone option added'], [1, '1 Oct 2025', 'Farid Hashim', 'First version']] },
    { id: 'n-transfer', kind: 'Notification', name: 'Credit transfer decision', ver: 1, status: 'Published', updated: '1 Oct 2025',
      en: 'Dear {{participant_name}}, the decision on credit transfer {{transfer_ref}} is {{decision}}. See the reasons in MCMS.',
      bm: '', hist: [[1, '1 Oct 2025', 'Farid Hashim', 'First version, English only']] },
  ],
  mailbox: [
    { id: 'M-0412', to: 'Hakim Rahman', addr: 'hakim.rahman@utem.edu.my', lang: 'EN', subject: 'Results ready to verify: Data Structures 2026/2', status: 'Failed', when: '26 Oct 2026, 09:40', err: 'SMTP 421 service not available',
      body: 'Dear Hakim Rahman,\n\nDr. Aina Zulkifli submitted 28 results for Data Structures, Cohort 2026/2. 27 pass and 1 does not.\n\nOpen the task: #/dd/verify/DS-2026-2\n\nUTeM MCMS' },
    { id: 'M-0413', to: 'Hakim Rahman', addr: 'hakim.rahman@utem.edu.my', lang: 'EN', subject: 'Credit transfer TR-2026-0012 submitted', status: 'Failed', when: '26 Oct 2026, 09:52', err: 'SMTP 421 service not available',
      body: 'Dear Hakim Rahman,\n\nAmir Hakimi applied for credit transfer TR-2026-0012 (1 line, 3 credits). The application is waiting for your decision.\n\nUTeM MCMS' },
    { id: 'M-0414', to: 'Amir Hakimi', addr: 'amir.hakimi@learner.utem.edu.my', lang: 'BM', subject: 'Kebenaran diperlukan: deposit kredit dalam ABC', status: 'Failed', when: '26 Oct 2026, 10:05', err: 'SMTP 421 service not available',
      body: 'Salam Amir Hakimi,\n\nACR anda bagi Cloud Native Development telah disahkan pada 24/10/2026. Sila beri, tolak atau tangguh kebenaran untuk mendepositkannya dalam Bank Kredit Akademik.\n\nUTeM MCMS' },
    { id: 'M-0411', to: 'Dr. Aina Zulkifli', addr: 'aina.z@utem.edu.my', lang: 'EN', subject: '2 marks missing in Cloud Native Development 2026/2', status: 'Sent', when: '25 Oct 2026, 08:01',
      body: 'Dear Dr. Aina Zulkifli,\n\nThe UCreds import of 25 Oct left 2 marks empty: Daniel Ong (project mark) and Wong Kar Seng (module 2 mark). Enter them in the cohort page before submitting for verification.\n\nUTeM MCMS' },
    { id: 'M-0410', to: 'Dr. Rosli Hamzah', addr: 'rosli.h@utem.edu.my', lang: 'EN', subject: 'Review requested: Cyber Security Operations Fundamentals', status: 'Sent', when: '23 Oct 2026, 14:10',
      body: 'Dear Dr. Rosli Hamzah,\n\nYou are asked to review Cyber Security Operations Fundamentals. Please declare any conflict of interest before opening the proposal. Due 30/10/2026.\n\nUTeM MCMS' },
    { id: 'M-0409', to: 'Amir Hakimi', addr: 'amir.hakimi@learner.utem.edu.my', lang: 'BM', subject: 'Kredit anda telah didepositkan dalam ABC', status: 'Sent', when: '20 Oct 2026, 15:41',
      body: 'Salam Amir Hakimi,\n\nKredit Data Structures (3 kredit) telah didepositkan dalam ABC. Rujukan ABC-MY-2026-0004418. Sah sehingga 20/10/2036.\n\nUTeM MCMS' },
    { id: 'M-0408', to: 'Ir. Siti Aminah Yusof', addr: 'siti.aminah@cloudworks.my', lang: 'EN', subject: 'Invitation to review: Applied Data Analytics with Python', status: 'Sent', when: '22 Oct 2026, 09:00',
      body: 'Dear Ir. Siti Aminah Yusof,\n\nYou are invited to comment on Applied Data Analytics with Python, FTMK. Comments are due 06/11/2026. You can comment only; you cannot rate or decide.\n\nUTeM MCMS' },
    { id: 'M-0407', to: 'Lee Jia Hui', addr: 'leejh@learner.utem.edu.my', lang: 'EN', subject: 'We received your credential correction request', status: 'Sent', when: '23 Oct 2026, 11:03',
      body: 'Dear Lee Jia Hui,\n\nWe received your request to correct the name on credential MCMS-C-2026-000418. The deputy dean will decide. You will be told the outcome.\n\nUTeM MCMS' },
  ],
  retentionCats: [['Audit trail', 'Append-only; never edited'], ['Credentials and ACR records', 'Issued and revoked records'], ['Results and achievements', 'Verified and provisional'], ['Payment records', 'Dummy service in this release'], ['Personal data', 'Profiles and contact details'], ['UCreds files', 'Imported and exported CSV files'], ['Notification log', 'Emails and in-system notices']],
  disposals: [
    { id: 'DP-2026-004', cat: 'UCreds files', what: 'Enrolment exports before Jan 2026 (6 files)', since: '20 Oct 2026', approver: 'Registrar, Pejabat Pendaftar', status: 'Waiting for approval' },
    { id: 'DP-2026-003', cat: 'Notification log', what: 'Test emails from the pilot (212 entries)', since: '14 Oct 2026', approver: 'Registrar, Pejabat Pendaftar', status: 'Waiting for approval' },
  ],
});
const failedMail = s => s.mailbox.filter(m => m.status === 'Failed');

/* ================= users and roles (fr-userlist, fr-roleassign, fr-scope, fr-multirole) ================= */
const syncRoles = (s, uid) => {
  const all = s.roleAssigns.filter(a => a.user === uid); if (!all.length) return;
  const row = s.users.find(u => u[1] === uid); if (!row) return;
  row[2] = [...new Set(all.filter(a => a.status === 'Assigned').map(a => a.role))].join('; ') || 'No active role';
};
const userEmail = id => { const p = Object.values(MC.people).find(x => x.id === id); return p && p.email ? p.email : 'From the UTeM Microsoft account'; };

screen('codl/users', { title: 'Users and roles', role: 'codl', nav: 'users', render: (p, s) => {
  const rf = p.role || 'All roles', pf = p.ptj || 'All PTj', sf = p.status || 'All statuses';
  const rows = s.users.filter(u => (rf === 'All roles' || u[2].includes(rf)) && (pf === 'All PTj' || u[3].startsWith(pf)) && (sf === 'All statuses' || u[5] === sf));
  const so = (k, list, v) => `<select class="input" data-ufilter="${k}" style="width:170px;height:32px" aria-label="Filter by ${k}">${list.map(o => `<option ${o === v ? 'selected' : ''}>${o}</option>`).join('')}</select>`;
  const bad = s.adapters.filter(a => a.status === 'Failing'), miss = s.tpls.filter(t => !t.bm || !t.en), dp = s.disposals.filter(d => d.status === 'Waiting for approval').length,
    ip = s.imports.filter(i => i.dir === 'Import' && i.problems.length && (s.cohorts.find(c => c.id === i.cohort) || {}).results === 'Draft').length;
  const need = [...bad.map(a => [`${a.name} is failing: ${a.err}`, '#/codl/adapters']), ...(miss.length ? [[`${miss.length} template${miss.length > 1 ? 's' : ''} missing a language: ${miss.map(t => t.name).join(', ')}`, `#/codl/template/${miss[0].id}`]] : []),
    ...(dp ? [[`${dp} disposal request${dp > 1 ? 's' : ''} waiting for the Registrar (CODL cannot approve)`, '#/codl/retention']] : []), ...(ip ? [[`${ip} import with problems: the lecturer still has marks to enter`, '#/codl/ucreds']] : [])];
  return `${head({ title: 'Users and roles', sub: `${s.users.length} accounts from UTeM single sign-on. Accounts are never deleted; deactivating keeps every record.`, actions: `<span class="btn" data-modal="assignrole">Assign role</span>` })}
  ${panel('Needs you', need.length ? need.map(n => `<div style="padding:5px 0"><a data-go="${n[1]}">${n[0]}</a></div>`).join('') : '<div class="small muted">Nothing waits on you.</div>')}
  ${kpis([['Active accounts', s.users.filter(u => u[5] === 'Active').length], ['Deactivated', s.users.filter(u => u[5] !== 'Active').length], ['Staff roles assigned', s.roleAssigns.filter(a => a.status === 'Assigned').length], ['Roles removed', s.roleAssigns.filter(a => a.status === 'Revoked').length]])}
  ${table({ id: 'codl-users', size: 10, search: 'Search by name or staff / matric number',
    tools: `${so('role', ['All roles', ...ROLES], rf)}${so('ptj', ['All PTj', ...PTJ_CODES, 'All'], pf)}${so('status', ['All statuses', 'Active', 'Deactivated'], sf)}`,
    cols: [{ h: 'Name', r: u => u[0] }, { h: 'Staff / matric no.', r: u => `<code>${u[1]}</code>` }, { h: 'Roles', k: 2 }, { h: 'PTj', k: 3 }, { h: 'Since', k: 4 }, { h: 'Status', r: u => tag(u[5], u[5] === 'Active' ? 'ok' : 'warn') }],
    rows, go: u => `#/codl/user/${u[1]}`, empty: 'No account matches these filters.' })}
  ${note('CODL assigns who can act. It does not make academic decisions: the deputy dean approves, verifies and confirms; roles listed here only decide who may see and do those things.')}`;
}, mount: root => root.querySelectorAll('[data-ufilter]').forEach(el => el.addEventListener('change', () => {
  const q = new URLSearchParams(); root.querySelectorAll('[data-ufilter]').forEach(x => q.set(x.dataset.ufilter, x.value)); MC.go(`#/codl/users?${q}`);
})) });

screen('codl/user/:id', { title: 'User', role: 'codl', nav: 'users', ex: 'S01842', render: (p, s) => {
  const u = s.users.find(x => x[1] === p.id);
  if (!u) return `${head({ crumb: [['Users and roles', '#/codl/users'], ['Not found']], title: 'No such account' })}${note('No account has this number. Go back to the list and search again.', 'warn')}`;
  const me = u[1] === 'S02210', off = u[5] !== 'Active', mine = s.roleAssigns.filter(a => a.user === u[1]);
  const rows = [...(u[2].includes('Participant') || !mine.length ? [{ role: 'Participant', scope: 'Own records only', reason: 'Created automatically at first sign-in', eff: u[4], by: 'System', status: 'Assigned', auto: true }] : []), ...mine];
  const sole = mine.filter(a => a.role === 'Deputy dean' && a.status === 'Assigned').find(a => !s.roleAssigns.some(b => b.id !== a.id && b.role === 'Deputy dean' && b.scope === a.scope && b.status === 'Assigned'));
  const deact = me ? '<span class="btn dis">Deactivate: you cannot deactivate your own account</span>' : sole ? `<span class="btn dis">Deactivate: assign another deputy dean for ${sole.scope.replace('PTj: ', '')} first</span>` : `<span class="btn danger ter" data-modal="deactivate" data-uid="${u[1]}" data-name="${u[0]}">Deactivate account</span>`;
  const action = off ? `<span class="btn ter" data-modal="reactivate" data-uid="${u[1]}" data-name="${u[0]}">Reactivate account</span><span class="btn dis">Assign role (reactivate first)</span>`
    : `${deact}${me ? '<span class="btn dis">Assign role: you cannot change your own roles</span>' : `<span class="btn" data-modal="assignrole" data-uid="${u[1]}">Assign role</span>`}`;
  return `${head({ crumb: [['Users and roles', '#/codl/users'], [u[0]]], title: u[0], sub: `${u[1]} · ${u[3]}`, status: tag(u[5], off ? 'warn' : 'ok'), actions: action })}
  ${off ? note('This account is deactivated. The person cannot sign in. Their results, credentials and audit entries are kept.', 'warn') : ''}
  <div class="row">${panel('Profile from single sign-on', dl([['Name', u[0]], ['Staff / matric no.', u[1]], ['Email', userEmail(u[1])], ['PTj', u[3]], ['In MCMS since', u[4]]]) + '<div class="small muted" style="margin-top:12px">Read-only. Changes are made at UTeM HR or Pejabat Pendaftar and arrive at the next sign-in.</div>').replace('class="panel"', 'class="panel grow"')}
  <div style="width:380px">${panel('Separation of duties', `<div class="small">One person may hold several roles, including an administrator role and an academic role. MCMS checks each record: whoever entered a result cannot be its only verifier, and nobody reviews their own proposal.</div>`)}</div></div>
  ${table({ id: 'codl-roles', title: 'Role assignments', nopager: true, cols: [{ h: 'Role', r: a => a.role }, { h: 'Scope', k: 'scope' }, { h: 'Reason', r: a => a.reason }, { h: 'Effective', k: 'eff' }, { h: 'Assigned by', k: 'by' }, { h: 'Status', r: a => a.status === 'Assigned' && off && !a.auto ? tag('Inactive (account deactivated)', 'warn') : tag(a.status, a.status === 'Assigned' ? 'ok' : a.status === 'Scheduled' ? 'info' : 'warn') },
    { h: '', r: a => a.auto ? '<span class="muted small">Automatic</span>' : (a.status !== 'Assigned' && a.status !== 'Scheduled') || off ? '' : me ? '<span class="muted small">Cannot remove your own</span>' : `<a data-modal="removerole" data-id="${a.id}" data-role="${a.role}" data-name="${u[0]}">Remove</a>` }],
    rows, empty: 'No roles.' })}`;
} });

modal('assignrole', { title: c => (c.uid ? `Assign a role to ${(MC.state.users.find(u => u[1] === c.uid) || [''])[0]}` : 'Assign a role'), kicker: 'Users and roles',
  body: (c, s) => `${c.uid ? '' : field('Person', sel('uid', s.users.filter(u => u[5] === 'Active').map(u => `${u[1]} ${u[0]}`)), 'Search the list by typing a number or name', true)}
    ${field('Role', sel('role', ASSIGNABLE), '', true)}${field('Scope', sel('scopeType', ['Whole university', 'PTj', 'Specific micro-credentials']), 'Users see only records inside their scope', true)}
    ${field('Scope detail', sel('scopeDetail', ['None', ...s.ptj.map(r => r[0]), ...s.mcs.map(m => `${m.id} · ${m.name}`)]), 'Needed for PTj or specific micro-credentials. A reviewer or industry expert is always limited to one micro-credential.')}
    ${field('Effective date', inp('eff', '2026-10-26', '', true, 'date'), 'A future date keeps the role dormant until then', true)}
    ${note('Holding an academic role as well as an administrator role is allowed. Separation of duties is checked on each record.')}`,
  reason: 'Reason (kept in the audit trail)', confirm: 'Assign role', act: 'roleassign', done: ['Role recorded', 'It works from the effective date; a future date shows as Scheduled. The change is in the audit trail.'] });
act('roleassign', (c, s) => {
  const uid = (c.uid || '').split(' ')[0], u = s.users.find(x => x[1] === uid); if (!u) return false;
  const err = (a, b) => { MC.toast(a, b, 'err'); return false; };
  if (uid === 'S02210') return err('Not allowed', 'You cannot change your own roles. Another system administrator must do it.');
  if (c.eff < today0(s) && c.eff) return err('Date in the past', 'The effective date cannot be before today.');
  const det = (c.scopeDetail || 'None').split(' ')[0], isPtj = s.ptj.some(r => r[0] === det), isMc = !isPtj && s.mcs.some(m => m.id === det);
  if ((c.role === 'Reviewer' || c.role === 'Industry expert') && c.scopeType !== 'Specific micro-credentials') return err('Scope not allowed', `${c.role} is limited to one micro-credential. Choose Specific micro-credentials.`);
  if (c.scopeType === 'PTj' && !isPtj) return err('Scope detail needed', 'Choose the PTj this role covers.');
  if (c.scopeType === 'Specific micro-credentials' && !isMc) return err('Scope detail needed', 'Choose the micro-credential this role covers.');
  if (c.role === 'Reviewer' && OWNER[uid] && (s.mcs.find(m => m.id === det) || {}).owner === OWNER[uid]) return err('Conflict of roles', `${u[0]} owns ${det}, so cannot also review it.`);
  const scope = c.scopeType === 'Whole university' ? 'Whole university' : c.scopeType === 'PTj' ? `PTj: ${det}` : `Micro-credentials: ${det}`;
  if (s.roleAssigns.some(a => a.user === uid && a.role === c.role && a.scope === scope && a.status !== 'Revoked')) return err('Already assigned', `${u[0]} already holds ${c.role} for this scope.`);
  const later = c.eff > today0(s), id = `RA-${String(s.roleAssigns.length + 1).padStart(3, '0')}`;
  s.roleAssigns.push(Object.assign(ra(id, uid, c.role, scope, c.reason, fd(c.eff) || s.today, 'Farid Hashim'), later ? { status: 'Scheduled' } : {}));
  syncRoles(s, uid); aud(s, `Assigned role ${c.role} (${scope}) to ${u[0]}, ${later ? 'starting' : 'effective'} ${fd(c.eff)}: ${c.reason}`, uid);
});
modal('removerole', { title: c => `Remove ${c.role} from ${c.name}?`, kicker: 'Users and roles', danger: true, reason: 'Reason (kept in the audit trail)', confirm: 'Remove role', act: 'roleremove',
  body: () => '<p>The person loses this role at once. Records they already handled stay as they were, with their name. They are told by email.</p>', done: ['Role removed', 'Recorded in the audit trail.'] });
act('roleremove', (c, s) => { const a = s.roleAssigns.find(x => x.id === c.id); a.status = 'Revoked'; a.revokedReason = c.reason; syncRoles(s, a.user); aud(s, `Removed role ${a.role} (${a.scope}) from ${c.name}: ${c.reason}`, a.user); });
modal('deactivate', { title: c => `Deactivate ${c.name}?`, kicker: 'Users and roles', danger: true, reason: 'Reason (kept in the audit trail)', confirm: 'Deactivate account', act: 'userdeact',
  body: (c, s) => { const rs = s.roleAssigns.filter(a => a.user === c.uid && a.status === 'Assigned'), dd = rs.some(a => a.role === 'Deputy dean'), n = s.tasks.filter(t => !t.done).length;
    return `<p>The person can no longer sign in. Their roles stop working. Results, credentials, ACR and audit entries stay and are not deleted. You can reactivate the account later.</p>
    <p class="small">Active roles that stop: ${rs.length ? rs.map(a => `${a.role} (${a.scope})`).join('; ') : 'none besides Participant'}.${dd ? ` ${n} open deputy dean tasks stay unassigned until another deputy dean takes the role.` : ''}</p>`; }, done: ['Account deactivated', 'Records are kept.'] });
act('userdeact', (c, s) => { if (c.uid === 'S02210') { MC.toast('Not allowed', 'You cannot deactivate your own account.', 'err'); return false; } s.users.find(u => u[1] === c.uid)[5] = 'Deactivated'; aud(s, `Deactivated account ${c.name}: ${c.reason}`, c.uid); });
modal('reactivate', { title: c => `Reactivate ${c.name}?`, kicker: 'Users and roles', confirm: 'Reactivate account', act: 'userreact', body: () => '<p>The person can sign in again. Roles removed earlier are not restored; assign them again if needed.</p>', done: ['Account reactivated', 'Recorded in the audit trail.'] });
act('userreact', (c, s) => { s.users.find(u => u[1] === c.uid)[5] = 'Active'; aud(s, `Reactivated account ${c.name}`, c.uid); });

/* ================= reference data (fr-refdata) ================= */
const IMP = {
  ptj: { file: 'ptj-2026-10.csv', read: 2, valid: [['ICC', 'Innovation and Commercialisation Centre']], bad: [[3, 'FTMK', 'code already exists']] },
  prog: { file: 'programmes-2026-10.csv', read: 2, valid: [{ code: 'BITE', name: 'Bachelor of Computer Science (Computer Networking) with Honours', ptj: 'FTMK', credits: 120, mqf: 6, mode: 'Conventional', accredited: 'to 2030' }], bad: [[3, 'BITX', 'MQF level 9 is outside 1 to 8']] },
  course: { file: 'courses-2026-10.csv', read: 4, valid: [{ code: 'BITS 3153', name: 'Machine Learning Basics', credits: 3, slt: 120, clos: 4 }, { code: 'BITS 3163', name: 'Computer Networks', credits: 3, slt: 120, clos: 3 }], bad: [[3, 'BITS 2343', 'code already exists'], [5, 'BITS 3173', 'learning time 100 hours does not equal credits x 40 (120)']] },
};
screen('codl/refdata', { title: 'Reference data', role: 'codl', nav: 'refdata', render: (p, s) => {
  const btns = (kind, label) => `<span class="btn ter sm" data-modal="refimport" data-kind="${kind}">Import CSV</span><span class="btn sm" data-modal="refform" data-kind="${kind}">Add ${label}</span>`;
  return `${head({ title: 'Reference data', sub: 'PTj, programmes and parent courses. Used by proposals, credit transfer and pathways. Changes are audited.' })}
  <div data-tabs>${tabs([['ptj', 'PTj', s.ptj.length], ['prog', 'Programmes', s.programmes.length], ['course', 'Parent courses', s.courses.length]], 'ptj')}
  <div data-pane="ptj">${table({ id: 'codl-ref-ptj', right: btns('ptj', 'PTj'), cols: [{ h: 'Code', r: r => `<code>${r.r[0]}</code>` }, { h: 'Name', r: r => r.r[1] }, { h: 'Micro-credentials', num: true, r: r => s.mcs.filter(m => m.ptj === r.r[0]).length }, { h: '', r: r => `<a data-modal="refform" data-kind="ptj" data-i="${r.i}">Edit</a>` }], rows: s.ptj.map((r, i) => ({ r, i })) })}</div>
  <div data-pane="prog">${table({ id: 'codl-ref-prog', right: btns('prog', 'programme'), cols: [{ h: 'Code', r: r => `<code>${r.r.code}</code>` }, { h: 'Name', r: r => `${r.r.name}<span class="s">${r.r.ptj}</span>` }, { h: 'MQF level', num: true, r: r => r.r.mqf }, { h: 'Mode', r: r => r.r.mode }, { h: 'Accreditation', r: r => r.r.accredited }, { h: 'Total credits', num: true, r: r => r.r.credits }, { h: '', r: r => `<a data-modal="refform" data-kind="prog" data-i="${r.i}">Edit</a>` }], rows: s.programmes.map((r, i) => ({ r, i })) })}</div>
  <div data-pane="course">${table({ id: 'codl-ref-course', right: btns('course', 'course'), size: 10, search: 'Search courses', cols: [{ h: 'Code', r: r => `<code>${r.r.code}</code>` }, { h: 'Name', r: r => r.r.name }, { h: 'Programme', r: r => r.r.prog || '—' }, { h: 'Credit', num: true, r: r => r.r.credits }, { h: 'Learning time (hours)', num: true, r: r => r.r.slt }, { h: 'CLOs', num: true, r: r => r.r.clos }, { h: '', r: r => `<a data-modal="refform" data-kind="course" data-i="${r.i}">Edit</a>` }], rows: s.courses.map((r, i) => ({ r, i })) })}</div></div>`;
} });
const LBL = { ptj: 'PTj', prog: 'programme', course: 'parent course' };
modal('refform', { title: c => `${c.i === undefined ? 'Add' : 'Edit'} ${LBL[c.kind]}`, kicker: 'Reference data', reason: true, confirm: 'Save', act: 'refsave', done: ['Saved', 'Reference data updated and recorded in the audit trail.'],
  body: (c, s) => {
    const i = c.i === undefined ? null : +c.i;
    if (c.kind === 'ptj') { const r = i === null ? ['', ''] : s.ptj[i]; return field('Code', inp('code', r[0], 'e.g. FTMK', true), '', true) + field('Name', inp('name', r[1], '', true), '', true); }
    if (c.kind === 'prog') { const r = i === null ? { code: '', name: '', ptj: 'FTMK', credits: 120, mqf: 6, mode: 'Conventional', accredited: '' } : s.programmes[i];
      return field('Code', inp('code', r.code, '', true), '', true) + field('Name', inp('name', r.name, '', true), '', true) + field('PTj', sel('ptj', PTJ_CODES, r.ptj)) + `<div class="row">${field('MQF level (1 to 8)', inp('mqf', r.mqf, '', true, 'number'), '', true)}${field('Total credits', inp('credits', r.credits, '', true, 'number'), '', true)}</div>`
        + field('Mode', sel('mode', ['Conventional', 'Open and distance', 'Part-time'], r.mode)) + field('Accreditation', inp('accredited', r.accredited, 'e.g. to 2029'), 'Status and end year as in the MQA record'); }
    const r = i === null ? { code: '', name: '', credits: 3, slt: 120, clos: 3 } : s.courses[i];
    const used = i === null ? [] : s.mcs.filter(m => m.code === r.code && m.status === 'Approved');
    return (used.length ? note(`Used by ${used.map(m => `${m.name} v${m.version} (${m.status})`).join(', ')}. Approved versions keep their values; new proposals use the new ones.`, 'warn') : '')
      + field('Code', inp('code', r.code, 'e.g. BITS 1123', true), '', true) + field('Name', inp('name', r.name, '', true), '', true) + field('Programme', sel('prog', ['None', ...s.programmes.map(p => p.code)], r.prog || 'None'), 'The programme that owns this course') + `<div class="row">${field('Credit', inp('credits', r.credits, '', true, 'number'), '', true)}${field('Learning time (hours)', inp('slt', r.slt, '', true, 'number'), 'Credit x 40 hours', true)}${field('CLOs', inp('clos', r.clos, '', true, 'number'), '', true)}</div>`;
  } });
act('refsave', (c, s) => {
  const i = c.i === undefined ? null : +c.i, code = c.code.trim(), err = t => { MC.toast('Not saved', t, 'err'); return false; };
  const list = c.kind === 'ptj' ? s.ptj.map(r => r[0]) : (c.kind === 'prog' ? s.programmes : s.courses).map(r => r.code);
  if (list.some((x, k) => x === code && k !== i)) return err(`The code ${code} already exists.`);
  if (c.kind === 'ptj') { const r = [code, c.name.trim()]; i === null ? s.ptj.push(r) : (s.ptj[i] = r); }
  else if (c.kind === 'prog') {
    if (+c.mqf < 1 || +c.mqf > 8) return err('MQF level must be from 1 to 8.');
    const r = { code, name: c.name.trim(), ptj: c.ptj, credits: +c.credits, mqf: +c.mqf, mode: c.mode, accredited: c.accredited.trim() || 'Not recorded' }; i === null ? s.programmes.push(r) : (s.programmes[i] = r);
  } else {
    if (+c.slt !== +c.credits * 40) return err(`Learning time must equal credit x 40 hours (${+c.credits * 40} hours for ${c.credits} credits).`);
    const r = { code, name: c.name.trim(), credits: +c.credits, slt: +c.slt, clos: +c.clos, prog: c.prog === 'None' ? '' : c.prog }; i === null ? s.courses.push(r) : (s.courses[i] = r);
  }
  aud(s, `${i === null ? 'Added' : 'Edited'} ${LBL[c.kind]} ${code}: ${c.reason}`, 'Reference data');
});
modal('refimport', { title: c => `Import ${LBL[c.kind]} list from CSV`, kicker: 'Reference data', confirm: c => `Import ${IMP[c.kind].valid.length} valid row${IMP[c.kind].valid.length > 1 ? 's' : ''}`, act: 'refimport',
  body: c => { const m = IMP[c.kind]; return `${field('CSV file', `<input class="input" type="file" accept=".csv"><div class="small muted" style="margin-top:6px">Sample file chosen: ${m.file}</div>`)}
    ${note(`${m.file}: ${m.read} rows read, ${m.valid.length} valid, ${m.bad.length} rejected. Rejected rows are not imported.`, 'warn')}
    <ul class="small" style="margin:8px 0 0 18px">${m.bad.map(b => `<li>Row ${b[0]} (${b[1]}): ${b[2]}</li>`).join('')}</ul>`; } });
act('refimport', (c, s) => {
  const m = IMP[c.kind]; let n = 0;
  m.valid.forEach(v => { if (c.kind === 'ptj') { if (!s.ptj.some(x => x[0] === v[0])) { s.ptj.push(v); n++; } } else { const L = c.kind === 'prog' ? s.programmes : s.courses; if (!L.some(x => x.code === v.code)) { L.push(Object.assign({}, v)); n++; } } });
  aud(s, `Imported ${m.file}: ${n} added, ${m.bad.length} rejected`, 'Reference data'); MC.toast(n ? 'Import finished' : 'Nothing new to import', n ? `${n} added, ${m.bad.length} rejected. Download the row report from the import log.` : 'These rows were imported before.', n ? 'ok' : 'info');
});

/* ================= settings (fr-settings, fr-settinghist, fr-approverbody, D-16) ================= */
const lastChange = (s, k) => s.settingHistory.find(h => h.key === k);
screen('codl/settings', { title: 'Settings', role: 'codl', nav: 'settings', render: (p, s) => {
  const pending = s.settingHistory.filter(h => h.sched);
  return `${head({ title: 'Settings', sub: 'Values the rules read. Every change needs a reason and an effective date. A decision uses the value in force on the day it was taken.' })}
  ${pending.length ? note(`${pending.length} scheduled change${pending.length > 1 ? 's' : ''}: ${pending.map(h => `${h.key} becomes ${h.new} on ${h.eff}`).join('; ')}.`) : ''}
  ${table({ id: 'codl-settings', size: 10, search: 'Search settings', cols: [
    { h: 'Setting', r: r => `${r[1]}<span class="s"><code>${r[0]}</code></span>` }, { h: 'Value', r: r => `<b style="font-weight:600">${clean(r[2])}</b>` }, { h: 'Source', r: r => clean(r[3]) },
    { h: 'Last changed', r: r => { const h = lastChange(s, r[0]); return h ? `${h.when}<span class="s">${h.by}</span>` : '<span class="muted">Initial value</span>'; } },
    { h: '', r: r => `<a data-modal="setchange" data-key="${r[0]}">Change</a> &nbsp; <a data-drawer="sethist" data-key="${r[0]}">History</a>` }], rows: s.settings })}
  ${note('The module credit flag is not a setting. Whether a module carries credit is decided with the programme and the deputy dean, not by CODL.')}
  ${table({ id: 'codl-routes', title: 'Approval routes per decision type', nopager: true, right: '<span class="small muted">approval.route.*</span>', cols: [{ h: 'Decision', r: r => `${r.type}<span class="s"><code>${r.key}</code></span>` }, { h: 'Decided by', r: r => `<b style="font-weight:600">${r.role}</b>` }, { h: '', r: r => `<a data-modal="routechange" data-key="${r.key}">Change</a> &nbsp; <a data-drawer="sethist" data-key="${r.key}">History</a>` }], rows: s.approvalRoutes })}
  ${panel('Test environment only', `<div class="grid3" style="grid-template-columns:1fr 1fr">
    <div class="col" data-form><h3>Demo clock</h3><div class="small muted">Today is ${s.today}${s.demoClock ? ' (set by the demo clock)' : ''}. Move it to show expiry alerts and overdue reminders.</div>
      ${field('Pretend today is', inp('day', s.demoClock || '2026-10-26', '', true, 'date'), '', true)}
      <div class="row" style="gap:8px"><span class="btn ter" data-validate data-act="demoset">Set demo date</span>${s.demoClock ? '<span class="btn ghost" data-act="democlear">Back to the real date</span>' : ''}</div>
      ${note('This cannot be switched on in production. The setting is absent from the production build.', 'warn')}</div>
    <div class="col" data-form><h3>Maintenance banner</h3>${s.banner ? note(`Scheduled: "${s.banner.text}" from ${s.banner.when}.`) : '<div class="small muted">No banner scheduled.</div>'}
      ${field('Starts on', `<div class="row" style="gap:8px">${inp('bday', '', '', true, 'date')}${inp('btime', '22:00', '', true, 'time')}</div>`, 'At least 48 hours from now', true)}
      ${field('Message', area('btext', 'MCMS will be unavailable for maintenance from 22:00 to 23:00.', '', true), '', true)}
      <div class="row" style="gap:8px"><span class="btn ter" data-validate data-act="bannerset">Schedule banner</span>${s.banner ? '<span class="btn ghost" data-act="bannerclear">Cancel banner</span>' : ''}</div></div></div>`, 'Hidden in production')}`;
} });
modal('setchange', { title: c => `Change ${c.key}`, kicker: 'Settings', reason: true, confirm: 'Change setting', act: 'setchange', done: ['Setting saved', 'Recorded in the history and the audit trail.'],
  body: (c, s) => { const r = s.settings.find(x => x[0] === c.key), sn = splitNum(r[2]), pub = c.key === 'verification.public_fields', cur = r[2].toLowerCase();
    return `${dl([['Setting', r[1]], ['Current value', clean(r[2])], ['Source', clean(r[3]) || '—']])}${pub
      ? field('Fields on the public page', `<div class="col" style="gap:4px">${PF.map(f => `<label><input type="checkbox" name="pf_${f}" value="${f}" ${cur.includes(f) ? 'checked' : ''}> ${f[0].toUpperCase() + f.slice(1)}</label>`).join('')}</div>`, 'Only these five fields may appear. National ID numbers and contact details cannot be added.', true)
      : field('New value', inp('nv', sn ? sn.n : clean(r[2]), '', true), sn ? `Numbers only${sn.n.includes(',') ? ', separated by commas' : ''}. The unit${sn.unit ? ` (${sn.unit})` : ''} is kept.` : '', true)}${field('Effective date', inp('eff', '2026-10-26', '', true, 'date'), 'Today or later. Decisions already taken keep the value that applied on their date', true)}`; } });
act('setchange', (c, s) => {
  const r = s.settings.find(x => x[0] === c.key), err = t => { MC.toast('Not saved', t, 'err'); return false; };
  if (c.eff < today0(s)) return err('The effective date cannot be before today.');
  let nv;
  if (c.key === 'verification.public_fields') { const on = PF.filter(f => c['pf_' + f]); if (!on.length) return err('Choose at least one field.'); nv = on.map((f, k) => (k ? f : f[0].toUpperCase() + f.slice(1))).join(', '); }
  else { nv = (c.nv || '').trim(); const sn = splitNum(r[2]);
    if (sn) { if (!/^\d+(\s*,\s*\d+)*$/.test(nv)) return err('Enter numbers only' + (sn.n.includes(',') ? ', separated by commas.' : '.')); nv = nv + (sn.unit ? (sn.unit === '%' ? '' : ' ') + sn.unit : ''); } }
  if (nv === r[2]) return err('The new value is the same as the current one.');
  const later = c.eff > today0(s);
  s.settingHistory.unshift({ key: c.key, old: r[2], new: nv, by: 'Farid Hashim', when: s.today, reason: c.reason, eff: fd(c.eff), sched: later });
  if (!later) r[2] = nv;
  aud(s, `Changed ${c.key} ${s.settingHistory[0].old} → ${nv}${later ? ` (from ${fd(c.eff)})` : ''}: ${c.reason}`, 'Settings');
});
drawer('sethist', { title: c => `History: ${c.key}`, body: (c, s) => {
  const h = s.settingHistory.filter(x => x.key === c.key);
  return `${note('Decisions use the value in force on the day they were taken, so a change never rewrites an earlier decision.')}${h.length ? h.map(x => `<div class="panel"><div class="bd">${dl([['Changed', `${clean(x.old)} → <b>${clean(x.new)}</b>`], ['When', x.when], ['Effective', x.eff || x.when], ['By', x.by], ['Reason', x.reason]])}${x.sched ? '<div style="margin-top:8px">' + tag('Scheduled', 'info') + '</div>' : ''}</div></div>`).join('') : '<div class="empty">No changes since this value was first set.</div>'}`; } });
modal('routechange', { title: c => `Change who decides: ${(MC.state.approvalRoutes.find(r => r.key === c.key) || {}).type}`, kicker: 'Approval route', reason: true, confirm: 'Change route', act: 'routechange', done: ['Route changed', 'New decisions go to the new role. Decisions already waiting stay where they are.'],
  body: (c, s) => { const r = s.approvalRoutes.find(x => x.key === c.key);
    return `${dl([['Decision', r.type], ['Now decided by', r.role]])}${field('Decided by', sel('role', ['Deputy dean of the proposing PTj', 'Deputy dean of the offering PTj', 'Deputy dean of the degree faculty', 'Dean of the PTj', 'Registrar (Pejabat Pendaftar)'], r.role), '', true)}${field('Effective date', inp('eff', '2026-10-26', '', true, 'date'), 'Today or later', true)}${note('CODL administrator is not offered: administrators do not decide.')}`; } });
act('routechange', (c, s) => { const r = s.approvalRoutes.find(x => x.key === c.key); if (r.role === c.role) { MC.toast('Nothing to change', 'That role already decides this.', 'err'); return false; }
  if (c.eff < today0(s)) { MC.toast('Not saved', 'The effective date cannot be before today.', 'err'); return false; }
  const later = c.eff > today0(s);
  s.settingHistory.unshift({ key: c.key, old: r.role, new: c.role, by: 'Farid Hashim', when: s.today, reason: c.reason, eff: fd(c.eff), sched: later }); aud(s, `Changed ${c.key} ${r.role} → ${c.role}${later ? ` (from ${fd(c.eff)})` : ''}: ${c.reason}`, 'Settings'); if (!later) r.role = c.role; });
act('demoset', (c, s) => { s.demoClock = c.day; s.today = fd(c.day); applyDue(s); aud(s, `Set demo clock to ${s.today}`, 'Settings'); MC.toast('Demo date set', `Today is now ${s.today} in this test environment. Scheduled roles and settings that are due have started.`, 'warn'); });
act('democlear', (c, s) => { s.demoClock = ''; s.today = '26 Oct 2026'; aud(s, 'Restored the real date (26 Oct 2026)', 'Settings'); MC.toast('Real date restored', 'Today is 26 Oct 2026.', 'info'); });
act('bannerset', (c, s) => {
  const hrs = (new Date(`${c.bday}T${c.btime}`) - new Date(`${toIso(s.today)}T${NOW}`)) / 36e5;
  if (!(hrs >= 48)) { MC.toast('Too soon', `A banner needs at least 48 hours notice. This one starts in ${Math.max(0, Math.round(hrs))} hours.`, 'err'); return false; }
  s.banner = { when: `${fd(c.bday)} ${c.btime}`, text: c.btext.trim() }; aud(s, `Scheduled maintenance banner for ${s.banner.when}`, 'Settings'); MC.toast('Banner scheduled', 'Users see it from 48 hours before.', 'ok');
});
act('bannerclear', (c, s) => { s.banner = null; MC.toast('Banner cancelled', '', 'info'); });

/* ================= templates (fr-templates, fr-ntlang) ================= */
const phOf = t => [...new Set((t.en + t.bm).match(/\{\{\w+\}\}/g) || [])];
const SAMPLE = { participant_name: 'Amir Hakimi', participant_id: 'X2026-0193', mc_name: 'Cloud Native Development', credits: '2', ptj: 'FTMK', completion_date: '12/07/2026', verify_url: 'mcms.utem.edu.my/v/8TWN-5C2E', mqf_level: '4', qv_statement: 'Quality-Verified to 06/12/2026.', issuer: 'UTeM', issue_date: '12/07/2026', status: 'Issued', result: 'Pass, 78', consent_url: 'mcms.utem.edu.my/consent', transfer_ref: 'TR-2026-0012', decision: 'Approved', recognition_list: 'module badges, certificate, digital credential', credit_statement: 'Credit is recorded only for eligible micro-credentials.' };
const fill = t => t.replace(/\{\{(\w+)\}\}/g, (m, k) => SAMPLE[k] || m);
screen('codl/templates', { title: 'Templates', role: 'codl', nav: 'templates', render: (p, s) => `${head({ title: 'Templates', sub: 'Notices, certificates, credential pages and recognition statements. Each has a Bahasa Melayu and an English text; both are needed to publish.' })}
  ${table({ id: 'codl-tpls', size: 10, search: 'Search templates', cols: [{ h: 'Template', r: t => `${t.name}<span class="s"><code>${t.id}</code></span>` }, { h: 'Type', k: 'kind' }, { h: 'Languages', r: t => `${t.bm ? tag('BM', 'ok') : tag('BM missing', 'warn')} ${t.en ? tag('EN', 'ok') : tag('EN missing', 'warn')}` }, { h: 'Version', num: true, r: t => `v${t.ver}` }, { h: 'Status', r: t => tag(t.status, t.status === 'Published' ? 'ok' : 'info') }, { h: 'Updated', k: 'updated' }], rows: s.tpls, go: t => `#/codl/template/${t.id}` })}` });
screen('codl/template/:id', { title: 'Template', role: 'codl', nav: 'templates', ex: 'cert-umc', render: (p, s) => {
  const t = s.tpls.find(x => x.id === p.id);
  if (!t) return `${head({ crumb: [['Templates', '#/codl/templates'], ['Not found']], title: 'No such template' })}`;
  const miss = !t.bm || !t.en;
  return `${head({ crumb: [['Templates', '#/codl/templates'], [t.name]], title: t.name, sub: `${t.kind} · version ${t.ver} · updated ${t.updated}`, status: tag(t.status, t.status === 'Published' ? 'ok' : 'info'),
    actions: `<span class="btn ter" data-drawer="tplprev" data-id="${t.id}">Preview</span>${miss ? '<span class="btn dis">Publish blocked: one language is empty</span>' : `<span class="btn" data-modal="tplpublish" data-id="${t.id}">Publish new version</span>`}` })}
  ${miss ? note(`${!t.bm ? 'Bahasa Melayu' : 'English'} text is missing. Notices fall back to the other language until both are written. Fill it in, then publish.`, 'warn') : ''}
  <div data-form><div class="row">
    <div class="panel grow"><div class="hd"><h2>Bahasa Melayu</h2><span class="r">${t.bm ? tag('Written', 'ok') : tag('Missing', 'warn')}</span></div><div class="bd">${area('bm', t.bm, 'Tulis teks di sini')}</div></div>
    <div class="panel grow"><div class="hd"><h2>English</h2><span class="r">${t.en ? tag('Written', 'ok') : tag('Missing', 'warn')}</span></div><div class="bd">${area('en', t.en, 'Write the text here')}</div></div></div>
  <div class="row" style="gap:8px;margin-top:12px"><span class="btn ter" data-act="tplsave" data-id="${t.id}">Save draft</span><span class="btn ghost" data-drawer="tplprev" data-id="${t.id}">Preview with sample data</span></div></div>
  ${panel('Placeholders in this template', `<div class="row" style="gap:6px;flex-wrap:wrap">${phOf(t).map(x => `<code>${x}</code>`).join(' ') || '<span class="muted">None</span>'}</div><div class="small muted" style="margin-top:8px">Placeholders are replaced with the person's own data when the text is used. Keep them exactly as written, in both languages.</div>`)}
  ${table({ id: 'codl-tplhist', title: 'Version history', nopager: true, cols: [{ h: 'Version', r: h => `v${h[0]}` }, { h: 'Published', k: 1 }, { h: 'By', k: 2 }, { h: 'What changed', k: 3 }], rows: t.hist })}`;
} });
act('tplsave', (c, s) => { const t = s.tpls.find(x => x.id === c.id); t.bm = c.bm.trim(); t.en = c.en.trim(); if (t.status === 'Published') t.status = 'Draft changes'; aud(s, `Saved draft of template ${t.id}`, t.id); MC.toast('Draft saved', 'Nothing changes for users until you publish.', 'ok'); });
drawer('tplprev', { title: 'Preview with sample data', body: (c, s) => { const t = s.tpls.find(x => x.id === c.id), bm = c.bm !== undefined ? c.bm : t.bm, en = c.en !== undefined ? c.en : t.en;
  return `<div class="small muted">Sample participant: Amir Hakimi. Unknown placeholders stay visible.</div><div class="panel"><div class="hd"><h2>English</h2></div><div class="bd">${en ? fill(en) : '<span class="muted">No English text yet.</span>'}</div></div><div class="panel"><div class="hd"><h2>Bahasa Melayu</h2></div><div class="bd">${bm ? fill(bm) : '<span class="muted">Teks Bahasa Melayu belum ditulis.</span>'}</div></div>`; } });
modal('tplpublish', { title: c => `Publish a new version of ${c.id}?`, kicker: 'Templates', confirm: 'Publish new version', act: 'tplpublish', done: ['New version published', 'Used for notices and documents created from now on.'],
  body: (c, s) => { const t = s.tpls.find(x => x.id === c.id); return `<p>Version ${t.ver + 1} replaces version ${t.ver} for new notices and documents. Documents already issued keep the wording they were issued with. Nobody is notified.</p>${field('What changed', inp('note', '', 'e.g. Wording of the QV sentence', true), '', true)}`; } });
act('tplpublish', (c, s) => { const t = s.tpls.find(x => x.id === c.id), ph = x => new Set(x.match(/\{\{\w+\}\}/g) || []), a = ph(t.en), b = ph(t.bm), err = m => { MC.toast('Not published', m, 'err'); return false; };
  const noBm = [...a].filter(x => !b.has(x)), noEn = [...b].filter(x => !a.has(x));
  if (noBm.length) return err(`The Bahasa Melayu text is missing ${noBm.join(', ')}.`);
  if (noEn.length) return err(`The English text is missing ${noEn.join(', ')}.`);
  const bad = /guarantee|automatic(ally)? (credit|transfer|degree)|equivalent to a degree/i.exec(t.en + ' ' + t.bm);
  if (bad) return err(`The wording "${bad[0]}" promises something MCMS cannot promise. Reword it and save the draft.`);
  t.ver++; t.status = 'Published'; t.updated = s.today; t.hist.unshift([t.ver, s.today, 'Farid Hashim', c.note]); aud(s, `Published template ${t.id} v${t.ver}: ${c.note}`, t.id); });

/* ================= UCreds exchange ================= */
const FILES = () => [...MC.state.imports.filter(i => i.dir === 'Import' && i.cohort !== 'LA-2026-2').map(i => i.file), 'results-wrong-template.csv'];
const jobHtml = j => `${track([[j.state === 'queued' ? 'now' : 'done', 'Queued', 'Waiting for a free worker'], [j.state === 'running' ? 'now' : j.state === 'done' ? 'done' : '', 'Running', 'Reading rows and checking each one'], [j.state === 'done' ? 'done' : '', 'Done', 'Staged rows are Provisional']])}<div class="small muted" style="margin-top:8px">${j.file}: ${j.state === 'done' ? `${j.staged} rows staged as Provisional, ${j.rejected} rejected.` : 'in progress'}</div>`;
screen('codl/ucreds', { title: 'UCreds exchange', role: 'codl', nav: 'ucreds', render: (p, s) => {
  const r = s.ucReport, open = s.cohorts.filter(c => /Open|Running|Full|Ended/.test(c.status));
  return `${head({ title: 'UCreds exchange', sub: 'Export enrolments to the learning platform and import results back, by CSV file. The connection is manual in this release.' })}
  <div class="row" style="align-items:flex-start">
  <div class="panel grow" data-form><div class="hd"><h2>Export enrolments</h2></div><div class="bd col">
    ${field('Offering', sel('offering', open.map(c => `${c.id} · ${MC.mcName(c.mc)}`)), 'Enrolled participants only')}
    ${field('Template version', sel('tv', ['UCreds enrolment template v3 (current)', 'UCreds enrolment template v2 (until 31 Jul 2026)']))}
    <span class="btn ter" style="align-self:flex-start" data-act="ucexport">Export CSV</span></div></div>
  <div class="panel grow" data-form><div class="hd"><h2>Import results</h2></div><div class="bd col">
    ${field('File', sel('file', FILES()), 'Sample files for the prototype. Imports use the same template version as the export.')}
    <span class="btn ter" style="align-self:flex-start" data-act="ucvalidate">Check file</span></div></div></div>
  ${note('Imported results are Provisional. Nothing becomes an achievement or badge until the deputy dean verifies the cohort. CODL cannot verify.')}
  ${r ? panel('Validation report', r.fileError ? `${note(r.fileError, 'err')}<div class="small muted" style="margin:8px 0">No rows were staged. Fix the template and upload the file again.</div><span class="btn ter sm" data-go="#/doc/import/${r.file}">Open report</span>`
    : `${r.dup ? note(`${r.file} was already imported on ${r.dup}. Staging it again replaces the Provisional rows from that import; nothing is duplicated.`, 'warn') : ''}${kpis([['Rows read', r.rows], ['Staged as Provisional', r.rows - r.problems.length], ['Rejected', r.problems.length]])}
      ${r.problems.length ? table({ id: 'codl-rejected', size: 10, cols: [{ h: 'Row', num: true, r: x => x[0] }, { h: 'Learner', r: x => x[1] }, { h: 'Reason', r: x => x[2] }], rows: r.problems }) : '<div class="small muted">Every row passed: format, learner match, score range and duplicates.</div>'}
      <div class="row" style="gap:8px;margin-top:12px"><span class="btn ter sm" data-go="#/doc/import/${r.file}">Open report</span>${r.done ? tag('Imported', 'ok') : `<span class="btn" data-modal="ucimport">Stage ${r.rows - r.problems.length} rows</span>`}</div>
      ${r.problems.length ? `<div class="small muted" style="margin-top:8px">Rejected rows: ${MC.co(r.cohort) && MC.co(r.cohort).owner === 'aina' ? 'Dr. Aina Zulkifli' : 'the lecturer'} is told which marks are missing and enters them on the cohort page.</div>` : ''}`, r.done ? 'Done' : 'Not imported yet') : ''}
  ${s.ucJob ? panel('Job progress', `<div id="codl-job">${jobHtml(s.ucJob)}</div>`) : ''}
  ${table({ id: 'codl-imports', title: 'Import and export log', size: 5, search: 'Search file names', cols: [{ h: 'File', r: i => `<code>${i.file}</code>` }, { h: 'Direction', k: 'dir' }, { h: 'Rows', num: true, k: 'rows' }, { h: 'Result', r: i => tag(i.result) }, { h: 'When', k: 'when' },
    { h: '', r: i => i.problems.length ? `<a data-drawer="improb" data-i="${i.i}">${i.problems.length} problem${i.problems.length > 1 ? 's' : ''}</a>` : '' }], rows: s.imports.map((x, i) => Object.assign({ i }, x)) })}`;
}, mount: (root, p, s) => {
  const steps = ['queued', 'running', 'done']; if (!s.ucJob || s.ucJob.state === 'done') return;
  const adv = () => { const j = MC.state.ucJob, el = document.getElementById('codl-job'); if (!j || !el || j.state === 'done') return; j.state = steps[steps.indexOf(j.state) + 1]; MC.save(); el.innerHTML = jobHtml(j);
    if (j.state === 'done') MC.toast('Import finished', `${j.staged} rows staged as Provisional. ${j.told || ''}`); else setTimeout(adv, 1300); };
  setTimeout(adv, 1000);
} });
act('ucexport', (c, s) => {
  const id = c.offering.split(' ')[0], co = s.cohorts.find(x => x.id === id), n = co.enrolled || co.taken || 0;
  if (!n) { MC.toast('Nothing to export', `${id} has no enrolled participants yet.`, 'err'); return false; }
  s.imports.unshift({ file: `enrol-${id}.csv`, dir: 'Export', rows: n, result: 'Ready', when: `${s.today.replace(/ 2026$/, '')}, ${NOW}`, cohort: id, problems: [] }); aud(s, `Exported enrol-${id}.csv (${n} rows, ${c.tv.split(' (')[0]})`, id);
  MC.toast('Export ready', `enrol-${id}.csv, ${n} participants. Upload it to UCreds by hand.`, 'ok');
  setTimeout(() => MC.go('#/doc/export/' + id), 0); // open the file preview with its CSV download
});
act('ucvalidate', (c, s) => {
  const f = c.file, src = s.imports.find(i => i.file === f);
  s.ucReport = f === 'results-wrong-template.csv' ? { file: f, fileError: 'File rejected: column "module3_mark" is missing. This file uses template v2; v3 is required. All rows are rejected.', rows: 0, problems: [] }
    : { file: f, rows: src.rows, problems: src.problems, cohort: src.cohort, done: false, dup: src.dir === 'Import' ? src.when : '' };
  s.ucJob = null; MC.toast('File checked', s.ucReport.fileError ? 'The whole file is rejected.' : `${src.rows - src.problems.length} rows can be staged, ${src.problems.length} rejected.`, s.ucReport.fileError || src.problems.length ? 'warn' : 'ok'); return;
});
modal('ucimport', { title: 'Stage these results?', kicker: 'UCreds exchange', confirm: 'Stage results', act: 'ucimport',
  body: (c, s) => `<p>${s.ucReport.rows - s.ucReport.problems.length} rows from ${s.ucReport.file} are stored as <b>Provisional</b>. ${s.ucReport.problems.length} rejected rows are not stored. No achievement or badge is created, and nobody is told their result is final. ${s.ucReport.problems.length ? 'The lecturer is told which marks are missing.' : 'The deputy dean is told the results are staged.'}</p>` });
act('ucimport', (c, s) => { const r = s.ucReport; r.done = true; const staged = r.rows - r.problems.length;
  s.imports = s.imports.filter(i => !(i.file === r.file && i.dir === 'Import'));
  const co = MC.co(r.cohort), nm = co ? `${MC.mcName(co.mc)} ${co.name.replace('Cohort ', '')}` : r.cohort, when = s.today.replace(/ 2026$/, ''); let told;
  if (r.problems.length) { s.notifs.lec.unshift({ t: `${r.problems.length} marks missing in ${nm}`, s: `UCreds import ${when}`, when, go: `#/lec/cohort/${r.cohort}`, unread: true }); told = `${co && co.owner === 'aina' ? 'Dr. Aina Zulkifli is' : 'The lecturer is'} told which marks are missing.`; }
  else { s.notifs.dd.unshift({ t: `Results staged: ${nm}`, s: `${staged} rows Provisional, UCreds import ${when}`, when, go: '#/dd/results', unread: true }); told = 'The deputy dean is told.'; }
  s.imports.unshift({ file: r.file, dir: 'Import', rows: r.rows, result: r.problems.length ? `${staged} imported, ${r.problems.length} problem${r.problems.length > 1 ? 's' : ''}` : 'All imported', when: `${s.today.replace(/ 2026$/, '')}, ${NOW}`, cohort: r.cohort, problems: r.problems });
  aud(s, `Imported ${r.file}: ${staged} staged, ${r.problems.length} rejected`, r.cohort); s.ucJob = { file: r.file, state: 'queued', staged, rejected: r.problems.length, told }; });
drawer('improb', { title: c => `Problems in ${MC.state.imports[+c.i].file}`, body: (c, s) => { const i = s.imports[+c.i];
  return `${table({ id: 'codl-probs', size: 10, cols: [{ h: 'Row', num: true, r: x => x[0] }, { h: 'Learner', r: x => x[1] }, { h: 'Reason', r: x => x[2] }], rows: i.problems })}<div class="small muted">These rows were not stored. The lecturer enters missing marks on the cohort page; for an unknown matric number, Pejabat Pendaftar corrects the learner record.</div>
    <span class="btn ter sm" style="align-self:flex-start" data-go="#/doc/import/${i.file}">Open row report</span>`; } });

/* ================= adapters and mailbox (fr-adapterstatus, fr-ihregistry, fr-ihretry, fr-ntlog) ================= */
const MODE_NOTE = { 'UCreds learning platform': 'Files are exchanged by hand. A live connector is Release 2.', 'UTeM single sign-on': 'Test users only. Real sign-in needs the UTeM Microsoft connection.', 'Email notifications': 'Sends real email through the UTeM mail server.',
  Payment: 'A dummy service takes no real money.', ABC: 'The deputy dean records deposits by hand. No connector exists.', 'QVC / MQA': 'Packages are downloaded and sent by hand.', 'Credential signing': 'Signed with a test key pair, not the production key.' };
const modeKind = m => /Live/.test(m) ? 'Live' : /Stub/.test(m) ? 'Stub' : 'Manual';
screen('codl/adapters', { title: 'Adapter status', role: 'codl', nav: 'adapters', render: (p, s) => {
  const bad = s.adapters.filter(a => a.status === 'Failing');
  return `${head({ title: 'Adapter status', sub: 'Each outside connection runs in one of three modes: stub (test double), manual (a person moves files) or live.' })}
  ${bad.map(a => note(`${a.name} is failing: ${a.err}. A failed call never blocks the business process; the message waits and retries.`, 'err')).join('')}
  ${table({ id: 'codl-adapters', nopager: true, cols: [{ h: 'Adapter', r: a => `${a.name}<span class="s">${MODE_NOTE[a.name] || ''}</span>` }, { h: 'Mode', r: a => `${tag(modeKind(a.mode), modeKind(a.mode) === 'Live' ? 'ok' : 'info')}<span class="s">${a.mode}</span>` }, { h: 'Last run', k: 'last' }, { h: 'Status', r: a => tag(a.status, a.status === 'No connector' ? '' : undefined) }, { h: 'Errors', k: 'err' },
    { h: '', r: a => a.status === 'Failing' ? `<a data-drawer="aderr" data-name="${a.name}">Errors</a> &nbsp; <a data-modal="adretry" data-name="${a.name}">Retry ${failedMail(s).length}</a>` : '' }], rows: s.adapters })}`;
} });
drawer('aderr', { title: c => `Errors: ${c.name}`, body: (c, s) => { const f = failedMail(s);
  return f.length ? `${note('The mail server answered "service not available" (SMTP 421) from 09:40. Retries happen at increasing intervals; these are still waiting.', 'err')}${table({ id: 'codl-aderr', nopager: true, cols: [{ h: 'To', r: m => `${m.to}<span class="s">${m.when}</span>` }, { h: 'Message', k: 'subject' }, { h: 'Error', k: 'err' }], rows: f })}<span class="btn ter sm" style="align-self:flex-start" data-modal="adretry" data-name="${c.name}">Retry ${f.length} messages</span>`
    : '<div class="empty">No failed messages.</div>'; } });
modal('adretry', { title: c => `Retry ${failedMail(MC.state).length} failed messages?`, kicker: c => c.name, confirm: 'Retry now', act: 'adretry',
  body: (c, s) => `<p>MCMS sends the ${failedMail(s).length} waiting emails again. If the mail server still refuses them they stay in the failed list and you are told again.</p>` });
act('adretry', (c, s) => { const f = failedMail(s); f.forEach(m => { m.status = 'Sent'; m.when = `${s.today}, ${NOW}`; delete m.err; });
  const a = s.adapters.find(x => x.name === c.name); a.status = 'Working'; a.err = 'None'; a.last = `${s.today.replace(/ 2026$/, '')}, ${NOW}`; s.notifs.codl = s.notifs.codl.filter(n => !/adapter failing/i.test(n.t));
  aud(s, `Retried ${f.length} failed emails; Email adapter working`, 'Adapter'); MC.toast('Retry succeeded', `${f.length} messages sent. The adapter is working.`, 'ok'); });

screen('codl/mailbox', { title: 'Test mailbox', role: 'codl', nav: 'mailbox', render: (p, s) => `${head({ title: 'Test mailbox', sub: 'Every email MCMS sent or tried to send, in the recipient language. Also the notification log.' })}
  ${kpis([['Sent', s.mailbox.filter(m => m.status === 'Sent').length], ['Failed', failedMail(s).length, failedMail(s).length ? 'Retry from Adapter status' : ''], ['Bahasa Melayu', s.mailbox.filter(m => m.lang === 'BM').length], ['English', s.mailbox.filter(m => m.lang === 'EN').length]])}
  ${table({ id: 'codl-mailbox', size: 10, search: 'Search recipient or subject', cols: [{ h: 'To', r: m => `${m.to}<span class="s">${m.addr}</span>` }, { h: 'Language', k: 'lang' }, { h: 'Subject', r: m => `<a data-drawer="mail" data-id="${m.id}">${m.subject}</a>` }, { h: 'Status', r: m => tag(m.status) }, { h: 'When', k: 'when' }], rows: s.mailbox })}` });
drawer('mail', { title: c => (MC.state.mailbox.find(m => m.id === c.id) || {}).subject, body: (c, s) => { const m = s.mailbox.find(x => x.id === c.id);
  return `${dl([['To', `${m.to} &lt;${m.addr}&gt;`], ['Language', m.lang === 'BM' ? 'Bahasa Melayu' : 'English'], ['Status', tag(m.status)], ['When', m.when]])}${m.err ? note(`Not delivered: ${m.err}. Retry from Adapter status.`, 'err') : ''}<div class="panel"><div class="bd" style="white-space:pre-wrap">${m.body}</div></div>`; } });

/* ================= audit, reports, retention ================= */
screen('codl/audit', { title: 'Audit trail', role: 'codl', nav: 'audit', render: (p, s) => `${head({ title: 'Audit trail', sub: `${s.audit.length} entries. Append-only: nobody can edit or delete an entry, including CODL.`, actions: '<span class="btn ter" data-act="auditcheck">Check integrity</span><span class="btn ter" data-go="#/doc/audit">Export CSV</span>' })}${MC.auditTable('codl-audit')}` });
act('auditcheck', (c, s) => { MC.toast('Hash chain verified', `${s.audit.length} entries checked, none altered. Latest hash 9f3c…a71e.`, 'ok'); return false; });
MC.reportsScreen('codl', 'reports', 'University');
MC.reportScreen('codl', 'reports', 'University');

screen('codl/retention', { title: 'Retention', role: 'codl', nav: 'retention', render: (p, s) => `${head({ title: 'Retention', sub: 'How long each kind of record is kept before it may be disposed of. Periods are not decided yet.' })}
  ${table({ id: 'codl-retention', nopager: true, cols: [{ h: 'Record category', r: r => `${r[0]}<span class="s">${r[1]}</span>` }, { h: 'Retention period', r: () => tag('Not decided yet', 'warn') }, { h: 'Set in', r: () => '<a data-go="#/codl/settings">Settings, retention.*</a>' }], rows: s.retentionCats })}
  ${panel('Records holds', `<div class="small muted" style="margin-bottom:12px">A hold stops disposal of a record while it is needed, for example in an investigation.</div><span class="btn dis">Place a hold: Release 2</span>`)}
  ${table({ id: 'codl-disposal', title: 'Disposal requests', nopager: true, cols: [{ h: 'Request', r: d => `${d.id}<span class="s">${d.cat}</span>` }, { h: 'Records', k: 'what' }, { h: 'Requested', k: 'since' }, { h: 'Approver', k: 'approver' }, { h: 'Status', r: d => tag(d.status) }], rows: s.disposals, empty: 'No disposal requests.' })}
  ${note('Disposal is approved by the Registrar (Pejabat Pendaftar), only after the retention period and only if no hold applies. CODL cannot approve disposal.')}` });

/* ================= flows ================= */
flow({ id: 'FL-07', title: 'Import UCreds results with a bad row', actor: 'CODL, lecturer, deputy dean', summary: 'Import results-CND-2026-2.csv with 2 problems; the lecturer enters the missing marks and submits for verification',
  steps: [{ go: '#/codl/ucreds', say: 'Farid chooses results-CND-2026-2.csv and clicks Check file. The report shows 22 rows ready, 2 rejected (empty marks) and a notice that this file was already imported. Click Stage 22 rows and watch the job run. Dr. Aina is told which marks are missing. Nothing becomes an achievement yet.' },
    { go: '#/lec/cohort/CND-2026-2', say: 'Now as Dr. Aina: she enters Daniel Ong\'s project mark and Wong Kar Seng\'s module 2 mark, and submits the results for verification.' },
    { go: '#/dd/results', say: 'Now as Hakim, the deputy dean: the cohort waits for verification. Only the deputy dean can verify; CODL has no verify button.' }] });
flow({ id: 'FL-17', title: 'Assign a scoped role and change a setting', actor: 'CODL administrator', summary: 'Give a role with scope, reason and date; change a setting with a reason; see its history and the audit trail',
  steps: [{ go: '#/codl/users', say: 'Farid searches the user list and opens a person.' },
    { go: '#/codl/user/S01842', say: 'Click Assign role. Choose Reviewer, scope Specific micro-credentials (LE, which Dr. Aina does not own), a reason and an effective date. The new row appears in the table.' },
    { go: '#/codl/settings', say: 'Find alerts.qv_expiry_days and click Change. Enter a new value, an effective date and a reason. Then click History on the same row to see old and new values.' },
    { go: '#/codl/audit', say: 'Both changes are in the audit trail with who, when and why. Click Check integrity to verify the hash chain.' }] });
})();
