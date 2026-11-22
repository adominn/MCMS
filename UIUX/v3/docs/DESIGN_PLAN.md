# UTeM MCMS UI/UX Design Reference (v3) - Design Plan
**Deliverable**: Static UI/UX Design System & Public/Auth Reference  
**Design Authority**: `DESIGN.md` (v2.4 "linear-precision") in `design-samples/linear-precision/DESIGN.md`  
**Regulatory Authority**: `MC UTeM Guide.pdf` (Garis Panduan Micro-Credential UTeM v1.0, 18 Sept 2026)  
**Output Directory**: `C:\Users\Adomin\Documents\Intern\CODL\MC-ABC System - Antigravity 6\UIUX\v3`  
**Status**: DRAFT FOR USER APPROVAL (Phases 1 & 2 complete; Phase 3 Build paused until approved)

---

## 1. Sitemap of Public Area and Sign-In Flow

```
UIUX/v3/
├── index.html                           [Design System Index & Deliverables Hub]
├── public/
│   ├── home.html                        [PUB-01: Public Portal Landing]
│   ├── catalogue.html                   [PUB-02: Micro-Credential Search & Filters]
│   ├── catalogue-empty.html             [PUB-02-E: Search Empty Results State]
│   ├── offering.html                    [PUB-03: Offering Detail (Syllabus, CLOs, Claims)]
│   ├── pathways.html                    [PUB-04: Stackable Degree Pathways Explorer]
│   ├── pathway-print.html               [PUB-04-P: Printable Articulation Advisory]
│   ├── how-it-works.html                [PUB-05: 8-Stage Credit Lifecycle Explainer]
│   ├── faq.html                         [PUB-06: Interactive Accordion FAQ]
│   ├── verify.html                      [PUB-07: Public Verification Hub (Code & File Upload)]
│   ├── verify-result-valid.html         [PUB-08-A: Result - Authentic & Valid]
│   ├── verify-result-superseded.html    [PUB-08-B: Result - Superseded / Corrected]
│   ├── verify-result-revoked.html       [PUB-08-C: Result - Revoked Credential]
│   ├── verify-result-badge.html         [PUB-08-D: Result - Module Digital Badge]
│   ├── verify-result-not-found.html     [PUB-08-E: Result - 404 Credential Not Found]
│   ├── policy-privacy.html              [PUB-09: Privacy Notice (PDPA 2010)]
│   ├── policy-terms.html                [PUB-10: Terms of Use]
│   └── policy-accessibility.html        [PUB-11: WCAG 2.1 AA Accessibility Statement]
└── auth/
    ├── signin-default.html              [AUTH-01: Default Sign-In (SSO + Test Personas Modal)]
    ├── signin-validation-error.html     [AUTH-02: Form Validation Error State]
    ├── signin-wrong-credentials.html    [AUTH-03: SSO Authentication Failure]
    ├── signin-account-locked.html       [AUTH-04: Rate-limited / Deactivated Account]
    ├── signin-forgot-password.html      [AUTH-05: Office 365 Password Reset Guidance]
    ├── signin-reset-confirmation.html   [AUTH-06: Password Reset Dispatched Confirmation]
    ├── signin-mfa-entry.html            [AUTH-07: 6-Digit Authenticator Code Entry]
    ├── signin-mfa-failed.html           [AUTH-08: MFA Verification Code Error]
    ├── signin-session-expired.html      [AUTH-09: Inactivity Timeout / Session Expired]
    ├── signin-unauthorised.html         [AUTH-10: 403 Forbidden / Role Permission Denied]
    └── signin-signed-out.html           [AUTH-11: Successful Sign-Out Confirmation]
```

---

## 2. Page-by-Page Structure & Real Data Field Mapping

### 2.1. Public Landing (`public/home.html`)
- **Layout Grid**: 12-column precision grid (`max-width: 1280px`, `1px hairline` borders with corner `+` crosshairs).
- **Sections in Order**:
  1. `[C1]` **Brand Command Header**: Pure white container (`28px` bottom-right radius) with UTeM logo, portal title, floating quick-search pill, and `[Sign In]` action.
  2. `[C2]` **Public Navigation Ribbon**: 6 mono-numbered links (`01 Home` to `06 Verify`).
  3. `[F1]` **Hero Banner**: High-contrast slate-50 (`#f1f5f9`) container with 2px electric cobalt accent indicator, institutional eyebrow `// UNIVERSITI TEKNIKAL MALAYSIA MELAKA`, display headline (`Micro-Credential Management System`), and search pill launcher.
  4. `[F2]` **Three Categorical Architecture Tiles (Bento Grid)**:
     - `UMC` (Unbundled): Real fields `name_en`, `proposed_credit`, `Credit Status: Yes, via official ACR record`.
     - `SAMC` (Standalone): Real fields `discipline`, `Credit Status: Only with valid Quality Verification (QV)`.
     - `MCE` (Executive): Real fields `competency_statement`, `Credit Status: None (Certificate of Competency)`.
  5. `[F3]` **Upcoming Cohorts Strip**: Real fields `cohort_name`, `start_date`, `end_date`, `fee`, `delivery_mode`, `capacity`, `ptj_name`.
  6. `[F4]` **Institutional Verification Quick-Check**: Minimal verification code input directing to `public/verify.html`.
  7. `[F5]` **Institutional Footer**: 4-column structured footer with CODL logo, MQA compliance declaration.

### 2.2. Micro-Credential Catalogue (`public/catalogue.html` & `catalogue-empty.html`)
- **Layout Grid**: 2-column sidebar-content layout (280px filter panel + fluid results table/cards).
- **Sections in Order**:
  1. `[C1]` Brand Header & `[C2]` Nav Ribbon.
  2. `[F1]` **Orientation Strip**: `[LOCATION: PUBLIC CATALOGUE]`, `[ACTION: BROWSE & FILTER]`, `[GOVERNANCE: MQA MQF COMPLIANT]`.
  3. `[F2]` **Filter Sidebar**:
     - Text input `q` (Keyword search: name, code, or faculty)
     - Dropdown `category` (`All Categories`, `UMC`, `SAMC`, `MCE`)
     - Dropdown `ptj` (Faculty list: `FTMK`, `FKE`, `FKP`, `FKM`, `FTKEE`, `FTKIP`, `FPTT`, `CODL`)
     - Dropdown `mode` (`Online`, `Blended`, `Conventional`)
     - Text input `field` (Discipline)
     - Date picker `starts` (Earliest start date)
     - Number input `fee_max` (Maximum fee)
     - Checkbox `free` (Free offerings only)
     - Reset action `[Clear Filters]`
  4. `[F3]` **Results Header & Counter**: Real field `Showing :count micro-credentials`.
  5. `[F4]` **Offering Cards / Table**: Real fields `cohort_name`, `v.name_en`, `m.category`, `p.code`, `v.proposed_credit`, `o.delivery_mode`, `o.fee` (`Free` or `RM :amount`).
  6. `[F5]` **Empty State (`catalogue-empty.html`)**: Clear zero-data card with suggested filter reset.
  7. `[F6]` **Pagination**: Real 5-item page increments with precision controls.

### 2.3. Offering Detail (`public/offering.html`)
- **Layout Grid**: 8-column main content + 4-column metadata summary card.
- **Sections in Order**:
  1. `[C1]` Brand Header & `[C2]` Nav Ribbon.
  2. `[F1]` **Breadcrumbs**: `Home / Catalogue / [Offering Name]`.
  3. `[F2]` **Header Ribbon**: Offering Title (`v.name_en`), Code, Faculty tag (`p.code`), Category tag (`[UMC]`).
  4. `[F3]` **Key Facts Matrix**: MQF Level (`v.proposed_mqf_level`), Credits (`v.proposed_credit`), Student Learning Time (`v.slt_hours` SLT), Delivery Mode (`o.delivery_mode`), Fee (`o.fee`), Delivery Language (`v.delivery_language`).
  5. `[F4]` **Quality & Credit Recognition Notice**: Mandatory statutory distinction (Guide Cl. 10.3.1).
  6. `[F5]` **Syllabus & Modules**: Sequence, Module title, SLT breakdown, staged completion indicator.
  7. `[F6]` **Course Learning Outcomes (CLOs)**: Outcome description and MQF domain mapping.
  8. `[F7]` **Assessment & Integrity Standards**: Evaluation rubric, invigilation standard, identity verification.
  9. `[F8]` **Enrollment / Application CTA**: Schedule dates, capacity counter, action button.

### 2.4. Degree Pathways Explorer (`public/pathways.html` & `pathway-print.html`)
- **Layout Grid**: Fluid responsive cards with tabular articulation mapping.
- **Sections in Order**:
  1. `[C1]` Header & `[C2]` Nav Ribbon.
  2. `[F1]` **Degree Articulation Overview**: Target programs (`Bachelor of Computer Science`, etc.), max 30% credit transfer statutory notice.
  3. `[F2]` **Pathway Articulation Table**: Micro-credential code, title, credits, equivalent degree course code, syllabus match percentage.
  4. `[F3]` **Action Bar**: `[Print Pathway Advisory]` button opening printable sheet.
  5. `[F4]` **Print Sheet (`pathway-print.html`)**: Formal institutional layout with UTeM crest, curriculum mapping tables (G4 PLO, G5 BoK, G8 Remaining Courses, G9 Balance, G11 Signatures).

### 2.5. Recognition Stages & How It Works (`public/how-it-works.html`)
- **Layout Grid**: 12-column grid featuring the new `lifecycle-tracker` component.
- **Sections in Order**:
  1. `[C1]` Header & `[C2]` Nav Ribbon.
  2. `[F1]` **8-Stage Credit Lifecycle Tracker**: Two-digit mono numerals (`01` to `08`) arranged horizontally across the 4 governance milestones.
  3. `[F2]` **Milestone Breakdown Cards**:
     - Stage 01: Completion Confirmed (Passing grade confirmed by coordinator)
     - Stage 02: Eligibility Checked (PTj Authority checks prerequisites and compliance)
     - Stage 03: ACR Issued & Confirmed (Academic Credential Record signed by Records Custodian)
     - Stage 04: Participant Consent (Explicit learner consent to deposit record)
     - Stage 05: Deposited in ABC (Stored in Academic Bank of Certificate; storage only)
     - Stage 06: Transfer Applied (Learner submits application to receiving institution)
     - Stage 07: Equivalence Evaluated (Faculty curriculum committee evaluates syllabus overlap)
     - Stage 08: Decided & Redeemed (Senate approves or rejects exemption; credits redeemed once)
  4. `[F3]` **Prohibited Claims Statutory Warning**: Appendix E5/E10 callout box: "Storage is not transfer. Micro-credentials do not automatically confer university admission."

### 2.6. Frequently Asked Questions (`public/faq.html`)
- **Layout Grid**: Left vertical category nav (4 categories) + right content area with `<details>` accordions.
- **Sections in Order**:
  1. `[C1]` Header & `[C2]` Nav Ribbon.
  2. `[F1]` Category Selector: `Badges & credentials`, `Credit & ACR`, `Registering & fees`, `Verification & privacy`.
  3. `[F2]` Interactive Accordion Q&As (all 13 live questions).
  4. `[F3]` Support Contact Card: Direct mailto link to `codl@utem.edu.my`.

### 2.7. Credential Verification Hub (`public/verify.html`)
- **Layout Grid**: 2-column comparison layout (Method 1: Code Search vs Method 2: File Upload).
- **Sections in Order**:
  1. `[C1]` Header & `[C2]` Nav Ribbon.
  2. `[F1]` Method 1: Public Code Entry (Search box for `CND01-7K9MJ` or badge code).
  3. `[F2]` Method 2: Cryptographic File Verification (`.json`, `.jwt`, `.txt` file upload).
  4. `[F3]` Cryptographic Explanation: Ed25519 digital signatures, W3C Verifiable Credentials 2.0, Open Badges 3.0 standard.

### 2.8. Verification Results (`public/verify-result-*.html`)
- **Layout Grid**: Centered verification dossier (`max-width: 960px`).
- **5 Distinct Outcome Pages**:
  - `verify-result-valid.html`: Valid credential, green status tag `[STATUS: VALID]`, full details, W3C JSON-LD download, JWT download, print transcript.
  - `verify-result-superseded.html`: Superseded credential, yellow status tag `[STATUS: SUPERSEDED]`, explanation that credential was reissued, link to new code.
  - `verify-result-revoked.html`: Revoked credential, red status tag `[STATUS: REVOKED]`, revocation timestamp, revocation justification.
  - `verify-result-badge.html`: Digital badge result for modular completion, blue status tag `[STATUS: DIGITAL BADGE]`.
  - `verify-result-not-found.html`: 404 Credential Not Found, red status tag `[STATUS: NOT FOUND]`, retry search form.

### 2.9. Sign-In & Authentication States (`auth/signin-*.html`)
- **Layout Grid**: 60/40 Split View (60% Campus Hero panel with `#0b1a3d` cobalt navy overlay + 40% White Sign-In Card with `28px` curve).
- **11 Distinct States**:
  1. `signin-default.html`: Primary Microsoft Entra ID button (disabled) + Prototype Test Personas modal trigger.
  2. `signin-validation-error.html`: User selection error (`The user field is required.`).
  3. `signin-wrong-credentials.html`: SSO token rejected / invalid login.
  4. `signin-account-locked.html`: Rate limit throttling ("Too many failed sign-in attempts. Try again in 15 minutes.") AND Deactivated account error ("This account is deactivated. Ask the CODL administrator.").
  5. `signin-forgot-password.html`: Office 365 self-service reset instructions.
  6. `signin-reset-confirmation.html`: Reset email dispatched screen.
  7. `signin-mfa-entry.html`: 6-digit numeric authenticator entry for staff roles.
  8. `signin-mfa-failed.html`: "Enter the 6-digit code" validation error / invalid token.
  9. `signin-session-expired.html`: Session timeout banner with re-authentication prompt.
  10. `signin-unauthorised.html`: 403 Forbidden screen ("Role not authorized for this workspace").
  11. `signin-signed-out.html`: Successful sign-out confirmation with link back to public home.

---

## 3. Component List Mapped to DESIGN.md v2.4 Tokens

| Component | DESIGN.md Token / Radius Scale | Implementation & Visual Style |
|:---|:---|:---|
| **Public Header** | `canvas: #ffffff`, `radius: 0 0 28px 0` | Pure white bar flush to top, `28px` bottom-right radius, 1px `#e2e8f0` border bottom. |
| **Nav Ribbon** | `surface-1: #f1f5f9` or `sidebar-bg: #0b1a3d` | Horizontal tab bar with 1px border, 2px electric cobalt accent indicator on active item. |
| **Search Pill** | `search-pill: 12px`, `height: 42px`, `#ffffff` | Floating white container, 1px `#e2e8f0` border, `13px` input, text `[Search]` submit button. |
| **Buttons (Primary)** | `primary: #0052ff`, `radius: 4px` (sm) | High-contrast electric cobalt button, `#ffffff` text, hover `#0045d8`, pressed `#0036a8`. |
| **Buttons (Secondary)** | `surface-1: #f1f5f9`, `radius: 4px` (sm) | Cool slate resting state, 1px `#e2e8f0` border, `#090a0f` ink text. |
| **Inputs / Selects** | `surface-2: #e2e8f0` (border), `radius: 4px` | White background, 1px `#e2e8f0` hairline border, focus ring `2px solid #0052ff`. |
| **Section Tags** | `font-mono`, `radius: 2px` (xs) | Square-bordered tags e.g. `[F1]`, `[F2]`, uppercase 11px mono, 1px hairline border. |
| **Role Tags** | `font-mono`, `radius: 2px` (xs) | Square-bordered role tags e.g. `[P1]`, `[P2]`, `[P8]`. |
| **Status Pills** | `pill: 9999px` (status pips only) | 8px circular indicator pip paired with explicit text prefix: `[STATUS: VALID]`. |
| **Cards / Bento Cells** | `md: 6px`, `surface: #ffffff`, `border: 1px` | 1px hairline border (`#e2e8f0`), zero shadow, optional crosshair `+` at grid corners. |
| **Hero Containers** | `lg: 8px` / `28px` contour | Slate-50 wash or deep cobalt navy `#0b1a3d` container with 2px accent indicator. |
| **Alerts / Banners** | `radius: 4px`, `border-left: 3px` | Semantic banners: Info (`#0052ff`), Success (`#24a148`), Warning (`#f1c21b`), Error (`#da1e28`). |
| **Orientation Strip** | `font-mono: 11px`, `surface-1: #f1f5f9` | 3-column micro-bar: `[Where is this?]`, `[What happens next?]`, `[Who is responsible?]`. |
| **Lifecycle Tracker** | `font-mono`, `hairline: #e2e8f0` | 8-step horizontal stepper with two-digit numerals (`01` to `08`) and state indicators. |
| **Print Audit Table** | `font-mono: 12px`, 1px stark hairline | High-density black-and-white table for formal pathway advisories and transcripts. |

---

## 4. Resolution of Known Conflicts

### Conflict 1: SVG Icons vs "No Generic Icons / Typographic Markers" (D-53)
**Rule**: No generic icon libraries, no emoji, no arbitrary SVGs.
**Replacements for all elements in DESIGN.md**:
1. *Sidebar nav icons (grid, sliders, T, capsule, arc, check, queue)*: Replaced with two-digit mono section tags and text labels (e.g. `[01] ARCHITECTURE`, `[02] COMPONENTS`).
2. *Magnifying-glass SVG in search pill*: Replaced with a clean text label `Search catalogue...` and a typographic action button `[Find]`.
3. *Chevrons on categories / dropdowns*: Replaced with ASCII typographic markers `[+]` (collapsed) and `[-]` (expanded) or `v` / `^`.
4. *Pull-tab latch chevron*: (Applies to logged-in shell, but for any toggle) replaced with `<` and `>`.
5. *Checkmark icons on success banners / status tags*: Replaced with `[OK]` or `[STATUS: VALID]`.
6. *Close / Dismiss 'X' icon*: Replaced with `[Close]` or `[X]` character.

### Conflict 2: Adapting DESIGN.md Language to Drawer-less Public & Sign-In Pages
1. **Public Header**:
   - Uses the white brand header container from DESIGN.md with `28px` bottom-right radius docking flush to the top-left viewport.
   - Houses the official `Asset/LogoJawiUTeM_blue-01.png` logo (centered/proportional, max-height 64px).
   - Incorporates the floating search pill (`12px` radius, `#ffffff`, 1px `#e2e8f0` border) directly in the header bar.
2. **Public Footer**:
   - Anchors the page with a deep cobalt navy (`#0b1a3d`) panel, matching the sidebar background token, with 4 structured columns and CODL logo.
3. **Sign-In Layout**:
   - Employs a 60/40 viewport split:
     - Left: 60% width deep cobalt navy (`#0b1a3d`) hero with `Asset/Pic-2-4.jpg` background overlay, UTeM brand headline, and CODL institutional emblem.
     - Right: 40% width pure white (`#ffffff`) card with `28px` rounded contour docking against the hero, hosting the sign-in forms.

### Conflict 3: The "⌘K" Shortcut
- In the public live system, there is no keyboard shortcut handler for ⌘K.
- **Resolution**: Omit the `⌘K` badge on public pages to avoid misleading the user. In the public header, the search pill displays a clean placeholder `Keyword, field or code...` and a submit button `[Find]`.

---

## 5. Explaining the 8-Stage Credit Lifecycle

Public pages (particularly `public/how-it-works.html` and `public/offering.html`) articulate the complete 8-stage institutional governance lifecycle established in `MC UTeM Guide.pdf` and Decision Log D-01 to D-59:

```
[ MILESTONE I: ACHIEVEMENT & CREDENTIAL ]
  01. Completion Confirmed    -> Learner completes syllabus and passes assessment standards.
  02. Eligibility Checked     -> Faculty / Deputy Dean verifies prerequisites and academic authenticity.

[ MILESTONE II: OFFICIAL RECORD (ACR) ]
  03. ACR Issued & Confirmed  -> Academic Credential Record formally minted and signed by Records Custodian.

[ MILESTONE III: NATIONAL REPOSITORY (ABC) ]
  04. Participant Consent     -> Learner explicitly grants informed consent to deposit credits.
  05. Deposited in ABC        -> Stored in Academic Bank of Credit for 10-year validity. (STORAGE ONLY).

[ MILESTONE IV: PROGRAM ARTICULATION & TRANSFER ]
  06. Transfer Applied        -> Learner formally applies for credit exemption into an admitted degree.
  07. Equivalence Evaluated   -> Faculty Curriculum Committee evaluates 80% syllabus & MQF domain match.
  08. Decided & Redeemed      -> University Senate formally approves credit transfer (Redeemed once).
```

### Read-Only Lifecycle Tracker Component Anatomy:
- **Phase Grouping Ribbon**: 4-phase macro bar aligning institutional phases (Phase I: Course Achievement, Phase II: Official Academic Record, Phase III: National ABC Repository, Phase IV: Degree Articulation).
- **Linear Stepper Flow**: Continuous horizontal track line with circular step nodes (`.step-node`):
  - Completed steps (black circle with `✓` checkmark).
  - Active step (electric cobalt blue circle with white numeral and luminous outer halo glow).
  - Upcoming steps (subtle outlined circle with mono step numerals).
  - Centered labels (`.step-label`) and metadata (`.step-meta`) underneath each node.
- **Scrollable Track Container**: Responsive horizontal track allowing smooth scroll on mobile and tablet without distortion.
- **Statutory Notice Callout**:
  > **Statutory Notice (UTeM Guide Cl. 10.3.1)**:  
  > Completion of a micro-credential does not guarantee credit transfer, course exemption, or university admission. Storage in the Academic Bank of Credit (ABC) is storage only; articulation is decided separately by the receiving institution upon formal evaluation.

---

## 6. Sign-In UX Design & Role Guidance

### 6.1. Official SSO vs Prototype Test Personas
- **Official Path**:
  - Prominent "Sign in with Microsoft" button (styled with institutional Microsoft color squares).
  - Clear label: `(not connected in this prototype build)`.
  - Explanatory credential guide:
    - Staff: `UserId@utem.edu.my`
    - Students: `MatricNo@student.utem.edu.my`
- **Prototype Demonstration Path**:
  - High-contrast button: `[Select a Test Persona (24 Accounts)]`.
  - Opens accessible dialog with live search input and role filter pills.
  - Submitting selects a persona and routes to MFA or landing workspace.

### 6.2. Post-Login Landing Destinations by Role

| Role | Role Label | MFA Required? | Post-Login Landing Route | Description |
|:---|:---|:---:|:---|:---|
| `student` | Student / Participant (P8) | No | `tasks` (My Learning) | Personal learner dashboard, enrolments, ACRs |
| `lecturer` | MC Coordinator / Lecturer (P1) | No | `tasks` | Course coordination, syllabus drafts, grading |
| `deputy_dean` | PTj / Faculty Authority (P2) | **Yes** | `tasks` | Faculty approvals, eligibility endorsements |
| `academic_authority`| Academic Authority / Senate (P3) | **Yes** | `approvals` | Senate review queue, curriculum approvals |
| `qa_officer` | Quality Assurance Officer (P4) | **Yes** | `monitoring` | Quality verification (QV) audits and renewal |
| `codl_admin` | CODL Platform Administrator (P5) | **Yes** | `users` | Platform settings, user accounts, audit logs |
| `records_custodian` | Records & Credential Custodian (P6) | **Yes** | `credentials.admin` | Digital credential minting, revoke/reissue |
| `abc_depositor` | Authorized ABC Depositor (P7) | **Yes** | `deposits` | National ABC batch XML/API transmissions |
| `industry_expert` | Industry Reviewer | No | `reviews` | Industry relevance external reviews |

---

## 7. Asset Usage Map & Missing Assets List

### 7.1. Asset Usage Map
All assets are copied into `UIUX/v3/assets/` and referenced relatively:

| Asset Source File | Target Path in v3 | Dimensions | File Size | Usage & Purpose | Alt Text |
|:---|:---|:---:|:---:|:---|:---|
| `Asset/LogoJawiUTeM_blue-01.png` | `assets/LogoJawiUTeM_blue-01.png` | 1530 x 890 | 84.9 KB | Public Header Brand mark & Sign-in card header | "Universiti Teknikal Malaysia Melaka" |
| `Asset/LogoUTeM_crest.png` | `assets/LogoUTeM_crest.png` | 560 x 560 | 25.6 KB | Verification result official seal & Pathway print sheet | "UTeM Institutional Crest" |
| `Asset/CODL_logo.png` | `assets/CODL_logo.png` | 328 x 152 | 72.9 KB | Public Footer brand mark & Sign-in hero bottom signature | "Centre for Open and Distance Learning" |
| `Asset/Pic-2-4.jpg` | `assets/Pic-2-4.jpg` | 2977 x 1300 | 629.7 KB | Sign-in left hero panel photographic background | "UTeM Campus View" |

### 7.2. Missing Assets List
1. **Local Typography Font Files**:
   - `Plus Jakarta Sans` (`.woff2` or `.ttf`)
   - `JetBrains Mono` (`.woff2` or `.ttf`)
   - *Status*: Not present in `Asset/` directory. Per constraints, we do not download external files at runtime. We declare the official fallback stack:
     - Primary: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`
     - Monospace: `ui-monospace, "JetBrains Mono", "IBM Plex Mono", Menlo, Consolas, monospace`
   - *Action for User*: User may supply `.woff2` font files to `UIUX/v3/assets/fonts/` if desired.

---

## 8. Responsive Behaviour & Accessibility Strategy

### 8.1. Responsive Breakpoints
- **Desktop (`> 1024px`)**: Full 12-column bento grids, split-view sign-in (60/40), horizontal navigation ribbon, dual-column verification methods.
- **Tablet (`768px - 1023px`)**: Collapsed filter drawer into top bar, stacked sign-in view (hero on top, card below), 2-column bento grids.
- **Mobile (`< 768px`)**: Single-column layout, horizontal scrollable tab navigation, stacked cards, full-width inputs, sticky mobile bottom action buttons.

### 8.2. WCAG 2.1 AA Accessibility & Contrast Compliance
1. **Contrast Ratio Rules**:
   - **Body text & paragraph copy**: Must use `#090a0f` (ink, 18:1 on white) or `#475569` (ink-muted, 4.6:1 on white). Both exceed the 4.5:1 AA threshold.
   - **`#94a3b8` (ink-subtle) Restriction**: `#94a3b8` on white has a contrast ratio of only ~2.6:1 (FAILS WCAG AA). **Strict Rule**: `#94a3b8` is forbidden for readable body copy, form labels, and critical status text. It is used exclusively for non-text graphical elements (1px card divider hairlines, disabled input borders, decorative corner crosshairs).
   - **Primary Cobalt (`#0052ff`)**: On white background, `#0052ff` has a contrast ratio of ~4.6:1 (Meets AA for regular text and UI components).
2. **Keyboard Navigation & Order**:
   - Logical tab flow from header brand -> search pill -> nav tabs -> main content -> footer.
   - Visible focus indicators: `outline: 2px solid #0052ff; outline-offset: 2px;` on all focusable inputs and buttons.
3. **Status Never by Colour Alone**:
   - Every status pip or colored tag is paired with an explicit typographic prefix:
     - `[STATUS: VALID]` (Green `#24a148`)
     - `[STATUS: SUPERSEDED]` (Yellow `#f1c21b`)
     - `[STATUS: REVOKED]` (Red `#da1e28`)
     - `[STATUS: DIGITAL BADGE]` (Cobalt `#0052ff`)
4. **Form Labels & Error Associations**:
   - Every input has an explicit `<label for="...">`.
   - Error summaries use `role="alert"` and `aria-live="polite"`.

---

## 9. Open Questions for User Approval

Before proceeding to Phase 3 (Build), please confirm or advise on the following items:

1. **Public Search Pill**:  
   We propose a clean, functional text input `Search catalogue...` with a typographic submit button `[Find]` and no fake `⌘K` badge (since no shortcut exists in the live public app). Does this meet your expectations?
2. **Typographic Markers vs Bespoke SVGs**:  
   We have replaced all chevrons, search icons, and checkmarks with typographic markers (`+`, `<`, `>`, `[Search]`, `[OK]`, `[STATUS: VALID]`). Are there any specific institutional icons you would like bespoke SVGs for, or shall we maintain 100% pure typographic markers?
3. **8-Stage Lifecycle Presentation**:  
   We mapped the live 5-stage student view to the full 8-stage institutional governance lifecycle from `MC UTeM Guide.pdf` using our new `lifecycle-tracker` component on `public/how-it-works.html`. Do you approve this presentation?
4. **Sign-In Split Ratio**:  
   For the sign-in screen, we have adapted the DESIGN.md navy/white palette into a 60% campus hero (deep cobalt navy) + 40% white card (with 28px docking curve). Does this layout align with your vision?

---
*End of Design Plan. Awaiting your approval to proceed to Phase 3 Build.*
