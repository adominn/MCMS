// Saves every doc/* route (with its sample id, and every report id) as a PDF in UIUX/v2/samples.
// Usage: node UIUX/v2/tools/pdfs.js   (server must run on http://localhost:8731)
const path = require('path'), fs = require('fs');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const BASE = 'http://localhost:8731/', OUT = path.join(__dirname, '..', 'samples');

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ channel: 'msedge' });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const errs = []; page.on('pageerror', e => errs.push(e.message));
  await page.goto(BASE); await page.evaluate(() => sessionStorage.clear()); await page.goto(BASE + '#/');
  const jobs = await page.evaluate(() => {
    const out = [];
    MC.screens.filter(s => s.route.startsWith('doc/') && s.route !== 'doc/index').forEach(s => {
      const h = '#/' + s.parts.map(x => x[0] === ':' ? encodeURIComponent(s.ex || 'x') : x).join('/');
      if (s.route === 'doc/report/:id') MC.REPORTS.forEach(r => out.push([`doc-report-${r[0]}`, `#/doc/report/${r[0]}`]));
      else out.push([s.parts.join('-').replace(/:/g, '') + (s.ex ? '-' + String(s.ex).replace(/[^\w.-]+/g, '-') : ''), h]);
    });
    return out;
  });
  for (const [name, hash] of jobs) {
    errs.length = 0; await page.goto(BASE + hash); await page.waitForTimeout(250);
    const secs = await page.evaluate(() => document.querySelectorAll('section.a4').length);
    const pdf = await page.pdf({ printBackground: true, preferCSSPageSize: true });
    const n = (pdf.toString('latin1').match(/\/Type\s*\/Page[^s]/g) || []).length;
    fs.writeFileSync(path.join(OUT, name + '.pdf'), pdf);
    console.log(`${n === secs ? 'ok  ' : 'PAGES'} ${name}.pdf  sections=${secs} pdfpages=${n} ${errs.join('; ')}`);
  }
  await browser.close();
})();
