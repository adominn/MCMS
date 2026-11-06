# UTeM Micro-Credential Management System (MCMS)
**Centre for Open and Distance Learning (CODL) · Universiti Teknikal Malaysia Melaka (UTeM)**

The official academic gateway for proposal governance, quality verification, verifiable digital credentials, and Academic Bank of Credit (ABC) articulation.

---

## 🏛️ Project Overview

This repository contains the complete specification, clickable prototype, and production implementation of the **UTeM Micro-Credential Management System (MCMS)**, built in strict adherence to:
- *Garis Panduan Pembangunan dan Pengendalian Program Micro-Credential UTeM* (Senate-approved)
- MQA Guidelines to Good Practices: Micro-Credentials (GGP: MC)
- Academic Bank of Credit (ABC) regulations for credit accumulation and transfer
- IBM Carbon Design System guidelines (uLearn Navy `#1e2a6e` institutional theme)

---

## 📁 Repository Structure

```
.
├── app/                  # Full-stack Laravel 13 modular monolith web application
│   ├── app/Modules/      # Modular monolith domains (Curriculum, Offering, Enrolment, Credentials, CreditBank, Monitoring, etc.)
│   ├── database/         # Migrations, seeders (26 test personas, UCreds reference catalogue)
│   ├── resources/views/  # Plain Blade templates adhering to Carbon Design System
│   ├── tests/            # Automated test suite (Pest feature tests & Playwright E2E)
│   └── docker-compose.yml # MySQL 8.4 LTS & PostgreSQL 16 services
├── Asset/                # Official branding, logos (CODL, UTeM)
├── Document/             # Official specification & audit document suite:
│   ├── Audit/            # Role-Based Audit v1.0 & Guide Compliance and Verification Audit v2.0
│   ├── Decision log/     # Glossary & Decision Log (v0.2 – v0.9, D-01 through D-45)
│   ├── Diagrams/         # Complete System Flow Maps, ERD Book, and Database Schema
│   ├── PRD/              # Product Requirements Document (v1.0 – v1.2)
│   ├── SDD/              # System Design Document (v1.0 – v1.3)
│   ├── SRS/              # Software Requirements Specification (v1.0 – v1.3)
│   └── UIUX Spec/        # UI/UX Specification (v1.0 – v1.4)
├── UIUX/                 # Clickable, high-fidelity UI/UX HTML prototype (v2)
├── DESIGN.md             # Carbon design specifications, color tokens, and layout guidelines
├── HANDOVER.md           # Engineering history, module mapping, and architectural milestones
└── MC UTeM Guide.pdf     # Garis Panduan Micro-Credential UTeM (Senate Guide)
```

---

## 🚀 Quickstart Guide (Local Setup)

### Prerequisites
- **PHP**: 8.3 or higher
- **Composer**: 2.x
- **Node.js**: 20+ and npm
- **Docker**: Docker Desktop (for MySQL 8.4)

### 1. Start the Database
```bash
cd app
docker compose up -d mysql
```
*Note: MySQL runs on port `3306` with user `mcms:mcms` and database `mcms`.*

### 2. Install Dependencies & Setup Environment
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 3. Run Migrations & Seeders
```bash
php artisan migrate --seed
```
*Optional rich dataset:* To load the comprehensive demo database with full cross-faculty data, completed credentials, and ACR credit bank ledgers for all 26 personas across all 7 PTJs, run:
```bash
php artisan db:seed --class=ComprehensiveTestingSeeder
```
*Alternatively, you can import the pre-populated demo SQL dump directly into MySQL:*
```bash
docker exec -i app-mysql-1 mysql -u mcms -pmcms mcms < database/mcms_demo.sql
```

### 4. Serve the Application
```bash
php artisan serve --port=8110
```
Open **[http://localhost:8110](http://localhost:8110)** in your browser.

---

## 👥 Authentication & Test Personas

During development and demonstration, click **"Select a Test Persona"** on the sign-in page to authenticate as any of the 26 pre-configured users:
- **Student / Participant** (Enrolment, payment simulation, digital wallet, credit bank ledger, credit transfer)
- **Lecturer / Proposer** (Category determination, proposal workspace with 3 unbundling models, results entry, CQI)
- **Reviewer / Industry Expert** (Conflict of interest declaration, academic appraisal, tracked comments)
- **Deputy Dean (Academic)** (Senate endorsement, offering readiness confirmation, result verification, credential issuance, transfer evaluation)
- **CODL Administrator** (Reference data, UCreds exchange, audit trail verification, adapter logs)

*For staff personas, enter any 6 digits at the second-factor OTP prompt (e.g., `123456`).*

---

## 🧪 Testing & Quality Assurance

### Pest Feature Test Suite (Backend)
Run the 375 feature tests covering all SRS functional requirements, business rules, RBAC checks, and unbundling models:
```bash
cd app
php vendor/bin/pest
```
*Status: 375 passed, 5,345 assertions (100% green).*

### Playwright E2E Flows (Frontend)
```bash
cd app
npm run test:e2e
```

---

## 🔒 Security & Privacy Notice
- Sensitive configuration and keys are kept in `.env` (excluded via `.gitignore`).
- Never commit live credentials, API keys, or production session data.
- Use `.env.example` as the canonical environment template.
