# UTeM MCMS UI/UX Design Reference (v3)

Official static design deliverable for the **UTeM Micro-Credential Management System (MC-ABC)**.

- **Authority Context**: Derived from [`MC UTeM Guide.pdf`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/MC%20UTeM%20Guide.pdf) (Garis Panduan Micro-Credential UTeM v1.0, 18 Sept 2026) and [`design-samples/linear-precision/DESIGN.md`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/design-samples/linear-precision/DESIGN.md) (v2.4 "linear-precision" light theme).
- **Scope**: Public portal pages and Authentication / Sign-in flow (including all error, MFA, and recovery states).
- **Format**: Static HTML5 + CSS3 + Vanilla JavaScript. 100% offline; zero external CDN, remote font, or third-party runtime dependencies.

---

## 1. How to Open and Review the Design

Simply open [`index.html`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/UIUX/v3/index.html) in any modern web browser:

```bash
# Direct file open (Windows Edge / Chrome)
start UIUX/v3/index.html
```

All internal navigation, breadcrumbs, search redirection, and persona modal triggers use relative paths.

---

## 2. Directory Structure

```
UIUX/v3/
├── index.html                           # Master Deliverables & Design System Index
├── assets/                              # Local institutional marks & imagery
│   ├── LogoJawiUTeM_blue-01.png         # Official UTeM Jawi & Roman logo (Header brand)
│   ├── LogoUTeM_crest.png               # UTeM Institutional Crest (Official transcripts)
│   ├── CODL_logo.png                    # Centre for Open & Distance Learning logo (Footer)
│   └── Pic-2-4.jpg                      # Campus photographic hero background (Sign-In)
├── css/
│   ├── tokens.css                       # Light theme design tokens mapped 1:1 to DESIGN.md
│   ├── base.css                         # Architectural base, custom scrollbar, layout reset
│   ├── components.css                   # Header, search pill, buttons, cards, tags, stepper
│   └── pages.css                        # Page-specific grids, sign-in split, print layouts
├── js/
│   └── ui.js                            # Pure vanilla JS (modal, filtering, FAQ tabs, search)
├── public/                              # 17 Public portal screens and states
│   ├── home.html                        # PUB-01: Public Portal Landing
│   ├── catalogue.html                   # PUB-02: Micro-Credential Search & Filters
│   ├── catalogue-empty.html             # PUB-02-E: Zero Results Search State
│   ├── offering.html                    # PUB-03: Offering Detail (Syllabus, CLOs, Claims)
│   ├── pathways.html                    # PUB-04: Stackable Degree Pathways Explorer
│   ├── pathway-print.html               # PUB-04-P: Printable Articulation Advisory Sheet
│   ├── how-it-works.html                # PUB-05: 8-Stage Credit Recognition Explainer
│   ├── faq.html                         # PUB-06: Interactive Accordion FAQ
│   ├── verify.html                      # PUB-07: Public Verification Hub (Code & File Upload)
│   ├── verify-result-valid.html         # PUB-08-A: Result - Authentic & Valid
│   ├── verify-result-superseded.html    # PUB-08-B: Result - Superseded / Corrected
│   ├── verify-result-revoked.html       # PUB-08-C: Result - Revoked Credential
│   ├── verify-result-badge.html         # PUB-08-D: Result - Module Digital Badge
│   ├── verify-result-not-found.html     # PUB-08-E: Result - 404 Credential Not Found
│   ├── policy-privacy.html              # PUB-09: Privacy Notice (PDPA 2010)
│   ├── policy-terms.html                # PUB-10: Terms of Use
│   └── policy-accessibility.html        # PUB-11: WCAG 2.1 AA Accessibility Statement
├── auth/                                # 11 Authentication and Sign-In states
│   ├── signin-default.html              # AUTH-01: Default Sign-In (SSO + Test Personas)
│   ├── signin-validation-error.html     # AUTH-02: Missing Field Validation Error
│   ├── signin-wrong-credentials.html    # AUTH-03: SSO Authentication Failure
│   ├── signin-account-locked.html       # AUTH-04: Account Refused / Deactivated
│   ├── signin-forgot-password.html      # AUTH-05: Office 365 Password Recovery Guidance
│   ├── signin-reset-confirmation.html   # AUTH-06: Password Reset Dispatched Confirmation
│   ├── signin-mfa-entry.html            # AUTH-07: 6-Digit MFA Authenticator Entry
│   ├── signin-mfa-failed.html           # AUTH-08: Invalid MFA Code Error
│   ├── signin-session-expired.html      # AUTH-09: Session Security Inactivity Timeout
│   ├── signin-unauthorised.html         # AUTH-10: 403 Forbidden / Role Permission Denied
│   └── signin-signed-out.html           # AUTH-11: Signed Out Confirmation
└── docs/                                # Technical Documentation & Verification Audit
    ├── IMPLEMENTATION_INVENTORY.md      # Phase 1 code audit & route inventory
    ├── DESIGN_PLAN.md                   # Phase 2 component specifications & conflict log
    ├── COVERAGE_MATRIX.csv              # Phase 4 88-item traceability matrix
    ├── SCREENSHOTS_MANIFEST.md          # Multi-viewport screenshot verification log
    └── screenshots/                     # 87 automated full-fidelity captures (Edge)
```

---

## 3. What is Covered vs. Proposed

### A. Fully Covered from Live Implementation (`COVERED`)
- All 11 public portal routes from [`app/app/Modules/Offering/CatalogueController.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php) and [`app/app/Modules/Credentials/VerifyController.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Credentials/VerifyController.php).
- Exact offering syllabus fields, CLO MQF domain alignments, and assessment integrity mechanisms.
- All 5 credential verification outcomes (`valid`, `superseded`, `revoked`, `badge`, `not_found`).
- Dual verification mechanisms: Method 1 (10-character code) and Method 2 (Cryptographic file signature against Ed25519 public key).
- Inactive account refusal notice: `"This account is deactivated. Ask the CODL administrator."` (`StubSignInController.php:35`).
- Staff 6-digit MFA validation rules: `regex:/^\d{6}$/` with error `"Enter the 6-digit code."` (`StubSignInController.php:58`).
- All 9 active roles (`[P1]` to `[P8]` plus External Industry Reviewer) represented in the persona modal.

### B. Additions Derived from Institutional Rules (`PROPOSED`)
- **8-Stage Credit Lifecycle Tracker (`PUB-05`)**: Expands the live system's 5-stage student perspective into the full 8 governance milestones mandated by `MC UTeM Guide.pdf` (Appendix H & SDD Section 4).
- **Extended Auth States**: Formulated standard SSO lifecycle states (`signin-wrong-credentials.html`, `signin-forgot-password.html`, `signin-reset-confirmation.html`, `signin-session-expired.html`, `signin-unauthorised.html`).

### C. Explicitly Dropped Features & Design Rules (Per User Directives)
- **Prohibition of Redundant Context Blocks**: Never include redundant explanatory blocks (e.g. `"Where is this?"`, `"What happens next?"`, `"Who is responsible?"`) or redundant section markers (e.g. `[F1]`, `[F2]`, `[F3] VERIFICATION`) that state obvious UI context.
- **Clean Badges without Brackets**: Never use square brackets `[...]` around tags, badges, buttons, or faculty labels (e.g., use `FTMK`, `MQF 6`, `UMC`, `SAMC`, `Sign In` &mdash; not `[FTMK]`, `[MQF 6]`, `[Sign In]`).
- **Inline Single-Level Header Navigation**: Header brand logo, `UTeM MCMS`, clean navigation links (`Home`, `Catalogue`, `Pathways`, `Recognition Stages`, `FAQ`, `Verify`), and `Sign In` button sit on a single horizontal line. No numbers in nav links and no header search input.
- **Card Borders**: 1px solid black border (`#090a0f`) for crisp, high-definition contrast.
- **Spelling Standard**: Institutional spelling standard is `"enroll"` / `"enrollment"` (double 'l').
- **Language Switcher (`EN | BM`)**: Excluded per instruction (English only).
- **Dark Theme Toggle**: Excluded per instruction (Light theme only).
- **Left Navigation Drawer**: Excluded on public & sign-in pages (adapted to flush brand topbar).

---

## 4. Missing Assets (To Be Supplied)

- **Typography**: Local font files for `Plus Jakarta Sans` and `JetBrains Mono` (`.woff2` or `.ttf`) were not present in the repository. Per safety guidelines, no external fonts were fetched. The system uses the high-precision system fallback stack (`-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; "JetBrains Mono", Menlo, Consolas, monospace`). Users may place `.woff2` files into `assets/fonts/` if offline webfonts are desired.

---

## 5. How v3 Differs from Earlier Versions

1. **Light Theme Precision vs. Flat Carbon**: Supersedes the earlier v1/v2 0px flat Carbon theme with the approved **linear-precision** design language: pure white header with single-level inline navigation, controlled micro-radii (`2px`, `4px`, `6px`, `8px`), 1px solid black card outlines (`#090a0f`), and hairline dividers.
2. **Prohibition of Generic Icons & Excessive Brackets**: Replaces all generic icon libraries with clean typographic text tags without square brackets (`FTMK`, `MQF 6`, `UMC`, `SAMC`, `Sign In`).
3. **Institutional Branding & Campus Photography**: Sign-in page features a richly visible 60% campus photo (`Pic-2-4.jpg`), the real official UTeM mark (`LogoJawiUTeM_blue-01.png`), and a transparent, enlarged CODL mark (`CODL_logo.png`).
4. **Strict Compliance with Prohibited Claims**: Strictly implements Cl. 10.3.1 and Appendix E5/E10 of `MC UTeM Guide.pdf` across all public copy: never claims guaranteed credit transfer or automatic admission; explicitly establishes that ABC is storage only.
5. **WCAG 2.1 AA Contrast Enforcement**: Strictly restricts `#94a3b8` (ink-subtle, 2.6:1 contrast) to non-text decorative lines. All body text uses `#090a0f` (18:1) or `#475569` (4.6:1). All hero buttons feature high contrast (`Explore Degree Pathways →` rendered as solid white button on deep navy).
