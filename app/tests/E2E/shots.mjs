// Screenshots of a role's pages for comparing with the design prototype: node tests/E2E/shots.mjs "Amir Hakimi" outdir /path1 /path2
import { chromium } from '@playwright/test';
const [who, out, ...paths] = process.argv.slice(2);
const b = await chromium.launch({ channel: 'msedge' });
const p = await b.newPage({ viewport: { width: 1440, height: 900 } });
const base = process.env.BASE || 'http://127.0.0.1:8110';
await p.goto(base + '/signin');
await p.getByRole('button', { name: new RegExp('Sign in as ' + who) }).click();
if (/mfa/.test(p.url())) { await p.getByLabel('6-digit code').fill('123456'); await p.getByRole('button', { name: /Verify/ }).click(); }
for (const x of paths) { await p.goto(base + '/' + x.replace(/^\/+/, '')); await p.screenshot({ path: `${out}/${x.replace(/[^a-z0-9]+/gi, '_')}.png` }); }
await b.close();
