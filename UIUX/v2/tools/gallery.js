// Captures every state of the prototype for the UI/UX spec gallery: each screen (full page), each tab and
// wizard step, each pop-up and side panel, the menus, each page of each printable document, and each flow step.
// Writes JPEGs plus manifest.json into <out-dir>.
// Usage: node UIUX/v2/tools/gallery.js <out-dir>   (server on http://localhost:8731)
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const pwc = process.env.PWC || path.join(execSync('npm root -g').toString().trim(), '@playwright', 'cli', 'node_modules', 'playwright-core');
const { chromium } = require(pwc);
const BASE = 'http://localhost:8731/';
const out = process.argv[2]; fs.mkdirSync(out, { recursive: true });
const GROUPS = [['', 'cmn'], ['public', 'pub'], ['st', 'st'], ['lec', 'lec'], ['ext', 'ext'], ['dd', 'dd'], ['codl', 'codl'], ['doc', 'doc']];
const manifest = [];
const shot = async (page, file, opts = {}) => { await page.screenshot(Object.assign({ path: path.join(out, file), type: 'jpeg', quality: 62 }, opts)); return file; };

(async () => {
  const browser = await chromium.launch({ channel: 'msedge' });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const errs = []; page.on('pageerror', e => errs.push(e.message));
  await page.goto(BASE + '#/'); await page.waitForTimeout(300);
  // fresh sample data, no flow bar, no prototype dev bar, then go
  const open = async route => {
    await page.evaluate(r => { sessionStorage.clear(); MC.state = JSON.parse(JSON.stringify(MC.seed)); MC.close(); MC.go(r); }, route);
    await page.waitForTimeout(220);
    await page.evaluate(() => { document.querySelectorAll('.devbar,.flowbar,.toasts').forEach(e => e.remove()); });
  };
  const screens = await page.evaluate(() => MC.screens.filter(s => !['screens', 'flows', '404'].includes(s.route)).map(s => ({
    route: s.route, title: s.title, ex: s.ex, steps: /p\.step/.test(String(s.render)),
    role: s.route.startsWith('public') ? 'public' : s.route.startsWith('doc') ? 'doc' : (s.role || '') })));
  const seenPop = new Set();
  let n = 0;
  for (const [role, key] of GROUPS) {
    const list = screens.filter(s => s.role === role);
    for (let i = 0; i < list.length; i++) {
      const s = list[i], id = `${key.toUpperCase()}-${String(i + 1).padStart(2, '0')}`;
      const route = '#/' + s.route.split('/').map(x => x[0] === ':' ? encodeURIComponent(s.ex || 'x') : x).join('/');
      await open(route);
      const base = id.toLowerCase();
      if (role === 'doc') {
        const pages = await page.$$('.a4');
        if (!pages.length) manifest.push({ kind: 'screen', id, title: s.title, route, file: await shot(page, `${base}.jpg`, { fullPage: true }) });
        for (let k = 0; k < pages.length; k++) manifest.push({ kind: 'docpage', id, title: s.title, route, label: `Page ${k + 1} of ${pages.length}`, file: await shot(pages[k], `${base}-p${k + 1}.jpg`) });
        n++; continue;
      }
      manifest.push({ kind: 'screen', id, title: s.title, route, file: await shot(page, `${base}.jpg`, { fullPage: true }) });
      // wizard steps
      if (s.steps) for (let st = 2; st <= 4; st++) {
        await open(route + `?step=${st}`);
        const cur = await page.evaluate(() => location.hash);
        if (!cur.includes(`step=${st}`)) continue;
        manifest.push({ kind: 'state', id, title: s.title, route: route + `?step=${st}`, label: `Step ${st}`, file: await shot(page, `${base}-step${st}.jpg`, { fullPage: true }) });
      }
      await open(route);
      // tabs: in-page panes and route tabs (same route, different query)
      const tabs = await page.evaluate(r => [...document.querySelectorAll('#app .tabs .tab')].filter(t => !t.classList.contains('on')).map((t, ix) => ({ ix, label: t.textContent.replace(/\d+$/, '').trim(), pane: t.dataset.tab || '', go: t.dataset.go || '' }))
        .filter(t => t.pane || (t.go && t.go.split('?')[0] === r.split('?')[0])), route);
      for (const t of tabs) {
        await open(route);
        if (t.go) await open(t.go); else { await page.evaluate(k => document.querySelector(`#app .tabs .tab[data-tab="${k}"]`).click(), t.pane); await page.waitForTimeout(150); }
        manifest.push({ kind: 'state', id, title: s.title, route: t.go || route, label: `Tab: ${t.label}`, file: await shot(page, `${base}-tab-${t.ix}.jpg`, { fullPage: true }) });
      }
      // pop-ups and side panels on this screen (every tab pane), each once in the whole system
      const triggers = async () => page.evaluate(() => [...document.querySelectorAll('#app [data-modal],#app [data-drawer]')].map((el, ix) => {
        const k = el.dataset.modal ? 'm:' + el.dataset.modal + (el.dataset.title || '') : 'd:' + el.dataset.drawer;
        return { ix, k, text: el.textContent.trim().slice(0, 60) };
      }));
      // in-page panes and route tabs both hide pop-up triggers
      const panes = [null, ...tabs.filter(t => t.pane).map(t => ({ pane: t.pane })), ...tabs.filter(t => t.go).map(t => ({ go: t.go }))];
      const enter = async pn => {
        await open(pn && pn.go ? pn.go : route);
        if (pn && pn.pane) { await page.evaluate(k => document.querySelector(`#app .tabs .tab[data-tab="${k}"]`).click(), pn.pane); await page.waitForTimeout(120); }
      };
      for (const pane of panes) {
        await enter(pane);
        for (const tg of await triggers()) {
          if (seenPop.has(tg.k)) continue; seenPop.add(tg.k);
          await enter(pane);
          const ok = await page.evaluate(ix => {
            const el = [...document.querySelectorAll('#app [data-modal],#app [data-drawer]')][ix]; if (!el) return false;
            const ctx = Object.assign({}, el.dataset); const f = el.closest('[data-form]');
            if (f) f.querySelectorAll('[name]').forEach(i => { if ((i.type === 'radio' || i.type === 'checkbox') && !i.checked) return; ctx[i.name] = i.value; });
            // pick the first option of a required choice so the dialog shows a real decision
            if (f) f.querySelectorAll('input[type=radio][required]').forEach(r => { if (!ctx[r.name]) ctx[r.name] = r.value; });
            try { el.dataset.modal ? MC.open(el.dataset.modal, ctx) : MC.openDrawer(el.dataset.drawer, ctx); } catch (e) { return false; }
            return !!document.querySelector('.modal,.drawer');
          }, tg.ix);
          if (!ok) continue;
          await page.waitForTimeout(150);
          const title = await page.evaluate(() => (document.querySelector('.modal h2,.drawer h2') || {}).textContent || '');
          const kind = tg.k.startsWith('d:') ? 'Side panel' : (await page.evaluate(() => !!document.querySelector('.modal.danger'))) ? 'Warning (destructive)' : 'Confirmation';
          manifest.push({ kind: 'popup', id, title: s.title, route, key: tg.k, label: `${kind}: ${title.trim()}`, trigger: tg.text, file: await shot(page, `${base}-pop-${seenPop.size}.jpg`) });
        }
      }
      n++; process.stdout.write(`\r${n} screens, ${manifest.length} images`);
    }
  }
  // pop-ups that only appear once a record is in a particular state: set the state, then open the pop-up
  const RECIPES = [
    ['m:reactivate', 'CODL', 'User (deactivated account)', '#/codl/user/S01388'],
    ['m:refimport', 'CODL', 'Reference data', '#/codl/refdata'],
    ['m:ucimport', 'CODL', 'UCreds exchange (file validated)', '#/codl/ucreds', null, `[...document.querySelectorAll('#app .btn')].find(b=>/validate|check the file|check file/i.test(b.textContent))?.click()`],
    ['m:endorse', 'DD', 'Proposal review (all comments resolved, category verified)', '#/dd/proposal/DB', `s => { (s.ddComments||[]).forEach(c => { if (c.mc==='DB') c.status='resolved'; }); const m = s.mcs.find(x=>x.id==='DB'); m.verifiedCat = m.cat; m.openComments = 0; }`],
    ['m:readyconfirm', 'DD', 'Confirm readiness (checklist sent, evidence accepted)', '#/dd/readiness/DS-2027-1', `s => { const r = s.lecRead && s.lecRead['DS-2027-1']; if (r) { r.sent = true; r.items.forEach(i => { i.st = 'Yes'; i.accepted = true; i.ev = i.ev || 'Uploaded record, 26 Oct 2026'; }); } const c = s.cohorts.find(x=>x.id==='DS-2027-1'); c.readiness = 12; }`],
    ['m:closecase', 'DD', 'Verify results (held learner)', '#/dd/verify/LA-2026-2'],
    ['m:confirmcomp', 'DD', 'Confirm completion', '#/dd/completion/LE-2026-10'],
    ['m:qvReady', 'DD', 'QV record (preparing)', '#/dd/qv/CND', `s => { const r = s.qvRecs && s.qvRecs.CND; if (r) r.stage = 'Preparing'; }`],
    ['m:qvResult', 'DD', 'QV record (submitted, record the result)', '#/dd/qv/CND', `s => { const r = s.qvRecs && s.qvRecs.CND; if (r) { r.stage = 'Submitted to QVC/MQA'; r.submitted = { date: '20 Oct 2026', ref: 'QVC/2026/0457', by: 'Hakim Rahman' }; } }`, `const i = document.querySelector('#app input[name=ref]'); if (i) i.value = 'QV/2026/1180'; [...document.querySelectorAll('#app [data-act=ddQvCheck]')][0]?.click()`],
    ['m:reviewOutcome', 'DD', 'Periodic review (outcome chosen)', '#/dd/review/DS', null, `const r = document.querySelector('#app input[type=radio]'); if (r) r.checked = true; [...document.querySelectorAll('#app [data-act]')].find(b=>/record outcome/i.test(b.textContent))?.click()`],
    ['m:redeemComplete', 'DD', 'Credit transfer (approved, provisionally redeemed)', '#/dd/transfer/TR-2026-0012', `s => { const t = s.transfers.find(x=>x.id==='TR-2026-0012'); t.status = 'Approved'; t.decidedOn = '27 Oct 2026'; t.lines.forEach(l => l.decision = 'Approved'); s.acrs.find(a=>a.id==='MCMS-A-2026-000102').credit = 'Provisionally redeemed'; }`],
    ['m:redeemRelease', 'DD', 'Credit transfer (approved, provisionally redeemed)', '#/dd/transfer/TR-2026-0012', `s => { const t = s.transfers.find(x=>x.id==='TR-2026-0012'); t.status = 'Approved'; t.decidedOn = '27 Oct 2026'; t.lines.forEach(l => l.decision = 'Approved'); s.acrs.find(a=>a.id==='MCMS-A-2026-000102').credit = 'Provisionally redeemed'; }`],
    ['m:pwSenate', 'DD', 'Pathway mapping (submitted to the Senate)', '#/dd/pathway/PW-BITZ-1', `s => { s.pathways.find(w=>w.id==='PW-BITZ-1').status = 'Submitted to Senate'; }`],
    ['m:respond', 'LEC', 'Proposal workspace, comments', '#/lec/proposal/DB?tab=comments'],
    ['m:chgconfirm', 'LEC', 'Change to an approved version (change chosen)', '#/lec/change/DS', null, `const c = document.querySelector('#app input[type=checkbox]'); if (c) { c.checked = true; c.dispatchEvent(new Event('change', { bubbles: true })); c.dispatchEvent(new Event('input', { bubbles: true })); }`],
    ['m:st-pay', 'ST', 'Register, payment step', '#/st/register/CND-2027-1?step=3', `s => { s.regDrafts = s.regDrafts || {}; s.regDrafts['CND-2027-1'] = { ackVer: 'v1.0', ackOn: '27 Oct 2026', pdpaOn: '27 Oct 2026', phone: '012-345 6789' }; }`],
    ['m:st-cancelreg', 'ST', 'Registration pending payment', '#/st/enrolment/E-2026-0701'],
    ['m:st-tr-submit', 'ST', 'New transfer application, last step', '#/st/transfer/new', `s => { const a = s.acrs.find(x=>x.id==='MCMS-A-2026-000214'); Object.assign(a, { status: 'Deposited', consent: 'Given 26 Oct 2026', abcRef: 'ABC-MY-2026-0004501', deposited: '26 Oct 2026', expires: '26 Oct 2036', credit: 'Available' }); }`, `(async () => { const w = ms => new Promise(r => setTimeout(r, ms)); for (let k = 0; k < 6 && !document.querySelector('#app [data-modal=st-tr-submit]'); k++) { document.querySelectorAll('#app input[type=checkbox]:not(:disabled),#app input[type=radio]:not(:disabled)').forEach((c, ix) => { if (c.type === 'checkbox' || !document.querySelector('#app input[name=' + c.name + ']:checked')) { c.checked = true; c.dispatchEvent(new Event('change', { bubbles: true })); } }); document.querySelectorAll('#app [required]').forEach(i => { if (!i.value && i.tagName !== 'SELECT') i.value = 'Prototype entry'; }); const b = [...document.querySelectorAll('#app [data-act]')].find(e => e.dataset.act !== 'lang' && /continue|next/i.test(e.textContent)); if (!b) break; b.click(); await w(300); } })()`],
  ];
  const missed = [];
  for (const [key, grp, where, route, prep, before] of RECIPES) {
    if (seenPop.has(key)) continue;
    await page.evaluate(([r, prep]) => { sessionStorage.clear(); MC.state = JSON.parse(JSON.stringify(MC.seed)); if (prep) { try { (0, eval)('(' + prep + ')')(MC.state); } catch (e) { console.warn('prep failed', e.message); } } MC.save(); MC.close(); MC.go(r); }, [route, prep]);
    await page.waitForTimeout(250);
    if (before) { await page.evaluate(before); await page.waitForTimeout(250); }
    const name = key.slice(2);
    let ok = await page.evaluate(nm => !!document.querySelector('.modal,.drawer') || (() => {
      const el = document.querySelector(`#app [data-modal="${nm}"],#app [data-drawer="${nm}"]`); if (!el) return false;
      const ctx = Object.assign({}, el.dataset); const f = el.closest('[data-form]');
      if (f) f.querySelectorAll('[name]').forEach(i => { if ((i.type === 'radio' || i.type === 'checkbox') && !i.checked) return; ctx[i.name] = i.value; });
      if (f) f.querySelectorAll('input[type=radio][required]').forEach(r => { if (!ctx[r.name]) ctx[r.name] = r.value; });
      el.dataset.modal ? MC.open(nm, ctx) : MC.openDrawer(nm, ctx); return !!document.querySelector('.modal,.drawer');
    })(), name);
    if (!ok) { missed.push(`${key} at ${route}`); continue; }
    await page.waitForTimeout(150);
    await page.evaluate(() => document.querySelectorAll('.devbar,.flowbar,.toasts').forEach(e => e.remove()));
    seenPop.add(key);
    const title = await page.evaluate(() => (document.querySelector('.modal h2,.drawer h2') || {}).textContent || '');
    const kind = (await page.evaluate(() => !!document.querySelector('.modal.danger'))) ? 'Warning (destructive)' : 'Confirmation';
    manifest.push({ kind: 'popup', id: grp, title: where, route, key, label: `${kind}: ${title.trim()}`, file: await shot(page, `state-${name.toLowerCase()}.jpg`) });
  }
  if (missed.length) console.log('\nnot reachable by recipe: ' + missed.join('; '));

  // wizards: walk them for real, capturing every step (replaces the ?step= captures, which redirect without earlier steps)
  const fill = () => page.evaluate(card => {
    document.querySelectorAll('#app input[type=checkbox]:not(:disabled)').forEach(c => { c.checked = true; c.dispatchEvent(new Event('change', { bubbles: true })); });
    new Set([...document.querySelectorAll('#app input[type=radio]:not(:disabled)')].map(r => r.name)).forEach(g => { if (!document.querySelector(`#app input[name="${g}"]:checked`)) document.querySelector(`#app input[name="${g}"]:not(:disabled)`).checked = true; });
    document.querySelectorAll('#app input[required]:not([type=checkbox]):not([type=radio]),#app textarea[required]').forEach(i => { if (!i.value) i.value = /phone/i.test(i.name) ? '012-345 6789' : 'Prototype entry'; });
    if (card) document.querySelectorAll('#app input').forEach(i => { if (/card/i.test(i.name + i.placeholder)) i.value = card; });
  }, null);
  const advance = () => page.evaluate(() => { const b = [...document.querySelectorAll('#app [data-act],#app [data-go]')].find(e => e.dataset.act !== 'lang' && !e.classList.contains('dis') && /continue|next|review/i.test(e.textContent)); if (b) b.click(); return !!b; });
  const WIZ = [
    ['ST-03', 'Register', '#/st/register/CND-2027-1', null, 'st-pay'],
    ['ST-12', 'New transfer application', '#/st/transfer/new', `s => { const a = s.acrs.find(x=>x.id==='MCMS-A-2026-000214'); Object.assign(a, { status: 'Deposited', consent: 'Given 26 Oct 2026', abcRef: 'ABC-MY-2026-0004501', deposited: '26 Oct 2026', expires: '26 Oct 2036', credit: 'Available' }); }`, 'st-tr-submit'],
  ];
  for (const [id, title, route, prep, finalModal] of WIZ) {
    for (let k = manifest.length - 1; k >= 0; k--) if (manifest[k].id === id && manifest[k].kind === 'state' && /^Step/.test(manifest[k].label)) manifest.splice(k, 1);
    await page.evaluate(([r, prep]) => { sessionStorage.clear(); MC.state = JSON.parse(JSON.stringify(MC.seed)); if (prep) (0, eval)('(' + prep + ')')(MC.state); MC.close(); MC.go(r); }, [route, prep]);
    for (let st = 1; st <= 6; st++) {
      await page.waitForTimeout(250); await page.evaluate(() => document.querySelectorAll('.devbar,.flowbar,.toasts').forEach(e => e.remove()));
      if (st > 1) manifest.push({ kind: 'state', id, title, route: await page.evaluate(() => location.hash), label: `Step ${st}`, file: await shot(page, `${id.toLowerCase()}-walk${st}.jpg`, { fullPage: true }) });
      if (await page.$(`#app [data-modal="${finalModal}"]`)) break;
      await fill(); if (!(await advance())) break;
    }
    const k = 'm:' + finalModal;
    if (!seenPop.has(k)) {
      await page.evaluate(nm => { const el = document.querySelector(`#app [data-modal="${nm}"]`); if (el) MC.open(nm, Object.assign({}, el.dataset)); }, finalModal);
      await page.waitForTimeout(150);
      if (await page.$('.modal')) { seenPop.add(k); manifest.push({ kind: 'popup', id, title, route, key: k, label: `Confirmation: ${await page.evaluate(() => document.querySelector('.modal h2').textContent.trim())}`, file: await shot(page, `state-${finalModal}.jpg`) }); }
    }
    // registration: confirm the payment for the success page, and show the failure path with the test card
    if (finalModal === 'st-pay') {
      await page.evaluate(() => { MC.close(); const el = document.querySelector('#app [data-modal="st-pay"]'); MC.open('st-pay', Object.assign({}, el.dataset)); });
      await page.waitForTimeout(150); await page.evaluate(() => document.querySelector('[data-mok]')?.click()); await page.waitForTimeout(400);
      await page.evaluate(() => document.querySelectorAll('.devbar,.flowbar,.toasts').forEach(e => e.remove()));
      manifest.push({ kind: 'state', id, title, route: await page.evaluate(() => location.hash), label: 'Step 4: paid, registration confirmed', file: await shot(page, `${id.toLowerCase()}-paid.jpg`, { fullPage: true }) });
      await page.evaluate(([r]) => { sessionStorage.clear(); MC.state = JSON.parse(JSON.stringify(MC.seed)); MC.close(); MC.go(r); }, [route]);
      for (let st = 1; st <= 3 && !(await page.$('#app [data-modal="st-pay"]')); st++) { await page.waitForTimeout(250); await fill(); await advance(); }
      await page.waitForTimeout(250);
      await page.evaluate(() => document.querySelectorAll('#app input').forEach(i => { if (/card/i.test(i.name + i.placeholder)) i.value = '4000 0000 0000 0002'; }));
      await page.evaluate(() => { const el = document.querySelector('#app [data-modal="st-pay"]'); const f = el.closest('[data-form]'); const ctx = Object.assign({}, el.dataset); if (f) f.querySelectorAll('[name]').forEach(i => { if ((i.type === 'radio' || i.type === 'checkbox') && !i.checked) return; ctx[i.name] = i.value; }); MC.open('st-pay', ctx); });
      await page.waitForTimeout(150); await page.evaluate(() => document.querySelector('[data-mok]')?.click()); await page.waitForTimeout(400);
      await page.evaluate(() => document.querySelectorAll('.devbar,.flowbar,.toasts').forEach(e => e.remove()));
      manifest.push({ kind: 'state', id, title, route: await page.evaluate(() => location.hash), label: 'Payment failed with the test card, ready to retry', file: await shot(page, `${id.toLowerCase()}-payfail.jpg`, { fullPage: true }) });
    }
  }

  // menus for each signed-in role
  for (const [r, home] of [['st', '#/st/home'], ['lec', '#/lec/home'], ['dd', '#/dd/queue'], ['codl', '#/codl/users'], ['ext', '#/ext/home']]) {
    for (const m of ['notif', 'user']) {
      await open(home); await page.evaluate(k => document.querySelector(`[data-menu=${k}]`).click(), m); await page.waitForTimeout(150);
      manifest.push({ kind: 'menu', id: r, title: m === 'notif' ? 'Notifications menu' : 'Profile menu', route: home, label: r, file: await shot(page, `menu-${r}-${m}.jpg`, { clip: { x: 640, y: 0, width: 800, height: 560 } }) });
    }
  }
  // idle timeout warning and sign-out dialog
  await open('#/dd/queue'); await page.evaluate(() => MC.open('timeout')); await page.waitForTimeout(150);
  manifest.push({ kind: 'popup', id: 'CMN', title: 'Any signed-in screen', route: '#/dd/queue', label: 'Warning: idle sign-out', file: await shot(page, 'pop-timeout.jpg') });
  await open('#/dd/queue'); await page.evaluate(() => MC.open('signout')); await page.waitForTimeout(150);
  manifest.push({ kind: 'popup', id: 'CMN', title: 'Profile menu', route: '#/dd/queue', label: 'Confirmation: sign out', file: await shot(page, 'pop-signout.jpg') });
  // every step of every flow, at half resolution (thumbnails)
  const fpage = await browser.newPage({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 0.5 });
  await fpage.goto(BASE + '#/'); await fpage.waitForTimeout(300);
  const flows = await fpage.evaluate(() => MC.flows.map(f => ({ id: f.id, title: f.title, actor: f.actor, steps: f.steps.map(s => ({ go: s.go, say: s.say })) })));
  flows.sort((a, b) => a.id.localeCompare(b.id, 'en', { numeric: true }));
  for (const f of flows) {
    await fpage.evaluate(() => { sessionStorage.clear(); MC.state = JSON.parse(JSON.stringify(MC.seed)); });
    for (let i = 0; i < f.steps.length; i++) {
      await fpage.evaluate(([id, i]) => { MC.close(); MC.startFlow(id, i); }, [f.id, i]); await fpage.waitForTimeout(220);
      await fpage.evaluate(() => document.querySelectorAll('.devbar,.toasts').forEach(e => e.remove()));
      manifest.push({ kind: 'flowstep', id: f.id, title: f.title, actor: f.actor, route: f.steps[i].go, label: `Step ${i + 1} of ${f.steps.length}`, say: f.steps[i].say, file: await shot(fpage, `${f.id.toLowerCase()}-s${i + 1}.jpg`) });
    }
  }
  fs.writeFileSync(path.join(out, 'manifest.json'), JSON.stringify(manifest, null, 1));
  const c = k => manifest.filter(m => m.kind === k).length;
  console.log(`\nscreens ${c('screen')}, states ${c('state')}, pop-ups ${c('popup')}, menus ${c('menu')}, document pages ${c('docpage')}, flow steps ${c('flowstep')}; script errors ${errs.length}`);
  if (errs.length) console.log([...new Set(errs)].slice(0, 5).join('\n'));
  await browser.close();
})();
