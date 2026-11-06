// Opens every pop-up (data-modal) and side panel (data-drawer) on every screen and checks it draws with a title.
// Usage: node UIUX/v2/tools/clickcheck.js [route-prefix]   (server on http://localhost:8731)
const path = require('path');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const BASE = 'http://localhost:8731/';

(async () => {
  const browser = await chromium.launch({ channel: 'msedge' });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  await page.goto(BASE); await page.evaluate(() => sessionStorage.clear()); await page.goto(BASE + '#/');
  const routes = await page.evaluate(p => MC.screens.filter(s => s.route.startsWith(p) && s.route !== '404')
    .map(s => '#/' + s.parts.map(x => x[0] === ':' ? encodeURIComponent(s.ex || 'x') : x).join('/')), process.argv[2] || '');
  let opened = 0, bad = 0;
  for (const r of routes) {
    await page.goto(BASE + r); await page.waitForTimeout(40);
    const res = await page.evaluate(() => {
      const out = [];
      const seen = new Set();
      document.querySelectorAll('[data-modal],[data-drawer]').forEach(el => {
        const key = (el.dataset.modal || 'drawer:' + el.dataset.drawer) + (el.dataset.title || '');
        if (seen.has(key)) return; seen.add(key);
        MC.close();
        // same context a real click builds: the element's data plus the values of its enclosing [data-form]
        const ctx = Object.assign({}, el.dataset); const f = el.closest('[data-form]');
        if (f) f.querySelectorAll('[name]').forEach(i => { if ((i.type === 'radio' || i.type === 'checkbox') && !i.checked) return; ctx[i.name] = i.value; });
        try { el.dataset.modal ? MC.open(el.dataset.modal, ctx) : MC.openDrawer(el.dataset.drawer, ctx); }
        catch (e) { out.push(`${key}: threw ${e.message}`); return; }
        const box = document.querySelector('.modal, .drawer');
        const title = box && box.querySelector('h2') && box.querySelector('h2').textContent.trim();
        if (!box || !title || /undefined/.test(box.innerHTML.slice(0, 2000))) out.push(`${key}: ${!box ? 'did not open' : !title ? 'no title' : 'shows "undefined"'}`);
        else out.push('');
      });
      MC.close();
      return out;
    });
    opened += res.length;
    const probs = res.filter(Boolean);
    if (probs.length || errors.length) { bad++; console.log(`FAIL ${r}\n  - ${[...probs, ...errors].join('\n  - ')}`); }
    errors.length = 0;
  }
  console.log(`\n${routes.length} screens, ${opened} pop-ups and panels opened, ${bad} screens with problems.`);
  await browser.close(); process.exit(bad ? 1 : 0);
})();
