// Walks the hand-offs between roles through the real UI and checks each one lands on the next role's screen.
// Usage: node UIUX/v2/tools/scenario.js   (server on http://localhost:8731)
const path = require('path');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const BASE = 'http://localhost:8731/';

(async () => {
  const browser = await chromium.launch({ channel: 'msedge' });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const errs = []; page.on('pageerror', e => errs.push(e.message));
  await page.goto(BASE); await page.evaluate(() => sessionStorage.clear()); await page.goto(BASE + '#/');
  const go = async h => { await page.evaluate(x => { location.hash = x; }, h); await page.waitForTimeout(150); };
  // click the first visible control whose text matches
  const click = async re => {
    const ok = await page.evaluate(src => {
      const re = new RegExp(src, 'i');
      const el = [...document.querySelectorAll('[data-act],[data-modal],[data-go],[data-mok],.btn,a')].find(e => e.offsetParent && re.test(e.textContent.trim()) && !e.classList.contains('dis'));
      if (el) el.click(); return !!el;
    }, re.source);
    await page.waitForTimeout(200); return ok;
  };
  // fill every empty required field in an open dialog (or the page form), pick the first option of radio groups, confirm
  const confirm = async (pick, onPage) => {
    await page.evaluate(([pick, onPage]) => {
      const box = onPage ? document.getElementById('app') : (document.querySelector('.scrim') || document);
      box.querySelectorAll('textarea[required],input[required]:not([type=radio]):not([type=checkbox])').forEach(i => { if (!i.value) i.value = /date|eff|when/i.test(i.name) ? '26 Oct 2026' : 'Prototype test entry'; });
      const groups = new Set([...box.querySelectorAll('input[type=radio]')].map(r => r.name));
      groups.forEach(n => { const want = pick && box.querySelector(`input[name="${n}"][value="${pick}"]`); if (!box.querySelector(`input[name="${n}"]:checked`) || want) (want || box.querySelector(`input[name="${n}"]`)).checked = true; });
      box.querySelectorAll('input[type=checkbox][required]').forEach(c => { c.checked = true; });
    }, [pick, onPage]);
    if (onPage) return true;
    const mok = await page.$('.scrim [data-mok]'); if (mok) { await mok.click(); await page.waitForTimeout(250); }
    return !!mok;
  };
  const text = () => page.evaluate(() => document.getElementById('app').innerText);
  const state = f => page.evaluate(f);
  const results = [];
  const check = (name, cond, extra = '') => results.push(`${cond ? 'ok  ' : 'FAIL'} ${name}${cond ? '' : ' — ' + extra}`);

  // A. lecturer completes missing marks and submits → deputy dean sees the results to verify
  await go('#/lec/cohort/CND-2026-2');
  await page.evaluate(() => document.querySelectorAll('#app input.input').forEach(i => { if (!i.value && !i.closest('.tbar')) { i.value = '72'; i.dispatchEvent(new Event('input', { bubbles: true })); i.dispatchEvent(new Event('change', { bubbles: true })); } }));
  await page.waitForTimeout(200);
  await click(/send for verification|submit for verification/); await confirm();
  check('A1 lecturer submits CND-2026-2', await state(() => MC.co('CND-2026-2').results === 'Submitted'), await state(() => MC.co('CND-2026-2').results));
  await go('#/dd/verify/CND-2026-2');
  check('A2 deputy dean sees submitted learners', !/No learner results/i.test(await text()) && /Daniel Ong/.test(await text()));

  // B. readiness: lecturer uploads the missing record and sends; deputy dean accepts and confirms Ready → public catalogue
  await go('#/lec/readiness/DS-2027-1');
  await click(/^upload a record$/); await click(/send to deputy dean/); await confirm();
  check('B1 checklist sent', await state(() => !!(MC.state.lecRead && MC.state.lecRead['DS-2027-1'] && MC.state.lecRead['DS-2027-1'].sent)));
  await go('#/dd/readiness/DS-2027-1');
  await click(/accept evidence/); await confirm();
  await click(/record (the )?decision|confirm ready|record readiness/); await confirm('Ready');
  check('B2 cohort open after readiness', await state(() => MC.co('DS-2027-1').status === 'Open'), await state(() => MC.co('DS-2027-1').status));
  await go('#/public/catalogue');
  check('B3 public catalogue lists Data Structures 2027/1', /Data Structures/.test(await text()));

  // C. consent → deposit → ledger
  await go('#/st/acr/MCMS-A-2026-000214');
  await page.evaluate(() => { const r = document.querySelector('#app input[type=radio][value=give]'); if (r) r.checked = true; });
  await click(/^continue$/); await confirm();
  check('C1 consent given', await state(() => /^Given/.test(MC.state.acrs.find(a => a.id === 'MCMS-A-2026-000214').consent || '')));
  await go('#/dd/deposit/MCMS-A-2026-000214');
  await confirm(null, true); await click(/^record deposit$/); await confirm();
  check('C2 deposit recorded', await state(() => MC.state.acrs.find(a => a.id === 'MCMS-A-2026-000214').status === 'Deposited'));
  await go('#/st/ledger');
  check('C3 ledger shows credit Available', /Available/.test(await text()));

  // D. transfer approve → provisionally redeemed for the student; outcome-gap case cannot be approved
  await go('#/dd/transfer/TR-2026-0012');
  await confirm('Approved', true); await click(/^record decision$/); await confirm('Approved');
  check('D1 transfer decided', await state(() => /Approved/.test(MC.state.transfers.find(t => t.id === 'TR-2026-0012').status)), await state(() => MC.state.transfers.find(t => t.id === 'TR-2026-0012').status));
  check('D2 credit provisionally redeemed', await state(() => /Provisionally redeemed/i.test(MC.state.acrs.find(a => a.id === 'MCMS-A-2026-000102').credit)));
  await go('#/dd/transfer/TR-2026-0009');
  check('D3 line with no outcome match cannot be approved', await state(() => !!document.querySelector('#app input[type=radio][value="Approved"][disabled], #app .btn.dis')) );

  // E. verify then confirm completion → credentials issued, enrolment completed
  await go('#/dd/verify/DS-2026-2'); await click(/^verify \d+ results?$|^verify results$/); await confirm();
  check('E1 results verified', await state(() => MC.co('DS-2026-2').results === 'Verified'));
  const before = await state(() => MC.state.credentials.length);
  await go('#/dd/completion/DS-2026-2'); await click(/confirm completion/); await confirm(); await page.waitForTimeout(6000);
  check('E2 credentials issued by the system', (await state(() => MC.state.credentials.length)) > before);
  await go('#/dd/completion/LE-2026-10'); await click(/confirm completion/); await confirm(); await page.waitForTimeout(6000);
  check('E3 Amir\'s LE enrolment completed', await state(() => MC.state.enrolments.find(e => e.id === 'E-2026-0644').status === 'Completed'), await state(() => MC.state.enrolments.find(e => e.id === 'E-2026-0644').status));

  check('no script errors', !errs.length, errs.join(' | '));
  console.log(results.join('\n'));
  await browser.close(); process.exit(results.some(r => r.startsWith('FAIL')) ? 1 : 0);
})();
