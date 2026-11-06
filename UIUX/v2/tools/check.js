// Checks every prototype screen whose route starts with a prefix: it draws, has no script errors,
// every data-go target exists, and every data-modal / data-drawer is defined.
// Usage: node UIUX/v2/tools/check.js [route-prefix]   (server must run on http://localhost:8731)
const path = require('path');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const prefix = process.argv[2] || '';
const BASE = 'http://localhost:8731/';

(async () => {
  const browser = await chromium.launch({ channel: 'msedge' });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const errors = [];
  page.on('pageerror', e => errors.push(`pageerror: ${e.message}`));
  page.on('console', m => { if (m.type() === 'error') errors.push(`console: ${m.text()}`); });
  await page.goto(BASE);
  await page.evaluate(() => sessionStorage.clear());
  await page.goto(BASE + '#/');
  const routes = await page.evaluate(p => MC.screens.filter(s => s.route.startsWith(p) && s.route !== '404')
    .map(s => '#/' + s.parts.map(x => x[0] === ':' ? encodeURIComponent(s.ex || 'x') : x).join('/')), prefix);
  let bad = 0;
  for (const r of routes) {
    errors.length = 0;
    await page.goto(BASE + r); await page.waitForTimeout(60);
    const res = await page.evaluate(() => {
      const app = document.getElementById('app'), txt = app.innerText;
      const gos = [...new Set([...document.querySelectorAll('[data-go]')].map(e => e.dataset.go).filter(Boolean))];
      const deadGo = gos.filter(h => { const m = MC.match(h); return !m || m.s.route === '404'; });
      const modals = [...new Set([...document.querySelectorAll('[data-modal]')].map(e => e.dataset.modal))].filter(k => k !== 'confirm' && !MC.modals[k]);
      const drawers = [...new Set([...document.querySelectorAll('[data-drawer]')].map(e => e.dataset.drawer))].filter(k => !MC.drawers[k]);
      const acts = [...new Set([...document.querySelectorAll('[data-act]')].map(e => e.dataset.act))].filter(k => !MC.acts[k]);
      const confirmNoTitle = document.querySelectorAll('[data-modal="confirm"]:not([data-title])').length;
      return { len: txt.length, failed: /failed to draw|isn't designed yet/.test(txt), deadGo, modals, drawers, acts, confirmNoTitle };
    });
    const probs = [];
    if (res.failed || res.len < 80) probs.push(`did not draw (${res.len} chars)`);
    if (res.deadGo.length) probs.push(`dead links: ${res.deadGo.join(', ')}`);
    if (res.modals.length) probs.push(`undefined modals: ${res.modals.join(', ')}`);
    if (res.drawers.length) probs.push(`undefined drawers: ${res.drawers.join(', ')}`);
    if (res.acts.length) probs.push(`undefined acts: ${res.acts.join(', ')}`);
    if (res.confirmNoTitle) probs.push(`${res.confirmNoTitle} confirm without data-title`);
    if (errors.length) probs.push(...errors);
    if (probs.length) { bad++; console.log(`FAIL ${r}\n  - ${probs.join('\n  - ')}`); } else console.log(`ok   ${r}`);
  }
  const flows = await page.evaluate(p => MC.flows.map(f => ({ id: f.id, dead: f.steps.filter(s => { const m = MC.match(s.go); return !m || m.s.route === '404'; }).map(s => s.go) })), prefix);
  flows.filter(f => f.dead.length).forEach(f => { bad++; console.log(`FLOW ${f.id} has steps to missing screens: ${f.dead.join(', ')}`); });
  console.log(`\n${routes.length} screens checked, ${bad} with problems. ${flows.length} flows registered.`);
  await browser.close();
  process.exit(bad ? 1 : 0);
})();
