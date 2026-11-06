# Brief for prototype builders

You are building part of the MCMS clickable prototype in `UIUX/v2/prototype/`. It is plain HTML, CSS and JS: no framework, no build step, no dependencies.

## Read first
1. `UIUX/v2/DESIGN-BASIS.md`: the rules, status vocabulary, screen inventory (with exact routes) and flow list. This is the contract.
2. `UIUX/v2/basis/srs-fr-inventory.md`: per requirement, the actor, action, confirmation, result and blocking checks. Read the sections for your screens.
3. `UIUX/v2/prototype/js/core.js`: the API (helpers, router, tables, modals, drawers, acts, toasts, flows).
4. `UIUX/v2/prototype/js/data.js`: the shared sample world. Today is 26 Oct 2026.
5. `UIUX/v2/prototype/js/screens/common.js` and `dd-a.js`: worked examples of the pattern (`dd/queue`).
6. `DESIGN.md` for the look. `UIUX/v1/src/*.html` are the v1 screens: reuse their content and layout ideas, but apply the corrections in DESIGN-BASIS section 6.

## Rules
- **Write only your own file.** Do not edit `core.js`, `data.js`, `common.js`, `index.html`, CSS, or another builder's file. If you need a helper, define it inside your file's IIFE.
- **Adding data:**
  - New top-level keys go in via `Object.assign(MC.seed, { yourKey: [...] })`.
  - New records go into existing arrays with `MC.seed.tasks.push(...)`, and must have unique ids.
  - Never change or remove existing seed records.
  - Read state in `render(p, s)` from `s` (the live state), not from `MC.seed`.
- **Every route in your part of the inventory** must exist: `MC.screen(route, { title, role, nav, ex, render, mount? })`.
  - `ex` is the sample value for `:param` routes; use an id that exists in the data.
  - `nav` is the side-nav key to highlight.
- **Every control must do something:**
  - `data-go="#/route"`: the target must exist.
  - `data-modal="name"`: define it with `MC.modal`, or use `data-modal="confirm"` with `data-title`, `data-body`, `data-confirm`, optional `data-danger`, `data-reason`, `data-act`, `data-done="Title|text"`, `data-then="#/route"`.
  - `data-drawer="name"` (define it with `MC.drawer`), `data-act="name"` (define it with `MC.act`), `data-toast="Title|text"`.
  - A disabled button (`class="btn dis"`) must say why in its label or beside it.
- **Real state changes.** Decisions change the data through an act, so the next screen shows the result. For example:
  - Verifying sets `cohort.results = 'Verified'`.
  - It marks the task `done: true` with a `doneNote`.
  - It adds a notification for the affected person (`s.notifs.st.unshift({...})`).
  - It pushes an audit row (`s.audit.unshift([...])`).

  Disabled and blocked states must be reachable in the data, and so must the negative paths.
- **Confirmation dialogs** for everything listed in DESIGN-BASIS 2.2. Each states what happens, what does not, and who is told. Destructive dialogs use `danger: true` and require a reason.
- **Tables** use `MC.table({...})`. Paginate any list that can exceed 10 rows. Give each table a unique `id` (prefix it with your role).
- **Copy:**
  - Plain, specific sentences with real figures that add up.
  - Sentence case, no exclamation marks, no emoji, no lorem ipsum.
  - Dates as "26 Oct 2026" (converted to DD/MM/YYYY automatically), money with `MC.rm(450)`.
  - Use the PRD status words exactly.
- **Release 2 features** can appear as disabled controls labelled "Release 2".
- **Flows:** register the flows assigned to you with `MC.flow({ id, title, actor, summary, steps: [{ go, say }] })`.
  - `say` tells the viewer what is happening and what to click next.
  - Steps may point to other roles' routes from the inventory; they will exist when all builders finish.
- **Look:**
  - DESIGN.md: flat corners, IBM Plex Sans, navy only for the primary action, links and focus.
  - One filled primary button per screen.
  - Taste rules: colour on data not chrome, no icon-in-circle, KPIs as plain type in `MC.kpis`.

## Check your work
The server runs at http://localhost:8731. If it is not running, start it with `py -m http.server 8731 --directory UIUX/v2/prototype` in the background.

```
node UIUX/v2/tools/check.js <your-route-prefix>
```

This reports screens that fail to draw, script errors, undefined modals, drawers or acts, and dead links.
- Dead links to other builders' routes from the inventory are expected while they are still building. List them in your report.
- Everything else must pass.
- Also run `node --check` on your file.

## Report back
- The routes you built.
- The flows you registered.
- The seed keys you added.
- Any dead links that point to other builders' routes.
- Anything in the basis you could not follow, and why.
