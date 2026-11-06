import { execSync } from 'node:child_process';
import { expect, Page, test } from '@playwright/test';

// Roles that decide on records get a second step; the stub accepts any six digits.
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

test('FL-01 Sign in with a second step and land on the task page', async ({ page }) => {
  await page.goto('/signin');
  await expect(page.getByRole('button', { name: /Sign in with Microsoft/ })).toBeDisabled(); // Entra ID waits for OI-03
  await page.getByRole('button', { name: 'Sign in as Hakim Rahman' }).click();

  await expect(page).toHaveURL(/\/signin\/mfa/);
  await page.getByLabel('6-digit code').fill('12ab');
  await page.getByRole('button', { name: 'Verify' }).click();
  await expect(page.getByText('Enter the 6-digit code.')).toBeVisible();
  await expect(page).not.toHaveURL(/\/tasks/);

  await page.getByLabel('6-digit code').fill('123456');
  await page.getByRole('button', { name: 'Verify' }).click();
  await expect(page).toHaveURL(/\/tasks/);
  await expect(page.getByRole('heading', { name: 'My tasks' })).toBeVisible();
  await expect(page.getByRole('link', { name: /sample item/ }).first()).toBeVisible(); // the queue, not an empty home page
});

test('FL-01 A student signs in without a second step', async ({ page }) => {
  await signIn(page, 'Amir Hakimi');
  await expect(page.getByText('Give or decline consent to deposit your ACR')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Settings' })).toHaveCount(0); // no administrator pages in his menu
});

test('FL-00 Tour the deputy dean task queue', async ({ page }) => {
  await signIn(page, 'Hakim Rahman', true);
  await expect(page.getByRole('link', { name: 'All (24)' })).toHaveAttribute('aria-current', 'page');
  await expect(page.getByText('Overdue').first()).toBeVisible(); // oldest waiting items are flagged in words

  await page.getByRole('link', { name: 'Results (4)' }).click(); // tabs filter by area and the counts follow
  await expect(page).toHaveURL(/area=results/);
  await expect(page.locator('tbody tr')).toHaveCount(4);

  await page.getByLabel('Search tasks').fill('sample item 11');
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page.locator('tbody tr')).toHaveCount(1);

  await page.locator('tbody tr a').first().click(); // a row opens its decision screen
  await expect(page).toHaveURL(/\/tasks\/\d+/);
  await expect(page.getByRole('heading', { name: /Verify results: sample item 11/ })).toBeVisible();

  await page.goto('/tasks'); // ten to a page, sortable, searchable
  await expect(page.locator('tbody tr:visible')).toHaveCount(10);
  await expect(page.getByText('Page 1 of 3')).toBeVisible();
  await page.getByRole('button', { name: 'Next page' }).click();
  await expect(page.getByText('Page 2 of 3')).toBeVisible();
});

test('FL-17 Assign a scoped role and change a setting', async ({ page }) => {
  await signIn(page, 'Farid Hashim', true);

  await page.getByRole('link', { name: 'Users and roles' }).click(); // 1. search the user list and open a person
  await page.getByLabel('Search by name or email').fill('Aina');
  await page.getByRole('button', { name: 'Search' }).click();
  await page.getByRole('link', { name: 'Dr. Aina Zulkifli' }).click();

  await page.getByLabel('Role', { exact: true }).selectOption('reviewer'); // 2. assign a role with scope, reason and date
  await page.getByLabel('Scope', { exact: true }).selectOption('mc');
  await page.getByLabel('Micro-credential code').fill('LE');
  await page.locator('#reason').fill('Reviews Leadership Essentials, which Dr. Aina does not own');
  await page.getByRole('button', { name: 'Assign role' }).click();
  await expect(page.getByRole('status')).toHaveText('Role assigned.');
  await expect(page.getByRole('row', { name: /Reviewer.*Micro-credential LE/ })).toBeVisible();

  await page.getByRole('link', { name: 'Settings' }).click(); // 3. change a setting, then read its history
  const row = page.locator('[id="s-alerts.qv_expiry_days"]');
  await row.getByLabel('New value').fill('120, 60, 30');
  await row.getByLabel('Reason').fill('Faculty asked for earlier alerts');
  await row.getByRole('button', { name: 'Change' }).click();
  await expect(page.getByRole('row', { name: /120, 60, 30.*Faculty asked for earlier alerts/ })).toBeVisible();
  await expect(page.getByRole('row', { name: /180, 90, 30/ })).toBeVisible();

  await page.getByRole('link', { name: 'Audit trail' }).click(); // 4. both changes are in the audit trail
  await expect(page.getByRole('row', { name: /role\.assigned.*Reviews Leadership Essentials/ })).toBeVisible();
  await expect(page.getByRole('row', { name: /setting\.changed.*Faculty asked for earlier alerts/ })).toBeVisible();
  await page.getByRole('button', { name: 'Check integrity' }).click();
  await expect(page.getByRole('status')).toContainText('the chain is intact');
});

test('FL-02 Propose a UMC, fix the hours, submit', async ({ page }) => {
  await signIn(page, 'Dr. Aina Zulkifli');

  await page.getByRole('link', { name: /^(Proposals and versions|My proposals)$/ }).click(); // 1. the three questions decide the category
  await page.getByRole('link', { name: 'New proposal' }).click();
  await page.getByLabel('My own offering').check();
  await page.getByLabel('How many hours of learning time (SLT) in total?').fill('30');
  await page.getByRole('button', { name: 'Show the category' }).click();
  await expect(page.getByRole('heading', { name: /Proposed category: MCE/ })).toBeVisible(); // a 30 h own offering is an MCE only

  await page.getByLabel('Modules from a UTeM credit course').check();
  await page.getByRole('button', { name: 'Show the category' }).click();
  await expect(page.getByRole('heading', { name: /Proposed category: UMC/ })).toBeVisible();
  await page.getByLabel('Working title (English)').fill('Software Testing');
  await page.getByRole('button', { name: 'Create proposal' }).click();
  await expect(page.getByRole('status')).toContainText('proposed until your deputy dean verifies it');

  await page.getByLabel('Title (Bahasa Melayu)').fill('Ujian Perisian');
  await page.getByLabel('Delivery mode').selectOption('online');
  await page.getByLabel('Delivery language').fill('English');
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByRole('link', { name: 'Justification' }).click();
  await page.getByLabel('Need or problem addressed').fill('Employers cannot find enough software testers.');
  await page.getByRole('button', { name: 'Save' }).click();

  await page.getByRole('link', { name: 'Modules', exact: true }).click(); // 2. 40 + 38 + 32 = 110 h against a 120 h parent course
  await page.getByLabel('Unbundling model').selectOption('topic_based');
  for (let i = 0; i < 3; i++) {
    await page.getByRole('button', { name: 'Add module' }).click();
  }
  const hours = [40, 38, 32];
  for (let i = 0; i < 3; i++) {
    await page.getByLabel(`Module ${i + 1} name in English`).fill(`Module ${i + 1}`);
    await page.getByLabel(`Module ${i + 1} hours`).fill(String(hours[i]));
  }
  await page.getByRole('button', { name: 'Save' }).click();
  const checks = page.getByRole('complementary', { name: 'Checks' });
  await expect(checks).toContainText('The modules total 110 hours; they must equal the parent course (120 hours).');

  await page.getByRole('link', { name: 'Submit', exact: true }).click(); // Submit is off, with the reason beside it
  await expect(page.getByRole('button', { name: 'Submit for review' })).toBeDisabled();
  await expect(page.getByText('Submit is off')).toBeVisible();

  await page.getByRole('link', { name: 'Modules', exact: true }).click(); // 3. set module 3 to 42 h: the error clears
  await page.getByLabel('Module 3 hours').fill('42');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(checks).not.toContainText('they must equal the parent course');
  await expect(checks).toContainText('Total learning time: 120 h');

  await page.getByRole('link', { name: 'Learning outcomes' }).click(); // the remaining sections
  await page.getByRole('button', { name: 'Add outcome' }).click();
  await page.getByLabel('LO1 in English').fill('Design and run software tests');
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByRole('link', { name: 'Assessment', exact: true }).click();
  await page.getByRole('button', { name: 'Add component' }).click();
  await page.getByLabel('Component name').fill('Test project');
  await page.getByLabel('Weight in percent').fill('100');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(checks).toContainText('Outcome LO1 has no assessment.'); // the warning of the storyboard

  await page.getByRole('link', { name: 'Submit', exact: true }).click(); // 4. warnings need a written response
  await page.getByLabel(/I confirm that third-party materials/).check();
  await page.getByLabel('Your response to the warnings').fill('The project assesses it; I will link it after review.');
  await page.getByRole('button', { name: 'Submit for review' }).click();
  await expect(page.getByRole('status')).toContainText('Submitted. The content is locked');
  await expect(page.getByLabel('Your response to the warnings')).toBeDisabled();

  await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click(); // 5. the deputy dean has a task
  await signIn(page, 'Hakim Rahman', true);
  await page.getByRole('link', { name: /^Proposals \(/ }).click();
  await expect(page.getByRole('link', { name: 'Assign reviewers: Software Testing' })).toBeVisible();
});

// The demonstration proposals of the review and approval flows (FL-03, FL-04).
test.describe('Review and approval', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoProposalsSeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });

  test('FL-03 Review a colleague\'s proposal', async ({ page }) => {
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: 'Reviews' }).click(); // 1. the invitation
    await expect(page.getByRole('row', { name: /Cyber Security Operations Fundamentals.*Invited/ })).toBeVisible();
    await page.getByRole('link', { name: 'Cyber Security Operations Fundamentals' }).click();

    await expect(page.getByRole('heading', { name: 'Step 1: conflict of interest' })).toBeVisible(); // 2. the proposal stays closed until she answers
    await expect(page.getByText('Proposal summary')).toHaveCount(0);
    await page.getByLabel('I have no conflict of interest').check();
    await page.getByRole('button', { name: 'Confirm' }).click();
    await expect(page.getByText('Proposal summary')).toBeVisible();

    await page.getByRole('button', { name: 'Submit review' }).click(); // 3. nothing is rated yet
    await expect(page.getByText('Rate every item.').first()).toBeVisible();
    const ratings = page.locator('select[name^="rating["]');
    for (let i = 0; i < (await ratings.count()); i++) await ratings.nth(i).selectOption('satisfactory');
    await page.getByLabel('Rating for Adequacy of SLT').selectOption('needs_amendment');
    await page.getByRole('button', { name: 'Submit review' }).click();
    await expect(page.getByText('A "Needs amendment" rating needs a comment.').first()).toBeVisible();
    await page.getByLabel('Comment for Adequacy of SLT').fill('The practical work is under-counted.');
    await page.getByLabel('Mark the comment on Adequacy of SLT as blocking').check();

    await page.getByLabel('Recommendation').selectOption('amend'); // 4. recommend, summarise, submit
    await page.getByLabel('Summary').fill('Good structure; the hours need correcting.');
    await page.getByRole('button', { name: 'Submit review' }).click();
    await expect(page.getByRole('status')).toHaveText('Review submitted. Thank you.');
    await expect(page.getByRole('row', { name: /Cyber Security Operations Fundamentals.*Submitted/ })).toBeVisible();
  });

  test('FL-04 Endorse a proposal, then record the Senate decision', async ({ page }) => {
    await signIn(page, 'Hakim Rahman', true); // 1. two proposal tasks
    await page.getByRole('link', { name: /^Proposals \(/ }).click();
    await expect(page.getByRole('link', { name: 'Record Senate decision: Applied Data Analytics' })).toBeVisible();
    await page.getByRole('link', { name: 'Decide on proposal: Database Systems' }).click();

    await page.getByLabel(/I have checked the category/).check(); // 2. endorse is refused while blocking comments are open
    await page.getByRole('button', { name: 'Endorse', exact: true }).click();
    await expect(page.getByText('A blocking comment is not resolved yet.')).toBeVisible();
    const answered = page.locator('#comments tbody tr', { hasText: 'Answered' });
    await expect(answered).toContainText('I added 6 hours of lab work');
    await answered.getByPlaceholder('Reason').fill('The extra lab hours answer the comment.');
    await answered.getByRole('button', { name: 'Mark resolved' }).click();
    await expect(page.getByText("Waiting for the lecturer's response")).toBeVisible(); // the other one cannot be resolved yet
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await signIn(page, 'Dr. Aina Zulkifli'); // 3. the lecturer answers it
    await page.getByRole('link', { name: 'Respond to review comments: Database Systems' }).click();
    await expect(page.getByRole('link', { name: 'Comments' })).toHaveAttribute('aria-current', 'page');
    await page.getByLabel('Your response').fill('Outcome 3 is now assessed by the project.');
    await page.getByRole('button', { name: 'Send response' }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Your response is sent.' })).toBeVisible();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await signIn(page, 'Hakim Rahman', true); // 4. resolve, verify the category, endorse
    await page.goto('/approvals?status=in_review');
    await page.getByRole('link', { name: 'Database Systems', exact: true }).click();
    const second = page.locator('#comments tbody tr', { hasText: 'Answered' });
    await second.getByPlaceholder('Reason').fill('Alignment is fixed.');
    await second.getByRole('button', { name: 'Mark resolved' }).click();
    await page.getByLabel(/I have checked the category/).check();
    await page.getByRole('button', { name: 'Endorse', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Endorsed. The category is verified.');
    await expect(page.getByRole('row', { name: /Endorsement.*Endorsed.*category SAMC verified/ })).toBeVisible();

    await page.getByRole('link', { name: 'Decision pack' }).click(); // 5. the decision pack shows the endorsement
    await expect(page.getByText('Verified by Hakim Rahman')).toBeVisible();

    await page.goto('/approvals?status=endorsed'); // 6. record what the Senate decided
    await page.getByRole('link', { name: 'Applied Data Analytics' }).click();
    await page.getByRole('link', { name: 'Record Senate decision' }).click();
    await page.getByLabel('Senate meeting reference').fill('Senate 8/2026');
    const d = new Date(); d.setDate(d.getDate() - 2);
    await page.getByLabel('Meeting date').fill(d.toISOString().slice(0, 10));
    await page.getByLabel('Decision').selectOption('approved');
    await page.getByLabel('Wording from the minutes').fill('Approved as presented to the Senate.');
    await expect(page.getByText('does not set any credit')).toBeVisible();
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('status')).toContainText('No credit, Quality Verification or transfer status is set');

    await page.goto('/approvals'); // 7. both proposals show their new status
    await expect(page.getByRole('row', { name: /Database Systems.*Endorsed/ })).toBeVisible();
    await expect(page.getByRole('row', { name: /Applied Data Analytics.*Approved/ })).toBeVisible();
  });
  test('Versions: a minor change is acknowledged, a material change makes version 2', async ({ page }) => {
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: 'My micro-credentials' }).click();
    await page.getByRole('link', { name: 'Applied Data Analytics' }).click(); // approved in FL-04
    await expect(page.getByRole('row', { name: /Version 1.*Approved/ })).toBeVisible();

    await page.getByRole('link', { name: 'Request a change' }).click(); // a change that touches nothing on the list is minor
    await page.getByLabel('What is changing, and why?').fill('Replace the sample dataset with a newer one.');
    await page.getByLabel(/Impact on participants/).fill('Participants use the new dataset from the next cohort.');
    await page.getByRole('button', { name: 'Record the change' }).click();
    await expect(page.getByRole('status')).toContainText('This is a minor change');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Proposals \(/ }).click();
    await page.getByRole('link', { name: 'Acknowledge minor change: Applied Data Analytics' }).click();
    await page.getByRole('button', { name: 'Acknowledge' }).click();
    await expect(page.getByRole('status')).toContainText('logged for the quality record');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await signIn(page, 'Dr. Aina Zulkifli'); // a change to the outcomes is material: a new version
    await page.getByRole('link', { name: 'My micro-credentials' }).click();
    await page.getByRole('link', { name: 'Applied Data Analytics' }).click();
    await page.getByRole('link', { name: 'Request a change' }).click();
    await page.getByLabel('Learning outcomes').check();
    await page.getByLabel('What is changing, and why?').fill('Add an outcome on data ethics.');
    await page.getByRole('button', { name: 'Record the change' }).click();
    await expect(page.getByRole('status').first()).toContainText('This is a material change');
    await expect(page.getByText('Version 2').first()).toBeVisible();

    await page.getByRole('link', { name: 'My micro-credentials' }).click(); // version 1 stays in force while version 2 is a draft
    await page.getByRole('link', { name: 'Applied Data Analytics' }).click();
    await expect(page.getByRole('row', { name: /Version 1.*Approved/ })).toBeVisible();
    await expect(page.getByRole('row', { name: /Version 2.*Draft.*Material change/ })).toBeVisible();
  });
  test('FL-05 Create an offering and confirm readiness', async ({ page }) => {
    const day = (n: number) => { const d = new Date(); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: /^(Cohorts and results|Offerings and readiness|Offerings)$/ }).click(); // 1. a new cohort of an approved version
    await page.getByRole('link', { name: 'New offering' }).click();
    await page.getByLabel('Approved version').selectOption({ label: 'Applied Data Analytics (version 1)' });
    await page.getByLabel('Cohort name').fill('2027/1');
    await page.getByLabel('Start date').fill(day(30));
    await page.getByLabel('End date').fill(day(90));
    await page.getByLabel('Places').fill('40');
    await page.getByLabel('Registration opens').fill(day(0));
    await page.getByLabel('Registration closes').fill(day(20));
    await page.getByLabel('Facilitators').fill('Dr. Aina Zulkifli');
    await page.getByLabel('Description').fill('Hands-on notebooks and weekly clinics.');
    await page.getByLabel('Prerequisites').fill('Basic Python');
    await page.getByLabel('Completion conditions').fill('Pass the project');
    await page.getByRole('button', { name: 'Create offering' }).click();
    await expect(page.getByRole('status')).toContainText('Cohort created');
    await expect(page.getByText('Not ready').first()).toBeVisible();

    const items = ['Transparency of information', 'Learning materials and platform', 'Assessment and integrity', 'Participant and achievement records', 'Completion and credential process', 'Change notification'];
    for (const label of items.filter((l) => l !== 'Learning materials and platform')) { // 2. one item is still open
      await page.getByLabel(`Status of ${label}`).selectOption('yes');
      await page.getByLabel(`Evidence note for ${label}`).fill(`Evidence for ${label}`);
    }
    await page.getByRole('button', { name: 'Send the checklist' }).click();
    await expect(page.getByText('"Ready" needs every checklist item answered')).toBeVisible();
    await page.getByLabel('Status of Learning materials and platform').selectOption('yes');
    await page.getByLabel('Evidence note for Learning materials and platform').fill('Facilitator qualification evidence uploaded');
    await page.getByRole('button', { name: 'Send the checklist' }).click();
    await expect(page.getByRole('status')).toContainText('Checklist sent to the deputy dean');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await signIn(page, 'Hakim Rahman', true); // 3. the deputy dean accepts the evidence and records the decision
    await page.getByRole('link', { name: /^Offerings \(/ }).click();
    await page.getByRole('link', { name: 'Confirm readiness: Applied Data Analytics 2027/1' }).click();
    for (const label of items) await page.getByLabel(`Accept evidence for ${label}`).check();
    await page.getByLabel('Outcome').selectOption('ready');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('status')).toContainText('Readiness confirmed');
    await expect(page.getByText('Open', { exact: true }).first()).toBeVisible(); // 4. the cohort is open
  });

  test('FL-22 A visitor explores the public site', async ({ page }) => {
    await page.goto('/public'); // 1. no login; the categories are explained first
    await expect(page.getByRole('heading', { name: 'Micro-credentials at UTeM' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UMC' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'SAMC' })).toBeVisible();

    await page.getByLabel('Search', { exact: true }).first().fill('analytics'); // 2. the search opens the catalogue
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(page).toHaveURL(/q=analytics/);
    await expect(page.getByRole('link', { name: 'Applied Data Analytics 2027/1' })).toBeVisible();
    await expect(page.getByText('Open for registration').first()).toBeVisible();

    await page.getByRole('link', { name: 'Applied Data Analytics 2027/1' }).click(); // 3. modules, assessment, credential and the separate-stages notice
    await expect(page.getByRole('heading', { name: 'Modules' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Assessment' })).toBeVisible();
    await expect(page.getByText('Credential you receive')).toBeVisible();
    await expect(page.getByText('no credit value and no MQF level')).toBeVisible(); // a SAMC without Quality Verification claims nothing
    await expect(page.getByText('are separate decisions')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Register' })).toBeVisible();

    await page.getByRole('link', { name: 'How recognition works' }).click(); // 4. each stage says what it is and what it is not
    await expect(page.getByText('Each stage is a separate decision')).toBeVisible();
    await page.getByRole('link', { name: 'Pathways to a degree' }).click(); // 5. pathways: none approved yet, no automatic degree
    await expect(page.getByText('No micro-credential leads to a degree automatically')).toBeVisible();

    await page.getByRole('button', { name: 'BM' }).click(); // the same site in Bahasa Melayu (the header switch)
    await expect(page.getByRole('link', { name: 'Katalog' })).toBeVisible();
    await page.getByRole('link', { name: 'Katalog' }).click();
    await expect(page.getByText('Dibuka untuk pendaftaran').first()).toBeVisible();
  });
});

test.describe('Registration and payment', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoOfferingsSeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });

  test('FL-06 Find, register and pay', async ({ page }) => {
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Find a micro-credential' }).first().click(); // 1. the offering page
    await page.getByRole('link', { name: 'Cloud Native Development 2027/1' }).click();
    await expect(page.getByText('no credit value and no MQF level')).toBeVisible(); // credit is not claimed
    await page.getByRole('link', { name: 'Register' }).click();

    await expect(page.getByRole('heading', { name: 'Step 1: eligibility' })).toBeVisible(); // 2. eligibility and the phone number
    await expect(page.getByText('The checks pass')).toBeVisible();
    await page.getByLabel('Confirm your phone number').fill('012-3456789');
    await page.getByLabel(/I meet the prerequisites/).check();
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByRole('heading', { name: /Step 2/ })).toBeVisible(); // 3. the wording and the personal data notice
    await expect(page.getByText('Standalone Micro-Credential (SAMC)')).toBeVisible();
    await page.getByRole('button', { name: 'Continue to payment' }).click();
    await expect(page.getByText('Tick that you have read the recognition statement.')).toBeVisible();
    await page.getByLabel('I have read the recognition statement.').check();
    await page.getByLabel('I have read the personal data notice.').check();
    await page.getByRole('button', { name: 'Continue to payment' }).click();

    await expect(page.getByRole('heading', { name: 'Step 3: payment' })).toBeVisible(); // 4. pay with the dummy card
    await expect(page.getByText('pending payment of RM 150.00')).toBeVisible();
    await page.getByRole('button', { name: 'Pay', exact: true }).click();
    await page.getByRole('button', { name: 'Pay now' }).click();

    await expect(page.getByRole('heading', { name: 'Step 4: confirmation' })).toBeVisible(); // 5. confirmation with the enrolment ID
    await expect(page.getByText(/Enrolment ID E-\d{4}-\d{4}/)).toBeVisible();
    await expect(page.getByText('Open the learning platform course')).toBeVisible();

    await page.getByRole('link', { name: 'Registrations and payments' }).first().click(); // 6. listed as Enrolled with its receipt
    await expect(page.getByRole('row', { name: /Cloud Native Development 2027\/1.*RM 150.00.*Enrolled.*Receipt/ })).toBeVisible();
    await page.getByRole('link', { name: 'Receipt' }).click();
    await expect(page.getByText('Dummy card ending 4242')).toBeVisible();
  });

  test('FL-06b Payment failure and retry', async ({ page }) => {
    await signIn(page, 'Amir Hakimi');
    await page.goto('/public/catalogue?q=lean');
    await page.getByRole('link', { name: 'Lean Six Sigma Yellow Belt 2026/12' }).click();
    await page.getByRole('link', { name: 'Register' }).click();
    await page.getByLabel('Confirm your phone number').fill('012-3456789');
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByLabel('I have read the recognition statement.').check();
    await page.getByRole('button', { name: 'Continue to payment' }).click(); // the personal data notice was acknowledged in FL-06

    await page.getByLabel('Card number').fill('4000 0000 0000 0002'); // 1. the declined card
    await page.getByRole('button', { name: 'Pay', exact: true }).click();
    await page.getByRole('button', { name: 'Pay now' }).click();

    const block = page.locator('#payment-block'); // 2. the failure shows in the payment block, with a reference
    await expect(block).toContainText('Payment declined. Reference PAY-');
    await expect(block).toContainText('nothing was charged');
    await page.getByLabel('Card number').fill('4242 4242 4242 4242');
    await page.getByRole('button', { name: 'Retry payment' }).click();
    await page.getByRole('button', { name: 'Pay now' }).click();

    await expect(page.getByRole('heading', { name: 'Step 4: confirmation' })).toBeVisible(); // 3. enrolled after the retry, one payment
    await page.getByRole('link', { name: 'Registrations and payments' }).first().click();
    await expect(page.getByRole('row', { name: /Lean Six Sigma Yellow Belt 2026\/12.*Enrolled/ })).toBeVisible();
    await expect(page.getByRole('row', { name: /Lean Six Sigma.*Pending payment/ })).toHaveCount(0);
  });
});

test.describe('Results', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoResultsSeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });

  test('FL-07 Import UCreds results with bad rows, FL-08 verify them', async ({ page }) => {
    // FL-07 step 1: CODL checks the file, stages the good rows
    await signIn(page, 'Farid Hashim', true);
    await page.getByRole('link', { name: 'UCreds exchange' }).click();
    const offeringId = await page.locator('#offering_id option', { hasText: 'Data Structures 2026/2' }).getAttribute('value');
    const exportCsv = await (await page.request.get(`/admin/ucreds/export/${offeringId}`)).text(); // the template the learning platform is loaded with
    const learners = exportCsv.trim().split('\r\n').slice(1).map((l) => l.split(','));
    expect(learners).toHaveLength(4);
    const ref = learners[0][3];
    const header = 'learner_id,enrolment_id,offering,progress_percent,modules_completed,module1_mark,module2_mark,project_mark';
    const rows = learners.map(([matric, , enrolment], i) => {
      const marks = [['70', '65', '80'], ['72', '60', '75'], ['60', '60', ''], ['101', '60', '70']][i]; // the third has no project mark, the fourth a mark above the maximum
      return [matric, enrolment, ref, '90', '2', ...marks].join(',');
    });
    await page.locator('#offering_id').selectOption(offeringId!);
    await page.getByLabel('CSV file').setInputFiles({ name: 'results-DS-2026-2.csv', mimeType: 'text/csv', buffer: Buffer.from([header, ...rows].join('\r\n') + '\r\n') });
    await page.getByRole('button', { name: 'Check file' }).click();
    await expect(page.getByText('2 rows ready')).toBeVisible();
    await expect(page.getByText('2 rejected')).toBeVisible();
    await expect(page.getByText('project_mark is empty')).toBeVisible();
    await expect(page.getByText('module1_mark is out of range (0 to 100)')).toBeVisible();
    await page.getByRole('button', { name: 'Stage 2 rows' }).click();
    await expect(page.getByRole('status')).toContainText('Nothing becomes an achievement until the deputy dean verifies');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // FL-07 step 2: the lecturer is told, enters the missing marks and submits
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: 'Notifications' }).click();
    await expect(page.getByText('2 rows were rejected')).toBeVisible();
    await page.goto(`/offerings/${offeringId}/results`);
    await page.getByRole('button', { name: 'Submit for verification' }).click();
    await expect(page.getByText('Some marks are missing')).toBeVisible();
    const fill = async (name: string, key: string, v: string) => page.getByLabel(`${key} for ${name}`).fill(v);
    for (const [name, marks] of [['Cee Lim', ['60', '60', '55']], ['Dan Ong', ['45', '60', '70']]] as const) {
      await fill(name, 'module1_mark', marks[0]); await fill(name, 'module2_mark', marks[1]); await fill(name, 'project_mark', marks[2]);
    }
    await page.getByRole('button', { name: 'Submit for verification' }).click();
    await expect(page.getByRole('status')).toContainText('Submitted for verification');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // FL-07 step 3 and FL-08 step 1: only the deputy dean verifies; this creates module badges only
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: 'Results and completion' }).click();
    await page.getByRole('link', { name: 'Data Structures 2026/2' }).click();
    await expect(page.getByRole('heading', { name: 'Summary' })).toBeVisible();
    await page.getByLabel('Evidence linking identity, assessment and result').fill('UCreds proctoring log and photo ID check');
    await page.getByRole('button', { name: 'Verify 12 results' }).click();
    await expect(page.getByRole('status')).toContainText('Verified 12 results');
    await expect(page.getByRole('status')).toContainText('Module badges created: 7');
    await expect(page.getByRole('status')).toContainText('Nothing is issued yet');

    // FL-08 step 2: a task appeared for completion; one learner is below the pass mark and is not confirmed
    await page.getByRole('link', { name: 'Completion', exact: true }).click();
    await expect(page.getByText('All requirements met')).toHaveCount(3);
    await expect(page.getByText('Requirement missing, not issued')).toHaveCount(1);
    await expect(page.getByText(/Module 1 not achieved: Module 1 test below the pass mark \(45 of 50 needed\)/)).toBeVisible();
    await page.getByRole('button', { name: 'Confirm completion' }).click();
    await expect(page.getByRole('status')).toContainText('Completion confirmed for 3 participants');
    await expect(page.getByRole('status')).toContainText('Credentials issued: 3');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // the learner sees verified results and the badges
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'My results' }).click();
    await expect(page.getByText('70 / 100')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Module badges' }).first()).toBeVisible();
    await page.getByRole('link', { name: 'Achievement record' }).click(); // the record says what it is and what it is not
    await expect(page.getByText('Internal record, not an Academic Credit Record').first()).toBeVisible();
    await expect(page.getByText('Completion: Complete')).toBeVisible();
    await expect(page.getByText('Final result: 72.5%')).toBeVisible();
  });
});

// The credentials issued in the Results flow (Amir, Bee and Cee completed) are shared, corrected, replaced, revoked and verified.
test.describe('Credentials', () => {
  const nav = (page: Page, name: string) => page.getByRole('navigation', { name: 'Main' }).getByRole('link', { name: new RegExp('^' + name + '( \\d+)?$') });
  const codeOf = async (page: Page, who: string) => { // from the deputy dean's list
    await nav(page, 'Credentials').click();
    await page.getByRole('row', { name: new RegExp(who) }).getByRole('link').click();
    return (await page.locator('p code').first().innerText()).trim();
  };

  test('FL-09 Share a credential and have it verified', async ({ page }) => {
    await signIn(page, 'Amir Hakimi'); // 1. the share link and what an employer will see
    await nav(page, 'Credentials').click();
    await page.getByRole('link', { name: 'Data Structures' }).click();
    await expect(page.getByText('shows an employer only your name')).toBeVisible();
    await expect(page.locator('svg').first()).toBeVisible(); // the QR code
    const share = await page.getByLabel('Share link').inputValue();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await page.goto(share); // 2. an employer opens the link: minimum fields, no sign-in
    await expect(page.locator('#vstatus')).toHaveText('Valid');
    await expect(page.getByRole('cell', { name: 'Amir Hakimi' })).toBeVisible();
    await expect(page.getByText('B032410001')).toHaveCount(0);
    await expect(page.getByText('70 /')).toHaveCount(0);

    await signIn(page, 'Hakim Rahman', true); // 3. a revoked credential says Revoked and shows no result
    const cee = await codeOf(page, 'Cee Lim');
    await page.getByLabel('Reason', { exact: true }).last().fill('Found to be issued in error after an integrity case');
    await page.getByRole('button', { name: 'Revoke the credential' }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Revoke the credential' }).click(); // the confirmation dialog
    await expect(page.getByRole('status')).toContainText('Revoked');
    await page.goto(`/public/verify/${cee}`);
    await expect(page.locator('#vstatus')).toHaveText('Revoked');
    await expect(page.getByText('must not be relied on')).toBeVisible();
    await expect(page.getByText('Cee Lim')).toHaveCount(0);

    await page.goto('/public/verify/ZZZZZ-00000'); // 4. a code that does not exist
    await expect(page.getByText('We could not find this credential')).toBeVisible();
  });

  test('FL-10 Correct a credential name, FL-23 an employer verifies', async ({ page }) => {
    await signIn(page, 'Hakim Rahman', true); // FL-10: start the correction
    const bee = await codeOf(page, 'Bee Tan');
    await page.getByLabel('Name as it should read').fill('Bee Tan Mei Ling');
    await page.getByLabel('Reason', { exact: true }).first().fill('The full name must be shown');
    await page.getByRole('button', { name: 'Correct the credential' }).click();
    await expect(page.getByRole('status')).toContainText('Corrected');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await page.goto('/public/verify'); // FL-23 step 1: the employer types the code, no sign-in
    await page.getByLabel(/Credential code/).fill(bee.toLowerCase());
    await page.getByRole('button', { name: 'Verify', exact: true }).click();
    await expect(page.locator('#vstatus')).toHaveText('Valid, corrected'); // step 3: corrected stays valid and shows the correction
    await expect(page.getByRole('cell', { name: 'Bee Tan Mei Ling' })).toBeVisible();

    await signIn(page, 'Hakim Rahman', true); // step 4: replace it as the deputy dean
    await nav(page, 'Credentials').click();
    await page.getByRole('row', { name: /Bee Tan Mei Ling/ }).getByRole('link').click();
    await page.getByLabel('Reason', { exact: true }).nth(1).fill('Re-issued with the corrected name on the certificate');
    await page.getByRole('button', { name: 'Replace the credential' }).click();
    await expect(page.getByRole('status')).toContainText('Replaced');
    const fresh = (await page.locator('p code').first().innerText()).trim();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await page.goto(`/public/verify/${bee}`); // step 5: the old code shows Replaced and links to the new credential
    await expect(page.locator('#vstatus')).toHaveText('Replaced');
    await page.getByRole('link', { name: fresh }).click();
    await expect(page.locator('#vstatus')).toHaveText('Valid');
  });
});

test.describe('Quality Verification', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoQualitySeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });
  const iso = (days: number) => new Date(Date.now() + days * 86400000).toISOString().slice(0, 10);

  test('FL-14 Prepare, submit and record Quality Verification of a SAMC', async ({ page }) => {
    // Steps 3 and 4: the list shows the alert, and a SAMC without a completed cohort cannot be Ready to submit
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: /^(QV evidence|Quality Verification)$/ }).click();
    await expect(page.getByRole('row', { name: /Cloud Native Development.*Quality-Verified.*41 days left/ })).toBeVisible();
    await page.getByRole('link', { name: 'Lean Six Sigma Yellow Belt' }).click();
    await expect(page.locator('#conditions')).toContainText('Not met: Completed cohorts with verified evidence: 0');
    await page.getByRole('button', { name: 'Start a Quality Verification cycle' }).click();
    await page.locator('#decision').selectOption('ready');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByText('Waiting for the deputy dean to confirm')).toBeVisible();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^(QV evidence|Quality Verification)$/ }).click();
    await page.getByRole('link', { name: 'Lean Six Sigma Yellow Belt' }).click();
    await page.getByRole('button', { name: 'Confirm readiness decision' }).click();
    await expect(page.getByRole('alert')).toContainText('Not ready to submit');
    await expect(page.locator('#submit-off')).toContainText('Submission needs the status Ready to submit');
    await expect(page.getByRole('button', { name: 'Record submission' })).toBeDisabled();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 1: the lecturer keeps evidence for the SAMC that has a completed cohort
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: /^(QV evidence|Quality Verification)$/ }).click();
    await page.getByRole('link', { name: 'Cloud Native Development' }).click();
    await page.getByRole('button', { name: 'Start a Quality Verification cycle' }).click();
    await expect(page.locator('#evidence')).toContainText('Available (linked from MCMS)');
    await page.locator('#st-quality_cqi').selectOption('available');
    await page.getByLabel('File reference for Quality assurance and CQI: feedback and improvement records (D6)').fill('cqi-2026-1.pdf');
    await page.getByRole('button', { name: 'Save checklist' }).click();
    await expect(page.getByRole('status')).toContainText('Evidence checklist saved');
    await page.locator('#decision').selectOption('ready');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 2: the deputy dean confirms, submits, and records the result; a validity above 3 years is refused
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^(QV evidence|Quality Verification)$/ }).click();
    await page.getByRole('link', { name: 'Cloud Native Development' }).click();
    await page.getByRole('button', { name: 'Confirm readiness decision' }).click();
    await expect(page.locator('#cycle')).toContainText('Ready to submit');
    await page.getByLabel('Date submitted').fill(iso(0));
    await page.getByLabel('QVC/MQA reference').fill('MQA/QV/2026/312');
    await page.getByRole('button', { name: 'Record submission' }).click();
    await expect(page.locator('#cycle')).toContainText('Submitted');
    await page.getByLabel('QV reference').fill('QV/2026/312');
    await page.getByLabel('Verified credit').fill('2');
    await page.getByLabel('MQF level').fill('4');
    await page.getByLabel('Valid from').fill(iso(0));
    await page.getByLabel('Valid until').fill(iso(365 * 3 + 40));
    await page.getByLabel('Decision document').fill('qv-2026-312.pdf');
    await page.getByRole('button', { name: 'Record result' }).click();
    await expect(page.getByRole('alert')).toContainText('at most 3 years');
    await page.getByLabel('QV reference').fill('QV/2026/312');
    await page.getByLabel('Verified credit').fill('2');
    await page.getByLabel('MQF level').fill('4');
    await page.getByLabel('Valid from').fill(iso(0));
    await page.getByLabel('Valid until').fill(iso(365 * 3 - 5));
    await page.getByLabel('Decision document').fill('qv-2026-312.pdf');
    await page.getByRole('button', { name: 'Record result' }).click();
    await expect(page.getByRole('status')).toContainText('Result recorded');
    await expect(page.locator('#in-force')).toContainText('QV/2026/312');
    await expect(page.locator('#history')).toContainText('Superseded');
  });
});

test.describe('Credit bank', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoCreditBankSeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });

  test('FL-11 Confirm the ACR, consent, record the ABC deposit, see the ledger', async ({ page }) => {
    // Step 1: the deputy dean reviews the generated record and confirms it; nothing on it can be edited
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: 'ACR and ABC deposits' }).click();
    await page.getByRole('row', { name: /Data Structures.*Amir Hakimi/ }).getByRole('link', { name: 'Review' }).click();
    await expect(page.locator('#acr')).toContainText('Amir Hakimi');
    await expect(page.getByText('cannot be edited')).toBeVisible();
    await expect(page.locator('#checks')).toContainText('Passed');
    await page.getByRole('button', { name: 'Confirm Academic Credit Record' }).click();
    await expect(page.getByRole('status')).toContainText('The participant is asked for consent');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 2: the participant gives explicit consent
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Credit ledger and ABC' }).click();
    await expect(page.getByRole('cell', { name: /Confirmed, waiting for your consent/ })).toBeVisible();
    await page.getByRole('link', { name: 'Data Structures' }).click();
    await expect(page.locator('#consent')).toContainText('It is not a credit transfer');
    await page.getByLabel('I consent to the deposit').check();
    await page.getByRole('button', { name: 'Save my choice' }).click();
    await expect(page.getByRole('status')).toContainText('Consent recorded');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 3: the deputy dean finds it in the queue and records the deposit
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: 'ACR and ABC deposits' }).click();
    await page.getByRole('button', { name: /^Deposit queue/ }).click();
    await page.getByRole('row', { name: /Data Structures.*Amir Hakimi/ }).getByRole('link', { name: 'Record deposit' }).click();
    await page.getByLabel('ABC reference').fill('ABC-2026-0042');
    await page.getByLabel('Deposit date').fill(new Date().toISOString().slice(0, 10));
    await page.getByRole('button', { name: 'Record deposit' }).click();
    await expect(page.getByRole('status')).toContainText('This is storage, not a credit transfer');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 4: the ledger shows the credit
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Credit ledger and ABC' }).click();
    await expect(page.getByRole('row', { name: /Data Structures.*Available/ })).toBeVisible();
    await expect(page.locator('#total')).toHaveText('3');
    await expect(page.getByText('not automatically accepted by any programme')).toBeVisible();
  });
});

test.describe('Credit transfer', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoTransferSeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });
  const evaluate = async (page: Page, matches: string[]) => {
    const form = page.locator('.line form').first();
    const n = await form.getByLabel(/^Match for:/).count();
    for (let i = 0; i < n; i++) {
      const m = matches[i] ?? 'full'; // outcomes not named here match fully
      await form.getByLabel(/^Outcome meeting:/).nth(i).selectOption({ index: m === 'none' ? 0 : 1 });
      await form.getByLabel(/^Match for:/).nth(i).selectOption(m);
    }
    for (const label of ['Credit value and learning time', 'MQF level', 'Content and depth', 'Learning activities', 'Assessment and standard', 'Accreditation or Quality Verification status', 'Currency of knowledge', 'Programme or professional body requirements']) {
      await form.getByLabel(`Finding for ${label}`).fill('Checked against the course');
      await form.getByLabel(`Result for ${label}`).selectOption('equivalent');
    }
    await form.getByRole('button', { name: 'Save evaluation' }).click();
  };
  const validity = async (page: Page) => {
    for (const r of await page.locator('#validity').getByRole('radio', { name: 'Passed' }).all()) await r.check();
    await page.getByRole('button', { name: 'Record validity check' }).click();
  };

  test('FL-12 Apply, decide by outcome, credit held, second use and an unmatched outcome blocked', async ({ page }) => {
    // Step 1: Amir applies with his deposited credit
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Credit transfer', exact: true }).click();
    await page.getByRole('link', { name: 'New transfer application' }).click();
    await page.getByLabel(/^Use Data Structures/).check();
    await page.getByRole('button', { name: 'Submit application' }).click();
    await expect(page.getByRole('status')).toContainText('Applying does not mean the credit is accepted');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 2: the deputy dean checks validity, compares outcomes one by one and approves
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Credit transfer( evaluation)?( \d+)?$/ }).click();
    await page.getByRole('row', { name: /Amir Hakimi/ }).getByRole('link', { name: 'Open' }).click();
    await validity(page);
    await evaluate(page, ['full', 'full', 'full']);
    await expect(page.locator('.line-lo')).toContainText('Equivalent');
    await page.locator('#decision_ref').fill('FTMK 4/2026');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('status')).toContainText('provisionally redeemed');
    await expect(page.locator('#app-status')).toHaveText('Approved');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 3: the credit is held and a second use is blocked with the reason
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Credit transfer', exact: true }).click();
    await page.getByRole('link', { name: 'New transfer application' }).click();
    await expect(page.getByText('provisionally redeemed for a programme')).toBeVisible();
    await expect(page.getByLabel(/^Use Data Structures/)).toBeDisabled();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Steps 4 and 5: a case with an unmatched outcome cannot be approved, and the refusal leaves the credit alone
    await signIn(page, 'Bee Tan');
    await page.getByRole('link', { name: 'Credit transfer', exact: true }).click();
    await page.getByRole('link', { name: 'New transfer application' }).click();
    await page.getByLabel(/^Use Data Structures/).check();
    await page.getByRole('button', { name: 'Submit application' }).click();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Credit transfer( evaluation)?( \d+)?$/ }).click();
    await page.getByRole('row', { name: /Bee Tan.*Submitted/ }).getByRole('link', { name: 'Open' }).click();
    await validity(page);
    await evaluate(page, ['full', 'full', 'none']);
    await expect(page.locator('.line-lo')).toContainText('Approved is blocked');
    await page.locator('#decision_ref').fill('FTMK 5/2026');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('alert')).toContainText('not matched by the micro-credential');
    await page.locator('#outcome').selectOption('not_approved');
    await page.getByLabel(/^Credits approved for/).fill('0');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.locator('#app-status')).toHaveText('Not approved');
    await expect(page.getByRole('status')).toContainText('The credit and the records are unchanged');
  });
});

test.describe('Stackable pathways', () => {
  test.beforeAll(() => {
    execSync('php artisan db:seed --class=DemoPathwaySeeder --force --no-interaction', { stdio: 'inherit', env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' } });
  });

  test('FL-13 A plan below the home-credit share is Not yet suitable, a suitable plan needs admission', async ({ page }) => {
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: 'Stackable pathways' }).click();
    await expect(page.getByRole('row', { name: /PW-BITS-1.*Published/ })).toBeVisible();

    // Step 1: all three courses come from outside, so 25% would be earned at UTeM, below the 30% rule
    await page.getByRole('link', { name: 'New individual plan' }).click();
    await page.getByLabel('Participant').selectOption({ label: 'Amir Hakimi' });
    for (const box of await page.getByRole('checkbox').all()) await box.check();
    await page.getByLabel('Requirement 1').fill('Final year project');
    await page.getByRole('button', { name: 'Create plan' }).click();
    await expect(page.locator('#figures')).toContainText('25%');
    await expect(page.locator('#checks')).toContainText('Not met: 25% of the 12 graduating credits');
    await expect(page.getByText('does not mean the rest can be transferred automatically').first()).toBeVisible();
    await page.locator('#decision-sel').selectOption('suitable');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('alert')).toContainText('Suitable to proceed needs the programme admission');
    await page.locator('#decision-sel').selectOption('not_yet');
    await page.getByLabel('Conditions and follow-up').fill('Earn more of the credit at UTeM');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.locator('#plan-status')).toHaveText('Not yet suitable');

    // Step 2: two courses from outside, 50% at UTeM; the plan is updated and still needs the admission to be Suitable
    await page.getByRole('checkbox', { name: /BITS 1125/ }).uncheck();
    await page.getByRole('button', { name: 'Update plan' }).click();
    await expect(page.locator('#figures')).toContainText('50%');
    await expect(page.locator('#plan-status')).toHaveText('Needs review');
    await page.locator('#decision-sel').selectOption('suitable');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('alert')).toContainText('admission');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 3: the participant reads what the plan does and does not mean
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'My degree plan' }).click();
    await expect(page.locator('.plan').first()).toContainText('50%');
    await expect(page.getByText('No number of micro-credentials or badges leads to a degree automatically')).toBeVisible();

    // Step 4: the public page shows the pathway and says it transfers no credit
    await page.goto('/public/pathways');
    await expect(page.locator('.pathway')).toContainText('Bachelor of Computer Science');
    await expect(page.locator('#pathway-notes')).toContainText('transfers no credit');
  });
});

test.describe('Quality review', () => {
  test('FL-15 Close an action only after the effectiveness check, then submit to the deputy dean', async ({ page }) => {
    // Step 1: the lecturer opens the quality review of the Data Structures cohort and records the domains
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: /^Quality review( \(CQI\))?$/ }).click();
    await expect(page.locator('.cohort').filter({ hasText: 'Data Structures 2026/2' })).toContainText('Completion rate');
    await page.getByRole('button', { name: /Start a quality review of Data Structures 2026\/2/ }).click();
    for (const [i, d] of ['Content currency', 'Delivery', 'Assessment and integrity', 'Platform and access', 'Participant support', 'Learning outcome attainment'].entries()) {
      await page.getByLabel(`Status of ${d}`).selectOption(i === 2 ? '0' : '1');
      await page.getByLabel(`Evidence for ${d}`).fill(`Evidence for ${d}`);
    }
    await page.getByRole('button', { name: 'Save domains' }).click();
    await expect(page.getByRole('status')).toContainText('Quality domains saved');

    // Step 2: an issue and an action, worked through to Done
    await page.getByLabel('Evidence', { exact: true }).fill('Two participants missed the project mark');
    await page.getByLabel('Root cause').fill('The project brief was unclear');
    await page.getByLabel('Impact').fill('Two results were held');
    await page.getByLabel('Improvement action').fill('Rewrite the project brief');
    await page.getByLabel('Due date').fill(new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10));
    await page.getByLabel('Success indicator').fill('No held project marks next cohort');
    await page.getByRole('button', { name: 'Record', exact: true }).click();
    await page.getByRole('button', { name: /^Start: Rewrite/ }).click();
    await page.getByRole('button', { name: /^Mark done: Rewrite/ }).click();
    await expect(page.locator('.action')).toContainText('Done (effectiveness pending)');
    await page.getByRole('button', { name: 'Submit to the deputy dean' }).click();
    await expect(page.locator('#cqi-status')).toHaveText('Submitted to the deputy dean');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 3: the deputy dean cannot close the action before it is evaluated
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Quality review( \(CQI\))?$/ }).click();
    await page.getByRole('link', { name: /CQI-/ }).first().click();
    await page.getByRole('button', { name: /^Close: Rewrite/ }).click();
    await expect(page.getByRole('alert')).toContainText('only after its effectiveness is evaluated');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 4: the lecturer evaluates, the deputy dean closes the action and confirms the record
    await signIn(page, 'Dr. Aina Zulkifli');
    await page.getByRole('link', { name: /^Quality review( \(CQI\))?$/ }).click();
    await page.getByRole('link', { name: /CQI-/ }).first().click();
    await page.getByLabel(/^Before evidence/).fill('Two marks held');
    await page.getByLabel(/^After evidence/).fill('No marks held in the trial');
    await page.getByRole('button', { name: /^Evaluate: Rewrite/ }).click();
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Quality review( \(CQI\))?$/ }).click();
    await page.getByRole('link', { name: /CQI-/ }).first().click();
    await page.getByRole('button', { name: /^Close: Rewrite/ }).click();
    await expect(page.locator('.action')).toContainText('Closed');
    for (const box of await page.locator('#confirm').getByRole('checkbox').all()) await box.check();
    await page.getByRole('button', { name: 'Confirm', exact: true }).click();
    await expect(page.locator('#cqi-status')).toHaveText('Confirmed');
  });

  test('FL-16 A periodic review leads to a suspension with a transition plan; earned credentials stay valid', async ({ page }) => {
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Quality review( \(CQI\))?$/ }).click();
    // Step 1: the review outcome is "consider suspension"
    const row = page.locator('#periodic tbody tr').filter({ hasText: 'Lean Six Sigma Yellow Belt' });
    await row.getByLabel(/^Outcome for/).selectOption('suspension');
    await row.getByRole('button', { name: /^Record review outcome/ }).click();
    await expect(page.getByRole('status')).toContainText('Consider suspension or termination');

    // Step 2: the deputy dean sees the active participants and records the decision with a transition plan
    await page.getByRole('link', { name: 'Suspension or termination' }).click();
    await page.getByLabel('Micro-credential').selectOption({ label: 'Lean Six Sigma Yellow Belt' });
    await page.getByRole('button', { name: 'Show active participants' }).click();
    await expect(page.locator('#participants')).toContainText('Amir Hakimi');
    await page.getByLabel('Decision', { exact: true }).selectOption('suspension');
    await page.getByLabel('Reason').fill('Facilitator unavailable this term');
    await page.getByLabel('Transition plan', { exact: true }).selectOption('completion_window');
    await page.getByLabel(/^Plan in words/).fill('Participants finish within six months of the new start date');
    await page.getByRole('button', { name: 'Record decision' }).click();
    await expect(page.getByRole('status')).toContainText('Credentials and credit already earned stay valid');
    await expect(page.locator('#decisions')).toContainText('Lean Six Sigma Yellow Belt');
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Step 3: the participant is told, and the earned credential is still valid
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Notifications' }).click();
    await expect(page.getByText('Participants finish within six months')).toBeVisible();
    await page.getByRole('link', { name: 'Credentials', exact: true }).click();
    await expect(page.locator('main').getByText('Issued').first()).toBeVisible();
  });
});

test.describe('Records and documents', () => {
  test('FL-18 Trace a participant from approval to credit use, then read the audit rows', async ({ page }) => {
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: /^Traceability( and records)?$/ }).click();
    await page.getByLabel('Find a participant by name or matric number').fill('Amir');
    await page.getByRole('button', { name: 'Search' }).click();
    await page.getByRole('row', { name: /Amir Hakimi.*Data Structures/ }).getByRole('link', { name: 'Trace' }).click();

    // Step 1: every stage from development to the credit
    for (const stage of ['development', 'approval', 'offering', 'learning', 'assessment', 'achievement', 'credential', 'acr']) {
      await expect(page.locator(`#chain li[data-stage="${stage}"]`)).toHaveClass(/present/);
    }
    await expect(page.locator('#chain li[data-stage="acr"]')).toContainText('ABC reference');
    await expect(page.locator('#chain li[data-stage="transfer"]')).toContainText('TR-');

    // Step 2: the audit rows of the same records
    await expect(page.locator('#trace-audit')).toContainText('credential.issued');
    await expect(page.locator('#trace-audit')).toContainText('abc.deposit_recorded');
    await expect(page.locator('#trace-audit')).toContainText('transfer.decided');

    // Step 3: the minimum records of the cohort
    await page.getByRole('link', { name: 'Minimum records of this cohort' }).click();
    await expect(page.getByRole('row', { name: /Credentials issued.*Yes/ })).toBeVisible();
    await expect(page.getByRole('row', { name: /Quality review \(CQI\) record/ })).toBeVisible();
  });

  test('FL-24 Open the printable documents', async ({ page }) => {
    // Steps 1 to 3: the participant's receipt, certificate and Digital Credential
    await signIn(page, 'Amir Hakimi');
    await page.getByRole('link', { name: 'Documents', exact: true }).click();
    await page.getByRole('link', { name: /^Receipt for/ }).first().click();
    await expect(page.getByRole('heading', { name: 'Payment receipt' })).toBeVisible();
    await page.goBack();
    await page.getByRole('link', { name: 'Certificate' }).first().click();
    await expect(page.getByRole('button', { name: 'Print' })).toBeVisible();
    await page.goBack();
    await expect(page.getByRole('link', { name: 'Digital Credential (JSON)' }).first()).toHaveAttribute('href', /download\/json/);
    await page.locator('.who-btn').click();
    await page.getByRole('button', { name: 'Sign out' }).click();

    // Steps 4 to 6: the deputy dean prints a full report, saves it as CSV and prints the audit export with its integrity line
    await signIn(page, 'Hakim Rahman', true);
    await page.getByRole('link', { name: 'Documents', exact: true }).click();
    await page.locator('#report-documents tr').filter({ hasText: 'Credentials issued, corrected and revoked' }).getByRole('link', { name: 'Print' }).click();
    await expect(page.getByText('Generated by MCMS').first()).toBeVisible();
    await expect(page.locator('#report')).toContainText('MCMS-C-');
    await expect(page.locator('#report')).not.toContainText('Amir Hakimi');
    const csv = await page.request.get('/records/reports/credentials/csv');
    expect(csv.headers()['content-type']).toContain('text/csv');
    await page.goto('/documents');
    await page.getByRole('link', { name: 'Audit trail export' }).click();
    await expect(page.locator('#integrity')).toContainText('is intact');
    await expect(page.locator('#audit')).toContainText('credential.issued');
  });
});
