import { expect, Page, test } from '@playwright/test';

// FL-19: the Release 1 demonstration scenario of PRD Section 7.1, act by act, through the screens only.
// Act 1 Curriculum, Act 2 Learning and credentials, Act 3 Credit, Act 4 Quality. Every step is a separate, recorded decision.
// The acts depend on each other, so they run in order and share the demo clock.

test.describe.configure({ mode: 'serial' });

let clock = 0; // days the demo clock is ahead
const day = (n: number) => { const d = new Date(Date.now() + (clock + n) * 86400000); return d.toISOString().slice(0, 10); };
let offeringId = '';
let shareLink = '';

async function signIn(page: Page, name: string, mfa = false) {
  await page.goto('/signin');
  await page.getByRole('button', { name: `Sign in as ${name}` }).click();
  if (mfa) {
    await expect(page).toHaveURL(/\/signin\/mfa/);
    await page.getByLabel('6-digit code').fill('123456');
    await page.getByRole('button', { name: 'Verify' }).click();
  }
  await expect(page).toHaveURL(/\/tasks/);
}
const signOut = async (page: Page) => { await page.locator('.who-btn').click(); await page.getByRole('button', { name: 'Sign out' }).click(); };
const alias: Record<string, string> = { Offerings: 'Cohorts and results|Offerings and readiness', 'Credit transfer evaluation': 'Credit transfer', Reviews: 'Reviews for colleagues', 'Quality Verification': 'QV evidence', 'Quality review': 'Quality review (CQI)', 'Traceability and records': 'Traceability' };
const nav = (page: Page, name: string) => { const m = page.getByRole('navigation', { name: 'Main' }); const rx = (n: string) => new RegExp('^' + n.replace(/[()]/g, '\\$&') + '( \\d+)?$'); return m.getByRole('link', { name: rx(name) }).or(m.getByRole('link', { name: rx(alias[name] ?? name) })); };

test('Act 1: Curriculum. A guided proposal stops an invalid UMC, and approval applies to one version', async ({ page }) => {
  // Dr. Aina proposes the UMC "Data Structures" from BITS 1123: 3 credits, 120 hours, 3 modules
  await signIn(page, 'Dr. Aina Zulkifli');
  await page.getByRole('link', { name: 'Proposals and versions' }).click();
  await page.getByRole('link', { name: 'New proposal' }).click();
  await page.getByLabel('Modules from a UTeM credit course').check();
  await page.getByRole('button', { name: 'Show the category' }).click();
  await expect(page.getByRole('heading', { name: /Proposed category: UMC/ })).toBeVisible();
  await page.getByLabel('Working title (English)').fill('Data Structures');
  await page.getByRole('button', { name: 'Create proposal' }).click();
  await expect(page.getByRole('status')).toContainText('proposed until your deputy dean verifies it');

  await page.getByLabel('Title (Bahasa Melayu)').fill('Struktur Data');
  await page.getByLabel('Delivery mode').selectOption('online');
  await page.getByLabel('Delivery language').fill('English');
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByRole('link', { name: 'Justification' }).click();
  await page.getByLabel('Need or problem addressed').fill('Employers need people who can choose and build the right data structure.');
  await page.getByRole('button', { name: 'Save' }).click();

  // The SLT check fails at 110 hours and is fixed to 120
  await page.getByRole('link', { name: 'Modules', exact: true }).click();
  await page.getByLabel('Unbundling model').selectOption('credit_based');
  for (let i = 0; i < 3; i++) await page.getByRole('button', { name: 'Add module' }).click();
  const hours = [40, 40, 30];
  for (let i = 0; i < 3; i++) {
    await page.getByLabel(`Module ${i + 1} name in English`).fill(`Module ${i + 1}`);
    await page.getByLabel(`Module ${i + 1} hours`).fill(String(hours[i]));
  }
  await page.getByRole('button', { name: 'Save' }).click();
  const checks = page.getByRole('complementary', { name: 'Checks' });
  await expect(checks).toContainText('The modules total 110 hours; they must equal the parent course (120 hours).');
  await page.getByRole('link', { name: 'Submit', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Submit for review' })).toBeDisabled();
  await page.getByRole('link', { name: 'Modules', exact: true }).click();
  await page.getByLabel('Module 3 hours').fill('40');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(checks).toContainText('Total learning time: 120 h');

  // Outcomes and assessments: each module has its own assessment, so that each module can be achieved
  await page.getByRole('link', { name: 'Learning outcomes' }).click();
  for (let i = 0; i < 3; i++) await page.getByRole('button', { name: 'Add outcome' }).click();
  for (let i = 1; i <= 3; i++) await page.getByLabel(`LO${i} in English`).fill(`Outcome ${i} of Data Structures`);
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByRole('link', { name: 'Assessment', exact: true }).click();
  for (let i = 0; i < 3; i++) await page.getByRole('button', { name: 'Add component' }).click();
  for (let i = 0; i < 3; i++) {
    await page.locator('input[name$="[name_en]"]').nth(i).fill(`Module ${i + 1} test`);
    await page.locator('input[name$="[weight_percent]"]').nth(i).fill(['30', '30', '40'][i]);
    await page.locator('input[name$="[csv_key]"]').nth(i).fill(`module${i + 1}_mark`);
    await page.locator('select[name$="[mc_module_id]"]').nth(i).selectOption({ index: i + 1 });
    await page.locator('input[name^="align["]').nth(i * 3 + i).check();
  }
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(checks).not.toContainText('has no assessment');

  await page.getByRole('link', { name: 'Submit', exact: true }).click();
  await page.getByLabel(/I confirm that third-party materials/).check();
  const reply = page.getByLabel('Your response to the warnings');
  if (await reply.isEnabled().catch(() => false)) await reply.fill('Reviewed; the rubrics are attached after approval.');
  await page.getByRole('button', { name: 'Submit for review' }).click();
  await expect(page.getByRole('status')).toContainText('Submitted. The content is locked');
  await signOut(page);

  // Colleague review: the deputy dean assigns Dr. Lim, who answers the conflict question and recommends support
  await signIn(page, 'Hakim Rahman', true);
  await page.getByRole('link', { name: /^Proposals \(/ }).click();
  await page.getByRole('link', { name: 'Assign reviewers: Data Structures' }).click();
  await page.getByLabel('Dr. Lim Wei Jie').check();
  await page.getByLabel('Due date').fill(day(10));
  await page.getByLabel('Scope of review').fill('Academic content and assessment');
  await page.getByRole('button', { name: 'Assign' }).click();
  await signOut(page);

  await signIn(page, 'Dr. Lim Wei Jie');
  await nav(page, 'Reviews').click();
  await page.getByRole('link', { name: 'Data Structures' }).click();
  await page.getByLabel('I have no conflict of interest').check();
  await page.getByRole('button', { name: 'Confirm' }).click();
  const ratings = page.locator('select[name^="rating["]');
  for (let i = 0; i < (await ratings.count()); i++) await ratings.nth(i).selectOption('satisfactory');
  await page.getByLabel('Recommendation').selectOption('support');
  await page.getByLabel('Summary').fill('Sound structure; the hours are right.');
  await page.getByRole('button', { name: 'Submit review' }).click();
  await expect(page.getByRole('status')).toHaveText('Review submitted. Thank you.');
  await signOut(page);

  // Deputy dean endorsement with the category check; the Senate approval is recorded by the deputy dean
  await signIn(page, 'Hakim Rahman', true);
  await page.goto('/approvals');
  await page.getByRole('link', { name: 'Data Structures' }).click();
  await page.getByLabel(/I have checked the category/).check();
  await page.getByRole('button', { name: 'Endorse', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Endorsed. The category is verified.');
  await page.getByRole('link', { name: 'Record Senate decision' }).click();
  await page.getByLabel('Senate meeting reference').fill('Senate 8/2026');
  await page.getByLabel('Meeting date').fill(day(0));
  await page.getByLabel('Decision').selectOption('approved');
  await page.getByLabel('Wording from the minutes').fill('Approved as presented to the Senate.');
  await page.getByRole('button', { name: 'Record decision' }).click();
  await expect(page.getByRole('status')).toContainText('No credit, Quality Verification or transfer status is set');
  await signOut(page);

  // Readiness is confirmed and the cohort opens in the catalogue
  await signIn(page, 'Dr. Aina Zulkifli');
  await nav(page, 'Offerings').click();
  await page.getByRole('link', { name: 'New offering' }).click();
  await page.getByLabel('Approved version').selectOption({ label: 'Data Structures (version 1)' });
  await page.getByLabel('Cohort name').fill('Jan 2027');
  await page.getByLabel('Start date').fill(day(30));
  await page.getByLabel('End date').fill(day(90));
  await page.getByLabel('Places').fill('40');
  await page.getByLabel('Registration opens').fill(day(0));
  await page.getByLabel('Registration closes').fill(day(20));
  await page.getByLabel('Fee (RM, 0 for free)').fill('150');
  await page.getByLabel('Facilitators').fill('Dr. Aina Zulkifli');
  await page.getByLabel('Description').fill('Hands-on labs and weekly clinics.');
  await page.getByLabel('Prerequisites').fill('Basic programming');
  await page.getByLabel('Completion conditions').fill('Pass every module test');
  await page.getByRole('button', { name: 'Create offering' }).click();
  await expect(page.getByRole('status')).toContainText('Cohort created');
  const items = ['Transparency of information', 'Learning materials and platform', 'Assessment and integrity', 'Participant and achievement records', 'Completion and credential process', 'Change notification'];
  for (const label of items) {
    await page.getByLabel(`Status of ${label}`).selectOption('yes');
    await page.getByLabel(`Evidence note for ${label}`).fill(`Evidence for ${label}`);
  }
  await page.getByRole('button', { name: 'Send the checklist' }).click();
  await expect(page.getByRole('status')).toContainText('Checklist sent to the deputy dean');
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await page.getByRole('link', { name: /^Offerings \(/ }).click();
  await page.getByRole('link', { name: 'Confirm readiness: Data Structures Jan 2027' }).click();
  for (const label of items) await page.getByLabel(`Accept evidence for ${label}`).check();
  await page.getByLabel('Outcome').selectOption('ready');
  await page.getByRole('button', { name: 'Record decision' }).click();
  await expect(page.getByRole('status')).toContainText('Readiness confirmed');
  await signOut(page);
  await page.goto('/public/catalogue');
  await expect(page.getByText('Data Structures').first()).toBeVisible();
});

test('Act 2: Learning and credentials. Imported data stays provisional until verified; credentials are signed; a correction keeps history', async ({ page }) => {
  // Amir registers and pays with the dummy card
  await signIn(page, 'Amir Hakimi');
  await page.getByRole('link', { name: 'Find a micro-credential' }).first().click();
  await page.getByRole('link', { name: 'Data Structures Jan 2027' }).click();
  await page.getByRole('link', { name: 'Register' }).click();
  await page.getByLabel('Confirm your phone number').fill('012-3456789');
  await page.getByLabel(/I meet the prerequisites/).check();
  await page.getByRole('button', { name: 'Continue' }).click();
  await expect(page.getByText('Unbundled Micro-Credential (UMC)').first()).toBeVisible();
  await page.getByLabel('I have read the recognition statement.').check();
  await page.getByLabel('I have read the personal data notice.').check();
  await page.getByRole('button', { name: 'Continue to payment' }).click();
  await page.getByRole('button', { name: 'Pay', exact: true }).click();
  await page.getByRole('button', { name: 'Pay now' }).click();
  await expect(page.getByText(/Enrolment ID E-\d{4}-\d{4}/)).toBeVisible();
  await signOut(page);

  // The demo clock passes the registration and the cohort starts
  await signIn(page, 'Farid Hashim', true);
  await page.getByRole('link', { name: 'Demo clock' }).click();
  await page.getByLabel('Move forward by (days)').fill('31');
  await page.getByRole('button', { name: 'Move the clock' }).click();
  clock = 31;
  await page.getByRole('button', { name: 'Run the daily jobs' }).click();
  await expect(page.getByRole('status')).toContainText('Daily jobs run');

  // UCreds results are imported by CSV; one bad row is rejected and the good row is staged
  await page.getByRole('link', { name: 'UCreds exchange' }).click();
  offeringId = (await page.locator('#offering_id option', { hasText: 'Data Structures Jan 2027' }).getAttribute('value')) ?? '';
  const exportCsv = await (await page.request.get(`/admin/ucreds/export/${offeringId}`)).text();
  const [matric, , enrolment, ref] = exportCsv.trim().split('\r\n')[1].split(',');
  const header = 'learner_id,enrolment_id,offering,progress_percent,modules_completed,module1_mark,module2_mark,module3_mark';
  const rows = [[matric, enrolment, ref, '100', '3', '70', '65', '80'], ['B039999999', 'E-0000-0000', ref, '100', '3', '60', '60', '60']];
  await page.locator('#offering_id').selectOption(offeringId);
  await page.getByLabel('CSV file').setInputFiles({ name: 'results-DS-Jan-2027.csv', mimeType: 'text/csv', buffer: Buffer.from([header, ...rows.map((r) => r.join(','))].join('\r\n') + '\r\n') });
  await page.getByRole('button', { name: 'Check file' }).click();
  await expect(page.getByText(/1 rows? ready/)).toBeVisible();
  await expect(page.getByText(/1 rejected/)).toBeVisible();
  await page.getByRole('button', { name: /^Stage 1 row/ }).click();
  await expect(page.getByRole('status')).toContainText('Nothing becomes an achievement until the deputy dean verifies');
  await signOut(page);

  // The lecturer submits the staged results; only the deputy dean verifies, which creates the module badges
  await signIn(page, 'Dr. Aina Zulkifli');
  await page.goto(`/offerings/${offeringId}/results`);
  await page.getByRole('button', { name: 'Submit for verification' }).click();
  await expect(page.getByRole('status')).toContainText('Submitted for verification');
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Results and completion').click();
  await page.getByRole('link', { name: 'Data Structures Jan 2027' }).click();
  await page.getByLabel('Evidence linking identity, assessment and result').fill('UCreds proctoring log and photo ID check');
  await page.getByRole('button', { name: /^Verify \d+ results?/ }).click();
  await expect(page.getByRole('status')).toContainText('Module badges created: 3');
  await expect(page.getByRole('status')).toContainText('Nothing is issued yet');

  // Completion is confirmed separately; the system then issues the certificate and the signed Digital Credential
  await page.getByRole('link', { name: 'Completion', exact: true }).click();
  await expect(page.getByText('All requirements met')).toHaveCount(1);
  await page.getByRole('button', { name: 'Confirm completion' }).click();
  await expect(page.getByRole('status')).toContainText('Credentials issued: 1');
  await signOut(page);

  // An employer verifies by the share link; a name typo is corrected with its history kept
  await signIn(page, 'Amir Hakimi');
  await nav(page, 'Credentials').click();
  await page.getByRole('link', { name: 'Data Structures' }).click();
  shareLink = await page.getByLabel('Share link').inputValue();
  await signOut(page);
  await page.goto(shareLink);
  await expect(page.locator('#vstatus')).toHaveText('Valid');
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Credentials').click();
  await page.getByRole('row', { name: /Amir Hakimi/ }).getByRole('link').click();
  await page.getByLabel('Name as it should read').fill('Amir Hakimi bin Razak');
  await page.getByLabel('Reason', { exact: true }).first().fill('The father\'s name was left out by mistake');
  await page.getByRole('button', { name: 'Correct the credential' }).click();
  await expect(page.getByRole('status')).toContainText('Corrected');
  await signOut(page);
  await page.goto(shareLink);
  await expect(page.locator('#vstatus')).toHaveText('Valid, corrected');
  await expect(page.getByRole('cell', { name: 'Amir Hakimi bin Razak' })).toBeVisible();
});

test('Act 3: Credit. ACR needs consent; deposit is not transfer; transfer is decided on outcomes; credit cannot be used twice; stackable rules are checked', async ({ page }) => {
  // The system generated the ACR; the deputy dean confirms it
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'ACR and ABC deposits').click();
  await page.getByRole('row', { name: /Data Structures.*Amir Hakimi/ }).getByRole('link', { name: 'Review' }).click();
  await expect(page.locator('#checks')).toContainText('Passed');
  await page.getByRole('button', { name: 'Confirm Academic Credit Record' }).click();
  await signOut(page);

  // Amir consents; the deputy dean records the ABC reference; the ledger shows 3 credits Available with an expiry in 10 years
  await signIn(page, 'Amir Hakimi');
  await nav(page, 'Credit ledger and ABC').click();
  await page.getByRole('link', { name: 'Data Structures' }).click();
  await expect(page.locator('#consent')).toContainText('It is not a credit transfer');
  await page.getByLabel('I consent to the deposit').check();
  await page.getByRole('button', { name: 'Save my choice' }).click();
  await expect(page.getByRole('status')).toContainText('Consent recorded');
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'ACR and ABC deposits').click();
  await page.getByRole('row', { name: /Data Structures.*Amir Hakimi/ }).getByRole('link', { name: 'Record deposit' }).click();
  await page.getByLabel('ABC reference').fill('ABC-2026-0001');
  await page.getByLabel('Deposit date').fill(day(0));
  await page.getByRole('button', { name: 'Record deposit' }).click();
  await expect(page.getByRole('status')).toContainText('This is storage, not a credit transfer');
  await signOut(page);
  await signIn(page, 'Amir Hakimi');
  await nav(page, 'Credit ledger and ABC').click();
  await expect(page.getByRole('row', { name: /Data Structures.*Available/ })).toBeVisible();
  await expect(page.locator('#total')).toHaveText('3');
  await expect(page.locator('#ledger')).toContainText(String(new Date(Date.now() + clock * 86400000).getFullYear() + 10));

  // Amir applies for transfer; the deputy dean records his admission as a separate fact, judges the equivalence on outcomes and approves
  await nav(page, 'Credit transfer').click();
  await page.getByRole('link', { name: 'New transfer application' }).click();
  await page.getByLabel(/^Use Data Structures/).check();
  await page.getByRole('button', { name: 'Submit application' }).click();
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Credit transfer evaluation').click();
  await page.getByRole('row', { name: /Amir Hakimi/ }).getByRole('link', { name: 'Open' }).click();
  await page.getByLabel('Intake').fill('2027/1');
  await page.getByLabel('Evidence reference').first().fill('Registrar letter 12/2026');
  await page.getByRole('button', { name: 'Record admission' }).click();
  for (const r of await page.locator('#validity').getByRole('radio', { name: 'Passed' }).all()) await r.check();
  await page.getByRole('button', { name: 'Record validity check' }).click();
  const form = page.locator('.line form').first();
  const n = await form.getByLabel(/^Match for:/).count();
  for (let i = 0; i < n; i++) {
    await form.getByLabel(/^Outcome meeting:/).nth(i).selectOption({ index: 1 });
    await form.getByLabel(/^Match for:/).nth(i).selectOption('full');
  }
  for (const label of ['Credit value and learning time', 'MQF level', 'Content and depth', 'Learning activities', 'Assessment and standard', 'Accreditation or Quality Verification status', 'Currency of knowledge', 'Programme or professional body requirements']) {
    await form.getByLabel(`Finding for ${label}`).fill('Checked against the course');
    await form.getByLabel(`Result for ${label}`).selectOption('equivalent');
  }
  await form.getByRole('button', { name: 'Save evaluation' }).click();
  await expect(page.locator('.line-lo')).toContainText('Equivalent');
  await page.locator('#decision_ref').fill('FTMK 11/2026');
  await page.getByRole('button', { name: 'Record decision' }).click();
  await expect(page.getByRole('status')).toContainText('provisionally redeemed');
  await signOut(page);

  // A second application using the same credit is blocked
  await signIn(page, 'Amir Hakimi');
  await nav(page, 'Credit transfer').click();
  await page.getByRole('link', { name: 'New transfer application' }).click();
  await expect(page.getByText('provisionally redeemed for a programme')).toBeVisible();
  await expect(page.getByLabel(/^Use Data Structures/)).toBeDisabled();
  await signOut(page);

  // A stackable pathway is mapped, approved and published; the plan shows 25% UTeM credit: Not yet suitable
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Stackable pathways').click();
  await page.getByRole('button', { name: 'Start a draft mapping' }).click();
  await page.getByLabel('Micro-credential').selectOption({ label: 'Data Structures (UMC)' });
  await page.getByLabel('Target course').selectOption({ index: 0 });
  await page.getByLabel('Credits', { exact: true }).fill('3');
  await page.getByRole('button', { name: 'Save mapping' }).click();
  await page.getByLabel('Senate meeting reference').fill('Senate 9/2026');
  await page.getByRole('button', { name: 'Record approval' }).click();
  await page.getByRole('button', { name: 'Publish to participants' }).click();
  await expect(page.locator('#pw-status')).toHaveText('Published');
  await nav(page, 'Stackable pathways').click();
  await page.getByRole('link', { name: 'New individual plan' }).click();
  await page.getByLabel('Participant').selectOption({ label: 'Amir Hakimi' });
  for (const box of await page.getByRole('checkbox').all()) await box.check();
  await page.getByLabel('Other credit from prior qualifications').fill('87');
  await page.getByLabel('Requirement 1').fill('Final year project');
  await page.getByRole('button', { name: 'Create plan' }).click();
  await expect(page.locator('#figures')).toContainText('25%');
  await page.locator('#decision-sel').selectOption('not_yet');
  await page.getByLabel('Conditions and follow-up').fill('Earn more of the credit at UTeM');
  await page.getByRole('button', { name: 'Record decision' }).click();
  await expect(page.locator('#plan-status')).toHaveText('Not yet suitable');
  await signOut(page);
});

test('Act 4: Quality. QV gives credit and MQF level only while valid; an MCE never earns an ACR; CQI closes only after its effect is checked', async ({ page }) => {
  // The SAMC "Cloud Native Development": evidence is linked and the Quality Verification result is recorded: 2 credits, MQF level 4
  await signIn(page, 'Dr. Aina Zulkifli');
  await nav(page, 'Quality Verification').click();
  await page.getByRole('link', { name: 'Cloud Native Development' }).click();
  await page.getByRole('button', { name: 'Start a Quality Verification cycle' }).click();
  await expect(page.locator('#evidence')).toContainText('Available (linked from MCMS)');
  await page.locator('#decision').selectOption('ready');
  await page.getByRole('button', { name: 'Record decision' }).click();
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Quality Verification').click();
  await page.getByRole('link', { name: 'Cloud Native Development' }).click();
  await page.getByRole('button', { name: 'Confirm readiness decision' }).click();
  await expect(page.locator('#cycle')).toContainText('Ready to submit');
  await page.getByLabel('Date submitted').fill(day(0));
  await page.getByLabel('QVC/MQA reference').fill('MQA/QV/2026/501');
  await page.getByRole('button', { name: 'Record submission' }).click();
  await page.getByLabel('QV reference').fill('QV/2026/501');
  await page.getByLabel('Verified credit').fill('2');
  await page.getByLabel('MQF level').fill('4');
  await page.getByLabel('Valid from').fill(day(-60));
  await page.getByLabel('Valid until').fill(day(41));
  await page.getByLabel('Decision document').fill('qv-2026-501.pdf');
  await page.getByRole('button', { name: 'Record result' }).click();
  await expect(page.locator('#in-force')).toContainText('2 credit, MQF level 4');
  await signOut(page);

  // The demo clock passes the expiry: the Quality-Verified claims disappear
  await signIn(page, 'Farid Hashim', true);
  await page.getByRole('link', { name: 'Demo clock' }).click();
  await page.getByLabel('Move forward by (days)').fill('50');
  await page.getByRole('button', { name: 'Move the clock' }).click();
  clock = 81;
  await page.getByRole('button', { name: 'Run the daily jobs' }).click();
  await expect(page.getByRole('status')).toContainText('Lapse expired Quality Verification (1)');
  await signOut(page);
  await signIn(page, 'Dr. Aina Zulkifli');
  await nav(page, 'Quality Verification').click();
  await expect(page.getByRole('row', { name: /Cloud Native Development.*Expired/ })).toBeVisible();
  await expect(page.getByRole('row', { name: /Cloud Native Development.*Quality-Verified/ })).toHaveCount(0);
  await signOut(page);

  // The MCE "Leadership Essentials" completes: no ACR is offered
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Results and completion').click();
  await page.getByRole('link', { name: 'Leadership Essentials 2026/2' }).click();
  await page.getByRole('link', { name: 'Completion', exact: true }).click();
  await page.getByRole('button', { name: 'Confirm completion' }).click();
  await expect(page.getByRole('status')).toContainText('Credentials issued: 1');
  await nav(page, 'ACR and ABC deposits').click();
  await expect(page.locator('main').getByText('Leadership Essentials')).toHaveCount(0);
  await signOut(page);
  await signIn(page, 'Amir Hakimi');
  await nav(page, 'Credit ledger and ABC').click();
  await expect(page.locator('main').getByText('Leadership Essentials')).toHaveCount(0);
  await signOut(page);

  // A CQI action closes only after its effectiveness check
  await signIn(page, 'Dr. Aina Zulkifli');
  await nav(page, 'Quality review').click();
  await page.getByRole('button', { name: /Start a quality review of Data Structures Jan 2027/ }).click();
  for (const [i, d] of ['Content currency', 'Delivery', 'Assessment and integrity', 'Platform and access', 'Participant support', 'Learning outcome attainment'].entries()) {
    await page.getByLabel(`Status of ${d}`).selectOption(i === 2 ? '0' : '1');
    await page.getByLabel(`Evidence for ${d}`).fill(`Evidence for ${d}`);
  }
  await page.getByRole('button', { name: 'Save domains' }).click();
  await page.getByLabel('Evidence', { exact: true }).fill('One participant needed a re-mark');
  await page.getByLabel('Root cause').fill('The rubric was unclear');
  await page.getByLabel('Impact').fill('A result was held');
  await page.getByLabel('Improvement action').fill('Rewrite the rubric');
  await page.getByLabel('Due date').fill(day(30));
  await page.getByLabel('Success indicator').fill('No re-marks next cohort');
  await page.getByRole('button', { name: 'Record', exact: true }).click();
  await page.getByRole('button', { name: /^Start: Rewrite/ }).click();
  await page.getByRole('button', { name: /^Mark done: Rewrite/ }).click();
  await page.getByRole('button', { name: 'Submit to the deputy dean' }).click();
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Quality review').click();
  await page.getByRole('link', { name: /CQI-/ }).first().click();
  await page.getByRole('button', { name: /^Close: Rewrite/ }).click();
  await expect(page.getByRole('alert')).toContainText('only after its effectiveness is evaluated');
  await signOut(page);
  await signIn(page, 'Dr. Aina Zulkifli');
  await nav(page, 'Quality review').click();
  await page.getByRole('link', { name: /CQI-/ }).first().click();
  await page.getByLabel(/^Before evidence/).fill('One re-mark');
  await page.getByLabel(/^After evidence/).fill('No re-marks in the trial');
  await page.getByRole('button', { name: /^Evaluate: Rewrite/ }).click();
  await signOut(page);
  await signIn(page, 'Hakim Rahman', true);
  await nav(page, 'Quality review').click();
  await page.getByRole('link', { name: /CQI-/ }).first().click();
  await page.getByRole('button', { name: /^Close: Rewrite/ }).click();
  await expect(page.locator('.action')).toContainText('Closed');
  for (const box of await page.locator('#confirm').getByRole('checkbox').all()) await box.check();
  await page.getByRole('button', { name: 'Confirm', exact: true }).click();
  await expect(page.locator('#cqi-status')).toHaveText('Confirmed');

  // The traceability view for Amir shows every stage, with the audit rows of the same records
  await nav(page, 'Traceability and records').click();
  await page.getByLabel('Find a participant by name or matric number').fill('Amir');
  await page.getByRole('button', { name: 'Search' }).click();
  await page.getByRole('row', { name: /Amir Hakimi.*Data Structures/ }).getByRole('link', { name: 'Trace' }).click();
  for (const stage of ['development', 'approval', 'offering', 'learning', 'assessment', 'achievement', 'credential', 'acr', 'transfer', 'cqi']) {
    await expect(page.locator(`#chain li[data-stage="${stage}"]`)).toHaveClass(/present/);
  }
  await expect(page.locator('#trace-audit')).toContainText('credential.corrected');
  await expect(page.locator('#trace-audit')).toContainText('transfer.decided');
});
