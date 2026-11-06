// Captures the prototype screens used as figures in the UI/UX Design Specification.
// Usage: node UIUX/v2/tools/specshots.js <out-dir>   (server on http://localhost:8731)
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const BASE = 'http://localhost:8731/';
const out = process.argv[2]; fs.mkdirSync(out, { recursive: true });

// [file, route, optional script run after load (opens a pop-up, menu, etc.), viewport height]
const SHOTS = [
  ['shell-dd-queue', '#/dd/queue'],
  ['menu-notif', '#/dd/queue', `document.querySelector('[data-menu=notif]').click()`, 520],
  ['menu-user', '#/dd/queue', `document.querySelector('[data-menu=user]').click()`, 520],
  ['profile', '#/profile?tab=notif'],
  ['modal-confirm', '#/dd/verify/DS-2026-2', `[...document.querySelectorAll('#app .btn')].find(b=>/^Verify/.test(b.textContent)).click()`],
  ['modal-danger', '#/dd/cred/MCMS-C-2026-000418', `const b=[...document.querySelectorAll('#app [data-modal]')].find(b=>/revoke/i.test(b.textContent)); if(b) b.click()`],
  ['validation', '#/lec/proposal/ST?tab=mods'],
  ['table-pager', '#/codl/users'],
  ['dd-proposal', '#/dd/proposal/DB'],
  ['dd-verify', '#/dd/verify/DS-2026-2'],
  ['dd-transfer', '#/dd/transfer/TR-2026-0009'],
  ['dd-readiness', '#/dd/readiness/DS-2027-1'],
  ['lec-home', '#/lec/home'],
  ['lec-review', '#/lec/review/RV-031'],
  ['lec-cohort', '#/lec/cohort/CND-2026-2'],
  ['st-home', '#/st/home'],
  ['st-register', '#/st/register/CND-2027-1?step=3'],
  ['st-ledger', '#/st/ledger'],
  ['st-acr', '#/st/acr/MCMS-A-2026-000214'],
  ['pub-home', '#/public/home'],
  ['pub-offering', '#/public/offering/CND-2027-1'],
  ['pub-verify', '#/public/verify/3HXP-92LD'],
  ['pub-revoked', '#/public/verify/R7KD-1MQA', '', 620],
  ['codl-settings', '#/codl/settings'],
  ['codl-ucreds', '#/codl/ucreds'],
  ['flowbar', '#/flows', `MC.startFlow('FL-19', 6)`],
  ['doc-certificate', '#/doc/certificate/MCMS-C-2026-000098'],
  ['doc-report', '#/doc/report/credentials-issued'],
  ['personas', '#/'],
];

(async () => {
  const browser = await chromium.launch({ channel: 'msedge' });
  for (const [file, route, script, h] of SHOTS) {
    const page = await browser.newPage({ viewport: { width: 1440, height: h || 900 } });
    await page.goto(BASE); await page.evaluate(() => sessionStorage.clear());
    await page.goto(BASE + route); await page.waitForTimeout(300);
    if (script) { await page.evaluate(script); await page.waitForTimeout(400); }
    await page.evaluate(() => { const d = document.querySelector('.devbar'); if (d) d.style.display = 'none'; document.querySelectorAll('.toasts').forEach(t => t.remove()); });
    await page.screenshot({ path: path.join(out, file + '.png') });
    await page.close(); console.log('ok', file);
  }
  await browser.close();
})();
