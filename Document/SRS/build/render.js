// Renders the assembled SRS HTML to PDF with Edge (Playwright). Usage: node render.js <in.html> <out.pdf> [toc.json]
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
function resolvePlaywrightCore() {
  if (process.env.PWC && fs.existsSync(process.env.PWC)) return process.env.PWC;
  const localApp = path.resolve(__dirname, '..', '..', '..', 'app', 'node_modules', 'playwright-core');
  if (fs.existsSync(localApp)) return localApp;
  try {
    const globalPath = path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
    if (fs.existsSync(globalPath)) return globalPath;
  } catch (e) {}
  return 'playwright-core';
}
const pwc = resolvePlaywrightCore();
const { chromium } = require(pwc);

const [, , src, out, toc] = process.argv;

const header = `<div style="width:100%;margin:0 18mm;padding-bottom:3px;border-bottom:0.5px solid #999;display:flex;justify-content:space-between;font-family:Georgia,'Times New Roman',serif;font-size:8px;font-weight:bold;color:#555">
<span>Universiti Teknikal Malaysia Melaka (UTeM)</span><span>Micro-Credential Management System (MCMS)</span></div>`;
const footer = `<div style="width:100%;margin:0 18mm;display:flex;justify-content:space-between;font-family:Georgia,'Times New Roman',serif;font-size:8px;color:#888">
<span>${process.env.DOC_REF || 'MCMS_SRS_V1.0_Oct_2026'}</span><span style="color:#3f6fa6;font-size:9px"><span class="pageNumber"></span></span><span>Draft for review</span></div>`;

(async () => {
  const browser = await chromium.launch({ channel: 'msedge' });
  const page = await browser.newPage();
  page.on('console', m => { if (m.type() === 'error') console.log('[page]', m.text()); });
  if (toc) await page.addInitScript(`window.__TOC = ${fs.readFileSync(toc, 'utf8')};`);
  await page.goto('file:///' + path.resolve(src).replace(/\\/g, '/'));
  await page.waitForFunction(() => document.body.dataset.done, null, { timeout: 120000 });
  const info = await page.evaluate(() => ({
    heads: [...document.querySelectorAll('[data-toc]')].map(h => h.textContent.trim()),
    problems: window.__problems,
  }));
  if (info.problems.length) console.log('PROBLEMS:\n  ' + info.problems.join('\n  '));
  fs.writeFileSync(out.replace(/\.pdf$/, '.headings.json'), JSON.stringify(info.heads));
  await page.pdf({
    path: out, format: 'A4', printBackground: true, displayHeaderFooter: true,
    headerTemplate: header, footerTemplate: footer,
    margin: { top: '22mm', bottom: '18mm', left: '18mm', right: '18mm' },
  });
  await browser.close();
  console.log('written', out);
})().catch(e => { console.error(e); process.exit(1); });
