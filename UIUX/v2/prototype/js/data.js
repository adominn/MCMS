/* Shared sample data. One world, records at different stages, so every flow can be walked.
   Today in the prototype is 26 Oct 2026. Screen files may add their own keys to MC.seed but must not rename these. */
(function () {
const MC = window.MC;

/* ---------- people and roles ---------- */
MC.people = {
  amir: { name: 'Amir Hakimi', initials: 'AH', post: 'Participant · external learner', email: 'amir.hakimi@learner.utem.edu.my', id: 'X2026-0193' },
  aina: { name: 'Dr. Aina Zulkifli', initials: 'AZ', post: 'Lecturer, FTMK', email: 'aina.z@utem.edu.my', id: 'S01842' },
  rosli: { name: 'Dr. Rosli Hamzah', initials: 'RH', post: 'Lecturer, FTMK', email: 'rosli.h@utem.edu.my', id: 'S01177' },
  hakim: { name: 'Hakim Rahman', initials: 'HR', post: 'Deputy dean (Academic), FTMK', email: 'hakim.rahman@utem.edu.my', id: 'S00921' },
  farah: { name: 'Dr. Farah Nadia Ismail', initials: 'FN', post: 'Deputy dean (Academic), FKE', email: 'farahnadia@utem.edu.my', id: 'S00954' },
  farid: { name: 'Farid Hashim', initials: 'FH', post: 'System administrator, CODL', email: 'farid.h@utem.edu.my', id: 'S02210' },
  siti: { name: 'Ir. Siti Aminah Yusof', initials: 'SA', post: 'Industry expert, CloudWorks Sdn Bhd', email: 'siti.aminah@cloudworks.my', id: 'X2026-0050' },
  tan: { name: 'Tan Mei Ling', initials: 'TM', post: 'HR manager, Melaka Precision Sdn Bhd', email: '', id: '' },
};

MC.role('st', { name: 'Student', user: MC.people.amir, home: '#/st/home', search: '',
  nav: [['Learning'], ['home', 'home', 'My learning'], ['catalogue', 'list', 'Find a micro-credential'], ['enrolments', 'cart', 'Registrations and payments'],
    ['Achievements'], ['record', 'check', 'Achievement record'], ['credentials', 'badge', 'Credentials'],
    ['Credit'], ['ledger', 'bank', 'Credit ledger and ABC'], ['transfer', 'swap', 'Credit transfer'], ['plan', 'grad', 'Stackable plan']],
  counts: { ledger: s => s.acrs.filter(a => a.owner === 'amir' && a.status === 'Verified' && !a.consent).length } });
MC.role('lec', { name: 'Lecturer', user: MC.people.aina, home: '#/lec/home', search: 'Search micro-credentials, cohorts, learners',
  nav: [['Teaching'], ['home', 'home', 'My micro-credentials'], ['proposals', 'doc', 'Proposals and versions'], ['reviews', 'clip', 'Reviews for colleagues'], ['cohorts', 'users', 'Cohorts and results'],
    ['Quality'], ['cqi', 'chart', 'Quality review (CQI)'], ['qv', 'shield', 'QV evidence'], ['Insight'], ['reports', 'chart', 'Reports']],
  counts: { proposals: s => s.mcs.filter(m => m.owner === 'aina' && /Draft|Returned/.test(m.status)).length, reviews: s => s.reviews.filter(r => r.reviewer === 'aina' && r.status !== 'Submitted').length,
    cohorts: s => s.cohorts.filter(c => c.owner === 'aina' && c.results === 'Draft').length, cqi: s => s.cqi.filter(c => c.owner === 'aina' && c.status === 'Draft').length } });
MC.role('dd', { name: 'Deputy dean', user: MC.people.hakim, home: '#/dd/queue', search: 'Search micro-credentials, learners, credentials',
  nav: [['Work'], ['queue', 'inbox', 'Task queue'], ['proposals', 'doc', 'Proposals and approval'], ['offerings', 'cal', 'Offerings and readiness'], ['results', 'check', 'Results and completion'],
    ['creds', 'badge', 'Credentials'], ['quality', 'shield', 'Quality, QV and review'], ['acr', 'bank', 'ACR and ABC deposits'], ['transfer', 'swap', 'Credit transfer'], ['stack', 'layers', 'Stackable pathways'],
    ['Insight'], ['reports', 'chart', 'Reports'], ['trace', 'audit', 'Traceability']],
  counts: { queue: s => s.tasks.filter(t => !t.done).length, proposals: s => s.tasks.filter(t => !t.done && t.area === 'proposals').length, offerings: s => s.tasks.filter(t => !t.done && t.area === 'offerings').length,
    results: s => s.tasks.filter(t => !t.done && t.area === 'results').length, quality: s => s.tasks.filter(t => !t.done && t.area === 'quality').length, acr: s => s.tasks.filter(t => !t.done && t.area === 'acr').length,
    transfer: s => s.tasks.filter(t => !t.done && t.area === 'transfer').length } });
MC.role('codl', { name: 'CODL administrator', user: MC.people.farid, home: '#/codl/users', search: 'Search users, settings, files',
  nav: [['Administration'], ['users', 'users', 'Users and roles'], ['refdata', 'db', 'Reference data'], ['settings', 'gear', 'Settings'], ['templates', 'doc', 'Templates'],
    ['Data exchange'], ['ucreds', 'sync', 'UCreds exchange'], ['adapters', 'plug', 'Adapter status'], ['mailbox', 'mail', 'Test mailbox'],
    ['Records'], ['audit', 'audit', 'Audit trail'], ['reports', 'chart', 'Reports'], ['retention', 'clip', 'Retention']],
  counts: { adapters: s => s.adapters.filter(a => a.status === 'Failing').length } });
MC.role('ext', { name: 'Industry expert', user: MC.people.siti, home: '#/ext/home', search: '',
  nav: [['Invitations'], ['home', 'clip', 'My review invitations']] });

/* ---------- seed ---------- */
const NAMES = ['Muhammad Danish Roslan', 'Kavitha Raman', 'Nur Iman Azlan', 'Tan Wei Ming', 'Chong Mei Ling', 'Siti Khadijah Omar', 'Haziq Firdaus', 'Arjun Pillai',
  'Nurul Ain Kamarudin', 'Daniel Ong', 'Wong Kar Seng', 'Fatimah Zahra Idris', 'Rajesh Kumar', 'Ahmad Faiz Rahim', 'Grace Lim Hui Min', 'Aiman Hakim Zulkifli',
  'Lee Jia Hui', 'Priya Devi', 'Hafiz Rosli', 'Nadia Sofea', 'Jason Teoh', 'Syafiqah Ali', 'Mohd Irfan Musa', 'Ooi Kah Wai', 'Balqis Hani', 'Ravi Shankar', 'Liyana Aziz', 'Ng Pei Shan', 'Zulaikha Noor'];
// DS cohort 2026/2: 28 learners, weights 40/30/30, pass mark 50. Exactly one fail (Arjun Pillai).
const DS262 = NAMES.slice(0, 28).map((n, i) => {
  const base = n === 'Arjun Pillai' ? [41, 38, 35] : [90 - i, 88 - i, 92 - i].map((v, k) => Math.max(51, v - (i % 3) * 2 + k));
  const fin = Math.round(base[0] * .4 + base[1] * .3 + base[2] * .3);
  return { n, id: `B0324${10077 + i * 3}`, m: base, fin, res: fin >= 50 ? 'Pass' : 'Fail', src: i === 5 ? 'Edited by lecturer 25 Oct' : 'UCreds import 24 Oct', flag: '' };
});

MC.seed = {
  lang: 'EN', as: 'dd', today: '26 Oct 2026',
  ptj: [['FTMK', 'Faculty of Information and Communication Technology'], ['FKE', 'Faculty of Electrical Technology and Engineering'], ['FPTT', 'Faculty of Technology Management and Technopreneurship'], ['FTKIP', 'Faculty of Industrial and Manufacturing Technology and Engineering'], ['CODL', 'Centre for Open and Distance Learning']],
  programmes: [{ code: 'BITZ', name: 'Bachelor of Computer Science (Software Development) with Honours', ptj: 'FTMK', credits: 120, mqf: 6, mode: 'Conventional', accredited: 'to 2029' },
    { code: 'BITD', name: 'Bachelor of Computer Science (Database Management) with Honours', ptj: 'FTMK', credits: 120, mqf: 6, mode: 'Conventional', accredited: 'to 2029' }],
  courses: [{ code: 'BITS 1123', name: 'Data Structures', credits: 3, slt: 120, clos: 4 }, { code: 'BITM 2313', name: 'Database Systems', credits: 3, slt: 120, clos: 3 },
    { code: 'BITS 2343', name: 'Software Testing', credits: 3, slt: 120, clos: 4 }, { code: 'BITI 1213', name: 'Linear Algebra', credits: 3, slt: 120, clos: 3 }, { code: 'BITU 3973', name: 'Cloud Computing (elective)', credits: 3, slt: 120, clos: 4 }],

  /* micro-credentials: status uses the PRD approval vocabulary */
  mcs: [
    { id: 'DS', cat: 'UMC', code: 'BITS 1123', name: 'Data Structures', ptj: 'FTMK', owner: 'aina', credits: 3, slt: 120, version: 2, status: 'Approved', approvedBy: 'Senate 5/2026', modules: [['Linear structures', 40], ['Trees and graphs', 40], ['Algorithm analysis', 40]], weights: [40, 30, 30] },
    { id: 'DB', cat: 'UMC', code: 'BITM 2313', name: 'Database Systems', ptj: 'FTMK', owner: 'aina', credits: 3, slt: 120, version: 1, status: 'In review', submitted: '20 Oct 2026', reviews: '2 of 2 done', openComments: 1, modules: [['Relational model and SQL', 48], ['Design and normalisation', 36], ['Transactions and indexing', 36]], weights: [40, 30, 30] },
    { id: 'ST', cat: 'UMC', code: 'BITS 2343', name: 'Software Testing', ptj: 'FTMK', owner: 'aina', credits: 3, slt: 120, version: 1, status: 'Draft', modules: [['Testing fundamentals', 40], ['Test design techniques', 38], ['Automation and tools', 32]], weights: [40, 30, 30] },
    { id: 'CND', cat: 'SAMC', code: '', name: 'Cloud Native Development', ptj: 'FTMK', owner: 'aina', credits: 2, slt: 80, mqf: 4, version: 3, status: 'Approved', approvedBy: 'Senate 9/2023', qv: { status: 'Quality-Verified', from: '6 Dec 2023', to: '6 Dec 2026', daysLeft: 41 }, modules: [['Containers and images', 24], ['Pipelines and release', 24], ['Project: running services', 32]], weights: [30, 30, 40] },
    { id: 'ADA', cat: 'SAMC', code: '', name: 'Applied Data Analytics with Python', ptj: 'FTMK', owner: 'aina', credits: 2, slt: 80, version: 1, status: 'Pending approval', endorsed: '2 Oct 2026', senate: 'Senate 7/2026, 14 Oct', qv: { status: 'Not applied' }, modules: [['Python for data', 24], ['Analysis and visualisation', 28], ['Capstone', 28]], weights: [30, 30, 40] },
    { id: 'CSO', cat: 'SAMC', code: '', name: 'Cyber Security Operations Fundamentals', ptj: 'FTMK', owner: 'rosli', credits: 2, slt: 80, version: 2, status: 'In review', submitted: '23 Oct 2026', qv: { status: 'Preparing' }, modules: [['Security operations basics', 30], ['Monitoring and detection', 30], ['Incident response', 20]], weights: [30, 30, 40] },
    { id: 'LA', cat: 'UMC', code: 'BITI 1213', name: 'Linear Algebra', ptj: 'FTMK', owner: 'rosli', credits: 3, slt: 120, version: 1, status: 'Approved', approvedBy: 'Senate 7/2026', modules: [['Vectors and matrices', 40], ['Linear transformations', 40], ['Eigenvalues', 40]], weights: [40, 30, 30] },
    { id: 'LE', cat: 'MCE', code: '', name: 'Leadership Essentials', ptj: 'FTMK', owner: 'rosli', credits: 0, slt: 16, version: 1, status: 'Approved', approvedBy: 'Senate 7/2026', modules: [['Leading a team', 8], ['Coaching conversations', 8]], weights: [50, 50] },
    { id: 'IIOT', cat: 'SAMC', code: '', name: 'Industrial IoT Fundamentals', ptj: 'FKE', owner: 'zul', credits: 2, slt: 80, mqf: 4, version: 1, status: 'Approved', approvedBy: 'Senate 3/2025', qv: { status: 'Quality-Verified', from: '2 Jun 2025', to: '2 Jun 2028' }, modules: [['Sensors and gateways', 26], ['Data on the factory floor', 26], ['Dashboards project', 28]], weights: [30, 30, 40] },
    { id: 'LSS', cat: 'MCE', code: '', name: 'Lean Six Sigma Yellow Belt', ptj: 'FTKIP', owner: 'other', credits: 0, slt: 16, version: 1, status: 'Approved', approvedBy: 'Senate 4/2026', modules: [['Lean basics', 8], ['DMAIC project', 8]], weights: [50, 50] },
  ],

  cohorts: [
    { id: 'DS-2025-2', mc: 'DS', name: 'Cohort 2025/2', start: '1 Sep 2025', end: '20 Feb 2026', enrolled: 28, achieved: 25, status: 'Ended', results: 'Verified', owner: 'aina' },
    { id: 'DS-2026-1', mc: 'DS', name: 'Cohort 2026/1', start: '2 Mar 2026', end: '30 Jun 2026', enrolled: 29, achieved: 26, status: 'Ended', results: 'Verified', owner: 'aina' },
    { id: 'DS-2026-2', mc: 'DS', name: 'Cohort 2026/2', start: '6 Jul 2026', end: '24 Oct 2026', enrolled: 28, achieved: 27, status: 'Ended', results: 'Submitted', owner: 'aina', learners: DS262 },
    { id: 'DS-2027-1', mc: 'DS', name: 'Cohort 2027/1', start: '4 Jan 2027', end: '28 Mar 2027', places: 40, taken: 0, fee: 450, status: 'Not ready', readiness: 11, owner: 'aina' },
    { id: 'CND-2026-1', mc: 'CND', name: 'Cohort 2026/1', start: '2 Mar 2026', end: '30 Jun 2026', enrolled: 22, achieved: 19, status: 'Ended', results: 'Verified', owner: 'aina' },
    { id: 'CND-2026-2', mc: 'CND', name: 'Cohort 2026/2', start: '6 Jul 2026', end: '24 Oct 2026', enrolled: 24, achieved: 0, status: 'Ended', results: 'Draft', missing: 2, owner: 'aina' },
    { id: 'CND-2027-1', mc: 'CND', name: 'Cohort 2027/1', start: '11 Jan 2027', end: '4 Apr 2027', places: 40, taken: 9, fee: 600, status: 'Open', owner: 'aina' },
    { id: 'LA-2027-1', mc: 'LA', name: 'Cohort 2027/1', start: '4 Jan 2027', end: '28 Mar 2027', places: 40, taken: 12, fee: 450, status: 'Open', owner: 'rosli' },
    { id: 'LE-2026-10', mc: 'LE', name: 'Cohort Oct 2026', start: '20 Oct 2026', end: '24 Oct 2026', enrolled: 40, achieved: 38, status: 'Ended', results: 'Verified', completion: 'To confirm', owner: 'rosli' },
    { id: 'LE-2026-12', mc: 'LE', name: 'Cohort Dec 2026', start: '2 Dec 2026', end: '3 Dec 2026', places: 30, taken: 30, fee: 350, status: 'Full', owner: 'rosli' },
    { id: 'IIOT-2026-2', mc: 'IIOT', name: 'Cohort 2026/2', start: '1 Sep 2026', end: '20 Dec 2026', enrolled: 35, status: 'Running', owner: 'zul' },
    { id: 'IIOT-2027-1', mc: 'IIOT', name: 'Cohort 2027/1', start: '18 Jan 2027', end: '10 Apr 2027', places: 35, taken: 6, fee: 520, status: 'Open', owner: 'zul' },
    { id: 'LSS-2026-12', mc: 'LSS', name: 'Cohort Dec 2026', start: '9 Dec 2026', end: '10 Dec 2026', places: 30, taken: 14, fee: 480, status: 'Open', owner: 'other' },
  ],

  /* Amir's learning */
  enrolments: [
    { id: 'E-2025-0311', who: 'amir', cohort: 'DS-2025-2', status: 'Completed', paid: 'RM 450, 18 Aug 2025', modules: [['Achieved', '12 Oct 2025'], ['Achieved', '30 Nov 2025'], ['Achieved', '14 Feb 2026']] },
    { id: 'E-2026-0207', who: 'amir', cohort: 'CND-2026-1', status: 'Completed', paid: 'RM 600, 20 Feb 2026', modules: [['Achieved', '10 Apr 2026'], ['Achieved', '22 May 2026'], ['Achieved', '28 Jun 2026']] },
    { id: 'E-2026-0644', who: 'amir', cohort: 'LE-2026-10', status: 'Completed, waiting for verification', paid: 'RM 350, 2 Oct 2026', modules: [['Achieved', '22 Oct 2026'], ['Achieved', '24 Oct 2026']] },
    { id: 'E-2026-0590', who: 'amir', cohort: 'IIOT-2026-2', status: 'Enrolled', paid: 'RM 520, 25 Aug 2026', progress: 62, modules: [['Achieved', '6 Oct 2026'], ['In progress', ''], ['Not started', '']] },
  ],
  credentials: [
    { id: 'MCMS-C-2026-000098', code: '3HXP-92LD', who: 'Amir Hakimi', owner: 'amir', mc: 'DS', cohort: 'DS-2025-2', issued: '3 Mar 2026', status: 'Issued', revision: 1, result: 'Pass, 84' },
    { id: 'MCMS-C-2026-000209', code: '8TWN-5C2E', who: 'Amir Hakimi', owner: 'amir', mc: 'CND', cohort: 'CND-2026-1', issued: '12 Jul 2026', status: 'Issued', revision: 1, result: 'Pass, 78', qvClaim: 'Quality-Verified, MQF level 4, 2 credits' },
    { id: 'MCMS-C-2026-000418', code: '7Q2M-K4PA', who: 'Lee Jia Hui', owner: 'leejh', mc: 'DS', cohort: 'DS-2026-1', issued: '14 Jul 2026', status: 'Issued', revision: 1, result: 'Pass, 68', request: 'Name should read "Lee Jiahui" (student record updated 23 Oct)' },
    { id: 'MCMS-C-2026-000377', code: 'R7KD-1MQA', who: 'Rizal Bakar', owner: 'other', mc: 'LA', cohort: 'LA-2026-1', issued: '9 Jul 2026', status: 'Revoked', revoked: '2 Sep 2026', revision: 1, result: 'Pass, 61', reason: 'Result withdrawn after an academic integrity finding' },
  ],
  acrs: [
    { id: 'MCMS-A-2026-000102', owner: 'amir', mc: 'DS', credits: 3, status: 'Deposited', consent: 'Given 5 Mar 2026', abcRef: 'ABC-MY-2026-0004418', deposited: '20 Oct 2026', expires: '20 Oct 2036', credit: 'Available' },
    { id: 'MCMS-A-2026-000214', owner: 'amir', mc: 'CND', credits: 2, status: 'Verified', consent: '', abcRef: '', deposited: '', expires: '', credit: '', confirmed: '24 Oct 2026' },
  ],
  acrBatches: [
    { id: 'ACR-DS-2026-1', mc: 'DS', cohort: 'DS-2026-1', achieved: 26, consented: 24, status: 'ACR prepared' },
    { id: 'ACR-CND-2026-1', mc: 'CND', cohort: 'CND-2026-1', achieved: 19, consented: 18, status: 'ACR prepared' }, // Amir has not consented yet
    { id: 'ABC-FTMK-2026-03', mc: '', cohort: '', count: 31, status: 'Sent, waiting for ABC reference', sent: '9 Oct 2026' },
  ],
  transfers: [
    { id: 'TR-2026-0012', who: 'amir', name: 'Amir Hakimi', programme: 'BITZ', applied: '21 Oct 2026', status: 'Under evaluation',
      lines: [{ acr: 'MCMS-A-2026-000102', mc: 'DS', course: 'BITS 1123', credits: 3, match: ['Full', 'Full', 'Full', 'Full'], decision: '' }] },
    { id: 'TR-2026-0009', who: 'wong', name: 'Wong Kar Seng', programme: 'BITZ', applied: '17 Oct 2026', status: 'Under evaluation',
      lines: [{ acr: 'MCMS-A-2026-000188', mc: 'CND', course: 'BITU 3973', credits: 2, match: ['Full', 'Partial', 'Full', 'None'], decision: '' }] },
  ],
  admissions: [{ who: 'amir', programme: 'BITZ', intake: 'Feb 2027', recorded: '1 Oct 2026 by Pejabat Pendaftar' }],
  plans: [
    { id: 'SP-2026-003', who: 'amir', programme: 'BITZ', proposed: 5, total: 120, residency: 2, status: 'Suitable' },
    { id: 'SP-2026-004', who: 'Wong Kar Seng', programme: 'BITZ', proposed: 90, total: 120, residency: 1, status: 'Not yet suitable' },
  ],
  pathways: [{ id: 'PW-BITZ-1', programme: 'BITZ', status: 'Draft', maps: [['DS', 'BITS 1123', 3], ['DB', 'BITM 2313', 3], ['LA', 'BITI 1213', 3], ['CND', 'BITU 3973', 2]] }],

  reviews: [
    { id: 'RV-031', mc: 'CSO', reviewer: 'aina', due: '30 Oct 2026', status: 'Invited', coi: '' },
    { id: 'RV-029', mc: 'DB', reviewer: 'rosli', due: '15 Oct 2026', status: 'Submitted', rec: 'Support', comments: 2 },
    { id: 'RV-030', mc: 'DB', reviewer: 'siti', due: '15 Oct 2026', status: 'Submitted', rec: 'Amend', comments: 1 },
    { id: 'RV-033', mc: 'ADA', reviewer: 'siti', due: '6 Nov 2026', status: 'Invited', coi: '' },
  ],
  cqi: [
    { id: 'CQI-DS-2026-1', mc: 'DS', cohort: 'DS-2026-1', owner: 'aina', status: 'Draft', due: '9 Nov 2026' },
    { id: 'CQI-CND-2026-1', mc: 'CND', cohort: 'CND-2026-1', owner: 'aina', status: 'Submitted', submitted: '22 Oct 2026' },
  ],

  /* deputy dean's queue: counts on the nav come from here */
  tasks: [
    { id: 'T1', area: 'proposals', what: 'Endorse proposal', ref: 'DB', from: 'Dr. Aina Zulkifli', check: '5 of 6 checks pass, 1 comment open', waiting: '6 days', go: '#/dd/proposal/DB' },
    { id: 'T2', area: 'proposals', what: 'Record Senate decision', ref: 'ADA', from: 'Senate 7/2026', check: 'Senate met 14 Oct; decision not recorded', waiting: '3 days', go: '#/dd/senate/ADA' },
    { id: 'T3', area: 'offerings', what: 'Confirm readiness', ref: 'DS-2027-1', from: 'Dr. Aina Zulkifli', check: '11 of 12 readiness items done', waiting: '2 days', go: '#/dd/readiness/DS-2027-1' },
    { id: 'T4', area: 'results', what: 'Verify results', ref: 'DS-2026-2', from: 'Dr. Aina Zulkifli', check: '28 results, 27 pass · module badges follow', waiting: '1 day', go: '#/dd/verify/DS-2026-2' },
    { id: 'T5', area: 'results', what: 'Confirm completion', ref: 'LE-2026-10', from: 'Dr. Rosli Hamzah', check: 'Results verified 25 Oct · 38 complete · MCE: certificate, no ACR', waiting: 'Today', go: '#/dd/completion/LE-2026-10' },
    { id: 'T6', area: 'quality', what: 'Confirm quality review', ref: 'CQI-CND-2026-1', from: 'Dr. Aina Zulkifli', check: '3 actions proposed · QV expires 6 Dec 2026', waiting: '4 days', go: '#/dd/cqi/CQI-CND-2026-1' },
    { id: 'T7', area: 'acr', what: 'Confirm ACR', ref: 'ACR-DS-2026-1', from: 'System', check: '26 achieved, 24 consented to ABC', waiting: '5 days', go: '#/dd/acr/ACR-DS-2026-1' },
    { id: 'T8', area: 'acr', what: 'Confirm ACR', ref: 'ACR-CND-2026-1', from: 'System', check: '19 achieved, 18 consented to ABC', waiting: '5 days', go: '#/dd/acr/ACR-CND-2026-1' },
    { id: 'T9', area: 'acr', what: 'Record ABC deposit', ref: 'ABC-FTMK-2026-03', from: 'Hakim Rahman', check: 'Waiting for the ABC reference number', waiting: '17 days', go: '#/dd/deposit/ABC-FTMK-2026-03' },
    { id: 'T10', area: 'transfer', what: 'Decide credit transfer', ref: 'TR-2026-0012', from: 'Amir Hakimi', check: '1 line, 3 credits · single redemption checked', waiting: '5 days', go: '#/dd/transfer/TR-2026-0012' },
    { id: 'T12', area: 'transfer', what: 'Decide credit transfer', ref: 'TR-2026-0009', from: 'Wong Kar Seng', check: '1 line, 2 credits · one course outcome has no match', waiting: '9 days', go: '#/dd/transfer/TR-2026-0009' },
    { id: 'T11', area: 'creds', what: 'Correct credential', ref: 'MCMS-C-2026-000418', from: 'Lee Jia Hui', check: 'Name spelling, student record already updated', waiting: '3 days', go: '#/dd/cred/MCMS-C-2026-000418' },
  ],

  notifs: {
    dd: [{ t: 'Results ready to verify: Data Structures 2026/2', s: 'Dr. Aina Zulkifli submitted 28 results', when: '25 Oct', go: '#/dd/verify/DS-2026-2', unread: true },
      { t: 'Credit transfer TR-2026-0012 submitted', s: 'Amir Hakimi, 2 lines', when: '21 Oct', go: '#/dd/transfer/TR-2026-0012', unread: true },
      { t: 'QV for Cloud Native Development expires in 41 days', s: 'Renewal package due 6 Nov', when: '26 Oct', go: '#/dd/quality', unread: true },
      { t: 'Decision overdue: ABC deposit FTMK-2026-03', s: 'Waiting 17 days (reminder after 14)', when: '23 Oct', go: '#/dd/deposit/ABC-FTMK-2026-03', unread: false },
      { t: 'Proposal submitted: Database Systems', s: 'Reviews 2 of 2 done', when: '20 Oct', go: '#/dd/proposal/DB', unread: false }],
    lec: [{ t: 'Review requested: Cyber Security Operations Fundamentals', s: 'Dr. Rosli Hamzah, due 30 Oct', when: '23 Oct', go: '#/lec/review/RV-031', unread: true },
      { t: '2 marks missing in Cloud Native Development 2026/2', s: 'UCreds import 25 Oct', when: '25 Oct', go: '#/lec/cohort/CND-2026-2', unread: true },
      { t: 'Quality review due 9 Nov', s: 'Data Structures 2026/1', when: '19 Oct', go: '#/lec/cqi/CQI-DS-2026-1', unread: false },
      { t: 'Readiness item open: facilitator qualification', s: 'Data Structures 2027/1', when: '24 Oct', go: '#/lec/readiness/DS-2027-1', unread: true }],
    st: [{ t: 'Consent needed: deposit your Cloud Native Development credit in ABC', s: 'ACR confirmed 24 Oct. Give, decline or postpone.', when: '24 Oct', go: '#/st/acr/MCMS-A-2026-000214', unread: true },
      { t: 'Your credit transfer is being decided', s: 'TR-2026-0012, FTMK deputy dean', when: '21 Oct', go: '#/st/transfer/TR-2026-0012', unread: true },
      { t: 'Credit deposited in ABC', s: 'Data Structures, 3 credits, valid to 20 Oct 2036', when: '20 Oct', go: '#/st/ledger', unread: false },
      { t: 'Module 2 quiz due 2 Nov', s: 'Industrial IoT Fundamentals', when: '24 Oct', go: '#/st/home', unread: true }],
    codl: [{ t: 'Email adapter failing', s: '3 messages not sent since 09:40', when: '26 Oct', go: '#/codl/adapters', unread: true },
      { t: 'Import finished with 2 problems', s: 'results-CND-2026-2.csv', when: '25 Oct', go: '#/codl/ucreds', unread: false }],
    ext: [{ t: 'Invitation to review: Applied Data Analytics with Python', s: 'FTMK, due 6 Nov', when: '22 Oct', go: '#/ext/home', unread: true }],
  },

  settings: [
    ['credit.slt_hours_per_credit', 'Learning hours per credit', '40 hours', 'MQF, D-27'], ['slt.min_hours_samc', 'Minimum learning time for a SAMC', '40 hours', 'G 6.3.2, BR-01'],
    ['slt.min_hours_umc_whole', 'Minimum learning time for a whole UMC', '40 hours', 'G 6.3.1, BR-01'], ['slt.min_hours_module_credit_model', 'Minimum learning time per module (credit model)', '40 hours', 'G 6.3.1.1'],
    ['qv.max_validity_years', 'Maximum QV validity', '3 years', 'G 11.2.2, BR-02'], ['qv.min_completed_cohorts', 'Completed cohorts before QV submission', '1', 'G 11.2.1, BR-03'],
    ['credit.abc_validity_years', 'Credit validity in ABC', '10 years', 'G 12.8, BR-04'], ['stackable.min_home_credit_percent', 'Minimum credit earned at UTeM (stackable)', '30%', 'G 14.5.1, BR-05'],
    ['stackable.residency_semesters', 'Residency for conventional programmes', '1 semester', 'G 14.5.3, BR-06'], ['alerts.qv_expiry_days', 'QV expiry alerts', '180, 90, 30 days', 'D-16'],
    ['alerts.credit_expiry_days', 'Credit expiry alerts to participant', '365, 90 days', 'D-16'], ['alerts.decision_overdue_days', 'Remind a pending decision after', '14 days', ''],
    ['umc.completion_window_months', 'Time to complete all modules of a modular UMC', 'TBD (OI-13)', 'G 7.4'], ['auth.idle_timeout_minutes', 'Idle session timeout', '30 minutes', ''],
    ['verification.public_fields', 'Fields on the public verification page', 'Name, credential, issuer, date, status', 'App E6'], ['retention.*', 'Retention per record category', 'TBD (OI-07)', 'G 18.12'],
  ],
  settingHistory: [{ key: 'alerts.qv_expiry_days', old: '90, 30 days', new: '180, 90, 30 days', by: 'Farid Hashim', when: '2 Sep 2026', reason: 'Renewal packages need more lead time (CODL memo 14/2026)' }],
  adapters: [
    { name: 'UCreds learning platform', mode: 'Manual (CSV)', last: '25 Oct, 08:00', status: 'Working', err: 'None' },
    { name: 'UTeM single sign-on', mode: 'Stub (test users)', last: '26 Oct, 10:12', status: 'Working', err: 'None' },
    { name: 'Email notifications', mode: 'Live', last: '26 Oct, 10:05', status: 'Failing', err: '3 messages not sent since 09:40' },
    { name: 'Payment', mode: 'Stub (dummy service)', last: '25 Oct, 15:31', status: 'Working', err: 'None' },
    { name: 'ABC', mode: 'Manual (deputy dean records)', last: '20 Oct', status: 'No connector', err: '—' },
    { name: 'QVC / MQA', mode: 'Manual (package download)', last: '—', status: 'No connector', err: '—' },
    { name: 'Credential signing', mode: 'Test key pair', last: '14 Oct, 10:42', status: 'Working', err: 'None' },
  ],
  audit: [
    ['26 Oct 2026, 10:05', 'System', 'Email to Hakim Rahman failed (SMTP 421)', 'Adapter'],
    ['25 Oct 2026, 16:20', 'Dr. Aina Zulkifli', 'Submitted results, Data Structures 2026/2 (28)', 'DS-2026-2'],
    ['25 Oct 2026, 08:00', 'Farid Hashim', 'Imported results-CND-2026-2.csv: 22 staged, 2 rejected', 'CND-2026-2'],
    ['23 Oct 2026, 11:02', 'Lee Jia Hui', 'Requested credential correction: name spelling', 'MCMS-C-2026-000418'],
    ['21 Oct 2026, 09:14', 'Amir Hakimi', 'Applied for credit transfer TR-2026-0012', 'TR-2026-0012'],
    ['20 Oct 2026, 15:40', 'Hakim Rahman', 'Recorded ABC deposit FTMK-2026-02 (28 ACR), ref ABC-MY-2026-0004391..4419', 'ABC-FTMK-2026-02'],
    ['20 Oct 2026, 10:31', 'Dr. Aina Zulkifli', 'Submitted proposal Database Systems v1', 'DB'],
    ['14 Oct 2026, 10:42', 'System', 'Issued 31 credentials after verification, Linear Algebra 2026/2', 'LA-2026-2'],
    ['2 Sep 2026, 09:20', 'Farid Hashim', 'Changed alerts.qv_expiry_days 90, 30 → 180, 90, 30', 'Settings'],
    ['2 Sep 2026, 08:55', 'Hakim Rahman', 'Revoked MCMS-C-2026-000377: result withdrawn', 'MCMS-C-2026-000377'],
    ['5 Mar 2026, 21:12', 'Amir Hakimi', 'Gave consent to deposit Data Structures ACR in ABC', 'MCMS-A-2026-000102'],
    ['3 Mar 2026, 10:00', 'System', 'Issued credential MCMS-C-2026-000098 to Amir Hakimi', 'MCMS-C-2026-000098'],
  ],
  users: [
    ['Hakim Rahman', 'S00921', 'Deputy dean', 'FTMK', '1 Jan 2026', 'Active'], ['Dr. Farah Nadia Ismail', 'S00954', 'Deputy dean', 'FKE', '1 Jan 2026', 'Active'],
    ['Dr. Aina Zulkifli', 'S01842', 'Lecturer; Reviewer', 'FTMK', '3 Feb 2025', 'Active'], ['Dr. Rosli Hamzah', 'S01177', 'Lecturer; Reviewer', 'FTMK', '3 Feb 2025', 'Active'],
    ['Ir. Dr. Zul Hilmi Che Ani', 'S01420', 'Lecturer', 'FKE', '3 Feb 2025', 'Active'], ['Dr. Kamal Ariffin', 'S01388', 'Lecturer', 'FKE', '3 Feb 2025', 'Deactivated'],
    ['Farid Hashim', 'S02210', 'System administrator', 'All PTj', '1 Oct 2024', 'Active'], ['Nor Azlina Musa', 'S02231', 'CODL operator', 'All PTj', '1 Oct 2024', 'Active'],
    ['Ir. Siti Aminah Yusof', 'X2026-0050', 'Industry expert', 'FTMK (invited)', '20 Oct 2026', 'Active'], ['Amir Hakimi', 'X2026-0193', 'Participant', '—', '2 Aug 2025', 'Active'],
    ...NAMES.slice(0, 18).map((n, i) => [n, `B0324${10077 + i * 3}`, 'Participant', '—', '1 Jul 2026', 'Active']),
  ],
  imports: [
    { file: 'results-CND-2026-2.csv', dir: 'Import', rows: 24, result: '22 imported, 2 problems', when: '25 Oct, 08:00', cohort: 'CND-2026-2', problems: [[7, 'Daniel Ong, X2026-0196', 'project_mark is empty'], [19, 'Wong Kar Seng, X2026-0201', 'module2_mark is empty']] },
    { file: 'results-DS-2026-2.csv', dir: 'Import', rows: 28, result: 'All imported', when: '24 Oct, 08:00', cohort: 'DS-2026-2', problems: [] },
    { file: 'enrol-IIOT-2026-2.csv', dir: 'Export', rows: 35, result: 'Sent', when: '1 Sep, 09:30', cohort: 'IIOT-2026-2', problems: [] },
    { file: 'results-LA-2026-2.csv', dir: 'Import', rows: 32, result: '31 imported, 1 problem', when: '10 Oct, 08:00', cohort: 'LA-2026-2', problems: [[12, 'B0324999', 'unknown matric number']] },
  ],
};

/* lookups every screen uses */
MC.mc = id => MC.state.mcs.find(m => m.id === id);
MC.co = id => MC.state.cohorts.find(c => c.id === id);
MC.mcName = id => { const m = MC.mc(id); return m ? `${m.code ? m.code + ' ' : ''}${m.name}` : id; };
MC.mcNm = id => { const m = MC.mc(id); return m ? MC.nm(m.cat, MC.mcName(id)) : id; };
})();
