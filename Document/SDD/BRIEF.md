# SDD v1.0 writer brief

You are writing part of the **Software Design Description (SDD) v1.0** of the UTeM Micro-Credential Management System (MCMS), reference `MCMS_SDD_V1.0_Oct_2026`, dated 27 October 2026, by Aliff (intern). The SDD is the last document before the Release 1 build. It says **how** MCMS is built, so developers and AI coding agents can build Release 1 without guessing.

## Sources (read what your sections need; they are the contract)

| Source | Path | Use |
|---|---|---|
| Glossary and Decision Log v0.5 | `Decision log/src/01-glossary.html` | Decisions D-01..D-29, assumptions A-xx, open items OI-xx, terms |
| SRS v1.0 | `SRS/src/*.html` | Requirements: FR per module (04–06), BR/IR (07), data model + ER (08), NFR/SR (09), use cases (10) |
| PRD v1.0 | `PRD/src/*.html` | Features F-xx, stories, release increments 1.1–1.4, prototype scope (07) |
| UI/UX Design Specification v1.1 | `UIUX Spec/v1.1/src/*.html` | Screens (IDs in 06b-inventory), interaction rules, components, documents |
| Approved prototype | `UIUX/v2/prototype/js/*.js` and `screens/*.js` | The behaviour each screen must have; acts show state changes; `data.js` shows record fields |
| SRS screen inventory | `UIUX/v2/basis/srs-fr-inventory.md` | Per FR: actor, action, confirmation, outcome, blocking checks, statuses |
| Design system | `DESIGN.md` | Visual tokens |

Where sources disagree: SRS wins on requirements, Glossary on terms and decisions, UI/UX spec on screens.

## Architecture decisions (fixed for all writers; cite by ID)

| ID | Decision | Basis |
|---|---|---|
| AD-01 | **Modular monolith**: one Laravel application, code split into modules under `app/Modules/<Module>`. A module owns its tables; other modules use its public service interface or its domain events, never its tables directly. | D-07 |
| AD-02 | **Laravel 11 on PHP 8.3**. | D-08 |
| AD-03 | **PostgreSQL 16** as the database. Queries go through Eloquent and the query builder, with no vendor-specific SQL outside migrations, so MySQL 8 stays a fallback. | D-09, OI-02 (assumed) |
| AD-04 | **Server-rendered UI**: Blade templates with Livewire 3 and Alpine.js. Design tokens from DESIGN.md are CSS custom properties, built with Vite. Shared Blade components mirror the prototype: shell, data table (search, sort, pagination), dialog, drawer, toast, status tag, form field, stage track. | UI/UX spec §3–4 |
| AD-05 | **Background work** runs as Laravel queued jobs on Redis, supervised by Laravel Horizon: credential issuing, imports, report and PDF generation, notifications. Timed alerts run from the Laravel scheduler. | NFR queue |
| AD-06 | **Sign-in**: Microsoft Entra ID through OpenID Connect (Laravel Socialite, Microsoft provider). MFA for staff roles is enforced by Entra Conditional Access, and MCMS checks the `amr` claim. Idle timeout is 30 minutes (a setting). A stub identity provider exists only when `APP_ENV` is not `production`, and the app refuses to start in production with it on. | D-10, OI-03 (assumed) |
| AD-07 | **Authorisation**: role assignments carry a scope (University, PTj, or listed micro-credentials). Every action is checked by a Laravel Policy for role, scope and separation of duties on the record. There is no super-user bypass for academic actions; CODL roles never pass an academic policy. | D-11, D-12, D-28, FR rbac/sod |
| AD-08 | **Adapters**: each external system is a PHP interface with Stub, Manual and Live implementations, chosen by configuration per environment. The integration hub logs every call and retries failures. | D-06, D-14 |
| AD-09 | **Credentials** are Open Badges 3.0 / W3C Verifiable Credentials (JSON-LD), signed with Ed25519 (Data Integrity `eddsa-rdfc-2022`). Signing keys are held in a key vault owned by CODL, and the app never sees the private key in plain text. Public verification works by a random 10-character code, a link or a QR code. | D-19, OI-08 (assumed CODL) |
| AD-10 | **Documents and PDF**: Blade A4 templates (same layout as the prototype's printable documents) rendered to PDF by headless Chromium (Spatie Browsershot) in a queue worker. Templates are bilingual and versioned. | UI/UX spec §8 |
| AD-11 | **Audit**: an append-only `audit_events` table with a hash chain (each row stores the previous row's hash). The application database role has no UPDATE or DELETE on it. Audit rows are written in the same transaction as the change they record. | D-20 |
| AD-12 | **Settings** are versioned with effective dates. A decision stores the setting version it used. | D-16 |
| AD-13 | **Bilingual**: interface strings in Laravel lang files `ms` and `en`; content fields that users see stored in both languages; locale per user. | D-02 |
| AD-14 | **Files** in S3-compatible object storage (MinIO on premises assumed), scanned by ClamAV on upload, served through signed short-lived URLs. | sr-uploads |
| AD-15 | **Email** through SMTP relay (UTeM Microsoft 365 assumed). Every notification is stored first, then sent by a queued job. | IR smtp |
| AD-16 | **Search** for staff uses PostgreSQL full-text search, always within the user's scope. | nfr-search |
| AD-17 | **Testing**: Pest feature tests named after the SRS requirement they prove, plus Playwright end-to-end tests reusing the prototype's flows and hand-off scenario. | D-01 |
| AD-18 | **Hosting**: Linux VMs at the UTeM data centre (assumed) running Nginx + PHP-FPM, PostgreSQL, Redis, MinIO and Horizon workers. Environments are dev, test, staging and production. Demo clock and stub adapters are disabled in production. | A-09, D-21 |

## Modules (use these IDs and names everywhere)

The SRS modules M1–M16 are regrouped (D-07 allows it): M2 and M3 merge into one Curriculum module. M12 covers ACR, deposit and redemption, and M13 is split into Transfer and Pathways.

| ID | Module (`app/Modules/…`) | SRS module | SRS section |
|---|---|---|---|
| MD-01 | Identity (`Identity`) | M1 | 3.1 |
| MD-02 | Curriculum (`Curriculum`) | M2, M3 | 3.2 |
| MD-03 | Approval (`Approval`) | M4 | 3.3 |
| MD-04 | Offering (`Offering`) | M5 | 3.4 |
| MD-05 | Enrolment (`Enrolment`) | M6 | 3.5 |
| MD-06 | Learning platform (`LearningPlatform`) | M7 | 3.6 |
| MD-07 | Assessment (`Assessment`) | M8 | 3.7 |
| MD-08 | Achievement record (`AchievementRecord`) | M9 | 3.8 |
| MD-09 | Credentials (`Credentials`) | M10 | 3.9 |
| MD-10 | Quality verification (`QualityVerification`) | M11 | 3.10 |
| MD-11 | Credit bank (`CreditBank`): ACR, consent, deposit, ledger, redemption | M12 | 3.11, 3.13 |
| MD-12 | Transfer (`Transfer`) | M13 (part) | 3.12 |
| MD-13 | Pathways (`Pathways`): stackable mapping and plans | M13 (part) | 3.14 |
| MD-14 | Quality and change (`Monitoring`): CQI, periodic review, suspension | M14 | 3.15 |
| MD-15 | Records (`Records`): audit, traceability, reports, retention | M15 | 3.16 |
| MD-16 | Integration hub (`Integration`): notifications, adapters, jobs | M16 | 3.17 |
| MD-00 | Platform (`Platform`, shared kernel): settings, i18n, files, PDF rendering, UI components, demo clock | — | cross-cutting |

## ID scheme for this document
- **AD-xx**: architecture decisions (table above, owned by §1). Writers cite them; they do not create new AD IDs. If you need a new decision, put it in your report and I will add it.
- **DA-xx**: design assumptions, owned by §11. Same rule.
- **MD-xx**: modules.
- **JB-xx** (jobs) and **EV-xx** (domain events): defined in §4 (module design) and listed again in §6 (interfaces). The §4 writer owns their numbering, and the §6 writer reuses the same IDs. **Event naming**: `PastTense` class names, for example `ResultsVerified`, `CompletionConfirmed`, `CredentialIssued`.
- **Citing SRS requirements**: write `{{fr-key}}`, for example `{{fr-asverify}}`. The build replaces it with the published ID (FR-xx). The keys are in `SDD/src/00-srs-ids.html` (also BR, IR, DR, NFR, SR keys). Use only keys that exist there; grep the file to check. Cite PRD features as plain text (F-15), and UI spec screens by their ID (DD-09) and route.

## Format (the document template; same as the SRS and PRD)
- One HTML fragment file per section, in `SDD/src/`, named as assigned. Start with `<!-- ===================== N. TITLE ===================== -->` and `<h1>N. Title</h1>`. Use `<h2>N.n Title</h2>` and `<h3>N.n.n Title</h3>`.
- **Tables**:

  ```
  <p class="tcap" data-t="uniquekey">Caption</p>
  <script type="text/tbl" data-h="Col A|Col B" data-w="30%|70%" data-cls="small">
  row a | row b
  </script>
  ```

  Rows are pipe-separated; never put a literal `|` inside a cell. Reference a table with `{{tab:uniquekey}}`.
- **Figures**: `<figure data-f="uniquekey"><pre class="mermaid">…</pre><figcaption>Caption.</figcaption></figure>`. Reference a figure with `{{fig:uniquekey}}`. Keys must be unique in the whole document, so prefix yours with your section, for example `arch-`, `mod-`, `data-`, `seq-`.
- **Mermaid rules** (broken diagrams have cost us before):
  - Use `flowchart`, `sequenceDiagram`, `stateDiagram-v2` or `erDiagram`. Keep diagrams small enough for one A4 page (at most about 12 nodes, or split them).
  - In sequenceDiagram messages there are **no semicolons**; use commas.
  - No `&nbsp;` or HTML entities. Quote node labels with special characters, for example `A["Text (x)"]`.
  - `erDiagram`: attribute types must be single words (`string`, `int`, `bigint`, `timestamp`, `bool`, `json`), and attribute names use snake_case only.
- **Notes**: `<div class="note"><b>Note:</b> …</div>`. **Code** in `<code>`; multi-line code in `<pre class="code">…</pre>`, kept short.
- **Style**:
  - Plain, formal English, one statement per sentence where possible.
  - Use the Glossary terms exactly (Unbundled Micro-Credential (UMC), Stand-alone Micro-Credential (SAMC), Micro-Credential Eksekutif (MCE), ACR, ABC, QV, CQI).
  - Statuses exactly as in UI/UX spec §4.8.
  - No marketing words. No invented facts about UTeM: anything not in the sources is either an AD or DA from this brief, or flagged in your report.
- **Scope**:
  - Design for Release 1 (SHALL). Name the Release 2 and 3 hooks only where they shape the design.
  - Every module and every interface must trace to SRS requirements.

## Section owners and files

| File | Section | Owner |
|---|---|---|
| `01-front.html`, `02-intro.html` | Cover, control, contents, glossary; 1 Introduction (incl. AD table) | Lead |
| `03-architecture.html` | 2 Architecture overview: context diagram, containers and runtime, module map and dependencies, layering inside a module, inter-module communication (service interfaces, events), adapter pattern, request lifecycle | Writer A |
| `04-technology.html` | 3 Technology and code structure: stack table with versions and purpose, repository and module folder layout, naming and coding conventions, UI component library (each UI spec component → Blade/Livewire component), i18n, configuration and feature flags (Release 2 switches) | Writer A |
| `05-modules.html` | 4 Module design: for every MD-00..MD-16, its purpose, entities owned, main services and actions, state machines (stateDiagram for the key records), policies, events published and consumed, jobs, screens served (UI spec IDs), SRS requirements traced. Owns the EV-xx and JB-xx numbering. | Writer B |
| `06-data.html` | 5 Data design: ER diagrams by area (from SRS §6, adjusted to the modules), table catalogue (table, module, purpose, key columns), status enums, identifiers (internal ULIDs, public random codes, human numbers such as MCMS-C-2026-000098), audit hash chain, versioned settings, no-delete policy and corrections, retention and archiving, indexing, migrations and seed data | Writer C |
| `07-interfaces.html` | 6 Interface design: UI routing and page structure per role (summary; screen-level detail is the UI spec), external interfaces (SSO/OIDC claims, UCreds CSV import and export column specs, payment stub, SMTP, ABC export, QVC/MQA package, public verification page and JSON API, Open Badges 3.0 credential JSON example, PDF documents), internal event catalogue (EV-xx from §4) | Writer C |
| `08-processes.html` | 7 Process design: sequence diagrams for propose → review → endorse → Senate record; readiness → open offering; register and pay (incl. failure); UCreds import → results → verify → confirm completion → issue job; credential correction, replace and revoke; consent → ACR confirm → deposit; transfer decision and redemption; QV lapse; plus job designs (issue, import, report/PDF, alerts) with retries and idempotency | Writer D |
| `09-security.html` | 8 Security and privacy design: authentication and session, authorisation (policy matrix by role, scope, separation of duties), input and output protection (OWASP ASVS L2), key management and signing, audit integrity, public page minimisation and rate limits, PDPA handling, logging of security events, threat list with mitigations | Writer D |
| `10-quality.html` | 9 Quality attributes and operations: performance (targets from NFR, and how they are met), scalability (D-21), availability, backup and restore, accessibility (WCAG 2.1 AA build rules), bilingual, observability, maintainability; and 10 Deployment: environments, deployment diagram, CI/CD pipeline, configuration, migrations, demo clock and stub safety, release flags | Writer A |
| `11-verification.html` | 11 Verification, traceability, decisions and open items (DA-xx), closing `</main>` | Lead |

## Check your work
- Build the whole document from the project root in PowerShell:

  ```
  $env:PYTHONPATH = "C:\Users\Adomin\AppData\Local\Temp\claude\C--Users-Adomin-Documents-Intern-CODL-MC-ABC-System\c4d816e6-fb8f-4059-82fe-31530f1297f2\scratchpad\pylib"
  & .\SRS\build\build.ps1 -Src 'SDD\src' -Out 'SDD\MCMS_SDD_v1.0.pdf' -Ref MCMS_SDD_V1.0_Oct_2026
  ```

  Other writers' files may be missing or half-done while you work; your own sections must render.
- Then check the assembled HTML, `SRS\build\out\MCMS_SDD_v1.0\MCMS_SDD_v1.0.html`, for text that still shows `{{` and for diagrams that failed. A failed Mermaid diagram leaves the raw code or an error in the PDF text.
- Report back: the sections you wrote; the figure and table keys you used; every new decision or assumption you needed but did not invent; every SRS key you could not find; anything in the sources that conflicts.
