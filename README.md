# CBK Green Events Governance & Disclosure Platform (Kenya)
**A bank-grade, multi-tenant platform for sustainable event governance, evidence capture, and disclosure-ready reporting.**

> Status: **Early scaffold (Laravel base setup done)**  
> Next step: **Set up modular monolith domain structure + multi-tenant (database-per-bank) architecture**.

---

## Why this project exists
Kenyan banks are moving toward stronger **climate risk governance and disclosure readiness**. Events (workshops, trainings, conferences) are operationally “evidence-rich” and highly visible—perfect for building repeatable governance habits: procurement controls, documented waste handling, and traceable approvals.

This platform turns every event into an **audit-ready evidence pack** and generates a **CBK-aligned report** (Governance / Strategy / Risk Management / Metrics & Targets) with referenced artifacts.

---

## Core Outcomes
This system aims to help institutions:

- Plan events with **sustainability controls** (vendor, plastics, waste, transport)
- Enforce **procurement guardrails** (policy-based rules, exceptions, approvals)
- Capture **evidence** (contracts, receipts, photos, handover proof) with audit trails
- Generate **disclosure-ready reports** (PDF + JSON/CSV exports)
- Scale across many banks using **database-per-tenant** isolation

---

## Product Scope (High-level)
### Primary users
- Bank: ESG/Sustainability Lead
- Bank: Procurement Officer
- Bank: Event Owner (Admin / L&D / Corporate Affairs)
- Bank: Compliance/Risk Reviewer
- Auditor (internal/external)
- System/CBK workspace user (template governance, optional aggregates)

### Main modules (planned)
1. **System Workspace (Central DB)**
   - Tenant registry (banks)
   - Global reference data (vendor categories, factor versions)
   - Report templates and baseline control checklists
   - Integration registry (API/webhook metadata)

2. **Tenant Workspace (Per-bank DB)**
   - Identity & RBAC
   - Events lifecycle + approvals workflow
   - Vendor governance + Approved Vendor List (AVL)
   - Evidence locker (metadata + file pointers)
   - Waste & leakage controls + handover proof
   - Transport (aggregate capture)
   - Reporting (PDF + structured exports)
   - Integrations (REST APIs + webhooks)

---

## Architecture (Target)
### Multi-tenancy: database-per-bank
This project is designed for **strong isolation**:
- One **central system database** (tenants, templates, global configs)
- One **tenant database per bank** (events, vendors, evidence, reports, audit logs)

Why: this is easier to defend to bank security/compliance and scales cleanly.

### Two-panel UI (Filament)
- **System Panel**: central workspace (CBK/system administrators)
- **Tenant Panel**: per-bank workspace (bank staff)

### Modular monolith structure
We will use a modular monolith layout with domain folders:


> Note: Domain folders are planned but may not exist yet in the current repo state.

---

## Tech Stack (Target)
- **Backend:** Laravel (monolith, domain-driven structure)
- **Admin UI:** Filament v3 + Livewire v3
- **Database:** PostgreSQL
- **Multi-tenancy:** stancl/tenancy (database-per-tenant)
- **Queue:** Redis + Laravel Queues
- **Storage:** S3-compatible object storage (AWS S3 / MinIO)
- **Reports:** HTML → PDF via headless Chrome (queued job)
- **Exports:** JSON/CSV schemas for integrations
- **Quality:** Laravel Pint + static analysis (Larastan/PHPStan) + feature tests (Pest or PHPUnit)
- **Integrations:** REST APIs + Webhooks (versioned)

---

## Report Structure (CBK-aligned output)
The system generates an “Event Sustainability & Climate Disclosure Evidence Pack” that follows the four-pillar structure:

1. **Governance**
   - Roles and responsibilities
   - Policies and standards applied
   - Approval timeline & audit references

2. **Strategy**
   - Sustainability objectives for the event
   - Sustainable procurement approach
   - Vendor selection and trade-offs

3. **Risk Management**
   - Compliance checks (location-aware plastics mode)
   - Waste leakage controls and handover proof
   - Exceptions register

4. **Metrics & Targets**
   - Emissions estimate breakdown (versioned assumptions)
   - Transport mode split (aggregate)
   - Waste diversion outcomes
   - Data quality rating

Appendices contain:
- Procurement evidence index (POs, invoices, contracts)
- Waste handover receipts & photo proofs
- Audit trail extract
- Export references (CSV/JSON)

---

## Requirements Philosophy (for humans + AI agents)
This project is built to be **AI-agent friendly**, meaning:
- Clear boundaries between System DB and Tenant DB code
- Small, test-backed increments
- Immutable audit trails
- Consistent naming and IDs for evidence and reports

### “Done” means:
- Feature works
- Has basic tests (at least feature tests for critical flows)
- Produces audit log entries for privileged actions
- Meets tenant isolation requirements (no cross-tenant leakage)

---

## Development Setup (Local)
> Adjust these steps to your environment once you add tenancy + queues + storage.

### Prerequisites
- PHP 8.2+
- Composer
- Node.js + npm (for Vite if applicable)
- PostgreSQL 
- Redis (for queues)
- Optional: Docker (recommended)

### Install
```bash
composer install
cp .env.example .env
php artisan key:generate
```
