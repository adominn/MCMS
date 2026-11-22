# UTeM MCMS Implementation Inventory (v3 Reference)
**Generated**: 2026-11-20  
**Authority Context**: Read-only audit of the running Laravel application (`app/`) and official documents (`MC UTeM Guide.pdf`, `DESIGN.md` v2.4).  
**Scope**: Public pages and Sign-In flow only.

---

## Section A: Public Pages Inventory

Every route listed below is publicly accessible without user authentication (`@guest` mode in `app/resources/views/layouts/app.blade.php:222-228`).

---

### A.1. Public Landing / Home
- **Route**: `GET /` (redirects guest to `/signin` or `/public` per `StubSignInController.php:21`), `GET /public` (`public.home` per `CatalogueController.php:29`)
- **View File**: [`app/resources/views/public/home.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/home.blade.php#L1-L273)
- **Controller Action**: `App\Modules\Offering\CatalogueController@home` ([`CatalogueController.php:29-36`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L29-L36))
- **Purpose**: Welcomes prospective participants, introduces UTeM micro-credential categories (UMC, SAMC, MCE), provides quick search into the catalogue, showcases upcoming cohorts, and directs learners to verification and degree pathways.
- **Exact Data Fields Shown**:
  - `cohorts`: Collection of upcoming cohorts with fields: `o.id`, `o.cohort_name`, `o.start_date`, `o.end_date`, `o.fee`, `o.delivery_mode`, `o.status`, `o.capacity`, `v.name_en`, `v.name_ms`, `v.discipline`, `v.delivery_language`, `v.proposed_credit`, `m.category` (`umc`|`samc`|`mce`), `p.code as ptj`, `p.name as ptj_name`, `u.name as lecturer_name` (`CatalogueController.php:33-35`).
  - `faculties`: Collection of PTjs (`id`, `code`, `name`) (`CatalogueController.php:36`).
  - `count`: Total count of listed active cohorts (`CatalogueController.php:36`).
- **Filters / Search / Sort / Pagination**:
  - Quick Search Form (`home.blade.php:21-27`): Single text search input `name="q"` (`Keyword, field or code...`) submitting `GET` to `route('public.catalogue')`.
  - No pagination on home view; cohorts sorted by `o.start_date ASC` (`CatalogueController.php:33`).
- **Actions**:
  - Quick Search submit -> `public.catalogue?q=...` (`home.blade.php:27`)
  - Category CTA cards -> `public.catalogue?category=umc|samc|mce` (`home.blade.php:70,102,134`)
  - Direct offering view -> `public.offering/{id}` (`home.blade.php:215`)
  - Verification quick-check -> `public.verify` (`home.blade.php:243`)
  - Degree pathways link -> `public.pathways` (`home.blade.php:262`)
- **Empty & Error States**:
  - Empty Cohorts: `@forelse($cohorts as $c) ... @empty <div class="card p-4 text-center text-muted">No upcoming micro-credential cohorts currently open for enrollment.</div> @endforelse` (`home.blade.php:228-232`).
- **Visible Strings (English)**:
  - "Universiti Teknikal Malaysia Melaka" (`home.blade.php:10`)
  - "Micro-Credential Portal" (`home.blade.php:12`)
  - "Stackable, verifiable modular learning accredited by UTeM and aligned with the Malaysian Qualifications Framework (MQF)." (`home.blade.php:15`)
  - "Search offerings" (`home.blade.php:22`)
  - "Keyword, field or code..." (`home.blade.php:25`)
  - "Search Catalogue" (`home.blade.php:28`)
  - "UMC – Unbundled Micro-Credential" (`home.blade.php:48`)
  - "Credit Status: Yes, via official ACR record" (`home.blade.php:50`)
  - "SAMC – Standalone Micro-Credential" (`home.blade.php:80`)
  - "Credit Status: Only with valid Quality Verification" (`home.blade.php:82`)
  - "MCE – Micro-Credential Eksekutif" (`home.blade.php:112`)
  - "Credit Status: None (Certificate of Competency)" (`home.blade.php:114`)
  - "Upcoming Cohorts" (`home.blade.php:146`)
  - "Verify Credentials & Badges" (`home.blade.php:239`)

---

### A.2. Public Course Catalogue & Search
- **Route**: `GET /public/catalogue` (`public.catalogue`)
- **View File**: [`app/resources/views/public/catalogue.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/catalogue.blade.php#L1-L142)
- **Controller Action**: `App\Modules\Offering\CatalogueController@catalogue` ([`CatalogueController.php:38-60`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L38-L60))
- **Purpose**: Search, filter, inspect, and select micro-credential offerings across all faculties, modes, and categories.
- **Exact Data Fields Shown**:
  - Filter parameters `$f`: `q` (keyword), `category` (`umc`|`samc`|`mce`), `ptj` (faculty code), `field` (discipline), `mode` (`online`|`blended`|`conventional`), `language` (delivery language), `starts` (date), `fee_max` (max price), `free` (boolean free toggle).
  - Offering records `$rows`: Paginated collection (5 items per page) containing `o.id`, `o.cohort_name`, `o.start_date`, `o.end_date`, `o.fee`, `o.delivery_mode`, `o.capacity`, `v.name_en`, `v.name_ms`, `v.discipline`, `v.delivery_language`, `v.proposed_credit`, `v.proposed_mqf_level`, `m.category`, `p.code as ptj`, `p.name as ptj_name`, `u.name as lecturer_name` (`CatalogueController.php:52-57`).
- **Filters / Search / Sort / Pagination**:
  - Form attributes: `method="get"`, `data-auto-filter`, `data-target="#catalogue-results"`, `data-count="#catalogue-count"` (`catalogue.blade.php:14`).
  - Inputs: Text search (`q`), dropdown (`category`), dropdown (`ptj`), dropdown (`mode`), text (`field`), text (`language`), date (`starts`), number (`fee_max`), checkbox (`free`).
  - Sorting: Ordered by `o.start_date ASC` (`CatalogueController.php:52`).
  - Pagination: 5 records per page with standard links `{{ $rows->withQueryString()->links() }}` (`catalogue.blade.php:137`).
- **Actions**:
  - Filter change (auto-filters or submit button) -> reload `public.catalogue` with query parameters.
  - Reset filters -> `route('public.catalogue')` with no parameters.
  - Card click / "Details & Enrol" -> `route('public.offering', $r->id)` (`catalogue.blade.php:127`).
- **Empty & Error States**:
  - Empty search results: `@if($rows->isEmpty()) <div class="card p-5 text-center text-muted">No micro-credentials matched your search criteria. Try clearing filters or using different keywords.</div> @endif` (`catalogue.blade.php:132-135`).
- **Visible Strings (English)**:
  - "Catalogue" (`catalogue.blade.php:2`)
  - "Filters" (`catalogue.blade.php:14`)
  - "Keyword search" (`catalogue.blade.php:16`)
  - "Category" (`catalogue.blade.php:20`)
  - "All categories" (`catalogue.blade.php:22`)
  - "Faculty / PTj" (`catalogue.blade.php:28`)
  - "Delivery mode" (`catalogue.blade.php:36`)
  - "Discipline / Field" (`catalogue.blade.php:44`)
  - "Earliest start date" (`catalogue.blade.php:52`)
  - "Maximum fee" (`catalogue.blade.php:56`)
  - "Free offerings only" (`catalogue.blade.php:61`)
  - "Reset filters" (`catalogue.blade.php:65`)
  - "Showing :count micro-credentials" (`catalogue.blade.php:73`)
  - "Free" / "RM :amount" (`catalogue.blade.php:104`)

---

### A.3. Micro-Credential Offering Detail
- **Route**: `GET /public/offering/{id}` (`public.offering`)
- **View File**: [`app/resources/views/public/offering.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/offering.blade.php#L1-L348)
- **Controller Action**: `App\Modules\Offering\CatalogueController@offering` ([`CatalogueController.php:62-85`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L62-L85))
- **Purpose**: Authoritative public syllabus, learning outcomes, assessment integrity, credit claims, and enrollment entry point for an offering.
- **Exact Data Fields Shown**:
  - Offering info `$o`: `cohort_name`, `start_date`, `end_date`, `fee`, `delivery_mode`, `capacity`, `facilitators`, `assessment_standard`, `integrity_mechanism`, `staged_completion`, `enrolment_url`.
  - Micro-Credential Version `$v`: `name_en`, `name_ms`, `discipline`, `delivery_language`, `proposed_credit`, `proposed_mqf_level`, `statement_en`, `statement_ms`, `slt_hours`, `qv_status`, `qv_valid_until`.
  - Claims array `$claims` (`Recognition::claimsForPublic($o->version)`):
    - `credit`: credit value if eligible (`$claims['credit']`),
    - `mqf`: MQF level (`$claims['mqf']`),
    - `qv_until`: QV expiration date (`$claims['qv_until']`).
  - Learning Outcomes `$los`: array of CLOs with code, description, and MQF domain mapping (`offering.blade.php:120-142`).
  - Modules `$modules`: list of modular components with sequence, name, SLT hours, and assessment weight (`offering.blade.php:144-190`).
- **Filters / Search / Sort / Pagination**: None (single record detail view).
- **Actions**:
  - Enrol action: If `$o->enrolment_url` exists, external link; else internal sign-in/register redirect (`offering.blade.php:48-56`).
  - Breadcrumb navigation -> `public.catalogue` or `public.home`.
- **Empty & Error States**:
  - Invalid ID -> 404 abort (`CatalogueController.php:64`).
- **Visible Strings (English)**:
  - "Overview" (`offering.blade.php:100`)
  - "Learning Outcomes" (`offering.blade.php:118`)
  - "Modular Breakdown" (`offering.blade.php:145`)
  - "Assessment & Integrity Standards" (`offering.blade.php:195`)
  - "Credit and Academic Recognition Notice" (`offering.blade.php:230`)
  - "Credit transfer eligibility requires formal application and evaluation by the receiving institution. Micro-credentials do not automatically confer university admission or credit exemption." (`offering.blade.php:245-250`)

---

### A.4. Degree Pathways / Articulation Explorer
- **Route**: `GET /public/pathways` (`public.pathways`)
- **View File**: [`app/resources/views/public/pathways.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/pathways.blade.php#L1-L140)
- **Controller Action**: `App\Modules\Offering\CatalogueController@pathways` ([`CatalogueController.php:92-110`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L92-L110))
- **Purpose**: Allows learners and academic advisors to explore approved stackable articulation pathways from micro-credentials into UTeM undergraduate degree programmes.
- **Exact Data Fields Shown**:
  - Pathways collection `$paths`: `id`, `name_en`, `name_ms`, `faculty_code`, `target_degree_title`, `total_credits`, `max_transferable_credits` (capped at 30% per MQA regulations), `minimum_residency_credits`, `mapped_mc_count`.
  - Articulation items: mapped micro-credentials with course code equivalence, credit equivalence, and status (`pathways.blade.php:50-95`).
- **Actions**:
  - Select pathway -> inspect mapped components.
  - "Print Official Pathway Advisory" -> `route('public.pathways.print', $pathway->id)` (`pathways.blade.php:112`).
- **Visible Strings (English)**:
  - "Academic Degree Pathways" (`pathways.blade.php:10`)
  - "Explore stackable micro-credential pathways towards accredited UTeM degrees." (`pathways.blade.php:12`)
  - "Maximum Transferable Credit: 30% of total programme credits" (`pathways.blade.php:24`)
  - "Credit Transfer Notice: Storing credits in the Academic Bank of Credit (ABC) does not guarantee academic credit transfer. Exemption is subject to formal Senate evaluation." (`pathways.blade.php:32-36`)

---

### A.5. Printable Degree Pathway Advisory
- **Route**: `GET /public/pathways/{id}/print` (`public.pathways.print`)
- **View File**: [`app/resources/views/public/pathway-print.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/pathway-print.blade.php#L1-L249)
- **Controller Action**: `App\Modules\Offering\CatalogueController@pathwayPrint` ([`CatalogueController.php:112-215`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L112-L215))
- **Purpose**: Clean, formal print-optimised curriculum articulation sheet suitable for academic counselling and physical records.
- **Exact Data Fields Shown**:
  - Target program name, faculty code, total credit requirements, maximum stackable credits (`pathway-print.blade.php:20-45`).
  - G4: PLO contribution mapping (`pathway-print.blade.php:70-95`).
  - G5: Body of Knowledge (BoK) domain coverage (`pathway-print.blade.php:98-125`).
  - G8: Remaining curriculum components breakdown (`pathway-print.blade.php:130-165`).
  - G9: Credit summary balance (`pathway-print.blade.php:170-195`).
  - G11: Institutional signatories (Coordinator, Faculty Deputy Dean, QA Assessor) (`pathway-print.blade.php:200-240`).

---

### A.6. How Credit Recognition Works
- **Route**: `GET /public/how` (`public.how`)
- **View File**: [`app/resources/views/public/how.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/how.blade.php#L1-L95)
- **Controller Action**: `App\Modules\Offering\CatalogueController@how` ([`CatalogueController.php:87-90`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L87-L90))
- **Purpose**: Explicitly details the recognition stages and disclaims prohibited claims (Appendix E5/E10, Cl. 10.3.1).
- **Exact Data Fields Shown**:
  - 5-stage sequential progression strip: (1) Module badge, (2) Credential, (3) ACR, (4) ABC deposit, (5) Credit transfer (`how.blade.php:18-24`).
  - Detailed card breakdown per stage with "What it is" and "What it is not" (`how.blade.php:40-80`).
- **Visible Strings (English)**:
  - "Recognition stages" (`how.blade.php:12`)
  - "Five stages. Each one is decided separately and recorded." (`how.blade.php:13`)
  - "Each stage is a separate decision. One does not give you the next." (`how.blade.php:27`)
  - Stage 1: "Module badge" - "A badge for each module you pass... It is not a qualification and carries no credit by itself." (`how.blade.php:31`)
  - Stage 2: "Certificate or credential" - "A signed, verifiable record that you completed the whole micro-credential... It is not proof that credit has been recorded or accepted anywhere." (`how.blade.php:32`)
  - Stage 3: "ACR (Academic Credit Record)" - "UTeM confirms the credit you earned... It is not a transfer, and an MCE never has one. Storing is not transferring." (`how.blade.php:33`)
  - Stage 4: "ABC deposit" - "With your consent, the credit is deposited in the Academic Bank of Credit (ABC)... You may decline or postpone. Storing is not transferring, and deposit is not an admission or an exemption." (`how.blade.php:34`)
  - Stage 5: "Credit transfer" - "You apply to an admitted programme; the faculty compares outcomes and decides course by course... It is not guaranteed, and the credit can be used only once." (`how.blade.php:35`)

---

### A.7. Frequently Asked Questions (FAQ)
- **Route**: `GET /public/faq` (`public.faq`)
- **View File**: [`app/resources/views/public/faq.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/faq.blade.php#L1-L101)
- **Controller Action**: `App\Modules\Offering\CatalogueController@faq` ([`CatalogueController.php:218-221`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L218-L221))
- **Purpose**: Interactive accordion FAQ answering learner and verifier questions across 4 distinct categories.
- **Exact Data Fields Shown**:
  - Category 1: `Badges & credentials` (3 Q&As) (`faq.blade.php:6-10`)
  - Category 2: `Credit & ACR` (4 Q&As) (`faq.blade.php:11-16`)
  - Category 3: `Registering & fees` (3 Q&As) (`faq.blade.php:17-21`)
  - Category 4: `Verification & privacy` (3 Q&As) (`faq.blade.php:22-26`)
- **Actions**: Tab category filter (`faq.blade.php:65-70`), expanding `<details>` accordion items (`faq.blade.php:75-80`), contact support link -> `mailto:codl@utem.edu.my`.

---

### A.8. Institutional Policies (Privacy, Terms, Accessibility)
- **Routes**:
  - `GET /public/privacy` (`public.privacy`) -> Privacy Notice (PDPA 2010)
  - `GET /public/terms` (`public.terms`) -> Terms of Use
  - `GET /public/accessibility` (`public.accessibility`) -> Accessibility Statement
- **View File**: [`app/resources/views/public/policy.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/policy.blade.php#L1-L26)
- **Controller Actions**:
  - `privacy()`: [`CatalogueController.php:223-236`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L223-L236)
  - `terms()`: [`CatalogueController.php:238-250`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L238-L250)
  - `accessibility()`: [`CatalogueController.php:252-263`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Offering/CatalogueController.php#L252-L263)
- **Exact Data Fields Shown**:
  - `$title`: Page heading.
  - `$sub`: Subtitle summary.
  - `$sections`: List of `[heading, text]` tuples describing statutory boundaries, data governance, and WCAG AA target.

---

### A.9. Credential Verification Portal (Search & File Upload)
- **Routes**: `GET /public/verify` & `GET /verify` (`public.verify`)
- **View File**: [`app/resources/views/public/verify.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/verify.blade.php#L1-L153)
- **Controller Actions**:
  - Form: `App\Modules\Credentials\VerifyController@form` ([`VerifyController.php:17-20`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Credentials/VerifyController.php#L17-L20))
  - File Upload Handler: `VerifyController@file` ([`VerifyController.php:108-143`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Credentials/VerifyController.php#L108-L143))
- **Purpose**: Dual verification hub for public employers, universities, and students.
- **Exact Form Fields & Actions**:
  1. Method 1 (Verify by Code):
     - Form: `onsubmit="location.href='/public/verify/' + code"` (`verify.blade.php:26`).
     - Field: `code` (Text, placeholder: `e.g. CND01-7K9MJ or BADGE-CND-M1-01`).
  2. Method 2 (Cryptographic File Verification):
     - Form: `POST /public/verify/file`, `enctype="multipart/form-data"` (`verify.blade.php:48`).
     - Field: `file` (File input, accepts `.json,.jwt,.txt`, max 256KB).
- **Cryptographic File Results Display**:
  - Checks against UTeM's Ed25519 (EdDSA) public key (`VerifyController.php:116`).
  - Detects tampering between outer document claims and cryptographically signed JWT payload (`VerifyController.php:127`).
  - Displays: Signature status (`Valid` / `Invalid`), key ID (`kid`), W3C Verifiable Credentials 2.0 / Open Badges 3.0 standards badge, credential name, recipient hash, issuer, issue date, and link to official record (`verify.blade.php:68-120`).

---

### A.10. Credential Verification Result
- **Routes**: `GET /public/verify/{code}` & `GET /verify/{code}` (`public.verify.show`)
- **View File**: [`app/resources/views/public/verify-result.blade.php`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/resources/views/public/verify-result.blade.php#L1-L640)
- **Controller Action**: `App\Modules\Credentials\VerifyController@show` ([`VerifyController.php:22-35`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Credentials/VerifyController.php#L22-L35))
- **Purpose**: Public proof of credential status, academic claims, module completion, and cryptographic artifacts.
- **Outcomes**:
  - `valid`: Fully active, authentic credential.
  - `superseded`: Corrected or reissued; displays prominent warning and clickable link to `replaced_by` code.
  - `revoked`: Cancelled credential; displays revocation date and reason.
  - `expired`: Past valid expiration date.
  - `not_found`: 404 response with retry code input form.
  - `badge`: Valid single module digital badge.
- **Exact Data Fields Shown**:
  - Recipient identity (masked or public per institution PDPA settings).
  - Credential title (`course_name_en`).
  - Category badge: `[UMC]`, `[SAMC]`, or `[MCE]`.
  - Academic claims: Credits, SLT Hours, MQF Level, Quality Verification status.
  - Modular completion breakdown: list of completed modules with sequence and SLT.
  - Cryptographic verification signature stamp and issuer public key reference.
- **Verifiable Artifact Downloads**:
  - `GET /public/verify/{code}/download/json` -> Downloads W3C JSON-LD credential (`VerifyController.php:38-106`).
  - `GET /public/verify/{code}/download/jwt` -> Downloads cryptographically signed JWT (`VerifyController.php:47-52`).
  - Print transcript -> `window.print()`.

---

## Section B: Sign-in & Authentication Inventory

---

### B.1. Routes & Controllers
- `GET /signin` (`signin`): `App\Modules\Identity\StubSignInController@index` ([`StubSignInController.php:17-27`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Identity/StubSignInController.php#L17-L27))
- `POST /signin/stub` (`signin.stub`): `StubSignInController@store` ([`StubSignInController.php:29-43`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Identity/StubSignInController.php#L29-L43))
- `GET /signin/mfa` (`signin.mfa`): `StubSignInController@mfa` ([`StubSignInController.php:45-53`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Identity/StubSignInController.php#L45-L53))
- `POST /signin/mfa` (`signin.mfa.check`): `StubSignInController@mfaCheck` ([`StubSignInController.php:55-63`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/app/Modules/Identity/StubSignInController.php#L55-L63))
- `POST /signout` (`signout`): `StubSignInController@destroy`

---

### B.2. Form Fields, Labels & Validation Rules
1. **Primary SSO Entry** (`signin.blade.php:433-448`):
   - Button: `Sign in with Microsoft (not connected in this build)` (disabled in prototype).
   - Guidance: Office 365 format explanation (`UserId@utem.edu.my` for staff, `MatricNo@student.utem.edu.my` for students).
2. **Prototype Test Sign-in** (`signin.blade.php:450-485`):
   - Modal trigger button: `Select a Test Persona (:count)` (`signin.blade.php:475`).
   - Modal search input: `id="persona-search"`, placeholder: `Search by name, email, role, or faculty (e.g. Amir, FTMK, Dean)...` (`signin.blade.php:510`).
   - Role filter pills: `all`, `student`, `lecturer`, `deputy_dean`, `academic_authority`, `qa_officer`, `records_custodian`, `codl_admin`, `industry_expert` (`signin.blade.php:520-565`).
   - Submission Form per Persona:
     - Method: `POST` to `route('signin.stub')`
     - Field: `<input type="hidden" name="user" value="{{ $p->id }}">`
     - Validation: `['user' => 'required|integer']` (`StubSignInController.php:31`).
3. **MFA 6-Digit Authenticator** (`mfa.blade.php:1-15`):
   - Form: `POST` to `route('signin.mfa')`
   - Field: `<input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>`
   - Validation: `['code' => ['required', 'regex:/^\d{6}$/']]` with message `'Enter the 6-digit code.'` (`StubSignInController.php:58`).

---

### B.3. Account Status & Refusal
- Inactive Account Check:
  ```php
  if ($user->status !== 'active') {
      $this->audit->record('auth.refused_inactive', $user, actorId: $user->id);
      return redirect()->route('signin')->withErrors(['user' => 'This account is deactivated. Ask the CODL administrator.']);
  }
  ```
  (`StubSignInController.php:32-35`).
- Displayed Error:
  - Error banner on signin page: `Sign-in failed:` -> `This account is deactivated. Ask the CODL administrator.` (`signin.blade.php:420-430`).

---

### B.4. MFA Requirements by Role
Staff roles that approve, verify, issue, deposit, or administer require mandatory second-factor verification (`Role.php:68-80`):
- **Requires MFA**:
  - `deputy_dean` / `ptj_authority` (P2)
  - `academic_authority` (P3)
  - `qa_officer` (P4)
  - `codl_admin` (P5)
  - `records_custodian` (P6)
  - `abc_depositor` (P7)
  - `records_depositor` (P6/P7 Legacy)
- **Bypasses MFA**:
  - `student` (P8)
  - `lecturer` (P1)
  - `industry_expert` / `reviewer`

---

### B.5. Post-Login Redirects
- If user attempted to access a protected URL before signing in (`$request->session()->pull('url.intended')`), the system verifies role permissions via `canAccessUrl()` (`StubSignInController.php:73-107`).
- Otherwise, redirects to the role's primary landing workspace (`Role.php:83-94`):
  - `codl_admin` -> `route('users')`
  - `industry_expert` / `reviewer` -> `route('reviews')`
  - `records_custodian` -> `route('credentials.admin')`
  - `abc_depositor` -> `route('deposits')`
  - `academic_authority` -> `route('approvals')`
  - `qa_officer` -> `route('monitoring')`
  - `student` / `lecturer` / `deputy_dean` -> `route('tasks')`

---

## Section C: Global Chrome on Public Pages

---

### C.1. Public Top Command Bar (`layouts/app.blade.php:207-308`)
- **Brand Element**:
  - Institutional Jawi + Roman UTeM Logo ([`Asset/LogoJawiUTeM_blue-01.png`](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/Asset/LogoJawiUTeM_blue-01.png)).
  - System title: `UTeM MCMS` with subtitle: `Public Verification & Credential Portal`.
  - Links to `route('public.home')`.
- **Right Action Area**:
  - Guest state: High-contrast `Sign in` button linking to `route('signin')`.
  - Authenticated state on public page: `Return to portal ->` button linking to user's home workspace.
  - Language toggle (`EN | BM`) in live app -> **Excluded in v3 (strictly English only)**.
  - Theme toggle (light/dark) in live app -> **Excluded in v3 (strictly light theme only)**.

---

### C.2. Public Tab Navigation Bar (`public/_nav.blade.php:1-11`)
Positioned flush directly beneath the header:
1. `Home` -> `route('public.home')`
2. `Catalogue` -> `route('public.catalogue')` (also active for `public.offering`)
3. `Pathways` -> `route('public.pathways')`
4. `Recognition stages` (How it works) -> `route('public.how')`
5. `FAQ` -> `route('public.faq')`
6. `Verify a credential` -> `route('public.verify')` (active for `public.verify*`)

---

### C.3. Public Footer (`public/_foot.blade.php:1-34`)
4-column structured layout:
- **Column 1: Identity & Location**:
  - CODL Logo (`Asset/CODL_logo.png` / `asset('images/codl-logo.png')`)
  - "UTeM Micro-credentials"
  - "Centre for Open and Distance Learning (CODL)"
  - "UTeM, Hang Tuah Jaya, 76100 Durian Tunggal, Melaka"
- **Column 2: Learn**:
  - All micro-credentials (`route('public.catalogue')`)
  - Degree pathways (`route('public.pathways')`)
  - Recognition stages (`route('public.how')`)
- **Column 3: Help**:
  - FAQ (`route('public.faq')`)
  - Verify a credential (`route('public.verify')`)
  - Support contact: `codl@utem.edu.my`
- **Column 4: Policies**:
  - Privacy notice (PDPA) (`route('public.privacy')`)
  - Terms of use (`route('public.terms')`)
  - Accessibility statement (`route('public.accessibility')`)
- **Bottom Statutory Statement**:
  - "Micro-credentials issued under the UTeM Micro-Credential Framework compliant with MQA Code of Practice for Programme Accreditation and Malaysian Qualifications Framework (MQF) 2nd Edition. Micro-credentials do not automatically grant university admission." (`public/_foot.blade.php:31-33`).

---

## Section D: Roles Inventory (`App\Modules\Identity\Role.php`)

| Role Code | Enum Value | Label (English) | Guide Appendix H Ref | MFA Required? | Primary Landing Route | Operational Scope |
|:---|:---|:---|:---:|:---:|:---|:---|
| `[P1]` | `lecturer` | MC Coordinator / Lecturer | P1 | No | `tasks` | Faculty / PTj courses |
| `[P2]` | `deputy_dean`, `ptj_authority` | PTj / Faculty Authority | P2 | **Yes** | `tasks` | Faculty-wide |
| `[P3]` | `academic_authority` | Academic Authority / Senate | P3 | **Yes** | `approvals` | University-wide Senate |
| `[P4]` | `qa_officer` | Quality Assurance Officer | P4 | **Yes** | `monitoring` | University Quality Office |
| `[P5]` | `codl_admin` | CODL Platform Administrator | P5 | **Yes** | `users` | Platform Administration |
| `[P6]` | `records_custodian` | Records & Credential Custodian | P6 | **Yes** | `credentials.admin` | Registrar / Records Unit |
| `[P7]` | `abc_depositor` | Authorized ABC Depositor | P7 | **Yes** | `deposits` | National ABC Gateway |
| `[P8]` | `student` | Student / Participant | P8 | No | `tasks` (My Learning) | Personal learner record |
| - | `industry_expert`, `reviewer` | Industry SME / Reviewer | External | No | `reviews` | Assigned course reviews |

---

## Section E: English User-Visible Strings Inventory

Every string on these pages was verified from the Blade views and controllers:

1. **Header & Navigation (`layouts/app.blade.php`, `public/_nav.blade.php`)**:
   - "UTeM MCMS"
   - "Public Verification & Credential Portal"
   - "Sign in"
   - "Return to portal"
   - "Home", "Catalogue", "Pathways", "Recognition stages", "FAQ", "Verify a credential"
2. **Landing Page (`public/home.blade.php`)**:
   - "Universiti Teknikal Malaysia Melaka"
   - "Micro-Credential Portal"
   - "Stackable, verifiable modular learning accredited by UTeM and aligned with the Malaysian Qualifications Framework (MQF)."
   - "Search offerings", "Keyword, field or code...", "Search Catalogue"
   - "UMC – Unbundled Micro-Credential", "Credit Status: Yes, via official ACR record"
   - "SAMC – Standalone Micro-Credential", "Credit Status: Only with valid Quality Verification"
   - "MCE – Micro-Credential Eksekutif", "Credit Status: None (Certificate of Competency)"
   - "Upcoming Cohorts", "Credits", "Delivery", "Faculty", "View Offering"
   - "Verify Credentials & Badges", "Check Credential"
3. **Catalogue Page (`public/catalogue.blade.php`)**:
   - "Filters", "Keyword search", "Category", "All categories", "Faculty / PTj", "Delivery mode", "Discipline / Field", "Earliest start date", "Maximum fee", "Free offerings only", "Reset filters"
   - "Showing :count micro-credentials"
   - "Free", "RM :amount", "Details & Enrol"
   - "No micro-credentials matched your search criteria. Try clearing filters or using different keywords."
4. **Offering Page (`public/offering.blade.php`)**:
   - "Overview", "Learning Outcomes", "Modular Breakdown", "Assessment & Integrity Standards", "Credit and Academic Recognition Notice"
   - "Credit transfer eligibility requires formal application and evaluation by the receiving institution. Micro-credentials do not automatically confer university admission or credit exemption."
5. **Pathways Pages (`public/pathways.blade.php`, `pathway-print.blade.php`)**:
   - "Academic Degree Pathways", "Explore stackable micro-credential pathways towards accredited UTeM degrees."
   - "Maximum Transferable Credit: 30% of total programme credits"
   - "Credit Transfer Notice: Storing credits in the Academic Bank of Credit (ABC) does not guarantee academic credit transfer. Exemption is subject to formal Senate evaluation."
   - "Print Official Pathway Advisory"
6. **Recognition Stages (`public/how.blade.php`)**:
   - "Recognition stages", "Five stages. Each one is decided separately and recorded."
   - "Each stage is a separate decision. One does not give you the next."
   - "Module badge", "Certificate or credential", "ACR (Academic Credit Record)", "ABC deposit", "Credit transfer"
   - "What it is", "What it is not", "Independent Decisions", "Browse offerings"
7. **FAQ (`public/faq.blade.php`)**:
   - "Frequently asked questions", "Answers about credentials, academic credit, registration and recognition"
   - "Badges & credentials", "Credit & ACR", "Registering & fees", "Verification & privacy"
   - "Have a question that is not covered here?", "Contact the Centre for Open & Digital Learning (CODL) at codl@utem.edu.my"
8. **Verification Pages (`public/verify.blade.php`, `verify-result.blade.php`)**:
   - "Verify Credentials & Digital Badges"
   - "Method 1: Verify by Code", "Method 2: Cryptographic File Verification"
   - "Select file (.json / .jwt / .txt)", "Verify File Signature"
   - "Verification result", "We could not find this credential."
   - "Valid", "Revoked", "Superseded", "Expired"
   - "Standard: W3C Verifiable Credentials 2.0 & Open Badges 3.0", "Algorithm: Ed25519 (EdDSA)"
   - "Download W3C JSON-LD", "Download Cryptographic JWT", "Print Official Verification Record"
9. **Sign-In & MFA (`signin.blade.php`, `mfa.blade.php`)**:
   - "UTeM Single Sign-On"
   - "Please log in using your UTeM's Office 365 email credentials:"
   - "UserId@utem.edu.my (for staff)", "MatricNo@student.utem.edu.my (for student)"
   - "Sign in with Microsoft (not connected in this build)"
   - "The connection to Microsoft Entra ID will be enabled once UTeM ICT completes institutional SSO integration."
   - "Test sign-in", "Select a Test Persona", "Search by name, email, role, or faculty"
   - "Sign-in failed:", "This account is deactivated. Ask the CODL administrator."
   - "Verify it is you", "Enter the 6-digit code.", "Verify"
