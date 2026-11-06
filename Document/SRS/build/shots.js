// Review helper: screenshots every figure and the cover of the assembled HTML in print layout.
// Usage: node shots.js <in.html> <outdir>
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const [, , src, dir] = process.argv;

(async () => {
  fs.mkdirSync(dir, { recursive: true });
  const browser = await chromium.launch({ channel: 'msedge' });
  // 174mm content width at 96dpi = 658px
  const page = await browser.newPage({ viewport: { width: 658, height: 1000 }, deviceScaleFactor: 1.5 });
  await page.emulateMedia({ media: 'print' });
  await page.goto('file:///' + path.resolve(src).replace(/\\/g, '/'));
  await page.waitForFunction(() => document.body.dataset.done, null, { timeout: 120000 });
  const figs = await page.$$('figure');
  for (let i = 0; i < figs.length; i++) await figs[i].screenshot({ path: path.join(dir, `fig${String(i + 1).padStart(2, '0')}.png`) });
  await (await page.$('.cover')).screenshot({ path: path.join(dir, 'cover.png') });
  await (await page.$('.toc')).screenshot({ path: path.join(dir, 'toc.png') });
  const reqs = await page.$$('table.req');
  await reqs[0].screenshot({ path: path.join(dir, 'req-first.png') });
  console.log(figs.length, 'figures');
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
