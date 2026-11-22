# UTeM MCMS UI/UX v3 — Student Portal Design Plan

**Document Version**: 1.0  
**Status**: Ready for User Review & Approval  
**Authority**: `MC UTeM Guide.pdf` (v1.0, 18 Sept 2026), `DESIGN.md` (v2.4 "linear-precision" light theme), and Live Implementation (`app/resources/views/student/`, `app/resources/views/layouts/app.blade.php`).

---

## 1. Executive Summary & Design Scope

The Student Portal (`P8` role: Participant / Learner) is the core operational interface for individuals enrolled in UTeM Micro-Credentials. It empowers learners to track course progressions, review modular assessment outcomes, manage cryptographic credentials and digital badges, grant informed consent for Academic Bank of Credit (ABC) deposits, and articulate modular learning toward degree qualifications.

### Core Constraints & Directives:
- **Design System Authority**: Strictly adheres to `DESIGN.md` (v2.4 "linear-precision" light theme) with crisp 1px solid black card outlines (`#090a0f`), deep cobalt navy sidebar (`#0b1a3d`), 28px corner curvature, 24px pull tab with `<` / `>` toggle, and 12px active tab pill.
- **Prohibition of Redundant Text**: No `"Where is this / What happens next / Who is responsible"` orientation blocks. Information architecture is summary-first, intuitive, and immediate.
- **Clean Badges without Brackets**: Zero square brackets around tags, badges, buttons, or role labels (e.g., `FTMK`, `MQF 6`, `UMC`, `SAMC`, `P8`).
- **Standard Institutional Spelling**: Uses `"enroll"` / `"enrollment"` (double 'l') exclusively.
- **Regulatory Truth (MC UTeM Guide v1.0)**:
  - **ABC is Storage Only (Cl. 10.3.1)**: Storing credits in the ABC does not imply automatic credit transfer or degree admission.
  - **80% Syllabus Equivalence (Cl. 10.3.2)**: Credit transfer requires formal faculty committee review of syllabus overlap.
  - **Statutory 30% Exemption Ceiling**: Maximum credit transfer into a degree is capped at 30% of total degree credits.
  - **Single Redemption**: Credits can only be redeemed once toward degree graduation.
- **Light Theme Only & English Only**: Pure white canvas (`#ffffff`), slate surfaces (`#f1f5f9`), no dark theme, no Malay toggle.

---

## 2. Student Persona Context

| Parameter | Persona Specification |
|:---|:---|
| **Full Name** | **Amirul Hakim bin Rosli** |
| **Institutional ID** | `B032110045` (Student Matric No.) |
| **Official Email** | `amirul.hakim@student.utem.edu.my` |
| **Faculty / PTj** | Faculty of Information and Communication Technology (`FTMK`) |
| **Enrolled Degree** | Bachelor of Computer Science (Software Development) with Honours |
| **Active Role** | `P8` &mdash; Student / Participant |
| **MFA Status** | Optional for students (Direct SSO access) |

---

## 3. Logged-In App Shell Architecture

Unlike the public portal, the Student Portal operates within the authenticated **Linear-Precision App Shell** defined in `DESIGN.md`:

```
+--------------------------------------------------------------------------------------------------------------------+
| BRAND HEADER (Pure White #ffffff, 64px height)                                                                     |
| [UTeM Logo]  UTeM MCMS              [ Search or Jump to action... ⌘K ]          [🔔 2]  [AH Amirul Hakim · Student P8 ●]|
+--------------------------+-----------------------------------------------------------------------------------------+
| SIDEBAR (Cobalt Navy)    | MAIN WORKSPACE CONTENT CANVAS (#f8fafc canvas-subtle)                                  |
| #0b1a3d, 28px radius     |                                                                                         |
|                          | Title Bar: My Learning & Action Tasks                                                   |
| LEARNING                 | Metric Strip: 2 Active Courses | 7 Modules Achieved | 6 Credits | 1 Action Due          |
| > My Learning (Active)   |                                                                                         |
|   Find Micro-Credential  | Continuous Linear Stepper: Learner Recognition Journey                                 |
|   Registrations & Fees   | (✓) 01. Course  →  (✓) 02. Eligibility  →  (✓) 03. ACR Issued  →  (04) Consent (Active) |
|   My Results & CLOs      |                                                                                         |
|   Course Feedback        | Primary Panels:                                                                         |
|                          | +---------------------------------------------------+ +---------------------------------+ |
| ACHIEVEMENTS             | | Actions Waiting for You (1 Open Task)             | | Recognition Track Summary       | |
|   Digital Wallet         | | In-Progress Courses (Module Completion Bars)      | | Upcoming Registrations          | |
|   Achievement Record     | | Recent Verifiable Credentials & Badges            | | Degree Articulation Progress    | |
|                          | +---------------------------------------------------+ +---------------------------------+ |
| CREDIT & DEGREE          |                                                                                         |
|   Credit Ledger & ABC    | Card outlines: 1px solid black #090a0f. Zero emojis. Geometric precision typography.   |
|   Credit Transfer        |                                                                                         |
|   My Degree Plan         |                                                                                         |
|                          |                                                                                         |
| GENERAL                  |                                                                                         |
|   Documents & Guide      |                                                                                         |
|   Sign Out               |                                                                                         |
|                          |                                                                                         |
| [< Pull Tab Toggle]      |                                                                                         |
+--------------------------+-----------------------------------------------------------------------------------------+
```

### Key Elements of the App Shell:
1. **Left Rounded Sidebar (`.app-sidebar`)**:
   - Background: Deep Cobalt Navy (`#0b1a3d`).
   - Top-right corner radius: `28px` (`--radius-sidebar-corner`).
   - Pull-tab toggle: Semi-pill latch (`0 24px 24px 0`, radius `24px`) with typographic arrow `<` to collapse / expand.
   - 4 Semantic Navigation Sections:
     - `LEARNING`: My Learning, Find Micro-Credential, Registrations & Fees, My Results, Course Feedback.
     - `ACHIEVEMENTS`: Digital Wallet (Badges & Certificates), Achievement Record (Transcript).
     - `CREDIT & DEGREE`: Credit Ledger & ABC, Credit Transfer, My Degree Plan.
     - `GENERAL`: Documents & Policies, Notifications, Sign Out.
   - Active navigation item: Solid electric cobalt blue pill (`#0052ff`) with white text and 12px pill radius.
2. **Brand Topbar (`.app-topbar`)**:
   - Pure white background with hairline bottom border.
   - Institutional UTeM mark (`LogoJawiUTeM_blue-01.png`) and `UTeM MCMS`.
   - Command search pill: `Search or jump to action... ⌘K`.
   - Top-right user chip: User avatar initials (`AH`), name (`Amirul Hakim`), role badge (`Student P8`), green online status pip, and dropdown toggle.

---

## 4. Screen-by-Screen Inventory & Structure

We will produce **6 core student screens** covering the complete end-to-end student journey:

### Screen 1: Student Learning Dashboard (`student/dashboard.html`)
*The primary landing screen (`tasks.html` in live system).*
- **Greeting & Header**: `My Learning` &middot; Amirul Hakim bin Rosli (B032110045 &middot; FTMK).
- **Metric Cards (4 Bento Cells)**:
  1. *Active Enrollments*: 2 micro-credentials currently in progress.
  2. *Modules Achieved*: 7 / 10 modular badges earned (70% milestone).
  3. *Accumulated Credits*: 6 Academic Credits earned across verified ACRs.
  4. *Action Queue*: 1 pending decision (ABC Deposit Consent required).
- **Interactive Linear Stepper Flow**:
  - Live 8-stage progress tracker showing student's progress across current cohorts.
  - Steps 01–03 completed (`✓`), Step 04 active (`04 Consent &middot; In Progress`), Steps 05–08 upcoming.
- **Action Tasks Panel (`#actions`)**:
  - Open task item: `"Provide ABC Deposit Consent for Cloud Architecture (ACR-2026-0042)"` &rarr; Direct action button `Review & Consent &rarr;`.
  - Overdue indicator and days-waiting counter.
- **In-Progress Courses (`#in-progress`)**:
  - Offering card 1: *Cloud Native Application Development & Microservices* (Cohort CND-2027A). Progress bar: 3 of 4 modules achieved (75%). End date, lecturer name, link to module syllabus.
  - Offering card 2: *Industrial IoT Sensors & Edge Computing Gateway* (Cohort IIOT-2027B). Progress bar: 1 of 3 modules achieved (33%).
- **Recent Credentials & Badges (`#achieved`)**:
  - Table of minted credentials: Badge preview, Course name, Issued date, Public verification code (`CND-4482-991`), Status (`Issued`).
- **Sidebar Aside Widgets**:
  - Upcoming Registrations (Confirmed cohorts starting next month).
  - Degree Articulation Progress widget (6 of 36 allowable credits completed &rarr; 16.7% toward degree limit).

---

### Screen 2: Digital Credential Wallet (`student/wallet.html`)
*Equivalent to `credentials.wallet` in live system.*
- **Header**: `Digital Credential Wallet & Badges` with subtitle: *"Cryptographically verifiable credentials minted under UTeM Framework."*
- **Two-Tab Interface**:
  1. *Micro-Credential Certificates (Full Awards)*:
     - Certificate dossier cards with official UTeM crest, credential ID, MQF Level, Credit Value, public verification link, and PDF download action.
  2. *Module Badges (Open Badges v2 / W3C Verifiable Credentials)*:
     - Grid of minted modular achievement badges with cryptographic hash, issuing lecturer signature, and JSON-LD download.
- **Interactive Verification Modal Trigger**:
  - Click any credential to inspect cryptographic authenticity, Ed25519 public key signature, and QR code for external employer verification.

---

### Screen 3: Credit Ledger & ABC Consent Hub (`student/credit-ledger.html`)
*Equivalent to `credit` and `credit.acr` in live system.*
- **Header**: `Credit Ledger & Academic Bank of Credit (ABC)` with action: `Apply for Credit Transfer`.
- **Mandatory Statutory Notice (Appendix F5)**:
  - Callout emphasizing that ABC is storage only; credits do not grant automatic graduation or transfer.
- **KPI Summary**:
  - *Total Accumulated*: 6 Credits.
  - *Available for Articulation*: 6 Credits (Deposited in ABC).
  - *Exemption Limit Remaining*: 30 Credits remaining before reaching 36-credit degree ceiling.
- **Pending Consent Action Banner**:
  - Action card for ACR requiring student consent: Option to `[Grant Deposit Consent]`, `[Postpone]`, or `[Decline]`. Explains 10-year archival terms under PDPA 2010.
- **Official ACR Ledger Table**:
  - Columns: ACR Reference, Micro-Credential Title, MQF Level, Credit Hours, Issue Date, Dean Signature, ABC Status (`Deposited`, `Consent Given`, `Ready`), Expiry Date (10 years).
  - Row actions: `Inspect ACR Sheet`, `Download PDF`.

---

### Screen 4: Academic Results & CLO Attainment (`student/results.html`)
*Equivalent to `student/results.blade.php` and `result-detail.blade.php` in live system.*
- **Header**: `My Academic Results & Competencies` &middot; FTMK.
- **Course Selection Tabs / Selector**:
  - Select active or concluded micro-credential to inspect marks.
- **Assessment Components Table**:
  - Continuous Assessments (Lab Quizzes, Practical Projects, Architecture Proposal) with weightages, student scores, and pass thresholds.
  - Grade Point Average (GPA) & Grade letter (`A`, `A-`, `B+`).
- **Constructive Alignment / CLO Domain Radar**:
  - Mapping of learner performance across MQF Domains: Knowledge & Understanding (MQF 1), Cognitive Skills (MQF 2), Functional Work Skills (MQF 3), Personal Skills (MQF 4).

---

### Screen 5: Degree Pathway Plan & Transfer Explorer (`student/pathway-plan.html`)
*Equivalent to `pathways.mine` and `transfer.create` in live system.*
- **Header**: `My Degree Pathway & Articulation Plan`.
- **Target Degree Program**:
  - *Bachelor of Computer Science (Software Development) with Honours* &middot; FTMK &middot; 120 Total Credits.
- **30% Articulation Progress Bar**:
  - Statutory 36-credit maximum transfer allowance.
  - Shows 6 credits mapped (16.7%), remaining eligible room: 30 credits.
- **Course-by-Course Equivalence Matrix**:
  - Side-by-side mapping table:
    - *Left*: UTeM Degree Core Course (e.g. `BITD 2213: Advanced Web Architecture`, 3 credits).
    - *Right*: Completed Micro-Credential (e.g. `UMC-BITD-2401: Cloud Native Microservices`, 3 credits).
    - *Overlap Score*: `88% Syllabus Overlap` (Passes 80% threshold).
    - *Exemption Status*: `Eligible for Credit Exemption`.
- **Transfer Application Button**:
  - Action to submit formal credit transfer request to Faculty Academic Committee for Senate endorsement.

---

### Screen 6: Course Registrations & Payment Status (`student/enrolments.html`)
*Equivalent to `student/enrolments.blade.php` and `student/enrolment.blade.php` in live system.*
- **Header**: `Course Registrations & Cohort Details`.
- **Status Filter Pills**: `All Registrations`, `Enrolled`, `Completed`, `Pending Payment`.
- **Registration Cards**:
  - Cohort schedule, meeting link, start date, payment receipt download, and withdrawal options compliant with institutional refund timelines.

---

## 5. Visual Styling & Component Token Enforcements

All student pages will be built strictly with our established tokens:

| Element | Token / Rule | Visual Treatment |
|:---|:---|:---|
| **Card Borders** | `var(--color-card-border, #090a0f)` | 1px stark black outline for high-definition geometry |
| **Sidebar Canvas** | `var(--color-sidebar-bg, #0b1a3d)` | Deep Cobalt Navy with 28px corner curvature and 24px pull tab |
| **Topbar Canvas** | `var(--color-header-bg, #ffffff)` | Pure white sticky bar with hairline divider |
| **Primary Actions** | `var(--color-primary, #0052ff)` | Electric cobalt buttons with smooth transitions |
| **Stepper Flow** | `.linear-stepper` | Continuous horizontal track line, black checkmark nodes, glowing active node |
| **Tags & Badges** | `.tag` | Clean text badges without square brackets (`UMC`, `FTMK`, `MQF 6`, `P8`) |
| **Progress Bars** | `.progress-bar` | Thin 6px rounded bar with cobalt fill and percent readout |
| **Typography** | `Plus Jakarta Sans` / `JetBrains Mono` | High legibility fallback stack, mono numerals for codes and dates |

---

## 6. Directory Structure & Deliverables

All deliverables will be written strictly under `UIUX/v3/student/`:

```
UIUX/v3/
├── student/
│   ├── dashboard.html             # STU-01: Main Student Learning Hub & Action Queue
│   ├── wallet.html                # STU-02: Digital Credential Wallet & Badges
│   ├── credit-ledger.html         # STU-03: Credit Ledger & ABC Consent Workflow
│   ├── results.html               # STU-04: Academic Results & CLO Competencies
│   ├── pathway-plan.html          # STU-05: Degree Stacking Plan & Equivalence Matrix
│   └── enrolments.html            # STU-06: Course Enrollments & Cohort Schedules
├── css/
│   ├── student.css                # Dedicated student app-shell, sidebar & dashboard styles
│   ├── tokens.css                 # Shared light tokens
│   ├── components.css             # Shared cards, buttons, linear stepper
│   └── pages.css                  # Shared layouts
└── docs/
    └── STUDENT_DESIGN_PLAN.md     # This specification document
```

---

## 7. Next Steps Upon Approval

Once you approve this design plan, I will immediately:
1. Create `UIUX/v3/css/student.css` defining the logged-in app shell, sidebar navigation, pull tab, and dashboard widgets.
2. Build the generator script `scratch/generate_student_pages.js` to construct all 6 student HTML pages with rich, realistic institutional data.
3. Update `UIUX/v3/index.html` and `UIUX/v3/docs/COVERAGE_MATRIX.csv` to seamlessly link the Student Portal.
4. Run Microsoft Edge Playwright capture to generate verified desktop, tablet, and mobile screenshots.
